#!/usr/bin/env node

/**
 * Check what scopes are actually granted to an access token
 */

const https = require('https');
const config = require('./config');

const ACCESS_TOKEN = process.argv[2];

if (!ACCESS_TOKEN) {
    console.error('❌ Error: Please provide access token as argument');
    console.log('Usage: node check-token-scopes.js <access_token>');
    process.exit(1);
}

console.log('🔍 Checking Access Token Scopes\n');

// Get token info from HubSpot
const apiUrl = new URL(`${config.hubspot.apiBaseUrl}/oauth/v1/access-tokens/${ACCESS_TOKEN}`);
const options = {
    hostname: apiUrl.hostname,
    path: apiUrl.pathname,
    method: 'GET',
    headers: {
        'Content-Type': 'application/json'
    }
};

const req = https.request(options, (res) => {
    let data = '';

    res.on('data', (chunk) => {
        data += chunk;
    });

    res.on('end', () => {
        try {
            const parsed = JSON.parse(data);

            if (res.statusCode === 200) {
                console.log('✅ Token Information:\n');
                console.log('Hub ID (Portal ID):', parsed.hub_id);
                console.log('App ID:', parsed.app_id);
                console.log('User ID:', parsed.user_id);
                console.log('Token Type:', parsed.token_type);
                console.log('\n📋 Granted Scopes:');
                console.log('='.repeat(60));

                if (parsed.scopes && parsed.scopes.length > 0) {
                    parsed.scopes.sort().forEach((scope, index) => {
                        console.log(`${(index + 1).toString().padStart(2)}. ${scope}`);
                    });
                    console.log('='.repeat(60));
                    console.log(`\nTotal: ${parsed.scopes.length} scopes\n`);
                } else {
                    console.log('⚠️  No scopes found in token\n');
                }

                // Check for specific scopes we need
                console.log('🔎 Checking Required Scopes:');
                console.log('='.repeat(60));

                const requiredScopes = config.requiredScopes;

                requiredScopes.forEach(scope => {
                    const hasScope = parsed.scopes && parsed.scopes.includes(scope);
                    const icon = hasScope ? '✅' : '❌';
                    console.log(`${icon} ${scope}`);
                });

                console.log('='.repeat(60) + '\n');

            } else {
                console.log('❌ Error getting token info:');
                console.log('Status:', res.statusCode);
                console.log('Response:', parsed);
            }
        } catch (e) {
            console.error('❌ Failed to parse response:', e.message);
            console.log('Raw response:', data);
        }
    });
});

req.on('error', (error) => {
    console.error('❌ Request failed:', error.message);
});

req.end();
