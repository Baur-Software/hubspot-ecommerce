# SOC2 Compliance Guide

This document outlines the security measures implemented in the HubSpot Ecommerce plugin to support SOC2 compliance requirements.

## Overview

The HubSpot Ecommerce plugin handles sensitive credentials for two external services:
1. **HubSpot OAuth** - Client ID and Client Secret for HubSpot API access
2. **License Server** - Consumer Key and Secret for license verification API

All credentials are stored securely following industry best practices.

---

## Credentials Storage (CC1.2 - Common Criteria Related to Confidentiality)

### Architecture

The plugin implements a secure credentials management system:

```
Priority Order:
1. wp-config.php constants (RECOMMENDED for production)
2. Environment variables via getenv() (hosting platforms)
3. Fallback values (development only - placeholders for license server)
```

### Implementation Details

#### License Server Credentials

**File:** `wordpress/includes/class-license-manager.php:28-45`

```php
private function get_consumer_key() {
    if (defined('HUBSPOT_LICENSE_CONSUMER_KEY')) {
        return HUBSPOT_LICENSE_CONSUMER_KEY;
    }
    // Placeholder - will not work until configured
    return 'ck_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
}

private function get_consumer_secret() {
    if (defined('HUBSPOT_LICENSE_CONSUMER_SECRET')) {
        return HUBSPOT_LICENSE_CONSUMER_SECRET;
    }
    // Placeholder - will not work until configured
    return 'cs_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
}
```

Placeholder credentials intentionally use invalid formats that will fail API authentication, ensuring production systems cannot operate without proper configuration.

#### OAuth Credentials

**File:** `wordpress/includes/class-oauth-client.php:25-44`

```php
private function get_client_id() {
    if (defined('HUBSPOT_OAUTH_CLIENT_ID')) {
        return HUBSPOT_OAUTH_CLIENT_ID;
    }
    // No fallback - must be configured in wp-config.php
    error_log('HubSpot Ecommerce: HUBSPOT_OAUTH_CLIENT_ID not defined');
    return null;
}

private function get_client_secret() {
    if (defined('HUBSPOT_OAUTH_CLIENT_SECRET')) {
        return HUBSPOT_OAUTH_CLIENT_SECRET;
    }
    // No fallback - must be configured in wp-config.php
    error_log('HubSpot Ecommerce: HUBSPOT_OAUTH_CLIENT_SECRET not defined');
    return null;
}
```

OAuth credentials include working fallback values for development. Production deployments SHOULD override these in wp-config.php for credential rotation and isolation.

---

## Secure Configuration (CC3.1 - Risk Assessment)

### Production wp-config.php Setup

Add these constants to `wp-config.php` **above** the line `/* That's all, stop editing! */`:

```php
/**
 * HubSpot Ecommerce Plugin - SOC2 Compliant Configuration
 */

// OAuth Credentials (from HubSpot Developer Portal)
// ROTATE QUARTERLY or when personnel with access changes
define('HUBSPOT_OAUTH_CLIENT_ID', 'your-production-client-id');
define('HUBSPOT_OAUTH_CLIENT_SECRET', 'your-production-client-secret');

// License Server Credentials (from baursoftware.com License Manager)
// ROTATE QUARTERLY or when personnel with access changes
define('HUBSPOT_LICENSE_CONSUMER_KEY', 'ck_your_production_consumer_key');
define('HUBSPOT_LICENSE_CONSUMER_SECRET', 'cs_your_production_consumer_secret');
```

### File Permissions

**Linux/macOS:**
```bash
# Recommended: Owner read/write only
chmod 600 wp-config.php
chown www-data:www-data wp-config.php

# Alternative: Owner read/write, group read
chmod 640 wp-config.php
chown www-data:www-data wp-config.php
```

**Windows:**
Use File Properties → Security tab to ensure only:
- SYSTEM (Full Control)
- Administrators (Full Control)
- Web server user (Read)

**Verification:**
```bash
# Linux/macOS
ls -l wp-config.php
# Should show: -rw------- or -rw-r-----

# Windows (PowerShell)
Get-Acl wp-config.php | Format-List
```

---

## Error Handling (CC7.2 - System Monitoring)

### Non-Disclosure of Credentials

The plugin implements secure error handling that never exposes credentials:

**License Verification Error Handling (lines 176-192):**

```php
if (is_wp_error($response)) {
    error_log('HubSpot Ecommerce: License verification failed - ' . $response->get_error_message());
    return false;
}

if ($status_code !== 200 || empty($data['success'])) {
    $error_msg = $data['data']['message'] ?? 'Unknown error';
    error_log('HubSpot Ecommerce: License validation failed - ' . $error_msg);

    $this->tier = 'free';
    $this->status = 'invalid';
    $this->save_cached_status();
    return false;
}
```

Error messages are:
- Logged to WordPress error log (server-side only)
- Never displayed to end users
- Sanitized to remove credential information
- Generic enough to not expose system architecture

**Activation Error Handling (lines 246-270):**

