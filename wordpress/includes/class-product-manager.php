<?php
/**
 * Product Manager - Syncs products from HubSpot
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Product_Manager {

    private static $instance = null;
    private $api;
    private $currency_manager;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api = HubSpot_Ecommerce_API::instance();
        $this->currency_manager = HubSpot_Ecommerce_Currency_Manager::instance();

        // Schedule product sync
        add_action('hubspot_ecommerce_sync_products', [$this, 'sync_products']);

        // Schedule automatic sync only if Pro and enabled (Pro feature)
        $license_manager = HubSpot_Ecommerce_License_Manager::instance();
        $auto_sync_enabled = get_option('hubspot_ecommerce_auto_sync_from_hubspot', false);

        if ($license_manager->can_use_auto_sync() && $auto_sync_enabled) {
            if (!wp_next_scheduled('hubspot_ecommerce_sync_products')) {
                $interval = get_option('hubspot_ecommerce_sync_interval', 'hourly');
                wp_schedule_event(time(), $interval, 'hubspot_ecommerce_sync_products');
            }
        } else {
            // Clear scheduled sync if not Pro or disabled
            $timestamp = wp_next_scheduled('hubspot_ecommerce_sync_products');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'hubspot_ecommerce_sync_products');
            }
        }

        // Add custom columns to products admin
        add_filter('manage_hs_product_posts_columns', [$this, 'add_product_columns']);
        add_action('manage_hs_product_posts_custom_column', [$this, 'render_product_columns'], 10, 2);
    }

    /**
     * Sync all products from HubSpot
     */
    public function sync_products() {
        $after = null;
        $synced_count = 0;
        $errors = [];

        do {
            $response = $this->api->get_products(100, $after);

            if (is_wp_error($response)) {
                $errors[] = $response->get_error_message();
                break;
            }

            if (!isset($response['results']) || !is_array($response['results'])) {
                break;
            }

            foreach ($response['results'] as $hubspot_product) {
                $result = $this->sync_single_product($hubspot_product);
                if (!is_wp_error($result)) {
                    $synced_count++;
                } else {
                    $errors[] = $result->get_error_message();
                }
            }

            // Check if there are more pages
            $after = isset($response['paging']['next']['after']) ? $response['paging']['next']['after'] : null;

        } while ($after !== null);

        // Log sync results
        update_option('hubspot_ecommerce_last_sync', [
            'timestamp' => current_time('mysql'),
            'synced_count' => $synced_count,
            'errors' => $errors,
        ]);

        do_action('hubspot_ecommerce_products_synced', $synced_count, $errors);

        return [
            'synced' => $synced_count,
            'errors' => $errors,
        ];
    }

    /**
     * Sync a single product from HubSpot
     */
    public function sync_single_product($hubspot_product) {
        $hubspot_id = $hubspot_product['id'];
        $properties = $hubspot_product['properties'];

        // Check if product already exists
        $existing_post = $this->get_product_by_hubspot_id($hubspot_id);

        $post_data = [
            'post_title' => sanitize_text_field($properties['name'] ?? ''),
            'post_content' => wp_kses_post($properties['description'] ?? ''),
            'post_status' => 'publish',
            'post_type' => 'hs_product',
        ];

        if ($existing_post) {
            $post_data['ID'] = $existing_post->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save product meta
        update_post_meta($post_id, '_hubspot_product_id', $hubspot_id);
        update_post_meta($post_id, '_price', floatval($properties['price'] ?? 0));
        update_post_meta($post_id, '_sku', sanitize_text_field($properties['hs_sku'] ?? ''));
        update_post_meta($post_id, '_cost_of_goods', floatval($properties['hs_cost_of_goods_sold'] ?? 0));
        update_post_meta($post_id, '_product_type', sanitize_text_field($properties['hs_product_type'] ?? 'simple'));

        // Save currency-specific prices from HubSpot
        $enabled_currencies = $this->currency_manager->get_enabled_currencies();
        foreach ($enabled_currencies as $currency) {
            $code = strtolower($currency['code']);
            $price_field = "hs_price_{$code}";

            if (isset($properties[$price_field]) && !empty($properties[$price_field])) {
                // Store currency-specific price
                update_post_meta($post_id, "_price_{$code}", floatval($properties[$price_field]));
            } else {
                // Remove meta if price not set in HubSpot
                delete_post_meta($post_id, "_price_{$code}");
            }
        }

        // Save subscription/recurring billing info
        if (!empty($properties['hs_recurring_billing_period'])) {
            update_post_meta($post_id, '_is_subscription', true);
            update_post_meta($post_id, '_recurring_billing_period', sanitize_text_field($properties['hs_recurring_billing_period']));
            update_post_meta($post_id, '_recurring_billing_frequency', sanitize_text_field($properties['recurringbillingfrequency'] ?? ''));
            update_post_meta($post_id, '_billing_period_units', sanitize_text_field($properties['hs_billing_period_units'] ?? ''));
        } else {
            update_post_meta($post_id, '_is_subscription', false);
        }

        // Handle product images
        if (!empty($properties['hs_images'])) {
            $this->sync_product_images($post_id, $properties['hs_images']);
        }

        // Save sanitized HubSpot data for reference
        $safe_hubspot_data = [
            'id' => sanitize_text_field($hubspot_product['id'] ?? ''),
            'created_at' => sanitize_text_field($hubspot_product['createdAt'] ?? ''),
            'updated_at' => sanitize_text_field($hubspot_product['updatedAt'] ?? ''),
            'archived' => (bool) ($hubspot_product['archived'] ?? false),
            'properties' => [
                'name' => sanitize_text_field($properties['name'] ?? ''),
                'description' => sanitize_textarea_field($properties['description'] ?? ''),
                'price' => floatval($properties['price'] ?? 0),
                'hs_sku' => sanitize_text_field($properties['hs_sku'] ?? ''),
                'hs_cost_of_goods_sold' => floatval($properties['hs_cost_of_goods_sold'] ?? 0),
                'hs_product_type' => sanitize_text_field($properties['hs_product_type'] ?? ''),
                'hs_recurring_billing_period' => sanitize_text_field($properties['hs_recurring_billing_period'] ?? ''),
                'recurringbillingfrequency' => sanitize_text_field($properties['recurringbillingfrequency'] ?? ''),
                'hs_billing_period_units' => sanitize_text_field($properties['hs_billing_period_units'] ?? ''),
            ],
        ];
        update_post_meta($post_id, '_hubspot_data', $safe_hubspot_data);

        do_action('hubspot_ecommerce_product_synced', $post_id, $hubspot_product);

        return $post_id;
    }

    /**
     * Sync product images from HubSpot
     */
    private function sync_product_images($post_id, $images_data) {
        // HubSpot stores images as a semicolon-separated list of URLs
        $image_urls = explode(';', $images_data);
        $image_urls = array_filter(array_map('trim', $image_urls));

        if (empty($image_urls)) {
            return;
        }

        // Set the first image as featured image
        $featured_image_url = $image_urls[0];
        $attachment_id = $this->upload_image_from_url($featured_image_url, $post_id);

        if ($attachment_id && !is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }

        // Store all image URLs as meta
        update_post_meta($post_id, '_product_images', $image_urls);
    }

    /**
     * Upload image from URL to WordPress media library
     */
    private function upload_image_from_url($url, $post_id = 0) {
        // 1. Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('Invalid image URL', 'hubspot-ecommerce'));
        }

        // 2. Require HTTPS
        if (strpos($url, 'https://') !== 0) {
            return new WP_Error('insecure_url', __('Only HTTPS URLs allowed', 'hubspot-ecommerce'));
        }

        // 3. Whitelist HubSpot domains
        $allowed_domains = ['hs-fs.hubspot.net', 'hubspot.com', 'hubspot.net'];
        $parsed_url = parse_url($url);
        $domain = $parsed_url['host'] ?? '';

        $is_allowed = false;
        foreach ($allowed_domains as $allowed_domain) {
            if (stripos($domain, $allowed_domain) !== false) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            return new WP_Error('domain_not_allowed', __('Image must be from HubSpot', 'hubspot-ecommerce'));
        }

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Check if image already exists
        $existing = $this->get_attachment_by_url($url);
        if ($existing) {
            return $existing;
        }

        // 4. Download file
        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            return $tmp;
        }

        // 5. Validate file type by content
        $file_type = wp_check_filetype(basename($url), null);
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file_type['type'], $allowed_types, true)) {
            @unlink($tmp);
            return new WP_Error('invalid_file_type', __('Invalid image type', 'hubspot-ecommerce'));
        }

        // 6. Validate actual image content
        $image_info = @getimagesize($tmp);
        if ($image_info === false) {
            @unlink($tmp);
            return new WP_Error('invalid_image', __('File is not a valid image', 'hubspot-ecommerce'));
        }

        // 7. Check file size (max 5MB)
        if (filesize($tmp) > 5 * 1024 * 1024) {
            @unlink($tmp);
            return new WP_Error('file_too_large', __('Image exceeds 5MB limit', 'hubspot-ecommerce'));
        }

        $file_array = [
            'name' => basename($url),
            'tmp_name' => $tmp,
        ];

        // Upload to media library
        $id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return $id;
        }

        // Store source URL for future reference
        update_post_meta($id, '_source_url', $url);

        return $id;
    }

    /**
     * Get attachment ID by source URL
     */
    private function get_attachment_by_url($url) {
        global $wpdb;

        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_source_url' AND meta_value = %s LIMIT 1",
            $url
        ));

        return $attachment_id ? intval($attachment_id) : null;
    }

    /**
     * Get WordPress product by HubSpot ID
     */
    private function get_product_by_hubspot_id($hubspot_id) {
        $args = [
            'post_type' => 'hs_product',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_hubspot_product_id',
                    'value' => $hubspot_id,
                    'compare' => '=',
                ],
            ],
        ];

        $query = new WP_Query($args);

        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Get product price in specific currency
     *
     * @param int $post_id Product post ID
     * @param string $currency_code Optional currency code (defaults to site setting)
     * @return float Price in requested currency
     */
    public function get_product_price($post_id, $currency_code = null) {
        // If no currency specified, use site default
        if (is_null($currency_code)) {
            $currency_code = get_option('hubspot_ecommerce_currency', 'USD');
        }

        // Try to get currency-specific price
        $code = strtolower($currency_code);
        $currency_price = get_post_meta($post_id, "_price_{$code}", true);

        if (!empty($currency_price)) {
            return floatval($currency_price);
        }

        // Fallback to default price
        return floatval(get_post_meta($post_id, '_price', true));
    }

    /**
     * Get all available prices for a product across all currencies
     *
     * @param int $post_id Product post ID
     * @return array Array of currency code => price
     */
    public function get_product_prices_all_currencies($post_id) {
        $prices = [];

        // Get default price
        $default_price = floatval(get_post_meta($post_id, '_price', true));
        $default_currency = get_option('hubspot_ecommerce_currency', 'USD');
        $prices[strtoupper($default_currency)] = $default_price;

        // Get currency-specific prices
        $enabled_currencies = $this->currency_manager->get_enabled_currencies();
        foreach ($enabled_currencies as $currency) {
            $code = $currency['code'];
            $code_lower = strtolower($code);
            $currency_price = get_post_meta($post_id, "_price_{$code_lower}", true);

            if (!empty($currency_price)) {
                $prices[$code] = floatval($currency_price);
            }
        }

        return $prices;
    }

    /**
     * Check if product has price for specific currency
     *
     * @param int $post_id Product post ID
     * @param string $currency_code Currency code to check
     * @return bool True if price exists for currency
     */
    public function has_currency_price($post_id, $currency_code) {
        $code = strtolower($currency_code);
        $price = get_post_meta($post_id, "_price_{$code}", true);
        return !empty($price);
    }

    /**
     * Get product SKU
     */
    public function get_product_sku($post_id) {
        return get_post_meta($post_id, '_sku', true);
    }

    /**
     * Get HubSpot product ID
     */
    public function get_hubspot_product_id($post_id) {
        return get_post_meta($post_id, '_hubspot_product_id', true);
    }

    /**
     * Format price for display using Currency Manager
     *
     * @param float $price Price to format
     * @param string $currency_code Optional currency code (defaults to site setting)
     * @return string Formatted price with proper symbol, decimals, and separators
     */
    public function format_price($price, $currency_code = null) {
        return $this->currency_manager->format_price($price, $currency_code);
    }

    /**
     * Add custom columns to products admin table
     */
    public function add_product_columns($columns) {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['price'] = __('Price', 'hubspot-ecommerce');
                $new_columns['sku'] = __('SKU', 'hubspot-ecommerce');
                $new_columns['hubspot_id'] = __('HubSpot ID', 'hubspot-ecommerce');
            }
        }

        return $new_columns;
    }

    /**
     * Render custom columns in products admin table
     */
    public function render_product_columns($column, $post_id) {
        switch ($column) {
            case 'price':
                $price = $this->get_product_price($post_id);
                echo esc_html($this->format_price($price));
                break;

            case 'sku':
                $sku = $this->get_product_sku($post_id);
                echo esc_html($sku);
                break;

            case 'hubspot_id':
                $hubspot_id = $this->get_hubspot_product_id($post_id);
                echo esc_html($hubspot_id);
                break;
        }
    }
}
