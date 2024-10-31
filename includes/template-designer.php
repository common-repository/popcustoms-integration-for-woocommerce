<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Popcustoms_Template_Designer
{
    public static function init() {
    	new self;
    }

	public function __construct() {
	    add_filter('the_content', [$this, 'addTemplateDesignerHtml'], 1000);
        add_action('wp_footer', [$this, 'addTemplateDesignerJs']);
        add_filter('woocommerce_add_cart_item_data', [$this, 'addTemplateDesignerHashToCart']);
        add_action('woocommerce_add_order_item_meta', [$this, 'addTemplateDesignerHashToOrderItem'] , 10, 3 );
        add_action('woocommerce_after_cart_item_name', [$this, 'displayPopTemplateDesignerInCartPage']);
        add_filter('woocommerce_cart_item_price_html', [$this, 'displayPopTemplateDesignerInDropdownCart'], 10, 3);
        add_filter('woocommerce_widget_cart_item_quantity', [$this, 'displayPopTemplateDesignerInMiniCart'], 10, 2);
        add_action('woocommerce_cart_calculate_fees', [$this, 'addExtraCharge']);
        add_action('woocommerce_get_item_data', [$this, 'additionalInfo'], 10, 2);
        add_filter('woocommerce_ajax_variation_threshold', [$this, 'variationThreshold'], 9999);
	}

    public function variationThreshold()
    {
        return 200;
    }

    public function additionalInfo($item_data, $cart_item)
    {
        if (isset($cart_item['_pop_additional_info'])) {
            $item_data[] = [
                'key' => 'Personalization',
                'display' => $cart_item['_pop_additional_info']
            ];
        }

        return $item_data;
    }

	public function addTemplateDesignerHtml($content) {
	    global $post, $product;

	    if (!$post || $post->post_type !== 'product') {
	        return $content;
	    }

	    $info = get_post_meta($post->ID, '_pop_product_info', true);

	    if (!isset($info['support_template_designer']) || !$info['support_template_designer']) {
	        return $content;
	    }

	    if (!isset($info['template_designer_html']) || !$info['template_designer_html']) {
	        return $content;
	    }

	    $content = preg_replace('@<span.*?class="pop-support-template-designer".*?></span>@', '', $content);

	    $content = $content.$info['template_designer_html'];

	    if ($product && $product->product_type === 'simple') {
            $content = str_replace('span data-pop-store', 'span data-pop-sku="'.$product->sku.'" data-pop-store', $content);
        }

        return $content;
	}

	public function addTemplateDesignerJs()
	{
		wp_enqueue_script('popcustoms-template-designer-js', 'https://template-designer.popcustoms.com/template-designer.js');
	}

    public function addExtraCharge($cart)
    {
        $charge = 0;
        foreach ($cart->get_cart_contents() as $item) {
            if (isset($item['_pop_charge']) && is_numeric($item['_pop_charge'])) {
                $charge = bcadd($charge, bcmul($item['_pop_charge'], $item['quantity'], 2), 2);
            }
        }

        if ($charge) {
            $cart->add_fee( 'Personalization Fee', $charge, false, '' );
        }
    }

	public function addTemplateDesignerHashToCart(array $cart_item_data)
	{
	    if (isset($_POST['properties'])) {
            if (isset($_POST['properties']['pop'])) {
                $cart_item_data['_pop'] = $_POST['properties']['pop'];
            }

            if (isset($_POST['properties']['pop_charge']) && is_numeric($_POST['properties']['pop_charge'])) {
                $cart_item_data['_pop_charge'] = $_POST['properties']['pop_charge'];
            }

            if (isset($_POST['properties']['pop_additional_info']) && is_string($_POST['properties']['pop_additional_info'])) {
                $cart_item_data['_pop_additional_info'] = $_POST['properties']['pop_additional_info'];
            }
	    }

	    return $cart_item_data;
	}

	public function addTemplateDesignerHashToOrderItem($item_id, $cart_item, $cart_item_key)
    {
        if (isset($cart_item['_pop'])) {
            wc_add_order_item_meta( $item_id, '_pop', $cart_item['_pop']);
            return ;
        }
    }

    public function displayPopTemplateDesignerInCartPage($item)
    {
        if (isset($item['_pop'])) {
            echo '<span style="display:none">pop:'.$item['_pop'].'</span>';
        }

        return $item;
    }

    public function displayPopTemplateDesignerInDropdownCart($price, $item)
    {
        $html = '';
        if (isset($item['_pop'])) {
            $html .= '<span style="display:none">pop:'.$item['_pop'].'</span>';
        }
        return $price.$html;
    }

    public function displayPopTemplateDesignerInMiniCart($html, $item)
    {
        if (isset($item['_pop'])) {
            $html .= '<span style="display:none">pop:'.$item['_pop'].'</span>';
        }

        return $html;
    }

}