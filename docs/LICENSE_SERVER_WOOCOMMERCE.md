# License Server Setup - WooCommerce Solution

## Overview

Use baursoftware.com (WPEngine) with WooCommerce + License Manager plugin to handle licensing for the HubSpot Ecommerce plugin.

---

## Setup on baursoftware.com

### Step 1: Install Required Plugins

**Required:**

1. **WooCommerce** (free)
   - <https://wordpress.org/plugins/woocommerce/>

2. **License Manager for WooCommerce** (free)
   - <https://wordpress.org/plugins/license-manager-for-woocommerce/>
   - Provides REST API for license verification
   - Auto-generates license keys
   - Handles activation/deactivation

3. **WooCommerce Subscriptions** ($199/year)
   - <https://woocommerce.com/products/woocommerce-subscriptions/>
   - Enables recurring billing
   - Handles renewals/cancellations
   - **Alternative (Free):** Use Stripe Billing directly

---

### Step 2: Configure WooCommerce

1. Install WooCommerce
2. Run setup wizard
3. Configure Stripe payment gateway
4. Set currency to USD

---

### Step 3: Configure License Manager

**Settings → License Manager → General:**

- Enable REST API: ✅
- License key format: `BSHS-{RANDOM:4}-{RANDOM:4}-{RANDOM:4}`
- Expires after: Based on subscription
- Max activations: 1 (1 site per license)

**Settings → License Manager → REST API:**

- Enable API: ✅
- Generate API keys for authentication

---

### Step 4: Create Products

#### Product 1: HubSpot Ecommerce Pro

**General:**

- Product name: HubSpot Ecommerce Pro
- Price: $39/month
- Type: Subscription product
- Billing cycle: Every 1 month

**License Manager Settings:**

- Deliver license keys: ✅
- License uses: 1 (max 1 activation)
- Expires: When subscription cancelled
- Pattern: `BSHS-{RANDOM:4}-{RANDOM:4}-{RANDOM:4}`

**Product Meta:**

- Set tier: `pro`

#### Product 2: HubSpot Ecommerce Enterprise

**General:**

- Product name: HubSpot Ecommerce Enterprise
- Price: $99/month
- Type: Subscription product
- Billing cycle: Every 1 month

**License Manager Settings:**

- Deliver license keys: ✅
- License uses: 1 (max 1 activation)
- Expires: When subscription cancelled
- Pattern: `BSHS-{RANDOM:4}-{RANDOM:4}-{RANDOM:4}`

**Product Meta:**

- Set tier: `enterprise`

---

### Step 5: API Authentication

**Generate API Key:**

1. Go to License Manager → Settings → REST API
2. Click "Create new API key"
3. Label: "HubSpot Ecommerce Plugin"
4. Copy Consumer Key and Consumer Secret

**Store securely for plugin use**

---

## REST API Endpoints

Base URL: `https://baursoftware.com/wp-json/lmfwc/v2`

### 1. Validate License

**Endpoint:** `POST /licenses/validate`

**Request:**

```json
{
  "license_key": "BSHS-1234-5678-9ABC"
}
```

**Response (Valid):**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_id": 456,
    "license_key": "BSHS-1234-5678-9ABC",
    "expires_at": "2025-11-20 23:59:59",
    "status": 1,
    "times_activated": 1,
    "times_activated_max": 1
  }
}
```

**Response (Invalid):**

```json
{
  "success": false,
  "data": {
    "error": "License key not found"
  }
}
```

### 2. Activate License

**Endpoint:** `POST /licenses/activate`

**Request:**

```json
{
  "license_key": "BSHS-1234-5678-9ABC",
  "label": "example.com"
}
```

**Response (Success):**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "token": "xyz789...",
    "expires_at": "2025-11-20 23:59:59"
  }
}
```

**Response (Already Activated):**

```json
{
  "success": false,
  "data": {
    "error": "License already activated on maximum number of domains"
  }
}
```

### 3. Deactivate License

**Endpoint:** `POST /licenses/deactivate`

