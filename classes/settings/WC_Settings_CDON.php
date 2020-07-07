<?php

function cdon_add_settings_page()
{

  class WC_Settings_CDON extends WC_Settings_Page
  {
    private $_categories;
    private $_post_attributes;

    public function __construct()
    {
      $this->id = 'cdon';
      $this->label = __('CDON', 'woocommerce');

      $this->_categories = $this->_get_categories();
      $this->_post_attributes = $this->_to_options_array($this->_post_attributes());

      add_filter('woocommerce_settings_tabs_array',        array($this, 'add_settings_page'), 20);
      add_action('woocommerce_settings_' . $this->id,      array($this, 'output'));
      add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
      add_action('woocommerce_sections_' . $this->id,      array($this, 'output_sections'));
    }

    public function output()
    {
      global $current_section;

      $settings = $this->get_settings($current_section);
      WC_Admin_Settings::output_fields($settings);
    }

    public function save()
    {
      global $current_section;

      $settings = $this->get_settings($current_section);
      WC_Admin_Settings::save_fields($settings);
    }

    public function get_sections()
    {

      $sections = array(
        ''         => 'General',
        'markets' => 'Markets',
        'categories' => 'Categories'
      );

      return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
    }

    public function get_settings($current_section = '')
    {
      $fields = [];
      if ($current_section == 'markets') {
        foreach (MARKETS as $key => $market) {
          $market_key = strtoupper($key);
          $fields[] = [
            'id' => 'wc_settings_cdon_markets',
            'name' => __($market, 'cdon'),
            'desc' => '<a href="https://cdon.' . $key .'" target="_blank" rel="norefferer">https://cdon.' . $key . '/</a>',
            'type' => 'title',
          ];
          $fields[] = [
            'id' => 'cdon_' . $key . '_enabled',
            'name' => __('Enabled', 'cdon'),
            'type' => 'checkbox',
            'desc' => 'Export products?'
          ];
          $fields[] = [
            'id' => 'cdon_' . $key . '_title',
            'name' => __('Title ' . $market_key, 'cdon'),
            'type' => 'select',
            'options' => $this->_post_attributes,
            'default' => 'name'
          ];
          $fields[] = [
            'id' => 'cdon_' . $key . '_description',
            'name' => __('Description ' . $market_key, 'cdon'),
            'type' => 'select',
            'options' => $this->_post_attributes,
            'default' => 'description'
          ];
          $fields[] = [
            'id' => 'cdon_' . $key . '_original_price',
            'name' => __('Original price ' . $market_key, 'cdon'),
            'type' => 'select',
            'options' => $this->_post_attributes,
            'default' => 'regular_price'
          ];
          $fields[] = [
            'id' => 'cdon_' . $key . '_sales_price',
            'name' => __('Sale price ' . $market_key, 'cdon'),
            'type' => 'select',
            'options' => $this->_post_attributes,
            'default' => 'sale_price',
            'desc' => 'The discounted price for your product (if any)'
          ];
          $fields[] = [
            'type' => 'sectionend',
            'id' => 'wc_settings_cdon_markets_end'
          ];
        };

        $settings = apply_filters('cdon_market_settings', $fields);
      } else if ($current_section == 'categories') {
        $fields[] = [
          'id' => 'wc_settings_cdon_categories',
          'name' => __('Category mappings', 'cdon'),
          'type' => 'title',
          'desc' => '<span>Map your Woocommerce categories to CDONs categories.</span><br/><span><strong>Note that</strong> all home electronics categories require a valid GTIN.</span>'
        ];
        foreach ($this->_categories as $category) {
          $fields[] = [
            'id' => 'cdon_category_' . $category->term_id,
            'name' => $category->name,
            'type' => 'select',
            'options' => array_flip(CDON_CATEGORIES),
            'default' => '',
            'desc_tip' => 'Choose a CDON Category'
          ];
        }
        $fields[] = [
          'type' => 'sectionend',
          'id' => 'wc_settings_cdon_categories_end'
        ];
        $settings = apply_filters('cdon_category_settings', $fields);
      } else {
        $fields[] = [
          'id' => 'wc_settings_cdon_general',
          'name' => __('General settings', 'cdon'),
          'type' => 'title',
        ];
        $fields[] = [
          'id' => 'cdon_product_id',
          'name' => __('Product Id', 'cdon'),
          'type' => 'select',
          'options' => $this->_post_attributes,
          'default' => 'ID',
          'desc' => 'This is the unique identifier that will be used on CDON'
        ];
        $fields[] = [
          'id' => 'cdon_sku',
          'name' => __('SKU', 'cdon'),
          'type' => 'select',
          'options' => ['' => '-- None --'] + $this->_post_attributes, true,
          'desc' => 'Stock Keeping Unit'
        ];
        $fields[] = [
          'id' => 'cdon_gtin',
          'name' => __('GTIN', 'cdon'),
          'type' => 'select',
          'options' => ['' => '-- None --'] + $this->_post_attributes, true,
          'desc' => 'EAN, JAN, ISBN or any other GTIN'
        ];
        $fields[] = [
          'type' => 'sectionend',
          'id' => 'wc_settings_cdon_general_end'
        ];
        $settings = apply_filters('cdon_general_settings', $fields);
      }
      return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
    }
    private function _to_options_array($arr)
    {
      $finished_array = [];
      foreach ($arr as $v) {
        $finished_array[$v] = $v;
      }
      return $finished_array;
    }

    private function _post_attributes()
    {
      $posts = get_posts([
        'post_type' => 'product',
        'posts_per_page' => 1
      ]);

      if (count($posts) <= 0)
        return ['N/A'];

      $product_data = (new WC_Product($posts[0]->ID))->get_data();
      return array_keys($product_data);
    }

    private function _get_categories()
    {
      $orderby = 'name';
      $order = 'asc';
      $hide_empty = false;
      $cat_args = array(
        'orderby'    => $orderby,
        'order'      => $order,
        'hide_empty' => $hide_empty,
      );

      $product_categories = get_terms('product_cat', $cat_args);
      return $product_categories;
    }
  }
  return new WC_Settings_CDON();
}
