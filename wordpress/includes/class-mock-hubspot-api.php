<?php
/**
 * Mock HubSpot API for Demo/Testing
 *
 * Provides fake HubSpot API responses when HUBSPOT_ECOMMERCE_DEMO_MODE is enabled
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Mock_API {

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
        // Hook into API calls if demo mode is enabled
        if ($this->is_demo_mode_enabled()) {
            add_filter('pre_http_request', [$this, 'intercept_hubspot_requests'], 10, 3);
        }
    }

    /**
     * Check if demo mode is enabled
     */
    public function is_demo_mode_enabled() {
        // Check constant first (for testing)
        if (defined('HUBSPOT_ECOMMERCE_DEMO_MODE') && HUBSPOT_ECOMMERCE_DEMO_MODE) {
            return true;
        }

        // Check option (for admin setting)
        return get_option('hubspot_ecommerce_demo_mode', false);
    }

    /**
     * Intercept HubSpot API requests and return mock data
     */
    public function intercept_hubspot_requests($preempt, $args, $url) {
        // Only intercept HubSpot API calls
        if (strpos($url, 'api.hubapi.com') === false) {
            return $preempt;
        }

        // Determine what endpoint is being called
        if (strpos($url, '/crm/v3/objects/products') !== false) {
            return $this->mock_get_products($url);
        } elseif (strpos($url, '/crm/v3/objects/contacts/search') !== false) {
            return $this->mock_search_contact($args);
        } elseif (strpos($url, '/crm/v3/objects/contacts') !== false) {
            return $this->mock_create_update_contact($args, $url);
        } elseif (strpos($url, '/crm/v3/objects/deals') !== false) {
            return $this->mock_create_deal($args);
        } elseif (strpos($url, '/crm/v3/objects/line_items') !== false) {
            return $this->mock_create_line_item($args);
        } elseif (strpos($url, '/crm/v3/objects/invoices') !== false) {
            return $this->mock_invoice_operations($args, $url);
        } elseif (strpos($url, '/associations/') !== false) {
            return $this->mock_create_association();
        }

        // Default success response
        return $this->create_response(['success' => true]);
    }

    /**
     * Mock: Get Products
     */
    private function mock_get_products($url) {
        $products = [
            [
                'id' => 'mock-product-001',
                'properties' => [
                    'name' => 'Test Widget',
                    'description' => 'A demonstration widget product for testing purposes',
                    'price' => '10.00',
                    'hs_sku' => 'TEST-001',
                    'hs_cost_of_goods_sold' => '5.00',
                    'hs_product_type' => 'simple',
                    'hs_images' => 'https://via.placeholder.com/300x300.png?text=Test+Widget',
                    'createdate' => date('Y-m-d\TH:i:s\Z'),
                ],
                'createdAt' => date('Y-m-d\TH:i:s\Z'),
                'updatedAt' => date('Y-m-d\TH:i:s\Z'),
                'archived' => false,
            ],
            [
                'id' => 'mock-product-002',
                'properties' => [
                    'name' => 'Premium Gadget',
                    'description' => 'An premium gadget for testing',
                    'price' => '25.00',
                    'hs_sku' => 'TEST-002',
                    'hs_cost_of_goods_sold' => '12.00',
                    'hs_product_type' => 'simple',
                    'hs_images' => 'https://via.placeholder.com/300x300.png?text=Premium+Gadget',
                    'createdate' => date('Y-m-d\TH:i:s\Z'),
                ],
                'createdAt' => date('Y-m-d\TH:i:s\Z'),
                'updatedAt' => date('Y-m-d\TH:i:s\Z'),
                'archived' => false,
            ],
            [
                'id' => 'mock-product-003',
                'properties' => [
                    'name' => 'Subscription Service',
                    'description' => 'A monthly subscription service for testing',
                    'price' => '100.00',
                    'hs_sku' => 'TEST-003',
                    'hs_cost_of_goods_sold' => '20.00',
                    'hs_product_type' => 'subscription',
                    'hs_recurring_billing_period' => 'monthly',
                    'recurringbillingfrequency' => '1',
                    'hs_billing_period_units' => 'month',
                    'hs_images' => 'https://via.placeholder.com/300x300.png?text=Subscription',
                    'createdate' => date('Y-m-d\TH:i:s\Z'),
                ],
                'createdAt' => date('Y-m-d\TH:i:s\Z'),
                'updatedAt' => date('Y-m-d\TH:i:s\Z'),
                'archived' => false,
            ],
        ];

        return $this->create_response([
            'results' => $products,
            'paging' => [
                'next' => null,
            ],
        ]);
    }

    /**
     * Mock: Search Contact
     */
    private function mock_search_contact($args) {
        $body = json_decode($args['body'], true);
        $email = $body['filterGroups'][0]['filters'][0]['value'] ?? 'test@example.com';

        // Randomly decide if contact exists (for testing both paths)
        $contact_exists = (strpos($email, 'existing') !== false);

        if ($contact_exists) {
            return $this->create_response([
                'results' => [
                    [
                        'id' => 'mock-contact-' . md5($email),
                        'properties' => [
                            'email' => $email,
                            'firstname' => 'Test',
                            'lastname' => 'Customer',
                            'createdate' => date('Y-m-d\TH:i:s\Z'),
                        ],
                    ],
                ],
            ]);
        }

        return $this->create_response(['results' => []]);
    }

    /**
     * Mock: Create/Update Contact
     */
    private function mock_create_update_contact($args, $url) {
        $body = json_decode($args['body'], true);
        $properties = $body['properties'] ?? [];

        // Check if this is an update (has ID in URL)
        preg_match('/contacts\/(\d+)/', $url, $matches);
        $contact_id = $matches[1] ?? 'mock-contact-' . uniqid();

        return $this->create_response([
            'id' => $contact_id,
            'properties' => $properties,
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * Mock: Create Deal
     */
    private function mock_create_deal($args) {
        $body = json_decode($args['body'], true);
        $properties = $body['properties'] ?? [];

        return $this->create_response([
            'id' => 'mock-deal-' . uniqid(),
            'properties' => $properties,
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * Mock: Create Line Item
     */
    private function mock_create_line_item($args) {
        $body = json_decode($args['body'], true);

        // Handle batch creation
        if (isset($body['inputs'])) {
            $results = [];
            foreach ($body['inputs'] as $input) {
                $results[] = [
                    'id' => 'mock-lineitem-' . uniqid(),
                    'properties' => $input['properties'] ?? [],
                ];
            }
            return $this->create_response(['results' => $results]);
        }

        // Single line item
        return $this->create_response([
            'id' => 'mock-lineitem-' . uniqid(),
            'properties' => $body['properties'] ?? [],
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * Mock: Invoice Operations
     */
    private function mock_invoice_operations($args, $url) {
        $method = $args['method'] ?? 'GET';
        $body = json_decode($args['body'] ?? '{}', true);

        // Check if getting specific invoice
        preg_match('/invoices\/([^\/]+)/', $url, $matches);
        $invoice_id = $matches[1] ?? 'mock-invoice-' . uniqid();

        if ($method === 'POST') {
            // Create invoice
            return $this->create_response([
                'id' => 'mock-invoice-' . uniqid(),
                'properties' => array_merge(
                    $body['properties'] ?? [],
                    [
                        'hs_payment_link' => 'https://invoice.hubspot.com/payment/mock-' . uniqid(),
                    ]
                ),
                'createdAt' => date('Y-m-d\TH:i:s\Z'),
                'updatedAt' => date('Y-m-d\TH:i:s\Z'),
            ]);
        } elseif ($method === 'PATCH') {
            // Update invoice
            return $this->create_response([
                'id' => $invoice_id,
                'properties' => $body['properties'] ?? [],
                'updatedAt' => date('Y-m-d\TH:i:s\Z'),
            ]);
        } else {
            // Get invoice
            return $this->create_response([
                'id' => $invoice_id,
                'properties' => [
                    'hs_invoice_billable' => true,
                    'hs_currency' => 'USD',
                    'hs_invoice_status' => 'open',
                    'hs_payment_link' => 'https://invoice.hubspot.com/payment/' . $invoice_id,
                ],
                'createdAt' => date('Y-m-d\TH:i:s\Z'),
                'updatedAt' => date('Y-m-d\TH:i:s\Z'),
            ]);
        }
    }

    /**
     * Mock: Create Association
     */
    private function mock_create_association() {
        return $this->create_response([
            'status' => 'COMPLETE',
        ], 200);
    }

    /**
     * Create mock HTTP response
     */
    private function create_response($body, $status_code = 200) {
        return [
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => json_encode($body),
            'response' => [
                'code' => $status_code,
                'message' => 'OK',
            ],
            'cookies' => [],
            'filename' => null,
        ];
    }

    /**
     * Get demo mode banner HTML
     */
    public function get_demo_banner() {
        if (!$this->is_demo_mode_enabled()) {
            return '';
        }

        return '
        <div class="notice notice-warning" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px;">
            <p style="margin: 0; font-weight: bold;">
                🎭 DEMO MODE ACTIVE
            </p>
            <p style="margin: 5px 0 0 0;">
                Using mock HubSpot API responses. No real API calls are being made.
                <a href="' . admin_url('admin.php?page=hubspot-ecommerce-settings') . '">Disable in Settings</a>
            </p>
        </div>
        ';
    }
}
