# WP Engine Local Setup Guide - Step by Step

**Goal**: Get WordPress running locally so we can run the full test suite

---

## Step 1: Download WP Engine Local

### Option A: Direct Download
1. Go to: https://localwp.com/
2. Click "Download"
3. Choose your OS (Windows)
4. Enter email (optional, can skip)
5. Download installer

### Option B: Chocolatey (Windows)
```powershell
# Run as Administrator
choco install local
```

### Option C: Already Installed?
```powershell
# Check if it's already installed
Get-Command local
# or
where.exe local
```

---

## Step 2: Install WP Engine Local

1. **Run the installer** (`local-X.X.X-windows.exe`)
2. **Accept defaults** (install location, shortcuts)
3. **Wait for installation** (2-5 minutes)
4. **Launch Local** from Start Menu or Desktop

---

## Step 3: Create WordPress Site

### In Local Application:

1. **Click the big "+" button** (bottom left)
2. **Create a new site**

### Site Configuration:

```
Site Name: hubspot-ecommerce-test
✓ Continue

Environment:
○ Preferred (Recommended)
  PHP: 8.0+
  Web Server: Nginx
  Database: MySQL 8.0
✓ Continue

WordPress Setup:
  Username: admin
  Password: admin
  Email: admin@local.test
✓ Add Site
```

3. **Wait for site creation** (3-5 minutes)
   - Creating database
   - Downloading WordPress
   - Installing WordPress
   - Setting up site

---

## Step 4: Trust SSL Certificate

**IMPORTANT**: Required for HTTPS in tests

1. **Right-click the site** in Local sidebar
2. **Click "Trust"** (under SSL section)
3. **Confirm** administrator prompt
4. **Restart browser** after trusting

---

## Step 5: Start Site

1. **Click the site** in Local sidebar
2. **Click "Start site"** (if not already started)
3. **Wait for** green "Running" status
4. **Note the URL**: `https://hubspot-ecommerce-test.local`

---

## Step 6: Access WordPress

### WordPress Admin
```
URL: https://hubspot-ecommerce-test.local/wp-admin
Username: admin
Password: admin
```

### Site Frontend
```
URL: https://hubspot-ecommerce-test.local
```

### Database
```
Host: localhost
Port: (shown in Local)
Database: local
Username: root
Password: root
```

---

## Step 7: Open Site Shell

**In Local**:
1. Right-click site → **"Open site shell"**
2. This opens a terminal with WP-CLI available

**Test WP-CLI**:
```bash
wp --version
# Should show: WP-CLI 2.x.x
```

---

## Step 8: Install Plugin

### Option A: Symlink (Recommended for Development)

**In Site Shell (as Administrator on Windows)**:
```powershell
cd app\public\wp-content\plugins

# Create symbolic link
cmd /c mklink /D hubspot-ecommerce "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce"

# Verify
ls hubspot-ecommerce
```

### Option B: Copy Files

**In Site Shell**:
```bash
cd app/public/wp-content/plugins
cp -r "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce" .
```

---

## Step 9: Install Plugin Dependencies

**In Site Shell**:
```bash
cd app/public/wp-content/plugins/hubspot-ecommerce

# Install Composer dependencies
composer install

# Verify
ls vendor/
```

---

## Step 10: Activate Plugin

### Option A: WP-CLI
```bash
wp plugin activate hubspot-ecommerce
```

### Option B: WordPress Admin
1. Go to: https://hubspot-ecommerce-test.local/wp-admin/plugins.php
2. Find "HubSpot Ecommerce"
3. Click "Activate"

---

## Step 11: Configure HubSpot API Key

### Get Test API Key from HubSpot

1. Log into HubSpot test account
2. Go to: **Settings → Integrations → Private Apps**
3. Click **"Create private app"**
4. Configure:
   ```
   Name: WP Test App
   Description: For WordPress plugin testing

   Scopes:
   ✓ crm.objects.products.read
   ✓ crm.objects.products.write
   ✓ crm.objects.contacts.read
   ✓ crm.objects.contacts.write
   ✓ crm.objects.deals.read
   ✓ crm.objects.deals.write
   ✓ crm.objects.invoices.read
   ✓ crm.objects.invoices.write
   ✓ crm.objects.line_items.write
   ✓ crm.objects.commerce_payments.read
   ```
5. Click **"Create app"**
6. **Copy the access token**

### Add to WordPress

**Option A: WordPress Admin**
1. Go to: **HubSpot Shop → Settings**
2. Paste API key
3. Click **"Save"**

**Option B: WP-CLI**
```bash
wp option update hubspot_ecommerce_api_key "your-api-key-here"
```

---

## Step 12: Create Test Pages

**In Site Shell**:
```bash
# Create Shop page
wp post create --post_type=page --post_title='Shop' --post_content='[hubspot_products]' --post_status=publish --post_name=shop

# Create Cart page
wp post create --post_type=page --post_title='Cart' --post_content='[hubspot_cart]' --post_status=publish --post_name=cart

# Create Checkout page
wp post create --post_type=page --post_title='Checkout' --post_content='[hubspot_checkout]' --post_status=publish --post_name=checkout

# Create Account page
wp post create --post_type=page --post_title='My Account' --post_content='[hubspot_account]' --post_status=publish --post_name=my-account
```

