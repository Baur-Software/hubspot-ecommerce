# HubSpot Subscriptions Integration

This plugin now includes comprehensive subscription support for both **Commerce Subscriptions** (recurring billing/products) and **Marketing Email Subscription Types**.

## Overview

The plugin integrates with two types of HubSpot subscriptions:

1. **Commerce Subscriptions (CRM)** - For recurring billing and subscription products
2. **Email Subscription Types (Marketing)** - For managing email communication preferences

## Features

### 1. Commerce Subscriptions

#### Product Sync
- Automatically detects recurring/subscription products from HubSpot
- Syncs recurring billing properties:
  - `hs_recurring_billing_period`
  - `recurringbillingfrequency`
  - `hs_billing_period_units`

#### Subscription Detection
Products with recurring billing are automatically tagged and can be:
- Displayed differently in the storefront
- Processed as subscriptions during checkout
- Associated with HubSpot subscription objects

### 2. Email Subscription Types

#### Admin Interface
- **HubSpot Shop → Subscriptions** menu
- Sync email subscription types from HubSpot
- View all subscription types with:
  - Name and description
  - Purpose (Marketing, Sales, Service, etc.)
  - Active/Inactive status
  - Option to display on checkout

#### Customer Account
- Customers can manage their email preferences
- **My Account → Email Preferences** link
- Subscribe/Unsubscribe from available types
- Real-time updates via AJAX
- Changes sync immediately to HubSpot

## Setup

### 1. HubSpot Configuration

#### For Commerce Subscriptions:
1. In HubSpot, go to Sales → Products
2. Create or edit products
3. Enable "Recurring billing" options
4. Set billing frequency (monthly, yearly, etc.)
5. Sync products in plugin: **HubSpot Shop → Sync Products**

#### For Email Subscription Types:
1. In HubSpot, go to Settings → Marketing → Email
2. Click "Subscription Types"
3. Create subscription types for different communication categories:
   - Newsletter
   - Product Updates
   - Promotions
   - Blog Updates
   - etc.
4. Sync to plugin: **HubSpot Shop → Subscriptions → Sync Types**

### 2. API Permissions Required

Your HubSpot API key needs these scopes:

#### CRM Objects:
- `crm.objects.contacts.read`
- `crm.objects.contacts.write`
- `crm.objects.deals.read`
- `crm.objects.deals.write`
- `crm.objects.line_items.write`
- `crm.objects.products.read`
- `crm.objects.subscriptions.read`
- `crm.objects.subscriptions.write`

#### Communication Preferences:
- `communication_preferences.read`
- `communication_preferences.write`
- `communication_preferences.read_write`

### 3. Plugin Configuration

1. Activate the plugin
2. Configure API key with proper permissions
3. Sync subscription types
4. (Optional) Choose which subscription types to display on checkout

## Usage

### For Administrators

#### Sync Subscription Types
```
WordPress Admin → HubSpot Shop → Subscriptions → Sync Subscription Types from HubSpot
```

This fetches all email subscription types from your HubSpot account and stores them locally.

#### Manage Checkout Display
In the subscription types table, check which types should be shown during checkout. Customers can opt-in to these during the checkout process.

### For Customers

#### Viewing Subscriptions
1. Log into account
2. Go to "My Account"
3. Click "Email Preferences"
4. See all available subscription types with current status

#### Managing Subscriptions
1. Check boxes to subscribe
2. Uncheck boxes to unsubscribe
3. Click "Update Preferences"
4. Changes sync to HubSpot immediately

### For Developers

#### Check if Product is Subscription
```php
$subscription_manager = HubSpot_Ecommerce_Subscription_Manager::instance();
$is_subscription = $subscription_manager->is_subscription_product($post_id);
```

#### Get Subscription Details
```php
$details = $subscription_manager->get_product_subscription_details($post_id);
// Returns:
// [
//     'billing_period' => 'MONTHLY',
//     'billing_frequency' => '1',
//     'billing_period_units' => 'MONTH'
// ]
```

#### Get Contact's Subscription Statuses
```php
$statuses = $subscription_manager->get_contact_statuses('email@example.com');
```

