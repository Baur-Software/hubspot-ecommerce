#!/usr/bin/env node

/**
 * Test all HubSpot API endpoints with OAuth token
 * This verifies that all required scopes are working
 */

const https = require('https');

// Get access token from command line
const ACCESS_TOKEN = process.argv[2];

if (!ACCESS_TOKEN) {
    console.error('❌ Error: Please provide access token as argument');
    console.log('Usage: node test-all-scopes.js <access_token>');
    process.exit(1);
}

console.log('🔍 Testing HubSpot API Scopes\n');
console.log('Access Token:', ACCESS_TOKEN.substring(0, 20) + '...\n');

// Helper function to make API requests
function makeRequest(endpoint, label) {
    return new Promise((resolve, reject) => {
        const options = {
            hostname: 'api.hubapi.com',
            path: endpoint,
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${ACCESS_TOKEN}`,
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
                        console.log(`✅ ${label}`);
                        console.log(`   Status: ${res.statusCode}`);
                        if (parsed.results) {
                            console.log(`   Results: ${parsed.results.length} items found`);
                        } else if (parsed.total !== undefined) {
                            console.log(`   Total: ${parsed.total}`);
                        }
                        console.log('');
                        resolve({ success: true, label, data: parsed });
                    } else {
                        console.log(`❌ ${label}`);
                        console.log(`   Status: ${res.statusCode}`);
                        console.log(`   Error: ${parsed.message || parsed.error || 'Unknown error'}`);
                        if (parsed.category) {
                            console.log(`   Category: ${parsed.category}`);
                        }
                        console.log('');
                        resolve({ success: false, label, error: parsed });
                    }
                } catch (e) {
                    console.log(`❌ ${label}`);
                    console.log(`   Status: ${res.statusCode}`);
                    console.log(`   Error: Failed to parse response`);
                    console.log(`   Raw: ${data.substring(0, 100)}`);
                    console.log('');
                    resolve({ success: false, label, error: e.message });
                }
            });
        });

        req.on('error', (error) => {
            console.log(`❌ ${label}`);
            console.log(`   Error: ${error.message}`);
            console.log('');
            resolve({ success: false, label, error: error.message });
        });

        req.end();
    });
}

// Test all endpoints
async function testAllScopes() {
    const tests = [
        // Products API (e-commerce scope)
        {
            endpoint: '/crm/v3/objects/products?limit=5&properties=name,price,hs_sku',
            label: 'Products API (e-commerce scope)'
        },

        // Subscriptions API (e-commerce scope)
        {
            endpoint: '/crm/v3/objects/subscriptions?limit=5',
            label: 'Subscriptions API (e-commerce scope)'
        },

        // Invoices API (e-commerce scope)
        {
            endpoint: '/crm/v3/objects/invoices?limit=5',
            label: 'Invoices API (e-commerce scope)'
        },

        // Line Items API
        {
            endpoint: '/crm/v3/objects/line_items?limit=5',
            label: 'Line Items API (crm.objects.line_items.read)'
        },

        // Contacts API
        {
            endpoint: '/crm/v3/objects/contacts?limit=5&properties=email,firstname,lastname',
            label: 'Contacts API (crm.objects.contacts.read)'
        },

        // Deals API
        {
            endpoint: '/crm/v3/objects/deals?limit=5&properties=dealname,amount,dealstage',
            label: 'Deals API (crm.objects.deals.read)'
        },

        // Email Subscription Definitions (optional scope)
        {
            endpoint: '/communication-preferences/v4/definitions',
            label: 'Email Subscription Definitions (communication_preferences.read)'
        },
    ];

    const results = [];

    for (const test of tests) {
        const result = await makeRequest(test.endpoint, test.label);
        results.push(result);

        // Wait a bit between requests to avoid rate limiting
        await new Promise(resolve => setTimeout(resolve, 200));
    }

    // Summary
    console.log('\n' + '='.repeat(60));
    console.log('SUMMARY');
    console.log('='.repeat(60));

    const successful = results.filter(r => r.success).length;
    const failed = results.filter(r => !r.success).length;

    console.log(`\n✅ Successful: ${successful}/${results.length}`);
    console.log(`❌ Failed: ${failed}/${results.length}\n`);

    if (failed > 0) {
        console.log('Failed Tests:');
        results.filter(r => !r.success).forEach(r => {
            console.log(`  - ${r.label}`);
            if (r.error && r.error.message) {
                console.log(`    ${r.error.message}`);
            }
        });
        console.log('');
    }

    // Check critical scopes
    const criticalTests = [
        'Products API (e-commerce scope)',
        'Subscriptions API (e-commerce scope)',
        'Contacts API (crm.objects.contacts.read)',
        'Deals API (crm.objects.deals.read)',
        'Line Items API (crm.objects.line_items.read)'
    ];

    const criticalResults = results.filter(r => criticalTests.includes(r.label));
    const allCriticalPassed = criticalResults.every(r => r.success);

    console.log('Critical Scopes Status:');
    if (allCriticalPassed) {
        console.log('✅ All critical scopes working! Your OAuth app is ready.\n');
    } else {
        console.log('❌ Some critical scopes are missing. Review scope configuration.\n');
        console.log('Note: If your test account has no data, some APIs may return');
        console.log('empty results but still work (status 200 with 0 results).\n');
    }
}

// Run tests
testAllScopes().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
