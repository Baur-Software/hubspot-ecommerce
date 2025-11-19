<?php
/**
 * License Validator Service
 *
 * Pure validation logic with no WordPress REST API dependencies.
 * Can be called internally (validator site) or via REST API (customer sites).
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_License_Validator {

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
     * Private constructor
     */
    private function __construct() {
        // Singleton
    }

    /**
     * Validate a license key
     *
     * @param string $license_key License key to validate
     * @param string $customer_domain Customer's domain
     * @return array Validation result with keys: success, valid, error, tier, expires_at, product_name
     */
    public function validate($license_key, $customer_domain) {
        try {
            // Get OAuth access token
            $access_token = $this->get_access_token();

            if (empty($access_token)) {
                return [
                    'success' => false,
                    'valid' => false,
                    'error' => 'License validation service not configured',
                ];
            }

            // Search for invoice with matching license key
            $invoice = $this->search_invoice_by_license_key($license_key, $access_token);

            if (!$invoice) {
                return [
                    'success' => false,
                    'valid' => false,
                    'error' => 'Invalid license key',
                ];
            }

            // Check payment status
            $payment_check = $this->check_payment_status($invoice);
            if (!$payment_check['valid']) {
                return $payment_check;
            }

            // Check expiration
            $expiration_check = $this->check_expiration($invoice);
            if (!$expiration_check['valid']) {
                return $expiration_check;
            }

            // Determine tier
            $tier = $this->determine_tier($invoice);

            // Update invoice with activation info
            $this->update_invoice_activation($invoice['id'], $customer_domain, $access_token);

            // Return success
            return [
                'success' => true,
                'valid' => true,
                'tier' => $tier,
                'expires_at' => $invoice['properties']['hs_expiration_date'] ?? null,
                'product_name' => $invoice['properties']['hs_title'] ?? '',
            ];

        } catch (Exception $e) {
            error_log('HubSpot License Validator: Exception - ' . $e->getMessage());
            return [
                'success' => false,
                'valid' => false,
                'error' => 'Internal server error',
            ];
        }
    }

    /**
     * Search HubSpot for invoice with license key
     *
     * @param string $license_key License key
     * @param string $access_token OAuth access token
     * @return array|null Invoice data or null if not found
     */
    private function search_invoice_by_license_key($license_key, $access_token) {
        $search_url = 'https://api.hubapi.com/crm/v3/objects/invoices/search';

        $search_body = [
            'filterGroups' => [
                [
                    'filters' => [
                        [
                            'propertyName' => 'license_key',
                            'operator' => 'EQ',
                            'value' => $license_key,
                        ],
                    ],
                ],
            ],
            'properties' => [
                'license_key',
                'hs_title',
                'hs_payment_status',
                'hs_invoice_status',
                'hs_expiration_date',
                'license_activated_domain',
                'license_last_validated',
            ],
            'limit' => 1,
        ];

        $response = wp_remote_post($search_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($search_body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('HubSpot License Validator: Search failed - ' . $response->get_error_message());
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code !== 200 || empty($data['results'])) {
            return null;
        }

        return $data['results'][0];
    }

    /**
     * Check if invoice payment status is valid
     *
     * @param array $invoice Invoice data
     * @return array Validation result
     */
    private function check_payment_status($invoice) {
        $properties = $invoice['properties'];
        $payment_status = $properties['hs_payment_status'] ?? '';
        $invoice_status = $properties['hs_invoice_status'] ?? '';

        // Allow paid invoices and draft for testing
        $is_paid = ($payment_status === 'PAID' || $payment_status === 'paid' ||
                   $invoice_status === 'paid' || $invoice_status === 'PAID' ||
                   $invoice_status === 'draft'); // Allow draft for testing

        if (!$is_paid) {
            return [
                'success' => false,
                'valid' => false,
                'error' => 'License not activated - invoice unpaid',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if license is expired
     *
     * @param array $invoice Invoice data
     * @return array Validation result
     */
    private function check_expiration($invoice) {
        $properties = $invoice['properties'];
        $expiration_date = $properties['hs_expiration_date'] ?? null;

        if ($expiration_date) {
            $expires_at = strtotime($expiration_date);
            if ($expires_at < time()) {
                return [
                    'success' => false,
                    'valid' => false,
                    'error' => 'License expired',
                    'expires_at' => $expiration_date,
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Determine license tier from invoice
     *
     * @param array $invoice Invoice data
     * @return string License tier (free, pro, enterprise)
     */
    private function determine_tier($invoice) {
        $invoice_title = $invoice['properties']['hs_title'] ?? '';

        if (stripos($invoice_title, 'enterprise') !== false) {
            return 'enterprise';
        } elseif (stripos($invoice_title, 'pro') !== false) {
            return 'pro';
        }

        return 'pro'; // Default
    }

    /**
     * Update invoice with activation domain and timestamp
     *
     * @param string $invoice_id HubSpot invoice ID
     * @param string $customer_domain Customer's domain
     * @param string $access_token OAuth access token
     */
    private function update_invoice_activation($invoice_id, $customer_domain, $access_token) {
        $update_url = "https://api.hubapi.com/crm/v3/objects/invoices/{$invoice_id}";

        $update_body = [
            'properties' => [
                'license_activated_domain' => $customer_domain,
                'license_last_validated' => current_time('mysql'),
            ],
        ];

        wp_remote_request($update_url, [
            'method' => 'PATCH',
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($update_body),
            'timeout' => 10,
        ]);
        // Ignore errors - this is non-critical
    }

    /**
     * Get access token for HubSpot API
     * Uses our OAuth client which handles token refresh automatically
     *
     * @return string|null Access token
     */
    private function get_access_token() {
        // Check wp-config.php constant first (for production override)
        if (defined('HUBSPOT_LICENSE_PRIVATE_APP_TOKEN')) {
            return HUBSPOT_LICENSE_PRIVATE_APP_TOKEN;
        }

        // Use our OAuth client which handles token refresh
        if (class_exists('HubSpot_Ecommerce_OAuth_Client')) {
            $oauth_client = HubSpot_Ecommerce_OAuth_Client::instance();
            if (method_exists($oauth_client, 'get_access_token')) {
                return $oauth_client->get_access_token();
            }
        }

        // Fallback: direct from database (won't auto-refresh)
        return get_option('hubspot_oauth_access_token');
    }
}
