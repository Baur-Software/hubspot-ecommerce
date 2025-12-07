/**
 * Test HubSpot App API Access
 *
 * This tests the app's ability to access the Products API with the e-commerce scope.
 *
 * To use:
 * 1. Install the app in your test account
 * 2. Get the access token from the Auth tab
 * 3. Run: node test-app-api.js YOUR_ACCESS_TOKEN
 */

const axios = require('axios');
const config = require('./config');

const accessToken = process.argv[2];

if (!accessToken) {
    console.error('\n❌ Error: Access token required');
    console.error('Usage: node test-app-api.js YOUR_ACCESS_TOKEN\n');
    process.exit(1);
}

console.log(`\n🔧 Testing HubSpot App API Access`);
console.log(`   App: Baur Software HubSpot Ecommerce for WordPress\n`);

// Test 1: Products API with e-commerce scope
async function testProductsAPI() {
    console.log('📦 Test 1: Fetching Products (e-commerce scope)...');

    try {
        const response = await axios.get(`${config.hubspot.apiBaseUrl}/crm/v3/objects/products`, {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            params: {
                limit: 10,
                properties: 'name,description,price,hs_sku,hs_product_type,hs_recurring_billing_period,recurringbillingfrequency,hs_billing_period_units'
            }
        });

        const products = response.data.results || [];
        console.log(`   ✅ Success! Found ${products.length} products`);

        if (products.length > 0) {
            console.log(`\n   📋 Sample Products:`);
            products.slice(0, 3).forEach((product, index) => {
                console.log(`   ${index + 1}. ${product.properties.name || 'Unnamed Product'}`);
                console.log(`      - ID: ${product.id}`);
                console.log(`      - Price: $${product.properties.price || 0}`);
                console.log(`      - SKU: ${product.properties.hs_sku || 'N/A'}`);
                console.log(`      - Type: ${product.properties.hs_product_type || 'simple'}`);

                if (product.properties.hs_recurring_billing_period) {
                    console.log(`      - 💳 Subscription: ${product.properties.hs_recurring_billing_period}`);
                    console.log(`      - Frequency: ${product.properties.recurringbillingfrequency || 'N/A'}`);
                }
                console.log('');
            });
        } else {
            console.log(`   ℹ️  No products found. Create some in HubSpot to test sync.`);
        }

        return { success: true, count: products.length };
    } catch (error) {
        console.log(`   ❌ Error: ${error.response?.data?.message || error.message}`);

        if (error.response?.status === 403) {
            console.log(`   👉 Scope issue: Make sure 'e-commerce' scope is authorized`);
        } else if (error.response?.status === 401) {
            console.log(`   👉 Auth issue: Access token may be invalid or expired`);
        }

        return { success: false, error: error.message };
    }
}

// Test 2: Line Items API
async function testLineItemsAPI() {
    console.log('📝 Test 2: Fetching Line Items...');

    try {
        const response = await axios.get(`${config.hubspot.apiBaseUrl}/crm/v3/objects/line_items`, {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            params: {
                limit: 5
            }
        });

        console.log(`   ✅ Success! Found ${response.data.results?.length || 0} line items`);
        return { success: true };
    } catch (error) {
        if (error.response?.status === 403) {
            console.log(`   ⚠️  Missing scope: crm.objects.line_items.read`);
        } else {
            console.log(`   ℹ️  ${error.response?.data?.message || error.message}`);
        }
        return { success: false };
    }
}

// Test 3: Contacts API
async function testContactsAPI() {
    console.log('\n👤 Test 3: Fetching Contacts...');

    try {
        const response = await axios.get(`${config.hubspot.apiBaseUrl}/crm/v3/objects/contacts`, {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            params: {
                limit: 5
            }
        });

        console.log(`   ✅ Success! Found ${response.data.results?.length || 0} contacts`);
        return { success: true };
    } catch (error) {
        if (error.response?.status === 403) {
            console.log(`   ⚠️  Missing scope: crm.objects.contacts.read`);
        } else {
            console.log(`   ℹ️  ${error.response?.data?.message || error.message}`);
        }
        return { success: false };
    }
}

// Test 4: Deals API
async function testDealsAPI() {
    console.log('\n💰 Test 4: Fetching Deals...');

    try {
        const response = await axios.get(`${config.hubspot.apiBaseUrl}/crm/v3/objects/deals`, {
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            },
            params: {
                limit: 5
            }
        });

        console.log(`   ✅ Success! Found ${response.data.results?.length || 0} deals`);
        return { success: true };
    } catch (error) {
        if (error.response?.status === 403) {
            console.log(`   ⚠️  Missing scope: crm.objects.deals.read`);
        } else {
            console.log(`   ℹ️  ${error.response?.data?.message || error.message}`);
        }
        return { success: false };
    }
}

// Run all tests
async function runTests() {
    const results = {
        products: await testProductsAPI(),
        lineItems: await testLineItemsAPI(),
        contacts: await testContactsAPI(),
        deals: await testDealsAPI()
    };

    console.log(`\n${'='.repeat(60)}`);
    console.log('📊 Test Results Summary:');
    console.log(`${'='.repeat(60)}`);
    console.log(`   Products API (e-commerce):     ${results.products.success ? '✅ PASS' : '❌ FAIL'}`);
    console.log(`   Line Items API:                ${results.lineItems.success ? '✅ PASS' : '⚠️  WARN'}`);
    console.log(`   Contacts API:                  ${results.contacts.success ? '✅ PASS' : '⚠️  WARN'}`);
    console.log(`   Deals API:                     ${results.deals.success ? '✅ PASS' : '⚠️  WARN'}`);
    console.log(`${'='.repeat(60)}\n`);

    if (results.products.success) {
        console.log('✨ Great! The e-commerce scope is working!');
        console.log('🚀 You can now sync products to WordPress.\n');

        if (results.products.count > 0) {
            console.log('📦 Next steps:');
            console.log('   1. Update WordPress plugin with OAuth credentials');
            console.log('   2. Implement OAuth flow in WordPress');
            console.log('   3. Test product sync from HubSpot to WordPress\n');
        } else {
            console.log('💡 Tip: Create some products in HubSpot to test the full sync flow.\n');
        }
    } else {
        console.log('⚠️  Products API access failed.');
        console.log('   Make sure the app is installed in your account and');
        console.log('   the e-commerce scope is authorized.\n');
    }
}

runTests().catch(console.error);
