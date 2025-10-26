# Feature Flag Implementation Guide

## Overview

This document outlines the exact code changes needed to add freemium licensing with feature flags to the HubSpot Ecommerce plugin.

---

## Payment Architecture

### Free Tier (OAuth Authentication)

```
Checkout → Create Deal → Create Order → Fire Payment Hook → User Handles Payment
```

**User must provide their own payment gateway integration via hooks**

### Pro Tier (Private App Authentication)

```
Checkout → Create Invoice → Get HubSpot Payment Link → Redirect to HubSpot → Auto-paid
```

**Fully automated via HubSpot Payments API**

---

## Step 1: Create License Manager Class

**File:** `includes/class-license-manager.php` (NEW FILE)

```php
<?php
/**
 * License Manager
 * Handles license verification and feature gating
 *
 * @package HubSpot_Ecommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class HubSpot_Ecommerce_License_Manager {

    private static $instance = null;
    private $license_key = null;
    private $tier = 'free';
    private $status = 'inactive';

    const API_URL = 'https://baursoftware.com/wp-json/hubspot-license/v1';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->license_key = get_option('hubspot_ecommerce_license_key');
        $this->load_cached_status();

        // Daily license check
        add_action('hubspot_ecommerce_daily_license_check', [$this, 'verify_license']);
        if (!wp_next_scheduled('hubspot_ecommerce_daily_license_check')) {
            wp_schedule_event(time(), 'daily', 'hubspot_ecommerce_daily_license_check');
        }

        // Admin hooks
        add_action('admin_init', [$this, 'handle_license_actions']);
    }

    /**
     * Get current tier
     */
    public function get_tier() {
        return $this->tier;
    }

    /**
     * Get license status
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Check if licensed (any paid tier)
     */
    public function is_licensed() {
        return in_array($this->tier, ['pro', 'enterprise']) && $this->status === 'active';
    }

    /**
     * Check if user can use Private App authentication
     */
    public function can_use_private_app() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use subscriptions
     */
    public function can_use_subscriptions() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use invoices (HubSpot Payments)
     */
    public function can_use_invoices() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use email preferences
     */
    public function can_use_email_preferences() {
        return $this->is_licensed();
    }

    /**
     * Check if user can use multi-store
     */
    public function can_use_multistore() {
        return $this->tier === 'enterprise';
    }

    /**
     * Verify license with server
     */
    public function verify_license($force = false) {
        // Check cache unless forced
        if (!$force) {
            $last_check = get_transient('hubspot_ecommerce_license_check');
            if ($last_check) {
                return true; // Cache valid
            }
        }

        if (empty($this->license_key)) {
            $this->tier = 'free';
            $this->status = 'inactive';
            $this->save_cached_status();
            return false;
        }

        $response = wp_remote_post(self::API_URL . '/verify', [
            'body' => json_encode([
                'license_key' => $this->license_key,
                'domain' => home_url(),
                'plugin_version' => HUBSPOT_ECOMMERCE_VERSION,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('License verification failed: ' . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['status']) && $data['status'] === 'active') {
            $this->tier = $data['tier'] ?? 'free';
            $this->status = 'active';
            $this->save_cached_status();

            // Cache for 24 hours
            set_transient('hubspot_ecommerce_license_check', true, DAY_IN_SECONDS);

            return true;
        } else {
            $this->tier = 'free';
            $this->status = $data['status'] ?? 'invalid';
            $this->save_cached_status();

            return false;
        }
    }

    /**
     * Activate license
     */
    public function activate_license($license_key) {
        $response = wp_remote_post(self::API_URL . '/activate', [
            'body' => json_encode([
                'license_key' => $license_key,
                'domain' => home_url(),
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['activated'])) {
            update_option('hubspot_ecommerce_license_key', $license_key);
            $this->license_key = $license_key;
            $this->verify_license(true);

            return true;
        }

        return new WP_Error('activation_failed', $data['message'] ?? __('Activation failed', 'hubspot-ecommerce'));
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        if (empty($this->license_key)) {
            return true;
        }

        wp_remote_post(self::API_URL . '/deactivate', [
            'body' => json_encode([
                'license_key' => $this->license_key,
                'domain' => home_url(),
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
        ]);

        delete_option('hubspot_ecommerce_license_key');
        delete_option('hubspot_ecommerce_license_tier');
        delete_option('hubspot_ecommerce_license_status');
        delete_transient('hubspot_ecommerce_license_check');

        $this->license_key = null;
        $this->tier = 'free';
        $this->status = 'inactive';

        return true;
    }

    /**
     * Handle admin actions (activate/deactivate)
     */
    public function handle_license_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Activate license
        if (isset($_POST['activate_license']) && check_admin_referer('hubspot_activate_license', 'hubspot_license_nonce')) {
            $license_key = sanitize_text_field($_POST['license_key']);
            $result = $this->activate_license($license_key);

            if (is_wp_error($result)) {
                add_settings_error(
                    'hubspot_ecommerce_license',
                    'license_activation_failed',
                    $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'hubspot_ecommerce_license',
                    'license_activated',
                    __('License activated successfully!', 'hubspot-ecommerce'),
                    'success'
                );
            }
        }

        // Deactivate license
        if (isset($_POST['deactivate_license']) && check_admin_referer('hubspot_deactivate_license', 'hubspot_license_nonce')) {
            $this->deactivate_license();

            add_settings_error(
                'hubspot_ecommerce_license',
                'license_deactivated',
                __('License deactivated.', 'hubspot-ecommerce'),
                'info'
            );
        }
    }

    /**
     * Get upgrade URL
     */
    public function get_upgrade_url() {
        return 'https://baursoftware.com/hubspot-ecommerce-pricing/';
    }

    /**
     * Render upgrade notice (for admin pages)
     */
    public function render_upgrade_notice($feature_name) {
        ?>
        <div class="notice notice-info" style="padding: 20px;">
            <h3><?php echo esc_html($feature_name); ?> 🔒</h3>
            <p style="font-size: 15px;">
                <?php printf(
                    __('%s is a Pro feature. Upgrade to unlock HubSpot Payments, subscriptions, and advanced automation.', 'hubspot-ecommerce'),
                    '<strong>' . esc_html($feature_name) . '</strong>'
                ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url($this->get_upgrade_url()); ?>"
                   class="button button-primary"
                   target="_blank">
                    <?php _e('Upgrade to Pro - $39/month', 'hubspot-ecommerce'); ?>
                </a>
                <a href="https://baursoftware.com/hubspot-ecommerce/features/"
                   class="button button-secondary"
                   target="_blank"
                   style="margin-left: 10px;">
                    <?php _e('Learn More', 'hubspot-ecommerce'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Save cached status
     */
    private function save_cached_status() {
        update_option('hubspot_ecommerce_license_tier', $this->tier);
        update_option('hubspot_ecommerce_license_status', $this->status);
    }

    /**
     * Load cached status
     */
    private function load_cached_status() {
        $this->tier = get_option('hubspot_ecommerce_license_tier', 'free');
        $this->status = get_option('hubspot_ecommerce_license_status', 'inactive');
    }
}
```

