# Demo Mode Guide

**Purpose**: Run the plugin without HubSpot API credentials for testing/demo

---

## 🎭 What is Demo Mode?

Demo Mode uses **mock HubSpot API responses** instead of making real API calls. This allows you to:

✅ Test the plugin without HubSpot account
✅ Demo the plugin functionality
✅ Run E2E tests without API credentials
✅ Develop locally without internet
✅ Create repeatable test scenarios

---

## 🚀 Quick Start - Enable Demo Mode

### Method 1: wp-config.php (Recommended)

Add this to your `wp-config.php` file **before** `/* That's all, stop editing! */`:

```php
/**
 * Enable HubSpot Ecommerce Demo Mode
 */
define('HUBSPOT_ECOMMERCE_DEMO_MODE', true);
```

### Method 2: WP-CLI

```bash
wp option update hubspot_ecommerce_demo_mode 1
```

### Method 3: WordPress Admin

1. Go to: **HubSpot Shop → Settings**
2. Check: **"Enable Demo Mode"**
3. Save Settings

---

## 🎨 What Demo Mode Provides

### Mock Products (3 items)

1. **Test Widget**
   - Price: $10.00
   - SKU: TEST-001
   - Type: Simple product

2. **Premium Gadget**
   - Price: $25.00
   - SKU: TEST-002
   - Type: Simple product

3. **Subscription Service**
   - Price: $100.00/month
   - SKU: TEST-003
   - Type: Subscription

### Mock API Responses

- ✅ Product listings
- ✅ Contact search/create
- ✅ Deal creation
- ✅ Line item creation
- ✅ Invoice creation
- ✅ Invoice payment links
- ✅ Object associations

---

## 📋 Setup Steps (granttk8org.local)

### 1. Enable Demo Mode

**In WP Engine Local Site Shell**:

```bash
# Navigate to wp-config.php location
cd app/public

# Edit wp-config.php
# Add HUBSPOT_ECOMMERCE_DEMO_MODE constant
```

**Or using WP-CLI**:

```bash
wp option update hubspot_ecommerce_demo_mode 1
```

### 2. Activate Plugin

```bash
cd app/public/wp-content/plugins

# If not already linked
cmd /c mklink /D hubspot-ecommerce "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce"

# Navigate to plugin
cd hubspot-ecommerce

# Install dependencies (if not done)
composer install

# Activate
wp plugin activate hubspot-ecommerce
```

### 3. Create Mock Products

#### Option A: Via WordPress Admin (Recommended)

1. Go to: **Products → Add New**
2. Create products using the product editor
3. Products are created locally first (no HubSpot sync required)

#### Option B: Pull from Mock HubSpot

- Go to: **HubSpot Shop → Product Sync**
- Click: **"Pull Products from HubSpot"**
- Mock HubSpot API returns 3 sample products

#### Option C: Via WP-CLI

```bash
# Trigger product sync (will use mock data)
wp cron event run hubspot_ecommerce_sync_products
```

### 4. Create Pages

```bash
wp post create --post_type=page --post_title='Shop' --post_content='[hubspot_products]' --post_status=publish --post_name=shop

wp post create --post_type=page --post_title='Cart' --post_content='[hubspot_cart]' --post_status=publish --post_name=cart

wp post create --post_type=page --post_title='Checkout' --post_content='[hubspot_checkout]' --post_status=publish --post_name=checkout

wp post create --post_type=page --post_title='My Account' --post_content='[hubspot_account]' --post_status=publish --post_name=my-account
```

### 5. Verify Demo Mode Active

Visit: <https://granttk8org.local/wp-admin>

You should see a **yellow warning banner**:

```
🎭 DEMO MODE ACTIVE
Using mock HubSpot API responses. No real API calls are being made.
```

---

## ✅ Test the Demo

### 1. View Products

Visit: <https://granttk8org.local/shop>

You should see 3 mock products.

### 2. Add to Cart

Click any product → **Add to Cart**

### 3. Checkout Flow

1. View Cart: <https://granttk8org.local/cart>
2. Proceed to Checkout
3. Fill in test data
4. Place Order

**Mock invoice will be created** with a fake payment URL.

---

## 🧪 Run Tests with Demo Mode

### Update Test Config

Make sure `playwright.config.js` uses your site:

```javascript
baseURL: 'https://granttk8org.local',
```

### Enable webServer Check

Uncomment in `playwright.config.js`:

```javascript
webServer: {
  command: 'echo "WP Engine Local running"',
  url: 'https://granttk8org.local',
  reuseExistingServer: true,
  ignoreHTTPSErrors: true,
  timeout: 120 * 1000,
},
```

### Run All Tests

```bash
cd C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce

# Run full test suite
npm test

# Expected: 46 tests (20 without WordPress + 26 E2E tests)
```

---

## 🔍 Verify Demo Mode is Working

### Check 1: Admin Notice

Log into WordPress admin - should see yellow demo banner.

### Check 2: Product Creation

**Create a test product via admin or pull from mock HubSpot**:

