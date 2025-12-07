import { expect } from '@playwright/test';

/**
 * Test Helper Utilities for HubSpot Ecommerce Plugin
 *
 * Provides reusable functions for common test operations
 */
export class TestHelpers {
  constructor(page) {
    this.page = page;
  }

  /**
   * Login as WordPress admin
   */
  async loginAsAdmin() {
    await this.page.goto('/wp-admin');

    // Check if already logged in
    if (await this.page.locator('#wpadminbar').isVisible().catch(() => false)) {
      return;
    }

    await this.page.fill('input[name="log"]', 'admin');
    await this.page.fill('input[name="pwd"]', 'admin');
    await this.page.click('input[type="submit"]');
    await this.page.waitForURL('/wp-admin/**');
  }

  /**
   * Login as test customer
   */
  async loginAsCustomer() {
    await this.page.goto('/my-account');

    // Check if already logged in
    if (await this.page.locator('.logout-link, a:has-text("Logout")').isVisible().catch(() => false)) {
      return;
    }

    await this.page.fill('input[name="log"]', 'testcustomer');
    await this.page.fill('input[name="pwd"]', 'testpass123');
    await this.page.click('button:has-text("Log In"), input[type="submit"][value*="Log"]');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Logout current user
   */
  async logout() {
    await this.page.goto('/my-account');
    if (await this.page.locator('.logout-link, a:has-text("Logout")').isVisible().catch(() => false)) {
      await this.page.click('.logout-link, a:has-text("Logout")');
      await this.page.waitForLoadState('networkidle');
    }
  }

  /**
   * Add a product to cart by name
   */
  async addProductToCart(productName, quantity = 1) {
    await this.page.goto('/shop');
    await this.page.click(`text=${productName}`);
    await this.page.waitForSelector('.single-product, .product-single');

    // Set quantity if not 1
    if (quantity > 1) {
      const quantityInput = this.page.locator('input[name="quantity"], input[type="number"]').first();
      if (await quantityInput.isVisible()) {
        await quantityInput.fill(quantity.toString());
      }
    }

    await this.page.click('button:has-text("Add to Cart")');

    // Wait for cart to update
    await this.page.waitForTimeout(1000);
  }

  /**
   * Clear all items from cart
   */
  async clearCart() {
    await this.page.goto('/cart');

    // Wait for cart to load
    await this.page.waitForLoadState('networkidle');

    // Check if cart is already empty
    if (await this.page.locator('.cart-empty, .empty-cart').isVisible().catch(() => false)) {
      return;
    }

    // Remove all items
    while (await this.page.locator('button:has-text("Remove"), .remove-item').count() > 0) {
      await this.page.locator('button:has-text("Remove"), .remove-item').first().click();
      await this.page.waitForTimeout(500);
    }
  }

  /**
   * Fill checkout form with data
   */
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
      const selector = `input[name="${field}"]`;
      if (await this.page.locator(selector).isVisible()) {
        await this.page.fill(selector, value);
      }
    }
  }

  /**
   * Wait for AJAX requests to complete
   */
  async waitForAjax() {
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Get cart item count
   */
  async getCartCount() {
    const cartCount = await this.page.locator('.cart-count, .cart-items-count').textContent();
    return parseInt(cartCount.trim() || '0');
  }

  /**
   * Get cart total
   */
  async getCartTotal() {
    const cartTotal = await this.page.locator('.cart-total, .order-total').textContent();
    return cartTotal.trim();
  }

  /**
   * Take a screenshot for debugging
   */
  async screenshot(name) {
    await this.page.screenshot({
      path: `test-results/screenshots/${name}.png`,
      fullPage: true,
    });
  }

  /**
   * Check if element exists
   */
  async elementExists(selector) {
    return await this.page.locator(selector).count() > 0;
  }

  /**
   * Sync products from HubSpot (admin only)
   */
  async syncProducts() {
    await this.loginAsAdmin();
    await this.page.goto('/wp-admin/admin.php?page=hubspot-ecommerce-sync');
    await this.page.click('button:has-text("Sync Products Now"), button:has-text("Sync Now")');

    // Wait for sync to complete
    await this.page.waitForSelector('.sync-success, .notice-success', { timeout: 30000 });
  }

  /**
   * Create test order programmatically
   */
  async createTestOrder(productName = 'Test Widget') {
    await this.clearCart();
    await this.addProductToCart(productName);
    await this.page.goto('/checkout');
    await this.fillCheckoutForm();
    await this.page.click('button:has-text("Place Order")');

    // Wait for order confirmation
    await this.page.waitForTimeout(2000);
  }

  /**
   * Get last created order ID
   */
  async getLastOrderId() {
    await this.loginAsAdmin();
    await this.page.goto('/wp-admin/edit.php?post_type=hs_order');

    const firstOrder = this.page.locator('.wp-list-table tbody tr').first();
    const orderLink = await firstOrder.locator('a.row-title').getAttribute('href');

    const match = orderLink.match(/post=(\d+)/);
    return match ? match[1] : null;
  }

  /**
   * Verify nonce is present in form
   */
  async verifyNoncePresent() {
    const nonce = await this.page.locator('input[name*="nonce"]').count();
    expect(nonce).toBeGreaterThan(0);
  }

  /**
   * Set HubSpot API key (admin only)
   */
  async setHubSpotApiKey(apiKey) {
    await this.loginAsAdmin();
    await this.page.goto('/wp-admin/admin.php?page=hubspot-ecommerce-settings');
    await this.page.fill('input[name="hubspot_ecommerce_api_key"]', apiKey);
    await this.page.click('button[type="submit"], input[type="submit"]');
    await this.page.waitForSelector('.notice-success');
  }
}

/**
 * Mock HubSpot API Response Helper
 */
export class MockHubSpotAPI {
  static mockProduct(overrides = {}) {
    return {
      id: '12345',
      properties: {
        name: 'Test Widget',
        description: 'Test product description',
        price: '10.00',
        hs_sku: 'TEST-001',
        hs_cost_of_goods_sold: '5.00',
        hs_product_type: 'simple',
        ...overrides,
      },
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z',
      archived: false,
    };
  }

  static mockInvoice(overrides = {}) {
    return {
      id: 'invoice-12345',
      properties: {
        hs_invoice_billable: true,
        hs_currency: 'USD',
        hs_invoice_status: 'open',
        hs_payment_link: 'https://invoice.hubspot.com/payment/12345',
        ...overrides,
      },
    };
  }

  static mockContact(overrides = {}) {
    return {
      id: 'contact-12345',
      properties: {
        email: 'test@example.com',
        firstname: 'Test',
        lastname: 'Customer',
        phone: '555-0100',
        ...overrides,
      },
    };
  }
}

/**
 * Database Helper for test data
 */
export class DatabaseHelper {
  constructor(page) {
    this.page = page;
  }

  /**
   * Reset database to clean state
   */
  async resetDatabase() {
    // This would use WP-CLI via ssh/exec if available
    // For now, this is a placeholder
    console.log('Database reset not implemented - use WP-CLI manually');
  }

  /**
   * Seed test data
   */
  async seedTestData() {
    // This would run seed-data.php script
    console.log('Data seeding not implemented - use WP-CLI manually');
  }
}