#### Subscribe Contact to Types
```php
$subscription_ids = ['123', '456'];
$results = $subscription_manager->subscribe_contact_to_types(
    'email@example.com',
    $subscription_ids,
    'CONSENT' // Legal basis
);
```

## API Endpoints Used

### Commerce Subscriptions (v3)
- `GET /crm/v3/objects/subscriptions` - List subscriptions
- `GET /crm/v3/objects/subscriptions/{id}` - Get subscription
- `POST /crm/v3/objects/subscriptions` - Create subscription
- `PATCH /crm/v3/objects/subscriptions/{id}` - Update subscription

### Email Subscription Types (v4)
- `GET /communication-preferences/v4/definitions` - Get all subscription type definitions
- `GET /communication-preferences/v4/statuses/{email}` - Get contact's subscription statuses
- `POST /communication-preferences/v4/statuses/{email}/subscribe` - Subscribe contact
- `POST /communication-preferences/v4/statuses/{email}/unsubscribe` - Unsubscribe contact
- `POST /communication-preferences/v4/statuses/{email}/unsubscribe-all` - Unsubscribe from all

## Hooks & Filters

### Actions

```php
// After subscription types synced
do_action('hubspot_ecommerce_subscription_types_synced', $types);
```

### Filters

```php
// Customize checkout subscription types
add_filter('hubspot_ecommerce_checkout_subscription_types', function($types) {
    // Modify which types appear on checkout
    return $types;
});
```

## Templates

### Subscription Preferences Page
Location: `templates/account/subscription-preferences.php`

Override in your theme:
```
your-theme/hubspot-ecommerce/account/subscription-preferences.php
```

## Styling

The subscription preferences page uses Twenty Twenty-Five CSS variables:

```css
.hubspot-subscription-preferences {
    /* Automatically adapts to theme colors */
    background: var(--wp--preset--color--base);
    color: var(--wp--preset--color--contrast);
}

.status-subscribed {
    background: var(--wp--preset--color--pale-cyan-blue);
    color: var(--wp--preset--color--vivid-green-cyan);
}
```

## Troubleshooting

### Subscription types not syncing
- Check API key has `communication_preferences.read_write` scope
- Verify subscription types exist in HubSpot
- Check WordPress debug.log for API errors

### Customers can't update preferences
- Ensure they're logged in
- Check API key has write permissions
- Verify customer email exists in HubSpot

### Products not showing as subscriptions
- Ensure "Recurring billing" is enabled in HubSpot
- Re-sync products from HubSpot
- Check product meta: `_is_subscription`

## Legal Compliance

### GDPR/Privacy
- Plugin respects HubSpot's legal basis requirements
- Default legal basis: `CONSENT`
- Legal basis explanation included in API calls
- Customers can unsubscribe anytime

### Legal Basis Options
- `LEGITIMATE_INTEREST_CLIENT` - Legitimate business interest
- `LEGITIMATE_INTEREST_OTHER` - Other legitimate interest
- `PERFORMANCE_OF_CONTRACT` - Necessary for contract
- `CONSENT` - Explicit consent (default)
- `NON_GDPR` - Outside GDPR jurisdiction
- `PROCESS_AND_STORE` - General processing

## Future Enhancements

Potential additions:
- [ ] Display subscription billing info on product pages
- [ ] Create subscription records in HubSpot on checkout
- [ ] Subscription management dashboard for admins
- [ ] Webhook integration for subscription updates
- [ ] Automatic invoice generation for subscriptions
- [ ] Trial periods and setup fees
- [ ] Subscription cancellation flow
- [ ] Payment method management

## Resources

- [HubSpot Subscriptions API Documentation](https://developers.hubspot.com/docs/api-reference/crm-commerce-subscriptions-v3/guide)
- [HubSpot Communication Preferences API](https://developers.hubspot.com/docs/api-reference/communication-preferences-subscriptions-v4/guide)
- [HubSpot Marketing Subscription Types Guide](https://knowledge.hubspot.com/contacts/how-do-subscription-preferences-and-types-work)

## Support

For subscription-related issues:
1. Check this documentation
2. Review HubSpot API documentation
3. Check plugin logs in `debug.log`
4. Open an issue on GitHub with:
   - WordPress version
   - PHP version
   - HubSpot tier (Starter/Professional/Enterprise)
   - Error messages from logs