---

## Step 2: Update Checkout Class (Add Payment Hooks)

**File:** `includes/class-checkout.php`

**Add this method after `process_checkout()`:**

```php
/**
 * Process checkout - routes to appropriate payment method
 */
public function process_checkout($customer_data, $billing_data) {
    // Validate cart
    $cart_items = $this->cart->get_cart_items_with_products();
    if (empty($cart_items)) {
        return new WP_Error('empty_cart', __('Cart is empty', 'hubspot-ecommerce'));
    }

    // Get or create contact in HubSpot
    $contact_id = $this->get_or_create_contact($customer_data);
    if (is_wp_error($contact_id)) {
        return $contact_id;
    }

    // Check license tier to determine payment method
    $license = HubSpot_Ecommerce_License_Manager::instance();

    if ($license->can_use_invoices()) {
        // PRO TIER: Use HubSpot Payments (Invoice API)
        return $this->process_checkout_with_hubspot_payments($contact_id, $customer_data, $billing_data);
    } else {
        // FREE TIER: Use custom payment gateway (via hooks)
        return $this->process_checkout_with_custom_payment($contact_id, $customer_data, $billing_data);
    }
}

/**
 * Process checkout with HubSpot Payments (Pro feature)
 */
private function process_checkout_with_hubspot_payments($contact_id, $customer_data, $billing_data) {
    // Create HubSpot invoice
    $invoice_manager = HubSpot_Ecommerce_Invoice_Manager::instance();
    $invoice = $invoice_manager->create_invoice_from_cart($contact_id, $billing_data);

    if (is_wp_error($invoice)) {
        return $invoice;
    }

    // Get HubSpot payment link
    $payment_url = $this->api->get_invoice_payment_link($invoice['id']);

    if (is_wp_error($payment_url) || empty($payment_url)) {
        return new WP_Error('no_payment_url', __('Failed to get payment URL', 'hubspot-ecommerce'));
    }

    // Create order post
    $order_id = $this->create_order_post_for_invoice(
        $invoice['id'],
        $customer_data,
        $billing_data,
        'pending'
    );

    if (is_wp_error($order_id)) {
        return $order_id;
    }

    // Clear cart
    $this->cart->clear_cart();

    do_action('hubspot_ecommerce_checkout_processed', $order_id, $invoice['id']);

    return [
        'success' => true,
        'order_id' => $order_id,
        'invoice_id' => $invoice['id'],
        'payment_url' => $payment_url,
        'payment_method' => 'hubspot',
    ];
}

/**
 * Process checkout with custom payment gateway (Free tier)
 */
private function process_checkout_with_custom_payment($contact_id, $customer_data, $billing_data) {
    // Create deal in HubSpot (not invoice)
    $deal_id = $this->create_deal($contact_id, $billing_data);
    if (is_wp_error($deal_id)) {
        return $deal_id;
    }

    // Add line items to deal
    $cart_items = $this->cart->get_cart_items_with_products();
    $line_items_result = $this->add_line_items_to_deal($deal_id, $cart_items);
    if (is_wp_error($line_items_result)) {
        return $line_items_result;
    }

    // Create order post in WordPress
    $order_id = $this->create_order_post($deal_id, $customer_data, $billing_data, $cart_items);
    if (is_wp_error($order_id)) {
        return $order_id;
    }

    // Calculate total
    $total = $this->cart->get_total();

    // Fire payment hook - user must provide payment URL
    $payment_url = apply_filters(
        'hubspot_ecommerce_payment_url',
        '',
        $order_id,
        $total,
        $customer_data,
        $billing_data
    );

    // Clear cart
    $this->cart->clear_cart();

    do_action('hubspot_ecommerce_order_created', $order_id, $deal_id);

    if (empty($payment_url)) {
        // No payment gateway configured - return success but warn
        return [
            'success' => true,
            'order_id' => $order_id,
            'deal_id' => $deal_id,
            'payment_url' => null,
            'payment_method' => 'manual',
            'message' => __('Order created. Please configure a payment gateway or mark order as paid manually.', 'hubspot-ecommerce'),
        ];
    }

    return [
        'success' => true,
        'order_id' => $order_id,
        'deal_id' => $deal_id,
        'payment_url' => $payment_url,
        'payment_method' => 'custom',
    ];
}
```

