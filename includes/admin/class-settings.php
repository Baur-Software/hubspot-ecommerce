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
    }

    /**
     * Render settings page
     */
    public function render() {
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

        $license_manager = HubSpot_Ecommerce_License_Manager::instance();

        $settings = [
            'hubspot_ecommerce_sync_interval',
            'hubspot_ecommerce_currency',
            'hubspot_ecommerce_shop_page',
            'hubspot_ecommerce_cart_page',
            'hubspot_ecommerce_checkout_page',
            'hubspot_ecommerce_account_page',
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
