<?php
/**
 * Extract HubSpot OAuth access token from WordPress
 *
 * Usage: Place this file in the WordPress root and visit it in browser
 *        OR run: php get-wp-token.php
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../Local Sites/baur-software/app/public/wp-load.php');

$access_token = get_option('hubspot_oauth_access_token');
$portal_id = get_option('hubspot_oauth_portal_id');
$expires_at = get_option('hubspot_oauth_expires_at');

if ($access_token) {
    $is_expired = time() >= $expires_at;

    echo "========================================\n";
    echo "HubSpot OAuth Token Info\n";
    echo "========================================\n\n";
    echo "Portal ID: " . $portal_id . "\n";
    echo "Token Status: " . ($is_expired ? "EXPIRED ❌" : "VALID ✅") . "\n";
    echo "Expires At: " . date('Y-m-d H:i:s', $expires_at) . "\n\n";
    echo "Access Token:\n";
    echo $access_token . "\n\n";
    echo "========================================\n";
    echo "To seed products, run:\n";
    echo "cd C:\\Users\\Todd\\Projects\\wp-plugins\\hubspot-ecommerce-app\n";
    echo "node seed-products.js " . $access_token . "\n";
    echo "========================================\n";
} else {
    echo "❌ No OAuth token found. Please connect to HubSpot first.\n";
}
