<?php
/**
 * Example wp-config.php Configuration for HubSpot Ecommerce Plugin
 *
 * Add these lines to your wp-config.php file ABOVE the line that says:
 * "That's all, stop editing! Happy publishing."
 *
 * SECURITY WARNING: Never commit wp-config.php to version control!
 */

// ==============================================================================
// HubSpot Ecommerce Plugin Configuration
// ==============================================================================

/**
 * OAuth Credentials
 *
 * Get these from your HubSpot Developer App:
 * https://developers.hubspot.com/
 *
 * Development values (can be used for testing):
 * - Client ID: b4cf1036-14c9-4e46-a976-be06e31f2a78
 * - Client Secret: 8651ec90-6c28-41a4-8c36-82c9f5694936
 *
 * For production, these are already set as fallbacks in the code,
 * but you can override them here if you create your own HubSpot app.
 */
define('HUBSPOT_OAUTH_CLIENT_ID', 'b4cf1036-14c9-4e46-a976-be06e31f2a78');
define('HUBSPOT_OAUTH_CLIENT_SECRET', '8651ec90-6c28-41a4-8c36-82c9f5694936');

/**
 * License Server Credentials
 *
 * REQUIRED: Get these from baursoftware.com → License Manager → Settings → REST API
 *
 * Steps to obtain:
 * 1. Log into baursoftware.com WordPress admin
 * 2. Go to License Manager → Settings → REST API
 * 3. Click "Add Key"
 * 4. Description: "HubSpot Ecommerce Plugin"
 * 5. Permissions: Read/Write
 * 6. Click "Generate API Key"
 * 7. Copy the Consumer Key (ck_...) and Consumer Secret (cs_...)
 * 8. Replace the XXXXXXXX values below
 */
define('HUBSPOT_LICENSE_CONSUMER_KEY', 'ck_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');
define('HUBSPOT_LICENSE_CONSUMER_SECRET', 'cs_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

// ==============================================================================
// End HubSpot Ecommerce Configuration
// ==============================================================================

/**
 * Alternative: Load from Environment Variables (WP Engine, Kinsta, etc.)
 *
 * If your hosting provider supports environment variables, you can use this instead:
 */

/*
// Load from environment variables
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
*/

/**
 * Notes:
 *
 * 1. Development/Testing:
 *    - OAuth credentials have working fallbacks in the code
 *    - License credentials are placeholders - Pro features won't work until configured
 *
 * 2. Production:
 *    - OAuth credentials work as-is (using fallback values)
 *    - License credentials MUST be configured for Pro tier features
 *
 * 3. Security:
 *    - Never commit this file or wp-config.php to version control
 *    - Set file permissions to 600 or 640
 *    - Regenerate keys if accidentally exposed
 */
