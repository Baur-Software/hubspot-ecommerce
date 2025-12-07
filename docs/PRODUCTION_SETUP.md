# Production Setup Guide

This guide explains how to configure the HubSpot Ecommerce plugin for production deployment with proper environment variable management.

## Security Best Practices

**IMPORTANT:** Never commit sensitive credentials to version control. Always use environment variables or WordPress constants defined in `wp-config.php`.

---

## Configuration Methods

### Method 1: wp-config.php (Recommended for WordPress)

Add these constants to your `wp-config.php` file **above** the line that says `/* That's all, stop editing! */`:

```php
/**
 * HubSpot Ecommerce Plugin Configuration
 */

// OAuth Credentials (from HubSpot Developer App)
define('HUBSPOT_OAUTH_CLIENT_ID', 'your-client-id-here');
define('HUBSPOT_OAUTH_CLIENT_SECRET', 'your-client-secret-here');

// License Server Credentials (from baursoftware.com)
define('HUBSPOT_LICENSE_CONSUMER_KEY', 'ck_your_consumer_key_here');
define('HUBSPOT_LICENSE_CONSUMER_SECRET', 'cs_your_consumer_secret_here');
```

### Method 2: Environment Variables (Recommended for WP Engine/Hosting)

If your hosting provider supports environment variables (WP Engine, Kinsta, etc.), add these to your environment:

```bash
HUBSPOT_OAUTH_CLIENT_ID=your-client-id-here
HUBSPOT_OAUTH_CLIENT_SECRET=your-client-secret-here
HUBSPOT_LICENSE_CONSUMER_KEY=ck_your_consumer_key_here
HUBSPOT_LICENSE_CONSUMER_SECRET=cs_your_consumer_secret_here
```

Then add this to your `wp-config.php` to load them:

```php
// Load environment variables into WordPress constants
if (getenv('HUBSPOT_OAUTH_CLIENT_ID')) {
    define('HUBSPOT_OAUTH_CLIENT_ID', getenv('HUBSPOT_OAUTH_CLIENT_ID'));
}
if (getenv('HUBSPOT_OAUTH_CLIENT_SECRET')) {
    define('HUBSPOT_OAUTH_CLIENT_SECRET', getenv('HUBSPOT_OAUTH_CLIENT_SECRET'));
}
if (getenv('HUBSPOT_LICENSE_CONSUMER_KEY')) {
    define('HUBSPOT_LICENSE_CONSUMER_KEY', getenv('HUBSPOT_LICENSE_CONSUMER_KEY'));
}
if (getenv('HUBSPOT_LICENSE_CONSUMER_SECRET')) {
    define('HUBSPOT_LICENSE_CONSUMER_SECRET', getenv('HUBSPOT_LICENSE_CONSUMER_SECRET'));
}
```

---

## Step-by-Step Production Setup

### 1. Get HubSpot OAuth Credentials