---

## Step 3: Update Subscription Manager (Add Feature Gates)

**File:** `includes/class-subscription-manager.php`

**Replace the `__construct()` method:**

```php
private function __construct() {
    $this->api = HubSpot_Ecommerce_API::instance();

    // Check if Pro tier before adding hooks
    $license = HubSpot_Ecommerce_License_Manager::instance();

    if (!$license->can_use_subscriptions()) {
        // Show locked menu item for Free tier
        add_action('admin_menu', [$this, 'add_locked_submenu'], 20);
        return; // Don't add any other hooks
    }

    // Original hooks (only if Pro tier)
    add_action('admin_menu', [$this, 'add_admin_submenu'], 20);
    add_action('admin_init', [$this, 'sync_subscription_types']);
    add_action('wp_ajax_hs_sync_subscription_types', [$this, 'ajax_sync_subscription_types']);
    add_action('wp_ajax_hs_update_email_subscriptions', [$this, 'ajax_update_email_subscriptions']);
    add_action('wp_ajax_nopriv_hs_update_email_subscriptions', [$this, 'ajax_update_email_subscriptions']);
}
```

**Add these new methods:**

```php
/**
 * Add locked menu item (Free tier)
 */
public function add_locked_submenu() {
    add_submenu_page(
        'hubspot-ecommerce',
        __('Subscriptions 🔒', 'hubspot-ecommerce'),
        __('Subscriptions 🔒', 'hubspot-ecommerce'),
        'manage_options',
        'hubspot-ecommerce-subscriptions-locked',
        [$this, 'render_locked_page']
    );
}

/**
 * Render locked page (Free tier)
 */
public function render_locked_page() {
    $license = HubSpot_Ecommerce_License_Manager::instance();
    ?>
    <div class="wrap">
        <h1><?php _e('Subscription Management', 'hubspot-ecommerce'); ?> 🔒</h1>
        <?php $license->render_upgrade_notice(__('Subscription Management', 'hubspot-ecommerce')); ?>

        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2><?php _e('What You Get with Pro', 'hubspot-ecommerce'); ?></h2>
            <ul style="font-size: 15px; line-height: 2;">
                <li>✅ <strong>Recurring subscriptions</strong> - Sell monthly/yearly products</li>
                <li>✅ <strong>Email subscription preferences</strong> - Sync marketing opt-ins</li>
                <li>✅ <strong>Automated billing</strong> - HubSpot handles recurring charges</li>
                <li>✅ <strong>Subscription analytics</strong> - Track MRR and churn</li>
            </ul>
        </div>

        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2><?php _e('How to Upgrade', 'hubspot-ecommerce'); ?></h2>
            <ol style="font-size: 15px; line-height: 1.8;">
                <li>Purchase a Pro license at <a href="<?php echo esc_url($license->get_upgrade_url()); ?>" target="_blank">baursoftware.com</a></li>
                <li>Receive your license key via email</li>
                <li>Go to HubSpot Shop → License and enter your key</li>
                <li>Follow the guided wizard to set up Private App</li>
                <li>All Pro features automatically unlock!</li>
            </ol>
        </div>
    </div>
    <?php
}
```

