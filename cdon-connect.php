<?php

/**
 * Plugin Name: CDON Connect
 * Plugin URI: http://cdonmarketplace.com/
 * Description: Export products to CDON Connect.
 * Version: 1.0.0
 * Author: CDON AB
 * Author URI: http://cdonmarketplace.com/
 * License: MIT
 */

defined('ABSPATH') || die();

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    if (!class_exists('CDON')) {
        require_once('cdon-constants.php');
        require_once('assets/cdon-styles.php');
        require_once('classes/CDON.php');
        require_once('classes/CDON_Feed.php');
        require_once('classes/settings/WC_Settings_CDON.php');
        require_once('classes/settings/CDON_Product_Settings.php');

        $cdon = $GLOBALS['cdon'] = new CDON();
        $cdon->init();
    }
}