**Request:**

```json
{
  "license_key": "BSHS-1234-5678-9ABC",
  "token": "xyz789..."
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "message": "License deactivated successfully"
  }
}
```

---

## Update Plugin License Manager Class

**File:** `includes/class-license-manager.php`

Update the API URL and methods to match License Manager for WooCommerce:

```php
class HubSpot_Ecommerce_License_Manager {

    const API_URL = 'https://baursoftware.com/wp-json/lmfwc/v2/licenses';
    const CONSUMER_KEY = 'ck_XXXXXXXXXXXX'; // From License Manager settings
    const CONSUMER_SECRET = 'cs_XXXXXXXXXXXX';

    /**
     * Verify license with WooCommerce License Manager
     */
    public function verify_license($force = false) {
        // Check cache
        if (!$force) {
            $last_check = get_transient('hubspot_ecommerce_license_check');
            if ($last_check) {
                return true;
            }
        }

        if (empty($this->license_key)) {
            $this->tier = 'free';
            $this->status = 'inactive';
            $this->save_cached_status();
            return false;
        }

        // Call License Manager API
        $response = wp_remote_post(self::API_URL . '/validate', [
            'body' => json_encode([
                'license_key' => $this->license_key,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(self::CONSUMER_KEY . ':' . self::CONSUMER_SECRET),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('License verification failed: ' . $response->get_error_message());
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['success']) && $data['success'] === true) {
            $license_data = $data['data'];

            // Check if expired
            if (!empty($license_data['expires_at'])) {
                $expires = strtotime($license_data['expires_at']);
                if ($expires < time()) {
                    $this->tier = 'free';
                    $this->status = 'expired';
                    $this->save_cached_status();
                    return false;
                }
            }

            // Check status (1 = sold, 2 = delivered, 3 = active)
            if ($license_data['status'] < 2) {
                $this->tier = 'free';
                $this->status = 'inactive';
                $this->save_cached_status();
                return false;
            }

            // Get tier from product meta
            $tier = $this->get_license_tier($license_data['order_id']);

            $this->tier = $tier;
            $this->status = 'active';
            $this->save_cached_status();

            // Cache for 24 hours
            set_transient('hubspot_ecommerce_license_check', true, DAY_IN_SECONDS);

            return true;
        }

        // Invalid license
        $this->tier = 'free';
        $this->status = 'invalid';
        $this->save_cached_status();

        return false;
    }

    /**
     * Activate license
     */
    public function activate_license($license_key) {
        $response = wp_remote_post(self::API_URL . '/activate', [
            'body' => json_encode([
                'license_key' => $license_key,
                'label' => parse_url(home_url(), PHP_URL_HOST),
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(self::CONSUMER_KEY . ':' . self::CONSUMER_SECRET),
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($data['success']) && $data['success'] === true) {
            // Store license key and activation token
            update_option('hubspot_ecommerce_license_key', $license_key);
            update_option('hubspot_ecommerce_license_token', $data['data']['token']);

            $this->license_key = $license_key;
            $this->verify_license(true);

            return true;
        }

        return new WP_Error(
            'activation_failed',
            $data['data']['error'] ?? __('License activation failed', 'hubspot-ecommerce')
        );
    }

    /**
     * Deactivate license
     */
    public function deactivate_license() {
        if (empty($this->license_key)) {
            return true;
        }

        $token = get_option('hubspot_ecommerce_license_token');

        wp_remote_post(self::API_URL . '/deactivate', [
            'body' => json_encode([
                'license_key' => $this->license_key,
                'token' => $token,
            ]),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode(self::CONSUMER_KEY . ':' . self::CONSUMER_SECRET),
            ],
            'timeout' => 15,
        ]);

        delete_option('hubspot_ecommerce_license_key');
        delete_option('hubspot_ecommerce_license_token');
        delete_option('hubspot_ecommerce_license_tier');
        delete_option('hubspot_ecommerce_license_status');
        delete_transient('hubspot_ecommerce_license_check');

        $this->license_key = null;
        $this->tier = 'free';
        $this->status = 'inactive';

        return true;
    }

    /**
     * Get tier from WooCommerce order
     */
    private function get_license_tier($order_id) {
        // Make API call to get order details
        $response = wp_remote_get("https://baursoftware.com/wp-json/wc/v3/orders/{$order_id}", [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode(self::CONSUMER_KEY . ':' . self::CONSUMER_SECRET),
            ],
        ]);

        if (is_wp_error($response)) {
            return 'pro'; // Default to pro on error
        }

        $order = json_decode(wp_remote_retrieve_body($response), true);

        // Check product name to determine tier
        foreach ($order['line_items'] as $item) {
            if (stripos($item['name'], 'Enterprise') !== false) {
                return 'enterprise';
            }
        }

        return 'pro';
    }
}
```

