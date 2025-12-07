<?php
/**
 * Rate Limiter for AJAX endpoints
 * Protects against brute force attacks
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Rate_Limiter {

    private static $instance = null;

    /**
     * Rate limit rules: [action => [max_attempts, time_window_seconds]]
     */
    private $rules = [
        'checkout' => [5, 300],      // 5 attempts per 5 minutes
        'add_to_cart' => [30, 60],   // 30 attempts per minute
        'login' => [5, 900],          // 5 attempts per 15 minutes
        'api_request' => [60, 60],   // 60 API requests per minute
    ];

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Intentionally empty - singleton pattern
    }

    /**
     * Check if action is rate limited
     *
     * @param string $action The action identifier
     * @param string $identifier Unique identifier (IP, user ID, session)
     * @return bool True if rate limited, false if allowed
     */
    public function is_rate_limited($action, $identifier = null) {
        // Get identifier (use IP if not provided)
        if (!$identifier) {
            $identifier = $this->get_client_ip();
        }

        // Get rule for this action
        if (!isset($this->rules[$action])) {
            return false; // No rule defined, allow
        }

        list($max_attempts, $time_window) = $this->rules[$action];

        // Generate transient key
        $transient_key = $this->get_transient_key($action, $identifier);

        // Get current attempt count
        $attempts = get_transient($transient_key);

        if ($attempts === false) {
            // No record, first attempt
            set_transient($transient_key, 1, $time_window);
            return false;
        }

        // Check if limit exceeded
        if ($attempts >= $max_attempts) {
            // Rate limited
            do_action('hubspot_ecommerce_rate_limited', $action, $identifier, $attempts);
            return true;
        }

        // Increment attempt count
        set_transient($transient_key, $attempts + 1, $time_window);
        return false;
    }

    /**
     * Record a successful action (for rate limiting)
     *
     * @param string $action The action identifier
     * @param string $identifier Unique identifier
     */
    public function record_attempt($action, $identifier = null) {
        if (!$identifier) {
            $identifier = $this->get_client_ip();
        }

        $transient_key = $this->get_transient_key($action, $identifier);
        $attempts = get_transient($transient_key);

        if ($attempts === false) {
            $attempts = 0;
        }

        if (isset($this->rules[$action])) {
            list($max_attempts, $time_window) = $this->rules[$action];
            set_transient($transient_key, $attempts + 1, $time_window);
        }
    }

    /**
     * Clear rate limit for an identifier
     *
     * @param string $action The action identifier
     * @param string $identifier Unique identifier
     */
    public function clear_rate_limit($action, $identifier = null) {
        if (!$identifier) {
            $identifier = $this->get_client_ip();
        }

        $transient_key = $this->get_transient_key($action, $identifier);
        delete_transient($transient_key);
    }

    /**
     * Get client IP address (considering proxies)
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR',           // Direct connection
        ];

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                // Handle multiple IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0'; // Fallback
    }

    /**
     * Generate transient key
     *
     * @param string $action
     * @param string $identifier
     * @return string
     */
    private function get_transient_key($action, $identifier) {
        return 'hs_ecom_rl_' . md5($action . '_' . $identifier);
    }

    /**
     * Get remaining attempts
     *
     * @param string $action
     * @param string $identifier
     * @return int
     */
    public function get_remaining_attempts($action, $identifier = null) {
        if (!$identifier) {
            $identifier = $this->get_client_ip();
        }

        if (!isset($this->rules[$action])) {
            return PHP_INT_MAX;
        }

        list($max_attempts, $time_window) = $this->rules[$action];
        $transient_key = $this->get_transient_key($action, $identifier);
        $attempts = get_transient($transient_key);

        if ($attempts === false) {
            return $max_attempts;
        }

        return max(0, $max_attempts - $attempts);
    }

    /**
     * Add custom rate limit rule
     *
     * @param string $action
     * @param int $max_attempts
     * @param int $time_window
     */
    public function add_rule($action, $max_attempts, $time_window) {
        $this->rules[$action] = [$max_attempts, $time_window];
    }

    /**
     * Send rate limit error response
     *
     * @param string $action
     */
    public function send_rate_limit_error($action = '') {
        $message = __('Too many requests. Please try again later.', 'hubspot-ecommerce');

        if (!empty($action) && isset($this->rules[$action])) {
            list($max_attempts, $time_window) = $this->rules[$action];
            $minutes = ceil($time_window / 60);
            $message = sprintf(
                __('Rate limit exceeded. Maximum %d attempts per %d minutes. Please try again later.', 'hubspot-ecommerce'),
                $max_attempts,
                $minutes
            );
        }

        wp_send_json_error([
            'message' => $message,
            'code' => 'rate_limit_exceeded',
        ], 429);
    }
}
