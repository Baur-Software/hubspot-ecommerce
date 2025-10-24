<?php
/**
 * Checkout Template
 */

$cart = HubSpot_Ecommerce_Cart::instance();
$product_manager = HubSpot_Ecommerce_Product_Manager::instance();
$cart_items = $cart->get_cart_items_with_products();

if (empty($cart_items)) {
    echo '<p>' . __('Your cart is empty.', 'hubspot-ecommerce') . '</p>';
    return;
}

$current_user = wp_get_current_user();
?>

<div class="hubspot-checkout">
    <h1><?php _e('Checkout', 'hubspot-ecommerce'); ?></h1>

    <form id="checkout-form" class="checkout-form">
        <div class="checkout-layout">
            <div class="checkout-billing">
                <h2><?php _e('Billing Details', 'hubspot-ecommerce'); ?></h2>

                <div class="form-row">
                    <div class="form-field">
                        <label for="first_name"><?php _e('First Name', 'hubspot-ecommerce'); ?> *</label>
                        <input type="text"
                               id="first_name"
                               name="first_name"
                               value="<?php echo esc_attr($current_user->first_name); ?>"
                               required>
                    </div>

                    <div class="form-field">
                        <label for="last_name"><?php _e('Last Name', 'hubspot-ecommerce'); ?> *</label>
                        <input type="text"
                               id="last_name"
                               name="last_name"
                               value="<?php echo esc_attr($current_user->last_name); ?>"
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="email"><?php _e('Email Address', 'hubspot-ecommerce'); ?> *</label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="<?php echo esc_attr($current_user->user_email); ?>"
                               required>
                    </div>

                    <div class="form-field">
                        <label for="phone"><?php _e('Phone', 'hubspot-ecommerce'); ?></label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               value="<?php echo esc_attr(get_user_meta($current_user->ID, 'billing_phone', true)); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="address"><?php _e('Address', 'hubspot-ecommerce'); ?></label>
                        <input type="text"
                               id="address"
                               name="address"
                               value="<?php echo esc_attr(get_user_meta($current_user->ID, 'billing_address_1', true)); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="city"><?php _e('City', 'hubspot-ecommerce'); ?></label>
                        <input type="text"
                               id="city"
                               name="city"
                               value="<?php echo esc_attr(get_user_meta($current_user->ID, 'billing_city', true)); ?>">
                    </div>

                    <div class="form-field">
                        <label for="state"><?php _e('State / Province', 'hubspot-ecommerce'); ?></label>
                        <input type="text"
                               id="state"
                               name="state"
                               value="<?php echo esc_attr(get_user_meta($current_user->ID, 'billing_state', true)); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="zip"><?php _e('Postal Code', 'hubspot-ecommerce'); ?></label>
                        <input type="text"
                               id="zip"
                               name="zip"
                               value="<?php echo esc_attr(get_user_meta($current_user->ID, 'billing_postcode', true)); ?>">
                    </div>

                    <div class="form-field">
                        <label for="country"><?php _e('Country', 'hubspot-ecommerce'); ?></label>
                        <input type="text"
                               id="country"
                               name="country"
                               value="<?php echo esc_attr(get_user_meta($current_user->ID, 'billing_country', true)); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="notes"><?php _e('Order Notes (Optional)', 'hubspot-ecommerce'); ?></label>
                        <textarea id="notes" name="notes" rows="4"></textarea>
                    </div>
                </div>
            </div>

            <div class="checkout-summary">
                <h2><?php _e('Your Order', 'hubspot-ecommerce'); ?></h2>

                <div class="order-review">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th><?php _e('Product', 'hubspot-ecommerce'); ?></th>
                                <th><?php _e('Total', 'hubspot-ecommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html($item['product']->post_title); ?>
                                        <strong class="product-quantity">
                                            &times; <?php echo esc_html($item['cart_item']['quantity']); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php echo esc_html($product_manager->format_price($item['subtotal'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="cart-subtotal">
                                <th><?php _e('Subtotal', 'hubspot-ecommerce'); ?></th>
                                <td><?php echo esc_html($product_manager->format_price($cart->get_cart_subtotal())); ?></td>
                            </tr>
                            <tr class="order-total">
                                <th><?php _e('Total', 'hubspot-ecommerce'); ?></th>
                                <td>
                                    <strong><?php echo esc_html($product_manager->format_price($cart->get_cart_total())); ?></strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="payment-method">
                    <h3><?php _e('Payment Method', 'hubspot-ecommerce'); ?></h3>
                    <p class="description">
                        <?php _e('Your order will be created in HubSpot. Payment processing can be configured separately.', 'hubspot-ecommerce'); ?>
                    </p>
                </div>

                <div class="place-order">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Place Order', 'hubspot-ecommerce'); ?>
                    </button>
                </div>

                <div class="checkout-message"></div>
            </div>
        </div>
    </form>
</div>
