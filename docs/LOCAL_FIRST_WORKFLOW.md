# Local-First Product Workflow

The plugin uses a local-first approach to product management, giving you full control over when and how products sync with HubSpot.

## Philosophy

**WordPress is the source of truth** for your product content and presentation. Products are created and edited in WordPress, then optionally synced to HubSpot for CRM, invoicing, and payment processing.

This approach provides:

- Full control over product pages and design
- Ability to work offline or without HubSpot connection
- Manual control over sync timing
- Clear separation between presentation (WordPress) and business logic (HubSpot)

## Product Creation Flow

### Free Tier (Default Behavior)

1. **Create Product in WordPress**
   - Navigate to HubSpot Shop → Add New Product
   - Fill in product details (title, description, price, SKU)
   - Choose a product template
   - Add images
   - Publish

2. **Product Stays Local**
   - Product is immediately available on your website
   - No automatic sync to HubSpot
   - Sync status shows: "Local Product"

3. **Manual Push to HubSpot (When Ready)**
   - Click "Push to HubSpot" button in product editor
   - Product is created in HubSpot CRM
   - Sync status changes to: "Synced to HubSpot"
   - HubSpot product ID is stored in WordPress

4. **Subsequent Updates**
   - Edit product in WordPress
   - Click "Push to HubSpot" to sync changes
   - Or click "Pull from HubSpot" to get HubSpot updates

### Pro Tier (Automatic Sync)

Pro users can enable automatic syncing in Settings:

**Auto-Push TO HubSpot:**

- Products automatically sync to HubSpot on save
- Enabled in Settings → Product Sync Settings
- Requires Pro license

**Auto-Sync FROM HubSpot:**

- Products created in HubSpot automatically appear in WordPress
- Runs on configurable schedule (hourly, twice daily, daily)
- Enabled in Settings → Product Sync Settings
- Requires Pro license

## Sync Controls

### Product Editor Meta Box

Each product has a "HubSpot Sync" meta box showing:

**Sync Status Badges:**

- 🔵 **Local Product** - Exists only in WordPress
- 🟢 **Synced to HubSpot** - Connected to HubSpot product

**Action Buttons:**

- **Push to HubSpot** - Send current product data to HubSpot
- **Pull from HubSpot** - Update from HubSpot data
- **Enable sync to HubSpot** - Checkbox to include in auto-sync (Pro)

**Information Displayed:**

- HubSpot Product ID (if synced)
- Last sync timestamp
- Sync status

### Settings Page

**Product Sync Settings** (Settings → HubSpot Shop)

Free Tier:

- Manual sync controls only
- Pro feature indicators shown

Pro Tier:

- ✅ Auto-Sync FROM HubSpot (checkbox)
  - Sync Interval (hourly/twice daily/daily)
- ✅ Auto-Push TO HubSpot (checkbox)

## Welcome Notice

First-time product creators see a helpful notice explaining:

- Products are like WordPress posts (full design flexibility)
- Choose product templates in sidebar
- Products stay local by default
- Use "Push to HubSpot" button when ready

The notice can be dismissed and won't show again.

## Use Cases

### Scenario 1: Import from HubSpot

You have existing products in HubSpot CRM.

**Free Tier:**

1. Go to HubSpot Shop → Product Sync
2. Click "Pull Products from HubSpot"
3. Products are imported to WordPress
4. Edit and customize product pages
5. Products remain synced

**Pro Tier:**

1. Enable "Auto-Sync FROM HubSpot" in settings
2. Products automatically sync on schedule
3. New HubSpot products appear automatically

### Scenario 2: Create in WordPress

You want to design product pages in WordPress first.

**Free Tier:**

1. Create products in WordPress
2. Design pages with templates
3. Preview and perfect the presentation
4. Click "Push to HubSpot" when ready to sell
5. Products appear in HubSpot CRM

**Pro Tier:**

1. Enable "Auto-Push TO HubSpot" in settings
2. Create products in WordPress
3. Products automatically sync on save
4. Immediately available in HubSpot

### Scenario 3: Hybrid Workflow

Some products managed locally, others synced.

1. Create local products for drafts/previews
2. Keep sync disabled for those products
3. Enable sync for live products
4. Use "Push to HubSpot" selectively

## Technical Implementation

### Sync Status Storage

Product sync data stored as post meta:

- `_hubspot_product_id` - HubSpot CRM product ID
- `_hubspot_sync_enabled` - Whether sync is enabled (1/0)
- `_last_synced_from_hubspot` - Timestamp of last sync
- `_hubspot_data` - Full HubSpot data snapshot (JSON)

### Automatic Sync (Pro)

Scheduled via WordPress cron:

- Action: `hubspot_ecommerce_sync_products`
- Frequency: Set in settings (hourly/twice daily/daily)
- Only runs if Pro license active AND setting enabled

Auto-push on save:

- Hook: `save_post_hs_product`
- Checks Pro license and setting
- Pushes to HubSpot if enabled

### License Gating

Auto-sync features check:

```php
$license_manager = HubSpot_Ecommerce_License_Manager::instance();
if ($license_manager->can_use_auto_sync()) {
    // Enable automatic sync features
}
```

Implementation:

- Free: Manual buttons only
- Pro/Enterprise: All features available

## Admin UI Components

### Meta Boxes

`HubSpot_Ecommerce_Product_Meta_Boxes` class handles:

- Template selection
- Sync status display
- Manual sync buttons
- Welcome notice
- Pro upgrade prompts

### Settings Page

`HubSpot_Ecommerce_Settings` class handles:

- Auto-sync toggle controls
- Sync interval selection
- Pro feature gating
- Upgrade notices

### Product Manager

`HubSpot_Ecommerce_Product_Manager` class handles:

- Sync logic (push/pull)
- Cron scheduling
- HubSpot API communication
- Meta data management

## Best Practices

1. **Start Local** - Design product pages in WordPress first
2. **Test Before Syncing** - Preview products before pushing to HubSpot
3. **Selective Sync** - Only sync products you're ready to sell
4. **Use Templates** - Consistent presentation across products
5. **Monitor Sync Status** - Check sync badges to know what's connected

## Troubleshooting

**Product won't sync:**

- Check HubSpot connection (Settings → Connect to HubSpot)
- Verify sync checkbox is enabled
- Check for error messages in sync status

**Synced product not updating:**

- Click "Pull from HubSpot" to refresh
- Or "Push to HubSpot" to send local changes
- Check last sync timestamp

**Auto-sync not working:**

- Verify Pro license is active (Settings → License)
- Check auto-sync setting is enabled
- Confirm WordPress cron is running

## Future Enhancements

- Conflict resolution UI for divergent data
- Sync preview before push/pull
- Bulk sync operations
- Sync history log
- Webhook-based instant sync (Enterprise)