```bash
# Option 1: Pull from mock HubSpot
wp cron event run hubspot_ecommerce_sync_products

# Check products
wp post list --post_type=hs_product
```

Should show mock products (either created locally or pulled from mock HubSpot).

### Check 3: Network Tab

Open browser DevTools → Network tab
Place a test order
**No actual API calls** to `api.hubapi.com` should appear.

### Check 4: Order Creation

Complete checkout - order should be created with:

- Mock deal ID
- Mock invoice ID
- Fake payment URL

---

## 🎯 Demo Mode Features

### What Works

✅ Local product creation (WordPress as source of truth)
✅ Manual sync to/from mock HubSpot (push/pull buttons)
✅ Product display with templates
✅ Add to cart
✅ Checkout flow
✅ Contact creation (mock)
✅ Deal creation (mock)
✅ Invoice creation (mock)
✅ Payment URL generation (fake)
✅ Order storage in WordPress

### What Doesn't Work

❌ Real HubSpot API calls
❌ Actual payment processing
❌ Real invoice payment
❌ Webhook verification (needs real signatures)
❌ Pro features (auto-sync, subscriptions, etc.)

### Free Tier Features in Demo

All Free tier features work in demo mode:

- Create products locally with templates (default, minimal, detailed, landing)
- Manual push to mock HubSpot
- Manual pull from mock HubSpot
- Shopping cart and checkout
- Basic order management

Pro features require a license even in demo mode, but you can test them by setting:

```php
define('HUBSPOT_LICENSE_TIER', 'pro');
```

---

## 🛠️ Troubleshooting

### Demo Banner Not Showing

```bash
# Check if demo mode enabled
wp option get hubspot_ecommerce_demo_mode

# Enable it
wp option update hubspot_ecommerce_demo_mode 1

# Or check wp-config.php for:
# define('HUBSPOT_ECOMMERCE_DEMO_MODE', true);
```

### No Mock Products After Sync

**Note:** In the current version, products are created locally first. You don't need to sync to see products.

**To create products:**

1. Go to **Products → Add New** in WordPress admin
2. Or pull from mock HubSpot: **HubSpot Shop → Product Sync → Pull Products from HubSpot**

**To reset products:**

```bash
# Delete existing products
wp post delete $(wp post list --post_type=hs_product --format=ids) --force

# Pull mock products from HubSpot
# Go to: HubSpot Shop → Product Sync → Click "Pull Products from HubSpot"
# Or via WP-CLI:
wp cron event run hubspot_ecommerce_sync_products

# Check
wp post list --post_type=hs_product
```

### Tests Failing

```bash
# Verify site is accessible
curl -k https://granttk8org.local

# Check plugin is active
wp plugin list | grep hubspot

# Verify demo mode
wp option get hubspot_ecommerce_demo_mode
```

---

## 🔄 Switch Between Demo and Real Mode

### Disable Demo Mode

**wp-config.php**:

```php
// Comment out or set to false
// define('HUBSPOT_ECOMMERCE_DEMO_MODE', true);
define('HUBSPOT_ECOMMERCE_DEMO_MODE', false);
```

**WP-CLI**:

```bash
wp option delete hubspot_ecommerce_demo_mode
```

**Admin**:

- Uncheck "Enable Demo Mode" in settings
- Add real HubSpot API key

### Enable Real Mode

1. Get HubSpot API key from: <https://app.hubspot.com/>
2. Add to WordPress:

   ```bash
   wp option update hubspot_ecommerce_api_key "YOUR_REAL_API_KEY"
   ```

3. Disable demo mode (see above)
4. Sync real products

---

## 📊 Mock Data Details

### Mock Contact IDs

Format: `mock-contact-{md5(email)}`

### Mock Deal IDs

Format: `mock-deal-{uniqid()}`

### Mock Invoice IDs

Format: `mock-invoice-{uniqid()}`

### Mock Payment URLs

Format: `https://invoice.hubspot.com/payment/mock-{uniqid()}`

---

## 🎉 Benefits

✅ **No HubSpot Account Required** - Demo without credentials
✅ **Offline Development** - Work without internet
✅ **Consistent Test Data** - Same mock products every time
✅ **Fast Testing** - No API latency
✅ **Safe Experimentation** - Can't break real HubSpot data
✅ **E2E Test Ready** - Run full test suite without setup

---

## 🚀 Ready to Demo

**Quick Command Summary**:

```bash
# Enable demo mode
wp option update hubspot_ecommerce_demo_mode 1

# Create products (choose one):
# Option 1: Create via WordPress admin (Products → Add New)
# Option 2: Pull from mock HubSpot
wp cron event run hubspot_ecommerce_sync_products

# Run tests
cd C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce
npm test
```

**Visit**: <https://granttk8org.local/shop>

**Demo away!** 🎭

## 📚 Related Documentation

- [Local-First Workflow](LOCAL_FIRST_WORKFLOW.md) - Understanding the product creation workflow
- [Product Templates](PRODUCT_TEMPLATES.md) - Using product templates (default, minimal, detailed, landing)
- [Pro Features](PRO_FEATURES.md) - License tiers and Pro feature gating
