<?php
/**
Plugin Name: Popcustoms Integration for WooCommerce
Plugin URI: https://wordpress.org/plugins/popcustoms-for-woocommerce/
Description: Connects your Popcustoms account with WooCommerce.
Version: 1.1.0
Author: Popcustoms
Author URI: http://www.popcustoms.com
Text Domain: popcustoms
License: GPL3 https://www.gnu.org/licenses/gpl-3.0.en.html
WC requires at least: 3.0.0
WC tested up to: 6.4
*/
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

class Popcustoms_Base {

    const VERSION = '1.0';

    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    public function init() {

        if (!class_exists('WC_Integration')) {
            return;
        }
        load_plugin_textdomain( 'popcustoms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/' );
        require_once __DIR__.'/includes/admin.php';
        require_once __DIR__.'/includes/admin-dashboard.php';
        require_once __DIR__.'/includes/integration.php';
	    require_once __DIR__.'/includes/shipping.php';
        require_once __DIR__.'/includes/template-designer.php';

	    Popcustoms_Shipping::init();
	    Popcustoms_Template_Designer::init();
        Popcustoms_Admin::init();

    }
}

new Popcustoms_Base();