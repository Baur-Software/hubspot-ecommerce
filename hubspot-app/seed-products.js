/**
 * Seed HubSpot developer account with test products
 *
 * Usage: node seed-products.js YOUR_ACCESS_TOKEN
 */

const https = require('https');
const config = require('./config');

const ACCESS_TOKEN = process.argv[2];

if (!ACCESS_TOKEN) {
    console.error('Error: Please provide an access token');
    console.error('Usage: node seed-products.js YOUR_ACCESS_TOKEN');
    process.exit(1);
}

const products = [
    {
        name: 'WordPress Hosting - Basic',
        description: 'Entry-level WordPress hosting perfect for small blogs and personal websites',
        price: 9.99,
        hs_sku: 'WP-HOST-BASIC-001',
        hs_product_type: 'service',
        hs_recurring_billing_period: 'monthly'
    },
    {
        name: 'WordPress Hosting - Pro',
        description: 'Professional WordPress hosting with enhanced performance and support',
        price: 29.99,
        hs_sku: 'WP-HOST-PRO-002',
        hs_product_type: 'service',
        hs_recurring_billing_period: 'monthly'
    },
    {
        name: 'WordPress Hosting - Enterprise',
        description: 'Enterprise-grade WordPress hosting with dedicated resources and priority support',
        price: 99.99,
        hs_sku: 'WP-HOST-ENT-003',
        hs_product_type: 'service',
        hs_recurring_billing_period: 'monthly'
    },
    {
        name: 'WordPress Theme - Modern Blog',
        description: 'Beautiful, responsive blog theme with customizable layouts',
        price: 49.00,
        hs_sku: 'WP-THEME-BLOG-001',
        hs_product_type: 'digital_product'
    },
    {
        name: 'WordPress Theme - Business Pro',
        description: 'Professional business theme with advanced features and e-commerce ready',
        price: 79.00,
        hs_sku: 'WP-THEME-BIZ-002',
        hs_product_type: 'digital_product'
    },
    {
        name: 'SEO Plugin - Premium',
        description: 'Advanced SEO plugin with keyword tracking, schema markup, and analytics',
        price: 99.00,
        hs_sku: 'WP-PLUGIN-SEO-001',
        hs_product_type: 'digital_product',
        hs_recurring_billing_period: 'annually'
    },
    {
        name: 'Security Plugin - Pro',
        description: 'Comprehensive WordPress security with malware scanning and firewall',
        price: 149.00,
        hs_sku: 'WP-PLUGIN-SEC-001',
        hs_product_type: 'digital_product',
        hs_recurring_billing_period: 'annually'
    },
    {
        name: 'Website Maintenance Package',
        description: 'Monthly maintenance including updates, backups, and security monitoring',
        price: 199.00,
        hs_sku: 'WP-MAINT-MONTH-001',
        hs_product_type: 'service',
        hs_recurring_billing_period: 'monthly'
    },
    {
        name: 'Custom WordPress Development',
        description: 'Custom plugin or theme development (per hour)',
        price: 150.00,
        hs_sku: 'WP-DEV-HOURLY-001',
        hs_product_type: 'service'
    },
    {
        name: 'WordPress Training Session',
        description: 'One-on-one WordPress training and consultation (2 hours)',
        price: 250.00,
        hs_sku: 'WP-TRAIN-2HR-001',
        hs_product_type: 'service'
    }
];

function createProduct(product) {
    return new Promise((resolve, reject) => {
        const properties = {
            name: product.name,
            description: product.description,
            price: product.price,
            hs_sku: product.hs_sku
        };

        // Add optional properties
        if (product.hs_product_type) {
            properties.hs_product_type = product.hs_product_type;
        }
        if (product.hs_recurring_billing_period) {
            properties.hs_recurring_billing_period = product.hs_recurring_billing_period;
        }

        const data = JSON.stringify({
            properties: properties
        });

        const apiUrl = new URL(`${config.hubspot.apiBaseUrl}/crm/v3/objects/products`);
        const options = {
            hostname: apiUrl.hostname,
            port: 443,
            path: apiUrl.pathname,
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${ACCESS_TOKEN}`,
                'Content-Type': 'application/json',
                'Content-Length': data.length
            }
        };

        const req = https.request(options, (res) => {
            let responseData = '';

            res.on('data', (chunk) => {
                responseData += chunk;
            });

            res.on('end', () => {
                if (res.statusCode === 201) {
                    const result = JSON.parse(responseData);
                    console.log(`✅ Created: ${product.name} (ID: ${result.id})`);
                    resolve(result);
                } else {
                    console.error(`❌ Failed: ${product.name}`);
                    console.error(`   Status: ${res.statusCode}`);
                    console.error(`   Response: ${responseData}`);
                    reject(new Error(`Failed to create ${product.name}`));
                }
            });
        });

        req.on('error', (error) => {
            console.error(`❌ Error creating ${product.name}:`, error.message);
            reject(error);
        });

        req.write(data);
        req.end();
    });
}

async function seedProducts() {
    console.log('🌱 Seeding HubSpot with test products...\n');

    let successCount = 0;
    let failCount = 0;

    for (const product of products) {
        try {
            await createProduct(product);
            successCount++;
            // Small delay to avoid rate limits
            await new Promise(resolve => setTimeout(resolve, 500));
        } catch (error) {
            failCount++;
        }
    }

    console.log('\n========================================');
    console.log(`✅ Success: ${successCount} products created`);
    console.log(`❌ Failed: ${failCount} products`);
    console.log('========================================');
    console.log('\nYou can now sync these products in WordPress!');
}

seedProducts().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