```php
if (is_wp_error($response)) {
    return $response; // WordPress error object (no credentials)
}

if ($status_code === 200 && !empty($data['success'])) {
    // Success path
} else {
    return new WP_Error(
        'activation_failed',
        $data['data']['message'] ?? __('License activation failed', 'hubspot-ecommerce')
    );
}
```

### Graceful Degradation

When credentials are missing or invalid:
- Plugin continues to function in FREE tier mode
- No PHP fatal errors or warnings
- Users see upgrade prompts instead of errors
- System fails closed (secure by default)

---

## Access Controls (CC6.1 - Logical and Physical Access Controls)

### WordPress Capability Checks

All sensitive operations require `manage_options` capability:

**License Management (line 314):**
```php
public function handle_license_actions() {
    if (!current_user_can('manage_options')) {
        return;
    }
    // ... license activation/deactivation logic
}
```

**Nonce Verification (lines 319, 352):**
```php
if (isset($_POST['activate_license']) && check_admin_referer('hubspot_activate_license', 'hubspot_license_nonce')) {
    // Process activation
}

if (isset($_POST['deactivate_license']) && check_admin_referer('hubspot_deactivate_license', 'hubspot_license_nonce')) {
    // Process deactivation
}
```

### Input Sanitization

All user input is sanitized:

```php
$license_key = sanitize_text_field($_POST['license_key'] ?? '');
```

---

## Monitoring & Logging (CC7.3 - System Operations)

### What Gets Logged

**Successful Operations:**
- License activations (with obfuscated keys: `****-****-****-1234`)
- OAuth connections (with portal ID only)
- License verification successes

**Failed Operations:**
- License verification failures (without credentials)
- OAuth authentication errors (generic messages)
- API connectivity issues (without showing auth headers)

### What NEVER Gets Logged

- Consumer keys or secrets
- OAuth client secrets
- Full license keys
- Authentication headers
- API request bodies containing credentials

### Example Secure Logging

```php
// GOOD: Generic error message
error_log('HubSpot Ecommerce: License verification failed - Connection timeout');

// BAD: Would expose credentials (NOT DONE)
// error_log('License verification failed with key: ck_abc123...');
```

---

## Version Control Exclusions (CC6.3 - Change Management)

### Excluded from Git

The following files MUST NEVER be committed:

1. `wp-config.php` - Contains all credentials
2. `.env` files - Contains environment variables
3. `hubspot.config.yml` - Contains HubSpot app secrets

**Repository .gitignore:**
```gitignore
# Credentials and secrets
.env
hubspot.config.yml
hubspot-app/.env

# WordPress config (not in repo, but documented for clarity)
# wp-config.php is in WordPress installation, not plugin repository
```

### Verification Commands

```bash
# Check that no credentials are in git history
git log -p | grep -i "consumer_key\|client_secret\|ck_\|cs_" && echo "WARNING: Credentials found in git!" || echo "OK: No credentials in git"

# Check current working tree
git ls-files | xargs grep -i "ck_[a-f0-9]\{40\}" && echo "WARNING: Consumer keys in files!" || echo "OK: No keys found"
```

---

## API Communication Security (CC6.6 - Encryption)

### Transport Security

All API communications use HTTPS with TLS 1.2+:

**License Manager API:**
```php
const API_URL = 'https://baursoftware.com/wp-json/lmfwc/v2/licenses';
```

**HubSpot API:**
```php
const OAUTH_BASE = 'https://app.hubspot.com/oauth';
const TOKEN_ENDPOINT = 'https://api.hubapi.com/oauth/v1/token';
```

### Authentication Methods

**License Server (HTTP Basic Auth):**
```php
'headers' => [
    'Content-Type' => 'application/json',
    'Authorization' => 'Basic ' . base64_encode(
        $this->get_consumer_key() . ':' . $this->get_consumer_secret()
    ),
]
```

**HubSpot OAuth (Bearer Token):**
```php
'headers' => [
    'Authorization' => 'Bearer ' . $access_token,
    'Content-Type' => 'application/json',
]
```

### Timeout Configuration

All API requests have reasonable timeouts to prevent resource exhaustion:

```php
'timeout' => 15, // 15 seconds
```

---

## Credential Rotation Procedure (CC6.2 - Logical Access)

### Quarterly Rotation Schedule

**License Server Credentials:**

1. Generate new REST API key in License Manager:
   - Go to: License Manager → Settings → REST API
   - Click "Revoke" on old key
   - Click "Add Key" for new credentials
   - Update wp-config.php with new `ck_` and `cs_` values

2. Test license activation:
   ```bash
   # Use WP-CLI to verify
   wp option get hubspot_ecommerce_license_status
   ```

3. Decommission old credentials after 24-hour grace period

**OAuth Credentials:**

1. Regenerate in HubSpot Developer Portal:
   - Go to: App Settings → Auth
   - Click "Rotate client secret"
   - Copy new secret immediately

2. Update wp-config.php:
   ```php
   define('HUBSPOT_OAUTH_CLIENT_SECRET', 'new-secret-here');
   ```

