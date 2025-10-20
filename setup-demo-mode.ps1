# Demo Mode Setup Script for granttk8org.local
# Run this in PowerShell to set up demo mode

Write-Host "🎭 Setting up Demo Mode for granttk8org.local" -ForegroundColor Cyan
Write-Host ""

# Instructions for manual setup
Write-Host "📋 Setup Steps:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Open WP Engine Local" -ForegroundColor White
Write-Host "2. Right-click 'granttk8org' site → 'Open site shell'" -ForegroundColor White
Write-Host ""
Write-Host "3. Run these commands in the site shell:" -ForegroundColor White
Write-Host ""
Write-Host "   # Navigate to plugins directory" -ForegroundColor Gray
Write-Host "   cd app/public/wp-content/plugins" -ForegroundColor Green
Write-Host ""
Write-Host "   # Create symlink (requires Administrator shell)" -ForegroundColor Gray
Write-Host "   cmd /c mklink /D hubspot-ecommerce `"C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce`"" -ForegroundColor Green
Write-Host ""
Write-Host "   # Install Composer dependencies" -ForegroundColor Gray
Write-Host "   cd hubspot-ecommerce" -ForegroundColor Green
Write-Host "   composer install" -ForegroundColor Green
Write-Host ""
Write-Host "   # Enable demo mode" -ForegroundColor Gray
Write-Host "   wp option update hubspot_ecommerce_demo_mode 1" -ForegroundColor Green
Write-Host ""
Write-Host "   # Activate plugin" -ForegroundColor Gray
Write-Host "   wp plugin activate hubspot-ecommerce" -ForegroundColor Green
Write-Host ""
Write-Host "   # Sync mock products" -ForegroundColor Gray
Write-Host "   wp cron event run hubspot_ecommerce_sync_products" -ForegroundColor Green
Write-Host ""
Write-Host "   # Create shop pages" -ForegroundColor Gray
Write-Host "   wp post create --post_type=page --post_title='Shop' --post_content='[hubspot_products]' --post_status=publish --post_name=shop" -ForegroundColor Green
Write-Host "   wp post create --post_type=page --post_title='Cart' --post_content='[hubspot_cart]' --post_status=publish --post_name=cart" -ForegroundColor Green
Write-Host "   wp post create --post_type=page --post_title='Checkout' --post_content='[hubspot_checkout]' --post_status=publish --post_name=checkout" -ForegroundColor Green
Write-Host ""
Write-Host "4. Verify demo mode is active:" -ForegroundColor White
Write-Host "   - Visit: https://granttk8org.local/wp-admin" -ForegroundColor Green
Write-Host "   - You should see a yellow banner: '🎭 DEMO MODE ACTIVE'" -ForegroundColor Green
Write-Host ""
Write-Host "5. Test the shop:" -ForegroundColor White
Write-Host "   - Visit: https://granttk8org.local/shop" -ForegroundColor Green
Write-Host "   - You should see 3 mock products" -ForegroundColor Green
Write-Host ""
Write-Host "6. Run full test suite:" -ForegroundColor White
Write-Host "   cd C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce" -ForegroundColor Green
Write-Host "   npm test" -ForegroundColor Green
Write-Host ""
Write-Host "✅ After setup, you should have 46 tests running!" -ForegroundColor Cyan
Write-Host "   - 20 tests: Framework + Code validation (no WordPress needed)" -ForegroundColor Gray
Write-Host "   - 26 tests: E2E tests (require WordPress)" -ForegroundColor Gray
Write-Host ""
