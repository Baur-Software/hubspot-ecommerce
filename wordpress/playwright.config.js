import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright Configuration for HubSpot Ecommerce Plugin
 *
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests/e2e',

  // Run tests sequentially (important for ecommerce state)
  fullyParallel: false,

  // Fail the build on CI if you accidentally left test.only in the source code
  forbidOnly: !!process.env.CI,

  // Retry on CI only
  retries: process.env.CI ? 2 : 0,

  // Opt out of parallel tests (one at a time for ecommerce)
  workers: 1,

  // Reporter to use
  reporter: [
    ['html'],
    ['list'],
    ['json', { outputFile: 'test-results/results.json' }]
  ],

  // Shared settings for all the projects below
  use: {
    // Base URL for all tests
    baseURL: process.env.BASE_URL || 'https://granttk8org.local',

    // Collect trace when retrying the failed test
    trace: 'on-first-retry',

    // Take screenshot on failure
    screenshot: 'only-on-failure',

    // Record video on failure
    video: 'retain-on-failure',

    // Accept self-signed SSL certificates (for WP Engine Local)
    ignoreHTTPSErrors: true,

    // Default timeout for actions
    actionTimeout: 10000,

    // Default navigation timeout
    navigationTimeout: 30000,
  },

  // Configure projects for major browsers
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1280, height: 720 },
      },
    },

    {
      name: 'firefox',
      use: {
        ...devices['Desktop Firefox'],
        viewport: { width: 1280, height: 720 },
      },
    },

    {
      name: 'webkit',
      use: {
        ...devices['Desktop Safari'],
        viewport: { width: 1280, height: 720 },
      },
    },

    // Test against mobile viewports
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] },
    },

    {
      name: 'mobile-safari',
      use: { ...devices['iPhone 12'] },
    },

    // Test against branded browsers
    {
      name: 'microsoft-edge',
      use: {
        ...devices['Desktop Edge'],
        channel: 'msedge',
        viewport: { width: 1280, height: 720 },
      },
    },
  ],

  // Folder for test artifacts such as screenshots, videos, traces, etc.
  outputDir: 'test-results/',

  // Run your local dev server before starting the tests
  // Note: WP Engine Local should already be running
  // Commented out for framework testing without WordPress
  // webServer: {
  //   command: 'echo "WP Engine Local should already be running at https://hubspot-ecommerce-test.local"',
  //   url: 'https://hubspot-ecommerce-test.local',
  //   reuseExistingServer: true,
  //   ignoreHTTPSErrors: true,
  //   timeout: 120 * 1000,
  // },
});