3. Notify affected users to reconnect OAuth (if client ID changed)

### Incident Response (Credential Compromise)

If credentials are exposed:

1. **Immediate Actions (within 1 hour):**
   - Rotate compromised credentials immediately
   - Revoke all affected API keys
   - Clear WordPress transient caches
   - Audit access logs for unauthorized usage

2. **Investigation (within 24 hours):**
   - Review git history for credential commits
   - Check error logs for unauthorized API calls
   - Verify no data exfiltration occurred
   - Document incident timeline

3. **Remediation (within 48 hours):**
   - Update all production systems with new credentials
   - Implement additional access controls if needed
   - Notify affected customers (if applicable)
   - Update incident response documentation

4. **Post-Incident (within 1 week):**
   - Conduct root cause analysis
   - Update security training materials
   - Implement preventive controls
   - Schedule follow-up audit

---

## Testing & Validation (CC8.1 - Change Management)

### Pre-Production Testing Checklist

Before deploying to production:

- [ ] Verify placeholder credentials fail gracefully (return to FREE tier)
- [ ] Verify real credentials work in staging environment
- [ ] Test license activation with valid key
- [ ] Test license activation with invalid key (should fail gracefully)
- [ ] Verify Pro features unlock after successful activation
- [ ] Test OAuth connection flow end-to-end
- [ ] Verify error messages don't expose credentials
- [ ] Check WordPress debug.log for credential leaks
- [ ] Confirm wp-config.php has correct permissions
- [ ] Validate HTTPS is enforced on all API endpoints

### Automated Security Testing

```bash
# Run security checks
npm run security:check

# Check for hardcoded secrets
npm run secrets:scan

# Validate wp-config.php is gitignored
git check-ignore -v wp-config.php
```

---

## Production Deployment (CC8.1 - Change Management)

### Deployment Steps

1. **Prepare Production wp-config.php:**
   ```bash
   # SSH to production server
   cd /var/www/html
   nano wp-config.php
   # Add credentials from secure password manager
   ```

2. **Set File Permissions:**
   ```bash
   chmod 600 wp-config.php
   chown www-data:www-data wp-config.php
   ```

3. **Deploy Plugin:**
   ```bash
   # From repository
   git pull origin main
   cd wordpress
   composer install --no-dev
   ```

4. **Verify Configuration:**
   ```bash
   wp eval "echo defined('HUBSPOT_LICENSE_CONSUMER_KEY') ? 'OK' : 'MISSING';"
   wp eval "echo defined('HUBSPOT_OAUTH_CLIENT_ID') ? 'OK' : 'MISSING';"
   ```

5. **Test Functionality:**
   ```bash
   # Test license verification
   wp option get hubspot_ecommerce_license_status

   # Test OAuth connection (manual browser test required)
   # Navigate to: HubSpot Shop → Connect to HubSpot
   ```

6. **Monitor Logs:**
   ```bash
   tail -f wp-content/debug.log | grep "HubSpot Ecommerce"
   ```

---

## Compliance Evidence Collection

### Documents to Maintain

1. **Access Logs:**
   - Who accessed production wp-config.php (when, why)
   - Credential rotation dates and personnel
   - Failed authentication attempts

2. **Configuration Snapshots:**
   - Hash of wp-config.php (not contents) at each change
   - Changelog with dates and authorized personnel
   - Version control tags for plugin releases

3. **Security Audits:**
   - Quarterly reviews of credential management
   - Penetration testing reports (if applicable)
   - Vulnerability scan results

4. **Training Records:**
   - Personnel trained on secure credential management
   - Dates of security awareness training
   - Acknowledgment of security policies

### Sample Evidence Collection Script

```bash
#!/bin/bash
# evidence-collection.sh - Run quarterly

echo "=== SOC2 Evidence Collection $(date) ===" >> compliance-log.txt

# File permissions audit
echo "wp-config.php permissions:" >> compliance-log.txt
ls -l wp-config.php >> compliance-log.txt

# Credential rotation check
echo "Last rotation dates:" >> compliance-log.txt
git log -1 --grep="credential rotation" --format="%ai %s" >> compliance-log.txt

# Git history check for leaks
echo "Scanning for credential leaks..." >> compliance-log.txt
git log --all --full-history --grep="consumer.key\|client.secret" >> compliance-log.txt

# Access logs
echo "Recent wp-config.php modifications:" >> compliance-log.txt
stat wp-config.php >> compliance-log.txt
```

---

## Contact & Support

**Security Issues:**
- Email: security@baursoftware.com
- PGP Key: Available at https://baursoftware.com/pgp-key.txt

**Compliance Questions:**
- Email: compliance@baursoftware.com

**Emergency Contact (Credential Compromise):**
- Phone: [REDACTED]
- Available: 24/7

---

**Document Version:** 1.0
**Last Updated:** 2025-01-24
**Next Review:** 2025-04-24
**Owner:** Security Team, Baur Software
