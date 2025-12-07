# HubSpot Ecommerce Buy Button Shortcode

## Overview

The `[hubspot_buy_button]` shortcode creates a customizable "Add to Cart" or "Buy Now" button that can be placed anywhere on your WordPress site. It supports automatic redirect to checkout, quantity selectors, icons from GeneratePress Pro, and automatically hides quantity fields for services and subscriptions.

## Basic Usage

```
[hubspot_buy_button product_id="291" text="Sign Up Now" redirect="checkout"]
```

## Parameters

### Required Parameters

- **product_id** (integer) - The WordPress product ID
  - Example: `product_id="291"`

OR

- **product** (string) - The product slug (alternative to product_id)
  - Example: `product="data-360"`

### Optional Parameters

- **text** (string) - Button text
  - Default: `"Add to Cart"`
  - Example: `text="Buy Now"`

- **redirect** (string) - Redirect behavior after adding to cart
  - Values: `"checkout"` or leave empty
  - Default: Empty (no redirect, just adds to cart)
  - Example: `redirect="checkout"`

- **show_quantity** (boolean) - Show quantity selector
  - Values: `"1"`, `"true"`, `"0"`, `"false"`
  - Default: Automatically determined based on product type (hidden for services/subscriptions)
  - Example: `show_quantity="1"`

- **quantity** (integer) - Default quantity value
  - Default: `"1"`
  - Example: `quantity="2"`

- **class** (string) - Additional CSS classes
  - Default: Empty
  - Example: `class="my-custom-button-class"`

- **style** (string) - Inline CSS styles
  - Default: Empty
  - Example: `style="background: red; color: white;"`

- **icon** (string) - Icon class from GeneratePress Pro
  - Default: Empty (no icon)
  - Example: `icon="shopping-cart"`, `icon="cart-plus"`

- **icon_position** (string) - Icon position relative to text
  - Values: `"left"` or `"right"`
  - Default: `"left"`
  - Example: `icon_position="right"`

## Usage Examples

### 1. Simple Add to Cart Button

```
[hubspot_buy_button product_id="291" text="Add to Cart"]
```

### 2. Buy Now Button (Redirects to Checkout)

```
[hubspot_buy_button product_id="291" text="Buy Now" redirect="checkout"]
```

### 3. Button with Quantity Selector

```
[hubspot_buy_button product_id="280" text="Add to Cart" show_quantity="1"]
```

### 4. Button with Icon (Left Side)

```
[hubspot_buy_button product_id="291" text="Add to Cart" icon="shopping-cart"]
```

### 5. Button with Icon (Right Side)

```
[hubspot_buy_button product_id="291" text="Buy Now" icon="arrow-right" icon_position="right" redirect="checkout"]
```

### 6. Using Product Slug Instead of ID

```
[hubspot_buy_button product="data-360" text="Get Started" redirect="checkout"]
```

### 7. Custom Styled Button

```
[hubspot_buy_button product_id="291" text="Sign Up" redirect="checkout" class="custom-btn" style="background: #8e44ad; padding: 1rem 2rem;"]
```

### 8. Button with Default Quantity of 3

```
[hubspot_buy_button product_id="294" text="Add to Cart" show_quantity="1" quantity="3"]
```

## Automatic Behavior

### Quantity Field Auto-Hide

The quantity selector is automatically hidden for:
- Products with `_product_type` meta set to `"service"`
- Products with `_is_subscription` meta set to a truthy value

You can override this behavior by explicitly setting `show_quantity="1"` or `show_quantity="0"`.

### Product Type Detection

The shortcode automatically:
- Validates that the product exists
- Checks the product type and subscription status
- Determines appropriate UI based on product metadata

## GeneratePress Pro Icons

The shortcode supports icons from GeneratePress Pro theme. Common icon examples:

- `shopping-cart`
- `cart-plus`
- `check`
- `arrow-right`
- `arrow-left`
- `star`
- `heart`

Icon format: `icon="icon-name"`

## CSS Classes

The generated button includes the following CSS classes:
- `.hs-buy-button` - Base button class
- `.hs-add-to-cart-checkout` - Added when `redirect="checkout"` (redirects after adding)
- `.add-to-cart-quick` - Added when no redirect (just adds to cart)
- Any custom classes specified in the `class` parameter

## JavaScript Events

The button automatically triggers these events:
- AJAX add to cart request
- Cart count update in header
- Success/error messages
- Automatic redirect to `/checkout/` (if `redirect="checkout"`)

## Styling Customization

You can customize the button appearance using:

1. **Custom CSS classes**:
   ```
   [hubspot_buy_button product_id="291" class="my-custom-class"]
   ```

2. **Inline styles**:
   ```
   [hubspot_buy_button product_id="291" style="background: red;"]
   ```

3. **Theme CSS** - Target these classes:
   - `.hs-buy-button-wrapper` - Container
   - `.hs-quantity-wrapper` - Quantity selector container
   - `.hs-quantity-input` - Quantity input field
   - `.hs-buy-button` - Button element
   - `.button-text` - Button text wrapper
   - `.gp-icon` - Icon element

## Error Handling

The shortcode displays user-friendly error messages when:
- No product ID or slug is provided
- Product doesn't exist
- Product has wrong post type (not `hs_product`)

Example error output:
```
Invalid product specified.
Product not found.
```

## Integration with Existing Pages

### Replacing Manual Button Code

If you have existing manual button code like:
```html
<a class="wp-block-button__link hs-add-to-cart-checkout"
   href="#"
   data-product-id="291">
   Sign Up Now
</a>
```

You can replace it with:
```
[hubspot_buy_button product_id="291" text="Sign Up Now" redirect="checkout"]
```

### WordPress Block Editor

In the WordPress Block Editor (Gutenberg):
1. Add a **Shortcode Block**
2. Paste your shortcode
3. Preview/Publish

### Page Builders

Works with popular page builders:
- **Elementor**: Use the Shortcode widget
- **Beaver Builder**: Use the HTML module
- **Divi**: Use the Text module with shortcode
- **GeneratePress**: Use shortcode in any text field

## Troubleshooting

### Button Not Working
- Ensure jQuery is loaded on the page
- Check browser console for JavaScript errors
- Verify the product ID is correct

### Quantity Field Not Showing
- Check if product type is "service" (quantity is hidden by default)
- Explicitly set `show_quantity="1"` to force display

### Icon Not Displaying
- Ensure GeneratePress Pro theme is active
- Check icon name spelling
- Verify theme icon library is loaded

## Best Practices

1. **Use product slugs** for better portability: `product="data-360"` instead of `product_id="291"`
2. **Add meaningful button text**: Be clear about the action (e.g., "Sign Up Now" vs "Click Here")
3. **Use redirect="checkout"** for single-product purchases to streamline the buying process
4. **Hide quantities** for services/subscriptions (this is automatic)
5. **Add icons** to improve visual appeal and user experience
6. **Test responsive behavior** - buttons adapt to mobile screens

## Support

For issues or feature requests, contact your WordPress administrator or developer.
