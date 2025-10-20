# Quick Setup for granttk8org.local

**Site**: https://granttk8org.local
**Admin**: todd / Glade123

---

## Step 1: Open Site Shell

In WP Engine Local:
1. Find **granttk8org** site
2. Right-click → **"Open site shell"**

---

## Step 2: Navigate to Plugins Directory

```bash
cd app/public/wp-content/plugins
```

---

## Step 3: Create Symlink to Plugin

**Windows (Run site shell as Administrator)**:
```powershell
cmd /c mklink /D hubspot-ecommerce "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce"
```

**Or Copy Files**:
```bash
cp -r "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce" .
```

---

## Step 4: Install Composer Dependencies

```bash
cd hubspot-ecommerce
composer install
```

---

## Step 5: Activate Plugin

```bash
wp plugin activate hubspot-ecommerce
```

---

## Step 6: Create Test Pages

```bash
# Shop page
wp post create --post_type=page --post_title='Shop' --post_content='[hubspot_products]' --post_status=publish --post_name=shop

# Cart page
wp post create --post_type=page --post_title='Cart' --post_content='[hubspot_cart]' --post_status=publish --post_name=cart

# Checkout page
wp post create --post_type=page --post_title='Checkout' --post_content='[hubspot_checkout]' --post_status=publish --post_name=checkout

# Account page
wp post create --post_type=page --post_title='My Account' --post_content='[hubspot_account]' --post_status=publish --post_name=my-account
```

---

## Step 7: Configure HubSpot API Key

**Option A: WordPress Admin**
1. Go to: https://granttk8org.local/wp-admin
2. Login: todd / Glade123
3. Go to: HubSpot Shop → Settings
4. Enter your HubSpot API key
5. Save

**Option B: WP-CLI**
```bash
wp option update hubspot_ecommerce_api_key "YOUR_HUBSPOT_API_KEY_HERE"
```

---

## Step 8: Seed Test Data

```bash
wp eval-file wp-content/plugins/hubspot-ecommerce/tests/setup/seed-data.php
```

---

## Step 9: Enable Tests

Uncomment webServer section in `playwright.config.js`:

```javascript
webServer: {
  command: 'echo "WP Engine Local should already be running"',
  url: 'https://granttk8org.local',
  reuseExistingServer: true,
  ignoreHTTPSErrors: true,
  timeout: 120 * 1000,
},
```

---

## Step 10: Run Tests!

```bash
cd C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce
npm test
```

---

## Quick Verification

```bash
# Check plugin is activated
wp plugin list

# Check pages created
wp post list --post_type=page

# Visit site
# https://granttk8org.local/shop
```

---

## Troubleshooting

### Symlink Permission Denied
- Run site shell as Administrator
- Or use copy method instead

### Composer Not Found
```bash
# Check if Composer is available
composer --version

# If not, download from https://getcomposer.org/
```

### Site Not Accessible
- Make sure site is "Started" in Local
- Trust SSL certificate (right-click site → SSL → Trust)

---

**Ready!** After setup, you'll have all 46 tests running!
