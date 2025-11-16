<?php
/**
 * License Validation REST API
 *
 * Provides public REST API endpoint for validating license keys.
 * This endpoint is called by customer WordPress installations to validate
 * their license keys against invoices in YOUR HubSpot portal.
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_License_API {

    private static $instance = null;

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
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('hubspot-ecommerce/v1', '/validate-license', [
            'methods' => 'POST',
            'callback' => [$this, 'validate_license'],
            'permission_callback' => '__return_true', // Public endpoint
            'args' => [
                'license_key' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'customer_domain' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Validate license key against HubSpot invoices
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function validate_license($request) {
        $license_key = $request->get_param('license_key');
        $customer_domain = $request->get_param('customer_domain');

        // Delegate to standalone validator service
        $validator = HubSpot_Ecommerce_License_Validator::instance();
        $result = $validator->validate($license_key, $customer_domain);

        // Return appropriate HTTP status
        $status_code = 200;
        if (!$result['success']) {
            $status_code = 500;
        }

        return new WP_REST_Response($result, $status_code);
    }

}
