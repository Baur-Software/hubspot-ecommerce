<?php
/**
 * GDPR Data Handler
 *
 * Implements GDPR rights: Right to Access, Right to Erasure, Right to Data Portability
 *
 * @package HubSpot_Ecommerce
 * @see docs/DATA_RETENTION_POLICY.md
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_GDPR_Handler {

    private static $instance = null;
    private $api;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api = HubSpot_Ecommerce_API::instance();

        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // WordPress privacy tools integration
        add_filter('wp_privacy_personal_data_exporters', [$this, 'register_data_exporter']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'register_data_eraser']);

        // Add privacy policy content
        add_action('admin_init', [$this, 'add_privacy_policy_content']);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Export customer data
        register_rest_route('hubspot-ecommerce/v1', '/gdpr/export', [
            'methods' => 'GET',
            'callback' => [$this, 'export_customer_data'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        // Request data deletion
        register_rest_route('hubspot-ecommerce/v1', '/gdpr/delete-request', [
            'methods' => 'POST',
            'callback' => [$this, 'request_data_deletion'],
            'permission_callback' => [$this, 'check_user_permission'],
        ]);

        // Confirm data deletion
        register_rest_route('hubspot-ecommerce/v1', '/gdpr/delete-confirm', [
            'methods' => 'POST',
            'callback' => [$this, 'confirm_data_deletion'],
            'permission_callback' => '__return_true', // Token-based verification
        ]);
    }

    /**
     * Check if user has permission to access their data
     *
     * @param WP_REST_Request $request Request object
     * @return bool
     */
    public function check_user_permission($request) {
        return is_user_logged_in();
    }

    /**
     * Export customer data (GDPR Article 15 - Right to Access)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function export_customer_data($request) {
        $user_id = get_current_user_id();
        $format = $request->get_param('format') ?? 'json';

        $data = $this->collect_customer_data($user_id);

        if (is_wp_error($data)) {
            return new WP_Error('export_failed', $data->get_error_message(), ['status' => 500]);
        }

        // Log the export request
        $this->log_gdpr_action('data_export', $user_id, [
            'format' => $format,
            'records' => array_sum(array_map('count', $data)),
        ]);

        // Return in requested format
        if ($format === 'csv') {
            return $this->format_data_as_csv($data);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'exported_at' => current_time('mysql'),
        ], 200);
    }

    /**
     * Collect all customer data
     *
     * @param int $user_id User ID
     * @return array|WP_Error Customer data
     */
    private function collect_customer_data($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('invalid_user', 'User not found');
        }

        $data = [
            'profile' => $this->get_user_profile_data($user),
            'orders' => $this->get_user_orders($user_id),
            'cart_sessions' => $this->get_user_cart_sessions($user->user_email),
            'subscriptions' => $this->get_user_subscriptions($user_id),
            'audit_logs' => $this->get_user_audit_logs($user_id),
            'hubspot_data' => $this->get_hubspot_contact_data($user_id),
        ];

        return apply_filters('hubspot_ecommerce_gdpr_export_data', $data, $user_id);
    }

    /**
     * Get user profile data
     *
     * @param WP_User $user User object
     * @return array Profile data
     */
    private function get_user_profile_data($user) {
        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'registered' => $user->user_registered,
            'billing_address' => get_user_meta($user->ID, 'billing_address', true),
            'billing_city' => get_user_meta($user->ID, 'billing_city', true),
            'billing_state' => get_user_meta($user->ID, 'billing_state', true),
            'billing_zip' => get_user_meta($user->ID, 'billing_zip', true),
            'billing_country' => get_user_meta($user->ID, 'billing_country', true),
            'phone' => get_user_meta($user->ID, 'billing_phone', true),
        ];
    }

    /**
     * Get user orders
     *
     * @param int $user_id User ID
     * @return array Orders
     */
    private function get_user_orders($user_id) {
        $orders = get_posts([
            'post_type' => 'hs_order',
            'post_status' => 'any',
            'meta_key' => '_customer_user',
            'meta_value' => $user_id,
            'posts_per_page' => -1,
        ]);

        $order_data = [];

        foreach ($orders as $order) {
            $order_data[] = [
                'id' => $order->ID,
                'order_number' => get_post_meta($order->ID, '_order_number', true),
                'date' => $order->post_date,
                'status' => get_post_meta($order->ID, '_order_status', true),
                'total' => get_post_meta($order->ID, '_order_total', true),
                'items' => get_post_meta($order->ID, '_order_items', true),
                'billing_details' => get_post_meta($order->ID, '_billing_details', true),
                'hubspot_deal_id' => get_post_meta($order->ID, '_hubspot_deal_id', true),
                'hubspot_invoice_id' => get_post_meta($order->ID, '_hubspot_invoice_id', true),
            ];
        }

        return $order_data;
    }

    /**
     * Get user cart sessions
     *
     * @param string $email User email
     * @return array Cart sessions
     */
    private function get_user_cart_sessions($email) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        // Get sessions associated with user's email (stored in session metadata)
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name}
            WHERE session_id IN (
                SELECT DISTINCT session_id FROM {$table_name}
            )
            ORDER BY created_at DESC",
            $email
        ), ARRAY_A);

        return $sessions;
    }

    /**
     * Get user subscriptions
     *
     * @param int $user_id User ID
     * @return array Subscriptions
     */
    private function get_user_subscriptions($user_id) {
        $hubspot_contact_id = get_user_meta($user_id, 'hubspot_contact_id', true);

        if (empty($hubspot_contact_id)) {
            return [];
        }

        $subscription_manager = HubSpot_Ecommerce_Subscription_Manager::instance();
        $subscriptions = $subscription_manager->get_customer_subscriptions($hubspot_contact_id);

        return is_wp_error($subscriptions) ? [] : $subscriptions;
    }

    /**
     * Get user audit logs
     *
     * @param int $user_id User ID
     * @return array Audit logs
     */
    private function get_user_audit_logs($user_id) {
        global $wpdb;

        $audit_table = $wpdb->prefix . 'hubspot_audit_log';

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT action, object_type, object_id, details, ip_address, created_at
            FROM {$audit_table}
            WHERE user_id = %d
            ORDER BY created_at DESC
            LIMIT 100",
            $user_id
        ), ARRAY_A);

        return $logs ?: [];
    }

    /**
     * Get HubSpot contact data
     *
     * @param int $user_id User ID
     * @return array HubSpot data
     */
    private function get_hubspot_contact_data($user_id) {
        $hubspot_contact_id = get_user_meta($user_id, 'hubspot_contact_id', true);

        if (empty($hubspot_contact_id)) {
            return ['message' => 'No HubSpot contact associated'];
        }

        $contact = $this->api->get_contact($hubspot_contact_id);

        if (is_wp_error($contact)) {
            return ['error' => $contact->get_error_message()];
        }

        return [
            'contact_id' => $hubspot_contact_id,
            'properties' => $contact['properties'] ?? [],
            'last_synced' => get_user_meta($user_id, 'hubspot_last_sync', true),
        ];
    }

    /**
     * Format data as CSV
     *
     * @param array $data Data to format
     * @return WP_REST_Response
     */
    private function format_data_as_csv($data) {
        $csv = '';

        foreach ($data as $section => $items) {
            $csv .= strtoupper($section) . "\n\n";

            if (is_array($items) && !empty($items)) {
                // Get headers from first item
                $headers = array_keys(is_array($items[0]) ? $items[0] : $items);
                $csv .= implode(',', $headers) . "\n";

                // Add data rows
                foreach ($items as $item) {
                    if (is_array($item)) {
                        $csv .= implode(',', array_map(function($value) {
                            return '"' . str_replace('"', '""', is_array($value) ? json_encode($value) : $value) . '"';
                        }, $item)) . "\n";
                    }
                }
            }

            $csv .= "\n";
        }

        return new WP_REST_Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customer-data-export-' . gmdate('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Request data deletion (GDPR Article 17 - Right to Erasure)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function request_data_deletion($request) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('invalid_user', 'User not found', ['status' => 404]);
        }

        // Generate confirmation token
        $token = wp_generate_password(32, false);
        $token_hash = wp_hash_password($token);

        // Store token with expiry (7 days)
        set_transient('hubspot_gdpr_delete_' . $user_id, $token_hash, 7 * DAY_IN_SECONDS);

        // Send confirmation email
        $this->send_deletion_confirmation_email($user, $token);

        // Log the request
        $this->log_gdpr_action('deletion_requested', $user_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Deletion request received. Please check your email to confirm.', 'hubspot-ecommerce'),
        ], 200);
    }

    /**
     * Send deletion confirmation email
     *
     * @param WP_User $user User object
     * @param string $token Confirmation token
     */
    private function send_deletion_confirmation_email($user, $token) {
        $confirm_url = rest_url('hubspot-ecommerce/v1/gdpr/delete-confirm') . '?user_id=' . $user->ID . '&token=' . $token;

        $subject = sprintf('[%s] Confirm Data Deletion Request', get_bloginfo('name'));

        $message = sprintf(
            "Hello %s,\n\n" .
            "We received a request to delete all your personal data from %s.\n\n" .
            "To confirm this deletion, please click the link below within 7 days:\n" .
            "%s\n\n" .
            "IMPORTANT:\n" .
            "- This action cannot be undone\n" .
            "- Your account will be permanently deleted or anonymized\n" .
            "- Order records required by law will be retained with anonymized customer information\n\n" .
            "If you did not make this request, please ignore this email.\n\n" .
            "Best regards,\n%s",
            $user->display_name,
            get_bloginfo('name'),
            $confirm_url,
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Confirm and execute data deletion
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function confirm_data_deletion($request) {
        $user_id = absint($request->get_param('user_id'));
        $token = sanitize_text_field($request->get_param('token'));

        if (empty($user_id) || empty($token)) {
            return new WP_Error('invalid_request', 'Missing required parameters', ['status' => 400]);
        }

        // Verify token
        $stored_token_hash = get_transient('hubspot_gdpr_delete_' . $user_id);

        if (!$stored_token_hash || !wp_check_password($token, $stored_token_hash)) {
            return new WP_Error('invalid_token', 'Invalid or expired confirmation token', ['status' => 403]);
        }

        // Execute deletion
        $result = $this->delete_customer_data($user_id);

        if (is_wp_error($result)) {
            return $result;
        }

        // Delete confirmation token
        delete_transient('hubspot_gdpr_delete_' . $user_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Your data has been successfully deleted.', 'hubspot-ecommerce'),
            'details' => $result,
        ], 200);
    }

    /**
     * Delete customer data
     *
     * @param int $user_id User ID
     * @return array|WP_Error Deletion results
     */
    private function delete_customer_data($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('invalid_user', 'User not found');
        }

        $results = [];

        // 1. Delete cart sessions
        $results['cart_sessions'] = $this->delete_cart_sessions($user->user_email);

        // 2. Check for orders
        $has_orders = $this->user_has_orders($user_id);

        if ($has_orders) {
            // Anonymize user instead of deleting (financial records must be retained)
            $results['user'] = $this->anonymize_user($user_id);
            $results['orders'] = 'anonymized'; // Orders retained but customer data removed
        } else {
            // No orders, safe to fully delete user
            $results['user'] = $this->delete_user($user_id);
        }

        // 3. Delete from HubSpot (if configured)
        if (get_option('hubspot_ecommerce_gdpr_delete_from_hubspot', false)) {
            $results['hubspot'] = $this->delete_hubspot_contact($user_id);
        } else {
            $results['hubspot'] = 'skipped';
        }

        // 4. Remove from audit logs (or anonymize)
        $results['audit_logs'] = $this->anonymize_audit_logs($user_id);

        // Log the deletion
        $this->log_gdpr_action('deletion_completed', $user_id, $results);

        return $results;
    }

    /**
     * Check if user has orders
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function user_has_orders($user_id) {
        $orders = get_posts([
            'post_type' => 'hs_order',
            'meta_key' => '_customer_user',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
        ]);

        return !empty($orders);
    }

    /**
     * Delete cart sessions
     *
     * @param string $email User email
     * @return array Result
     */
    private function delete_cart_sessions($email) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE session_id LIKE %s",
            '%' . $wpdb->esc_like($email) . '%'
        ));

        return [
            'deleted' => $deleted,
            'status' => 'success',
        ];
    }

    /**
     * Anonymize user data
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function anonymize_user($user_id) {
        $anonymous_email = 'deleted-' . $user_id . '@anonymized.local';

        wp_update_user([
            'ID' => $user_id,
            'user_email' => $anonymous_email,
            'user_login' => 'deleted_user_' . $user_id,
            'display_name' => 'Deleted User',
            'first_name' => '',
            'last_name' => '',
        ]);

        // Remove all user meta
        delete_user_meta($user_id, 'billing_address');
        delete_user_meta($user_id, 'billing_city');
        delete_user_meta($user_id, 'billing_state');
        delete_user_meta($user_id, 'billing_zip');
        delete_user_meta($user_id, 'billing_country');
        delete_user_meta($user_id, 'billing_phone');
        delete_user_meta($user_id, 'hubspot_contact_id');

        return [
            'status' => 'anonymized',
            'reason' => 'User has orders that must be retained for legal compliance',
        ];
    }

    /**
     * Delete user completely
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function delete_user($user_id) {
        require_once ABSPATH . 'wp-admin/includes/user.php';

        $deleted = wp_delete_user($user_id);

        return [
            'status' => $deleted ? 'deleted' : 'failed',
        ];
    }

    /**
     * Delete HubSpot contact
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function delete_hubspot_contact($user_id) {
        $hubspot_contact_id = get_user_meta($user_id, 'hubspot_contact_id', true);

        if (empty($hubspot_contact_id)) {
            return [
                'status' => 'skipped',
                'reason' => 'No HubSpot contact ID found',
            ];
        }

        $result = $this->api->delete_contact($hubspot_contact_id);

        if (is_wp_error($result)) {
            return [
                'status' => 'failed',
                'error' => $result->get_error_message(),
            ];
        }

        return [
            'status' => 'deleted',
            'contact_id' => $hubspot_contact_id,
        ];
    }

    /**
     * Anonymize audit logs
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function anonymize_audit_logs($user_id) {
        global $wpdb;

        $audit_table = $wpdb->prefix . 'hubspot_audit_log';

        // Replace user ID with 0 and anonymize IP
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$audit_table}
            SET user_id = 0, ip_address = '0.0.0.0'
            WHERE user_id = %d",
            $user_id
        ));

        return [
            'updated' => $updated,
            'status' => 'anonymized',
        ];
    }

    /**
     * Log GDPR action
     *
     * @param string $action Action type
     * @param int $user_id User ID
     * @param array $details Additional details
     */
    private function log_gdpr_action($action, $user_id, $details = []) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hubspot_audit_log';

        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'action' => sanitize_key($action),
                'object_type' => 'gdpr',
                'details' => wp_json_encode($details),
                'ip_address' => $this->get_user_ip(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get user IP address
     *
     * @return string IP address
     */
    private function get_user_ip() {
        $ip = '';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * Register data exporter for WordPress privacy tools
     *
     * @param array $exporters Existing exporters
     * @return array Modified exporters
     */
    public function register_data_exporter($exporters) {
        $exporters['hubspot-ecommerce'] = [
            'exporter_friendly_name' => __('HubSpot Ecommerce Data', 'hubspot-ecommerce'),
            'callback' => [$this, 'wordpress_privacy_exporter'],
        ];

        return $exporters;
    }

    /**
     * WordPress privacy data exporter callback
     *
     * @param string $email_address Email address
     * @param int $page Page number
     * @return array Export data
     */
    public function wordpress_privacy_exporter($email_address, $page = 1) {
        $user = get_user_by('email', $email_address);

        if (!$user) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        $data = $this->collect_customer_data($user->ID);
        $export_items = [];

        // Format data for WordPress privacy tools
        foreach ($data as $group_id => $group_data) {
            if (is_array($group_data) && !empty($group_data)) {
                $export_items[] = [
                    'group_id' => $group_id,
                    'group_label' => ucfirst(str_replace('_', ' ', $group_id)),
                    'item_id' => $group_id . '-' . $user->ID,
                    'data' => $this->flatten_array_for_export($group_data),
                ];
            }
        }

        return [
            'data' => $export_items,
            'done' => true,
        ];
    }

    /**
     * Flatten array for WordPress privacy export
     *
     * @param array $data Data to flatten
     * @return array Flattened data
     */
    private function flatten_array_for_export($data) {
        $flattened = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = wp_json_encode($value);
            }

            $flattened[] = [
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'value' => $value,
            ];
        }

        return $flattened;
    }

    /**
     * Register data eraser for WordPress privacy tools
     *
     * @param array $erasers Existing erasers
     * @return array Modified erasers
     */
    public function register_data_eraser($erasers) {
        $erasers['hubspot-ecommerce'] = [
            'eraser_friendly_name' => __('HubSpot Ecommerce Data', 'hubspot-ecommerce'),
            'callback' => [$this, 'wordpress_privacy_eraser'],
        ];

        return $erasers;
    }

    /**
     * WordPress privacy data eraser callback
     *
     * @param string $email_address Email address
     * @param int $page Page number
     * @return array Erasure results
     */
    public function wordpress_privacy_eraser($email_address, $page = 1) {
        $user = get_user_by('email', $email_address);

        if (!$user) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true,
            ];
        }

        $result = $this->delete_customer_data($user->ID);

        $messages = [];
        $items_retained = false;

        if (isset($result['user']['status']) && $result['user']['status'] === 'anonymized') {
            $messages[] = __('User data anonymized due to legal retention requirements for financial records.', 'hubspot-ecommerce');
            $items_retained = true;
        }

        return [
            'items_removed' => true,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => true,
        ];
    }

    /**
     * Add privacy policy content
     */
    public function add_privacy_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = $this->get_privacy_policy_text();

        wp_add_privacy_policy_content(
            'HubSpot Ecommerce',
            wp_kses_post(wpautop($content, false))
        );
    }

    /**
     * Get suggested privacy policy text
     *
     * @return string Privacy policy content
     */
    private function get_privacy_policy_text() {
        return __(
            '<h2>What Personal Data We Collect and Why</h2>' .
            '<p>When you make a purchase from our store, we collect the following information:</p>' .
            '<ul>' .
            '<li><strong>Contact Information:</strong> Name, email address, phone number</li>' .
            '<li><strong>Billing Information:</strong> Billing address, city, state, zip code, country</li>' .
            '<li><strong>Order Details:</strong> Products purchased, quantities, prices, order dates</li>' .
            '<li><strong>Shopping Cart:</strong> Products you add to cart, saved for up to 30 days</li>' .
            '</ul>' .
            '<h3>Third-Party Data Sharing</h3>' .
            '<p>We sync your data with HubSpot CRM for order management and customer relationship management. ' .
            'HubSpot\'s privacy policy can be found at: https://legal.hubspot.com/privacy-policy</p>' .
            '<h3>Data Retention</h3>' .
            '<ul>' .
            '<li><strong>Cart Sessions:</strong> 30 days</li>' .
            '<li><strong>Orders:</strong> 7 years (required by financial regulations)</li>' .
            '<li><strong>Account Data:</strong> Until you request deletion</li>' .
            '</ul>' .
            '<h3>Your Rights</h3>' .
            '<p>You have the right to:</p>' .
            '<ul>' .
            '<li><strong>Access:</strong> Request a copy of all your personal data</li>' .
            '<li><strong>Correction:</strong> Update incorrect or incomplete data</li>' .
            '<li><strong>Deletion:</strong> Request deletion of your account and data</li>' .
            '<li><strong>Portability:</strong> Export your data in machine-readable format</li>' .
            '</ul>' .
            '<p>To exercise these rights, please visit your account dashboard or contact us at privacy@baursoftware.com</p>',
            'hubspot-ecommerce'
        );
    }
}
