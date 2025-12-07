<?php
/**
 * HubSpot Payment Webhook Handler
 *
 * Processes payment-related webhook events from HubSpot.
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Payment_Webhook {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Webhook secret (for signature verification)
     */
    private $webhook_secret;

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
        $this->webhook_secret = get_option('hubspot_ecommerce_webhook_secret', '');
        $this->register_webhook_endpoint();
    }

    /**
     * Register webhook REST API endpoint.
     */
    private function register_webhook_endpoint() {
        add_action('rest_api_init', function() {
            register_rest_route('hubspot-ecommerce/v1', '/webhooks/payment', [
                'methods' => 'POST',
                'callback' => [$this, 'handle_webhook'],
                'permission_callback' => [$this, 'verify_webhook_request'],
            ]);
        });
    }

    /**
     * Verify webhook request authenticity.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool True if verified.
     */
    public function verify_webhook_request($request) {
        // 1. Check for signature header
        $signature = $request->get_header('X-HubSpot-Signature');

        if (empty($signature)) {
            error_log('Webhook received without signature');
            return false;
        }

        // 2. Get request body
        $body = $request->get_body();

        // 3. Calculate expected signature
        $expected_signature = hash_hmac('sha256', $body, $this->webhook_secret);

        // 4. Compare signatures (timing-safe comparison)
        if (!hash_equals($expected_signature, $signature)) {
            error_log('Webhook signature verification failed');
            return false;
        }

        return true;
    }

    /**
     * Handle incoming webhook.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public function handle_webhook($request) {
        $payload = $request->get_json_params();

        if (empty($payload)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid payload'
            ], 400);
        }

        // Log webhook for debugging
        error_log('HubSpot webhook received: ' . print_r($payload, true));

        // Determine event type
        $event_type = $payload['subscriptionType'] ?? '';
        $object_id = $payload['objectId'] ?? '';

        // Route to appropriate handler
        switch ($event_type) {
            case 'invoice.propertyChange':
                $this->handle_invoice_property_change($object_id, $payload);
                break;

            case 'commerce_payment.created':
                $this->handle_payment_created($object_id, $payload);
                break;

            case 'commerce_payment.updated':
                $this->handle_payment_updated($object_id, $payload);
                break;

            default:
                error_log('Unknown webhook event type: ' . $event_type);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Webhook processed'
        ], 200);
    }

    /**
     * Handle invoice property change.
     *
     * @param string $invoice_id Invoice ID.
     * @param array $payload Webhook payload.
     */
    private function handle_invoice_property_change($invoice_id, $payload) {
        $property_name = $payload['propertyName'] ?? '';
        $property_value = $payload['propertyValue'] ?? '';

        // Check if payment status changed
        if ($property_name === 'hs_invoice_status') {
            $this->handle_invoice_status_change($invoice_id, $property_value);
        }
    }

    /**
     * Handle invoice status change.
     *
     * @param string $invoice_id Invoice ID.
     * @param string $new_status New status.
     */
    private function handle_invoice_status_change($invoice_id, $new_status) {
        $invoice_manager = HubSpot_Ecommerce_Invoice_Manager::instance();

        switch ($new_status) {
            case 'paid':
                $invoice_manager->handle_invoice_paid($invoice_id);
                break;

            case 'failed':
                $invoice_manager->handle_invoice_failed($invoice_id);
                break;

            case 'voided':
                $invoice_manager->handle_invoice_voided($invoice_id);
                break;
        }
    }

    /**
     * Handle payment created.
     *
     * @param string $payment_id Payment ID.
     * @param array $payload Webhook payload.
     */
    private function handle_payment_created($payment_id, $payload) {
        // Extract payment details
        $invoice_id = $payload['properties']['hs_invoice_id'] ?? '';
        $amount = $payload['properties']['hs_payment_amount'] ?? 0;

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

        if (!empty($orders)) {
            $order_id = $orders[0]->ID;
            update_post_meta($order_id, '_payment_id', sanitize_text_field($payment_id));
            update_post_meta($order_id, '_payment_amount', floatval($amount));

            do_action('hubspot_ecommerce_payment_created', $order_id, $payment_id, $amount);
        }
    }

    /**
     * Handle payment updated.
     *
     * @param string $payment_id Payment ID.
     * @param array $payload Webhook payload.
     */
    private function handle_payment_updated($payment_id, $payload) {
        // Extract payment details
        $invoice_id = $payload['properties']['hs_invoice_id'] ?? '';
        $status = $payload['properties']['hs_payment_status'] ?? '';

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

        if (!empty($orders)) {
            $order_id = $orders[0]->ID;
            update_post_meta($order_id, '_payment_status', sanitize_text_field($status));

            do_action('hubspot_ecommerce_payment_updated', $order_id, $payment_id, $status);
        }
    }

    /**
     * Get webhook URL for display in admin.
     *
     * @return string Webhook URL.
     */
    public function get_webhook_url() {
        return rest_url('hubspot-ecommerce/v1/webhooks/payment');
    }
}
