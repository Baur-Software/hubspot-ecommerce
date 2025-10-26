# Pro Features & License Tiers

The HubSpot Ecommerce plugin offers three license tiers with progressively advanced features.

## License Tiers

### Free Tier

Core ecommerce functionality at no cost.

**Product Management:**

- Unlimited products
- Manual sync to/from HubSpot
- Product templates
- Local-first workflow

**Cart & Checkout:**

- Shopping cart
- Basic checkout
- Order management

**HubSpot Integration:**

- OAuth authentication
- Manual product sync
- Customer sync
- Order sync

### Pro Tier

Advanced automation and business features.

**Everything in Free, plus:**

**Automatic Product Sync:**

- Auto-sync FROM HubSpot (scheduled)
- Auto-push TO HubSpot (on save)
- Configurable sync intervals

**Private App Authentication:**

- Use HubSpot Private App tokens
- Required for advanced API scopes
- More reliable than OAuth

**Subscriptions:**

- Recurring billing products
- Subscription management
- Billing period configuration

**HubSpot Payments:**

- Invoice creation
- Payment processing
- Receipt management

**Email Preferences:**

- Customer email preferences
- Subscription management
- GDPR compliance

### Enterprise Tier

Multi-store and advanced features.

**Everything in Pro, plus:**

**Multi-Store Support:**

- Multiple WordPress sites
- Single HubSpot account
- Centralized management

**Priority Support:**

- Direct support access
- Faster response times
- Implementation assistance

**Custom Features:**

- Custom integrations
- Webhook handlers
- Advanced reporting

## Feature Gating Implementation

### License Manager

The `HubSpot_Ecommerce_License_Manager` class handles feature checking:

```php
$license_manager = HubSpot_Ecommerce_License_Manager::instance();

// Check license status
$license_manager->is_licensed();           // Any paid tier
$license_manager->get_tier();              // 'free', 'pro', 'enterprise'
$license_manager->get_status();            // 'active', 'inactive', 'expired'

// Check specific features
$license_manager->can_use_private_app();    // Pro+
$license_manager->can_use_subscriptions();  // Pro+
$license_manager->can_use_invoices();       // Pro+
$license_manager->can_use_email_preferences(); // Pro+
$license_manager->can_use_auto_sync();      // Pro+
$license_manager->can_use_multistore();     // Enterprise only
```

### Auto-Sync Gating

**Product Manager** checks license before scheduling:

```php
$license_manager = HubSpot_Ecommerce_License_Manager::instance();
$auto_sync_enabled = get_option('hubspot_ecommerce_auto_sync_from_hubspot', false);

if ($license_manager->can_use_auto_sync() && $auto_sync_enabled) {
    // Schedule automatic sync
    wp_schedule_event(time(), $interval, 'hubspot_ecommerce_sync_products');
}
```

Auto-push on product save:

```php
if ($this->license_manager->can_use_auto_sync() && $sync_enabled === '1') {
    $auto_push = get_option('hubspot_ecommerce_auto_push_products', false);
    if ($auto_push) {
        do_action('hubspot_ecommerce_auto_push_product', $post_id);
    }
}
```

### Settings Page Gating

Settings show Pro features with appropriate controls:

```php
$license_manager = HubSpot_Ecommerce_License_Manager::instance();
$is_pro = $license_manager->can_use_auto_sync();

if (!$is_pro) {
    // Show disabled checkbox with Pro badge
    // Display upgrade notice
}
```

Free users see:

- Disabled checkboxes for Pro features
- Pro badges on settings
- Upgrade links to purchase page
- Clear explanation of limitations

### Meta Box Gating

Product editor shows Pro features with upgrade prompts:

```php
<?php if (!$this->license_manager->can_use_auto_sync()) : ?>
    <div class="hubspot-pro-feature">
        <p><strong>Pro Feature</strong></p>
        <p>Upgrade to enable automatic sync on save and scheduled sync from HubSpot.</p>
        <a href="https://baursoftware.com/products/hubspot-ecommerce" target="_blank">
            Learn more
        </a>
    </p>
    </div>
<?php endif; ?>
```

## License Activation

### Setup Process

1. **Purchase License**
   - Visit <https://baursoftware.com/products/hubspot-ecommerce>
   - Select Pro or Enterprise tier
   - Complete purchase

2. **Receive License Key**
   - Delivered via email
   - Format: `xxxx-xxxx-xxxx-xxxx`

3. **Activate in WordPress**
   - Go to HubSpot Shop → License
   - Enter license key
   - Click "Activate License"

4. **Verification**
   - Plugin contacts license server
   - Validates key against purchase
   - Activates Pro features immediately

