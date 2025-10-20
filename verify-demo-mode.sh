#!/bin/bash
# Demo Mode Verification Script
# Run this in WP Engine Local site shell to verify demo mode is working

echo "🔍 Verifying Demo Mode Setup..."
echo ""

# Check if plugin is activated
echo "1. Checking plugin status..."
if wp plugin is-active hubspot-ecommerce; then
    echo "   ✅ Plugin is active"
else
    echo "   ❌ Plugin is NOT active"
    echo "   Run: wp plugin activate hubspot-ecommerce"
    exit 1
fi

echo ""

# Check if demo mode is enabled
echo "2. Checking demo mode setting..."
DEMO_MODE=$(wp option get hubspot_ecommerce_demo_mode 2>/dev/null)
if [ "$DEMO_MODE" = "1" ]; then
    echo "   ✅ Demo mode is ENABLED"
else
    echo "   ❌ Demo mode is NOT enabled"
    echo "   Run: wp option update hubspot_ecommerce_demo_mode 1"
    exit 1
fi

echo ""

# Check for mock products
echo "3. Checking for mock products..."
PRODUCT_COUNT=$(wp post list --post_type=hs_product --format=count 2>/dev/null)
if [ "$PRODUCT_COUNT" -ge 3 ]; then
    echo "   ✅ Found $PRODUCT_COUNT products"
    wp post list --post_type=hs_product --fields=ID,post_title --format=table
else
    echo "   ⚠️  Only found $PRODUCT_COUNT products (expected 3)"
    echo "   Run: wp cron event run hubspot_ecommerce_sync_products"
fi

echo ""

# Check for required pages
echo "4. Checking for shop pages..."
REQUIRED_PAGES=("shop" "cart" "checkout")
MISSING_PAGES=()

for page in "${REQUIRED_PAGES[@]}"; do
    if wp post list --post_type=page --name="$page" --format=count | grep -q "1"; then
        echo "   ✅ Page '$page' exists"
    else
        echo "   ❌ Page '$page' is missing"
        MISSING_PAGES+=("$page")
    fi
done

echo ""

# Summary
echo "📊 Summary:"
echo ""
if [ ${#MISSING_PAGES[@]} -eq 0 ] && [ "$PRODUCT_COUNT" -ge 3 ]; then
    echo "   ✅ Demo mode is fully configured and ready!"
    echo ""
    echo "   Next steps:"
    echo "   1. Visit: https://granttk8org.local/wp-admin (should see yellow demo banner)"
    echo "   2. Visit: https://granttk8org.local/shop (should see 3 products)"
    echo "   3. Run tests: cd C:\\Users\\Todd\\Projects\\wp-plugins\\hubspot-ecommerce && npm test"
else
    echo "   ⚠️  Demo mode needs some setup"
    echo ""
    if [ ${#MISSING_PAGES[@]} -gt 0 ]; then
        echo "   Missing pages: ${MISSING_PAGES[*]}"
        echo "   Create them with:"
        for page in "${MISSING_PAGES[@]}"; do
            case "$page" in
                shop)
                    echo "   wp post create --post_type=page --post_title='Shop' --post_content='[hubspot_products]' --post_status=publish --post_name=shop"
                    ;;
                cart)
                    echo "   wp post create --post_type=page --post_title='Cart' --post_content='[hubspot_cart]' --post_status=publish --post_name=cart"
                    ;;
                checkout)
                    echo "   wp post create --post_type=page --post_title='Checkout' --post_content='[hubspot_checkout]' --post_status=publish --post_name=checkout"
                    ;;
            esac
        done
    fi

    if [ "$PRODUCT_COUNT" -lt 3 ]; then
        echo ""
        echo "   Sync mock products:"
        echo "   wp cron event run hubspot_ecommerce_sync_products"
    fi
fi

echo ""
