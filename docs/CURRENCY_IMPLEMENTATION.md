# HubSpot Currency Integration Implementation

## Overview

This document describes the comprehensive currency support implementation that integrates with HubSpot's Currency API to automatically sync and format currencies based on your HubSpot account settings.

## Features Implemented

### 1. HubSpot Currency API Integration

**New API Methods** ([class-hubspot-api.php](../wordpress/includes/class-hubspot-api.php)):
- `get_company_currency()` - Retrieves the default company currency from HubSpot
- `get_account_currencies()` - Gets all enabled currencies with exchange rates
- `get_supported_currency_codes()` - Fetches list of all HubSpot supported currencies

**Required HubSpot API Scopes:**
- `multi-currency-read` - To retrieve currency data
- `multi-currency-write` - To edit currency data (future feature)

### 2. Currency Manager Class

**New File:** [class-currency-manager.php](../wordpress/includes/class-currency-manager.php)

**Key Features:**
- Automatic daily sync of currencies from HubSpot account
- Comprehensive currency formatting data for 35+ currencies
- Proper decimal places per currency (JPY=0, most=2)
- Symbol positioning (before/after price)
- Thousand separators and decimal separators per currency

**Supported Currencies:**
USD, EUR, GBP, JPY, AUD, CAD, CHF, CNY, SEK, NZD, MXN, SGD, HKD, NOK, KRW, TRY, RUB, INR, BRL, ZAR, DKK, PLN, THB, IDR, HUF, CZK, ILS, CLP, PHP, AED, COP, SAR, MYR, RON

**Public Methods:**
```php
// Sync currencies from HubSpot
$currency_manager->sync_currencies();

// Get enabled currencies
$enabled = $currency_manager->get_enabled_currencies();

// Format price with proper rules
$formatted = $currency_manager->format_price(99.99, 'EUR'); // €99,99

// Get currency symbol
$symbol = $currency_manager->get_currency_symbol('GBP'); // £
```

### 3. Enhanced Settings Page

**Updates to** [class-settings.php](../wordpress/includes/admin/class-settings.php):

- Currency dropdown now populated from HubSpot account
- "Sync Currencies from HubSpot" button for manual sync
- Display of last sync timestamp and company currency
- Shows count of enabled currencies
- Added missing `hubspot_ecommerce_cart_icon` to save settings (bug fix)

**User Experience:**
1. Navigate to **HubSpot Shop → Settings**
2. Click "Sync Currencies from HubSpot" to fetch available currencies
3. Select default currency from dropdown (shows enabled currencies only)
4. Currencies automatically sync daily via WP-Cron

### 4. Product Manager Integration

**Updates to** [class-product-manager.php](../wordpress/includes/class-product-manager.php):

- `format_price()` now uses Currency Manager for proper formatting
- Supports optional currency code parameter for multi-currency display
- Automatically uses site default currency if none specified

**Example:**
```php
$product_manager = HubSpot_Ecommerce_Product_Manager::instance();

// Format in default currency
echo $product_manager->format_price(1234.56); // $1,234.56

// Format in specific currency
echo $product_manager->format_price(1234.56, 'EUR'); // €1.234,56
echo $product_manager->format_price(1000, 'JPY'); // ¥1000 (no decimals)
```

### 5. Frontend JavaScript Updates

**Updates to** [frontend.js](../wordpress/assets/js/frontend.js):

- Enhanced `formatPrice()` function with 35+ currencies
- Proper decimal handling (0 for JPY/KRW/IDR/HUF/CLP/COP)
- Symbol positioning (before/after)
- Consistent with PHP backend formatting

**Currencies with Special Formatting:**
- **Zero decimals:** JPY, KRW, IDR, HUF, CLP, COP
- **Symbol after price:** SEK, NOK, DKK, PLN, RUB, CZK, RON

## How Currency Sync Works

### Automatic Sync (Daily)

```php
// Scheduled via WP-Cron
wp_schedule_event(time(), 'daily', 'hubspot_ecommerce_sync_currencies');
```

**Process:**
1. Fetches company currency from `/settings/v3/currencies/company-currency`
2. Fetches all enabled currencies from `/settings/v3/currencies/exchange-rates/current`
3. Stores results in WordPress options
4. Updates default currency to match HubSpot company currency
5. Logs sync results with timestamp

### Manual Sync

Navigate to: **HubSpot Shop → Settings** and click "Sync Currencies from HubSpot"

**Success Message:**
```
Successfully synced 5 currencies from HubSpot. Company currency: USD
```

**Error Handling:**
- Displays specific error messages if API calls fail
- Falls back to default currencies (USD, EUR, GBP, JPY) if sync fails
- Logs errors for debugging