### License Server

License validation uses **License Manager for WooCommerce** on baursoftware.com:

**API Endpoint:** `https://baursoftware.com/wp-json/lmfwc/v2/licenses`

**Authentication:**

- Consumer Key: Set via `HUBSPOT_LICENSE_CONSUMER_KEY` constant
- Consumer Secret: Set via `HUBSPOT_LICENSE_CONSUMER_SECRET` constant

**Validation Process:**

1. Plugin sends license key to server
2. Server checks key validity
3. Server returns tier and status
4. Plugin caches result locally
5. Daily revalidation

**Cached Data:**

- License key (hashed)
- License tier (free/pro/enterprise)
- License status (active/inactive/expired)
- Last check timestamp

### License Management

**Deactivation:**

- Go to HubSpot Shop → License
- Click "Deactivate License"
- Frees activation for use on another site

**Transfer:**

- Deactivate on old site
- Activate on new site
- Each license allows 1 active site

**Renewal:**

- Licenses are typically annual
- Renewal notice shown before expiration
- Grace period after expiration

**Refunds:**

- 30-day money-back guarantee
- Contact support for refund requests

## Upgrade Path

### From Free to Pro

1. Purchase Pro license
2. Activate license key
3. Pro features immediately available
4. Enable desired Pro features in Settings

**No data loss** - all existing products, orders, and customers remain unchanged.

### From Pro to Enterprise

1. Purchase Enterprise upgrade
2. Replace license key
3. Enterprise features immediately available
4. Configure multi-store settings

## Visual Indicators

### Pro Badges

Pro features show badges throughout admin:

```
[PRO] Auto-Sync FROM HubSpot
```

Styling:

- Blue background (#2271b1)
- White text
- Small font size
- Inline with feature name

### Upgrade Notices

Contextual upgrade prompts appear:

- Settings page (top of Pro features section)
- Product editor (sync meta box)
- Sync page (for automatic sync)

**Free Tier Notice Example:**

```
Automatic Product Sync is a Pro Feature

Free tier: Manual push/pull buttons in product editor.
Pro tier: Automatic scheduled sync from HubSpot + auto-push on save.

[Upgrade to Pro →]
```

### Disabled Controls

Free users see Pro features with:

- Grayed out/disabled checkboxes
- Pro badge next to label
- Explanation below control
- Upgrade link

## Feature Comparison Table

| Feature | Free | Pro | Enterprise |
|---------|------|-----|------------|
| Products | ✅ Unlimited | ✅ Unlimited | ✅ Unlimited |
| Product Templates | ✅ | ✅ | ✅ |
| Manual Sync | ✅ | ✅ | ✅ |
| Auto-Sync | ❌ | ✅ | ✅ |
| OAuth Auth | ✅ | ✅ | ✅ |
| Private App Auth | ❌ | ✅ | ✅ |
| Subscriptions | ❌ | ✅ | ✅ |
| Invoices/Payments | ❌ | ✅ | ✅ |
| Email Preferences | ❌ | ✅ | ✅ |
| Multi-Store | ❌ | ❌ | ✅ |
| Priority Support | ❌ | ❌ | ✅ |

## Development & Testing

### Demo Mode

Demo mode provides Pro features for testing:

```php
define('HUBSPOT_DEMO_MODE', true);
```

With demo mode:

- All Pro features enabled
- Mock HubSpot API (no real connection needed)
- Sample products and data
- Perfect for local development

### License Override

For development, set license tier directly:

```php
// In wp-config.php
define('HUBSPOT_LICENSE_TIER', 'pro'); // or 'enterprise'
```

This bypasses license server validation.

## Support

### Free Tier

- Community support (GitHub issues)
- Documentation
- Knowledge base

### Pro Tier

- Email support
- Response within 2 business days
- Access to all docs

### Enterprise Tier

- Priority email support
- Response within 1 business day
- Implementation consultation
- Custom feature requests

**Support Portal:** <https://baursoftware.com/support>
**GitHub Issues:** <https://github.com/baursoftware/hubspot-ecommerce/issues>

## Pricing

**Pro:** $99/year per site
**Enterprise:** $299/year per site

Volume discounts available for 5+ sites.

**Purchase:** <https://baursoftware.com/products/hubspot-ecommerce>

## Future Features (Roadmap)

**Planned Pro Features:**

- Abandoned cart recovery
- Advanced reporting
- Customer segmentation
- Product recommendations

**Planned Enterprise Features:**

- Multi-currency support
- Advanced webhooks
- Custom workflows
- Dedicated account manager
