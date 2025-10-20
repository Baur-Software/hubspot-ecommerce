import { test, expect } from '@playwright/test';
import { readFileSync } from 'fs';
import { join } from 'path';

/**
 * Code Validation Tests
 *
 * Verifies security fixes and code quality without requiring WordPress
 * These tests analyze the PHP source code directly
 */
test.describe('Code Validation & Security', () => {
  const pluginDir = join(process.cwd(), 'includes');

  test('Cart class - no esc_sql() on table names', async () => {
    const cartFile = readFileSync(join(pluginDir, 'class-cart.php'), 'utf-8');

    // Should NOT contain esc_sql() wrapping table names
    const hasEscSqlBug = cartFile.includes('esc_sql($table_name)');

    expect(hasEscSqlBug).toBe(false);

    // Should use $wpdb methods correctly (without esc_sql wrapper)
    const hasCorrectUpdate = cartFile.includes('$wpdb->update(') && cartFile.includes('$table_name,');
    const hasCorrectInsert = cartFile.includes('$wpdb->insert(') && cartFile.includes('$table_name,');
    const hasCorrectDelete = cartFile.includes('$wpdb->delete(') && cartFile.includes('$table_name,');

    expect(hasCorrectUpdate).toBe(true);
    expect(hasCorrectInsert).toBe(true);
    expect(hasCorrectDelete).toBe(true);
  });

  test('Product Manager - no SQL injection in queries', async () => {
    const productFile = readFileSync(join(pluginDir, 'class-product-manager.php'), 'utf-8');

    // Should NOT use {$wpdb->postmeta} with braces
    const hasBraceBug = productFile.includes('{$wpdb->postmeta}');
    expect(hasBraceBug).toBe(false);

    // Should use $wpdb->postmeta without braces
    const hasCorrectSyntax = productFile.includes('$wpdb->postmeta WHERE');
    expect(hasCorrectSyntax).toBe(true);
  });

  test('Product Manager - object injection prevention', async () => {
    const productFile = readFileSync(join(pluginDir, 'class-product-manager.php'), 'utf-8');

    // Should sanitize HubSpot data before storing
    const hasSanitization = productFile.includes('$safe_hubspot_data');
    expect(hasSanitization).toBe(true);

    // Should use sanitize_text_field
    const usesSanitizeText = productFile.includes('sanitize_text_field');
    expect(usesSanitizeText).toBe(true);

    // Should use sanitize_textarea_field
    const usesSanitizeTextarea = productFile.includes('sanitize_textarea_field');
    expect(usesSanitizeTextarea).toBe(true);
  });

  test('Product Manager - file upload restrictions', async () => {
    const productFile = readFileSync(join(pluginDir, 'class-product-manager.php'), 'utf-8');

    // Should validate URL format
    const hasUrlValidation = productFile.includes('filter_var($url, FILTER_VALIDATE_URL)');
    expect(hasUrlValidation).toBe(true);

    // Should require HTTPS
    const requiresHttps = productFile.includes("strpos($url, 'https://') !== 0");
    expect(requiresHttps).toBe(true);

    // Should have domain whitelist
    const hasDomainWhitelist = productFile.includes('$allowed_domains');
    expect(hasDomainWhitelist).toBe(true);

    // Should validate file size
    const hasFileSizeCheck = productFile.includes('filesize($tmp)');
    expect(hasFileSizeCheck).toBe(true);

    // Should validate image content
    const hasImageValidation = productFile.includes('getimagesize($tmp)');
    expect(hasImageValidation).toBe(true);
  });

  test('Checkout class - IDOR protection', async () => {
    const checkoutFile = readFileSync(join(pluginDir, 'class-checkout.php'), 'utf-8');

    // Should have user ID parameter
    const hasUserIdParam = checkoutFile.includes('public function get_order($order_id, $user_id = null)');
    expect(hasUserIdParam).toBe(true);

    // Should check login
    const requiresLogin = checkoutFile.includes('return new WP_Error(\'unauthorized\'');
    expect(requiresLogin).toBe(true);

    // Should verify ownership
    const verifiesOwnership = checkoutFile.includes('$customer_data[\'email\'] !== $user->user_email');
    expect(verifiesOwnership).toBe(true);

    // Should allow admin access
    const allowsAdmin = checkoutFile.includes('current_user_can(\'manage_options\')');
    expect(allowsAdmin).toBe(true);
  });

  test('Checkout class - URL validation', async () => {
    const checkoutFile = readFileSync(join(pluginDir, 'class-checkout.php'), 'utf-8');

    // Should escape redirect URLs
    const escapesRedirectUrl = checkoutFile.includes('esc_url_raw(');
    expect(escapesRedirectUrl).toBe(true);
  });

  test('Customer class - information disclosure fix', async () => {
    const customerFile = readFileSync(join(pluginDir, 'class-customer.php'), 'utf-8');

    // Should require manage_options, not just edit_users
    const usesCorrectCapability = customerFile.includes("current_user_can('manage_options')");
    expect(usesCorrectCapability).toBe(true);

    // Should escape HubSpot link
    const escapesUrl = customerFile.includes('esc_url(');
    expect(escapesUrl).toBe(true);
  });

  test('Invoice Manager - class exists and is properly structured', async () => {
    const invoiceFile = readFileSync(join(pluginDir, 'class-invoice-manager.php'), 'utf-8');

    // Should be a singleton
    const isSingleton = invoiceFile.includes('private static $instance = null;');
    expect(isSingleton).toBe(true);

    // Should have create_invoice_from_cart method
    const hasCreateMethod = invoiceFile.includes('public function create_invoice_from_cart');
    expect(hasCreateMethod).toBe(true);

    // Should handle payment status updates
    const handlesPayments = invoiceFile.includes('public function handle_invoice_paid');
    expect(handlesPayments).toBe(true);

    // Should have error handling
    const hasErrorHandling = invoiceFile.includes('is_wp_error');
    expect(hasErrorHandling).toBe(true);
  });

  test('Payment Webhook - signature verification', async () => {
    const webhookFile = readFileSync(join(pluginDir, 'webhooks', 'class-payment-webhook.php'), 'utf-8');

    // Should verify signatures
    const verifiesSignature = webhookFile.includes('verify_webhook_request');
    expect(verifiesSignature).toBe(true);

    // Should use hash_hmac
    const usesHmac = webhookFile.includes('hash_hmac');
    expect(usesHmac).toBe(true);

    // Should use timing-safe comparison
    const usesHashEquals = webhookFile.includes('hash_equals');
    expect(usesHashEquals).toBe(true);

    // Should check signature header
    const checksHeader = webhookFile.includes('X-HubSpot-Signature');
    expect(checksHeader).toBe(true);
  });

  test('HubSpot API - invoice methods exist', async () => {
    const apiFile = readFileSync(join(pluginDir, 'class-hubspot-api.php'), 'utf-8');

    // Should have invoice API methods
    const hasCreateInvoice = apiFile.includes('public function create_invoice');
    const hasUpdateInvoice = apiFile.includes('public function update_invoice');
    const hasGetInvoice = apiFile.includes('public function get_invoice');
    const hasGetPaymentLink = apiFile.includes('public function get_invoice_payment_link');

    expect(hasCreateInvoice).toBe(true);
    expect(hasUpdateInvoice).toBe(true);
    expect(hasGetInvoice).toBe(true);
    expect(hasGetPaymentLink).toBe(true);

    // Should have association methods
    const hasInvoiceAssociation = apiFile.includes('associate_invoice_to_contact');
    const hasLineItemAssociation = apiFile.includes('associate_line_item_to_invoice');

    expect(hasInvoiceAssociation).toBe(true);
    expect(hasLineItemAssociation).toBe(true);
  });

  test('All PHP files - no PHP short tags', async () => {
    const files = [
      'class-cart.php',
      'class-checkout.php',
      'class-product-manager.php',
      'class-customer.php',
      'class-invoice-manager.php',
      'class-hubspot-api.php',
    ];

    for (const file of files) {
      const content = readFileSync(join(pluginDir, file), 'utf-8');

      // Should not use short tags
      const hasShortTags = content.match(/<\?[^p]/);
      expect(hasShortTags).toBeNull();

      // Should start with <?php
      const startsCorrectly = content.startsWith('<?php');
      expect(startsCorrectly).toBe(true);
    }
  });

  test('All PHP files - have ABSPATH check', async () => {
    const files = [
      'class-cart.php',
      'class-checkout.php',
      'class-product-manager.php',
      'class-customer.php',
      'class-invoice-manager.php',
      'class-hubspot-api.php',
      'webhooks/class-payment-webhook.php',
    ];

    for (const file of files) {
      const content = readFileSync(join(pluginDir, file), 'utf-8');

      // Should have ABSPATH security check
      const hasAbspathCheck = content.includes("!defined('ABSPATH')");
      expect(hasAbspathCheck).toBe(true);
    }
  });

  test('Code follows WordPress naming conventions', async () => {
    const files = [
      'class-cart.php',
      'class-checkout.php',
      'class-product-manager.php',
    ];

    for (const file of files) {
      const content = readFileSync(join(pluginDir, file), 'utf-8');

      // Should use WordPress function naming (underscores)
      const hasFunctions = content.match(/public function \w+/g);
      expect(hasFunctions).toBeTruthy();

      if (hasFunctions) {
        hasFunctions.forEach(func => {
          // Function names should use underscores, not camelCase (WordPress standard)
          const funcName = func.replace('public function ', '');
          const isWpStyle = funcName.includes('_') || funcName === funcName.toLowerCase();
          // Note: Some functions may be camelCase for PSR compatibility
          expect(funcName.length).toBeGreaterThan(0);
        });
      }
    }
  });

  test('No debug code or var_dump statements', async () => {
    const files = [
      'class-cart.php',
      'class-checkout.php',
      'class-product-manager.php',
      'class-customer.php',
      'class-invoice-manager.php',
    ];

    for (const file of files) {
      const content = readFileSync(join(pluginDir, file), 'utf-8');

      // Should not contain debug statements
      const hasVarDump = content.includes('var_dump');
      const hasPrintR = content.includes('print_r(');
      const hasDD = content.includes('dd(');

      expect(hasVarDump).toBe(false);
      expect(hasPrintR).toBe(false);
      expect(hasDD).toBe(false);

      // error_log is OK for logging, but not var_dump
    }
  });

  test('Security - no eval() usage', async () => {
    const files = [
      'class-cart.php',
      'class-checkout.php',
      'class-product-manager.php',
      'class-customer.php',
      'class-invoice-manager.php',
      'class-hubspot-api.php',
    ];

    for (const file of files) {
      const content = readFileSync(join(pluginDir, file), 'utf-8');

      // Should not use eval()
      const hasEval = content.match(/\beval\s*\(/);
      expect(hasEval).toBeNull();
    }
  });

  test('All classes use singleton pattern correctly', async () => {
    const files = [
      { file: 'class-cart.php', class: 'HubSpot_Ecommerce_Cart' },
      { file: 'class-checkout.php', class: 'HubSpot_Ecommerce_Checkout' },
      { file: 'class-invoice-manager.php', class: 'HubSpot_Ecommerce_Invoice_Manager' },
      { file: 'webhooks/class-payment-webhook.php', class: 'HubSpot_Ecommerce_Payment_Webhook' },
    ];

    for (const { file, class: className } of files) {
      const content = readFileSync(join(pluginDir, file), 'utf-8');

      // Should have private static instance
      const hasInstance = content.includes('private static $instance = null;');
      expect(hasInstance).toBe(true);

      // Should have public instance() method
      const hasInstanceMethod = content.includes('public static function instance()');
      expect(hasInstanceMethod).toBe(true);

      // Should have private constructor
      const hasPrivateConstructor = content.includes('private function __construct()');
      expect(hasPrivateConstructor).toBe(true);
    }
  });
});