---

## Step 13: Seed Test Data

**In Site Shell**:
```bash
wp eval-file wp-content/plugins/hubspot-ecommerce/tests/setup/seed-data.php
```

**This creates**:
- ✅ Test customer (testcustomer / testpass123)
- ✅ Syncs products from HubSpot
- ✅ Configures plugin pages

---

## Step 14: Verify Setup

### Check Plugin Status
```bash
wp plugin list
# Should show hubspot-ecommerce as "active"
```

### Check Pages
```bash
wp post list --post_type=page
# Should show: Shop, Cart, Checkout, My Account
```

### Visit Site
1. Open browser: https://hubspot-ecommerce-test.local
2. Should see WordPress site
3. Go to: https://hubspot-ecommerce-test.local/shop
4. Should see products (if synced)

---

## Step 15: Enable Playwright Tests

**Update playwright.config.js**:
```javascript
// Uncomment the webServer section:
webServer: {
  command: 'echo "WP Engine Local should already be running"',
  url: 'https://hubspot-ecommerce-test.local',
  reuseExistingServer: true,
  ignoreHTTPSErrors: true,
  timeout: 120 * 1000,
},
```

---

## Step 16: Run Tests!

**In plugin directory**:
```bash
cd C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce

# Run all tests
npm test

# Run specific suite
npx playwright test checkout-flow
npx playwright test product-browsing
npx playwright test security

# Run with visible browser
npm run test:headed

# Debug mode
npm run test:debug
```

---

## Troubleshooting

### Site Won't Start
```
1. Check Local app is running
2. Check port conflicts (80, 443)
3. Restart Local app
4. Delete site and recreate
```

### SSL Certificate Issues
```
1. Trust certificate in Local (right-click site → Trust)
2. Restart browser
3. Visit site in browser and accept certificate
4. Tests have ignoreHTTPSErrors: true
```

### Plugin Not Showing
```bash
# Check plugin directory exists
ls app/public/wp-content/plugins/hubspot-ecommerce

# Check permissions
# Symlink might need Administrator rights on Windows
```

### Composer Install Fails
```bash
# Make sure Composer is installed
composer --version

# Update Composer
composer self-update

# Try with verbose flag
composer install -vvv
```

### WP-CLI Not Found
```bash
# In Local site shell, WP-CLI should be available
wp --version

# If not, check Local installation
# Or install WP-CLI separately
```

### Products Not Syncing
```bash
# Check API key is set
wp option get hubspot_ecommerce_api_key

# Test connection
wp eval 'echo HubSpot_Ecommerce_API::instance()->test_connection() ? "OK" : "FAIL";'

# Manually trigger sync
wp cron event run hubspot_ecommerce_sync_products
```

---

## Quick Commands Reference

```bash
# Start/Stop Site
Right-click site in Local → Start/Stop

# Open Site Shell
Right-click site in Local → Open site shell

# View Site Logs
Right-click site in Local → Reveal in Finder/Explorer
→ Go to /app/public/wp-content/debug.log

# Database Access
Click site → Database tab → "Open Adminer"

# Reset Everything
wp db reset --yes
wp eval-file wp-content/plugins/hubspot-ecommerce/tests/setup/seed-data.php
```

---

## Automated Setup Script

**Create: `setup-local.ps1`** (PowerShell)

```powershell
# WP Engine Local Setup Script
Write-Host "🚀 Setting up WP Local environment..."

# Check if Local is installed
if (!(Get-Command "local" -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Local is not installed. Please install from https://localwp.com/"
    exit 1
}

Write-Host "✅ Local is installed"

# Instructions
Write-Host @"

📋 Next Steps:
1. Open Local application
2. Create site 'hubspot-ecommerce-test'
3. Trust SSL certificate
4. Run in site shell:
   cd app/public/wp-content/plugins
   mklink /D hubspot-ecommerce "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce"
   cd hubspot-ecommerce
   composer install
   wp plugin activate hubspot-ecommerce
   wp eval-file tests/setup/seed-data.php

🎯 Then run tests:
   npm test
"@
```

---

## Success Checklist

- [ ] Local installed and running
- [ ] Site created (hubspot-ecommerce-test)
- [ ] SSL certificate trusted
- [ ] Site started and accessible
- [ ] Plugin symlinked/copied
- [ ] Composer dependencies installed
- [ ] Plugin activated
- [ ] HubSpot API key configured
- [ ] Test pages created
- [ ] Test data seeded
- [ ] Can visit https://hubspot-ecommerce-test.local/shop
- [ ] Tests run successfully with `npm test`

---

**Time Estimate**: 15-30 minutes for first-time setup

**Next**: Once setup is complete, run `npm test` to execute the full test suite!
