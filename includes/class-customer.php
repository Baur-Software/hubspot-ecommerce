<?php
/**
 * Customer Management - Syncs WordPress users with HubSpot contacts
 * Only syncs when users register on the site, not importing from HubSpot
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_Customer {

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

        // Hook into WordPress user registration
        add_action('user_register', [$this, 'sync_new_user_to_hubspot'], 10, 1);

        // Hook into profile updates
        add_action('profile_update', [$this, 'sync_user_update_to_hubspot'], 10, 2);

        // Add HubSpot contact ID field to user profile
        add_action('show_user_profile', [$this, 'add_hubspot_field_to_profile']);
        add_action('edit_user_profile', [$this, 'add_hubspot_field_to_profile']);
    }

    /**
     * Sync new WordPress user to HubSpot as contact
     */
    public function sync_new_user_to_hubspot($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        // Check if already synced
        $existing_contact_id = get_user_meta($user_id, '_hubspot_contact_id', true);
        if ($existing_contact_id) {
            return; // Already synced
        }

        // Prepare contact data
        $properties = [
            'firstname' => $user->first_name ?: '',
            'lastname' => $user->last_name ?: '',
        ];

        // Create contact in HubSpot
        $result = $this->api->create_contact($user->user_email, $properties);

        if (is_wp_error($result)) {
            // Log error but don't fail user registration
            error_log('HubSpot Ecommerce: Failed to create contact for user ' . $user_id . ': ' . $result->get_error_message());
            return;
        }

        // Store HubSpot contact ID in user meta
        update_user_meta($user_id, '_hubspot_contact_id', $result['id']);

        do_action('hubspot_ecommerce_user_synced', $user_id, $result['id']);
    }

    /**
     * Sync user profile updates to HubSpot
     */
    public function sync_user_update_to_hubspot($user_id, $old_user_data) {
        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        // Get HubSpot contact ID
        $contact_id = get_user_meta($user_id, '_hubspot_contact_id', true);

        if (!$contact_id) {
            // If not synced yet, create contact
            $this->sync_new_user_to_hubspot($user_id);
            return;
        }

        // Prepare updated properties
        $properties = [
            'firstname' => $user->first_name ?: '',
            'lastname' => $user->last_name ?: '',
        ];

        // Get billing fields if available (WooCommerce-style meta fields)
        $billing_fields = [
            'phone' => get_user_meta($user_id, 'billing_phone', true),
            'address' => get_user_meta($user_id, 'billing_address_1', true),
            'city' => get_user_meta($user_id, 'billing_city', true),
            'state' => get_user_meta($user_id, 'billing_state', true),
            'zip' => get_user_meta($user_id, 'billing_postcode', true),
            'country' => get_user_meta($user_id, 'billing_country', true),
        ];

        foreach ($billing_fields as $key => $value) {
            if (!empty($value)) {
                $properties[$key] = $value;
            }
        }

        // Update contact in HubSpot
        $result = $this->api->update_contact($contact_id, $properties);

        if (is_wp_error($result)) {
            error_log('HubSpot Ecommerce: Failed to update contact ' . $contact_id . ': ' . $result->get_error_message());
            return;
        }

        do_action('hubspot_ecommerce_user_updated', $user_id, $contact_id);
    }

    /**
     * Get HubSpot contact ID for a user
     */
    public function get_contact_id($user_id) {
        return get_user_meta($user_id, '_hubspot_contact_id', true);
    }

    /**
     * Get user orders from WordPress
     */
    public function get_user_orders($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return [];
        }

        // Query orders by customer email
        $args = [
            'post_type' => 'hs_order',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_customer_data',
                    'value' => $user->user_email,
                    'compare' => 'LIKE',
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new WP_Query($args);
        $orders = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $order_id = get_the_ID();

                $orders[] = [
                    'id' => $order_id,
                    'deal_id' => get_post_meta($order_id, '_hubspot_deal_id', true),
                    'total' => get_post_meta($order_id, '_order_total', true),
                    'date' => get_post_meta($order_id, '_order_date', true),
                    'items' => get_post_meta($order_id, '_order_items', true),
                ];
            }
            wp_reset_postdata();
        }

        return $orders;
    }

    /**
     * Add HubSpot contact ID field to user profile
     */
    public function add_hubspot_field_to_profile($user) {
        // Only admins can see HubSpot contact IDs (security: information disclosure)
        if (!current_user_can('manage_options')) {
            return;
        }

        $contact_id = get_user_meta($user->ID, '_hubspot_contact_id', true);
        ?>
        <h3><?php _e('HubSpot Integration', 'hubspot-ecommerce'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label><?php _e('HubSpot Contact ID', 'hubspot-ecommerce'); ?></label></th>
                <td>
                    <?php if ($contact_id): ?>
                        <code><?php echo esc_html($contact_id); ?></code>
                        <p class="description">
                            <?php printf(
                                __('View in HubSpot: <a href="%s" target="_blank">Open Contact</a>', 'hubspot-ecommerce'),
                                esc_url('https://app.hubspot.com/contacts/your-portal-id/contact/' . $contact_id)
                            ); ?>
                        </p>
                    <?php else: ?>
                        <span><?php _e('Not synced to HubSpot yet', 'hubspot-ecommerce'); ?></span>
                        <p class="description">
                            <?php _e('Contact will be created in HubSpot on next profile update or order.', 'hubspot-ecommerce'); ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Check if user is synced with HubSpot
     */
    public function is_user_synced($user_id) {
        $contact_id = get_user_meta($user_id, '_hubspot_contact_id', true);
        return !empty($contact_id);
    }

    /**
     * Manually sync a user to HubSpot (for admin use)
     */
    public function manual_sync_user($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return new WP_Error('invalid_user', __('Invalid user', 'hubspot-ecommerce'));
        }

        $contact_id = get_user_meta($user_id, '_hubspot_contact_id', true);

        if ($contact_id) {
            // Update existing contact
            $this->sync_user_update_to_hubspot($user_id, null);
        } else {
            // Create new contact
            $this->sync_new_user_to_hubspot($user_id);
        }

        return true;
    }
}
