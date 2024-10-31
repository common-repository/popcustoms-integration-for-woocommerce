<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Popcustoms_Shipping extends WC_Shipping_Method
{
    public $show_warnings = false;
    public $calculate_tax = false;
    public $override_defaults = true;
    public $ratio = '';
    private $last_error = false;

    const POPCUSTOMS_SHIPPING = 'popcustoms_shipping';

    private $popcustoms_package = true;

    public static function init() {
        new self;
    }

    public function __construct() {

        $this->id                 = 'popcustoms_shipping';
        $this->method_title       = $this->title = __('Popcustoms Shipping', 'popcustoms');
        $this->method_description = __('Calculate live shipping rates based on actual Popcustoms shipping costs.', 'popcustoms');

        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_options' ) );

        $this->enabled           = $this->get_option( 'enabled' );
        $this->show_warnings     = $this->get_option( 'show_warnings' ) == 'yes';
        $this->override_defaults = $this->get_option( 'override_defaults' ) == 'yes';
        $this->ratio = $this->get_option( 'ratio' );

        add_filter( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ), 10000 );
        add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ), 10000 );

        add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'woocommerce_cart_shipping_packages' ), 10000 );
    }

    public function init_form_fields() {

        $this->form_fields = array(
            'enabled'           => array(
                'title'   => __( 'Enable/Disable', 'popcustoms' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this shipping method', 'popcustoms' ),
                'default' => 'no',
            ),
            'ratio' => array(
                'title'   => __( 'Shipping Fee Ratio', 'popcustoms' ),
                'type'    => 'decimal',
                'description'   => __( 'Our shipping fee is in USD. The shipping fee will multiple this value and be rounded to integer. To reset, please leave it blank.', 'popcustoms' ),
                'default' => '',
            ),
            'override_defaults' => array(
                'title'   => __( 'Disable Woocommerce rates', 'popcustoms' ),
                'type'    => 'checkbox',
                'label'   => __( 'Disable standard Woocommerce rates for products fulfilled by Popcustoms', 'popcustoms' ),
                'default' => 'yes',
            ),
            'show_warnings'     => array(
                'title'   => __( 'Show Popcustoms warnings', 'popcustoms' ),
                'type'    => 'checkbox',
                'label'   => __( 'Display Popcustoms status messages if shipping rates API request fails', 'popcustoms' ),
                'default' => 'yes',
            ),
        );

    }

    public function woocommerce_load_shipping_methods( $package = array() ) {

        $this->popcustoms_package = false;

        if ( $package && ! empty( $package['popcustoms'] ) ) {
            if ( $this->enabled == 'yes' ) {
                $this->popcustoms_package = true;
                if ( $this->override_defaults ) {
                    WC()->shipping()->unregister_shipping_methods();
                }
                WC()->shipping()->register_shipping_method( $this );
            }
        } else if ( ! $package ) {
            WC()->shipping()->register_shipping_method( $this );
        }
    }


    public function woocommerce_shipping_methods( $methods ) {

        if ( $this->override_defaults && $this->popcustoms_package && version_compare( WC()->version, '2.6', '<' ) ) {
            return [];
        }

        return $methods;
    }

    public function woocommerce_cart_shipping_packages( $packages = array() ) {
        if ( $this->enabled !== 'yes' ) {
            return $packages;
        }

        $return_packages = [];

        foreach ( $packages as $package ) {

            $new_contents = array(
                'popcustoms' => array(),
                'virtual' => array(),
                'woocommerce' => array(),
            );

            foreach ( $package['contents'] as $key => $item ) {
                $code = get_post_meta($item['product_id'], '_pop_basic_code', true);
                if ($code) {
                    $new_contents['popcustoms'][$key] = $item;
                    continue;
                }

                if ($item['data']->is_virtual() || $item['data']->is_downloadable()) {
                    $new_contents['virtual'][$key] = $item;
                    continue;
                }

                $new_contents['woocommerce'][$key] = $item;
            }


            if ( $new_contents['virtual'] ) {
                if ( $new_contents['popcustoms'] && ! $new_contents['woocommerce'] ) {
                    $new_contents['popcustoms'] += $new_contents['virtual'];
                } else {
                    $new_contents['woocommerce'] += $new_contents['virtual'];
                }
                unset ( $new_contents['virtual'] );
            }

            foreach ( $new_contents as $key => $contents ) {
                if ( $contents ) {
                    $new_package                  = $package;
                    $new_package['contents_cost'] = 0;
                    $new_package['contents']      = $contents;
                    foreach ( $contents as $item ) {
                        if ( $item['data']->needs_shipping() ) {
                            if ( isset( $item['line_total'] ) ) {
                                $new_package['contents_cost'] += $item['line_total'];
                            }
                        }
                    }
                    if ( $key == 'popcustoms' ) {
                        $new_package['popcustoms'] = true;
                    }
                    $return_packages[] = $new_package;
                }
            }
        }

        return $return_packages;
    }

    public function calculate_shipping( $package = array() ) {
        $data = $this->getData($package);

        if (!$data) {
            return false;
        }

        $content = json_encode($data);

        $signature = hash_hmac('sha256', $content, 'qHYTYnVFI4BxCoac');

        $key = 'popcommerce_rates_' . $signature;
        $result = get_transient( $key );
        if ( $result === false ) {
            $result = wp_remote_post('https://i.popcustoms.com/api/v1/shipping_fee', [
                'sslverify' => false,
                'body' => $content,
                'data_format' => 'body',
                'headers' => [
                    'content-type' => 'application/json',
                    'accept' => 'application/json',
                ]
            ]);

            if (is_wp_error($result)) {
                $this->set_error(new \Exception($result->get_error_message()));
                return false;
            }

        }
        $body = wp_remote_retrieve_body($result);

        $body = json_decode($body, true);

        if (isset($body['message']) && $body['message']) {
            $this->set_error(new \Exception($body['message']));
            return false;
        }

        if (isset($body['data']['error']) && $body['data']['error']) {
            $this->set_error(new \Exception($body['data']['error']));
            return false;
        }

        $subtotal = $package['cart_subtotal'];
        foreach ($body['data']['quotes'] as $q) {
            $key = $this->id.$q['type'];
            if (isset($this->rates[$key])) {
                continue;
            }
            $rate = [
                'id'             => $key,
                'label'          => ucfirst($q['type']).' Shipping ('.$q['delivery_range'].' working days)',
                'cost'           => bcadd(isset($q['vat_price']) ? $q['vat_price'] : 0,  $q['shipping_fee'], 2),
                'meta_data' => [
                    'popcustoms_shipping_method' => $q['shipping_method']
                ],
            ];


            if (is_numeric($this->ratio) && $this->ratio > 0) {
                $rate['cost'] = bcmul($rate['cost'], $this->ratio, 2);
            }

            $this->add_rate($rate);
        }

        return true;
    }

    private function getData($package) {

        $data = [
            'country_code' => $package['destination']['country'],
            'state' => $package['destination']['state'],
            'zip_code' => $package['destination']['postcode'],
            'skus' => [],
            'is_integration' => true,
            "currency_code" => get_woocommerce_currency()
        ];
        $skus = [];
        foreach ($package['contents'] as $content) {
            $code = get_post_meta($content['product_id'], '_pop_basic_code');
            if (isset($code[0])) {
                $skus[] = [
                    'quantity' => $content['quantity'],
                    'sku' => $code[0],
                    'size' => isset($content['variation']['attribute_size']) ? $content['variation']['attribute_size'] : '',
                ];
            }
        }

        if (!$skus) {
            return false;
        }

        $data['skus'] = $skus;

        return $data;
    }

    /**
     * @param $error
     */
    private function set_error( $error ) {
        if ( $this->show_warnings ) {
            $this->last_error = $error;
            add_filter( 'woocommerce_cart_no_shipping_available_html', array( $this, 'show_error' ) );
            add_filter( 'woocommerce_no_shipping_available_html', array( $this, 'show_error' ) );
        }
    }

    public function show_error( $data ) {
        $error   = $this->last_error;
        $message = $error->getMessage();

        return '<p>ERROR: ' . htmlspecialchars( $message ) . '</p>';
    }

    private function isBillingPhoneNumberRequired()
    {
        return get_option('woocommerce_checkout_phone_field', 'required') === 'required';
    }
}
