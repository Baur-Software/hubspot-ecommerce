# License Server Setup - Step-by-Step Guide

**Site:** <http://baur-software.local>
**Admin:** <http://baur-software.local/wp-admin>
**Username:** baursoftware
**Password:** Glade123!@

---

## Part 1: Install Required Plugins (15 minutes)

### Step 1.1: Install WooCommerce

1. Go to **Plugins → Add New**
2. Search for: **WooCommerce**
3. Find "WooCommerce" by Automattic
4. Click **Install Now**
5. Click **Activate**
6. **Setup Wizard will appear:**
   - Store Details:
     - Country: United States
     - Address: (skip or use dummy)
     - City: (skip or use dummy)
     - Postcode: (skip or use dummy)
   - Industry: Software
   - Product Types: Digital downloads
   - Business Details: (skip or fill as needed)
   - Theme: Skip theme installation
   - **Click "Skip Setup Store"** or complete if you prefer

### Step 1.2: Install License Manager for WooCommerce

1. Go to **Plugins → Add New**
2. Search for: **License Manager for WooCommerce**
3. Find "License Manager for WooCommerce" by Darko Gjorgjijoski
4. Click **Install Now**
5. Click **Activate**
6. You should see a new menu item: **License Manager**

---

## Part 2: Configure WooCommerce (10 minutes)

### Step 2.1: Basic Settings

1. Go to **WooCommerce → Settings**
2. **General Tab:**
   - Currency: USD ($)
   - Click **Save changes**

3. **Products Tab:**
   - Shop Page: (create new page "Shop" if needed, or skip)
   - Click **Save changes**