---

## Step 4: Update Invoice Manager (Add Feature Gates)

**File:** `includes/class-invoice-manager.php`

**Update `__construct()` method:**

```php
private function __construct() {
    $this->api = HubSpot_Ecommerce_API::instance();

    // Only enable if Pro tier
    $license = HubSpot_Ecommerce_License_Manager::instance();
    if (!$license->can_use_invoices()) {
        // Don't register any hooks - feature disabled
        return;
    }

    // Original functionality continues here...
}
```

**Add public availability check method:**

```php
/**
 * Check if invoice features are available
 */
public static function is_available() {
    $license = HubSpot_Ecommerce_License_Manager::instance();
    return $license->can_use_invoices();
}
```

---

## Step 5: Load License Manager in Main Plugin File

**File:** `hubspot-ecommerce.php`

**Add before other includes:**

```php
// Load License Manager FIRST (needed for feature checks)
require_once HUBSPOT_ECOMMERCE_PLUGIN_DIR . 'includes/class-license-manager.php';

// Initialize license manager early
add_action('plugins_loaded', function() {
    HubSpot_Ecommerce_License_Manager::instance();
}, 5);
```

---

## Step 6: Add License Settings Page

**File:** `includes/admin/class-admin.php`

**Add to `add_admin_menu()` method:**

```php
// Add License submenu
add_submenu_page(
    'hubspot-ecommerce',
    __('License', 'hubspot-ecommerce'),
    __('License', 'hubspot-ecommerce'),
    'manage_options',
    'hubspot-ecommerce-license',
    [$this, 'render_license_page']
);
```

**Add new method:**

