<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Popcustoms_Admin {

    const MENU_TITLE_TOP = 'Popcustoms';
    const PAGE_TITLE_DASHBOARD = 'Dashboard';
    const MENU_TITLE_DASHBOARD = 'Dashboard';
    const MENU_SLUG_DASHBOARD = 'popcustoms-dashboard';
    const CAPABILITY = 'manage_options';
    const OPTIONS_KEY = 'popcustoms_options';

    public static function init() {
        $admin = new self;
        $admin->register_admin();
    }

    public function register_admin() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu_page' ) );
    }

    public function register_admin_menu_page() {

        add_menu_page(
            __( 'Dashboard', 'popcustoms' ),
            self::MENU_TITLE_TOP,
            self::CAPABILITY,
            self::MENU_SLUG_DASHBOARD,
            array( 'Popcustoms_Admin', 'route' ),
            '',
            58
        );
    }

    public static function save_domain(string $domain)
    {
        if (in_array($domain, ['popcustoms.com', 'popcustoms.cn'])) {
            $options = get_option(self::OPTIONS_KEY, []);
            $options['domain'] = $domain;
            update_option(self::OPTIONS_KEY, $options);
        }
    }

    public static function get_domain()
    {
        $options = get_option(self::OPTIONS_KEY, []);

        $default = 'popcustoms.com';

        $domain = isset($options['domain']) ? $options['domain'] : $default;

        if (in_array($domain, ['popcustoms.com', 'popcustoms.cn'])) {
            return $domain;
        }

        return $default;
    }

    public static function route() {

        call_user_func( array( 'Popcustoms_Admin_Dashboard', 'view' ) );
    }

    public static function load_template( $name, $variables = array() ) {

        if ( ! empty( $variables ) ) {
            extract( $variables );
        }

        $filename = plugin_dir_path( __FILE__ ) . 'templates/' . $name . '.php';
        if ( file_exists( $filename ) ) {
            include( $filename );
        }
    }

}