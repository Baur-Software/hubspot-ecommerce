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
        // API Settings
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_api_key');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_sync_interval');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_currency');

        // Page Settings
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_shop_page');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_cart_page');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_checkout_page');
        register_setting('hubspot_ecommerce_settings', 'hubspot_ecommerce_account_page');
    }

    /**
     * Render settings page
     */
    public function render() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        $api = HubSpot_Ecommerce_API::instance();
        $auth_status = $api->get_auth_status();

        $api_key = get_option('hubspot_ecommerce_api_key', '');
        $sync_interval = get_option('hubspot_ecommerce_sync_interval', 'hourly');
        $currency = get_option('hubspot_ecommerce_currency', 'USD');

        ?>
        <div class="wrap">
            <h1><?php _e('HubSpot Ecommerce Settings', 'hubspot-ecommerce'); ?></h1>

            <!-- Authentication Status Card -->
            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php _e('Authentication Status', 'hubspot-ecommerce'); ?></h2>

                <?php if ($auth_status['mode'] === 'leadin') : ?>
                    <!-- Leadin OAuth Mode -->
                    <div class="notice notice-success inline" style="margin: 0;">
                        <p>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                            <strong><?php _e('Connected via HubSpot Plugin (OAuth 2.0)', 'hubspot-ecommerce'); ?></strong>
                        </p>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Authentication Mode', 'hubspot-ecommerce'); ?></th>
                            <td>
                                <strong><?php _e('OAuth 2.0', 'hubspot-ecommerce'); ?></strong>
                                <span class="description" style="display: block;">
                                    <?php _e('Secure OAuth authentication managed by the official HubSpot plugin', 'hubspot-ecommerce'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Portal ID', 'hubspot-ecommerce'); ?></th>
                            <td><code><?php echo esc_html($auth_status['portal_id']); ?></code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Token Management', 'hubspot-ecommerce'); ?></th>
                            <td>
                                <span style="color: green;">✓</span> <?php _e('Automatic token refresh enabled', 'hubspot-ecommerce'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Security', 'hubspot-ecommerce'); ?></th>
                            <td>
                                <span style="color: green;">✓</span> <?php _e('OAuth 2.0 (Most Secure)', 'hubspot-ecommerce'); ?>
                            </td>
                        </tr>
                    </table>
                    <p style="margin-top: 15px;">
                        <a href="<?php echo admin_url('admin.php?page=leadin'); ?>" class="button">
                            <?php _e('Manage HubSpot Connection', 'hubspot-ecommerce'); ?>
                        </a>
                    </p>

                <?php elseif ($auth_status['mode'] === 'private_app') : ?>
                    <!-- Private App Token Mode -->
                    <div class="notice notice-info inline" style="margin: 0;">
                        <p>
                            <span class="dashicons dashicons-admin-network" style="color: #0073aa;"></span>
                            <strong><?php _e('Connected via Private App Token', 'hubspot-ecommerce'); ?></strong>
                        </p>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Authentication Mode', 'hubspot-ecommerce'); ?></th>
                            <td>
                                <strong><?php _e('Private App Token', 'hubspot-ecommerce'); ?></strong>
                                <span class="description" style="display: block;">
                                    <?php _e('Using bearer token authentication', 'hubspot-ecommerce'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Token Status', 'hubspot-ecommerce'); ?></th>
                            <td>
                                <button type="button" id="test-connection" class="button">
                                    <?php _e('Test Connection', 'hubspot-ecommerce'); ?>
                                </button>
                                <span id="connection-status"></span>
                            </td>
                        </tr>
                    </table>

                    <div class="notice notice-warning inline" style="margin: 15px 0 0;">
                        <p>
                            <strong><?php _e('Recommended: Upgrade to OAuth', 'hubspot-ecommerce'); ?></strong><br>
                            <?php _e('Install the official HubSpot plugin for easier setup and automatic token management.', 'hubspot-ecommerce'); ?>
                            <a href="<?php echo admin_url('plugin-install.php?s=hubspot&tab=search&type=term'); ?>" class="button button-small">
                                <?php _e('Install HubSpot Plugin', 'hubspot-ecommerce'); ?>
                            </a>
                        </p>
                    </div>

                <?php else : ?>
                    <!-- No Authentication -->
                    <div class="notice notice-error inline" style="margin: 0;">
                        <p>
                            <span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
                            <strong><?php _e('No Authentication Configured', 'hubspot-ecommerce'); ?></strong>
                        </p>
                    </div>
                    <p style="margin: 15px 0;">
                        <?php _e('Choose one of the following authentication methods:', 'hubspot-ecommerce'); ?>
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                        <div class="notice notice-info inline" style="margin: 0; padding: 15px;">
                            <h3 style="margin: 0 0 10px;"><?php _e('Option 1: OAuth (Recommended)', 'hubspot-ecommerce'); ?></h3>
                            <p><?php _e('Easy one-click setup with automatic token management.', 'hubspot-ecommerce'); ?></p>
                            <a href="<?php echo admin_url('plugin-install.php?s=hubspot&tab=search&type=term'); ?>" class="button button-primary">
                                <?php _e('Install HubSpot Plugin', 'hubspot-ecommerce'); ?>
                            </a>
                        </div>

                        <div class="notice notice-info inline" style="margin: 0; padding: 15px;">
                            <h3 style="margin: 0 0 10px;"><?php _e('Option 2: Private App Token', 'hubspot-ecommerce'); ?></h3>
                            <p><?php _e('Manual setup using a Private App access token.', 'hubspot-ecommerce'); ?></p>
                            <a href="#manual-config" class="button" onclick="document.getElementById('manual-api-config').style.display='block'; return false;">
                                <?php _e('Configure Manually', 'hubspot-ecommerce'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('hubspot_ecommerce_settings', 'hubspot_ecommerce_settings_nonce'); ?>

                <!-- Manual API Configuration (collapsible if not needed) -->
                <div id="manual-api-config" style="<?php echo ($auth_status['mode'] === 'leadin') ? 'display:none;' : ''; ?>">
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
                            </td>
                        </tr>
                    </table>
                </div>

                    <tr>
                        <th scope="row">
                            <label for="sync_interval"><?php _e('Product Sync Interval', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <select id="sync_interval" name="hubspot_ecommerce_sync_interval">
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
                                <?php _e('How often to automatically sync products from HubSpot', 'hubspot-ecommerce'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="currency"><?php _e('Currency', 'hubspot-ecommerce'); ?></label>
                        </th>
                        <td>
                            <select id="currency" name="hubspot_ecommerce_currency">
                                <option value="USD" <?php selected($currency, 'USD'); ?>>USD - US Dollar</option>
                                <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR - Euro</option>
                                <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP - British Pound</option>
                                <option value="JPY" <?php selected($currency, 'JPY'); ?>>JPY - Japanese Yen</option>
                            </select>
                        </td>
                    </tr>
                </table>

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

        $settings = [
            'hubspot_ecommerce_api_key',
            'hubspot_ecommerce_sync_interval',
            'hubspot_ecommerce_currency',
            'hubspot_ecommerce_shop_page',
            'hubspot_ecommerce_cart_page',
            'hubspot_ecommerce_checkout_page',
            'hubspot_ecommerce_account_page',
        ];

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
