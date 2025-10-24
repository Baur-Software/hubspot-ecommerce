# HubSpot Ecommerce Plugin - Testing

Complete guide for setting up and running end-to-end tests with WP Engine Local and Playwright.

---

## Quick Start

```bash
# 1. Install dependencies
npm install

# 2. Install Playwright browsers
npx playwright install

# 3. Run tests
npm test
```

---

## Prerequisites

### Required Software

1. **WP Engine Local** (formerly LocalWP)
   - Download: https://localwp.com/
   - Version: Latest

2. **Node.js** (v18 or higher)
   - Download: https://nodejs.org/
   - Check: `node --version`

3. **Composer** (for PHP dependencies)
   - Download: https://getcomposer.org/
   - Check: `composer --version`

4. **WP-CLI** (comes with Local)
   - Check: `wp --version`

---

## Setup Instructions

### Step 1: Create Local WordPress Site

```
1. Open WP Engine Local
2. Click "+" to create new site
3. Configure:
   - Site name: hubspot-ecommerce-test
   - Environment: Preferred (PHP 8.0+)
   - WordPress: Latest
   - Admin: admin / admin
4. Click "Add Site"
5. Trust SSL certificate
6. Start site
```

### Step 2: Install Plugin

**Option A: Symlink (Recommended)**

```bash
# Open Local site shell (right-click site → Open Site Shell)
cd app/public/wp-content/plugins

# Windows (run as Administrator):
mklink /D hubspot-ecommerce "C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce"

# Mac/Linux:
ln -s /path/to/hubspot-ecommerce hubspot-ecommerce
```

**Option B: Copy Files**

```bash
# In Local site shell:
cp -r C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce \
      app/public/wp-content/plugins/hubspot-ecommerce
```

### Step 3: Install Plugin Dependencies

```bash
# In plugin directory:
cd app/public/wp-content/plugins/hubspot-ecommerce
composer install
```

### Step 4: Activate Plugin

```bash
# Via WP-CLI:
wp plugin activate hubspot-ecommerce

# Or via WordPress Admin:
# https://hubspot-ecommerce-test.local/wp-admin/plugins.php
```

### Step 5: Configure HubSpot

```
1. Get HubSpot Test API Key:
   - Log into HubSpot
   - Settings → Integrations → Private Apps
   - Create test app with required scopes
   - Copy API key

2. Configure in WordPress:
   - Go to: HubSpot Shop → Settings
   - Paste API key
   - Save settings
```

### Step 6: Seed Test Data

```bash
# In Local site shell:
wp eval-file wp-content/plugins/hubspot-ecommerce/tests/setup/seed-data.php
```

This creates:
- Test customer (testcustomer / testpass123)
- Shop pages with shortcodes
- Syncs products from HubSpot

### Step 7: Install Test Dependencies

```bash
# In plugin root directory:
cd C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce

# Install npm dependencies
npm install

# Install Playwright browsers
npx playwright install
```

---

## Running Tests

### All Tests

```bash
# Run all tests (headless)
npm test

# Run with visible browser
npm run test:headed

# Run in debug mode (step through)
npm run test:debug

# Run with UI (interactive)
npm run test:ui
```

### Specific Test Files

```bash
# Checkout flow tests
npx playwright test tests/e2e/checkout-flow.spec.js

# Product browsing tests
npx playwright test tests/e2e/product-browsing.spec.js

# Security tests
npx playwright test tests/e2e/security.spec.js
```

### Specific Browsers

```bash
# Chrome only
npm run test:chrome

# Firefox only
npm run test:firefox

# Safari (WebKit) only
npm run test:webkit
```

### Test Reports

```bash
# Generate and open HTML report
npm run test:report

# After running tests, report is in:
# playwright-report/index.html
```

---

## Test Structure

```
tests/
├── e2e/                          # End-to-end tests
│   ├── checkout-flow.spec.js    # Shopping cart and checkout
│   ├── product-browsing.spec.js # Product listing and viewing
│   └── security.spec.js         # Security vulnerability tests
├── helpers/
│   └── test-helpers.js          # Reusable test utilities
├── fixtures/
│   └── (test data files)
└── setup/
    └── seed-data.php            # Database seeding script
```

---

## Writing New Tests

### Example Test

```javascript
import { test, expect } from '@playwright/test';
import { TestHelpers } from '../helpers/test-helpers.js';

test.describe('My Feature', () => {
  let helpers;

  test.beforeEach(async ({ page }) => {
    helpers = new TestHelpers(page);
  });

  test('does something', async ({ page }) => {
    // Arrange
    await helpers.addProductToCart('Test Widget');

    // Act
    await page.goto('/cart');

    // Assert
    await expect(page.locator('.cart-item')).toBeVisible();
  });
});
```

