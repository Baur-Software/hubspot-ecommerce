<?php
/**
 * Frontend functionality
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Frontend {

    private static $instance = null;
    private $template_loader;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->template_loader = HubSpot_Ecommerce_Template_Loader::instance();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_head', [$this, 'add_cart_count_to_menu']);

        // Shortcodes
        add_shortcode('hubspot_products', [$this, 'products_shortcode']);
        add_shortcode('hubspot_cart', [$this, 'cart_shortcode']);
        add_shortcode('hubspot_checkout', [$this, 'checkout_shortcode']);
        add_shortcode('hubspot_account', [$this, 'account_shortcode']);
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Styles
        wp_enqueue_style(
            'hubspot-ecommerce',
            HUBSPOT_ECOMMERCE_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            HUBSPOT_ECOMMERCE_VERSION
        );

        // Scripts
        wp_enqueue_script(
            'hubspot-ecommerce',
            HUBSPOT_ECOMMERCE_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            HUBSPOT_ECOMMERCE_VERSION,
            true
        );

        // Localize script
        $cart = HubSpot_Ecommerce_Cart::instance();

        wp_localize_script('hubspot-ecommerce', 'hubspotEcommerce', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hubspot_ecommerce_nonce'),
            'cart_count' => $cart->get_cart_item_count(),
            'cart_total' => $cart->get_cart_total(),
            'currency' => get_option('hubspot_ecommerce_currency', 'USD'),
        ]);
    }

    /**
     * Add cart count to menu
     */
    public function add_cart_count_to_menu() {
        ?>
        <style>
            .hubspot-cart-count {
                display: inline-block;
                background: #e74c3c;
                color: white;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 12px;
                margin-left: 5px;
                vertical-align: super;
            }
        </style>
        <?php
    }

    /**
     * Products shortcode
     */
    public function products_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'category' => '',
        ], $atts);

        $args = [
            'post_type' => 'hs_product',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        ];

        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'hs_product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ],
            ];
        }

        $query = new WP_Query($args);

        ob_start();
        $this->template_loader->get_template_part('shop/products', null, ['query' => $query]);
        return ob_get_clean();
    }

    /**
     * Cart shortcode
     */
    public function cart_shortcode() {
        ob_start();
        $this->template_loader->get_template_part('cart/cart');
        return ob_get_clean();
    }

    /**
     * Checkout shortcode
     */
    public function checkout_shortcode() {
        ob_start();
        $this->template_loader->get_template_part('checkout/checkout');
        return ob_get_clean();
    }

    /**
     * Account shortcode
     */
    public function account_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your account.', 'hubspot-ecommerce') . '</p>';
        }

        ob_start();

        // Check if viewing subscription preferences
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';

        if ($view === 'subscriptions') {
            $this->template_loader->get_template_part('account/subscription-preferences');
        } else {
            $this->template_loader->get_template_part('account/dashboard');
        }

        return ob_get_clean();
    }
}
