<?php
/**
 * Products Grid Template (for shortcode)
 */

$product_manager = HubSpot_Ecommerce_Product_Manager::instance();

if ($query->have_posts()) :
?>
    <div class="hubspot-product-grid">
        <?php while ($query->have_posts()) : $query->the_post();
            $product_id = get_the_ID();
            $price = $product_manager->get_product_price($product_id);
        ?>
            <article id="product-<?php the_ID(); ?>" <?php post_class('hubspot-product-item'); ?>>
                <a href="<?php the_permalink(); ?>" class="product-link">
                    <div class="product-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else : ?>
                            <div class="product-image-placeholder">
                                <span><?php _e('No image', 'hubspot-ecommerce'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-info">
                        <h3 class="product-title"><?php the_title(); ?></h3>

                        <div class="product-price">
                            <span class="price"><?php echo esc_html($product_manager->format_price($price)); ?></span>
                        </div>
                    </div>
                </a>

                <div class="product-actions">
                    <button class="button add-to-cart-quick" data-product-id="<?php echo esc_attr($product_id); ?>">
                        <?php _e('Add to Cart', 'hubspot-ecommerce'); ?>
                    </button>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
<?php
    wp_reset_postdata();
else :
?>
    <p><?php _e('No products found.', 'hubspot-ecommerce'); ?></p>
<?php endif; ?>
