<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Popcustoms_Admin_Dashboard {

    const API_KEY_SEARCH_STRING = 'Popcustoms';

    public static $_instance;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


    function __construct() {
        add_thickbox();
    }

    public static function view() {

        $dashboard = self::instance();
        $dashboard->render_dashboard();
    }

    public function render_dashboard() {

        $domain = isset($_REQUEST['domain']) ? wp_strip_all_tags($_REQUEST['domain']) : '';

        $errors = [
            'permalinks' => $this->check_permalinks(),
            'time_limit' => $this->check_time_limit(),
        ];

        if (in_array($domain, ['popcustoms.com', 'popcustoms.cn'])) {
            Popcustoms_Admin::save_domain($domain);
            $is_connected = Popcustoms_Integration::instance()->is_connected();
            $site_url = get_option('siteurl');
            $connect_url = 'https://popcustoms.com/stores/Woocommerce?domain='.esc_url($site_url);
            if ($domain == 'popcustoms.cn') {
                $connect_url = 'https://www.popcustoms.cn/integrations/stores/create?platform_id=4&platform=Woocommerce&domain='.esc_url($site_url);
            }
            Popcustoms_Admin::load_template( 'dashboard', compact('errors', 'domain', 'is_connected', 'connect_url'));
            return;
        }

        Popcustoms_Admin::load_template( 'entry');

    }

    /**
     * @return int
     */
    private function check_time_limit() {
        $time_limit = ini_get( 'max_execution_time' );

        if ( !$time_limit || $time_limit >= 120 ) {
            return '';
        }

        return 'The PHP execution time limit should be 120 seconds at least to support products with multiple variants and images. Contact your hosting provider regarding this.';
    }

    private function check_permalinks() {

        $permalinks = get_option( 'permalink_structure', false );

        if ( $permalinks && strlen( $permalinks ) > 0 ) {
            return '';
        }

        return 'Permalink is not setup correctly, please follow this <a href="https://woocommerce.com/document/woocommerce-rest-api/#section-1">guide</a>.';
    }

}