<?php
/**
 * HubSpot API Integration
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_API {

    private static $instance = null;
    private $api_key;
    private $api_base = 'https://api.hubapi.com';
    private $use_leadin = false;
    private $portal_id = null;
    private $auth_mode = null; // 'leadin', 'private_app', or null

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->detect_authentication_mode();
    }

    /**
     * Detect which authentication mode to use
     */
    private function detect_authentication_mode() {
        // First, check if HubSpot leadin plugin is active and connected
        if ($this->is_leadin_active() && $this->get_leadin_portal_id()) {
            $this->use_leadin = true;
            $this->auth_mode = 'leadin';
            $this->portal_id = $this->get_leadin_portal_id();
            return;
        }

        // Fallback to Private App token
        $this->api_key = get_option('hubspot_ecommerce_api_key');
        if (!empty($this->api_key)) {
            $this->use_leadin = false;
            $this->auth_mode = 'private_app';
            return;
        }

        // No authentication available
        $this->auth_mode = null;
    }

    /**
     * Check if HubSpot leadin plugin is active
     */
    private function is_leadin_active() {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return is_plugin_active('leadin/leadin.php');
    }

    /**
     * Get portal ID from leadin plugin
     */
    private function get_leadin_portal_id() {
        return get_option('leadin_portalId');
    }

    /**
     * Get access token from leadin plugin
     */
    private function get_leadin_access_token() {
        // Try to access leadin's OAuth class
        if (!class_exists('Leadin\\admin\\LeadinAdmin')) {
            return null;
        }

        // Leadin stores tokens internally, we need to hook into their API
        // Check if we can access the token through filters or global state
        $token = apply_filters('leadin_access_token', null);

        if ($token) {
            return $token;
        }

        // Alternative: Try to get from leadin's internal storage
        // Note: This might need adjustment based on leadin's actual implementation
        $leadin_options = get_option('leadin_options', []);
        if (isset($leadin_options['access_token'])) {
            return $leadin_options['access_token'];
        }

        return null;
    }

    /**
     * Get authorization header for API requests
     */
    private function get_authorization_header() {
        if ($this->use_leadin) {
            $token = $this->get_leadin_access_token();
            if ($token) {
                return 'Bearer ' . $token;
            }
            // If leadin token fails, try to fall back
            $this->use_leadin = false;
            $this->api_key = get_option('hubspot_ecommerce_api_key');
        }

        if (!empty($this->api_key)) {
            return 'Bearer ' . $this->api_key;
        }

        return null;
    }

    /**
     * Get authentication status
     */
    public function get_auth_status() {
        return [
            'mode' => $this->auth_mode,
            'leadin_active' => $this->is_leadin_active(),
            'portal_id' => $this->portal_id,
            'has_private_key' => !empty($this->api_key),
        ];
    }

    /**
     * Make API request to HubSpot
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $auth_header = $this->get_authorization_header();

        if (!$auth_header) {
            return new WP_Error(
                'no_auth',
                __('HubSpot authentication not configured. Please install the HubSpot plugin or add a Private App token.', 'hubspot-ecommerce')
            );
        }

        $url = $this->api_base . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => $auth_header,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ];

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code >= 400) {
            return new WP_Error(
                'api_error',
                isset($decoded['message']) ? $decoded['message'] : __('API request failed', 'hubspot-ecommerce'),
                ['status' => $code, 'response' => $decoded]
            );
        }

        return $decoded;
    }

    /**
     * Get all products from HubSpot
     */
    public function get_products($limit = 100, $after = null) {
        $endpoint = '/crm/v3/objects/products';
        $query_args = [
            'limit' => $limit,
            'properties' => implode(',', [
                'name',
                'description',
                'price',
                'hs_sku',
                'hs_cost_of_goods_sold',
                'hs_images',
                'hs_url',
                'createdate',
                'hs_product_type',
            ]),
        ];

        if ($after) {
            $query_args['after'] = $after;
        }

        $endpoint .= '?' . http_build_query($query_args);

        return $this->request($endpoint);
    }

    /**
     * Get a single product by ID
     */
    public function get_product($product_id) {
        $endpoint = "/crm/v3/objects/products/{$product_id}";
        return $this->request($endpoint);
    }

    /**
     * Create a contact
     */
    public function create_contact($email, $properties = []) {
        $data = [
            'properties' => array_merge([
                'email' => $email,
            ], $properties),
        ];

        return $this->request('/crm/v3/objects/contacts', 'POST', $data);
    }

    /**
     * Update a contact
     */
    public function update_contact($contact_id, $properties = []) {
        $data = [
            'properties' => $properties,
        ];

        return $this->request("/crm/v3/objects/contacts/{$contact_id}", 'PATCH', $data);
    }

    /**
     * Search for a contact by email
     */
    public function search_contact_by_email($email) {
        $data = [
            'filterGroups' => [
                [
                    'filters' => [
                        [
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                            'value' => $email,
                        ],
                    ],
                ],
            ],
        ];

        return $this->request('/crm/v3/objects/contacts/search', 'POST', $data);
    }

    /**
     * Create a deal (order)
     */
    public function create_deal($properties, $associations = []) {
        $data = [
            'properties' => $properties,
        ];

        if (!empty($associations)) {
            $data['associations'] = $associations;
        }

        return $this->request('/crm/v3/objects/deals', 'POST', $data);
    }

    /**
     * Add line items to a deal
     */
    public function create_line_item($properties) {
        $data = [
            'properties' => $properties,
        ];

        return $this->request('/crm/v3/objects/line_items', 'POST', $data);
    }

    /**
     * Associate objects (e.g., line item to deal, deal to contact)
     */
    public function create_association($from_object_type, $from_object_id, $to_object_type, $to_object_id, $association_type) {
        $endpoint = "/crm/v3/objects/{$from_object_type}/{$from_object_id}/associations/{$to_object_type}/{$to_object_id}/{$association_type}";
        return $this->request($endpoint, 'PUT');
    }

    /**
     * Batch create line items
     */
    public function batch_create_line_items($line_items) {
        $data = [
            'inputs' => $line_items,
        ];

        return $this->request('/crm/v3/objects/line_items/batch/create', 'POST', $data);
    }

    /**
     * Get deal by ID
     */
    public function get_deal($deal_id) {
        return $this->request("/crm/v3/objects/deals/{$deal_id}");
    }

    /**
     * Update deal
     */
    public function update_deal($deal_id, $properties) {
        $data = [
            'properties' => $properties,
        ];

        return $this->request("/crm/v3/objects/deals/{$deal_id}", 'PATCH', $data);
    }

    /**
     * Get subscriptions (requires the appropriate HubSpot tier)
     */
    public function get_subscriptions($contact_id) {
        // Note: This might require different API endpoint based on HubSpot setup
        return $this->request("/crm/v3/objects/contacts/{$contact_id}/associations/subscriptions");
    }

    // ============================================
    // COMMERCE SUBSCRIPTIONS API (v3)
    // ============================================

    /**
     * Get all commerce subscriptions
     */
    public function get_commerce_subscriptions($limit = 100, $after = null) {
        $endpoint = '/crm/v3/objects/subscriptions';
        $query_args = ['limit' => $limit];

        if ($after) {
            $query_args['after'] = $after;
        }

        $endpoint .= '?' . http_build_query($query_args);
        return $this->request($endpoint);
    }

    /**
     * Get a single commerce subscription by ID
     */
    public function get_commerce_subscription($subscription_id) {
        return $this->request("/crm/v3/objects/subscriptions/{$subscription_id}");
    }

    /**
     * Create a commerce subscription
     */
    public function create_commerce_subscription($properties, $associations = []) {
        $data = [
            'properties' => $properties,
        ];

        if (!empty($associations)) {
            $data['associations'] = $associations;
        }

        return $this->request('/crm/v3/objects/subscriptions', 'POST', $data);
    }

    /**
     * Update a commerce subscription
     */
    public function update_commerce_subscription($subscription_id, $properties) {
        $data = [
            'properties' => $properties,
        ];

        return $this->request("/crm/v3/objects/subscriptions/{$subscription_id}", 'PATCH', $data);
    }

    /**
     * Get subscriptions associated with a contact
     */
    public function get_contact_subscriptions($contact_id) {
        return $this->request("/crm/v3/objects/contacts/{$contact_id}/associations/subscriptions");
    }

    // ============================================
    // MARKETING EMAIL SUBSCRIPTION TYPES API (v4)
    // ============================================

    /**
     * Get all email subscription type definitions
     */
    public function get_subscription_type_definitions($business_unit_id = null) {
        $endpoint = '/communication-preferences/v4/definitions';

        if ($business_unit_id) {
            $endpoint .= '?' . http_build_query(['businessUnitId' => $business_unit_id]);
        }

        return $this->request($endpoint);
    }

    /**
     * Get a contact's email subscription statuses
     */
    public function get_contact_subscription_statuses($email_or_id) {
        return $this->request("/communication-preferences/v4/statuses/{$email_or_id}");
    }

    /**
     * Subscribe a contact to an email subscription type
     */
    public function subscribe_contact($email_or_id, $subscription_id, $legal_basis, $legal_basis_explanation = '') {
        $data = [
            'subscriptionId' => $subscription_id,
            'legalBasis' => $legal_basis, // LEGITIMATE_INTEREST_CLIENT, CONSENT, etc.
            'legalBasisExplanation' => $legal_basis_explanation,
        ];

        return $this->request("/communication-preferences/v4/statuses/{$email_or_id}/subscribe", 'POST', $data);
    }

    /**
     * Unsubscribe a contact from an email subscription type
     */
    public function unsubscribe_contact($email_or_id, $subscription_id) {
        $data = [
            'subscriptionId' => $subscription_id,
        ];

        return $this->request("/communication-preferences/v4/statuses/{$email_or_id}/unsubscribe", 'POST', $data);
    }

    /**
     * Unsubscribe a contact from all email communication
     */
    public function unsubscribe_contact_from_all($email_or_id) {
        return $this->request("/communication-preferences/v4/statuses/{$email_or_id}/unsubscribe-all", 'POST');
    }

    /**
     * Get products with recurring billing properties
     */
    public function get_recurring_products($limit = 100, $after = null) {
        $endpoint = '/crm/v3/objects/products';
        $query_args = [
            'limit' => $limit,
            'properties' => implode(',', [
                'name',
                'description',
                'price',
                'hs_sku',
                'hs_cost_of_goods_sold',
                'hs_images',
                'hs_url',
                'createdate',
                'hs_product_type',
                'hs_recurring_billing_period', // For subscriptions
                'recurringbillingfrequency',
                'hs_billing_period_units',
            ]),
        ];

        if ($after) {
            $query_args['after'] = $after;
        }

        $endpoint .= '?' . http_build_query($query_args);
        return $this->request($endpoint);
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->request('/crm/v3/objects/products?limit=1');
        return !is_wp_error($response);
    }

    // ============================================
    // INVOICE API (Commerce Hub)
    // ============================================

    /**
     * Create a HubSpot invoice.
     *
     * @param array $invoice_data Invoice properties.
     * @return array|WP_Error Invoice data or error.
     */
    public function create_invoice($invoice_data) {
        $endpoint = '/crm/v3/objects/invoices';
        return $this->request($endpoint, 'POST', $invoice_data);
    }

    /**
     * Update a HubSpot invoice.
     *
     * @param string $invoice_id Invoice ID.
     * @param array $updates Properties to update.
     * @return array|WP_Error Updated invoice or error.
     */
    public function update_invoice($invoice_id, $updates) {
        $endpoint = '/crm/v3/objects/invoices/' . $invoice_id;
        return $this->request($endpoint, 'PATCH', $updates);
    }

    /**
     * Get a HubSpot invoice.
     *
     * @param string $invoice_id Invoice ID.
     * @return array|WP_Error Invoice data or error.
     */
    public function get_invoice($invoice_id) {
        $endpoint = '/crm/v3/objects/invoices/' . $invoice_id;
        return $this->request($endpoint, 'GET');
    }

    /**
     * Get invoice payment link.
     *
     * @param string $invoice_id Invoice ID.
     * @return string|WP_Error Payment URL or error.
     */
    public function get_invoice_payment_link($invoice_id) {
        $invoice = $this->get_invoice($invoice_id);

        if (is_wp_error($invoice)) {
            return $invoice;
        }

        return $invoice['properties']['hs_payment_link'] ?? '';
    }

    /**
     * Associate invoice with contact.
     *
     * @param string $invoice_id Invoice ID.
     * @param string $contact_id Contact ID.
     * @return bool|WP_Error Success or error.
     */
    public function associate_invoice_to_contact($invoice_id, $contact_id) {
        return $this->create_association(
            'invoices',
            $invoice_id,
            'contacts',
            $contact_id,
            '208' // invoice_to_contact association type
        );
    }

    /**
     * Associate line item with invoice.
     *
     * @param string $line_item_id Line item ID.
     * @param string $invoice_id Invoice ID.
     * @return bool|WP_Error Success or error.
     */
    public function associate_line_item_to_invoice($line_item_id, $invoice_id) {
        return $this->create_association(
            'line_items',
            $line_item_id,
            'invoices',
            $invoice_id,
            '20' // line_item_to_invoice association type
        );
    }
}
