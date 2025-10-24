<?php
/**
 * HubSpot Invoice Manager
 *
 * Manages HubSpot invoice creation and payment processing.
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Invoice_Manager {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * HubSpot API instance
     */
    private $api;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->api = HubSpot_Ecommerce_API::instance();

        // Only enable invoice features if Pro tier
        // Invoice API is not available with OAuth (Free tier)
        $license = HubSpot_Ecommerce_License_Manager::instance();
        if (!$license->can_use_invoices()) {
            // Don't register any hooks - feature disabled
            return;
        }

        // Future: Add any invoice-related hooks here
    }

    /**
     * Check if invoice features are available
     */
    public static function is_available() {
        $license = HubSpot_Ecommerce_License_Manager::instance();
        return $license->can_use_invoices();
    }

    /**
     * Create invoice from cart.
     *
     * @param int $contact_id HubSpot contact ID.
     * @param array $billing_data Billing information.
     * @return array|WP_Error Invoice data or error.
     */
    public function create_invoice_from_cart($contact_id, $billing_data) {
        $cart = HubSpot_Ecommerce_Cart::instance();
        $cart_items = $cart->get_cart_items_with_products();

        if (empty($cart_items)) {
            return new WP_Error('empty_cart', __('Cart is empty', 'hubspot-ecommerce'));
        }

        // 1. Create invoice
        $invoice_data = [
            'properties' => [
                'hs_invoice_billable' => true,
                'hs_currency' => get_option('hubspot_ecommerce_currency', 'USD'),
                'hs_invoice_status' => 'draft',
            ]
        ];

        $invoice = $this->api->create_invoice($invoice_data);

        if (is_wp_error($invoice)) {
            error_log('Failed to create HubSpot invoice: ' . $invoice->get_error_message());
            return $invoice;
        }

        $invoice_id = $invoice['id'];

        // 2. Associate with contact
        $association = $this->api->associate_invoice_to_contact($invoice_id, $contact_id);
        if (is_wp_error($association)) {
            error_log('Failed to associate invoice to contact: ' . $association->get_error_message());
            // Continue anyway - invoice still created
        }

        // 3. Add line items
        foreach ($cart_items as $item) {
            $cart_item = $item['cart_item'];
            $line_item_result = $this->add_line_item_to_invoice(
                $invoice_id,
                $cart_item['hubspot_product_id'],
                $cart_item['quantity'],
                $cart_item['price']
            );

            if (is_wp_error($line_item_result)) {
                error_log('Failed to add line item: ' . $line_item_result->get_error_message());
                // Continue with other items
            }
        }

        // 4. Move invoice to "open" status (ready for payment)
        $updated_invoice = $this->api->update_invoice($invoice_id, [
            'properties' => [
                'hs_invoice_status' => 'open'
            ]
        ]);

        if (is_wp_error($updated_invoice)) {
            error_log('Failed to open invoice: ' . $updated_invoice->get_error_message());
            return $updated_invoice;
        }

        do_action('hubspot_ecommerce_invoice_created', $invoice_id, $contact_id);

        return $updated_invoice;
    }

    /**
     * Add line item to invoice.
     *
     * @param string $invoice_id Invoice ID.
     * @param string $product_id HubSpot product ID.
     * @param int $quantity Quantity.
     * @param float $price Price.
     * @return array|WP_Error Line item or error.
     */
    private function add_line_item_to_invoice($invoice_id, $product_id, $quantity, $price) {
        // Create line item
        $line_item = $this->api->create_line_item([
            'properties' => [
                'hs_product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $price,
            ]
        ]);

        if (is_wp_error($line_item)) {
            return $line_item;
        }

        // Associate with invoice
        $association = $this->api->associate_line_item_to_invoice(
            $line_item['id'],
            $invoice_id
        );

        if (is_wp_error($association)) {
            return $association;
        }

        return $line_item;
    }

    /**
     * Get invoice payment status.
     *
     * @param string $invoice_id Invoice ID.
     * @return string|WP_Error Status or error.
     */
    public function get_invoice_payment_status($invoice_id) {
        $invoice = $this->api->get_invoice($invoice_id);

        if (is_wp_error($invoice)) {
            return $invoice;
        }

        return $invoice['properties']['hs_invoice_status'] ?? 'unknown';
    }

    /**
     * Handle invoice paid event.
     *
     * @param string $invoice_id Invoice ID.
     * @return bool Success.
     */
    public function handle_invoice_paid($invoice_id) {
        // Find order by invoice ID
        $orders = get_posts([
            'post_type' => 'hs_order',
            'meta_query' => [
                [
                    'key' => '_hubspot_invoice_id',
                    'value' => $invoice_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
        ]);

        if (empty($orders)) {
            error_log('No order found for invoice: ' . $invoice_id);
            return false;
        }

        $order_id = $orders[0]->ID;

        // Update order status
        wp_update_post([
            'ID' => $order_id,
            'post_status' => 'publish', // Or 'completed'
        ]);

        update_post_meta($order_id, '_payment_status', 'paid');
        update_post_meta($order_id, '_payment_date', current_time('mysql'));

        do_action('hubspot_ecommerce_order_paid', $order_id, $invoice_id);

        return true;
    }

    /**
     * Handle invoice failed event.
     *
     * @param string $invoice_id Invoice ID.
     * @return bool Success.
     */
    public function handle_invoice_failed($invoice_id) {
        // Find order
        $orders = get_posts([
            'post_type' => 'hs_order',
            'meta_query' => [
                [
                    'key' => '_hubspot_invoice_id',
                    'value' => $invoice_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
        ]);

        if (empty($orders)) {
            return false;
        }

        $order_id = $orders[0]->ID;

        // Update order status
        wp_update_post([
            'ID' => $order_id,
            'post_status' => 'failed',
        ]);

        update_post_meta($order_id, '_payment_status', 'failed');

        do_action('hubspot_ecommerce_payment_failed', $order_id, $invoice_id);

        return true;
    }

    /**
     * Handle invoice voided event.
     *
     * @param string $invoice_id Invoice ID.
     * @return bool Success.
     */
    public function handle_invoice_voided($invoice_id) {
        // Find order
        $orders = get_posts([
            'post_type' => 'hs_order',
            'meta_query' => [
                [
                    'key' => '_hubspot_invoice_id',
                    'value' => $invoice_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
        ]);

        if (empty($orders)) {
            return false;
        }

        $order_id = $orders[0]->ID;

        // Update order status
        wp_update_post([
            'ID' => $order_id,
            'post_status' => 'cancelled',
        ]);

        update_post_meta($order_id, '_payment_status', 'voided');

        do_action('hubspot_ecommerce_payment_voided', $order_id, $invoice_id);

        return true;
    }
}
