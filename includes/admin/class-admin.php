<?php
/**
 * Admin functionality
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Admin {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Add sync button to products admin
        add_filter('post_row_actions', [$this, 'add_sync_action'], 10, 2);
        add_action('admin_action_sync_hubspot_product', [$this, 'handle_sync_product_action']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('HubSpot Ecommerce', 'hubspot-ecommerce'),
            __('HubSpot Shop', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce',
            [$this, 'render_dashboard_page'],
            'dashicons-hubspot',
            30
        );

        add_submenu_page(
            'hubspot-ecommerce',
            __('Dashboard', 'hubspot-ecommerce'),
            __('Dashboard', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'hubspot-ecommerce',
            __('Settings', 'hubspot-ecommerce'),
            __('Settings', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'hubspot-ecommerce',
            __('Sync Products', 'hubspot-ecommerce'),
            __('Sync Products', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-sync',
            [$this, 'render_sync_page']
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'hubspot-ecommerce') === false) {
            return;
        }

        wp_enqueue_style(
            'hubspot-ecommerce-admin',
            HUBSPOT_ECOMMERCE_PLUGIN_URL . 'assets/css/admin.css',
            [],
            HUBSPOT_ECOMMERCE_VERSION
        );

        wp_enqueue_script(
            'hubspot-ecommerce-admin',
            HUBSPOT_ECOMMERCE_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            HUBSPOT_ECOMMERCE_VERSION,
            true
        );

        wp_localize_script('hubspot-ecommerce-admin', 'hubspotEcommerceAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hubspot_ecommerce_admin_nonce'),
        ]);
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $last_sync = get_option('hubspot_ecommerce_last_sync');
        $product_count = wp_count_posts('hs_product')->publish;
        $order_count = wp_count_posts('hs_order')->publish;

        ?>
        <div class="wrap">
            <h1><?php _e('HubSpot Ecommerce Dashboard', 'hubspot-ecommerce'); ?></h1>

            <div class="hubspot-dashboard-cards">
                <div class="hubspot-card">
                    <h3><?php _e('Products', 'hubspot-ecommerce'); ?></h3>
                    <div class="hubspot-card-value"><?php echo esc_html($product_count); ?></div>
                    <a href="<?php echo admin_url('edit.php?post_type=hs_product'); ?>" class="button">
                        <?php _e('View Products', 'hubspot-ecommerce'); ?>
                    </a>
                </div>

                <div class="hubspot-card">
                    <h3><?php _e('Orders', 'hubspot-ecommerce'); ?></h3>
                    <div class="hubspot-card-value"><?php echo esc_html($order_count); ?></div>
                    <a href="<?php echo admin_url('edit.php?post_type=hs_order'); ?>" class="button">
                        <?php _e('View Orders', 'hubspot-ecommerce'); ?>
                    </a>
                </div>

                <div class="hubspot-card">
                    <h3><?php _e('Last Sync', 'hubspot-ecommerce'); ?></h3>
                    <div class="hubspot-card-value">
                        <?php
                        if ($last_sync && isset($last_sync['timestamp'])) {
                            echo esc_html(human_time_diff(strtotime($last_sync['timestamp']), current_time('timestamp')) . ' ago');
                        } else {
                            _e('Never', 'hubspot-ecommerce');
                        }
                        ?>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=hubspot-ecommerce-sync'); ?>" class="button button-primary">
                        <?php _e('Sync Now', 'hubspot-ecommerce'); ?>
                    </a>
                </div>
            </div>

            <?php if ($last_sync && !empty($last_sync['errors'])): ?>
                <div class="notice notice-warning">
                    <p><?php _e('Last sync had errors:', 'hubspot-ecommerce'); ?></p>
                    <ul>
                        <?php foreach ($last_sync['errors'] as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        HubSpot_Ecommerce_Settings::instance()->render();
    }

    /**
     * Render sync page
     */
    public function render_sync_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Sync Products from HubSpot', 'hubspot-ecommerce'); ?></h1>

            <div class="hubspot-sync-container">
                <p><?php _e('This will sync all products from your HubSpot account to WordPress.', 'hubspot-ecommerce'); ?></p>

                <button id="hubspot-sync-products" class="button button-primary button-hero">
                    <?php _e('Sync Products Now', 'hubspot-ecommerce'); ?>
                </button>

                <div id="hubspot-sync-progress" style="display:none; margin-top: 20px;">
                    <p><?php _e('Syncing products...', 'hubspot-ecommerce'); ?></p>
                    <progress style="width: 100%;"></progress>
                </div>

                <div id="hubspot-sync-result" style="margin-top: 20px;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#hubspot-sync-products').on('click', function() {
                var $button = $(this);
                var $progress = $('#hubspot-sync-progress');
                var $result = $('#hubspot-sync-result');

                $button.prop('disabled', true);
                $progress.show();
                $result.html('');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hubspot_sync_products',
                        nonce: '<?php echo wp_create_nonce('hubspot_ecommerce_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        $progress.hide();
                        $button.prop('disabled', false);

                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' +
                                '<?php _e('Successfully synced', 'hubspot-ecommerce'); ?> ' +
                                response.data.synced + ' <?php _e('products', 'hubspot-ecommerce'); ?>' +
                                '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $progress.hide();
                        $button.prop('disabled', false);
                        $result.html('<div class="notice notice-error"><p><?php _e('Sync failed', 'hubspot-ecommerce'); ?></p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Add sync action to product row
     */
    public function add_sync_action($actions, $post) {
        if ($post->post_type === 'hs_product') {
            $hubspot_id = get_post_meta($post->ID, '_hubspot_product_id', true);

            if ($hubspot_id) {
                $actions['sync_hubspot'] = sprintf(
                    '<a href="%s">%s</a>',
                    wp_nonce_url(
                        admin_url('admin.php?action=sync_hubspot_product&post=' . $post->ID),
                        'sync_hubspot_product_' . $post->ID
                    ),
                    __('Sync from HubSpot', 'hubspot-ecommerce')
                );
            }
        }

        return $actions;
    }

    /**
     * Handle sync product action
     */
    public function handle_sync_product_action() {
        if (!isset($_GET['post']) || !check_admin_referer('sync_hubspot_product_' . $_GET['post'])) {
            wp_die(__('Invalid request', 'hubspot-ecommerce'));
        }

        $post_id = intval($_GET['post']);
        $hubspot_id = get_post_meta($post_id, '_hubspot_product_id', true);

        if (!$hubspot_id) {
            wp_die(__('No HubSpot ID found', 'hubspot-ecommerce'));
        }

        $api = HubSpot_Ecommerce_API::instance();
        $product = $api->get_product($hubspot_id);

        if (is_wp_error($product)) {
            wp_die($product->get_error_message());
        }

        $product_manager = HubSpot_Ecommerce_Product_Manager::instance();
        $result = $product_manager->sync_single_product($product);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_redirect(admin_url('edit.php?post_type=hs_product&synced=1'));
        exit;
    }
}

// AJAX handler for product sync
add_action('wp_ajax_hubspot_sync_products', function() {
    check_ajax_referer('hubspot_ecommerce_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'hubspot-ecommerce')]);
    }

    $product_manager = HubSpot_Ecommerce_Product_Manager::instance();
    $result = $product_manager->sync_products();

    if (!empty($result['errors'])) {
        wp_send_json_error([
            'message' => sprintf(
                __('Synced %d products with %d errors', 'hubspot-ecommerce'),
                $result['synced'],
                count($result['errors'])
            ),
            'errors' => $result['errors'],
        ]);
    }

    wp_send_json_success([
        'synced' => $result['synced'],
        'message' => sprintf(__('Successfully synced %d products', 'hubspot-ecommerce'), $result['synced']),
    ]);
});
