<?php


class CDON_Product_Settings
{
  public function add_cdon_product_tab($tabs)
  {
    $tabs['cdon'] = array(
      'label'    => __('CDON', 'cdon'),
      'target'  => 'cdon_options',
      'class'    => array('show_if_simple', 'show_if_variable'),
    );
    return $tabs;
  }
  public function add_cdon_product_settings()
  {
?>
    <div id='cdon_options' class='panel woocommerce_options_panel'>
      <div class="options-group">
        <p style="margin-bottom: 0; font-size: 14px;"><strong>Export to CDON</strong></p>
        <?php
        woocommerce_wp_checkbox(array(
          'id'     => 'cdon_export_product',
          'label'   => __('Product feed', 'cdon'),
          'description' => 'Titles, descriptions, categories and dimensions'
        ));
        woocommerce_wp_checkbox(array(
          'id'     => 'cdon_export_price',
          'label'   => __('Price feed', 'cdon'),
          'description' => 'Original- and salesprice, shipping cost and VAT'
        ));
        woocommerce_wp_checkbox(array(
          'id'     => 'cdon_export_availability',
          'label'   => __('Availability feed', 'cdon'),
          'description' => 'Stock- and status information'
        ));
        woocommerce_wp_checkbox(array(
          'id'     => 'cdon_export_media',
          'label'   => __('Media feed', 'cdon'),
          'description' => 'Images and videos'
        ));
        ?>
      </div>
    </div>
<?php
  }
  public function cdon_save_product_settings($post_id)
  {
    foreach (FEEDS as $feed) {
      $checked = isset($_POST['cdon_export_' . $feed]) ? 'yes' : 'no';
      update_post_meta($post_id, 'cdon_export_' . $feed, $checked);
    }
  }
  public function cdon_custom_query_var($query, $query_vars)
  {
    foreach (FEEDS as $feed) {
      if (!empty($query_vars['cdon_export_' . $feed])) {
        $query['meta_query'][] = array(
          'key' => 'cdon_export_' . $feed,
          'value' => esc_attr($query_vars['cdon_export_' . $feed]),
        );
      }
    }

    return $query;
  }
}
