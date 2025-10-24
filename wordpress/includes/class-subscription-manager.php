<?php
/**
 * Subscription Manager - Handles both commerce subscriptions and email subscription types
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Subscription_Manager {

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

        // Check if Pro tier before adding hooks
        $license = HubSpot_Ecommerce_License_Manager::instance();

        if (!$license->can_use_subscriptions()) {
            // Show locked menu item for Free tier
            add_action('admin_menu', [$this, 'add_locked_submenu'], 20);
            return; // Don't add any other hooks
        }

        // Admin hooks (only if Pro tier)
        add_action('admin_menu', [$this, 'add_admin_submenu'], 20);
        add_action('admin_init', [$this, 'sync_subscription_types']);

        // AJAX handlers
        add_action('wp_ajax_hs_sync_subscription_types', [$this, 'ajax_sync_subscription_types']);
        add_action('wp_ajax_hs_update_email_subscriptions', [$this, 'ajax_update_email_subscriptions']);
        add_action('wp_ajax_nopriv_hs_update_email_subscriptions', [$this, 'ajax_update_email_subscriptions']);
    }

    /**
     * Add subscription management submenu
     */
    public function add_admin_submenu() {
        add_submenu_page(
            'hubspot-ecommerce',
            __('Subscriptions', 'hubspot-ecommerce'),
            __('Subscriptions', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-subscriptions',
            [$this, 'render_subscriptions_page']
        );
    }

    /**
     * Add locked submenu (Free tier)
     */
    public function add_locked_submenu() {
        add_submenu_page(
            'hubspot-ecommerce',
            __('Subscriptions 🔒', 'hubspot-ecommerce'),
            __('Subscriptions 🔒', 'hubspot-ecommerce'),
            'manage_options',
            'hubspot-ecommerce-subscriptions-locked',
            [$this, 'render_locked_page']
        );
    }

    /**
     * Render locked page (Free tier)
     */
    public function render_locked_page() {
        $license = HubSpot_Ecommerce_License_Manager::instance();
        ?>
        <div class="wrap">
            <h1><?php _e('Subscription Management', 'hubspot-ecommerce'); ?> 🔒</h1>

            <?php $license->render_upgrade_notice(__('Subscription Management', 'hubspot-ecommerce')); ?>

            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php _e('What You Can Do with Subscriptions', 'hubspot-ecommerce'); ?></h2>
                <ul style="font-size: 15px; line-height: 2;">
                    <li>✅ <strong>Recurring subscriptions</strong> - Sell monthly/yearly products</li>
                    <li>✅ <strong>Email subscription preferences</strong> - Sync marketing opt-ins with HubSpot</li>
                    <li>✅ <strong>Automated billing</strong> - HubSpot handles recurring charges automatically</li>
                    <li>✅ <strong>Subscription analytics</strong> - Track MRR, churn, and customer lifetime value</li>
                    <li>✅ <strong>Customer portal</strong> - Let customers manage their own subscriptions</li>
                </ul>
            </div>

            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php _e('How to Upgrade', 'hubspot-ecommerce'); ?></h2>
                <ol style="font-size: 15px; line-height: 1.8;">
                    <li>Purchase a Pro license at <a href="<?php echo esc_url($license->get_upgrade_url()); ?>" target="_blank">baursoftware.com</a></li>
                    <li>Receive your license key via email</li>
                    <li>Go to <strong>HubSpot Shop → License</strong> and enter your key</li>
                    <li>Follow the guided wizard to set up Private App in HubSpot</li>
                    <li>All Pro features automatically unlock!</li>
                </ol>
            </div>
        </div>
        <?php
    }

    /**
     * Render subscriptions management page
     */
    public function render_subscriptions_page() {
        $subscription_types = get_option('hubspot_ecommerce_subscription_types', []);
        ?>
        <div class="wrap">
            <h1><?php _e('HubSpot Subscription Types', 'hubspot-ecommerce'); ?></h1>

            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Note:', 'hubspot-ecommerce'); ?></strong>
                    <?php _e('Subscription types must be created in HubSpot first. Use the button below to sync them from your HubSpot account.', 'hubspot-ecommerce'); ?>
                </p>
                <p>
                    <a href="https://app.hubspot.com/reports-dashboard/marketing/subscriptions" target="_blank">
                        <?php _e('Manage Subscription Types in HubSpot &rarr;', 'hubspot-ecommerce'); ?>
                    </a>
                </p>
            </div>

            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php _e('Email Subscription Types', 'hubspot-ecommerce'); ?></h2>
                <p><?php _e('These are the email subscription types from your HubSpot account. Customers can manage their preferences from their account dashboard.', 'hubspot-ecommerce'); ?></p>

                <button id="sync-subscription-types" class="button button-primary">
                    <?php _e('Sync Subscription Types from HubSpot', 'hubspot-ecommerce'); ?>
                </button>

                <div id="sync-status" style="margin-top: 15px;"></div>

                <?php if (!empty($subscription_types)) : ?>
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'hubspot-ecommerce'); ?></th>
                                <th><?php _e('Description', 'hubspot-ecommerce'); ?></th>
                                <th><?php _e('Purpose', 'hubspot-ecommerce'); ?></th>
                                <th><?php _e('Status', 'hubspot-ecommerce'); ?></th>
                                <th><?php _e('Display on Checkout', 'hubspot-ecommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscription_types as $type) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($type['name']); ?></strong></td>
                                    <td><?php echo esc_html($type['description'] ?? ''); ?></td>
                                    <td><?php echo esc_html($type['purpose'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($type['isActive']) : ?>
                                            <span style="color: green;">●</span> <?php _e('Active', 'hubspot-ecommerce'); ?>
                                        <?php else : ?>
                                            <span style="color: gray;">●</span> <?php _e('Inactive', 'hubspot-ecommerce'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <label>
                                            <input type="checkbox"
                                                   name="checkout_subscription_<?php echo esc_attr($type['id']); ?>"
                                                   value="1"
                                                   <?php checked($this->is_displayed_on_checkout($type['id'])); ?>>
                                            <?php _e('Show', 'hubspot-ecommerce'); ?>
                                        </label>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p style="margin-top: 20px;">
                        <em><?php _e('No subscription types synced yet. Click the button above to sync from HubSpot.', 'hubspot-ecommerce'); ?></em>
                    </p>
                <?php endif; ?>
            </div>

            <div class="card" style="max-width: 800px; margin: 20px 0;">
                <h2><?php _e('Commerce Subscriptions', 'hubspot-ecommerce'); ?></h2>
                <p><?php _e('Products with recurring billing are automatically detected and handled during checkout.', 'hubspot-ecommerce'); ?></p>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=hs_product'); ?>">
                        <?php _e('View Products', 'hubspot-ecommerce'); ?>
                    </a>
                </p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#sync-subscription-types').on('click', function() {
                var $button = $(this);
                var $status = $('#sync-status');

                $button.prop('disabled', true).text('<?php _e('Syncing...', 'hubspot-ecommerce'); ?>');
                $status.html('<span class="spinner is-active"></span>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hs_sync_subscription_types',
                        nonce: '<?php echo wp_create_nonce('hubspot_ecommerce_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        $button.prop('disabled', false).text('<?php _e('Sync Subscription Types from HubSpot', 'hubspot-ecommerce'); ?>');

                        if (response.success) {
                            $status.html('<div class="notice notice-success inline"><p>' +
                                '<?php _e('Successfully synced', 'hubspot-ecommerce'); ?> ' +
                                response.data.count + ' <?php _e('subscription types', 'hubspot-ecommerce'); ?>' +
                                '</p></div>');

                            // Reload page to show new types
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            $status.html('<div class="notice notice-error inline"><p>' +
                                (response.data.message || '<?php _e('Sync failed', 'hubspot-ecommerce'); ?>') +
                                '</p></div>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Sync Subscription Types from HubSpot', 'hubspot-ecommerce'); ?>');
                        $status.html('<div class="notice notice-error inline"><p><?php _e('Sync failed', 'hubspot-ecommerce'); ?></p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Sync subscription types from HubSpot
     */
    public function sync_subscription_types() {
        // Only sync if requested via AJAX or admin action
        if (isset($_GET['sync_subscription_types']) && wp_verify_nonce($_GET['_wpnonce'], 'sync_subscription_types')) {
            $this->do_sync_subscription_types();
            wp_redirect(admin_url('admin.php?page=hubspot-ecommerce-subscriptions&synced=1'));
            exit;
        }
    }

    /**
     * AJAX: Sync subscription types
     */
    public function ajax_sync_subscription_types() {
        check_ajax_referer('hubspot_ecommerce_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'hubspot-ecommerce')]);
        }

        $result = $this->do_sync_subscription_types();

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'count' => count($result),
            'types' => $result,
        ]);
    }

    /**
     * Perform the actual sync
     */
    private function do_sync_subscription_types() {
        $response = $this->api->get_subscription_type_definitions();

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['subscriptionDefinitions']) || !is_array($response['subscriptionDefinitions'])) {
            return new WP_Error('no_subscription_types', __('No subscription types found', 'hubspot-ecommerce'));
        }

        $types = $response['subscriptionDefinitions'];

        // Store subscription types
        update_option('hubspot_ecommerce_subscription_types', $types);
        update_option('hubspot_ecommerce_subscription_types_synced', current_time('mysql'));

        do_action('hubspot_ecommerce_subscription_types_synced', $types);

        return $types;
    }

    /**
     * Check if subscription type should be displayed on checkout
     */
    private function is_displayed_on_checkout($subscription_id) {
        $displayed = get_option('hubspot_ecommerce_checkout_subscriptions', []);
        return in_array($subscription_id, $displayed);
    }

    /**
     * Get active subscription types for checkout display
     */
    public function get_checkout_subscription_types() {
        $all_types = get_option('hubspot_ecommerce_subscription_types', []);
        $checkout_types = get_option('hubspot_ecommerce_checkout_subscriptions', []);

        if (empty($checkout_types)) {
            // Default: show all active, non-default types
            return array_filter($all_types, function($type) {
                return $type['isActive'] && !$type['isDefault'];
            });
        }

        // Filter to only show selected types
        return array_filter($all_types, function($type) use ($checkout_types) {
            return in_array($type['id'], $checkout_types) && $type['isActive'];
        });
    }

    /**
     * Subscribe contact to email subscription types
     */
    public function subscribe_contact_to_types($email, $subscription_ids, $legal_basis = 'CONSENT') {
        $results = [];

        foreach ($subscription_ids as $subscription_id) {
            $result = $this->api->subscribe_contact($email, $subscription_id, $legal_basis, 'Subscribed during checkout');

            if (is_wp_error($result)) {
                $results[$subscription_id] = [
                    'success' => false,
                    'error' => $result->get_error_message(),
                ];
            } else {
                $results[$subscription_id] = [
                    'success' => true,
                ];
            }
        }

        return $results;
    }

    /**
     * Get contact's current subscription statuses
     */
    public function get_contact_statuses($email) {
        $response = $this->api->get_contact_subscription_statuses($email);

        if (is_wp_error($response)) {
            return $response;
        }

        return $response;
    }

    /**
     * AJAX: Update email subscriptions
     */
    public function ajax_update_email_subscriptions() {
        check_ajax_referer('hubspot_ecommerce_nonce', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        $subscribe = $_POST['subscribe'] ?? [];
        $unsubscribe = $_POST['unsubscribe'] ?? [];

        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Invalid email address', 'hubspot-ecommerce')]);
        }

        $results = [
            'subscribed' => [],
            'unsubscribed' => [],
            'errors' => [],
        ];

        // Handle subscriptions
        foreach ($subscribe as $subscription_id) {
            $result = $this->api->subscribe_contact($email, $subscription_id, 'CONSENT', 'Updated from account dashboard');

            if (is_wp_error($result)) {
                $results['errors'][] = $result->get_error_message();
            } else {
                $results['subscribed'][] = $subscription_id;
            }
        }

        // Handle unsubscriptions
        foreach ($unsubscribe as $subscription_id) {
            $result = $this->api->unsubscribe_contact($email, $subscription_id);

            if (is_wp_error($result)) {
                $results['errors'][] = $result->get_error_message();
            } else {
                $results['unsubscribed'][] = $subscription_id;
            }
        }

        if (!empty($results['errors'])) {
            wp_send_json_error([
                'message' => __('Some subscriptions could not be updated', 'hubspot-ecommerce'),
                'details' => $results,
            ]);
        }

        wp_send_json_success([
            'message' => __('Subscription preferences updated successfully', 'hubspot-ecommerce'),
            'details' => $results,
        ]);
    }

    /**
     * Check if a product is a recurring/subscription product
     */
    public function is_subscription_product($post_id) {
        $hubspot_data = get_post_meta($post_id, '_hubspot_data', true);

        if (empty($hubspot_data)) {
            return false;
        }

        $properties = $hubspot_data['properties'] ?? [];

        // Check for recurring billing indicators
        return !empty($properties['hs_recurring_billing_period']) ||
               !empty($properties['recurringbillingfrequency']) ||
               ($properties['hs_product_type'] ?? '') === 'subscription';
    }

    /**
     * Get subscription details for a product
     */
    public function get_product_subscription_details($post_id) {
        $hubspot_data = get_post_meta($post_id, '_hubspot_data', true);

        if (empty($hubspot_data)) {
            return null;
        }

        $properties = $hubspot_data['properties'] ?? [];

        return [
            'billing_period' => $properties['hs_recurring_billing_period'] ?? '',
            'billing_frequency' => $properties['recurringbillingfrequency'] ?? '',
            'billing_period_units' => $properties['hs_billing_period_units'] ?? '',
        ];
    }
}
