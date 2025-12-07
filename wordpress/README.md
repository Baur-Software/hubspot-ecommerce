# HubSpot Ecommerce for WordPress

A full-featured ecommerce plugin that uses HubSpot as the backend for products, orders, subscriptions, and customer management. Built for WordPress with compatibility for the Twenty Twenty-Five theme.

## Features

### 🛍️ Product Management

**Local-First Workflow** (Free Tier)

- Create products in WordPress first (WordPress as source of truth)
- Choose from 4 product templates: Default, Minimal, Detailed, Landing
- Manual push TO HubSpot (on-demand via product editor)
- Manual pull FROM HubSpot (on-demand sync)
- Product images, pricing, SKUs, and descriptions
- Product categories and tags

**Auto-Sync** (Pro Feature)

- Automatic scheduled sync FROM HubSpot (hourly, twice daily, daily)
- Auto-push TO HubSpot on product save
- Configurable sync intervals

**[Local-First Workflow Guide →](../docs/LOCAL_FIRST_WORKFLOW.md)**
**[Product Templates Guide →](../docs/PRODUCT_TEMPLATES.md)**

### 🛒 Shopping Cart

- Session-based cart system
- Add, update, and remove items
- Real-time cart updates via AJAX
- Persistent cart storage

### 💳 Checkout & Orders

- Complete checkout flow
- Creates deals in HubSpot automatically
- Associates line items with deals
- Order history for customers

### 👤 Customer Management

- Syncs WordPress users to HubSpot contacts (on registration only)
- Updates contact information on profile changes
- Customer order history
- Account dashboard

### 🔄 Subscription Management (Pro Feature)

- **Commerce Subscriptions**: Automatic detection of recurring/subscription products from HubSpot
- **Email Subscription Types**: Full integration with HubSpot marketing subscription preferences
- Admin interface to sync and manage subscription types
- Customer-facing email preference management
- Real-time subscription status updates
- GDPR-compliant with legal basis support
- **[Full Subscription Documentation →](SUBSCRIPTIONS.md)**

### 💼 Pro Features

**Pro Tier** ($99/year)

- Automatic product sync FROM HubSpot (scheduled)
- Auto-push TO HubSpot on save
- Private App authentication
- Subscriptions (recurring billing)
- HubSpot Payments (invoices)
- Email preference management

**Enterprise Tier** ($299/year)

- Everything in Pro
- Multi-store support
- Priority support
- Custom features

**[Pro Features Documentation →](../docs/PRO_FEATURES.md)**

### 🎨 Theme Integration

- Optimized for Twenty Twenty-Five theme
- Uses Twenty Twenty-Five CSS custom properties
- Template override support
- Shortcodes for easy page building
- Fully responsive design

## Installation

### Production Installation

1. **Upload the plugin**

   ```
   wp-content/plugins/hubspot-ecommerce/
   ```

2. **Install dependencies**

   ```bash
   cd wp-content/plugins/hubspot-ecommerce/
   composer install
   ```

3. **Activate the plugin**
   - Go to WordPress Admin → Plugins
   - Find "HubSpot Ecommerce"
   - Click "Activate"

4. **Configure HubSpot API**
   - Go to HubSpot Shop → Settings
   - Enter your HubSpot API key
   - Test the connection
   - Save settings

5. **Create pages and add shortcodes**
   - **Shop page**: `[hubspot_products]`
   - **Cart page**: `[hubspot_cart]`
   - **Checkout page**: `[hubspot_checkout]`
   - **Account page**: `[hubspot_account]`

6. **Assign pages in settings**
   - Go to HubSpot Shop → Settings
   - Select the pages you created for Shop, Cart, Checkout, and Account

### Demo/Testing Mode Installation

Want to try the plugin without HubSpot API credentials? Use **Demo Mode**!

```bash
# Enable demo mode in wp-config.php
define('HUBSPOT_ECOMMERCE_DEMO_MODE', true);

# Or via WP-CLI
wp option update hubspot_ecommerce_demo_mode 1

# Sync mock products
wp cron event run hubspot_ecommerce_sync_products
```

**[Complete Demo Mode Guide →](DEMO_MODE_GUIDE.md)**

Demo mode provides:

- ✅ 3 mock products (Test Widget, Premium Gadget, Subscription Service)
- ✅ Mock HubSpot API responses
- ✅ Full checkout flow without real API calls
- ✅ E2E testing without credentials
- ✅ Safe offline development

## Getting Your HubSpot API Key

1. Log into your HubSpot account
2. Navigate to Settings → Integrations → API Key
3. Copy your Private App access token or API key
4. Paste it in the plugin settings

**Note**: You need appropriate permissions in HubSpot to:

- Read products
- Create/update contacts
- Create/update deals
- Create line items
- Manage associations

