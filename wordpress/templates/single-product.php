<?php
/**
 * Single Product Template
 * Compatible with Twenty Twenty-Five theme
 */

get_header();

$product_manager = HubSpot_Ecommerce_Product_Manager::instance();
$cart = HubSpot_Ecommerce_Cart::instance();

while (have_posts()) : the_post();
    $product_id = get_the_ID();
    $price = $product_manager->get_product_price($product_id);
    $sku = $product_manager->get_product_sku($product_id);
    $hubspot_id = $product_manager->get_hubspot_product_id($product_id);
?>

<main id="main" class="site-main">
    <article id="product-<?php the_ID(); ?>" <?php post_class('hubspot-product-single'); ?>>

        <div class="product-layout">
            <div class="product-images">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="product-image-main">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php else : ?>
                    <div class="product-image-placeholder">
                        <span><?php _e('No image available', 'hubspot-ecommerce'); ?></span>
                    </div>
                <?php endif; ?>

                <?php
                $image_urls = get_post_meta($product_id, '_product_images', true);
                if (!empty($image_urls) && is_array($image_urls) && count($image_urls) > 1) :
                ?>
                    <div class="product-image-gallery">
                        <?php foreach ($image_urls as $image_url) : ?>
                            <div class="gallery-thumbnail">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-summary">
                <header class="product-header">
                    <h1 class="product-title"><?php the_title(); ?></h1>

                    <?php if ($sku) : ?>
                        <div class="product-meta">
                            <span class="sku"><?php _e('SKU:', 'hubspot-ecommerce'); ?> <strong><?php echo esc_html($sku); ?></strong></span>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="product-price">
                    <span class="price"><?php echo esc_html($product_manager->format_price($price)); ?></span>
                </div>

                <div class="product-description">
                    <?php the_content(); ?>
                </div>

                <form class="add-to-cart-form" data-product-id="<?php echo esc_attr($product_id); ?>">
                    <div class="quantity-selector">
                        <label for="quantity"><?php _e('Quantity:', 'hubspot-ecommerce'); ?></label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="100">
                    </div>

                    <button type="submit" class="button button-primary add-to-cart">
                        <?php _e('Add to Cart', 'hubspot-ecommerce'); ?>
                    </button>

                    <div class="add-to-cart-message"></div>
                </form>

                <?php
                $categories = get_the_terms($product_id, 'hs_product_cat');
                if ($categories && !is_wp_error($categories)) :
                ?>
                    <div class="product-categories">
                        <span class="label"><?php _e('Categories:', 'hubspot-ecommerce'); ?></span>
                        <?php
                        $category_links = array_map(function($category) {
                            return '<a href="' . esc_url(get_term_link($category)) . '">' . esc_html($category->name) . '</a>';
                        }, $categories);
                        echo implode(', ', $category_links);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </article>
</main>

<?php
endwhile;

get_footer();
