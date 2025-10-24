<?php
/**
 * Seed Test Data
 *
 * Run this script to populate your test environment with sample data
 *
 * Usage: wp eval-file tests/setup/seed-data.php
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
    require_once ABSPATH . 'wp-load.php';
}

echo "=== HubSpot Ecommerce Test Data Seeder ===\n\n";

// 1. Create test customer user
echo "1. Creating test customer...\n";

$test_user = get_user_by('email', 'test@example.com');

if ($test_user) {
    echo "   ✓ Test customer already exists (ID: {$test_user->ID})\n";
} else {
    $user_id = wp_create_user('testcustomer', 'testpass123', 'test@example.com');

    if (!is_wp_error($user_id)) {
        wp_update_user([
            'ID' => $user_id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'role' => 'customer',
        ]);

        // Add user meta
        update_user_meta($user_id, 'billing_phone', '555-0100');
        update_user_meta($user_id, 'billing_address_1', '123 Test Street');
        update_user_meta($user_id, 'billing_city', 'Test City');
        update_user_meta($user_id, 'billing_state', 'CA');
        update_user_meta($user_id, 'billing_postcode', '90210');
        update_user_meta($user_id, 'billing_country', 'US');

        echo "   ✓ Test customer created (ID: $user_id)\n";
        echo "     Email: test@example.com\n";
        echo "     Password: testpass123\n";
    } else {
        echo "   ✗ Failed to create test customer: " . $user_id->get_error_message() . "\n";
    }
}

// 2. Sync products from HubSpot
echo "\n2. Syncing products from HubSpot...\n";

if (class_exists('HubSpot_Ecommerce_Product_Manager')) {
    $product_manager = HubSpot_Ecommerce_Product_Manager::instance();
    $result = $product_manager->sync_products();

    if (isset($result['synced'])) {
        echo "   ✓ Synced {$result['synced']} products\n";

        if (!empty($result['errors'])) {
            echo "   ⚠ Errors encountered:\n";
            foreach ($result['errors'] as $error) {
                echo "     - $error\n";
            }
        }
    } else {
        echo "   ✗ Sync failed\n";
    }
} else {
    echo "   ✗ Product Manager class not found\n";
}

// 3. Create test pages if they don't exist
echo "\n3. Creating test pages...\n";

$pages = [
    'shop' => [
        'title' => 'Shop',
        'content' => '[hubspot_products]',
    ],
    'cart' => [
        'title' => 'Cart',
        'content' => '[hubspot_cart]',
    ],
    'checkout' => [
        'title' => 'Checkout',
        'content' => '[hubspot_checkout]',
    ],
    'my-account' => [
        'title' => 'My Account',
        'content' => '[hubspot_account]',
    ],
];

foreach ($pages as $slug => $page_data) {
    $existing_page = get_page_by_path($slug);

    if ($existing_page) {
        echo "   ✓ Page '$slug' already exists (ID: {$existing_page->ID})\n";
    } else {
        $page_id = wp_insert_post([
            'post_title' => $page_data['title'],
            'post_content' => $page_data['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $slug,
        ]);

        if (!is_wp_error($page_id)) {
            echo "   ✓ Created page '$slug' (ID: $page_id)\n";

            // Update plugin settings with page IDs
            if ($slug === 'shop') {
                update_option('hubspot_ecommerce_shop_page', $page_id);
            } elseif ($slug === 'cart') {
                update_option('hubspot_ecommerce_cart_page', $page_id);
            } elseif ($slug === 'checkout') {
                update_option('hubspot_ecommerce_checkout_page', $page_id);
            } elseif ($slug === 'my-account') {
                update_option('hubspot_ecommerce_account_page', $page_id);
            }
        } else {
            echo "   ✗ Failed to create page '$slug': " . $page_id->get_error_message() . "\n";
        }
    }
}

// 4. Verify plugin settings
echo "\n4. Verifying plugin settings...\n";

$api_key = get_option('hubspot_ecommerce_api_key');
if (empty($api_key)) {
    echo "   ⚠ HubSpot API key not set\n";
    echo "     Please configure in: WP Admin → HubSpot Shop → Settings\n";
} else {
    echo "   ✓ HubSpot API key is configured\n";
}

// 5. Summary
echo "\n=== Setup Complete ===\n\n";
echo "Test Environment Ready!\n\n";
echo "Next steps:\n";
echo "1. Verify products are synced: wp-admin/edit.php?post_type=hs_product\n";
echo "2. Visit shop page: /shop\n";
echo "3. Run Playwright tests: npm test\n\n";
echo "Test Credentials:\n";
echo "- Admin: admin / admin\n";
echo "- Customer: testcustomer / testpass123\n";
echo "- Email: test@example.com\n\n";
