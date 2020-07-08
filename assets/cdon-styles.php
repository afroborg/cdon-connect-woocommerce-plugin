<?php

function cdon_custom_styles()
{
?>
  <style>
    #cdon_options .cdon__section-header {
      margin-bottom: 0;
    }
    #cdon_options .cdon__last-exports {
      padding-bottom: 14px;
    }
    #cdon_options .form-field {
      margin-top: 0;
    }
    #woocommerce-product-data ul.wc-tabs li.cdon_tab a:before {
      background: url(<?= 'data:image/svg+xml;base64,' . base64_encode(cdon_c_logo('#0073aa')) ?>);
      content: " " !important;
      background-size: 100%;
      width: 13px;
      height: 13px;
      margin-top: 2px;
      display: inline-block;
      line-height: 1;
    }
    #woocommerce-product-data ul.wc-tabs li.cdon_tab:hover a:before {
      background: url(<?= 'data:image/svg+xml;base64,' . base64_encode(cdon_c_logo('#00a0d2')) ?>);
      content: " " !important;
      background-size: 100%;
      width: 13px;
      height: 13px;
      display: inline-block;
      line-height: 1;
    }
    #woocommerce-product-data ul.wc-tabs li.cdon_tab.active a:before {
      background: url(<?= 'data:image/svg+xml;base64,' . base64_encode(cdon_c_logo('#555')) ?>);
      content: " " !important;
      background-size: 100%;
      width: 13px;
      height: 13px;
      display: inline-block;
      line-height: 1;
    }

  </style>
<?php
}

function cdon_c_logo($color)
{
  return 
  '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="60" height="60" viewBox="0 0 60 60">
    <g id="placeholder" class="cls-1">
      <path fill="'.$color.'" id="placeholder-2" data-name="placeholder" class="cls-2" d="M27.994,30.729c-.329,10.555-7.849,12.444-13.283,12.444C7.3,43.173,0,39.285,0,23.173s7.3-20,14.711-20c5.434,0,12.9,1.889,13.283,12.444.11,2.834-1.263,3.611-3.458,3.611H21.023c-2.2,0-2.909-.611-3.623-3.333-.6-2.278-1.592-3-3.019-2.945-1.921.056-4.062,2.611-4.062,10.222S12.46,33.34,14.381,33.4c1.427.056,2.361-.667,3.019-2.945.769-2.722,1.428-3.333,3.623-3.333h3.513C26.731,27.118,28.1,27.9,27.994,30.729Z" transform="translate(16 6.826)" />
    </g>
  </svg>';
}
?>