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
        add_action('admin_notices', [$this, 'show_missing_pages_notice']);

        // Add sync button to products admin
        add_filter('post_row_actions', [$this, 'add_sync_action'], 10, 2);
        add_action('admin_action_sync_hubspot_product', [$this, 'handle_sync_product_action']);
    }

    /**
     * Check if required pages are missing
     */
    private function get_missing_pages() {
        $required_pages = [
            'hubspot_ecommerce_shop_page' => 'Shop',
            'hubspot_ecommerce_cart_page' => 'Cart',
            'hubspot_ecommerce_checkout_page' => 'Checkout',
            'hubspot_ecommerce_account_page' => 'My Account',
        ];

        $missing = [];
        foreach ($required_pages as $option_name => $page_title) {
            $page_id = get_option($option_name);
            if (!$page_id || get_post_status($page_id) !== 'publish') {
                $missing[$option_name] = $page_title;
            }
        }

        return $missing;
    }

    /**
     * Show admin notice for missing pages
     */
    public function show_missing_pages_notice() {
        // Only show on HubSpot admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'hubspot-ecommerce') === false) {
            return;
        }

        // Don't show if user dismissed it
        if (get_option('hubspot_ecommerce_pages_notice_dismissed')) {
            return;
        }

        $missing_pages = $this->get_missing_pages();
        if (empty($missing_pages)) {
            return;
        }

        ?>
        <div class="notice notice-warning is-dismissible" id="hubspot-missing-pages-notice">
            <p>
                <strong><?php _e('HubSpot Ecommerce:', 'hubspot-ecommerce'); ?></strong>
                <?php _e('Some required pages are missing:', 'hubspot-ecommerce'); ?>
                <strong><?php echo esc_html(implode(', ', $missing_pages)); ?></strong>
            </p>
            <p>
                <button type="button" class="button button-primary" id="hubspot-create-pages">
                    <?php _e('Create Missing Pages Automatically', 'hubspot-ecommerce'); ?>
                </button>
                <button type="button" class="button" id="hubspot-dismiss-notice">
                    <?php _e('Dismiss', 'hubspot-ecommerce'); ?>
                </button>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#hubspot-create-pages').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('<?php esc_js(_e('Creating pages...', 'hubspot-ecommerce')); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hubspot_create_missing_pages',
                        nonce: '<?php echo wp_create_nonce('hubspot_create_pages'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#hubspot-missing-pages-notice')
                                .removeClass('notice-warning')
                                .addClass('notice-success')
                                .find('p').first().html('<strong><?php esc_js(_e('Success!', 'hubspot-ecommerce')); ?></strong> ' + response.data.message);
                            $('#hubspot-missing-pages-notice p').last().remove();
                            setTimeout(function() {
                                $('#hubspot-missing-pages-notice').fadeOut();
                            }, 3000);
                        } else {
                            alert(response.data.message);
                            $button.prop('disabled', false).text('<?php esc_js(_e('Create Missing Pages Automatically', 'hubspot-ecommerce')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php esc_js(_e('An error occurred. Please try again.', 'hubspot-ecommerce')); ?>');
                        $button.prop('disabled', false).text('<?php esc_js(_e('Create Missing Pages Automatically', 'hubspot-ecommerce')); ?>');
                    }
                });
            });

            $('#hubspot-dismiss-notice').on('click', function() {
                $.post(ajaxurl, {
                    action: 'hubspot_dismiss_pages_notice',
                    nonce: '<?php echo wp_create_nonce('hubspot_dismiss_notice'); ?>'
                });
                $('#hubspot-missing-pages-notice').fadeOut();
            });
        });
        </script>
        <?php
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
            'dashicons-cart',
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
            __('Product Sync', 'hubspot-ecommerce'),
            __('Product Sync', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-sync',
            [$this, 'render_sync_page']
        );

        add_submenu_page(
            'hubspot-ecommerce',
            __('License', 'hubspot-ecommerce'),
            __('License', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-license',
            [$this, 'render_license_page']
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

        // Get API authentication status
        $api = HubSpot_Ecommerce_API::instance();
        $auth_status = $api->get_auth_status();

        ?>
        <div class="wrap">
            <h1><?php _e('HubSpot Ecommerce Dashboard', 'hubspot-ecommerce'); ?></h1>

            <?php
            // Show authentication status
            if ($auth_status['mode'] === null): ?>
                <div class="notice notice-error">
                    <p>
                        <strong><?php _e('Authentication Required:', 'hubspot-ecommerce'); ?></strong>
                        <?php _e('Please install and configure the HubSpot plugin or add a Private App token in Settings.', 'hubspot-ecommerce'); ?>
                    </p>
                </div>
            <?php elseif ($auth_status['mode'] === 'leadin'): ?>
                <div class="notice notice-success">
                    <p>
                        <strong><?php _e('Authenticated via HubSpot Plugin', 'hubspot-ecommerce'); ?></strong>
                        <?php printf(__('Portal ID: %s', 'hubspot-ecommerce'), esc_html($auth_status['portal_id'])); ?>
                    </p>
                </div>
            <?php elseif ($auth_status['mode'] === 'private_app'): ?>
                <div class="notice notice-success">
                    <p>
                        <strong><?php _e('Authenticated via Private App Token', 'hubspot-ecommerce'); ?></strong>
                    </p>
                </div>
            <?php endif; ?>

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
        $license_manager = HubSpot_Ecommerce_License_Manager::instance();
        $is_pro = $license_manager->can_use_auto_sync();
        ?>
        <div class="wrap">
            <h1><?php _e('Product Sync', 'hubspot-ecommerce'); ?></h1>

            <?php if (!$is_pro) : ?>
                <div class="notice notice-info">
                    <p>
                        <strong><?php _e('Manual Sync Available (Free)', 'hubspot-ecommerce'); ?></strong><br>
                        <?php _e('Use the button below to manually sync products from HubSpot to WordPress.', 'hubspot-ecommerce'); ?><br>
                        <?php _e('Want automatic scheduled syncing?', 'hubspot-ecommerce'); ?>
                        <a href="https://baursoftware.com/products/hubspot-ecommerce" target="_blank">
                            <?php _e('Upgrade to Pro', 'hubspot-ecommerce'); ?> &rarr;
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="hubspot-sync-container">
                <p><?php _e('This will pull all products from your HubSpot account and sync them to WordPress.', 'hubspot-ecommerce'); ?></p>

                <button id="hubspot-sync-products" class="button button-primary button-hero">
                    <?php _e('Pull Products from HubSpot', 'hubspot-ecommerce'); ?>
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

    /**
     * Render license page
     */
    public function render_license_page() {
        $license = HubSpot_Ecommerce_License_Manager::instance();
        $tier = $license->get_tier();
        $status = $license->get_status();

        // Show any admin notices from license actions
        settings_errors('hubspot_ecommerce_license');

        ?>
        <div class="wrap">
            <h1><?php _e('License Management', 'hubspot-ecommerce'); ?></h1>

            <!-- Current Status -->
            <div class="card" style="max-width: 600px;">
                <h2><?php _e('Current Plan', 'hubspot-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Tier:', 'hubspot-ecommerce'); ?></th>
                        <td>
                            <strong style="font-size: 18px; text-transform: capitalize;">
                                <?php echo esc_html($tier); ?>
                            </strong>
                            <?php if ($tier === 'free'): ?>
                                <a href="<?php echo esc_url($license->get_upgrade_url()); ?>"
                                   class="button button-primary"
                                   target="_blank"
                                   style="margin-left: 10px;">
                                    <?php _e('Upgrade to Pro', 'hubspot-ecommerce'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($tier !== 'free'): ?>
                    <tr>
                        <th><?php _e('Status:', 'hubspot-ecommerce'); ?></th>
                        <td>
                            <?php if ($status === 'active'): ?>
                                <span style="color: green; font-size: 16px;">● <?php _e('Active', 'hubspot-ecommerce'); ?></span>
                            <?php else: ?>
                                <span style="color: red; font-size: 16px;">● <?php echo esc_html(ucfirst($status)); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Activate/Deactivate License -->
            <?php if ($tier === 'free'): ?>
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2><?php _e('Activate License', 'hubspot-ecommerce'); ?></h2>
                <p><?php _e('Enter your license key to unlock Pro features.', 'hubspot-ecommerce'); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field('hubspot_activate_license', 'hubspot_license_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="license_key"><?php _e('License Key:', 'hubspot-ecommerce'); ?></label></th>
                            <td>
                                <input type="text"
                                       id="license_key"
                                       name="license_key"
                                       class="regular-text code"
                                       placeholder="XXXX-XXXX-XXXX-XXXX"
                                       style="font-family: monospace; font-size: 14px;">
                                <p class="description">
                                    <?php _e('Enter the license key you received via email after purchase.', 'hubspot-ecommerce'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="activate_license" class="button button-primary">
                            <?php _e('Activate License', 'hubspot-ecommerce'); ?>
                        </button>
                    </p>
                </form>

                <p>
                    <?php _e("Don't have a license?", 'hubspot-ecommerce'); ?>
                    <a href="<?php echo esc_url($license->get_upgrade_url()); ?>" target="_blank">
                        <?php _e('Purchase one now →', 'hubspot-ecommerce'); ?>
                    </a>
                </p>
            </div>
            <?php else: ?>
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2><?php _e('Manage License', 'hubspot-ecommerce'); ?></h2>
                <p><?php _e('Deactivating your license will disable Pro features and revert to the Free tier.', 'hubspot-ecommerce'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('hubspot_deactivate_license', 'hubspot_license_nonce'); ?>
                    <p>
                        <button type="submit"
                                name="deactivate_license"
                                class="button button-secondary"
                                onclick="return confirm('<?php esc_attr_e('Are you sure? Pro features will be locked.', 'hubspot-ecommerce'); ?>');">
                            <?php _e('Deactivate License', 'hubspot-ecommerce'); ?>
                        </button>
                    </p>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

// AJAX handler for product sync
add_action('wp_ajax_hubspot_sync_products', function() {
    check_ajax_referer('hubspot_ecommerce_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'hubspot-ecommerce')]);
    }

    // Check authentication first
    $api = HubSpot_Ecommerce_API::instance();
    $auth_status = $api->get_auth_status();

    if ($auth_status['mode'] === null) {
        wp_send_json_error([
            'message' => __('HubSpot authentication not configured. Please install the HubSpot plugin or add a Private App token in Settings.', 'hubspot-ecommerce'),
        ]);
    }

    $product_manager = HubSpot_Ecommerce_Product_Manager::instance();
    $result = $product_manager->sync_products();

    if (!empty($result['errors'])) {
        // Show detailed error information
        $error_message = sprintf(
            __('Synced %d products with %d errors', 'hubspot-ecommerce'),
            $result['synced'],
            count($result['errors'])
        );

        // Add first error for quick diagnosis
        if (isset($result['errors'][0])) {
            $error_message .= ': ' . $result['errors'][0];
        }

        wp_send_json_error([
            'message' => $error_message,
            'errors' => $result['errors'],
            'synced' => $result['synced'],
        ]);
    }

    // Success - but check if any products were actually synced
    if ($result['synced'] === 0) {
        wp_send_json_success([
            'synced' => 0,
            'message' => __('Sync completed, but no products were found in HubSpot. Make sure you have products in your HubSpot account.', 'hubspot-ecommerce'),
        ]);
    }

    wp_send_json_success([
        'synced' => $result['synced'],
        'message' => sprintf(__('Successfully synced %d products', 'hubspot-ecommerce'), $result['synced']),
    ]);
});

// AJAX handler for creating missing pages
add_action('wp_ajax_hubspot_create_missing_pages', function() {
    check_ajax_referer('hubspot_create_pages', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'hubspot-ecommerce')]);
    }

    $pages_to_create = [
        'hubspot_ecommerce_shop_page' => [
            'title' => __('Shop', 'hubspot-ecommerce'),
            'content' => '[hubspot_products]',
        ],
        'hubspot_ecommerce_cart_page' => [
            'title' => __('Cart', 'hubspot-ecommerce'),
            'content' => '[hubspot_cart]',
        ],
        'hubspot_ecommerce_checkout_page' => [
            'title' => __('Checkout', 'hubspot-ecommerce'),
            'content' => '[hubspot_checkout]',
        ],
        'hubspot_ecommerce_account_page' => [
            'title' => __('My Account', 'hubspot-ecommerce'),
            'content' => '[hubspot_account]',
        ],
    ];

    $created_pages = [];
    foreach ($pages_to_create as $option_name => $page_data) {
        // Check if page already exists
        $existing_page_id = get_option($option_name);
        if ($existing_page_id && get_post_status($existing_page_id) === 'publish') {
            continue;
        }

        // Create the page
        $page_id = wp_insert_post([
            'post_title' => $page_data['title'],
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
        ]);

        if (!is_wp_error($page_id)) {
            update_option($option_name, $page_id);
            $created_pages[] = $page_data['title'];
        }
    }

    if (empty($created_pages)) {
        wp_send_json_error(['message' => __('No pages needed to be created.', 'hubspot-ecommerce')]);
    }

    wp_send_json_success([
        'message' => sprintf(
            __('Successfully created %d page(s): %s', 'hubspot-ecommerce'),
            count($created_pages),
            implode(', ', $created_pages)
        ),
    ]);
});

// AJAX handler for dismissing the notice
add_action('wp_ajax_hubspot_dismiss_pages_notice', function() {
    check_ajax_referer('hubspot_dismiss_notice', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions', 'hubspot-ecommerce')]);
    }

    update_option('hubspot_ecommerce_pages_notice_dismissed', true);
    wp_send_json_success();
});
