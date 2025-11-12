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
        add_action('wp_footer', [$this, 'render_floating_cart_widget']);

        // Shortcodes
        add_shortcode('hubspot_products', [$this, 'products_shortcode']);
        add_shortcode('hubspot_cart', [$this, 'cart_shortcode']);
        add_shortcode('hubspot_checkout', [$this, 'checkout_shortcode']);
        add_shortcode('hubspot_account', [$this, 'account_shortcode']);
        add_shortcode('hubspot_buy_button', [$this, 'buy_button_shortcode']);
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
            'rest_url' => rest_url(),
            'nonce' => wp_create_nonce('hubspot_ecommerce_nonce'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
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

    /**
     * Buy button shortcode - Add to cart and optionally redirect to checkout
     *
     * Usage examples:
     * [hubspot_buy_button product_id="291" text="Sign Up Now" redirect="checkout"]
     * [hubspot_buy_button product_id="291" text="Add to Cart" show_quantity="1"]
     * [hubspot_buy_button product_id="291" text="Buy Now" redirect="checkout" icon="shopping-cart" icon_position="left"]
     * [hubspot_buy_button product="data-360" text="Get Started" redirect="checkout" class="custom-button-class"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function buy_button_shortcode($atts) {
        $atts = shortcode_atts([
            'product_id' => '',           // Product ID (numeric)
            'product' => '',              // Product slug (alternative to product_id)
            'text' => 'Add to Cart',      // Button text
            'redirect' => '',             // 'checkout' to redirect after adding to cart, empty for no redirect
            'show_quantity' => '',        // '1' or 'true' to show quantity selector
            'quantity' => '1',            // Default quantity
            'class' => '',                // Additional CSS classes
            'style' => '',                // Inline styles
            'icon' => '',                 // Icon class (e.g., 'shopping-cart', 'cart-plus')
            'icon_position' => 'left',    // 'left' or 'right'
        ], $atts);

        // Get product ID
        $product_id = 0;
        if (!empty($atts['product_id'])) {
            $product_id = intval($atts['product_id']);
        } elseif (!empty($atts['product'])) {
            // Look up product by slug
            $product = get_page_by_path($atts['product'], OBJECT, 'hs_product');
            if ($product) {
                $product_id = $product->ID;
            }
        }

        if (!$product_id) {
            return '<p class="error">' . __('Invalid product specified.', 'hubspot-ecommerce') . '</p>';
        }

        // Verify product exists
        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'hs_product') {
            return '<p class="error">' . __('Product not found.', 'hubspot-ecommerce') . '</p>';
        }

        // Check if quantity should be shown based on product meta
        $show_quantity = filter_var($atts['show_quantity'], FILTER_VALIDATE_BOOLEAN);
        $product_type = get_post_meta($product_id, '_product_type', true);
        $is_subscription = get_post_meta($product_id, '_is_subscription', true);

        // Don't show quantity for subscriptions or services by default
        if (!$atts['show_quantity'] && ($product_type === 'service' || $is_subscription)) {
            $show_quantity = false;
        }

        // Determine button class
        $button_classes = ['hs-buy-button'];
        if ($atts['redirect'] === 'checkout') {
            $button_classes[] = 'hs-add-to-cart-checkout';
        } else {
            $button_classes[] = 'add-to-cart-quick';
        }
        if (!empty($atts['class'])) {
            $button_classes[] = esc_attr($atts['class']);
        }

        // Build icon HTML
        $icon_html = '';
        if (!empty($atts['icon'])) {
            $icon_class = 'gp-icon icon-' . esc_attr($atts['icon']);
            $icon_html = '<i class="' . $icon_class . '" aria-hidden="true"></i> ';
        }

        // Build output
        ob_start();
        ?>
        <div class="hs-buy-button-wrapper" data-product-id="<?php echo esc_attr($product_id); ?>">
            <?php if ($show_quantity): ?>
                <div class="hs-quantity-wrapper">
                    <label for="quantity-<?php echo esc_attr($product_id); ?>" class="screen-reader-text">
                        <?php _e('Quantity', 'hubspot-ecommerce'); ?>
                    </label>
                    <input
                        type="number"
                        id="quantity-<?php echo esc_attr($product_id); ?>"
                        name="quantity"
                        value="<?php echo esc_attr($atts['quantity']); ?>"
                        min="1"
                        step="1"
                        class="hs-quantity-input"
                    />
                </div>
            <?php endif; ?>

            <button
                type="button"
                class="<?php echo esc_attr(implode(' ', $button_classes)); ?>"
                data-product-id="<?php echo esc_attr($product_id); ?>"
                <?php if (!empty($atts['style'])): ?>
                style="<?php echo esc_attr($atts['style']); ?>"
                <?php endif; ?>
            >
                <?php if ($icon_html && $atts['icon_position'] === 'left'): ?>
                    <?php echo $icon_html; ?>
                <?php endif; ?>

                <span class="button-text"><?php echo esc_html($atts['text']); ?></span>

                <?php if ($icon_html && $atts['icon_position'] === 'right'): ?>
                    <?php echo $icon_html; ?>
                <?php endif; ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render floating cart widget in footer
     */
    public function render_floating_cart_widget() {
        // Get cart instance and count
        $cart = HubSpot_Ecommerce_Cart::instance();
        $cart_count = $cart->get_cart_item_count();

        // Get customization settings
        $cart_icon = get_option('hubspot_ecommerce_cart_icon', 'shopping-cart');
        $cart_page = get_option('hubspot_ecommerce_cart_page');
        $cart_url = $cart_page ? get_permalink($cart_page) : home_url('/cart/');

        // Only show if count > 0 (controlled by CSS initially, then JS)
        $widget_class = $cart_count > 0 ? 'hs-floating-cart-widget' : 'hs-floating-cart-widget hidden';

        ?>
        <div class="<?php echo esc_attr($widget_class); ?>" id="hs-floating-cart">
            <a href="<?php echo esc_url($cart_url); ?>" class="hs-cart-link" aria-label="<?php esc_attr_e('View shopping cart', 'hubspot-ecommerce'); ?>">
                <i class="fa fa-<?php echo esc_attr($cart_icon); ?>" aria-hidden="true"></i>
                <span class="hs-cart-count-badge"><?php echo esc_html($cart_count); ?></span>
            </a>
        </div>
        <?php
    }
}
