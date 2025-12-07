<?php
/**
 * License Manager
 *
 * Handles license verification and feature gating.
 * Integrates with License Manager for WooCommerce on baursoftware.com.
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_License_Manager {

    private static $instance = null;
    private $license_key = null;
    private $tier = 'free';
    private $status = 'inactive';

    // License server API (License Manager for WooCommerce on baursoftware.com)
    const API_URL = 'https://baursoftware.com/wp-json/lmfwc/v2/licenses';

    /**
     * Get license server consumer key from environment or wp-config.php
     */
    private function get_consumer_key() {
        if (defined('HUBSPOT_LICENSE_CONSUMER_KEY')) {
            return HUBSPOT_LICENSE_CONSUMER_KEY;
        }
        // Placeholder - will not work until configured
        return 'ck_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    }

    /**
     * Get license server consumer secret from environment or wp-config.php
     */
    private function get_consumer_secret() {
        if (defined('HUBSPOT_LICENSE_CONSUMER_SECRET')) {
            return HUBSPOT_LICENSE_CONSUMER_SECRET;
        }
        // Placeholder - will not work until configured
        return 'cs_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
    }

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
        $this->license_key = get_option('hubspot_ecommerce_license_key');
        $this->load_cached_status();

        // Daily license check
        add_action('hubspot_ecommerce_daily_license_check', [$this, 'verify_license']);
        if (!wp_next_scheduled('hubspot_ecommerce_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'hubspot_ecommerce_daily_license_check');
        }

        // Admin hooks
        add_action('admin_init', [$this, 'handle_license_actions']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
    }

    /**
     * Get current tier (free, pro, enterprise)
     */
    public function get_tier() {
        return $this->tier;
    }

    /**
     * Get license status
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Check if licensed (any paid tier)
     */
    public function is_licensed() {
        return in_array($this->tier, ['pro', 'enterprise']) && $this->status === 'active';
    }

    /**
     * Check if user can use Private App authentication
     *
     * Private App is required for Pro features because OAuth doesn't have
     * the necessary scopes (invoices, subscriptions, email preferences).
     */
    public function can_use_private_app() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use subscriptions
     */
    public function can_use_subscriptions() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use invoices (HubSpot Payments)
     */
    public function can_use_invoices() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use email preferences
     */
    public function can_use_email_preferences() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use automatic product sync (Pro feature)
     *
     * Free tier: Manual sync only (push/pull buttons)
     * Pro tier: Automatic scheduled sync from HubSpot + auto-push on save
     */
    public function can_use_auto_sync() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use multi-store (Enterprise only)
     */
    public function can_use_multistore() {
        return $this->tier === 'enterprise';
    }

    /**
     * Verify license with WooCommerce License Manager
     */
    public function verify_license($force = false) {
        // Check cache unless forced
        if (!$force) {
            $last_check = get_transient('hubspot_ecommerce_license_check');
            if ($last_check) {
                return true; // Cache valid
            }
        }

        // No license key = free tier
        if (empty($this->license_key)) {
            $this->tier = 'free';
            $this->status = 'inactive';
            $this->save_cached_status();
            return false;
        }

        // Call License Manager API to validate
        $response = wp_remote_post(self::API_URL . '/validate', [
            'body' => json_encode([
                'license_key' => $this->license_key,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->get_consumer_key() . ':' . $this->get_consumer_secret()),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('HubSpot Ecommerce: License verification failed - ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Check if API call was successful
        if ($status_code !== 200 || empty($data['success'])) {
            $error_msg = $data['data']['message'] ?? 'Unknown error';
            error_log('HubSpot Ecommerce: License validation failed - ' . $error_msg);

            $this->tier = 'free';
            $this->status = 'invalid';
            $this->save_cached_status();
            return false;
        }

        $license_data = $data['data'];

        // Check if license is expired
        if (!empty($license_data['expires_at'])) {
            $expires = strtotime($license_data['expires_at']);
            if ($expires < time()) {
                $this->tier = 'free';
                $this->status = 'expired';
                $this->save_cached_status();
                return false;
            }
        }

        // Check license status
        // License Manager statuses: 1 = sold, 2 = delivered, 3 = active, 4 = inactive
        if (isset($license_data['status']) && $license_data['status'] < 2) {
            $this->tier = 'free';
            $this->status = 'inactive';
            $this->save_cached_status();
            return false;
        }

        // Get tier from order
        $tier = $this->get_license_tier($license_data['order_id']);

        $this->tier = $tier;
        $this->status = 'active';
        $this->save_cached_status();

        // Cache for 24 hours
        set_transient('hubspot_ecommerce_license_check', true, DAY_IN_SECONDS);

        return true;
    }

    /**
     * Activate license
     */
    public function activate_license($license_key) {
        $response = wp_remote_post(self::API_URL . '/activate', [
            'body' => json_encode([
                'license_key' => $license_key,
                'label' => parse_url(home_url(), PHP_URL_HOST),
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->get_consumer_key() . ':' . $this->get_consumer_secret()),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code === 200 && !empty($data['success'])) {
            // Store license key and activation token
            update_option('hubspot_ecommerce_license_key', $license_key);

            if (!empty($data['data']['token'])) {
                update_option('hubspot_ecommerce_license_token', $data['data']['token']);
            }

            $this->license_key = $license_key;
            $this->verify_license(true);

            return true;
        }

        return new WP_Error(
            'activation_failed',
            $data['data']['message'] ?? __('License activation failed', 'hubspot-ecommerce')
        );
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        if (empty($this->license_key)) {
            return true;
        }

        $token = get_option('hubspot_ecommerce_license_token');

        // Call License Manager API to deactivate
        wp_remote_post(self::API_URL . '/deactivate', [
            'body' => json_encode([
                'license_key' => $this->license_key,
                'token' => $token,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->get_consumer_key() . ':' . $this->get_consumer_secret()),
            ],
            'timeout' => 15,
        ]);

        // Clean up local storage
        delete_option('hubspot_ecommerce_license_key');
        delete_option('hubspot_ecommerce_license_token');
        delete_option('hubspot_ecommerce_license_tier');
        delete_option('hubspot_ecommerce_license_status');
        delete_transient('hubspot_ecommerce_license_check');

        $this->license_key = null;
        $this->tier = 'free';
        $this->status = 'inactive';

        return true;
    }

    /**
     * Handle license activation/deactivation from admin
     */
    public function handle_license_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Activate license
        if (isset($_POST['activate_license']) && check_admin_referer('hubspot_activate_license', 'hubspot_license_nonce')) {
            $license_key = sanitize_text_field($_POST['license_key'] ?? '');

            if (empty($license_key)) {
                add_settings_error(
                    'hubspot_ecommerce_license',
                    'empty_license_key',
                    __('Please enter a license key', 'hubspot-ecommerce'),
                    'error'
                );
                return;
            }

            $result = $this->activate_license($license_key);

            if (is_wp_error($result)) {
                add_settings_error(
                    'hubspot_ecommerce_license',
                    'license_activation_failed',
                    $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'hubspot_ecommerce_license',
                    'license_activated',
                    __('License activated successfully! Pro features are now unlocked.', 'hubspot-ecommerce'),
                    'success'
                );
            }
        }

        // Deactivate license
        if (isset($_POST['deactivate_license']) && check_admin_referer('hubspot_deactivate_license', 'hubspot_license_nonce')) {
            $this->deactivate_license();

            add_settings_error(
                'hubspot_ecommerce_license',
                'license_deactivated',
                __('License deactivated. Pro features are now locked.', 'hubspot-ecommerce'),
                'info'
            );
        }
    }

    /**
     * Show admin notices for license status
     */
    public function show_admin_notices() {
        // Only show on HubSpot pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'hubspot-ecommerce') === false) {
            return;
        }

        // Don't show if already on license page
        if (isset($_GET['page']) && $_GET['page'] === 'hubspot-ecommerce-license') {
            return;
        }

        // Show expired license notice
        if ($this->status === 'expired') {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('HubSpot Ecommerce:', 'hubspot-ecommerce'); ?></strong>
                    <?php _e('Your license has expired. Pro features are now disabled.', 'hubspot-ecommerce'); ?>
                    <a href="<?php echo esc_url($this->get_upgrade_url()); ?>" target="_blank">
                        <?php _e('Renew your license', 'hubspot-ecommerce'); ?>
                    </a>
                </p>
            </div>
            <?php
        }

        // Show invalid license notice
        if ($this->status === 'invalid' && !empty($this->license_key)) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('HubSpot Ecommerce:', 'hubspot-ecommerce'); ?></strong>
                    <?php _e('Your license key is invalid.', 'hubspot-ecommerce'); ?>
                    <a href="<?php echo admin_url('admin.php?page=hubspot-ecommerce-license'); ?>">
                        <?php _e('Update license', 'hubspot-ecommerce'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Get tier from WooCommerce order
     *
     * Determines if customer bought Pro or Enterprise based on product name.
     */
    private function get_license_tier($order_id) {
        // Make WooCommerce API call to get order details
        $response = wp_remote_get(
            "https://baursoftware.com/wp-json/wc/v3/orders/{$order_id}",
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->get_consumer_key() . ':' . $this->get_consumer_secret()),
                ],
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            error_log('HubSpot Ecommerce: Failed to get order tier - ' . $response->get_error_message());
            return 'pro'; // Default to pro on error
        }

        $order = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($order['line_items'])) {
            return 'pro'; // Default to pro
        }

        // Check product names to determine tier
        foreach ($order['line_items'] as $item) {
            $product_name = strtolower($item['name'] ?? '');

            if (strpos($product_name, 'enterprise') !== false) {
                return 'enterprise';
            }
        }

        return 'pro';
    }

    /**
     * Get upgrade URL
     */
    public function get_upgrade_url() {
        return 'https://baursoftware.com/product/hubspot-ecommerce-pro/';
    }

    /**
     * Render upgrade notice (for admin pages)
     */
    public function render_upgrade_notice($feature_name) {
        ?>
        <div class="notice notice-info" style="padding: 20px; margin: 20px 0;">
            <h3 style="margin-top: 0;"><?php echo esc_html($feature_name); ?> 🔒</h3>
            <p style="font-size: 15px; margin-bottom: 15px;">
                <?php printf(
                    __('%s is a Pro feature. Upgrade to unlock HubSpot Payments, subscriptions, and advanced automation.', 'hubspot-ecommerce'),
                    '<strong>' . esc_html($feature_name) . '</strong>'
                ); ?>
            </p>

            <h4><?php _e('What you get with Pro:', 'hubspot-ecommerce'); ?></h4>
            <ul style="font-size: 14px; line-height: 1.8; margin-bottom: 20px;">
                <li>✅ <strong>HubSpot Payments</strong> - Let HubSpot handle invoicing & payments</li>
                <li>✅ <strong>Subscription Management</strong> - Sell recurring products</li>
                <li>✅ <strong>Email Preferences Sync</strong> - Manage marketing opt-ins</li>
                <li>✅ <strong>Private App Access</strong> - Full Commerce Hub integration</li>
                <li>✅ <strong>Priority Support</strong> - Get help when you need it</li>
            </ul>

            <p>
                <a href="<?php echo esc_url($this->get_upgrade_url()); ?>"
                   class="button button-primary button-hero"
                   target="_blank">
                    <?php _e('Upgrade to Pro - $39/month', 'hubspot-ecommerce'); ?>
                </a>
                <a href="https://baursoftware.com/hubspot-ecommerce/features/"
                   class="button button-secondary button-hero"
                   target="_blank"
                   style="margin-left: 10px;">
                    <?php _e('Learn More', 'hubspot-ecommerce'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Save cached license status
     */
    private function save_cached_status() {
        update_option('hubspot_ecommerce_license_tier', $this->tier);
        update_option('hubspot_ecommerce_license_status', $this->status);
    }

    /**
     * Load cached license status
     */
    private function load_cached_status() {
        $this->tier = get_option('hubspot_ecommerce_license_tier', 'free');
        $this->status = get_option('hubspot_ecommerce_license_status', 'inactive');
    }

    /**
     * Get feature comparison data
     */
    public function get_feature_comparison() {
        return [
            [
                'feature' => __('Product Sync', 'hubspot-ecommerce'),
                'free' => '✅ Unlimited',
                'pro' => '✅ Unlimited',
                'enterprise' => '✅ Unlimited',
            ],
            [
                'feature' => __('Contact Creation', 'hubspot-ecommerce'),
                'free' => '✅',
                'pro' => '✅',
                'enterprise' => '✅',
            ],
            [
                'feature' => __('Order Tracking (Deals)', 'hubspot-ecommerce'),
                'free' => '✅',
                'pro' => '✅',
                'enterprise' => '✅',
            ],
            [
                'feature' => __('Payment Processing', 'hubspot-ecommerce'),
                'free' => 'Custom gateway (Stripe, etc.)',
                'pro' => '✅ HubSpot Payments',
                'enterprise' => '✅ HubSpot Payments',
            ],
            [
                'feature' => __('Subscription Management', 'hubspot-ecommerce'),
                'free' => '❌',
                'pro' => '✅',
                'enterprise' => '✅',
            ],
            [
                'feature' => __('Invoice Creation', 'hubspot-ecommerce'),
                'free' => '❌',
                'pro' => '✅',
                'enterprise' => '✅',
            ],
            [
                'feature' => __('Email Preferences Sync', 'hubspot-ecommerce'),
                'free' => '❌',
                'pro' => '✅',
                'enterprise' => '✅',
            ],
            [
                'feature' => __('Multi-Store Support', 'hubspot-ecommerce'),
                'free' => '❌',
                'pro' => '❌',
                'enterprise' => '✅',
            ],
            [
                'feature' => __('Support', 'hubspot-ecommerce'),
                'free' => 'Community',
                'pro' => 'Priority Email',
                'enterprise' => 'Phone + Email',
            ],
            [
                'feature' => __('Price', 'hubspot-ecommerce'),
                'free' => '$0/month',
                'pro' => '$39/month',
                'enterprise' => '$99/month',
            ],
        ];
    }
}
