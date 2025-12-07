<?php
/**
 * Settings page
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Settings {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Settings
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_sync_interval');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_currency');

        // Pro feature: Private App Access Token
        $license_manager = HubSpot_Ecommerce_License_Manager::instance();
        if ($license_manager->can_use_private_app()) {
            register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_api_key');
        }

        // Page Settings
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_shop_page');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_cart_page');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_checkout_page');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_account_page');

        // Floating Cart Widget Settings
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_cart_icon');
    }

    /**
     * Render settings page
     */
    public function render() {
        // Handle currency sync action
        if (isset($_GET['action']) && $_GET['action'] === 'sync_currencies') {
            $currency_manager = HubSpot_Ecommerce_Currency_Manager::instance();
            $results = $currency_manager->sync_currencies();

            if (empty($results['errors'])) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                printf(
                    __('Successfully synced %d currencies from HubSpot. Company currency: %s', 'hubspot-ecommerce'),
                    count($results['enabled_currencies']),
                    $results['company_currency']
                );
                echo '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>';
                echo esc_html(__('Errors syncing currencies:', 'hubspot-ecommerce') . ' ' . implode(', ', $results['errors']));
                echo '</p></div>';
            }
        }

        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        $license_manager = HubSpot_Ecommerce_License_Manager::instance();
        $api_key = get_option('hubspot_ecommerce_api_key', '');
        $sync_interval = get_option('hubspot_ecommerce_sync_interval', 'hourly');
        $currency = get_option('hubspot_ecommerce_currency', 'USD');

        ?>
        <div class="wrap">
            <h1><?php _e('HubSpot Ecommerce Settings', 'hubspot-ecommerce'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('hubspot_ecommerce_settings', 'hubspot_ecommerce_settings_nonce'); ?>

                <?php if ($license_manager->can_use_private_app()) : ?>
                    <h2><?php _e('API Configuration', 'hubspot-ecommerce'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="hubspot_api_key"><?php _e('Private App Access Token', 'hubspot-ecommerce'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="hubspot_api_key" name="hubspot_ecommerce_api_key"
                                       value="<?php echo esc_attr($api_key); ?>" class="large-text" />
                                <p class="description">
                                    <?php printf(
                                        __('Create a Private App in HubSpot: <a href="%s" target="_blank">Settings → Integrations → Private Apps</a>', 'hubspot-ecommerce'),
                                        'https://knowledge.hubspot.com/integrations/how-do-i-get-my-hubspot-api-key'
                                    ); ?>
                                </p>
                                <button type="button" id="test-connection" class="button">
                                    <?php _e('Test Connection', 'hubspot-ecommerce'); ?>
                                </button>
                                <span id="connection-status"></span>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>

                <h2><?php _e('General Settings', 'hubspot-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="currency"><?php _e('Currency', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <?php
                            $currency_manager = HubSpot_Ecommerce_Currency_Manager::instance();
                            $enabled_currencies = $currency_manager->get_enabled_currencies();
                            $all_currency_data = $currency_manager->get_all_currency_data();
                            ?>
                            <select id="currency" name="hubspot_ecommerce_currency">
                                <?php foreach ($enabled_currencies as $curr): ?>
                                    <?php
                                    $code = $curr['code'];
                                    $name = isset($all_currency_data[$code]) ? $all_currency_data[$code]['name'] : $code;
                                    $symbol = isset($all_currency_data[$code]) ? $all_currency_data[$code]['symbol'] : $code;
                                    ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($currency, $code); ?>>
                                        <?php echo esc_html($code . ' - ' . $name . ' (' . $symbol . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Default currency for product pricing. Synced from your HubSpot account.', 'hubspot-ecommerce'); ?>
                                <br>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=hubspot-ecommerce-settings&action=sync_currencies')); ?>" class="button button-small" style="margin-top: 5px;">
                                    <?php _e('Sync Currencies from HubSpot', 'hubspot-ecommerce'); ?>
                                </a>
                            </p>
                            <?php
                            // Show last sync info
                            $last_sync = get_option('hubspot_ecommerce_currency_sync');
                            if ($last_sync && isset($last_sync['timestamp'])):
                            ?>
                                <p class="description" style="margin-top: 10px;">
                                    <strong><?php _e('Last synced:', 'hubspot-ecommerce'); ?></strong>
                                    <?php echo esc_html($last_sync['timestamp']); ?>
                                    <br>
                                    <strong><?php _e('Company currency:', 'hubspot-ecommerce'); ?></strong>
                                    <?php echo esc_html($last_sync['company_currency'] ?? 'N/A'); ?>
                                    <br>
                                    <strong><?php _e('Enabled currencies:', 'hubspot-ecommerce'); ?></strong>
                                    <?php echo esc_html($last_sync['enabled_count'] ?? 0); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <!-- Pro Features Section -->
                <?php
                $license_manager = HubSpot_Ecommerce_License_Manager::instance();
                $is_pro = $license_manager->can_use_auto_sync();
                $auto_sync_from_hubspot = get_option('hubspot_ecommerce_auto_sync_from_hubspot', false);
                $auto_push_to_hubspot = get_option('hubspot_ecommerce_auto_push_products', false);
                ?>

                <h2>
                    <?php _e('Product Sync Settings', 'hubspot-ecommerce'); ?>
                    <?php if (!$is_pro) : ?>
                        <span class="pro-badge" style="background: #2271b1; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: normal; margin-left: 10px;">PRO</span>
                    <?php endif; ?>
                </h2>

                <?php if (!$is_pro) : ?>
                    <div class="notice notice-info inline" style="margin: 15px 0;">
                        <p>
                            <strong><?php _e('Automatic Product Sync is a Pro Feature', 'hubspot-ecommerce'); ?></strong><br>
                            <?php _e('Free tier: Manual push/pull buttons in product editor.', 'hubspot-ecommerce'); ?><br>
                            <?php _e('Pro tier: Automatic scheduled sync from HubSpot + auto-push on save.', 'hubspot-ecommerce'); ?>
                            <a href="https://baursoftware.com/products/hubspot-ecommerce" target="_blank" style="margin-left: 10px;">
                                <?php _e('Upgrade to Pro', 'hubspot-ecommerce'); ?> &rarr;
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="auto_sync_from_hubspot">
                                <?php _e('Auto-Sync FROM HubSpot', 'hubspot-ecommerce'); ?>
                                <?php if (!$is_pro) : ?>
                                    <span class="pro-badge" style="background: #2271b1; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">PRO</span>
                                <?php endif; ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_sync_from_hubspot" name="hubspot_ecommerce_auto_sync_from_hubspot" value="1"
                                    <?php checked($auto_sync_from_hubspot, '1'); ?>
                                    <?php disabled(!$is_pro); ?>>
                                <?php _e('Automatically pull products from HubSpot on a schedule', 'hubspot-ecommerce'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, products created in HubSpot will automatically sync to WordPress.', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr id="sync-interval-row" style="<?php echo (!$is_pro || !$auto_sync_from_hubspot) ? 'display:none;' : ''; ?>">
                        <th scope="row">
                            <label for="sync_interval"><?php _e('Sync Interval', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <select id="sync_interval" name="hubspot_ecommerce_sync_interval" <?php disabled(!$is_pro); ?>>
                                <option value="hourly" <?php selected($sync_interval, 'hourly'); ?>>
                                    <?php _e('Hourly', 'hubspot-ecommerce'); ?>
                                </option>
                                <option value="twicedaily" <?php selected($sync_interval, 'twicedaily'); ?>>
                                    <?php _e('Twice Daily', 'hubspot-ecommerce'); ?>
                                </option>
                                <option value="daily" <?php selected($sync_interval, 'daily'); ?>>
                                    <?php _e('Daily', 'hubspot-ecommerce'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('How often to sync products from HubSpot', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="auto_push_to_hubspot">
                                <?php _e('Auto-Push TO HubSpot', 'hubspot-ecommerce'); ?>
                                <?php if (!$is_pro) : ?>
                                    <span class="pro-badge" style="background: #2271b1; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px;">PRO</span>
                                <?php endif; ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_push_to_hubspot" name="hubspot_ecommerce_auto_push_products" value="1"
                                    <?php checked($auto_push_to_hubspot, '1'); ?>
                                    <?php disabled(!$is_pro); ?>>
                                <?php _e('Automatically push products to HubSpot when saved in WordPress', 'hubspot-ecommerce'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, saving a product in WordPress will automatically sync it to HubSpot.', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <script>
                jQuery(document).ready(function($) {
                    $('#auto_sync_from_hubspot').on('change', function() {
                        if ($(this).is(':checked')) {
                            $('#sync-interval-row').show();
                        } else {
                            $('#sync-interval-row').hide();
                        }
                    });
                });
                </script>


                <h2><?php _e('Shop Pages', 'hubspot-ecommerce'); ?></h2>
                <table class="form-table">
                    <?php
                    $pages = [
                        'shop_page' => __('Shop Page', 'hubspot-ecommerce'),
                        'cart_page' => __('Cart Page', 'hubspot-ecommerce'),
                        'checkout_page' => __('Checkout Page', 'hubspot-ecommerce'),
                        'account_page' => __('Account Page', 'hubspot-ecommerce'),
                    ];

                    foreach ($pages as $key => $label):
                        $option_name = 'hubspot_ecommerce_' . $key;
                        $value = get_option($option_name, '');
                    ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                            </th>
                            <td>
                                <?php
                                wp_dropdown_pages([
                                    'name' => $option_name,
                                    'id' => $key,
                                    'selected' => $value,
                                    'show_option_none' => __('Select a page', 'hubspot-ecommerce'),
                                ]);
                                ?>
                                <p class="description">
                                    <?php printf(__('Select the page to use for %s', 'hubspot-ecommerce'), strtolower($label)); ?>
                                </p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <h2><?php _e('Floating Cart Widget', 'hubspot-ecommerce'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cart_icon"><?php _e('Cart Icon', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <?php
                            $cart_icon = get_option('hubspot_ecommerce_cart_icon', 'shopping-cart');
                            $available_icons = [
                                'shopping-cart' => __('Shopping Cart (default)', 'hubspot-ecommerce'),
                                'cart-plus' => __('Cart Plus', 'hubspot-ecommerce'),
                                'cart-arrow-down' => __('Cart Arrow Down', 'hubspot-ecommerce'),
                                'shopping-bag' => __('Shopping Bag', 'hubspot-ecommerce'),
                                'shopping-basket' => __('Shopping Basket', 'hubspot-ecommerce'),
                            ];
                            ?>
                            <select id="cart_icon" name="hubspot_ecommerce_cart_icon">
                                <?php foreach ($available_icons as $icon_key => $icon_label): ?>
                                    <option value="<?php echo esc_attr($icon_key); ?>" <?php selected($cart_icon, $icon_key); ?>>
                                        <?php echo esc_html($icon_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Select the icon to display in the floating cart widget. Uses Font Awesome icons from GeneratePress theme.', 'hubspot-ecommerce'); ?>
                            </p>
                            <div style="margin-top: 15px;">
                                <strong><?php _e('Preview:', 'hubspot-ecommerce'); ?></strong><br>
                                <div style="margin-top: 10px; display: flex; gap: 15px; flex-wrap: wrap;">
                                    <?php foreach ($available_icons as $icon_key => $icon_label): ?>
                                        <div style="text-align: center;">
                                            <div style="display: inline-flex; align-items: center; justify-content: center; width: 50px; height: 50px; background: #3498db; color: white; border-radius: 50%; margin-bottom: 5px;">
                                                <i class="fa fa-<?php echo esc_attr($icon_key); ?>" style="font-size: 20px;"></i>
                                            </div>
                                            <div style="font-size: 11px; color: #666;">
                                                <?php echo esc_html(str_replace(' (default)', '', $icon_label)); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-connection').on('click', function() {
                var $button = $(this);
                var $status = $('#connection-status');

                $button.prop('disabled', true);
                $status.html('<span class="spinner is-active"></span>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hubspot_test_connection',
                        nonce: '<?php echo wp_create_nonce('hubspot_ecommerce_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false);
                        if (response.success) {
                            $status.html('<span style="color: green;">✓ <?php _e('Connection successful', 'hubspot-ecommerce'); ?></span>');
                        } else {
                            $status.html('<span style="color: red;">✗ <?php _e('Connection failed', 'hubspot-ecommerce'); ?></span>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false);
                        $status.html('<span style="color: red;">✗ <?php _e('Connection failed', 'hubspot-ecommerce'); ?></span>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if (!isset($_POST['hubspot_ecommerce_settings_nonce']) ||
            !wp_verify_nonce($_POST['hubspot_ecommerce_settings_nonce'], 'hubspot_ecommerce_settings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $license_manager = HubSpot_Ecommerce_License_Manager::instance();

        $settings = [
            'hubspot_ecommerce_sync_interval',
            'hubspot_ecommerce_currency',
            'hubspot_ecommerce_shop_page',
            'hubspot_ecommerce_cart_page',
            'hubspot_ecommerce_checkout_page',
            'hubspot_ecommerce_account_page',
            'hubspot_ecommerce_cart_icon',
        ];

        // Add API key to settings if pro feature is enabled
        if ($license_manager->can_use_private_app()) {
            $settings[] = 'hubspot_ecommerce_api_key';
        }

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                // Use appropriate sanitization for each setting
                if ($setting === 'hubspot_ecommerce_api_key') {
                    // API keys should only contain alphanumeric, hyphens, and underscores
                    $value = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST[$setting]);
                    update_option($setting, $value);
                } else {
                    update_option($setting, sanitize_text_field($_POST[$setting]));
                }
            }
        }

        echo '<div class="notice notice-success"><p>' . __('Settings saved', 'hubspot-ecommerce') . '</p></div>';
    }
}

// AJAX handler for connection test
add_action('wp_ajax_hubspot_test_connection', function() {
    check_ajax_referer('hubspot_ecommerce_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }

    $api = HubSpot_Ecommerce_API::instance();
    $result = $api->test_connection();

    if ($result) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
});
