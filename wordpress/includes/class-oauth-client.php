<?php
/**
 * OAuth Client for HubSpot Public App
 *
 * Handles OAuth 2.0 authorization code flow for the HubSpot marketplace app.
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_OAuth_Client {

    private static $instance = null;

    // OAuth endpoints
    const OAUTH_BASE = 'https://app.hubspot.com/oauth';
    const TOKEN_ENDPOINT = 'https://api.hubapi.com/oauth/v1/token';

    /**
     * Get OAuth client ID from wp-config.php
     *
     * IMPORTANT: Define HUBSPOT_OAUTH_CLIENT_ID in wp-config.php
     * See wp-config-example.php for setup instructions.
     */
    private function get_client_id() {
        if (defined('HUBSPOT_OAUTH_CLIENT_ID')) {
            return HUBSPOT_OAUTH_CLIENT_ID;
        }
        error_log('HubSpot Ecommerce: HUBSPOT_OAUTH_CLIENT_ID not defined in wp-config.php');
        return null;
    }

    /**
     * Get OAuth client secret from wp-config.php
     *
     * IMPORTANT: Define HUBSPOT_OAUTH_CLIENT_SECRET in wp-config.php
     * See wp-config-example.php for setup instructions.
     */
    private function get_client_secret() {
        if (defined('HUBSPOT_OAUTH_CLIENT_SECRET')) {
            return HUBSPOT_OAUTH_CLIENT_SECRET;
        }
        error_log('HubSpot Ecommerce: HUBSPOT_OAUTH_CLIENT_SECRET not defined in wp-config.php');
        return null;
    }

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register OAuth callback handler
        add_action('admin_init', [$this, 'handle_oauth_callback']);

        // Add settings page hook
        add_action('admin_menu', [$this, 'add_oauth_settings_page'], 15);
    }

    /**
     * Add OAuth settings page
     */
    public function add_oauth_settings_page() {
        add_submenu_page(
            'hubspot-ecommerce',
            __('OAuth Connection', 'hubspot-ecommerce'),
            __('Connect to HubSpot', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-oauth',
            [$this, 'render_oauth_page']
        );

        // Register callback page (hidden, just for OAuth redirect)
        add_submenu_page(
            null, // No parent = hidden from menu
            __('OAuth Callback', 'hubspot-ecommerce'),
            __('OAuth Callback', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-oauth-callback',
            '__return_empty_string' // Empty callback, handled in admin_init
        );
    }

    /**
     * Render OAuth connection page
     */
    public function render_oauth_page() {
        $is_connected = $this->is_connected();
        $portal_id = get_option('hubspot_oauth_portal_id');
        $connected_at = get_option('hubspot_oauth_connected_at');

        ?>
        <div class="wrap">
            <h1><?php _e('HubSpot OAuth Connection', 'hubspot-ecommerce'); ?></h1>

            <?php if ($is_connected): ?>
                <div class="notice notice-success">
                    <p>
                        <strong><?php _e('Connected to HubSpot!', 'hubspot-ecommerce'); ?></strong><br>
                        <?php if ($portal_id): ?>
                            Portal ID: <?php echo esc_html($portal_id); ?><br>
                        <?php endif; ?>
                        <?php if ($connected_at): ?>
                            Connected: <?php echo esc_html(human_time_diff(strtotime($connected_at), current_time('timestamp'))); ?> ago
                        <?php endif; ?>
                    </p>
                </div>

                <h2><?php _e('Connection Status', 'hubspot-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Status', 'hubspot-ecommerce'); ?></th>
                        <td><span style="color: green;">●</span> Connected</td>
                    </tr>
                    <tr>
                        <th><?php _e('Portal ID', 'hubspot-ecommerce'); ?></th>
                        <td><?php echo esc_html($portal_id ?: 'Unknown'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Access Token', 'hubspot-ecommerce'); ?></th>
                        <td><?php echo $this->has_valid_access_token() ? 'Valid' : 'Expired (will auto-refresh)'; ?></td>
                    </tr>
                </table>

                <p>
                    <a href="<?php echo esc_url($this->get_disconnect_url()); ?>" class="button button-secondary">
                        <?php _e('Disconnect from HubSpot', 'hubspot-ecommerce'); ?>
                    </a>
                </p>

            <?php else: ?>
                <div class="notice notice-warning">
                    <p><?php _e('Not connected to HubSpot. Click the button below to authorize this plugin.', 'hubspot-ecommerce'); ?></p>
                </div>

                <h2><?php _e('Connect to HubSpot', 'hubspot-ecommerce'); ?></h2>
                <p><?php _e('Authorize this plugin to access your HubSpot account. You\'ll be redirected to HubSpot to grant permissions.', 'hubspot-ecommerce'); ?></p>

                <p>
                    <a href="<?php echo esc_url($this->get_authorization_url()); ?>" class="button button-primary button-hero">
                        <?php _e('Connect to HubSpot', 'hubspot-ecommerce'); ?>
                    </a>
                </p>

                <h3><?php _e('What permissions will be requested?', 'hubspot-ecommerce'); ?></h3>
                <ul>
                    <li><?php _e('E-commerce: Access to products and product data', 'hubspot-ecommerce'); ?></li>
                    <li><?php _e('CRM Contacts: Read and write contact information', 'hubspot-ecommerce'); ?></li>
                    <li><?php _e('CRM Deals: Read and write deals (orders)', 'hubspot-ecommerce'); ?></li>
                    <li><?php _e('Line Items: Manage products attached to deals', 'hubspot-ecommerce'); ?></li>
                    <li><?php _e('Communication Preferences: Manage email subscriptions (optional)', 'hubspot-ecommerce'); ?></li>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get OAuth authorization URL
     */
    public function get_authorization_url() {
        // Generate state token for CSRF protection
        $state = wp_create_nonce('hubspot_oauth_state');
        set_transient('hubspot_oauth_state_' . $state, true, 600); // 10 minutes

        $params = [
            'client_id' => $this->get_client_id(),
            'redirect_uri' => $this->get_redirect_uri(),
            'scope' => $this->get_required_scopes(),
            'state' => $state
        ];

        return self::OAUTH_BASE . '/authorize?' . http_build_query($params);
    }

    /**
     * Get redirect URI for OAuth callback
     */
    private function get_redirect_uri() {
        return admin_url('admin.php?page=hubspot-ecommerce-oauth-callback');
    }

    /**
     * Get required OAuth scopes
     */
    private function get_required_scopes() {
        return implode(' ', [
            'crm.objects.contacts.read',
            'crm.objects.contacts.write',
            'crm.objects.deals.read',
            'crm.objects.deals.write',
            'crm.objects.line_items.read',
            'crm.objects.line_items.write',
            'crm.schemas.line_items.read',
            'crm.objects.invoices.read',
            'crm.objects.invoices.write',
            'e-commerce',
            'oauth'
        ]);
    }

    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'hubspot-ecommerce-oauth-callback') {
            return;
        }

        // Verify state to prevent CSRF
        $state = sanitize_text_field($_GET['state'] ?? '');
        if (!get_transient('hubspot_oauth_state_' . $state)) {
            wp_die(__('Invalid OAuth state. Please try connecting again.', 'hubspot-ecommerce'));
        }
        delete_transient('hubspot_oauth_state_' . $state);

        // Check for errors
        if (isset($_GET['error'])) {
            $error = sanitize_text_field($_GET['error']);
            wp_die(sprintf(__('OAuth error: %s', 'hubspot-ecommerce'), $error));
        }

        // Get authorization code
        $code = sanitize_text_field($_GET['code'] ?? '');
        if (empty($code)) {
            wp_die(__('No authorization code received.', 'hubspot-ecommerce'));
        }

        // Exchange code for tokens
        $token_data = $this->exchange_code_for_token($code);

        if (is_wp_error($token_data)) {
            wp_die($token_data->get_error_message());
        }

        // Save tokens
        $this->save_tokens($token_data);

        // Get portal ID
        $portal_id = $this->get_portal_id_from_token($token_data['access_token']);
        if ($portal_id) {
            update_option('hubspot_oauth_portal_id', $portal_id);
        }

        update_option('hubspot_oauth_connected_at', current_time('mysql'));

        // Redirect to OAuth settings page with success message
        wp_redirect(add_query_arg('oauth', 'connected', admin_url('admin.php?page=hubspot-ecommerce-oauth')));
        exit;
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchange_code_for_token($code) {
        $response = wp_remote_post(self::TOKEN_ENDPOINT, [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->get_client_id(),
                'client_secret' => $this->get_client_secret(),
                'redirect_uri' => $this->get_redirect_uri(),
                'code' => $code
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error(
                'token_exchange_failed',
                $body['message'] ?? 'Failed to exchange code for token'
            );
        }

        return $body;
    }

    /**
     * Save OAuth tokens
     */
    private function save_tokens($token_data) {
        update_option('hubspot_oauth_access_token', $token_data['access_token']);
        update_option('hubspot_oauth_refresh_token', $token_data['refresh_token']);
        update_option('hubspot_oauth_expires_at', time() + $token_data['expires_in']);
        update_option('hubspot_oauth_connected', true);
    }

    /**
     * Get current access token (refresh if needed)
     */
    public function get_access_token() {
        if (!$this->is_connected()) {
            return new WP_Error('not_connected', 'Not connected to HubSpot');
        }

        // Check if token is expired
        $expires_at = get_option('hubspot_oauth_expires_at');
        if (time() >= $expires_at - 60) { // Refresh 1 minute early
            $token_data = $this->refresh_access_token();
            if (is_wp_error($token_data)) {
                return $token_data;
            }
            $this->save_tokens($token_data);
        }

        return get_option('hubspot_oauth_access_token');
    }

    /**
     * Refresh access token using refresh token
     */
    private function refresh_access_token() {
        $refresh_token = get_option('hubspot_oauth_refresh_token');

        $response = wp_remote_post(self::TOKEN_ENDPOINT, [
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->get_client_id(),
                'client_secret' => $this->get_client_secret(),
                'refresh_token' => $refresh_token
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error(
                'token_refresh_failed',
                $body['message'] ?? 'Failed to refresh token'
            );
        }

        return $body;
    }

    /**
     * Get portal ID from access token
     */
    private function get_portal_id_from_token($access_token) {
        $response = wp_remote_get('https://api.hubapi.com/oauth/v1/access-tokens/' . $access_token);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['hub_id'] ?? null;
    }

    /**
     * Check if connected to HubSpot
     */
    public function is_connected() {
        return (bool) get_option('hubspot_oauth_connected');
    }

    /**
     * Get portal ID
     */
    public function get_portal_id() {
        return get_option('hubspot_oauth_portal_id');
    }

    /**
     * Check if access token is valid
     */
    private function has_valid_access_token() {
        $expires_at = get_option('hubspot_oauth_expires_at');
        return $expires_at && time() < $expires_at;
    }

    /**
     * Get disconnect URL
     */
    private function get_disconnect_url() {
        return wp_nonce_url(
            admin_url('admin.php?page=hubspot-ecommerce-oauth&action=disconnect'),
            'hubspot_oauth_disconnect'
        );
    }

    /**
     * Disconnect from HubSpot
     */
    public function disconnect() {
        delete_option('hubspot_oauth_access_token');
        delete_option('hubspot_oauth_refresh_token');
        delete_option('hubspot_oauth_expires_at');
        delete_option('hubspot_oauth_connected');
        delete_option('hubspot_oauth_portal_id');
        delete_option('hubspot_oauth_connected_at');
    }
}