```php
/**
 * Render license page
 */
public function render_license_page() {
    $license = HubSpot_Ecommerce_License_Manager::instance();
    $tier = $license->get_tier();
    $status = $license->get_status();

    // Show any admin notices
    settings_errors('hubspot_ecommerce_license');

    ?>
    <div class="wrap">
        <h1><?php _e('License Management', 'hubspot-ecommerce'); ?></h1>

        <!-- Current Status -->
        <div class="card" style="max-width: 600px;">
            <h2><?php _e('Current Plan', 'hubspot-ecommerce'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Tier:', 'hubspot-ecommerce'); ?></th>
                    <td>
                        <strong style="font-size: 18px; text-transform: capitalize;">
                            <?php echo esc_html($tier); ?>
                        </strong>
                        <?php if ($tier === 'free'): ?>
                            <a href="<?php echo esc_url($license->get_upgrade_url()); ?>"
                               class="button button-primary"
                               target="_blank"
                               style="margin-left: 10px;">
                                <?php _e('Upgrade to Pro', 'hubspot-ecommerce'); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($tier !== 'free'): ?>
                <tr>
                    <th><?php _e('Status:', 'hubspot-ecommerce'); ?></th>
                    <td>
                        <?php if ($status === 'active'): ?>
                            <span style="color: green; font-size: 16px;">● <?php _e('Active', 'hubspot-ecommerce'); ?></span>
                        <?php else: ?>
                            <span style="color: red; font-size: 16px;">● <?php echo esc_html(ucfirst($status)); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Activate/Deactivate License -->
        <?php if ($tier === 'free'): ?>
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2><?php _e('Activate License', 'hubspot-ecommerce'); ?></h2>
            <p><?php _e('Enter your license key to unlock Pro features.', 'hubspot-ecommerce'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('hubspot_activate_license', 'hubspot_license_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="license_key"><?php _e('License Key:', 'hubspot-ecommerce'); ?></label></th>
                        <td>
                            <input type="text"
                                   id="license_key"
                                   name="license_key"
                                   class="regular-text code"
                                   placeholder="BSHS-XXXX-XXXX-XXXX"
                                   style="font-family: monospace;">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <button type="submit" name="activate_license" class="button button-primary">
                        <?php _e('Activate License', 'hubspot-ecommerce'); ?>
                    </button>
                </p>
            </form>

            <p>
                <?php _e("Don't have a license?", 'hubspot-ecommerce'); ?>
                <a href="<?php echo esc_url($license->get_upgrade_url()); ?>" target="_blank">
                    <?php _e('Purchase one now →', 'hubspot-ecommerce'); ?>
                </a>
            </p>
        </div>
        <?php else: ?>
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2><?php _e('Manage License', 'hubspot-ecommerce'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('hubspot_deactivate_license', 'hubspot_license_nonce'); ?>
                <p>
                    <?php _e('Deactivating your license will disable Pro features.', 'hubspot-ecommerce'); ?>
                </p>
                <p>
                    <button type="submit"
                            name="deactivate_license"
                            class="button button-secondary"
                            onclick="return confirm('<?php esc_attr_e('Are you sure? Pro features will be locked.', 'hubspot-ecommerce'); ?>');">
                        <?php _e('Deactivate License', 'hubspot-ecommerce'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
```

---

## Payment Hook Documentation

### For Users Implementing Custom Payment Gateways

**Hook: `hubspot_ecommerce_payment_url`**

**Example: Stripe Integration**

```php
// In theme functions.php or custom plugin

add_filter('hubspot_ecommerce_payment_url', 'my_stripe_payment_handler', 10, 5);

function my_stripe_payment_handler($payment_url, $order_id, $total, $customer_data, $billing_data) {
    require_once 'vendor/autoload.php'; // Stripe SDK

    \Stripe\Stripe::setApiKey('sk_test_...');

    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => $total * 100,
                'product_data' => [
                    'name' => 'Order #' . $order_id,
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => home_url('/order-confirmation/?order_id=' . $order_id),
        'cancel_url' => home_url('/checkout/'),
        'metadata' => [
            'order_id' => $order_id,
        ],
    ]);

    return $session->url;
}

// Handle Stripe webhook
add_action('rest_api_init', function() {
    register_rest_route('my-site/v1', '/stripe-webhook', [
        'methods' => 'POST',
        'callback' => 'my_stripe_webhook_handler',
        'permission_callback' => '__return_true',
    ]);
});

function my_stripe_webhook_handler($request) {
    $payload = $request->get_body();
    $sig_header = $request->get_header('stripe-signature');

    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, 'whsec_...');

    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;
        $order_id = $session->metadata->order_id;

        // Mark order as paid
        update_post_meta($order_id, '_payment_status', 'paid');
        wp_update_post(['ID' => $order_id, 'post_status' => 'publish']);
    }

    return new WP_REST_Response(['received' => true], 200);
}
```

---

## Summary

### New Files to Create

1. ✅ `includes/class-license-manager.php`

### Files to Modify

1. ✅ `hubspot-ecommerce.php` - Load license manager
2. ✅ `includes/class-checkout.php` - Add payment routing
3. ✅ `includes/class-subscription-manager.php` - Add feature gates
4. ✅ `includes/class-invoice-manager.php` - Add feature gates
5. ✅ `includes/admin/class-admin.php` - Add license page

### Hooks Added

1. ✅ `hubspot_ecommerce_payment_url` - For custom payment gateways (Free tier)

### License Server API

- Endpoints needed on baursoftware.com:
  - `POST /wp-json/hubspot-license/v1/verify`
  - `POST /wp-json/hubspot-license/v1/activate`
  - `POST /wp-json/hubspot-license/v1/deactivate`

---

## Next Steps

1. Create `class-license-manager.php`
2. Update all gated files
3. Test Free tier with custom payment hook
4. Test Pro tier with HubSpot Payments
5. Build license server API
6. Test license activation/deactivation

Ready to implement?