## Usage

### Creating and Syncing Products

**Local-First Workflow** (Free Tier)

Products are created in WordPress first, then optionally synced to HubSpot:

1. **Create Product**
   - Go to Products → Add New
   - Fill in title, description, price, SKU
   - Choose a product template (default, minimal, detailed, landing)
   - Publish

2. **Manual Push to HubSpot**
   - In product editor, find "HubSpot Sync" meta box
   - Click "Push to HubSpot" button
   - Product is created/updated in HubSpot

3. **Manual Pull from HubSpot**
   - Go to HubSpot Shop → Product Sync
   - Click "Pull Products from HubSpot"
   - Or use pull button in product editor

**Auto-Sync** (Pro Feature)

- Products sync automatically based on the interval you set in Settings
- Auto-push on save: Products automatically sync TO HubSpot when saved
- Auto-pull scheduled: Products automatically sync FROM HubSpot on schedule
- Default interval: Hourly

### Product Display

**Archive Page**

- Automatically available at `/shop/`
- Shows all products in a grid layout

**Single Product Page**

- Click any product to view details
- Shows images, description, price, SKU
- Add to cart functionality
- **Multiple templates available**:
  - **Default**: Full-featured product page
  - **Minimal**: Clean, simple layout
  - **Detailed**: Extended product information
  - **Landing**: Landing page optimized layout

#### Template Selection

Choose template in product editor:

1. Edit any product
2. Find "Product Template" meta box
3. Select desired template
4. Update product

#### Template Override

Copy templates to your theme:

```
your-theme/
└── hubspot-ecommerce/
    ├── single-product.php          # Default template
    ├── single-product-minimal.php  # Minimal template
    ├── single-product-detailed.php # Detailed template
    └── single-product-landing.php  # Landing template
```

**Using Shortcodes**

```
[hubspot_products limit="8" orderby="date" order="DESC"]
[hubspot_products category="featured"]
```

### Shopping Cart

Customers can:

- Add products from any page
- Update quantities
- Remove items
- View cart totals
- Proceed to checkout

### Checkout Process

1. Customer fills in billing details
2. Reviews order summary
3. Clicks "Place Order"
4. Plugin creates:
   - Contact in HubSpot (or updates existing)
   - Deal for the order
   - Line items for each product
   - Order post in WordPress
5. Customer sees order confirmation

### Customer Accounts

When users register on your WordPress site:

- Automatically creates a contact in HubSpot
- Stores HubSpot contact ID in WordPress
- Syncs profile updates to HubSpot
- Shows order history in account dashboard

**Important**: Contacts are NOT imported from HubSpot to WordPress. Only WordPress users are synced TO HubSpot.

## Template Customization

You can override any template by copying it to your theme:

```
your-theme/
└── hubspot-ecommerce/
    ├── single-product.php
    ├── archive-product.php
    ├── cart/
    │   └── cart.php
    ├── checkout/
    │   └── checkout.php
    └── account/
        └── dashboard.php
```

## Hooks & Filters

### Actions

```php
// After product synced from HubSpot
do_action('hubspot_ecommerce_product_synced', $post_id, $hubspot_product);

// After all products synced
do_action('hubspot_ecommerce_products_synced', $synced_count, $errors);

// After item added to cart
do_action('hubspot_ecommerce_cart_item_added', $product_id, $quantity);

// After order created
do_action('hubspot_ecommerce_order_created', $order_id, $deal_id);

// After user synced to HubSpot
do_action('hubspot_ecommerce_user_synced', $user_id, $contact_id);
```

### Filters

```php
// Customize product sync properties
add_filter('hubspot_ecommerce_product_properties', function($properties) {
    $properties[] = 'custom_field';
    return $properties;
});
```

## Requirements

- WordPress 6.4+
- PHP 8.1+
- HubSpot account with API access (or use Demo Mode)
- Composer (for installation)

## HubSpot Setup

### Required Objects

- **Products**: Your product catalog
- **Contacts**: Customer records
- **Deals**: Order records
- **Line Items**: Order line items

### Recommended Pipeline Setup

Create a custom pipeline for ecommerce orders:

1. Go to Sales → Deals → Pipelines
2. Create a new pipeline called "Ecommerce Orders"
3. Add stages:
   - New Order
   - Processing
   - Shipped
   - Completed
   - Cancelled

Update the plugin to use your pipeline:

```php
// In includes/class-checkout.php, update the create_deal method
'pipeline' => 'your-pipeline-id'
```

## Troubleshooting

### Products not syncing

- Check API key is valid
- Ensure HubSpot account has products
- Check error log in HubSpot Shop → Dashboard

### Orders not creating in HubSpot

- Verify API key has write permissions
- Check HubSpot deal pipeline exists
- Review WordPress debug log

### Cart not working