## Currency Formatting Rules

### Decimal Places

| Currency | Decimals | Reason |
|----------|----------|--------|
| USD, EUR, GBP | 2 | Standard |
| JPY, KRW | 0 | No subunits in circulation |
| IDR, HUF | 0 | Inflation-related |
| CLP, COP | 0 | Historical reasons |

### Symbol Positioning

**Before Price:**
- USD, EUR, GBP, JPY, CAD, AUD, CHF, CNY, NZD, MXN, SGD, HKD, KRW, TRY, INR, BRL, ZAR, THB, IDR, ILS, CLP, PHP, AED, COP, SAR, MYR

**After Price:**
- SEK, NOK, DKK, PLN, RUB, HUF, CZK, RON

**Examples:**
```
USD: $1,234.56
EUR: €1.234,56
JPY: ¥1235 (no decimals)
SEK: 1 234,56 kr (symbol after, space separator)
```

## Database Schema

### WordPress Options

| Option Key | Description | Example Value |
|-----------|-------------|---------------|
| `hubspot_ecommerce_currency` | Default currency code | `"USD"` |
| `hubspot_ecommerce_enabled_currencies` | Array of enabled currencies | `[{code: 'USD', rate: 1, visible: true}, ...]` |
| `hubspot_ecommerce_currency_sync` | Last sync metadata | `{timestamp: '2025-11-11 12:00:00', company_currency: 'USD', enabled_count: 5, errors: []}` |

## Multi-Currency Product Pricing ✅ IMPLEMENTED

### How It Works

The plugin now automatically syncs currency-specific prices from HubSpot and displays the correct price based on the site's currency setting.

**HubSpot Price Fields:**
- `price` - Default price (company currency)
- `hs_price_usd` - USD specific price
- `hs_price_eur` - EUR specific price
- `hs_price_gbp` - GBP specific price
- Pattern: `hs_price_{currency_code}` (lowercase)

**WordPress Storage:**
- `_price` - Default price (meta field)
- `_price_usd` - USD specific price (meta field)
- `_price_eur` - EUR specific price (meta field)
- Pattern: `_price_{currency_code}` (lowercase)

### Implementation Details

**1. Product Sync ([class-hubspot-api.php](../wordpress/includes/class-hubspot-api.php) lines 315-321):**
```php
// Automatically requests currency-specific price fields
$currency_manager = HubSpot_Ecommerce_Currency_Manager::instance();
$enabled_currencies = $currency_manager->get_enabled_currencies();
foreach ($enabled_currencies as $currency) {
    $code = strtolower($currency['code']);
    $properties[] = "hs_price_{$code}";
}
```

**2. Storing Prices ([class-product-manager.php](../wordpress/includes/class-product-manager.php) lines 138-151):**
```php
// Save currency-specific prices from HubSpot
$enabled_currencies = $this->currency_manager->get_enabled_currencies();
foreach ($enabled_currencies as $currency) {
    $code = strtolower($currency['code']);
    $price_field = "hs_price_{$code}";

    if (isset($properties[$price_field]) && !empty($properties[$price_field])) {
        update_post_meta($post_id, "_price_{$code}", floatval($properties[$price_field]));
    } else {
        delete_post_meta($post_id, "_price_{$code}");
    }
}
```

**3. Retrieving Prices ([class-product-manager.php](../wordpress/includes/class-product-manager.php) lines 342-407):**

New methods:
- `get_product_price($post_id, $currency_code = null)` - Gets price in specific currency
- `get_product_prices_all_currencies($post_id)` - Gets all available prices
- `has_currency_price($post_id, $currency_code)` - Checks if currency price exists

**Example Usage:**
```php
$product_manager = HubSpot_Ecommerce_Product_Manager::instance();

// Get price in site default currency (automatically uses currency-specific price if available)
$price = $product_manager->get_product_price($product_id);

// Get price in specific currency
$usd_price = $product_manager->get_product_price($product_id, 'USD');
$eur_price = $product_manager->get_product_price($product_id, 'EUR');

// Get all prices
$all_prices = $product_manager->get_product_prices_all_currencies($product_id);
// Returns: ['USD' => 99.99, 'EUR' => 89.99, 'GBP' => 79.99]

// Check if product has EUR price
if ($product_manager->has_currency_price($product_id, 'EUR')) {
    echo "EUR price available!";
}
```

### Fallback Behavior

If a currency-specific price is not set in HubSpot:
1. The plugin checks for `_price_{currency_code}` meta field
2. If not found, falls back to default `_price` field
3. Formats using the requested currency symbol

**Example:**
- Product has: `price: $100`, `hs_price_eur: €85`
- Site currency set to EUR → Displays €85 (currency-specific)
- Site currency set to GBP → Displays £100 (converted from default, formatted as GBP)

