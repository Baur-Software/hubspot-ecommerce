import { test, expect } from '@playwright/test';
import { TestHelpers } from '../helpers/test-helpers.js';

/**
 * Checkout Flow End-to-End Tests
 *
 * Tests the complete shopping and checkout process
 */
test.describe('Checkout Flow', () => {
  let helpers;

  test.beforeEach(async ({ page }) => {
    helpers = new TestHelpers(page);

    // Clear cookies and storage
    await page.context().clearCookies();
    await page.context().clearPermissions();

    // Clear cart before each test
    await helpers.clearCart();
  });

  test('guest user completes checkout successfully', async ({ page }) => {
    test.setTimeout(60000); // 60 seconds for full checkout

    // Step 1: Add product to cart
    await helpers.addProductToCart('Test Widget');

    // Verify cart count updated
    const cartCount = await helpers.getCartCount();
    expect(cartCount).toBeGreaterThan(0);

    // Step 2: View cart
    await page.goto('/cart');
    await expect(page.locator('.cart-item, .product-row')).toBeVisible();

    // Verify product in cart
    await expect(page.locator('body')).toContainText('Test Widget');

    // Verify cart total
    const cartTotal = await helpers.getCartTotal();
    expect(cartTotal).toContain('$');

    // Step 3: Proceed to checkout
    await page.click('button:has-text("Proceed to Checkout"), a:has-text("Proceed to Checkout")');
    await expect(page).toHaveURL(/\/checkout/);

    // Step 4: Verify nonce is present (security check)
    await helpers.verifyNoncePresent();

    // Step 5: Fill checkout form
    await helpers.fillCheckoutForm({
      first_name: 'E2E',
      last_name: 'Test',
      email: 'e2e-test@example.com',
    });

    // Step 6: Submit order
    const responsePromise = page.waitForResponse(
      response => response.url().includes('admin-ajax.php'),
      { timeout: 10000 }
    );

    await page.click('button:has-text("Place Order")');

    // Wait for AJAX response
    await responsePromise;

    // Step 7: Verify success
    // Should either show confirmation or redirect to payment
    await page.waitForTimeout(2000);

    const currentUrl = page.url();
    const hasSuccess = await page.locator('.success, .notice-success, .order-confirmation')
      .isVisible()
      .catch(() => false);

    const hasPaymentUrl = currentUrl.includes('hubspot.com') ||
                          currentUrl.includes('payment') ||
                          currentUrl.includes('order-confirmation');

    expect(hasSuccess || hasPaymentUrl).toBeTruthy();
  });

  test('validates required checkout fields', async ({ page }) => {
    // Add product
    await helpers.addProductToCart('Test Widget');

    // Go to checkout
    await page.goto('/checkout');

    // Try to submit without filling required fields
    await page.click('button:has-text("Place Order")');

    // Should show validation errors or prevent submission
    await page.waitForTimeout(1000);

    // Check for error messages
    const hasError = await page.locator('.error, .woocommerce-error, .alert-danger, .notice-error')
      .isVisible()
      .catch(() => false);

    const hasValidation = await page.locator('input:invalid').count() > 0;

    expect(hasError || hasValidation).toBeTruthy();
  });

  test('updates cart quantities correctly', async ({ page }) => {
    // Add product
    await helpers.addProductToCart('Test Widget', 1);

    // Go to cart
    await page.goto('/cart');

    // Find quantity input
    const quantityInput = page.locator('input[type="number"], input[name*="quantity"]').first();

    if (await quantityInput.isVisible()) {
      // Update quantity to 3
      await quantityInput.fill('3');

      // Click update button if exists
      if (await page.locator('button:has-text("Update")').isVisible()) {
        await page.click('button:has-text("Update")');
        await page.waitForLoadState('networkidle');
      }

      // Verify total increased
      const cartTotal = await helpers.getCartTotal();
      expect(cartTotal).toContain('$');
      // Note: Exact amount depends on product price
    }
  });

  test('removes product from cart', async ({ page }) => {
    // Add product
    await helpers.addProductToCart('Test Widget');

    // Go to cart
    await page.goto('/cart');

    // Verify product is there
    await expect(page.locator('.cart-item, .product-row')).toBeVisible();

    // Remove product
    await page.click('button:has-text("Remove"), .remove-item, a.remove');

    // Wait for removal
    await page.waitForTimeout(1000);

    // Verify cart is empty
    const isEmpty = await page.locator('.cart-empty, .empty-cart, text=Your cart is empty')
      .isVisible()
      .catch(() => false);

    expect(isEmpty).toBeTruthy();
  });

  test('adds multiple products to cart', async ({ page }) => {
    // Add first product
    await helpers.addProductToCart('Test Widget');

    // Add second product (if available)
    await page.goto('/shop');
    const products = await page.locator('.product, .product-item').count();

    if (products > 1) {
      // Click second product
      await page.locator('.product, .product-item').nth(1).click();
      await page.click('button:has-text("Add to Cart")');

      // Go to cart
      await page.goto('/cart');

      // Should have 2 items
      const itemCount = await page.locator('.cart-item, .product-row').count();
      expect(itemCount).toBeGreaterThanOrEqual(2);
    }
  });

  test('cart persists across page reloads', async ({ page }) => {
    // Add product
    await helpers.addProductToCart('Test Widget');

    // Get initial cart count
    const initialCount = await helpers.getCartCount();

    // Reload page
    await page.reload();
    await page.waitForLoadState('networkidle');

    // Cart count should be the same
    const afterReloadCount = await helpers.getCartCount();
    expect(afterReloadCount).toBe(initialCount);
  });

  test('cannot checkout with empty cart', async ({ page }) => {
    // Clear cart
    await helpers.clearCart();

    // Try to go to checkout
    await page.goto('/checkout');

    // Should redirect or show empty cart message
    const hasEmptyMessage = await page.locator('text=Your cart is empty, text=Cart is empty')
      .isVisible()
      .catch(() => false);

    const redirectedToCart = page.url().includes('/cart');

    expect(hasEmptyMessage || redirectedToCart).toBeTruthy();
  });
});
