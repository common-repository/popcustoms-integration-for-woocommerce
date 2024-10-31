<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Popcustoms_Integration
{
    public static $_instance;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
        self::$_instance = $this;
    }

    public function is_connected() {

        $site_url = get_option('siteurl');

        $domain = Popcustoms_Admin::get_domain();

        $response = wp_remote_get('https://i.'.$domain.'/api/v1/stores/check_woocommerce_connected?domain='.$site_url);

        $status_code = wp_remote_retrieve_response_code($response);

        return $status_code === 200 ? true : false;
    }

}