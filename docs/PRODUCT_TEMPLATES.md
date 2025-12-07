# Product Templates

Product templates allow you to control how individual product pages are displayed, similar to WordPress page templates.

## Available Templates

### Default Template

Standard product layout with all features.

Features:

- Product image gallery
- Price display with currency formatting
- SKU and product metadata
- Full product description
- Add to cart form with quantity selector
- Product categories
- Related products section

File: `wordpress/templates/single-product.php`

### Minimal Template

Clean, simple layout with essential information only.

Features:

- Single product image
- Title and price
- Basic description
- Simple add to cart form
- SKU display

File: `wordpress/templates/single-product-minimal.php`

### Detailed Template (Coming Soon)

Extended layout with tabbed sections.

Planned Features:

- Product description tab
- Specifications tab
- Reviews tab
- Related products

File: `wordpress/templates/single-product-detailed.php`

### Landing Page Template (Coming Soon)

Full-width marketing-focused layout.

Planned Features:

- Hero section
- Feature highlights
- Testimonials section
- Call-to-action blocks
- No sidebar

File: `wordpress/templates/single-product-landing.php`

## Selecting a Template

Templates are selected per-product using the "Product Template" meta box in the WordPress admin:

1. Edit any product
2. Find the "Product Template" meta box in the sidebar
3. Select your desired template from the dropdown
4. Publish or update the product

Template choices are stored in the `_product_template` post meta field.

## Theme Overrides

Themes can override plugin templates by creating a `hubspot-ecommerce/` directory in the theme:

```
your-theme/
└── hubspot-ecommerce/
    ├── single-product.php
    ├── single-product-minimal.php
    └── archive-product.php
```

The template loader checks theme files first, then falls back to plugin templates.

## Custom Templates

Themes can register custom product templates using the filter hook:

```php
add_filter('hubspot_ecommerce_product_templates', function($templates) {
    $templates['custom'] = [
        'label' => 'My Custom Template',
        'description' => 'Custom layout for special products',
        'file' => 'single-product-custom.php'
    ];
    return $templates;
});
```

Then create the template file in your theme:

```
your-theme/
└── hubspot-ecommerce/
    └── single-product-custom.php
```

## Template Structure

All product templates have access to:

```php
$product_manager = HubSpot_Ecommerce_Product_Manager::instance();
$cart = HubSpot_Ecommerce_Cart::instance();

$product_id = get_the_ID();
$price = $product_manager->get_product_price($product_id);
$sku = $product_manager->get_product_sku($product_id);
$hubspot_id = $product_manager->get_hubspot_product_id($product_id);
$image_urls = get_post_meta($product_id, '_product_images', true);
```

## Implementation Details

Template selection is handled by `HubSpot_Ecommerce_Template_Loader` class:

- Located: `wordpress/includes/frontend/class-template-loader.php`
- Reads `_product_template` meta
- Maps template choice to file name
- Locates template (theme first, then plugin)
- Falls back to default if selected template not found

Meta box implementation in `HubSpot_Ecommerce_Product_Meta_Boxes` class:

- Located: `wordpress/includes/admin/class-product-meta-boxes.php`
- Renders template selector
- Saves template choice on product save
- Shows template description on selection

## Future Enhancements

- Visual template preview in admin
- Template builder/customizer
- Additional default templates
- Template categories (marketing, minimal, detailed, etc.)
- Per-category default templates
