/**
 * Quick helper to show how to get the token from WordPress admin
 */

const config = require('./config');

console.log('========================================');
console.log('To get your HubSpot OAuth access token:');
console.log('========================================\n');
console.log(`1. Go to: ${config.wordpress.adminUrl}`);
console.log('2. Navigate to: HubSpot Ecommerce → Connect to HubSpot');
console.log('3. Look for "Access Token" status (it should say "Valid")');
console.log('4. OR run this in browser console on any WP admin page:\n');
console.log('   Open DevTools (F12) and paste this in Console:\n');
console.log(`   fetch("${config.wordpress.ajaxEndpoint}?action=${config.wordpress.tokenAction}")`);
console.log('     .then(r => r.text())');
console.log('     .then(token => console.log("Token:", token));\n');
console.log('========================================\n');
console.log('Once you have the token, run:');
console.log('node seed-products.js YOUR_TOKEN_HERE\n');
