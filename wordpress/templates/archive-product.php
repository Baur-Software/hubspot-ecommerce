<?php
/**
 * Product Archive Template
 * Compatible with Twenty Twenty-Five theme
 */

get_header();

$product_manager = HubSpot_Ecommerce_Product_Manager::instance();
?>

<main id="main" class="site-main">
    <header class="page-header">
        <h1 class="page-title">
            <?php
            if (is_tax('hs_product_cat')) {
                single_term_title();
            } else {
                _e('Shop', 'hubspot-ecommerce');
            }
            ?>
        </h1>

        <?php
        if (is_tax('hs_product_cat') && term_description()) {
            echo '<div class="taxonomy-description">' . term_description() . '</div>';
        }
        ?>
    </header>

    <div class="hubspot-product-grid">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                $product_id = get_the_ID();
                $price = $product_manager->get_product_price($product_id);
                $sku = $product_manager->get_product_sku($product_id);
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
                            <h2 class="product-title"><?php the_title(); ?></h2>

                            <?php if (has_excerpt()) : ?>
                                <div class="product-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                            <?php endif; ?>

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
        <?php
            endwhile;

            // Pagination
            the_posts_pagination([
                'mid_size' => 2,
                'prev_text' => __('Previous', 'hubspot-ecommerce'),
                'next_text' => __('Next', 'hubspot-ecommerce'),
            ]);

        else :
        ?>
            <div class="no-products">
                <p><?php _e('No products found.', 'hubspot-ecommerce'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
