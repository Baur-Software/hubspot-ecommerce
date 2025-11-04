<?php
/**
 * Data Cleanup and Retention Management
 *
 * Implements automated data cleanup based on retention policies for SOC2 and GDPR compliance.
 *
 * @package HubSpot_Ecommerce
 * @see docs/DATA_RETENTION_POLICY.md
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Data_Cleanup {

    private static $instance = null;

    /**
     * Retention periods in days
     */
    const RETENTION_CART_SESSIONS = 30;
    const RETENTION_ORDERS = 2555; // 7 years
    const RETENTION_AUDIT_LOGS_ACTIVE = 90;
    const RETENTION_AUDIT_LOGS_ARCHIVE = 365;
    const RETENTION_ERROR_LOGS = 30;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Schedule cleanup cron jobs
        add_action('hubspot_ecommerce_daily_cleanup', [$this, 'run_daily_cleanup']);
        add_action('hubspot_ecommerce_monthly_cleanup', [$this, 'run_monthly_cleanup']);

        // Register activation/deactivation hooks via init
        add_action('init', [$this, 'maybe_schedule_cleanups'], 20);
    }

    /**
     * Schedule cleanup tasks if not already scheduled
     */
    public function maybe_schedule_cleanups() {
        if (!wp_next_scheduled('hubspot_ecommerce_daily_cleanup')) {
            wp_schedule_event(
                strtotime('tomorrow 3:00 AM'),
                'daily',
                'hubspot_ecommerce_daily_cleanup'
            );
        }

        if (!wp_next_scheduled('hubspot_ecommerce_monthly_cleanup')) {
            wp_schedule_event(
                strtotime('first day of next month 3:00 AM'),
                'monthly',
                'hubspot_ecommerce_monthly_cleanup'
            );
        }
    }

    /**
     * Daily cleanup tasks
     */
    public function run_daily_cleanup() {
        $this->log_cleanup_start('daily');

        $results = [
            'cart_sessions' => $this->cleanup_old_cart_sessions(),
            'error_logs' => $this->cleanup_error_logs(),
            'audit_logs_archive' => $this->archive_old_audit_logs(),
            'transients' => $this->cleanup_expired_transients(),
        ];

        $this->log_cleanup_results('daily', $results);
        $this->send_cleanup_notification('daily', $results);

        return $results;
    }

    /**
     * Monthly cleanup tasks
     */
    public function run_monthly_cleanup() {
        $this->log_cleanup_start('monthly');

        $results = [
            'archived_audit_logs' => $this->cleanup_archived_audit_logs(),
            'compliance_report' => $this->generate_compliance_report(),
            'order_retention_check' => $this->check_order_retention_limits(),
        ];

        $this->log_cleanup_results('monthly', $results);
        $this->send_cleanup_notification('monthly', $results);

        return $results;
    }

    /**
     * Delete cart sessions older than retention period
     *
     * @return array Cleanup statistics
     */
    public function cleanup_old_cart_sessions() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hubspot_cart_items';
        $cutoff_date = gmdate('Y-m-d H:i:s', strtotime('-' . self::RETENTION_CART_SESSIONS . ' days'));

        // Get count before deletion for reporting
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE created_at < %s",
            $cutoff_date
        ));

        if ($count > 0) {
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $cutoff_date
            ));

            $this->log_audit_action('cart_cleanup', [
                'records_deleted' => $deleted,
                'cutoff_date' => $cutoff_date,
            ]);

            return [
                'success' => true,
                'deleted' => $deleted,
                'cutoff_date' => $cutoff_date,
            ];
        }

        return [
            'success' => true,
            'deleted' => 0,
            'message' => 'No cart sessions to clean up',
        ];
    }

    /**
     * Archive audit logs older than active retention period
     *
     * @return array Archive statistics
     */
    public function archive_old_audit_logs() {
        global $wpdb;

        $audit_table = $wpdb->prefix . 'hubspot_audit_log';
        $archive_table = $wpdb->prefix . 'hubspot_audit_log_archive';
        $cutoff_date = gmdate('Y-m-d H:i:s', strtotime('-' . self::RETENTION_AUDIT_LOGS_ACTIVE . ' days'));

        // Create archive table if it doesn't exist
        $this->maybe_create_archive_table();

        // Move old logs to archive
        $moved = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$archive_table}
            SELECT * FROM {$audit_table}
            WHERE created_at < %s
            AND id NOT IN (SELECT id FROM {$archive_table})",
            $cutoff_date
        ));

        if ($moved > 0) {
            // Delete from active table
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$audit_table} WHERE created_at < %s",
                $cutoff_date
            ));

            $this->log_audit_action('audit_log_archive', [
                'records_archived' => $moved,
                'cutoff_date' => $cutoff_date,
            ]);
        }

        return [
            'success' => true,
            'archived' => $moved,
            'cutoff_date' => $cutoff_date,
        ];
    }

    /**
     * Delete archived audit logs older than archive retention period
     *
     * @return array Cleanup statistics
     */
    public function cleanup_archived_audit_logs() {
        global $wpdb;

        $archive_table = $wpdb->prefix . 'hubspot_audit_log_archive';
        $cutoff_date = gmdate('Y-m-d H:i:s', strtotime('-' . self::RETENTION_AUDIT_LOGS_ARCHIVE . ' days'));

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$archive_table} WHERE created_at < %s",
            $cutoff_date
        ));

        if ($count > 0) {
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$archive_table} WHERE created_at < %s",
                $cutoff_date
            ));

            $this->log_audit_action('archived_audit_cleanup', [
                'records_deleted' => $deleted,
                'cutoff_date' => $cutoff_date,
            ]);

            return [
                'success' => true,
                'deleted' => $deleted,
                'cutoff_date' => $cutoff_date,
            ];
        }

        return [
            'success' => true,
            'deleted' => 0,
            'message' => 'No archived audit logs to clean up',
        ];
    }

    /**
     * Cleanup error logs older than retention period
     *
     * @return array Cleanup statistics
     */
    public function cleanup_error_logs() {
        $cutoff_time = strtotime('-' . self::RETENTION_ERROR_LOGS . ' days');

        // Clean up error log transients
        global $wpdb;
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
            AND option_name LIKE %s",
            $wpdb->esc_like('_transient_hubspot_ecommerce_error_') . '%',
            '%' . $wpdb->esc_like(gmdate('Y-m-d', $cutoff_time)) . '%'
        ));

        return [
            'success' => true,
            'deleted' => $deleted,
        ];
    }

    /**
     * Cleanup expired transients
     *
     * @return array Cleanup statistics
     */
    public function cleanup_expired_transients() {
        global $wpdb;

        // Delete expired transients
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_timeout_hubspot_%'
            AND option_value < UNIX_TIMESTAMP()"
        );

        // Delete orphaned transient options
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_hubspot_%'
            AND option_name NOT IN (
                SELECT REPLACE(option_name, '_timeout', '')
                FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_timeout_hubspot_%'
            )"
        );

        return [
            'success' => true,
            'deleted' => $deleted,
        ];
    }

    /**
     * Check for orders approaching retention limit
     *
     * @return array Orders approaching deletion
     */
    public function check_order_retention_limits() {
        // Orders approaching 7-year retention limit (within 30 days)
        $warning_date = gmdate('Y-m-d', strtotime('-' . (self::RETENTION_ORDERS - 30) . ' days'));

        $args = [
            'post_type' => 'hs_order',
            'post_status' => 'any',
            'date_query' => [
                [
                    'before' => $warning_date,
                    'inclusive' => true,
                ],
            ],
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $orders = get_posts($args);

        if (!empty($orders)) {
            $this->send_retention_warning_notification($orders);
        }

        return [
            'success' => true,
            'orders_approaching_limit' => count($orders),
            'warning_date' => $warning_date,
        ];
    }

    /**
     * Generate compliance report
     *
     * @return array Report data
     */
    public function generate_compliance_report() {
        global $wpdb;

        $report = [
            'generated_at' => current_time('mysql'),
            'cart_sessions' => [
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hubspot_cart_items"),
                'oldest' => $wpdb->get_var("SELECT MIN(created_at) FROM {$wpdb->prefix}hubspot_cart_items"),
            ],
            'orders' => [
                'total' => wp_count_posts('hs_order')->publish ?? 0,
            ],
            'audit_logs' => [
                'active' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hubspot_audit_log"),
                'archived' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hubspot_audit_log_archive"),
            ],
        ];

        // Store report
        update_option('hubspot_ecommerce_last_compliance_report', $report);

        return [
            'success' => true,
            'report' => $report,
        ];
    }

    /**
     * Create archive table if it doesn't exist
     */
    private function maybe_create_archive_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hubspot_audit_log_archive';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            details text,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Log audit action
     *
     * @param string $action Action type
     * @param array $details Action details
     */
    private function log_audit_action($action, $details = []) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hubspot_audit_log';

        $wpdb->insert(
            $table_name,
            [
                'user_id' => get_current_user_id(),
                'action' => sanitize_key($action),
                'object_type' => 'data_cleanup',
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

        // Validate IP address
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * Log cleanup start
     *
     * @param string $type Cleanup type (daily/monthly)
     */
    private function log_cleanup_start($type) {
        $this->log_audit_action('cleanup_start', [
            'type' => $type,
            'timestamp' => current_time('mysql'),
        ]);
    }

    /**
     * Log cleanup results
     *
     * @param string $type Cleanup type
     * @param array $results Cleanup results
     */
    private function log_cleanup_results($type, $results) {
        $this->log_audit_action('cleanup_complete', [
            'type' => $type,
            'results' => $results,
            'timestamp' => current_time('mysql'),
        ]);
    }

    /**
     * Send cleanup notification email
     *
     * @param string $type Cleanup type
     * @param array $results Cleanup results
     */
    private function send_cleanup_notification($type, $results) {
        // Only send if enabled in settings
        if (!get_option('hubspot_ecommerce_cleanup_notifications', false)) {
            return;
        }

        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = sprintf(
            '[%s] HubSpot Ecommerce %s Cleanup Report',
            $site_name,
            ucfirst($type)
        );

        $message = $this->format_cleanup_notification($type, $results);

        wp_mail($admin_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
    }

    /**
     * Format cleanup notification message
     *
     * @param string $type Cleanup type
     * @param array $results Cleanup results
     * @return string Formatted HTML message
     */
    private function format_cleanup_notification($type, $results) {
        $html = '<h2>' . ucfirst($type) . ' Cleanup Report</h2>';
        $html .= '<p><strong>Completed:</strong> ' . current_time('mysql') . '</p>';
        $html .= '<h3>Results:</h3>';
        $html .= '<ul>';

        foreach ($results as $task => $result) {
            $html .= '<li><strong>' . esc_html(str_replace('_', ' ', ucfirst($task))) . ':</strong> ';

            if (isset($result['deleted'])) {
                $html .= esc_html($result['deleted']) . ' records deleted';
            } elseif (isset($result['archived'])) {
                $html .= esc_html($result['archived']) . ' records archived';
            } elseif (isset($result['message'])) {
                $html .= esc_html($result['message']);
            } else {
                $html .= 'Completed successfully';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '<p><small>This is an automated message from HubSpot Ecommerce data retention system.</small></p>';

        return $html;
    }

    /**
     * Send retention warning notification
     *
     * @param array $order_ids Order IDs approaching retention limit
     */
    private function send_retention_warning_notification($order_ids) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = sprintf(
            '[%s] Orders Approaching 7-Year Retention Limit',
            $site_name
        );

        $message = '<h2>Order Retention Warning</h2>';
        $message .= '<p>' . count($order_ids) . ' orders are approaching the 7-year retention limit and will need review.</p>';
        $message .= '<p>Please review these orders and determine if they should be:</p>';
        $message .= '<ul>';
        $message .= '<li>Retained for legal/compliance reasons</li>';
        $message .= '<li>Anonymized (customer data removed, financial records kept)</li>';
        $message .= '<li>Deleted (if permitted by law)</li>';
        $message .= '</ul>';
        $message .= '<p><a href="' . admin_url('edit.php?post_type=hs_order') . '">Review Orders</a></p>';

        wp_mail($admin_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);

        $this->log_audit_action('retention_warning_sent', [
            'order_count' => count($order_ids),
            'order_ids' => $order_ids,
        ]);
    }

    /**
     * Unschedule cleanup tasks (for deactivation)
     */
    public static function unschedule_cleanups() {
        $timestamp = wp_next_scheduled('hubspot_ecommerce_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'hubspot_ecommerce_daily_cleanup');
        }

        $timestamp = wp_next_scheduled('hubspot_ecommerce_monthly_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'hubspot_ecommerce_monthly_cleanup');
        }
    }

    /**
     * Get retention statistics (for admin dashboard)
     *
     * @return array Statistics
     */
    public function get_retention_stats() {
        global $wpdb;

        $stats = [
            'cart_sessions' => [
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hubspot_cart_items"),
                'expiring_soon' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}hubspot_cart_items
                    WHERE created_at < %s AND created_at > %s",
                    gmdate('Y-m-d H:i:s', strtotime('-' . (self::RETENTION_CART_SESSIONS - 7) . ' days')),
                    gmdate('Y-m-d H:i:s', strtotime('-' . self::RETENTION_CART_SESSIONS . ' days'))
                )),
            ],
            'orders' => [
                'total' => wp_count_posts('hs_order')->publish ?? 0,
            ],
            'last_cleanup' => get_option('hubspot_ecommerce_last_cleanup_run'),
            'next_cleanup' => wp_next_scheduled('hubspot_ecommerce_daily_cleanup'),
        ];

        return $stats;
    }
}
