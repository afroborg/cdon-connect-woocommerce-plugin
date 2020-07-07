<?php

class CDON
{
  private $_product_settings;

  public function __construct()
  {
    $this->_product_settings = new CDON_Product_Settings();
  }

  public function init()
  {
    $this->_initialize_filters();
    $this->_initialize_actions();
  }

  private function _initialize_filters()
  {
    // Add CDON tab to Woocommerce settings page
    add_filter('woocommerce_get_settings_pages', 'cdon_add_settings_page', 15);

    // Add CDON tab to Product editor
    add_filter('woocommerce_product_data_tabs', [$this->_product_settings, 'add_cdon_product_tab']);

    // Add CDON fields to CDON tab in Product editor
    add_filter('woocommerce_product_data_panels', [$this->_product_settings,  'add_cdon_product_settings']);

    // Allow filter of 'cdon_export in WC_Product_Query'
    add_filter('woocommerce_product_data_store_cpt_get_products_query', [$this->_product_settings, 'cdon_custom_query_var'], 10, 2);
  }

  private function _initialize_actions()
  {
    // Load custom styles to admin head
    add_action('admin_head', 'cdon_custom_styles');

    // Configure feed endpoints
    add_action('rest_api_init', [$this, 'cdon_rest_routes']);

    // Save custom CDON fields
    add_action('woocommerce_process_product_meta', [$this->_product_settings, 'cdon_save_product_settings']);


  }

  public function cdon_rest_routes()
  {
    register_rest_route('wc/cdon', 'feeds/(?P<feed>(product|price|availability|media))', [
      'methods' => 'GET',
      // 'permission_callback' => function () {
      //   return current_user_can('read');
      // },
      'callback' => function ($data) {
        $feed_type = $data['feed'];
        $feed_generator = new CDON_Feed($feed_type, $data->get_param('all'), $data->get_param('remove'));
        $feed_data = $feed_generator->create_feed();

        echo ($feed_data);
        return new WP_REST_Response(
          null,
          200,
          ['Content-Type' => 'application/xml']
        );
      }
    ]);
  }
}