- Clear browser cookies
- Check database table exists: `wp_hubspot_cart_items`
- Verify JavaScript is loading (no console errors)

## Testing

### Automated Test Suite

This plugin includes a comprehensive Playwright test suite:

```bash
# Install test dependencies
npm install

# Run all tests
npm test

# Run with visible browser
npm run test:headed

# Run in debug mode
npm run test:debug

# View test report
npm run test:report
```

**Test Coverage:**

- ✅ 20 tests: Framework validation + Code security checks (no WordPress required)
- ✅ 26 tests: E2E tests for product browsing, checkout, and security (require WordPress)

**[Complete Testing Guide →](TESTING_GUIDE.md)**

### Test Results

Latest test run: **46/46 tests passing (100%)**

Security validations:

- ✅ SQL injection prevention verified
- ✅ IDOR protection confirmed
- ✅ File upload restrictions validated
- ✅ Output escaping verified
- ✅ Webhook signature verification tested

**[View Test Results →](FINAL_TEST_RESULTS.md)**

## Development

### File Structure

```
hubspot-ecommerce/
├── hubspot-ecommerce.php           # Main plugin file
├── includes/
│   ├── class-hubspot-api.php       # HubSpot API wrapper
│   ├── class-mock-hubspot-api.php  # Mock API for testing
│   ├── class-license-manager.php   # Pro feature gating
│   ├── class-product-manager.php
│   ├── class-cart.php
│   ├── class-checkout.php
│   ├── class-customer.php
│   ├── class-invoice-manager.php   # Invoice/payment handling
│   ├── webhooks/
│   │   └── class-payment-webhook.php
│   ├── admin/
│   │   ├── class-admin.php
│   │   ├── class-settings.php
│   │   └── class-product-meta-boxes.php  # Product editor enhancements
│   └── frontend/
│       ├── class-frontend.php
│       └── class-template-loader.php  # Multi-template support
├── templates/                      # Template files
│   ├── single-product.php         # Default template
│   ├── single-product-minimal.php # Minimal template
│   └── ...                        # Other templates
├── assets/                         # CSS and JS
├── tests/                          # Playwright E2E tests
├── composer.json
└── package.json
```

### Contributing

This is a starter template. Feel free to:

- Add payment gateway integrations
- Implement subscription management
- Add shipping calculations
- Create additional reports
- Improve security and performance

## Security

### Security Features

This plugin implements multiple layers of security:

✅ **SQL Injection Prevention**: All database queries use prepared statements and proper escaping
✅ **IDOR Protection**: Authorization checks prevent unauthorized access to orders
✅ **File Upload Restrictions**: 7-layer validation for product images (HTTPS, domain whitelist, file type, size limits)
✅ **Object Injection Prevention**: All HubSpot data is sanitized before storage
✅ **Output Escaping**: All user-generated content is escaped before display
✅ **Webhook Verification**: HMAC-SHA256 signature verification for payment webhooks
✅ **CSRF Protection**: WordPress nonces on all forms and AJAX requests

**[View Security Audit Results →](ISSUES_REPORT.md)**

### Security Notes

- Never commit your API key to version control
- Use WordPress nonces for all AJAX requests
- Sanitize and validate all user input
- Escape all output
- Follow WordPress coding standards
- All security vulnerabilities have been fixed and verified by automated tests

## Implemented Features

- ✅ **Local-First Product Workflow**: WordPress as source of truth with manual sync
- ✅ **Product Templates**: 4 templates (default, minimal, detailed, landing)
- ✅ **License Manager**: Pro feature gating with Free/Pro/Enterprise tiers
- ✅ **Payment Integration**: HubSpot Commerce Hub invoices with payment links (Pro)
- ✅ **Subscription Product Support**: Automatic detection from HubSpot (Pro)
- ✅ **Email Subscription Management**: Full marketing preferences integration (Pro)
- ✅ **Webhook Handling**: Payment status updates from HubSpot
- ✅ **Demo Mode**: Mock backend for testing without API credentials
- ✅ **Automated Testing**: 46 Playwright tests covering security and functionality
- ✅ **Customer Account Dashboard**: Order history and email preferences

## Future Enhancements

- [ ] Additional payment gateways (Stripe, PayPal direct)
- [ ] Inventory management and stock tracking
- [ ] Email notifications for order status
- [ ] Tax calculations based on location
- [ ] Multiple shipping methods
- [ ] Coupon/discount codes
- [ ] Product reviews and ratings
- [ ] Wishlist functionality
- [ ] Advanced analytics and reporting
- [ ] Multi-currency support

## License

GPL v2 or later

## Support

For issues and feature requests, please use the GitHub repository.

## Credits

Built with ❤️ for WordPress and HubSpot users who want the power of HubSpot CRM behind their ecommerce store.
