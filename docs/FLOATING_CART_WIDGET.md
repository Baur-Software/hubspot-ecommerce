# Floating Cart Widget

## Overview

The floating cart widget provides a persistent, always-visible shopping cart icon that floats on the bottom-right of the page. It displays the current cart item count and provides quick access to the cart page.

## Features

- **Conditional Visibility**: Only shows when cart has items (count > 0)
- **Customizable Icon**: Choose from 5 Font Awesome icons via admin settings
- **Animated Badge**: Pulses when items are added to cart
- **Responsive Design**: Adapts to mobile screens
- **Accessibility**: Proper ARIA labels and keyboard focus support
- **GeneratePress Integration**: Uses Font Awesome icons from the GeneratePress theme

## User Experience

### Visibility
- The widget is **hidden** when the cart is empty (0 items)
- The widget **appears** with a smooth animation when the first item is added
- The widget **pulses** briefly when items are added to provide visual feedback
- The widget **disappears** smoothly when the cart becomes empty

### Position
- Desktop: Bottom-right corner (20px from bottom, 20px from right)
- Mobile: Bottom-right corner (15px from bottom, 15px from right)

### Appearance
- Circular button (60px diameter on desktop, 56px on mobile)
- Blue background color (uses WordPress theme accent color)
- White icon (24px on desktop, 22px on mobile)
- Red badge with white text showing item count
- Hover effect: Slight lift and darker blue color
- Shadow for depth

## Configuration

### Admin Settings

Navigate to **HubSpot Ecommerce → Settings** in the WordPress admin.

Under the **Floating Cart Widget** section, you can:

1. **Select Cart Icon**: Choose from available Font Awesome icons:
   - Shopping Cart (default) - `fa-shopping-cart`
   - Cart Plus - `fa-cart-plus`
   - Cart Arrow Down - `fa-cart-arrow-down`
   - Shopping Bag - `fa-shopping-bag`
   - Shopping Basket - `fa-shopping-basket`

2. **Preview Icons**: See visual previews of all available icons

### Database Options

The widget uses the following WordPress options:

- `hubspot_ecommerce_cart_icon` - Selected icon (default: 'shopping-cart')
- `hubspot_ecommerce_cart_page` - Cart page URL (for widget link)

## Technical Implementation

### Files Modified

1. **PHP (Backend)**
   - `includes/frontend/class-frontend.php`
     - Added `render_floating_cart_widget()` method
     - Hooked to `wp_footer` action
   - `includes/admin/class-settings.php`
     - Added icon selection dropdown
     - Added icon preview display
     - Registered new setting

2. **CSS (Styling)**
   - `assets/css/frontend.css`
     - Added `.hs-floating-cart-widget` styles
     - Added responsive media queries
     - Added animation keyframes

3. **JavaScript (Behavior)**
   - `assets/js/frontend.js`
     - Updated `updateCartCount()` to control widget visibility
     - Added pulse animation trigger
     - Added show/hide logic based on cart count

### HTML Structure

```html
<div class="hs-floating-cart-widget" id="hs-floating-cart">
    <a href="/cart/" class="hs-cart-link" aria-label="View shopping cart">
        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
        <span class="hs-cart-count-badge">3</span>
    </a>
</div>
```

### CSS Classes

- `.hs-floating-cart-widget` - Main container
- `.hs-floating-cart-widget.hidden` - Hidden state (opacity: 0, visibility: hidden)
- `.hs-floating-cart-widget.pulse` - Pulse animation trigger
- `.hs-cart-link` - Clickable cart link/button
- `.hs-cart-count-badge` - Badge displaying item count

### JavaScript Events

The widget automatically updates when:
- Items are added to cart (via AJAX)
- Items are removed from cart
- Cart quantities are updated
- Cart is cleared

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with Font Awesome 4.7 support)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Dependencies

- **Font Awesome 4.7**: Provided by GeneratePress theme
- **jQuery**: For DOM manipulation and AJAX
- **WordPress**: Core functions and hooks

## Accessibility Features

1. **ARIA Labels**: Screen readers announce "View shopping cart"
2. **Keyboard Focus**: Visible outline when tabbed to
3. **Semantic HTML**: Uses proper `<a>` link element
4. **Color Contrast**: Meets WCAG AA standards

## Testing

### Manual Testing Checklist

- [ ] Widget is hidden when cart is empty
- [ ] Widget appears when item is added
- [ ] Badge shows correct item count
- [ ] Widget links to cart page
- [ ] Pulse animation plays on add-to-cart
- [ ] Icon changes when setting is updated
- [ ] Responsive design works on mobile
- [ ] Keyboard navigation works
- [ ] Widget hides when cart is emptied

### WP CLI Testing

```bash
# Check cart count
wp eval 'echo HubSpot_Ecommerce_Cart::instance()->get_cart_item_count();' --path="path/to/wordpress"

# Get current icon setting
wp option get hubspot_ecommerce_cart_icon --path="path/to/wordpress"

# Update icon setting
wp option update hubspot_ecommerce_cart_icon "shopping-bag" --path="path/to/wordpress"
```

## Customization

### Changing Widget Position

Edit `assets/css/frontend.css`:

```css
.hs-floating-cart-widget {
    /* Change from bottom-right to bottom-left */
    left: 20px;  /* instead of right: 20px */
}
```

### Changing Colors

The widget uses WordPress theme CSS custom properties:

- Background: `--wp--preset--color--accent` (default: #3498db)
- Badge: `--wp--preset--color--vivid-red` (default: #e74c3c)
- Text: `--wp--preset--color--base` (default: #fff)

Override in your theme or via Customizer.

### Adding Custom Icons

To add more icons, edit `includes/admin/class-settings.php`:

```php
$available_icons = [
    'shopping-cart' => __('Shopping Cart (default)', 'hubspot-ecommerce'),
    'cart-plus' => __('Cart Plus', 'hubspot-ecommerce'),
    // Add your icon here
    'gift' => __('Gift Box', 'hubspot-ecommerce'),
];
```

## Troubleshooting

### Widget Not Appearing

1. Check if Font Awesome is loaded:
   - Inspect page source for `font-awesome.css`
   - Verify GeneratePress theme is active

2. Check cart has items:
   - Widget only shows when count > 0
   - Use browser console: `$('#hs-floating-cart').hasClass('hidden')`

3. Check z-index conflicts:
   - Widget uses z-index: 9999
   - Other fixed elements may overlap

### Icon Not Changing

1. Clear browser cache
2. Verify setting is saved in database
3. Check for JavaScript errors in console

### Pulse Animation Not Working

1. Ensure jQuery is loaded
2. Check for JavaScript errors
3. Verify AJAX responses include `cart_count`

## Future Enhancements

Potential improvements:

- [ ] Mini-cart dropdown on hover/click
- [ ] Cart preview with item list
- [ ] Quick remove from mini-cart
- [ ] Position customization (left/right)
- [ ] Color customization in admin
- [ ] Custom icon upload support
- [ ] Animation style options
- [ ] Sound effects on add-to-cart

## Support

For issues or feature requests, please:
1. Check this documentation
2. Review browser console for errors
3. Test with default settings
4. Contact support at baursoftware.com
