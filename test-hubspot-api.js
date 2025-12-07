/**
 * Test HubSpot API Connection
 *
 * This script tests the HubSpot API connection and fetches products
 * to verify authentication is working correctly.
 */

const axios = require('axios');

// Read the access token from hubspot.config.yml
const fs = require('fs');
const yaml = require('js-yaml');

const configPath = './hubspot.config.yml';
const config = yaml.load(fs.readFileSync(configPath, 'utf8'));

const portal = config.portals.find(p => p.name === config.defaultPortal);
const accessToken = portal.auth.tokenInfo.accessToken;
const portalId = portal.portalId;

console.log(`\n🔧 Testing HubSpot API Connection`);
console.log(`   Portal: ${portal.name} (ID: ${portalId})`);
console.log(`   Environment: ${portal.env}`);
console.log(`   Auth Type: ${portal.authType}\n`);

// Test 1: Get Products
async function testProductsAPI() {
    console.log('📦 Test 1: Fetching Products...');

    try {
        const response = await axios.get('https://api.hubapi.com/crm/v3/objects/products', {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            params: {
                limit: 10,
                properties: 'name,description,price,hs_sku,hs_product_type,hs_recurring_billing_period'
            }
        });

        console.log(`   ✅ Success! Found ${response.data.results?.length || 0} products`);

        if (response.data.results && response.data.results.length > 0) {
            console.log(`\n   Sample Product:`);
            const product = response.data.results[0];
            console.log(`   - Name: ${product.properties.name}`);
            console.log(`   - Price: $${product.properties.price || 0}`);
            console.log(`   - SKU: ${product.properties.hs_sku || 'N/A'}`);
            console.log(`   - Type: ${product.properties.hs_product_type || 'simple'}`);

            if (product.properties.hs_recurring_billing_period) {
                console.log(`   - Subscription: ${product.properties.hs_recurring_billing_period}`);
            }
        } else {
            console.log(`   ⚠️  No products found in HubSpot account`);
            console.log(`   👉 Create some products in HubSpot to test sync`);
        }

        return true;
    } catch (error) {
        console.log(`   ❌ Error: ${error.response?.data?.message || error.message}`);
        if (error.response?.status === 403) {
            console.log(`   👉 Missing scope: crm.objects.products.read`);
        }
        return false;
    }
}

// Test 2: Get Subscriptions
async function testSubscriptionsAPI() {
    console.log('\n💳 Test 2: Fetching Subscriptions...');

    try {
        const response = await axios.get('https://api.hubapi.com/crm/v3/objects/subscriptions', {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            params: {
                limit: 10
            }
        });

        console.log(`   ✅ Success! Found ${response.data.results?.length || 0} subscriptions`);
        return true;
    } catch (error) {
        if (error.response?.status === 403) {
            console.log(`   ⚠️  Missing scope: crm.objects.subscriptions.read`);
            console.log(`   (This is optional for product sync)`);
        } else {
            console.log(`   ℹ️  ${error.response?.data?.message || error.message}`);
        }
        return false;
    }
}

// Test 3: Get Email Subscription Types
async function testEmailSubscriptionsAPI() {
    console.log('\n📧 Test 3: Fetching Email Subscription Types...');

    try {
        const response = await axios.get('https://api.hubapi.com/communication-preferences/v3/definitions', {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            }
        });

        const types = response.data.subscriptionDefinitions || [];
        console.log(`   ✅ Success! Found ${types.length} email subscription types`);

        if (types.length > 0) {
            const activeTypes = types.filter(t => t.isActive);
            console.log(`   - Active: ${activeTypes.length}`);
        }

        return true;
    } catch (error) {
        if (error.response?.status === 403) {
            console.log(`   ⚠️  Missing scope: communication_preferences.read`);
            console.log(`   (This is optional for email subscriptions)`);
        } else {
            console.log(`   ℹ️  ${error.response?.data?.message || error.message}`);
        }
        return false;
    }
}

// Run all tests
async function runTests() {
    const results = {
        products: await testProductsAPI(),
        subscriptions: await testSubscriptionsAPI(),
        emailSubs: await testEmailSubscriptionsAPI()
    };

    console.log(`\n${'='.repeat(60)}`);
    console.log('📊 Test Results Summary:');
    console.log(`${'='.repeat(60)}`);
    console.log(`   Products API:       ${results.products ? '✅ PASS' : '❌ FAIL'}`);
    console.log(`   Subscriptions API:  ${results.subscriptions ? '✅ PASS' : '⚠️  WARN'}`);
    console.log(`   Email Subs API:     ${results.emailSubs ? '✅ PASS' : '⚠️  WARN'}`);
    console.log(`${'='.repeat(60)}\n`);

    if (results.products) {
        console.log('✨ Ready to sync products to WordPress!\n');
    } else {
        console.log('⚠️  Products API test failed - check scopes and try again\n');
    }
}

runTests().catch(console.error);
