# Testing Guide - HubSpot Ecommerce Plugin

**Last Updated**: 2025-10-19
**Testing Environment**: WP Engine Local + Playwright/Cypress
**Purpose**: End-to-end and UI testing

---

## Table of Contents

1. [WP Engine Local Setup](#wp-engine-local-setup)
2. [Plugin Installation](#plugin-installation)
3. [Test Data Setup](#test-data-setup)
4. [UI Testing Setup](#ui-testing-setup)
5. [End-to-End Test Scenarios](#end-to-end-test-scenarios)
6. [Running Tests](#running-tests)
7. [Troubleshooting](#troubleshooting)

---

## WP Engine Local Setup

### 1. Install WP Engine Local

```bash
# Download from: https://localwp.com/
# Or install via Homebrew (Mac):
brew install --cask local

# Or via Chocolatey (Windows):
choco install local
```

### 2. Create New WordPress Site

```
1. Open Local
2. Click "+" to create new site
3. Site Configuration:
   - Site name: hubspot-ecommerce-test
   - Environment: Preferred (PHP 8.0+, MySQL 8.0)
   - WordPress: Latest version
   - Admin credentials: admin / admin (test only!)

4. Click "Add Site"
```

### 3. Configure Site Settings

```
In Local:
1. Right-click site → "Open Site Shell"
2. Enable SSL:
   - Click "SSL" tab
   - Click "Trust" to add certificate
3. Start site
4. Note the site URL (usually https://hubspot-ecommerce-test.local)
```

---

## Plugin Installation

### Method 1: Symlink Plugin (Recommended for Development)

```bash
# In Local site shell:
cd app/public/wp-content/plugins

# Create symlink to your plugin directory
# Windows (as Administrator):
mklink /D hubspot-ecommerce "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce"

# Mac/Linux:
ln -s /path/to/your/hubspot-ecommerce hubspot-ecommerce

# Or copy plugin files
cp -r C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce .
```

### Method 2: Upload via WordPress Admin

```
1. Go to https://hubspot-ecommerce-test.local/wp-admin
2. Login with admin credentials
3. Navigate to Plugins → Add New → Upload Plugin
4. Upload plugin ZIP file
5. Activate plugin
```

### 3. Install Dependencies

```bash
# In plugin directory:
cd app/public/wp-content/plugins/hubspot-ecommerce

# Install Composer dependencies
composer install

# Verify installation
ls vendor/
```

### 4. Configure Plugin

```
1. Go to HubSpot Shop → Settings
2. Add HubSpot API Key (get from HubSpot test account)
3. Configure pages:
   - Shop Page: Create new page, add [hubspot_products] shortcode
   - Cart Page: Create new page, add [hubspot_cart] shortcode
   - Checkout Page: Create new page, add [hubspot_checkout] shortcode
   - Account Page: Create new page, add [hubspot_account] shortcode
4. Save settings
```

---

## Test Data Setup

### 1. Create Test Products in HubSpot

```javascript
// Use HubSpot API or create manually in HubSpot UI
// Create at least 3 test products:

Product 1:
- Name: Test Widget
- Price: $10.00
- SKU: TEST-001
- Description: Test product for automated testing

Product 2:
- Name: Premium Gadget
- Price: $25.00
- SKU: TEST-002
- Description: Premium test product

Product 3:
- Name: Subscription Service
- Price: $100.00
- SKU: TEST-003
- Recurring: Monthly
- Description: Test subscription product
```

### 2. Sync Products to WordPress

```bash
# Via WP-CLI in Local site shell:
wp cron event run hubspot_ecommerce_sync_products

# Or via WordPress admin:
# Go to HubSpot Shop → Sync Products → Click "Sync Now"
```

### 3. Create Test User Account

```bash
# In Local site shell:
wp user create testcustomer test@example.com \
  --role=customer \
  --user_pass=testpass123 \
  --first_name=Test \
  --last_name=Customer
```

### 4. Database Seed Script

Create `tests/setup/seed-data.php`:

```php
<?php
/**
 * Seed test data for testing
 */

// Create test customer
$user_id = wp_create_user('testcustomer', 'testpass123', 'test@example.com');
wp_update_user([
    'ID' => $user_id,
    'first_name' => 'Test',
    'last_name' => 'Customer',
    'role' => 'customer',
]);

// Sync products from HubSpot
$product_manager = HubSpot_Ecommerce_Product_Manager::instance();
$result = $product_manager->sync_products();

echo "Synced {$result['synced']} products\n";
echo "User created: testcustomer\n";
```

Run with:
```bash
wp eval-file tests/setup/seed-data.php
```

---

## UI Testing Setup

### Option 1: Playwright (Recommended)

#### Install Playwright

```bash
# In project root (not plugin directory):
cd C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce

# Initialize npm project if needed
npm init -y

# Install Playwright
npm install -D @playwright/test
npx playwright install

# Install Playwright browsers
npx playwright install chromium firefox webkit
```

#### Create Playwright Config

Create `playwright.config.js`:

```javascript
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false, // Run tests sequentially for ecommerce
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1, // One worker for sequential execution
  reporter: 'html',

  use: {
    baseURL: 'https://hubspot-ecommerce-test.local',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',

    // Accept self-signed SSL certificates (for Local)
    ignoreHTTPSErrors: true,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] },
    },
  ],

  webServer: {
    command: 'echo "WP Engine Local should already be running"',
    url: 'https://hubspot-ecommerce-test.local',
    reuseExistingServer: true,
    ignoreHTTPSErrors: true,
  },
});
```

---

### Option 2: Cypress

#### Install Cypress

```bash
# In project root:
npm install -D cypress

# Open Cypress to initialize
npx cypress open
```

#### Create Cypress Config

Create `cypress.config.js`:

```javascript
const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'https://hubspot-ecommerce-test.local',
    viewportWidth: 1280,
    viewportHeight: 720,

    // For self-signed certificates
    chromeWebSecurity: false,

    setupNodeEvents(on, config) {
      // implement node event listeners here
    },

    env: {
      adminUser: 'admin',
      adminPassword: 'admin',
      testUser: 'testcustomer',
      testPassword: 'testpass123',
    },
  },
});
```

---

## End-to-End Test Scenarios

### Test 1: Complete Checkout Flow (Playwright)

Create `tests/e2e/checkout-flow.spec.js`:

```javascript
import { test, expect } from '@playwright/test';

test.describe('Checkout Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Clear cookies and storage
    await page.context().clearCookies();
    await page.goto('/shop');
  });

  test('complete checkout with guest user', async ({ page }) => {
    // Step 1: Add product to cart
    await page.click('text=Test Widget');
    await page.waitForSelector('.single-product');
    await page.click('button:has-text("Add to Cart")');

    // Verify cart count updated
    await expect(page.locator('.cart-count')).toContainText('1');

    // Step 2: View cart
    await page.click('a:has-text("Cart")');
    await expect(page).toHaveURL(/\/cart/);
    await expect(page.locator('.cart-item')).toBeVisible();

    // Verify product in cart
    await expect(page.locator('.cart-item .product-name'))
      .toContainText('Test Widget');
    await expect(page.locator('.cart-total'))
      .toContainText('$10.00');

    // Step 3: Proceed to checkout
    await page.click('button:has-text("Proceed to Checkout")');
    await expect(page).toHaveURL(/\/checkout/);

    // Step 4: Fill checkout form
    await page.fill('input[name="first_name"]', 'Test');
    await page.fill('input[name="last_name"]', 'Customer');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="phone"]', '555-0100');
    await page.fill('input[name="address"]', '123 Test Street');
    await page.fill('input[name="city"]', 'Test City');
    await page.fill('input[name="state"]', 'CA');
    await page.fill('input[name="zip"]', '90210');
    await page.fill('input[name="country"]', 'US');

    // Step 5: Submit order
    await page.click('button:has-text("Place Order")');

    // Step 6: Wait for response and verify redirect
    await page.waitForResponse(response =>
      response.url().includes('/wp-admin/admin-ajax.php') &&
      response.request().postData()?.includes('hs_process_checkout')
    );

    // Should redirect to payment URL or confirmation
    await page.waitForURL(/order-confirmation|hubspot\.com/, { timeout: 10000 });

    // Verify success message or payment page
    const url = page.url();
    if (url.includes('order-confirmation')) {
      await expect(page.locator('.order-confirmation'))
        .toContainText('Order placed successfully');
    } else if (url.includes('hubspot.com')) {
      // On HubSpot payment page
      await expect(page.locator('body')).toContainText('Payment');
    }
  });

  test('add multiple products and update quantities', async ({ page }) => {
    // Add first product
    await page.click('text=Test Widget');
    await page.click('button:has-text("Add to Cart")');
    await page.goto('/shop');

    // Add second product
    await page.click('text=Premium Gadget');
    await page.click('button:has-text("Add to Cart")');

    // Go to cart
    await page.click('a:has-text("Cart")');

    // Verify 2 products in cart
    const cartItems = await page.locator('.cart-item').count();
    expect(cartItems).toBe(2);

    // Update quantity
    await page.locator('.cart-item').first().locator('input[type="number"]').fill('3');
    await page.locator('.cart-item').first().locator('button:has-text("Update")').click();

    // Verify total updated
    await expect(page.locator('.cart-total')).toContainText('$55.00'); // 3*$10 + 1*$25
  });

  test('remove product from cart', async ({ page }) => {
    // Add product
    await page.click('text=Test Widget');
    await page.click('button:has-text("Add to Cart")');

    // Go to cart
    await page.click('a:has-text("Cart")');

    // Remove product
    await page.click('.cart-item button:has-text("Remove")');

    // Verify cart empty
    await expect(page.locator('.cart-empty')).toBeVisible();
    await expect(page.locator('.cart-empty')).toContainText('Your cart is empty');
  });

  test('validates required checkout fields', async ({ page }) => {
    // Add product to cart
    await page.click('text=Test Widget');
    await page.click('button:has-text("Add to Cart")');
    await page.click('a:has-text("Cart")');
    await page.click('button:has-text("Proceed to Checkout")');

    // Try to submit without filling fields
    await page.click('button:has-text("Place Order")');

    // Should show validation errors
    await expect(page.locator('.error-message, .woocommerce-error, .alert-danger'))
      .toBeVisible();
  });
});
```

### Test 2: Product Browsing and Search

Create `tests/e2e/product-browsing.spec.js`:

```javascript
import { test, expect } from '@playwright/test';

test.describe('Product Browsing', () => {
  test('displays product archive', async ({ page }) => {
    await page.goto('/shop');

    // Verify products are displayed
    const products = await page.locator('.product-item, .product').count();
    expect(products).toBeGreaterThan(0);

    // Verify product has required elements
    await expect(page.locator('.product').first()).toBeVisible();
    await expect(page.locator('.product .product-title').first()).toBeVisible();
    await expect(page.locator('.product .product-price').first()).toBeVisible();
  });

  test('views single product page', async ({ page }) => {
    await page.goto('/shop');

    // Click first product
    await page.click('.product .product-title');

    // Verify single product page
    await expect(page.locator('.single-product')).toBeVisible();
    await expect(page.locator('.product-title')).toBeVisible();
    await expect(page.locator('.product-price')).toBeVisible();
    await expect(page.locator('.product-description')).toBeVisible();
    await expect(page.locator('button:has-text("Add to Cart")')).toBeVisible();
  });
});
```

### Test 3: User Account Management

Create `tests/e2e/user-account.spec.js`:

```javascript
import { test, expect } from '@playwright/test';

test.describe('User Account', () => {
  test('user can login and view orders', async ({ page }) => {
    // Go to account page
    await page.goto('/my-account');

    // Should redirect to login or show login form
    await expect(page.locator('input[name="log"]')).toBeVisible();

    // Login
    await page.fill('input[name="log"]', 'testcustomer');
    await page.fill('input[name="pwd"]', 'testpass123');
    await page.click('button:has-text("Log In"), input[type="submit"][value*="Log"]');

    // Should see dashboard
    await expect(page.locator('.account-dashboard, .woocommerce-MyAccount-content'))
      .toBeVisible();

    // Click orders
    await page.click('a:has-text("Orders")');

    // Should see orders list (may be empty)
    await expect(page.locator('.orders-list, .woocommerce-orders-table'))
      .toBeVisible();
  });
});
```

### Test 4: Admin Product Sync

Create `tests/e2e/admin-product-sync.spec.js`:

```javascript
import { test, expect } from '@playwright/test';

test.describe('Admin Product Sync', () => {
  test.use({ storageState: 'tests/auth/admin-state.json' });

  test('admin can sync products from HubSpot', async ({ page }) => {
    // Login as admin first (or use saved auth state)
    await page.goto('/wp-admin');

    if (await page.locator('input[name="log"]').isVisible()) {
      await page.fill('input[name="log"]', 'admin');
      await page.fill('input[name="pwd"]', 'admin');
      await page.click('input[type="submit"]');
    }

    // Navigate to sync page
    await page.click('text=HubSpot Shop');
    await page.click('text=Sync Products');

    // Click sync button
    await page.click('button:has-text("Sync Products Now"), button:has-text("Sync Now")');

    // Wait for sync to complete
    await expect(page.locator('.sync-success, .notice-success'))
      .toBeVisible({ timeout: 30000 });

    // Verify success message
    await expect(page.locator('.sync-success, .notice-success'))
      .toContainText(/synced|success/i);
  });
});
```

---

## Running Tests

### Playwright Commands

```bash
# Run all tests
npx playwright test

# Run tests in headed mode (see browser)
npx playwright test --headed

# Run specific test file
npx playwright test tests/e2e/checkout-flow.spec.js

# Run tests in debug mode
npx playwright test --debug

# Run tests and open HTML report
npx playwright test
npx playwright show-report

# Run tests in specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox

# Run tests with UI mode (interactive)
npx playwright test --ui

# Generate test code (record actions)
npx playwright codegen https://hubspot-ecommerce-test.local
```

### Cypress Commands

```bash
# Open Cypress Test Runner (interactive)
npx cypress open

# Run Cypress tests headlessly
npx cypress run

# Run specific test file
npx cypress run --spec "cypress/e2e/checkout-flow.cy.js"

# Run in specific browser
npx cypress run --browser chrome
npx cypress run --browser firefox
```

---

## Helper Utilities

### Create `tests/helpers/test-helpers.js`:

```javascript
import { expect } from '@playwright/test';

export class TestHelpers {
  constructor(page) {
    this.page = page;
  }

  async loginAsAdmin() {
    await this.page.goto('/wp-admin');
    await this.page.fill('input[name="log"]', 'admin');
    await this.page.fill('input[name="pwd"]', 'admin');
    await this.page.click('input[type="submit"]');
    await this.page.waitForURL('/wp-admin/**');
  }

  async loginAsCustomer() {
    await this.page.goto('/my-account');
    await this.page.fill('input[name="log"]', 'testcustomer');
    await this.page.fill('input[name="pwd"]', 'testpass123');
    await this.page.click('button:has-text("Log In")');
  }

  async addProductToCart(productName) {
    await this.page.goto('/shop');
    await this.page.click(`text=${productName}`);
    await this.page.click('button:has-text("Add to Cart")');
    await this.page.waitForSelector('.cart-count');
  }

  async clearCart() {
    await this.page.goto('/cart');
    const removeButtons = await this.page.locator('button:has-text("Remove")').count();
    for (let i = 0; i < removeButtons; i++) {
      await this.page.locator('button:has-text("Remove")').first().click();
      await this.page.waitForTimeout(500);
    }
  }

  async fillCheckoutForm(data = {}) {
    const defaults = {
      first_name: 'Test',
      last_name: 'Customer',
      email: 'test@example.com',
      phone: '555-0100',
      address: '123 Test Street',
      city: 'Test City',
      state: 'CA',
      zip: '90210',
      country: 'US',
    };

    const formData = { ...defaults, ...data };

    for (const [field, value] of Object.entries(formData)) {
      await this.page.fill(`input[name="${field}"]`, value);
    }
  }

  async waitForAjax() {
    await this.page.waitForLoadState('networkidle');
  }
}
```

### Usage in Tests:

```javascript
import { test, expect } from '@playwright/test';
import { TestHelpers } from '../helpers/test-helpers';

test('use test helpers', async ({ page }) => {
  const helpers = new TestHelpers(page);

  await helpers.addProductToCart('Test Widget');
  await helpers.fillCheckoutForm({ email: 'custom@example.com' });
  await page.click('button:has-text("Place Order")');
});
```

---

## Troubleshooting

### SSL Certificate Issues

```bash
# Playwright:
# Already configured with ignoreHTTPSErrors: true in config

# Cypress:
# Already configured with chromeWebSecurity: false in config

# If still having issues, add to hosts file:
# Windows: C:\Windows\System32\drivers\etc\hosts
# Mac/Linux: /etc/hosts
127.0.0.1 hubspot-ecommerce-test.local
```

### WP Engine Local Not Starting

```
1. Check Local logs (Help → View Logs)
2. Restart Local application
3. Restart site in Local
4. Check disk space
5. Check port conflicts (80, 443, 3306)
```

### Tests Timing Out

```javascript
// Increase timeout for specific test
test('slow test', async ({ page }) => {
  test.setTimeout(60000); // 60 seconds
  // ... test code
});

// Or in config:
export default defineConfig({
  timeout: 30000, // 30 seconds default
});
```

### Database State Issues

```bash
# Reset database to clean state
wp db reset --yes

# Re-import baseline
wp db import tests/fixtures/baseline.sql

# Or use WP-CLI to manage
wp db export backup.sql
wp db import backup.sql
```

---

## Continuous Integration

### GitHub Actions Workflow

Create `.github/workflows/e2e-tests.yml`:

```yaml
name: E2E Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    timeout-minutes: 60
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3

    - uses: actions/setup-node@v3
      with:
        node-version: 18

    - name: Install dependencies
      run: npm ci

    - name: Install Playwright Browsers
      run: npx playwright install --with-deps

    - name: Run Playwright tests
      run: npx playwright test

    - uses: actions/upload-artifact@v3
      if: always()
      with:
        name: playwright-report
        path: playwright-report/
        retention-days: 30
```

---

## Next Steps

1. ✅ Set up WP Engine Local environment
2. ✅ Install plugin in Local
3. ✅ Configure HubSpot test account
4. ✅ Run product sync
5. ✅ Install Playwright/Cypress
6. ✅ Run first test
7. 📝 Expand test coverage
8. 🔄 Integrate with CI/CD

---

**Ready to test!** Start with the checkout flow test and expand from there.