4. **Payments Tab:**
   - For testing, enable **Cash on Delivery** or **Direct Bank Transfer**
   - (In production, you'd enable Stripe/PayPal)
   - Click **Save changes**

---

## Part 3: Create License Products (20 minutes)

### Step 3.1: Create "HubSpot Ecommerce Pro" Product

1. Go to **Products → Add New**

2. **Product Details:**
   - Product name: **HubSpot Ecommerce Pro**
   - Description:

     ```
     Unlock the full power of HubSpot Commerce Hub integration.

     Includes:
     - HubSpot Payments integration
     - Subscription management
     - Email preference sync
     - Priority email support
     ```

   - Short description:

     ```
     Full-featured HubSpot ecommerce integration for WordPress. $39/month.
     ```

3. **Product Data:**
   - Select: **Simple subscription** (from dropdown)
   - **General Tab:**
     - Subscription price: **39**
     - Subscription period: **month**
     - Expire after: **Never expire**
   - **Inventory Tab:**
     - Manage stock: **No** (digital product)
   - **Virtual:** Check this box
   - **Downloadable:** Leave unchecked

4. **Product Image:**
   - Upload a product image (or skip for now)

5. **Publish** the product

### Step 3.2: Create "HubSpot Ecommerce Enterprise" Product

1. Go to **Products → Add New**

2. **Product Details:**
   - Product name: **HubSpot Ecommerce Enterprise**
   - Description:

     ```
     Enterprise-level HubSpot integration for growing businesses.

     Everything in Pro, PLUS:
     - Multi-store support
     - Custom field mapping
     - Advanced automation
     - Priority phone + email support
     - Dedicated account manager
     - 5 hours custom development/month
     ```

   - Short description:

     ```
     Enterprise HubSpot ecommerce solution. White-glove service. $99/month.
     ```

3. **Product Data:**
   - Select: **Simple subscription**
   - **General Tab:**
     - Subscription price: **99**
     - Subscription period: **month**
     - Expire after: **Never expire**
   - **Virtual:** Check this box

4. **Publish** the product

---

## Part 4: Configure License Manager (30 minutes)

### Step 4.1: Create License Generator for Pro

1. Go to **License Manager → Generators**
2. Click **Add New**

3. **Generator Settings:**
   - Name: **HubSpot Ecommerce Pro Generator**
   - Character map: **ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789**
   - Number of chunks: **4**
   - Chunk length: **4**
   - Separator: **-** (dash)
   - Prefix: (leave empty)
   - Suffix: (leave empty)

   **This creates pattern:** `XXXX-XXXX-XXXX-XXXX`

4. **License Settings:**
   - Expires in: **0** (never expires while subscription active)
   - Expires at: (leave empty)
   - Valid for: **1** (activation)
   - Maximum number of activations: **1**

5. Click **Save Generator**

6. **Link Generator to Product:**
   - Scroll down to "Assigned to"
   - Click **Add products**
   - Search for "HubSpot Ecommerce Pro"
   - Select it
   - Click **Add**

### Step 4.2: Create License Generator for Enterprise

1. Go to **License Manager → Generators**
2. Click **Add New**

3. **Generator Settings:**
   - Name: **HubSpot Ecommerce Enterprise Generator**
   - Character map: **ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789**
   - Number of chunks: **4**
   - Chunk length: **4**
   - Separator: **-**
   - Prefix: (leave empty)
   - Suffix: (leave empty)

   **Pattern:** `XXXX-XXXX-XXXX-XXXX`

4. **License Settings:**
   - Expires in: **0**
   - Valid for: **1**
   - Maximum activations: **1**

5. Click **Save Generator**

6. **Link to Enterprise Product:**
   - Assign to "HubSpot Ecommerce Enterprise" product

---

## Part 5: Generate REST API Credentials (10 minutes)

### Step 5.1: Create API Key

1. Go to **License Manager → Settings**
2. Click **REST API** tab
3. Click **Add Key** button

4. **API Key Settings:**
   - Description: **HubSpot Ecommerce Plugin**
   - User: Select **baursoftware** (your admin user)
   - Permissions: **Read/Write**

5. Click **Generate API Key**

6. **IMPORTANT - Copy These Immediately:**

   ```
   Consumer Key: ck_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
   Consumer Secret: cs_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
   ```

   **⚠️ You can only see the secret ONCE! Copy both to a safe place.**

### Step 5.2: Test API Access

You can test the API is working with this curl command:

```bash
curl -u "ck_YOUR_KEY:cs_YOUR_SECRET" \
  http://baur-software.local/wp-json/lmfwc/v2/licenses
```

Should return: `{"code":"lmfwc_rest_data_error","message":"No licenses found.","data":{"status":404}}`

(This is good - means API is working, just no licenses yet)

---

## Part 6: Update Main Plugin with API Credentials (5 minutes)

### Option A: Using wp-config.php (Recommended)

1. Open: `C:\Users\Todd\Local Sites\baur-software\app\public\wp-config.php`

2. Add these lines **ABOVE** the line that says `/* That's all, stop editing! */`:

```php
// HubSpot Ecommerce License Server Credentials
define('HUBSPOT_LICENSE_CONSUMER_KEY', 'ck_PASTE_YOUR_KEY_HERE');
define('HUBSPOT_LICENSE_CONSUMER_SECRET', 'cs_PASTE_YOUR_SECRET_HERE');
```

3. Replace `ck_PASTE_YOUR_KEY_HERE` and `cs_PASTE_YOUR_SECRET_HERE` with the values from Step 5.1

4. Save the file

### Option B: Direct in Code (Not Recommended)

If you want to test quickly without wp-config:

1. Open: `c:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce\includes\class-license-manager.php`

2. Find the `get_consumer_key()` function (around line 28)

3. Replace the placeholder with your real key:

```php
return 'ck_YOUR_REAL_KEY_HERE';
```

4. Same for `get_consumer_secret()` function

**Note:** This is only for testing! Use wp-config.php for production.

---

## Part 7: Test License Flow End-to-End (15 minutes)

### Step 7.1: Purchase Test Subscription

1. Go to **Products** page on frontend: <http://baur-software.local/shop/>
2. Click on "HubSpot Ecommerce Pro"
3. Click **Add to cart**
4. Click **View cart**
5. Click **Proceed to checkout**

6. **Billing Details:**
   - First name: Test
   - Last name: User
   - Email: <your-email@example.com> (use real email to receive license)
   - Phone: 555-555-5555

7. **Payment:**
   - Select your enabled payment method
   - Click **Place Order**

8. **After order completes:**
   - You should see order confirmation
   - Check the order admin page
   - Go to **WooCommerce → Orders**
   - Click on the new order

9. **Verify License Generated:**
   - In the order page, scroll down
   - You should see a **License Keys** section
   - Copy the license key (format: `XXXX-XXXX-XXXX-XXXX`)

### Step 7.2: Activate License in HubSpot Plugin

**On your TEST WordPress site** (where HubSpot plugin is installed):

1. Go to **HubSpot Shop → License**
2. Enter the license key you copied
3. Click **Activate License**

4. **Verify Activation:**
   - Status should change to "Active"
   - Tier should change to "Pro"
   - Expiration should show subscription end date

5. **Verify Pro Features Unlocked:**
   - Go to **HubSpot Shop → Subscriptions**
   - Menu should NO LONGER show lock icon 🔒
   - Page should be fully accessible

### Step 7.3: Test Subscription Cancellation

**On baur-software.local:**

1. Go to **WooCommerce → Subscriptions**
2. Find your test subscription
3. Click **Cancel**
4. Confirm cancellation

**On test WordPress site:**

5. Wait 24 hours (or manually clear cache)
6. Go to **HubSpot Shop → License**
7. License status should change to "Expired" or "Cancelled"
8. Pro features should lock again

---

## Part 8: Verify Email Delivery (5 minutes)

### Check License Email Sent

1. Check your email inbox for license key
2. Subject should be something like "Your license for HubSpot Ecommerce Pro"
3. Email should contain the license key

### Configure Email (if not working)

If emails aren't arriving:

1. Go to **Plugins → Add New**
2. Install **WP Mail SMTP** or **MailPoet**
3. Configure with SMTP settings or SendGrid/Mailgun
4. Test email delivery

For local development, you can also use **MailHog** (included with Local):

- View emails at: <http://baur-software.local:8025>

---

## Part 9: Create Landing Pages (30 minutes)

### Create Pricing Page

1. Go to **Pages → Add New**
2. Title: **Pricing**
3. Content:

```html
<!-- Pricing Table -->
<div class="pricing-grid">

  <div class="pricing-card free">
    <h2>Free</h2>
    <p class="price">$0/month</p>
    <ul>
      <li>✅ Unlimited product sync</li>
      <li>✅ Contact creation</li>
      <li>✅ Deal tracking</li>
      <li>✅ Cart & checkout</li>
      <li>✅ Community support</li>
      <li>❌ HubSpot Payments</li>
      <li>❌ Subscriptions</li>
    </ul>
    <a href="/hubspot-ecommerce/" class="button">Download Free</a>
  </div>

  <div class="pricing-card pro featured">
    <h2>Pro</h2>
    <p class="price">$39/month</p>
    <ul>
      <li>✅ Everything in Free</li>
      <li>✅ HubSpot Payments</li>
      <li>✅ Subscription management</li>
      <li>✅ Email preferences</li>
      <li>✅ Private App support</li>
      <li>✅ Priority email support</li>
    </ul>
    <a href="/product/hubspot-ecommerce-pro/" class="button primary">Buy Pro</a>
  </div>

  <div class="pricing-card enterprise">
    <h2>Enterprise</h2>
    <p class="price">$99/month</p>
    <ul>
      <li>✅ Everything in Pro</li>
      <li>✅ Multi-store support</li>
      <li>✅ Custom field mapping</li>
      <li>✅ Phone + email support</li>
      <li>✅ Dedicated account manager</li>
      <li>✅ 5hrs custom dev/month</li>
    </ul>
    <a href="/product/hubspot-ecommerce-enterprise/" class="button">Buy Enterprise</a>
  </div>

</div>
```

4. **Publish** the page

---

## Checklist Summary

- [ ] WooCommerce installed and activated
- [ ] License Manager installed and activated
- [ ] WooCommerce basic settings configured (currency, payment)
- [ ] "HubSpot Ecommerce Pro" product created ($39/month)
- [ ] "HubSpot Ecommerce Enterprise" product created ($99/month)
- [ ] License generator created for Pro (pattern: XXXX-XXXX-XXXX-XXXX)
- [ ] License generator created for Enterprise (pattern: XXXX-XXXX-XXXX-XXXX)
- [ ] Generators linked to products
- [ ] REST API credentials generated
- [ ] API credentials copied to safe location
- [ ] API credentials added to wp-config.php (or test site)
- [ ] Test subscription purchased
- [ ] License key received
- [ ] License activated in plugin
- [ ] Pro features unlocked successfully
- [ ] Subscription cancellation tested
- [ ] Email delivery verified
- [ ] Pricing page created

---

## Troubleshooting

### License Not Generated After Purchase

1. Go to **License Manager → Settings → General**
2. Make sure "Auto-generate licenses" is enabled
3. Check generator is linked to product

### API Authentication Fails

1. Verify credentials are copied correctly (no extra spaces)
2. Check API key has Read/Write permissions
3. Test with curl command from Step 5.2

### Plugin Can't Connect to License Server

1. Verify site URL is correct in `class-license-manager.php`:
   - `const API_URL = 'https://baursoftware.com/wp-json/lmfwc/v2/licenses';`
   - For local testing: `http://baur-software.local/wp-json/lmfwc/v2/licenses`

2. Check WordPress REST API is enabled:
   - Visit: <http://baur-software.local/wp-json/>
   - Should return JSON, not 404

---

## Next Steps After Setup

1. ✅ Test free tier on fresh WordPress install
2. ✅ Test Pro tier with license activation
3. ✅ Create marketing pages
4. ✅ Prepare WordPress.org submission
5. ✅ Soft launch to beta users
6. ✅ Gather feedback and iterate

---

**Estimated Total Time:** 2-3 hours

**Status:** Ready to begin!
