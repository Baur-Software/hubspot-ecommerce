<?php
/**
 * Customer Subscription Preferences
 */

$current_user = wp_get_current_user();
$subscription_manager = HubSpot_Ecommerce_Subscription_Manager::instance();
$api = HubSpot_Ecommerce_API::instance();

// Get all subscription types
$subscription_types = get_option('hubspot_ecommerce_subscription_types', []);

// Get user's current subscription statuses
$current_statuses = [];
$statuses_response = $subscription_manager->get_contact_statuses($current_user->user_email);

if (!is_wp_error($statuses_response) && isset($statuses_response['subscriptionStatuses'])) {
    foreach ($statuses_response['subscriptionStatuses'] as $status) {
        $current_statuses[$status['id']] = $status['status'] ?? 'NOT_SUBSCRIBED';
    }
}
?>

<div class="hubspot-subscription-preferences">
    <h2><?php _e('Email Subscription Preferences', 'hubspot-ecommerce'); ?></h2>

    <p><?php _e('Manage which types of emails you\'d like to receive from us.', 'hubspot-ecommerce'); ?></p>

    <?php if (empty($subscription_types)) : ?>
        <div class="notice notice-info">
            <p><?php _e('No subscription types available at this time.', 'hubspot-ecommerce'); ?></p>
        </div>
    <?php else : ?>
        <form id="subscription-preferences-form" class="subscription-form">
            <input type="hidden" name="email" value="<?php echo esc_attr($current_user->user_email); ?>">

            <table class="subscription-types-table">
                <thead>
                    <tr>
                        <th class="subscription-name"><?php _e('Subscription Type', 'hubspot-ecommerce'); ?></th>
                        <th class="subscription-status"><?php _e('Status', 'hubspot-ecommerce'); ?></th>
                        <th class="subscription-action"><?php _e('Action', 'hubspot-ecommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscription_types as $type) :
                        if (!$type['isActive']) continue; // Skip inactive types

                        $subscription_id = $type['id'];
                        $current_status = $current_statuses[$subscription_id] ?? 'NOT_SUBSCRIBED';
                        $is_subscribed = $current_status === 'SUBSCRIBED';
                    ?>
                        <tr class="subscription-type-row">
                            <td class="subscription-info">
                                <strong><?php echo esc_html($type['name']); ?></strong>
                                <?php if (!empty($type['description'])) : ?>
                                    <p class="subscription-description">
                                        <?php echo esc_html($type['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td class="subscription-status">
                                <span class="status-indicator status-<?php echo $is_subscribed ? 'subscribed' : 'unsubscribed'; ?>">
                                    <?php echo $is_subscribed ? __('Subscribed', 'hubspot-ecommerce') : __('Not Subscribed', 'hubspot-ecommerce'); ?>
                                </span>
                            </td>
                            <td class="subscription-action">
                                <label class="subscription-toggle">
                                    <input type="checkbox"
                                           name="subscriptions[]"
                                           value="<?php echo esc_attr($subscription_id); ?>"
                                           data-subscription-id="<?php echo esc_attr($subscription_id); ?>"
                                           <?php checked($is_subscribed); ?>>
                                    <span class="toggle-label">
                                        <?php echo $is_subscribed ? __('Unsubscribe', 'hubspot-ecommerce') : __('Subscribe', 'hubspot-ecommerce'); ?>
                                    </span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="subscription-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Update Preferences', 'hubspot-ecommerce'); ?>
                </button>
            </div>

            <div class="subscription-message"></div>
        </form>

        <div class="subscription-footer">
            <p class="help-text">
                <?php _e('You can update your preferences at any time. Changes take effect immediately.', 'hubspot-ecommerce'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.hubspot-subscription-preferences {
    padding: var(--wp--preset--spacing--40, 1.5rem);
    background: var(--wp--preset--color--base, #fff);
    border: 1px solid var(--wp--preset--color--contrast-2, #e5e5e5);
    border-radius: var(--wp--custom--border--radius, 8px);
    margin: var(--wp--preset--spacing--40, 1.5rem) 0;
}

.subscription-types-table {
    width: 100%;
    border-collapse: collapse;
    margin: var(--wp--preset--spacing--40, 1.5rem) 0;
}

.subscription-types-table thead th {
    text-align: left;
    padding: var(--wp--preset--spacing--30, 1rem);
    background: var(--wp--preset--color--contrast-1, #f8f9fa);
    font-weight: 600;
    border-bottom: 2px solid var(--wp--preset--color--contrast-2, #e5e5e5);
}

.subscription-type-row {
    border-bottom: 1px solid var(--wp--preset--color--contrast-2, #e5e5e5);
}

.subscription-type-row td {
    padding: var(--wp--preset--spacing--40, 1.5rem) var(--wp--preset--spacing--30, 1rem);
}

.subscription-description {
    margin: var(--wp--preset--spacing--10, 0.25rem) 0 0;
    color: var(--wp--preset--color--contrast-3, #666);
    font-size: 0.9rem;
}

.status-indicator {
    display: inline-block;
    padding: var(--wp--preset--spacing--10, 0.25rem) var(--wp--preset--spacing--20, 0.5rem);
    border-radius: var(--wp--custom--border--radius, 4px);
    font-size: 0.875rem;
    font-weight: 600;
}

.status-subscribed {
    background: var(--wp--preset--color--pale-cyan-blue, #d4edda);
    color: var(--wp--preset--color--vivid-green-cyan, #155724);
}

.status-unsubscribed {
    background: var(--wp--preset--color--contrast-1, #f8f9fa);
    color: var(--wp--preset--color--contrast-3, #666);
}

.subscription-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.subscription-toggle input[type="checkbox"] {
    margin-right: var(--wp--preset--spacing--20, 0.5rem);
}

.subscription-actions {
    margin-top: var(--wp--preset--spacing--40, 1.5rem);
    padding-top: var(--wp--preset--spacing--40, 1.5rem);
    border-top: 1px solid var(--wp--preset--color--contrast-2, #e5e5e5);
}

.subscription-message {
    margin-top: var(--wp--preset--spacing--30, 1rem);
}

.subscription-footer {
    margin-top: var(--wp--preset--spacing--40, 1.5rem);
    padding-top: var(--wp--preset--spacing--40, 1.5rem);
    border-top: 1px solid var(--wp--preset--color--contrast-2, #e5e5e5);
}

.help-text {
    color: var(--wp--preset--color--contrast-3, #666);
    font-size: 0.9rem;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#subscription-preferences-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $message = $('.subscription-message');

        $button.prop('disabled', true).text('<?php _e('Updating...', 'hubspot-ecommerce'); ?>');
        $message.html('');

        // Determine which subscriptions to subscribe/unsubscribe
        var subscribe = [];
        var unsubscribe = [];

        $form.find('input[name="subscriptions[]"]').each(function() {
            var $checkbox = $(this);
            var subscriptionId = $checkbox.val();
            var wasChecked = $checkbox.data('original-checked') !== undefined ?
                            $checkbox.data('original-checked') :
                            $checkbox.prop('checked');
            var isChecked = $checkbox.prop('checked');

            // Store original state on first run
            if ($checkbox.data('original-checked') === undefined) {
                $checkbox.data('original-checked', wasChecked);
            }

            if (isChecked && !wasChecked) {
                subscribe.push(subscriptionId);
            } else if (!isChecked && wasChecked) {
                unsubscribe.push(subscriptionId);
            }
        });

        $.ajax({
            url: hubspotEcommerce.ajax_url,
            type: 'POST',
            data: {
                action: 'hs_update_email_subscriptions',
                nonce: hubspotEcommerce.nonce,
                email: $form.find('input[name="email"]').val(),
                subscribe: subscribe,
                unsubscribe: unsubscribe
            },
            success: function(response) {
                $button.prop('disabled', false).text('<?php _e('Update Preferences', 'hubspot-ecommerce'); ?>');

                if (response.success) {
                    $message.html('<div class="notice success"><p>' + response.data.message + '</p></div>');

                    // Update original-checked states
                    $form.find('input[name="subscriptions[]"]').each(function() {
                        $(this).data('original-checked', $(this).prop('checked'));
                    });

                    // Reload to update status indicators
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $message.html('<div class="notice error"><p>' +
                                (response.data.message || '<?php _e('Update failed', 'hubspot-ecommerce'); ?>') +
                                '</p></div>');
                }
            },
            error: function() {
                $button.prop('disabled', false).text('<?php _e('Update Preferences', 'hubspot-ecommerce'); ?>');
                $message.html('<div class="notice error"><p><?php _e('Update failed', 'hubspot-ecommerce'); ?></p></div>');
            }
        });
    });
});
</script>
