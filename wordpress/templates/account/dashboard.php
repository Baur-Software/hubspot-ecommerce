<?php
/**
 * Customer Account Dashboard
 */

$current_user = wp_get_current_user();
$customer = HubSpot_Ecommerce_Customer::instance();
$orders = $customer->get_user_orders($current_user->ID);
$product_manager = HubSpot_Ecommerce_Product_Manager::instance();
?>

<div class="hubspot-account-dashboard">
    <h1><?php printf(__('Welcome, %s', 'hubspot-ecommerce'), esc_html($current_user->display_name)); ?></h1>

    <div class="account-sections">
        <div class="account-info">
            <h2><?php _e('Account Information', 'hubspot-ecommerce'); ?></h2>

            <dl class="info-list">
                <dt><?php _e('Name', 'hubspot-ecommerce'); ?></dt>
                <dd><?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></dd>

                <dt><?php _e('Email', 'hubspot-ecommerce'); ?></dt>
                <dd><?php echo esc_html($current_user->user_email); ?></dd>

                <?php if ($customer->is_user_synced($current_user->ID)) : ?>
                    <dt><?php _e('HubSpot Status', 'hubspot-ecommerce'); ?></dt>
                    <dd><span class="status-synced"><?php _e('Synced', 'hubspot-ecommerce'); ?></span></dd>
                <?php endif; ?>
            </dl>

            <a href="<?php echo get_edit_profile_url($current_user->ID); ?>" class="button">
                <?php _e('Edit Profile', 'hubspot-ecommerce'); ?>
            </a>

            <a href="?view=subscriptions" class="button button-secondary" style="margin-top: 0.5rem;">
                <?php _e('Email Preferences', 'hubspot-ecommerce'); ?>
            </a>
        </div>

        <div class="account-privacy">
            <h2><?php _e('Privacy & Data', 'hubspot-ecommerce'); ?></h2>

            <p><?php _e('Manage your personal data and privacy preferences.', 'hubspot-ecommerce'); ?></p>

            <div class="privacy-actions">
                <a href="<?php echo esc_url(rest_url('hubspot-ecommerce/v1/gdpr/export')); ?>"
                   class="button button-secondary"
                   target="_blank">
                    <?php _e('Download My Data', 'hubspot-ecommerce'); ?>
                </a>

                <button type="button"
                        class="button button-link-delete"
                        id="request-data-deletion"
                        style="margin-top: 0.5rem;">
                    <?php _e('Request Data Deletion', 'hubspot-ecommerce'); ?>
                </button>
            </div>

            <p class="description">
                <?php _e('Your data is handled according to our', 'hubspot-ecommerce'); ?>
                <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank">
                    <?php _e('Privacy Policy', 'hubspot-ecommerce'); ?>
                </a>.
                <?php _e('Data retention: Cart (30 days), Orders (7 years).', 'hubspot-ecommerce'); ?>
            </p>
        </div>

        <div class="account-orders">
            <h2><?php _e('Recent Orders', 'hubspot-ecommerce'); ?></h2>

            <?php if (empty($orders)) : ?>
                <p><?php _e('You have no orders yet.', 'hubspot-ecommerce'); ?></p>
                <a href="<?php echo get_post_type_archive_link('hs_product'); ?>" class="button">
                    <?php _e('Start Shopping', 'hubspot-ecommerce'); ?>
                </a>
            <?php else : ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><?php _e('Order', 'hubspot-ecommerce'); ?></th>
                            <th><?php _e('Date', 'hubspot-ecommerce'); ?></th>
                            <th><?php _e('Total', 'hubspot-ecommerce'); ?></th>
                            <th><?php _e('Items', 'hubspot-ecommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_permalink($order['id']); ?>">
                                        #<?php echo esc_html($order['deal_id']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order['date']))); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($product_manager->format_price($order['total'])); ?>
                                </td>
                                <td>
                                    <?php echo esc_html(count($order['items'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="account-actions">
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="button button-secondary">
            <?php _e('Logout', 'hubspot-ecommerce'); ?>
        </a>
    </div>
</div>
