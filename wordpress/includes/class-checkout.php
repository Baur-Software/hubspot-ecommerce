<?php
/**
 * Checkout - Creates deals in HubSpot
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Checkout {

    private static $instance = null;
    private $api;
    private $cart;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api = HubSpot_Ecommerce_API::instance();
        $this->cart = HubSpot_Ecommerce_Cart::instance();

        // AJAX actions
        add_action('wp_ajax_hs_process_checkout', [$this, 'ajax_process_checkout']);
        add_action('wp_ajax_nopriv_hs_process_checkout', [$this, 'ajax_process_checkout']);
    }

    /**
     * Process checkout - routes to appropriate payment method based on license tier
     */
    public function process_checkout($customer_data, $billing_data) {
        // Validate cart
        $cart_items = $this->cart->get_cart_items_with_products();
        if (empty($cart_items)) {
            return new WP_Error('empty_cart', __('Cart is empty', 'hubspot-ecommerce'));
        }

        // Get or create contact in HubSpot
        $contact_id = $this->get_or_create_contact($customer_data);
        if (is_wp_error($contact_id)) {
            return $contact_id;
        }

        // Check license tier to determine payment method
        $license = HubSpot_Ecommerce_License_Manager::instance();

        if ($license->can_use_invoices()) {
            // PRO TIER: Use HubSpot Payments (Invoice API)
            return $this->process_checkout_with_hubspot_payments($contact_id, $customer_data, $billing_data);
        } else {
            // FREE TIER: Use custom payment gateway (via hooks)
            return $this->process_checkout_with_custom_payment($contact_id, $customer_data, $billing_data);
        }
    }

    /**
     * Process checkout with HubSpot Payments (Pro feature)
     * Creates invoice and returns HubSpot payment link
     */
    private function process_checkout_with_hubspot_payments($contact_id, $customer_data, $billing_data) {
        // Create HubSpot invoice
        $invoice_manager = HubSpot_Ecommerce_Invoice_Manager::instance();
        $invoice = $invoice_manager->create_invoice_from_cart($contact_id, $billing_data);

        if (is_wp_error($invoice)) {
            return $invoice;
        }

        // Get HubSpot payment link
        $payment_url = $this->api->get_invoice_payment_link($invoice['id']);

        if (is_wp_error($payment_url) || empty($payment_url)) {
            return new WP_Error('no_payment_url', __('Failed to get payment URL from HubSpot', 'hubspot-ecommerce'));
        }

        // Create order post
        $order_id = $this->create_order_post_for_invoice(
            $invoice['id'],
            $customer_data,
            $billing_data,
            'pending'
        );

        if (is_wp_error($order_id)) {
            return $order_id;
        }

        // Clear cart
        $this->cart->clear_cart();

        do_action('hubspot_ecommerce_checkout_processed', $order_id, $invoice['id']);

        return [
            'success' => true,
            'order_id' => $order_id,
            'invoice_id' => $invoice['id'],
            'payment_url' => $payment_url,
            'payment_method' => 'hubspot',
            'message' => __('Order created! Redirecting to payment...', 'hubspot-ecommerce'),
        ];
    }

    /**
     * Process checkout with custom payment gateway (Free tier)
     * Creates deal and fires hook for custom payment integration
     */
    private function process_checkout_with_custom_payment($contact_id, $customer_data, $billing_data) {
        // Create deal in HubSpot (not invoice)
        $deal_id = $this->create_deal($contact_id, $billing_data);
        if (is_wp_error($deal_id)) {
            return $deal_id;
        }

        // Add line items to deal
        $cart_items = $this->cart->get_cart_items_with_products();
        $line_items_result = $this->add_line_items_to_deal($deal_id, $cart_items);
        if (is_wp_error($line_items_result)) {
            return $line_items_result;
        }

        // Create order post in WordPress
        $order_id = $this->create_order_post($deal_id, $customer_data, $billing_data, $cart_items);
        if (is_wp_error($order_id)) {
            return $order_id;
        }

        // Calculate total
        $total = $this->cart->get_total();

        // Fire payment hook - user can provide custom payment URL (Stripe, PayPal, etc.)
        $payment_url = apply_filters(
            'hubspot_ecommerce_payment_url',
            '',
            $order_id,
            $total,
            $customer_data,
            $billing_data
        );

        // Clear cart
        $this->cart->clear_cart();

        do_action('hubspot_ecommerce_order_created', $order_id, $deal_id);

        if (empty($payment_url)) {
            // No payment gateway configured - return success but with manual payment message
            return [
                'success' => true,
                'order_id' => $order_id,
                'deal_id' => $deal_id,
                'payment_url' => null,
                'payment_method' => 'manual',
                'message' => __(
                    'Order created successfully! Please configure a payment gateway or mark the order as paid manually.',
                    'hubspot-ecommerce'
                ),
            ];
        }

        return [
            'success' => true,
            'order_id' => $order_id,
            'deal_id' => $deal_id,
            'payment_url' => $payment_url,
            'payment_method' => 'custom',
            'message' => __('Order created! Redirecting to payment...', 'hubspot-ecommerce'),
        ];
    }

    /**
     * Get or create contact in HubSpot
     */
    private function get_or_create_contact($customer_data) {
        $email = sanitize_email($customer_data['email']);

        // Search for existing contact
        $search_result = $this->api->search_contact_by_email($email);

        if (is_wp_error($search_result)) {
            return $search_result;
        }

        // If contact exists, return ID
        if (isset($search_result['results'][0]['id'])) {
            $contact_id = $search_result['results'][0]['id'];

            // Update contact with latest info
            $this->api->update_contact($contact_id, $this->prepare_contact_properties($customer_data));

            return $contact_id;
        }

        // Create new contact
        $create_result = $this->api->create_contact($email, $this->prepare_contact_properties($customer_data));

        if (is_wp_error($create_result)) {
            return $create_result;
        }

        return $create_result['id'];
    }

    /**
     * Prepare contact properties for HubSpot
     */
    private function prepare_contact_properties($customer_data) {
        return [
            'firstname' => sanitize_text_field($customer_data['first_name'] ?? ''),
            'lastname' => sanitize_text_field($customer_data['last_name'] ?? ''),
            'phone' => sanitize_text_field($customer_data['phone'] ?? ''),
            'address' => sanitize_text_field($customer_data['address'] ?? ''),
            'city' => sanitize_text_field($customer_data['city'] ?? ''),
            'state' => sanitize_text_field($customer_data['state'] ?? ''),
            'zip' => sanitize_text_field($customer_data['zip'] ?? ''),
            'country' => sanitize_text_field($customer_data['country'] ?? ''),
        ];
    }

    /**
     * Create deal in HubSpot
     */
    private function create_deal($contact_id, $billing_data) {
        $cart_total = $this->cart->get_cart_total();

        $properties = [
            'dealname' => sprintf(__('Order from %s', 'hubspot-ecommerce'), get_bloginfo('name')),
            'amount' => $cart_total,
            'dealstage' => 'presentationscheduled', // Default stage - customize as needed
            'pipeline' => 'default', // Customize based on your HubSpot setup
            'closedate' => date('Y-m-d'),
        ];

        // Note: Order notes from customers are stored in the order metadata
        // and can be synced to HubSpot as a note/engagement if needed
        // Standard deals don't have a 'notes' property

        // Create deal with association to contact
        $associations = [
            [
                'to' => ['id' => $contact_id],
                'types' => [
                    [
                        'associationCategory' => 'HUBSPOT_DEFINED',
                        'associationTypeId' => 3, // Deal to Contact association
                    ],
                ],
            ],
        ];

        $result = $this->api->create_deal($properties, $associations);

        if (is_wp_error($result)) {
            return $result;
        }

        return $result['id'];
    }

    /**
     * Add line items to deal
     */
    private function add_line_items_to_deal($deal_id, $cart_items) {
        $line_items = [];

        foreach ($cart_items as $item) {
            $line_items[] = [
                'properties' => [
                    'hs_product_id' => $item['cart_item']['hubspot_product_id'],
                    'quantity' => $item['cart_item']['quantity'],
                    'price' => $item['cart_item']['price'],
                    'amount' => $item['subtotal'],
                    'name' => get_the_title($item['product']->ID),
                ],
            ];
        }

        // Batch create line items
        $result = $this->api->batch_create_line_items($line_items);

        if (is_wp_error($result)) {
            return $result;
        }

        // Associate line items with deal
        foreach ($result['results'] as $line_item) {
            $this->api->create_association(
                'line_items',
                $line_item['id'],
                'deals',
                $deal_id,
                19 // Line item to Deal association type
            );
        }

        return true;
    }

    /**
     * Create order post in WordPress
     */
    private function create_order_post($deal_id, $customer_data, $billing_data, $cart_items) {
        $order_data = [
            'post_title' => sprintf(__('Order %s', 'hubspot-ecommerce'), $deal_id),
            'post_type' => 'hs_order',
            'post_status' => 'publish',
        ];

        $order_id = wp_insert_post($order_data);

        if (is_wp_error($order_id)) {
            return $order_id;
        }

        // Save order meta
        update_post_meta($order_id, '_hubspot_deal_id', $deal_id);
        update_post_meta($order_id, '_customer_data', $customer_data);
        update_post_meta($order_id, '_billing_data', $billing_data);
        update_post_meta($order_id, '_order_items', $cart_items);
        update_post_meta($order_id, '_order_total', $this->cart->get_cart_total());
        update_post_meta($order_id, '_order_date', current_time('mysql'));

        return $order_id;
    }

    /**
     * Process checkout with invoice (HubSpot Commerce Hub).
     *
     * @param array $customer_data Customer information.
     * @param array $billing_data Billing information.
     * @return array|WP_Error Result with payment URL or error.
     */
    public function process_checkout_with_invoice($customer_data, $billing_data) {
        // Validate cart
        $cart_items = $this->cart->get_cart_items_with_products();
        if (empty($cart_items)) {
            return new WP_Error('empty_cart', __('Cart is empty', 'hubspot-ecommerce'));
        }

        // Get or create contact in HubSpot
        $contact_id = $this->get_or_create_contact($customer_data);
        if (is_wp_error($contact_id)) {
            return $contact_id;
        }

        // Create HubSpot invoice (instead of deal)
        $invoice_manager = HubSpot_Ecommerce_Invoice_Manager::instance();
        $invoice = $invoice_manager->create_invoice_from_cart($contact_id, $billing_data);

        if (is_wp_error($invoice)) {
            return $invoice;
        }

        // Get payment URL
        $payment_url = $this->api->get_invoice_payment_link($invoice['id']);

        if (is_wp_error($payment_url) || empty($payment_url)) {
            return new WP_Error('no_payment_url', __('Failed to get payment URL', 'hubspot-ecommerce'));
        }

        // Create local order record (pending status)
        $order_id = $this->create_order_post_for_invoice(
            $invoice['id'],
            $customer_data,
            $billing_data,
            'pending'
        );

        if (is_wp_error($order_id)) {
            return $order_id;
        }

        // Clear cart
        $this->cart->clear_cart();

        do_action('hubspot_ecommerce_checkout_processed', $order_id, $invoice['id']);

        // Return payment information
        return [
            'success' => true,
            'order_id' => $order_id,
            'invoice_id' => $invoice['id'],
            'payment_url' => $payment_url,
            'requires_payment' => true,
        ];
    }

    /**
     * Create order post for invoice (UPDATED to store invoice ID).
     *
     * @param string $invoice_id HubSpot invoice ID.
     * @param array $customer_data Customer data.
     * @param array $billing_data Billing data.
     * @param string $status Order status.
     * @return int|WP_Error Order ID or error.
     */
    private function create_order_post_for_invoice($invoice_id, $customer_data, $billing_data, $status = 'pending') {
        $cart_total = $this->cart->get_cart_total();

        $order_data = [
            'post_title' => sprintf(
                __('Order - %s', 'hubspot-ecommerce'),
                current_time('mysql')
            ),
            'post_type' => 'hs_order',
            'post_status' => $status,
            'post_author' => get_current_user_id(),
        ];

        $order_id = wp_insert_post($order_data);

        if (is_wp_error($order_id)) {
            return $order_id;
        }

        // Store invoice ID (NEW)
        update_post_meta($order_id, '_hubspot_invoice_id', sanitize_text_field($invoice_id));

        // Store customer and billing data
        update_post_meta($order_id, '_customer_data', $customer_data);
        update_post_meta($order_id, '_billing_data', $billing_data);
        update_post_meta($order_id, '_order_items', $this->cart->get_cart_items_with_products());
        update_post_meta($order_id, '_order_total', $cart_total);
        update_post_meta($order_id, '_payment_status', 'pending');
        update_post_meta($order_id, '_order_date', current_time('mysql'));

        do_action('hubspot_ecommerce_order_created', $order_id, $invoice_id);

        return $order_id;
    }

    /**
     * AJAX: Process checkout
     */
    public function ajax_process_checkout() {
        check_ajax_referer('hubspot_ecommerce_nonce', 'nonce');

        // Rate limiting check (if rate limiter class exists)
        if (class_exists('HubSpot_Ecommerce_Rate_Limiter')) {
            $rate_limiter = HubSpot_Ecommerce_Rate_Limiter::instance();
            if ($rate_limiter->is_rate_limited('checkout')) {
                $rate_limiter->send_rate_limit_error('checkout');
            }
        }

        // Validate and sanitize input
        $customer_data = [
            'email' => sanitize_email($_POST['email'] ?? ''),
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'state' => sanitize_text_field($_POST['state'] ?? ''),
            'zip' => sanitize_text_field($_POST['zip'] ?? ''),
            'country' => sanitize_text_field($_POST['country'] ?? ''),
        ];

        $billing_data = [
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? 'manual'),
        ];

        // Validate required fields
        if (empty($customer_data['email']) || empty($customer_data['first_name']) || empty($customer_data['last_name'])) {
            wp_send_json_error(['message' => __('Please fill in all required fields', 'hubspot-ecommerce')]);
        }

        $result = $this->process_checkout($customer_data, $billing_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Order placed successfully!', 'hubspot-ecommerce'),
            'order_id' => $result['order_id'],
            'redirect_url' => esc_url_raw(home_url('/order-confirmation/?order_id=' . $result['order_id'])),
        ]);
    }

    /**
     * Get order by ID (with authorization check)
     *
     * @param int $order_id Order ID.
     * @param int|null $user_id User ID (null = current user).
     * @return array|WP_Error Order data or error.
     */
    public function get_order($order_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Require login
        if (!$user_id) {
            return new WP_Error('unauthorized', __('Please log in to view orders', 'hubspot-ecommerce'));
        }

        $post = get_post($order_id);

        if (!$post || $post->post_type !== 'hs_order') {
            return null;
        }

        // Verify ownership
        $customer_data = get_post_meta($post->ID, '_customer_data', true);
        $user = get_userdata($user_id);

        // Allow admins to view all orders
        if (!current_user_can('manage_options')) {
            // Regular users can only view their own orders
            if (empty($customer_data['email']) || $customer_data['email'] !== $user->user_email) {
                return new WP_Error('forbidden', __('Unauthorized access', 'hubspot-ecommerce'));
            }
        }

        return [
            'id' => $post->ID,
            'deal_id' => get_post_meta($post->ID, '_hubspot_deal_id', true),
            'customer_data' => $customer_data,
            'billing_data' => get_post_meta($post->ID, '_billing_data', true),
            'items' => get_post_meta($post->ID, '_order_items', true),
            'total' => get_post_meta($post->ID, '_order_total', true),
            'date' => get_post_meta($post->ID, '_order_date', true),
        ];
    }
}
