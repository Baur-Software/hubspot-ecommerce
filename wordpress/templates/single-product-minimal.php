<?php
/**
 * Single Product Template - Minimal
 * Clean, simple layout with essential information only
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
    <article id="product-<?php the_ID(); ?>" <?php post_class('hubspot-product-minimal'); ?>>

        <div class="product-minimal-container">
            <div class="product-content">
                <header class="product-header">
                    <h1 class="product-title"><?php the_title(); ?></h1>

                    <?php if ($price) : ?>
                        <div class="product-price">
                            <?php echo $product_manager->format_price($price); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="product-image">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>

                <div class="product-description">
                    <?php the_content(); ?>
                </div>

                <?php if ($sku) : ?>
                    <div class="product-meta">
                        <span class="sku"><strong><?php _e('SKU:', 'hubspot-ecommerce'); ?></strong> <?php echo esc_html($sku); ?></span>
                    </div>
                <?php endif; ?>

                <form class="cart" method="post">
                    <input type="hidden" name="hubspot_product_id" value="<?php echo esc_attr($hubspot_id); ?>">
                    <input type="hidden" name="product_name" value="<?php echo esc_attr(get_the_title()); ?>">
                    <input type="hidden" name="product_price" value="<?php echo esc_attr($price); ?>">

                    <div class="quantity-selector">
                        <label for="quantity"><?php _e('Quantity:', 'hubspot-ecommerce'); ?></label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="999" step="1">
                    </div>

                    <button type="submit" name="add_to_cart" class="button button-primary add-to-cart">
                        <?php _e('Add to Cart', 'hubspot-ecommerce'); ?>
                    </button>
                </form>
            </div>
        </div>

    </article>
</main>

<style>
.hubspot-product-minimal {
    max-width: 700px;
    margin: 0 auto;
    padding: 40px 20px;
}

.product-minimal-container {
    background: #fff;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-header {
    text-align: center;
    margin-bottom: 30px;
}

.product-title {
    font-size: 2em;
    margin: 0 0 15px 0;
    color: #333;
}

.product-price {
    font-size: 1.8em;
    color: #2271b1;
    font-weight: 600;
}

.product-image {
    margin-bottom: 30px;
    text-align: center;
}

.product-image img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}

.product-description {
    margin-bottom: 30px;
    line-height: 1.6;
    color: #555;
}

.product-meta {
    margin-bottom: 20px;
    padding: 15px;
    background: #f7f7f7;
    border-radius: 4px;
    text-align: center;
}

.cart {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.quantity-selector label {
    font-weight: 600;
}

.quantity-selector input {
    width: 80px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
}

.add-to-cart {
    padding: 12px 40px !important;
    font-size: 1.1em !important;
}

@media (max-width: 600px) {
    .product-minimal-container {
        padding: 20px;
    }

    .product-title {
        font-size: 1.5em;
    }

    .product-price {
        font-size: 1.4em;
    }
}
</style>

<?php
endwhile;
get_footer();