---

## Customer Purchase Flow

1. **Customer visits:** <https://baursoftware.com/hubspot-ecommerce-pricing/>
2. **Clicks:** "Buy Pro - $39/month"
3. **Checkout:** WooCommerce checkout with Stripe
4. **Payment:** Stripe processes $39/month subscription
5. **Order complete:** WooCommerce order created
6. **License generated:** License Manager auto-generates key: `BSHS-1234-5678-9ABC`
7. **Email sent:** Customer receives email with license key
8. **Customer activates:** Enters key in WordPress plugin
9. **Plugin verifies:** REST API call to baursoftware.com
10. **Pro unlocked:** All features enabled!

---

## Monthly Billing

**WooCommerce Subscriptions handles:**

- ✅ Monthly recurring charges via Stripe
- ✅ Automatic renewal emails
- ✅ Failed payment handling
- ✅ Customer cancellation
- ✅ License expiration on cancellation

**When subscription cancelled:**

- License Manager auto-expires license
- Next daily check in plugin → reverts to Free tier
- Customer can continue using Free features

---

## Cost Comparison

### Option A: Custom License Server

- Development: 40 hours × $100/hr = **$4,000**
- Maintenance: Ongoing
- Total: **$4,000+**

### Option B: WooCommerce + License Manager

- WooCommerce: **Free**
- License Manager: **Free**
- WooCommerce Subscriptions: **$199/year**
- Setup time: **2 hours**
- Total: **$199/year** 🎉

**Winner:** WooCommerce saves $3,800+ and is ready in 2 hours!

---

## Next Steps

1. ✅ Install WooCommerce on baursoftware.com
2. ✅ Install License Manager for WooCommerce
3. ✅ Install WooCommerce Subscriptions (or use Stripe Billing)
4. ✅ Create Pro and Enterprise products
5. ✅ Configure license settings
6. ✅ Generate REST API keys
7. ✅ Update plugin License Manager class with API credentials
8. ✅ Test license activation flow
9. ✅ Create pricing page
10. ✅ Launch! 🚀

---

## Alternative: Stripe Billing (No WooCommerce Subscriptions)

If you don't want to pay $199/year for WooCommerce Subscriptions:

**Use Stripe Billing Portal:**

- Create products in Stripe dashboard
- Use Stripe Checkout for purchases
- Handle webhooks to create license keys
- Customers manage subscriptions via Stripe portal

**This requires:**

- Custom webhook handler on baursoftware.com
- Manual license creation on subscription
- About 4 hours of development

Still cheaper than WooCommerce Subscriptions but less polished UX.

---

## Recommendation

**Use WooCommerce + License Manager for WooCommerce**

Reasons:

1. ✅ Setup in 2 hours vs 40 hours custom development
2. ✅ Proven, battle-tested solution (100k+ active sites)
3. ✅ Free (except WooCommerce Subscriptions $199/year)
4. ✅ Full customer dashboard for managing licenses
5. ✅ Automatic email delivery of license keys
6. ✅ REST API already built
7. ✅ Handles renewals/cancellations automatically
8. ✅ Already on WPEngine - no new infrastructure

Total savings: **$3,800+ in development + ongoing maintenance**

Ready to set this up?
