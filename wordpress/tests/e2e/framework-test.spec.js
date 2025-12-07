import { test, expect } from '@playwright/test';

/**
 * Framework Verification Test
 *
 * Verifies that Playwright is installed and working correctly
 * Does not require WordPress site to be running
 */
test.describe('Framework Verification', () => {
  test('playwright is installed and working', async ({ page }) => {
    // This test verifies the test framework itself works
    expect(true).toBeTruthy();
    expect(1 + 1).toBe(2);
  });

  test('can create page context', async ({ page }) => {
    // Verify we can create a page
    expect(page).toBeDefined();
  });

  test('browser launches successfully', async ({ page, context }) => {
    // Verify browser and context are available
    expect(context).toBeDefined();
    expect(page).toBeDefined();
  });

  test('can navigate to example.com', async ({ page }) => {
    // Navigate to a real site to verify internet connectivity
    await page.goto('https://example.com');
    await expect(page).toHaveTitle(/Example Domain/);
  });
});
