<?php
/**
 * Plugin Name: HubSpot Ecommerce
 * Plugin URI: https://github.com/baursoftware/hubspot-ecommerce
 * Description: A full-featured ecommerce solution using HubSpot as the backend for products, orders, and customer management.
 * Version: 1.0.0
 * Author: Todd Baur
 * Author URI: https://baursoftware.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hubspot-ecommerce
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HUBSPOT_ECOMMERCE_VERSION', '1.0.0');
define('HUBSPOT_ECOMMERCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HUBSPOT_ECOMMERCE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HUBSPOT_ECOMMERCE_PLUGIN_FILE', __FILE__);

// Require Composer autoloader
if (file_exists(HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'vendor/autoload.php';
}

// Main plugin class
final class HubSpot_Ecommerce {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init'], 0);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        // Rate limiter is optional - if file exists, load it
        $rate_limiter_path = HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-rate-limiter.php';
        if (file_exists($rate_limiter_path)) {
            require_once $rate_limiter_path;
        }

        // Mock API for demo mode
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-mock-hubspot-api.php';

        // OAuth client (loads FIRST - needed by validator)
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-oauth-client.php';

        // License Validator Service (standalone validation logic, needs OAuth client)
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-license-validator.php';

        // License API (REST endpoint for validating licenses)
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-license-api.php';

        // License Manager (loads after validator - needed for feature gating)
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-license-manager.php';

        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-hubspot-api.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-currency-manager.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-product-manager.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-cart.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-checkout.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-customer.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-subscription-manager.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-invoice-manager.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/webhooks/class-payment-webhook.php';

        // Privacy and compliance classes
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-data-cleanup.php';
        require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-gdpr-handler.php';

        // Admin classes
        if (is_admin()) {
            require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/admin/class-admin.php';
            require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/admin/class-settings.php';
            require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/admin/class-setup-wizard.php';
            require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/admin/class-product-meta-boxes.php';
            require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/admin/class-privacy-tools.php';

            // Initialize OAuth client
            HubSpot_Ecommerce_OAuth_Client::instance();
        }

        // Frontend classes
        if (!is_admin()) {
            require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/frontend/class-frontend.php';
            require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/frontend/class-template-loader.php';
        }

        // Initialize components
        HubSpot_Ecommerce_License_Manager::instance(); // Initialize license manager FIRST (feature gating)
        HubSpot_Ecommerce_License_API::instance(); // Initialize license API endpoint
        HubSpot_Ecommerce_Mock_API::instance(); // Initialize mock API second (checks for demo mode)
        HubSpot_Ecommerce_API::instance();
        HubSpot_Ecommerce_Currency_Manager::instance();
        HubSpot_Ecommerce_Product_Manager::instance();
        HubSpot_Ecommerce_Cart::instance();
        HubSpot_Ecommerce_Checkout::instance();
        HubSpot_Ecommerce_Customer::instance();
        HubSpot_Ecommerce_Subscription_Manager::instance();
        HubSpot_Ecommerce_Invoice_Manager::instance();
        HubSpot_Ecommerce_Payment_Webhook::instance();

        // Initialize privacy and compliance components
        HubSpot_Ecommerce_Data_Cleanup::instance();
        HubSpot_Ecommerce_GDPR_Handler::instance();

        if (is_admin()) {
            HubSpot_Ecommerce_Admin::instance();
            HubSpot_Ecommerce_Setup_Wizard::instance();
            HubSpot_Ecommerce_Product_Meta_Boxes::instance();
            HubSpot_Ecommerce_Privacy_Tools::instance();
        } else {
            HubSpot_Ecommerce_Frontend::instance();
        }
    }

    /**
     * Plugin initialization
     */
    public function init() {
        // Register custom post types
        $this->register_post_types();

        // Register taxonomies
        $this->register_taxonomies();

        // Flush rewrite rules if needed
        if (get_option('hubspot_ecommerce_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('hubspot_ecommerce_flush_rewrite_rules');
        }
    }

    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Products
        register_post_type('hs_product', [
            'labels' => [
                'name' => __('Products', 'hubspot-ecommerce'),
                'singular_name' => __('Product', 'hubspot-ecommerce'),
                'add_new' => __('Add New', 'hubspot-ecommerce'),
                'add_new_item' => __('Add New Product', 'hubspot-ecommerce'),
                'edit_item' => __('Edit Product', 'hubspot-ecommerce'),
                'new_item' => __('New Product', 'hubspot-ecommerce'),
                'view_item' => __('View Product', 'hubspot-ecommerce'),
                'search_items' => __('Search Products', 'hubspot-ecommerce'),
                'not_found' => __('No products found', 'hubspot-ecommerce'),
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'shop'],
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-cart',
        ]);

        // Orders (synced from HubSpot deals)
        register_post_type('hs_order', [
            'labels' => [
                'name' => __('Orders', 'hubspot-ecommerce'),
                'singular_name' => __('Order', 'hubspot-ecommerce'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap' => true,
            'supports' => ['title', 'custom-fields'],
            'menu_icon' => 'dashicons-list-view',
        ]);
    }

    /**
     * Register taxonomies
     */
    private function register_taxonomies() {
        // Product categories
        register_taxonomy('hs_product_cat', 'hs_product', [
            'labels' => [
                'name' => __('Categories', 'hubspot-ecommerce'),
                'singular_name' => __('Category', 'hubspot-ecommerce'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'shop-category'],
        ]);

        // Product tags
        register_taxonomy('hs_product_tag', 'hs_product', [
            'labels' => [
                'name' => __('Tags', 'hubspot-ecommerce'),
                'singular_name' => __('Tag', 'hubspot-ecommerce'),
            ],
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'shop-tag'],
        ]);
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'hubspot-ecommerce',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set flag to flush rewrite rules
        update_option('hubspot_ecommerce_flush_rewrite_rules', 1);

        // Create necessary database tables if needed
        $this->create_tables();

        // Set default options
        $this->set_default_options();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();

        // Unschedule cleanup tasks
        HubSpot_Ecommerce_Data_Cleanup::unschedule_cleanups();
    }

    /**
     * Create custom database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Cart items table
        $table_name = $wpdb->prefix . 'hubspot_cart_items';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            hubspot_product_id varchar(255) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY product_id (product_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Audit log table for compliance tracking
        $audit_table = $wpdb->prefix . 'hubspot_audit_log';

        $audit_sql = "CREATE TABLE IF NOT EXISTS $audit_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            details text,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at),
            KEY object_type (object_type)
        ) $charset_collate;";

        dbDelta($audit_sql);
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = [
            'hubspot_ecommerce_api_key' => '',
            'hubspot_ecommerce_sync_interval' => 'hourly',
            'hubspot_ecommerce_currency' => 'USD',
            'hubspot_ecommerce_shop_page' => '',
            'hubspot_ecommerce_cart_page' => '',
            'hubspot_ecommerce_checkout_page' => '',
            'hubspot_ecommerce_account_page' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

// Initialize the plugin
function hubspot_ecommerce() {
    return HubSpot_Ecommerce::instance();
}

// Kick off the plugin
hubspot_ecommerce();
