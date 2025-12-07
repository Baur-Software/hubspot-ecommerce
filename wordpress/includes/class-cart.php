<?php
/**
 * Shopping Cart Management
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Cart {

    private static $instance = null;
    private $session_id;
    private $cart_items = [];

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_session();
        $this->load_cart();

        // AJAX actions
        add_action('wp_ajax_hs_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_hs_add_to_cart', [$this, 'ajax_add_to_cart']);

        add_action('wp_ajax_hs_update_cart', [$this, 'ajax_update_cart']);
        add_action('wp_ajax_nopriv_hs_update_cart', [$this, 'ajax_update_cart']);

        add_action('wp_ajax_hs_remove_from_cart', [$this, 'ajax_remove_from_cart']);
        add_action('wp_ajax_nopriv_hs_remove_from_cart', [$this, 'ajax_remove_from_cart']);

        add_action('wp_ajax_hs_get_cart', [$this, 'ajax_get_cart']);
        add_action('wp_ajax_nopriv_hs_get_cart', [$this, 'ajax_get_cart']);
    }

    /**
     * Initialize session
     */
    private function init_session() {
        if (!isset($_COOKIE['hubspot_ecommerce_session'])) {
            $this->session_id = $this->generate_session_id();

            // Set secure cookie with proper flags
            $secure = is_ssl();
            $httponly = true;
            $samesite = 'Lax';

            // PHP 7.3+ supports samesite in setcookie
            if (PHP_VERSION_ID >= 70300) {
                setcookie(
                    'hubspot_ecommerce_session',
                    $this->session_id,
                    [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'domain' => '',
                        'secure' => $secure,
                        'httponly' => $httponly,
                        'samesite' => $samesite,
                    ]
                );
            } else {
                // Fallback for older PHP versions
                setcookie(
                    'hubspot_ecommerce_session',
                    $this->session_id,
                    time() + (86400 * 30),
                    '/; samesite=' . $samesite,
                    '',
                    $secure,
                    $httponly
                );
            }
        } else {
            // Validate session ID format (alphanumeric only)
            $this->session_id = preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE['hubspot_ecommerce_session']);
            if (strlen($this->session_id) !== 32) {
                // Invalid session ID, generate new one
                $this->session_id = $this->generate_session_id();
            }
        }
    }

    /**
     * Generate a cryptographically secure random session ID
     *
     * @return string 32-character hexadecimal session ID
     */
    private function generate_session_id() {
        // Use random_bytes for cryptographically secure random data
        // 16 bytes = 32 hex characters
        return bin2hex(random_bytes(16));
    }

    /**
     * Load cart from database
     */
    private function load_cart() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$table_name}` WHERE session_id = %s",
            $this->session_id
        ));

        foreach ($items as $item) {
            $this->cart_items[$item->product_id] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'hubspot_product_id' => $item->hubspot_product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        }
    }

    /**
     * Add item to cart
     */
    public function add_to_cart($product_id, $quantity = 1) {
        global $wpdb;

        $product_manager = HubSpot_Ecommerce_Product_Manager::instance();
        $price = $product_manager->get_product_price($product_id);
        $hubspot_product_id = $product_manager->get_hubspot_product_id($product_id);

        if (!$hubspot_product_id) {
            return new WP_Error('invalid_product', __('Invalid product', 'hubspot-ecommerce'));
        }

        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        // Check if item already exists
        if (isset($this->cart_items[$product_id])) {
            // Update quantity
            $new_quantity = $this->cart_items[$product_id]['quantity'] + $quantity;

            $wpdb->update(
                $table_name,
                ['quantity' => $new_quantity],
                ['id' => $this->cart_items[$product_id]['id']],
                ['%d'],
                ['%d']
            );

            $this->cart_items[$product_id]['quantity'] = $new_quantity;
        } else {
            // Insert new item
            $wpdb->insert(
                $table_name,
                [
                    'session_id' => $this->session_id,
                    'product_id' => $product_id,
                    'hubspot_product_id' => $hubspot_product_id,
                    'quantity' => $quantity,
                    'price' => $price,
                ],
                ['%s', '%d', '%s', '%d', '%f']
            );

            $this->cart_items[$product_id] = [
                'id' => $wpdb->insert_id,
                'product_id' => $product_id,
                'hubspot_product_id' => $hubspot_product_id,
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        do_action('hubspot_ecommerce_cart_item_added', $product_id, $quantity);

        return true;
    }

    /**
     * Update cart item quantity
     */
    public function update_cart_item($product_id, $quantity) {
        global $wpdb;

        if (!isset($this->cart_items[$product_id])) {
            return false;
        }

        if ($quantity <= 0) {
            return $this->remove_from_cart($product_id);
        }

        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        $wpdb->update(
            $table_name,
            ['quantity' => $quantity],
            ['id' => $this->cart_items[$product_id]['id']],
            ['%d'],
            ['%d']
        );

        $this->cart_items[$product_id]['quantity'] = $quantity;

        do_action('hubspot_ecommerce_cart_item_updated', $product_id, $quantity);

        return true;
    }

    /**
     * Remove item from cart
     */
    public function remove_from_cart($product_id) {
        global $wpdb;

        if (!isset($this->cart_items[$product_id])) {
            return false;
        }

        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        $wpdb->delete(
            $table_name,
            ['id' => $this->cart_items[$product_id]['id']],
            ['%d']
        );

        unset($this->cart_items[$product_id]);

        do_action('hubspot_ecommerce_cart_item_removed', $product_id);

        return true;
    }

    /**
     * Clear cart
     */
    public function clear_cart() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        $wpdb->delete(
            $table_name,
            ['session_id' => $this->session_id],
            ['%s']
        );

        $this->cart_items = [];

        do_action('hubspot_ecommerce_cart_cleared');

        return true;
    }

    /**
     * Get cart items
     */
    public function get_cart_items() {
        return $this->cart_items;
    }

    /**
     * Get cart items with full product data
     */
    public function get_cart_items_with_products() {
        $items = [];

        foreach ($this->cart_items as $item) {
            $product = get_post($item['product_id']);
            if ($product) {
                $items[] = [
                    'cart_item' => $item,
                    'product' => $product,
                    'subtotal' => $item['price'] * $item['quantity'],
                ];
            }
        }

        return $items;
    }

    /**
     * Get cart item count
     */
    public function get_cart_item_count() {
        $count = 0;
        foreach ($this->cart_items as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    /**
     * Calculate cart total
     */
    public function get_cart_total() {
        $total = 0;
        foreach ($this->cart_items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    /**
     * Calculate cart subtotal (before tax/shipping)
     */
    public function get_cart_subtotal() {
        return $this->get_cart_total();
    }

    /**
     * AJAX: Add to cart
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('hubspot_ecommerce_nonce', 'nonce');

        // Rate limiting check (if rate limiter class exists)
        if (class_exists('HubSpot_Ecommerce_Rate_Limiter')) {
            $rate_limiter = HubSpot_Ecommerce_Rate_Limiter::instance();
            if ($rate_limiter->is_rate_limited('add_to_cart')) {
                $rate_limiter->send_rate_limit_error('add_to_cart');
            }
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if (!$product_id) {
            wp_send_json_error(['message' => __('Invalid product', 'hubspot-ecommerce')]);
        }

        $result = $this->add_to_cart($product_id, $quantity);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Product added to cart', 'hubspot-ecommerce'),
            'cart_count' => $this->get_cart_item_count(),
            'cart_total' => $this->get_cart_total(),
        ]);
    }

    /**
     * AJAX: Update cart
     */
    public function ajax_update_cart() {
        check_ajax_referer('hubspot_ecommerce_nonce', 'nonce');

        // Rate limiting check (if rate limiter class exists)
        if (class_exists('HubSpot_Ecommerce_Rate_Limiter')) {
            $rate_limiter = HubSpot_Ecommerce_Rate_Limiter::instance();
            if ($rate_limiter->is_rate_limited('add_to_cart')) {
                $rate_limiter->send_rate_limit_error('add_to_cart');
            }
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

        if (!$product_id) {
            wp_send_json_error(['message' => __('Invalid product', 'hubspot-ecommerce')]);
        }

        $result = $this->update_cart_item($product_id, $quantity);

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to update cart', 'hubspot-ecommerce')]);
        }

        wp_send_json_success([
            'message' => __('Cart updated', 'hubspot-ecommerce'),
            'cart_count' => $this->get_cart_item_count(),
            'cart_total' => $this->get_cart_total(),
        ]);
    }

    /**
     * AJAX: Remove from cart
     */
    public function ajax_remove_from_cart() {
        check_ajax_referer('hubspot_ecommerce_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (!$product_id) {
            wp_send_json_error(['message' => __('Invalid product', 'hubspot-ecommerce')]);
        }

        $result = $this->remove_from_cart($product_id);

        if (!$result) {
            wp_send_json_error(['message' => __('Failed to remove item', 'hubspot-ecommerce')]);
        }

        wp_send_json_success([
            'message' => __('Item removed from cart', 'hubspot-ecommerce'),
            'cart_count' => $this->get_cart_item_count(),
            'cart_total' => $this->get_cart_total(),
        ]);
    }

    /**
     * AJAX: Get cart
     */
    public function ajax_get_cart() {
        check_ajax_referer('hubspot_ecommerce_nonce', 'nonce');

        $items = $this->get_cart_items_with_products();

        wp_send_json_success([
            'items' => $items,
            'cart_count' => $this->get_cart_item_count(),
            'cart_total' => $this->get_cart_total(),
        ]);
    }
}
