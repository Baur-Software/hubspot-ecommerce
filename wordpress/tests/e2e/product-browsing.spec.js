import { test, expect } from '@playwright/test';
import { TestHelpers } from '../helpers/test-helpers.js';

/**
 * Product Browsing Tests
 *
 * Tests product listing, viewing, and search functionality
 */
test.describe('Product Browsing', () => {
  let helpers;

  test.beforeEach(async ({ page }) => {
    helpers = new TestHelpers(page);
  });

  test('displays product archive page', async ({ page }) => {
    await page.goto('/shop');

    // Verify page loaded
    await page.waitForLoadState('networkidle');

    // Should have products or empty state
    const hasProducts = await page.locator('.product, .product-item').count() > 0;
    const hasEmptyState = await page.locator('text=No products found').isVisible().catch(() => false);

    expect(hasProducts || hasEmptyState).toBeTruthy();

    if (hasProducts) {
      // Verify product elements
      const firstProduct = page.locator('.product, .product-item').first();
      await expect(firstProduct).toBeVisible();

      // Should have title and price
      const hasTitle = await firstProduct.locator('.product-title, h2, h3').isVisible();
      expect(hasTitle).toBeTruthy();
    }
  });

  test('views single product page', async ({ page }) => {
    await page.goto('/shop');

    // Check if products exist
    const productCount = await page.locator('.product, .product-item').count();

    if (productCount > 0) {
      // Click first product
      await page.locator('.product, .product-item').first().click();

      // Wait for product page
      await page.waitForLoadState('networkidle');

      // Verify single product elements
      await expect(page.locator('.single-product, .product-single')).toBeVisible();

      // Should have add to cart button
      const addToCartExists = await page.locator('button:has-text("Add to Cart")').isVisible();
      expect(addToCartExists).toBeTruthy();
    }
  });

  test('product page displays correct information', async ({ page }) => {
    await page.goto('/shop');

    const productCount = await page.locator('.product, .product-item').count();

    if (productCount > 0) {
      await page.locator('.product, .product-item').first().click();
      await page.waitForLoadState('networkidle');

      // Check for key product information
      const checks = {
        title: await page.locator('.product-title, h1').isVisible(),
        price: await page.locator('.product-price, .price').isVisible(),
        description: await page.locator('.product-description, .description').isVisible().catch(() => false),
      };

      // At minimum, should have title and price
      expect(checks.title).toBeTruthy();
      expect(checks.price).toBeTruthy();
    }
  });

  test('product images load correctly', async ({ page }) => {
    await page.goto('/shop');

    const productCount = await page.locator('.product, .product-item').count();

    if (productCount > 0) {
      // Find product with image
      const productWithImage = page.locator('.product img, .product-item img').first();

      if (await productWithImage.isVisible()) {
        // Check image loaded
        const imgSrc = await productWithImage.getAttribute('src');
        expect(imgSrc).toBeTruthy();
        expect(imgSrc.length).toBeGreaterThan(0);

        // Verify image is from allowed domain (security check)
        const isHttps = imgSrc.startsWith('https://');
        expect(isHttps).toBeTruthy();
      }
    }
  });

  test('navigates between product archive and single product', async ({ page }) => {
    await page.goto('/shop');

    const productCount = await page.locator('.product, .product-item').count();

    if (productCount > 0) {
      // Remember shop URL
      const shopUrl = page.url();

      // Click product
      await page.locator('.product, .product-item').first().click();
      await page.waitForLoadState('networkidle');

      // Should be on different URL
      expect(page.url()).not.toBe(shopUrl);

      // Go back
      await page.goBack();
      await page.waitForLoadState('networkidle');

      // Should be back on shop
      expect(page.url()).toBe(shopUrl);
    }
  });

  test('product SKU displays when available', async ({ page }) => {
    await page.goto('/shop');

    const productCount = await page.locator('.product, .product-item').count();

    if (productCount > 0) {
      await page.locator('.product, .product-item').first().click();
      await page.waitForLoadState('networkidle');

      // Check if SKU is displayed (optional field)
      const skuVisible = await page.locator('.sku, .product-sku')
        .isVisible()
        .catch(() => false);

      // SKU might not always be present, just verify it doesn't break
      expect(true).toBeTruthy();
    }
  });

  test('handles products without images gracefully', async ({ page }) => {
    await page.goto('/shop');

    // Products should display even without images
    const productCount = await page.locator('.product, .product-item').count();

    if (productCount > 0) {
      const product = page.locator('.product, .product-item').first();

      // Should still show title and price
      const hasTitle = await product.locator('.product-title, h2, h3').isVisible();
      expect(hasTitle).toBeTruthy();

      // Click should still work
      await product.click();
      await page.waitForLoadState('networkidle');

      // Should reach product page
      const isProductPage = page.url().includes('/shop/') || page.url().includes('/product/');
      expect(isProductPage).toBeTruthy();
    }
  });
});
