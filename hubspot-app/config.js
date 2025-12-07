/**
 * Configuration for HubSpot Ecommerce App
 *
 * Loads configuration from environment variables (.env file)
 * or falls back to defaults for development.
 */

require('dotenv').config();

module.exports = {
    // WordPress Configuration
    wordpress: {
        adminUrl: process.env.WP_ADMIN_URL || 'https://baur-software.local/wp-admin/',
        ajaxEndpoint: process.env.WP_AJAX_ENDPOINT || '/wp-admin/admin-ajax.php',
        tokenAction: process.env.WP_TOKEN_ACTION || 'get_hubspot_token'
    },

    // HubSpot API Configuration
    hubspot: {
        apiBaseUrl: process.env.HUBSPOT_API_BASE_URL || 'https://api.hubapi.com',
        appBaseUrl: process.env.HUBSPOT_APP_BASE_URL || 'https://app.hubspot.com',
        clientId: process.env.HUBSPOT_CLIENT_ID,
        clientSecret: process.env.HUBSPOT_CLIENT_SECRET
    },

    // OAuth Configuration
    oauth: {
        port: parseInt(process.env.OAUTH_PORT || '3000', 10),
        redirectUri: process.env.OAUTH_REDIRECT_URI || 'http://localhost:3000/callback',
        scopes: [
            'crm.objects.contacts.read',
            'crm.objects.contacts.write',
            'crm.objects.deals.read',
            'crm.objects.deals.write',
            'crm.objects.line_items.read',
            'crm.objects.line_items.write',
            'crm.schemas.line_items.read',
            'crm.objects.invoices.read',
            'crm.objects.invoices.write',
            'e-commerce',
            'oauth'
        ]
    },

    // Required scopes for validation (must match app-hsmeta.json requiredScopes)
    requiredScopes: [
        'crm.objects.contacts.read',
        'crm.objects.contacts.write',
        'crm.objects.deals.read',
        'crm.objects.deals.write',
        'crm.objects.line_items.read',
        'crm.objects.line_items.write',
        'crm.schemas.line_items.read',
        'crm.objects.invoices.read',
        'crm.objects.invoices.write',
        'e-commerce',
        'oauth'
    ],

    // Validate required configuration
    validate() {
        const missing = [];

        if (!this.hubspot.clientId) {
            missing.push('HUBSPOT_CLIENT_ID');
        }
        if (!this.hubspot.clientSecret) {
            missing.push('HUBSPOT_CLIENT_SECRET');
        }

        if (missing.length > 0) {
            throw new Error(
                `Missing required environment variables: ${missing.join(', ')}\n` +
                'Please copy .env.example to .env and fill in your credentials.'
            );
        }

        return true;
    }
};