### Automatic Integration

All existing code automatically uses currency-specific prices:
- ✅ Product templates (single, archive, shop)
- ✅ Cart system
- ✅ Checkout process
- ✅ Order totals
- ✅ Customer dashboard
- ✅ Admin product listing

No template changes required! The `get_product_price()` method is smart enough to:
1. Use site default currency if no parameter provided
2. Look for currency-specific price first
3. Fall back to default price if currency-specific not available

## Future Enhancements

### Phase 2: Customer Currency Selection (Pending)

Allow customers to view prices in their preferred currency:
- Currency switcher widget
- Store preference in session/cookie
- Display prices in selected currency
- Use exchange rates from HubSpot

### Phase 3: Currency Conversion

- Automatic price conversion using HubSpot exchange rates
- Manual override for specific products
- Historical exchange rate tracking

## API Endpoints

### HubSpot Currency API

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/settings/v3/currencies/company-currency` | GET | Get default currency |
| `/settings/v3/currencies/exchange-rates/current` | GET | Get all enabled currencies |
| `/settings/v3/currencies/codes` | GET | Get supported currency codes |

### WordPress AJAX (Future)

Potential endpoints for frontend currency switching:
- `wp_ajax_hs_get_currencies` - Get available currencies
- `wp_ajax_hs_convert_price` - Convert price between currencies

## Testing

### Manual Testing Steps

1. **Sync Currencies:**
   - Go to HubSpot Shop → Settings
   - Click "Sync Currencies from HubSpot"
   - Verify success message appears
   - Check that currencies match HubSpot account

2. **Currency Formatting:**
   - View product on frontend
   - Verify price displays with correct symbol
   - Check decimal places (e.g., JPY should have 0)
   - Verify symbol position (e.g., SEK should be after)

3. **Settings Page:**
   - Verify currency dropdown shows enabled currencies
   - Verify last sync timestamp displays
   - Change currency and save
   - Verify prices update on frontend

4. **JavaScript Formatting:**
   - Add product to cart
   - Verify cart total displays correctly
   - Check mini cart / floating cart widget
   - Test with different currencies

### Error Scenarios

1. **API Connection Failure:**
   - Disconnect from HubSpot
   - Try to sync currencies
   - Verify error message displays
   - Verify fallback to default currencies

2. **Invalid Currency Code:**
   - Manually set invalid currency in database
   - Verify graceful fallback (displays code + space)

## Code References

- **Currency Manager:** [class-currency-manager.php](../wordpress/includes/class-currency-manager.php)
- **HubSpot API:** [class-hubspot-api.php](../wordpress/includes/class-hubspot-api.php) (lines 333-361)
- **Settings Page:** [class-settings.php](../wordpress/includes/admin/class-settings.php) (lines 96-145)
- **Product Manager:** [class-product-manager.php](../wordpress/includes/class-product-manager.php) (lines 348-357)
- **Frontend JS:** [frontend.js](../wordpress/assets/js/frontend.js) (lines 316-365)
- **Main Plugin:** [hubspot-ecommerce.php](../wordpress/hubspot-ecommerce.php) (line 87, 117)

## Troubleshooting

### Currencies Not Syncing

1. Check API connection in HubSpot Shop → Settings
2. Verify HubSpot account has multi-currency enabled
3. Check error log: `get_option('hubspot_ecommerce_currency_sync')['errors']`
4. Ensure API token has `multi-currency-read` scope

### Incorrect Formatting

1. Verify currency code is correct (3-letter ISO 4217)
2. Check `hubspot_ecommerce_currency` option value
3. Clear browser cache for JavaScript changes
4. Verify WP-Cron is running for daily syncs

### Missing Currencies

1. Enable currencies in HubSpot account first
2. Click "Sync Currencies from HubSpot" button
3. Check that currencies have `visibleInUI: true` in HubSpot
4. Verify no API errors in sync log

## Compliance

- **ISO 4217:** All currency codes follow ISO 4217 standard
- **Localization:** Currency names use WordPress `__()` for translation
- **Accessibility:** Currency symbols have proper UTF-8 encoding
- **Security:** All currency inputs sanitized with `sanitize_text_field()`

## Performance

- **Caching:** Enabled currencies cached in WordPress options
- **Daily Sync:** Minimal API calls (once per day via WP-Cron)
- **No External APIs:** No third-party currency conversion APIs used
- **Lightweight:** Currency data stored in PHP arrays, no database queries

## Support

For issues or questions:
1. Check HubSpot API status
2. Review error logs in Settings page
3. Verify multi-currency is enabled in HubSpot account
4. Check WP-Cron is executing scheduled tasks
