<?php

function cdon_custom_styles()
{
?><style>
    #woocommerce-product-data ul.wc-tabs li.cdon_tab a:before {
      
      /* Set CDON C as icon for product details tab */
      background: url(<?= 'data:image/png;base64,' . base64_encode(file_get_contents(plugin_dir_path(__FILE__) . 'images/cdon-c.png')) ?>);
      content: " " !important;
      background-size: 100%;
      width: 13px;
      height: 13px;
      display: inline-block;
      line-height: 1;
    }
  </style><?php
        }

          ?>