### Using Test Helpers

```javascript
// Login as admin
await helpers.loginAsAdmin();

// Login as customer
await helpers.loginAsCustomer();

// Add product to cart
await helpers.addProductToCart('Product Name', quantity);

// Fill checkout form
await helpers.fillCheckoutForm({ email: 'custom@example.com' });

// Clear cart
await helpers.clearCart();

// Get cart total
const total = await helpers.getCartTotal();

// Wait for AJAX
await helpers.waitForAjax();
```

---

## Debugging Tests

### Visual Debugging

```bash
# Run with browser visible
npm run test:headed

# Run in debug mode (pause at breakpoints)
npm run test:debug

# Interactive UI mode
npm run test:ui
```

### Screenshots

```javascript
// Take screenshot in test
await helpers.screenshot('my-test-screenshot');

// Automatic screenshots on failure (already configured)
```

### Trace Viewer

```bash
# After test failure, view trace
npx playwright show-trace test-results/.../trace.zip
```

### Console Logs

```javascript
// Log to console
test('my test', async ({ page }) => {
  page.on('console', msg => console.log(msg.text()));

  // Test code...
});
```

---

## CI/CD Integration

### GitHub Actions

The workflow file is already created at `.github/workflows/e2e-tests.yml`

To enable:

```bash
# Push to trigger
git push origin main

# Or create PR
git checkout -b feature/my-feature
git push origin feature/my-feature
```

### Environment Variables

Set these in GitHub Secrets:

```
HUBSPOT_API_KEY=your-test-api-key
BASE_URL=https://your-staging-site.com
```

---

## Troubleshooting

### Site Not Accessible

```bash
# Check Local is running
# Right-click site → Start

# Check SSL certificate
# Site → SSL → Trust

# Add to hosts file if needed
# Windows: C:\Windows\System32\drivers\etc\hosts
# Mac/Linux: /etc/hosts
127.0.0.1 hubspot-ecommerce-test.local
```

### Tests Failing

```bash
# Check site is running
curl -k https://hubspot-ecommerce-test.local

# Verify plugin is active
wp plugin list

# Check for JavaScript errors
npm run test:headed
# Open browser console

# Clear test data
wp eval-file wp-content/plugins/hubspot-ecommerce/tests/setup/seed-data.php
```

### Database Issues

```bash
# Reset database
wp db reset --yes

# Reimport (if you have backup)
wp db import backup.sql

# Re-seed test data
wp eval-file wp-content/plugins/hubspot-ecommerce/tests/setup/seed-data.php
```

### SSL Certificate Errors

```
1. In Local: Site → SSL → Trust
2. Restart browser
3. Config already has: ignoreHTTPSErrors: true
```

---

## Test Coverage

### Current Coverage

| Area | Tests | Status |
|------|-------|--------|
| Checkout Flow | 7 tests | ✅ Complete |
| Product Browsing | 7 tests | ✅ Complete |
| Security | 12 tests | ✅ Complete |
| User Account | - | 📝 Planned |
| Admin | - | 📝 Planned |

### Adding Coverage

Priority areas for additional tests:

1. User account management
2. Order history viewing
3. Admin product sync
4. Invoice webhook handling
5. Subscription products
6. Multi-currency
7. Mobile responsive

---

## Performance Testing

### Load Testing

```bash
# Run tests with multiple workers
npx playwright test --workers=4

# Measure page load times
# (Use Network tab in headed mode)
```

### Lighthouse Audit

```javascript
// Add to test
const { playAudit } = require('playwright-lighthouse');

test('lighthouse audit', async ({ page }) => {
  await page.goto('/shop');
  await playAudit({
    page,
    thresholds: {
      performance: 50,
      accessibility: 90,
    },
  });
});
```

---

## Resources

- **Playwright Docs**: https://playwright.dev/
- **WP Engine Local Docs**: https://localwp.com/help-docs/
- **WP-CLI Docs**: https://wp-cli.org/
- **Plugin Docs**: See `TESTING_GUIDE.md`

---

## Support

For issues with:

- **Tests failing**: Check test output and screenshots
- **Setup problems**: See troubleshooting section above
- **Plugin bugs**: Create issue in GitHub repo
- **HubSpot API**: Check HubSpot API status and credentials

---

## Next Steps

1. ✅ Complete setup above
2. ✅ Run first test: `npm test`
3. 📝 Add more test coverage
4. 📝 Integrate with CI/CD
5. 📝 Performance testing
6. 📝 Accessibility testing

Happy Testing! 🧪
