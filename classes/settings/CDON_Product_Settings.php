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
        <div class="cdon__product-settings">
          <p class="cdon__section-header"><strong>CDON Product Specific settings</strong></p>
          <?php
          woocommerce_wp_checkbox(array(
            'id'     => 'cdon_export',
            'label'   => __('Export?', 'cdon'),
            'description' => 'Determines wether or not to export this product to CDON'
          ));
          ?>
        </div>
        <div class="cdon__default-overrides">
          <p class="cdon__section-header"><strong>Default overrides</strong></p>
          <?php
          woocommerce_wp_select(array(
            'id'     => 'cdon_category',
            'label'   => __('Category', 'cdon'),
            'options' =>  array_flip(CDON_CATEGORIES)
          ));
          ?>
        </div>
        <div class="cdon__last-exports">
          <p class="cdon__section-header"><strong>Last exports</strong></p>
          <?php
          foreach (FEEDS as $f) {
            $feed = strtolower($f);
            $exported_date = (get_post_meta(get_the_ID(), 'cdon_last_exported_' . $feed) ?: [false])[0];
          ?>
            <p style="margin: 0;"><?= ucfirst($feed) ?>: <b> <?= isset($exported_date) ? date(DATE_ISO8601, $exported_date) : 'Never' ?></b></p>
          <?php
          }
          ?>
        </div>
      </div>
    </div>
<?php
  }
  public function cdon_save_product_settings($post_id)
  {
    $export_cdon = isset($_POST['cdon_export']) ? 'yes' : 'no';
    update_post_meta($post_id, 'cdon_export', $export_cdon);
    
  }
  
  public function cdon_custom_query_var($query, $query_vars)
  {
    if (!empty($query_vars['cdon_export'])) {
      $query['meta_query'][] = array(
        'key' => 'cdon_export',
        'value' => esc_attr($query_vars['cdon_export']),
      );
    }
    return $query;
  }
}