1. Go to [HubSpot Developer Portal](https://developers.hubspot.com/)
2. Navigate to your app: "Baur Software HubSpot Ecommerce for WordPress"
3. Copy the **Client ID** and **Client Secret**
4. Add them to `wp-config.php` as shown above

**Configuration Required:**

OAuth credentials must be configured in `wp-config.php`:

```php
define('HUBSPOT_OAUTH_CLIENT_ID', 'your-client-id-from-hubspot');
define('HUBSPOT_OAUTH_CLIENT_SECRET', 'your-client-secret-from-hubspot');
```

Get these values from your HubSpot Developer App settings.

### 2. Set Up License Server on baursoftware.com

#### A. Install Required Plugins

```bash
# On baursoftware.com WordPress site
wp plugin install woocommerce --activate
wp plugin install license-manager-for-woocommerce --activate
```

#### B. Configure License Manager

1. Go to **License Manager → Settings → REST API**
2. Click "Add Key"
3. Description: "HubSpot Ecommerce Plugin"
4. Permissions: Read/Write
5. Click "Generate API Key"
6. Copy the **Consumer Key** (starts with `ck_`)
7. Copy the **Consumer Secret** (starts with `cs_`)
8. Add them to production `wp-config.php`

#### C. Create License Products

1. Go to **Products → Add New**
2. Create "HubSpot Ecommerce Pro" product:
   - Type: Subscription
   - Price: $39/month
   - Billing interval: Monthly
3. Go to **License Manager → Generators**
4. Create license generator:
   - Name: "HubSpot Ecommerce Pro"
   - Pattern: `{RANDOM:4}-{RANDOM:4}-{RANDOM:4}-{RANDOM:4}`
   - Expires: When subscription cancelled
   - Max activations: 1
5. Link generator to product

Repeat for Enterprise tier ($99/month).

### 3. Configure Production wp-config.php

Example production configuration:

```php
<?php
/**
 * Production wp-config.php for baursoftware.com
 */

// Database settings
define('DB_NAME', 'production_db');
define('DB_USER', 'production_user');
define('DB_PASSWORD', 'secure_password_here');
define('DB_HOST', 'localhost');

// Security keys (generate at https://api.wordpress.org/secret-key/1.1/salt/)
define('AUTH_KEY',         'put your unique phrase here');
// ... other keys ...

// HubSpot Ecommerce Configuration
define('HUBSPOT_OAUTH_CLIENT_ID', 'your-client-id-here');
define('HUBSPOT_OAUTH_CLIENT_SECRET', 'your-client-secret-here');
define('HUBSPOT_LICENSE_CONSUMER_KEY', 'ck_your_consumer_key_here'); // From Step 2B
define('HUBSPOT_LICENSE_CONSUMER_SECRET', 'cs_your_consumer_secret_here'); // From Step 2B

// WordPress debug settings (disable in production)
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

/* That's all, stop editing! Happy publishing. */
require_once ABSPATH . 'wp-settings.php';
```

### 4. Test the Configuration

#### A. Test OAuth Connection

1. Go to **HubSpot Shop → Connect to HubSpot**
2. Click "Connect to HubSpot"
3. You should be redirected to HubSpot authorization
4. Grant permissions
5. Verify you're redirected back with success message

#### B. Test License Verification

1. Purchase a test subscription on baursoftware.com
2. Copy the license key from confirmation email
3. Go to **HubSpot Shop → License**
4. Enter license key and click "Activate"
5. Verify status changes to "Active" and tier shows "Pro"

---

## Security Checklist

- [ ] All credentials stored in `wp-config.php` (not in plugin code)
- [ ] `wp-config.php` has proper file permissions (640 or 600)
- [ ] `.gitignore` includes `wp-config.php`
- [ ] `hubspot.config.yml` never committed to git (companion app)
- [ ] HTTPS enabled on all sites
- [ ] WordPress admin protected with strong passwords
- [ ] Two-factor authentication enabled for HubSpot Developer account
- [ ] License Manager REST API keys regenerated after testing

---

## Deployment Workflow

### Development → Staging → Production

```bash
# 1. Development (Local)
# - Use fallback credentials in code for testing
# - Or add to local wp-config.php

# 2. Staging
# - Add staging credentials to wp-config.php
# - Test OAuth flow
# - Test license activation
# - Test Pro features

# 3. Production
# - Add production credentials to wp-config.php
# - Deploy via git (wp-config.php stays on server, not in repo)
# - Verify all features work
# - Monitor logs for errors
```

---

## WP Engine Specific Setup

If hosting on WP Engine:

1. Go to WP Engine dashboard → Sites → Your Site → Environment Variables
2. Add variables:
   - `HUBSPOT_OAUTH_CLIENT_ID`
   - `HUBSPOT_OAUTH_CLIENT_SECRET`
   - `HUBSPOT_LICENSE_CONSUMER_KEY`
   - `HUBSPOT_LICENSE_CONSUMER_SECRET`

3. Add to `wp-config.php` (WP Engine auto-loads env vars):

```php
// WP Engine environment variables are automatically available
// No need to call getenv() - just define constants
if (!defined('HUBSPOT_OAUTH_CLIENT_ID')) {
    define('HUBSPOT_OAUTH_CLIENT_ID', getenv('HUBSPOT_OAUTH_CLIENT_ID'));
}
// ... repeat for other variables
```

---

## Troubleshooting

### OAuth Not Working

**Symptom:** "Invalid OAuth state" or redirect fails

**Solutions:**

1. Verify `HUBSPOT_OAUTH_CLIENT_ID` and `HUBSPOT_OAUTH_CLIENT_SECRET` are correct
2. Check redirect URI in HubSpot Developer App matches your site URL
3. Ensure HTTPS is enabled
4. Clear browser cookies and try again

### License Verification Fails

**Symptom:** "Failed to verify license" or stuck on "Free" tier

**Solutions:**

1. Verify `HUBSPOT_LICENSE_CONSUMER_KEY` and `HUBSPOT_LICENSE_CONSUMER_SECRET` are correct
2. Check license server (baursoftware.com) is accessible
3. Verify license key is valid in License Manager admin
4. Check WordPress error logs for API error messages

### Credentials Not Loading

**Symptom:** Plugin still using fallback/placeholder credentials

**Solutions:**

1. Verify constants are defined **before** `wp-settings.php` loads
2. Check for PHP syntax errors in `wp-config.php`
3. Use `var_dump(HUBSPOT_OAUTH_CLIENT_ID)` to verify constant is defined
4. Clear WordPress object cache if using Redis/Memcached

---

## Additional Resources

- **HubSpot Developer Docs:** <https://developers.hubspot.com/docs/api/oauth>
- **License Manager Plugin:** <https://www.licensemanager.at/>
- **WordPress.org Security:** <https://wordpress.org/support/article/hardening-wordpress/>

---

## Support

If you encounter issues:

1. Check WordPress debug logs: `wp-content/debug.log`
2. Enable WP_DEBUG in `wp-config.php` temporarily
3. Contact <support@baursoftware.com> with:
   - Error messages from logs
   - Steps to reproduce
   - WordPress version
   - Plugin version

---

**Last Updated:** 2025-01-24
**Plugin Version:** 1.0.0
