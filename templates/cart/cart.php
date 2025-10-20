<?php
/**
 * Shopping Cart Template
 */

$cart = HubSpot_Ecommerce_Cart::instance();
$product_manager = HubSpot_Ecommerce_Product_Manager::instance();
$cart_items = $cart->get_cart_items_with_products();
?>

<div class="hubspot-cart">
    <h1><?php _e('Shopping Cart', 'hubspot-ecommerce'); ?></h1>

    <?php if (empty($cart_items)) : ?>
        <div class="cart-empty">
            <p><?php _e('Your cart is empty.', 'hubspot-ecommerce'); ?></p>
            <a href="<?php echo esc_url(get_post_type_archive_link('hs_product')); ?>" class="button">
                <?php _e('Continue Shopping', 'hubspot-ecommerce'); ?>
            </a>
        </div>
    <?php else : ?>
        <form class="cart-form">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th class="product-thumbnail"><?php _e('Product', 'hubspot-ecommerce'); ?></th>
                        <th class="product-name">&nbsp;</th>
                        <th class="product-price"><?php _e('Price', 'hubspot-ecommerce'); ?></th>
                        <th class="product-quantity"><?php _e('Quantity', 'hubspot-ecommerce'); ?></th>
                        <th class="product-subtotal"><?php _e('Subtotal', 'hubspot-ecommerce'); ?></th>
                        <th class="product-remove">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item) : ?>
                        <tr class="cart-item" data-product-id="<?php echo esc_attr($item['product']->ID); ?>">
                            <td class="product-thumbnail">
                                <a href="<?php echo esc_url(get_permalink($item['product']->ID)); ?>">
                                    <?php echo get_the_post_thumbnail($item['product']->ID, 'thumbnail'); ?>
                                </a>
                            </td>
                            <td class="product-name">
                                <a href="<?php echo esc_url(get_permalink($item['product']->ID)); ?>">
                                    <?php echo esc_html($item['product']->post_title); ?>
                                </a>
                            </td>
                            <td class="product-price">
                                <?php echo esc_html($product_manager->format_price($item['cart_item']['price'])); ?>
                            </td>
                            <td class="product-quantity">
                                <input type="number"
                                       class="quantity-input"
                                       value="<?php echo esc_attr($item['cart_item']['quantity']); ?>"
                                       min="0"
                                       max="100"
                                       data-product-id="<?php echo esc_attr($item['product']->ID); ?>">
                            </td>
                            <td class="product-subtotal">
                                <span class="subtotal-amount">
                                    <?php echo esc_html($product_manager->format_price($item['subtotal'])); ?>
                                </span>
                            </td>
                            <td class="product-remove">
                                <button type="button"
                                        class="remove-item"
                                        data-product-id="<?php echo esc_attr($item['product']->ID); ?>"
                                        aria-label="<?php _e('Remove item', 'hubspot-ecommerce'); ?>">
                                    &times;
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-actions">
                <a href="<?php echo esc_url(get_post_type_archive_link('hs_product')); ?>" class="button button-secondary">
                    <?php _e('Continue Shopping', 'hubspot-ecommerce'); ?>
                </a>
            </div>
        </form>

        <div class="cart-totals">
            <h2><?php _e('Cart Totals', 'hubspot-ecommerce'); ?></h2>

            <table class="totals-table">
                <tbody>
                    <tr class="cart-subtotal">
                        <th><?php _e('Subtotal', 'hubspot-ecommerce'); ?></th>
                        <td>
                            <span class="cart-total-amount">
                                <?php echo esc_html($product_manager->format_price($cart->get_cart_subtotal())); ?>
                            </span>
                        </td>
                    </tr>
                    <tr class="order-total">
                        <th><?php _e('Total', 'hubspot-ecommerce'); ?></th>
                        <td>
                            <strong class="cart-total-amount">
                                <?php echo esc_html($product_manager->format_price($cart->get_cart_total())); ?>
                            </strong>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="proceed-to-checkout">
                <?php
                $checkout_page = get_option('hubspot_ecommerce_checkout_page');
                if ($checkout_page) {
                    $checkout_url = get_permalink($checkout_page);
                } else {
                    $checkout_url = home_url('/checkout/');
                }
                ?>
                <a href="<?php echo esc_url($checkout_url); ?>" class="button button-primary">
                    <?php _e('Proceed to Checkout', 'hubspot-ecommerce'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
