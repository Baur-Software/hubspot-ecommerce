# HubSpot Private App Setup Guide

## Why Private Apps?

HubSpot deprecated API keys in 2022 and now recommends using Private Apps for custom integrations. Private Apps use access tokens that are:

- **More secure** than API keys
- **Scoped** to only the permissions you need
- **Easier to manage** than OAuth for single-account integrations
- **Recommended by HubSpot** for WordPress integrations

## Creating a Private App

### 1. Go to HubSpot Settings

1. Log into your HubSpot account
2. Click the **Settings** gear icon (top right)
3. In the left sidebar, go to **Integrations** → **Private Apps**

### 2. Create Your Private App

1. Click **"Create a private app"**
2. Fill in the **Basic Info** tab:
   - **Name**: "WordPress Ecommerce Integration"
   - **Description**: "Syncs products and orders between WordPress and HubSpot"
   - **Logo**: (optional)

### 3. Configure Scopes

Click the **Scopes** tab and enable these permissions:

#### CRM Scopes (Required)

- ✅ `crm.objects.contacts.read` - Read contacts
- ✅ `crm.objects.contacts.write` - Create/update contacts
- ✅ `crm.objects.deals.read` - Read deals (orders)
- ✅ `crm.objects.deals.write` - Create/update deals
- ✅ `crm.objects.line_items.read` - Read line items (**important!**)
- ✅ `crm.objects.line_items.write` - Create line items (**important!**)
- ✅ `crm.objects.products.read` - Read products (**important!**)
- ✅ `crm.schemas.line_items.read` - Read line item schemas

#### Commerce Scopes (Required for Subscriptions)

- ✅ `crm.objects.subscriptions.read` - Read subscriptions

#### Communication Scopes (Optional - for email subscriptions)

- ✅ `communication_preferences.read` - Read email subscription types
- ✅ `communication_preferences.write` - Subscribe/unsubscribe contacts

### 4. Create the App

1. Click **"Create app"** button
2. HubSpot will show you the access token **once**
3. **Copy the token immediately** - you won't be able to see it again!

### 5. Add Token to WordPress

1. Go to your WordPress admin
2. Navigate to **HubSpot Shop** → **Settings**
3. Find the **"Private App Access Token"** field
4. Paste your token
5. Click **"Save Changes"**

### 6. Test the Connection

1. Go to **HubSpot Shop** → **Dashboard**
2. You should see: **"Authenticated via Private App Token"**
3. Try syncing products: **HubSpot Shop** → **Sync Products** → **Sync Products Now**

## Managing Your Private App

### View/Edit Scopes

1. Go to HubSpot **Settings** → **Integrations** → **Private Apps**
2. Click on your app name
3. Go to **Scopes** tab to modify permissions
4. **Note**: Changing scopes will generate a new access token

### Rotate Token

If your token is compromised:

1. Go to your Private App in HubSpot settings
2. Click **"Show token"**
3. Click **"Rotate token"**
4. Copy the new token
5. Update it in WordPress **HubSpot Shop** → **Settings**

### Delete App

To revoke all access:

1. Go to HubSpot **Settings** → **Integrations** → **Private Apps**
2. Click on your app
3. Click **"Delete app"**
4. Confirm deletion

## Troubleshooting

### "Authentication not configured" Error

**Solution**: Make sure you've pasted the Private App token in **HubSpot Shop** → **Settings**

### "No products found" After Sync

**Causes**:

1. No products in HubSpot → Create products in HubSpot first
2. Missing `crm.objects.products.read` scope → Add scope and get new token
3. Products are archived → Un-archive products in HubSpot

### "Insufficient permissions" Error

**Solution**: Your Private App is missing required scopes. Go back to HubSpot and add the missing scopes, then copy the new token to WordPress.

### Products Sync But No Subscriptions

**Solution**: Make sure your products in HubSpot have these properties set:

- `hs_recurring_billing_period`
- `recurringbillingfrequency`
- `hs_billing_period_units`

## Security Best Practices

1. **Never share your access token** - treat it like a password
2. **Don't commit tokens to Git** - use environment variables or WordPress options
3. **Use HTTPS** - always access your WordPress site via HTTPS
4. **Limit scopes** - only enable permissions you actually need
5. **Rotate regularly** - change your token periodically for security

## vs. OAuth (HubSpot Plugin)

|  | Private App | OAuth (leadin) |
|---|---|---|
| **Setup** | Simple - just copy/paste token | Complex - requires app credentials |
| **Security** | Scoped access tokens | Full OAuth 2.0 flow |
| **Multi-account** | One token per HubSpot account | Supports multiple accounts |
| **Token Management** | Manual token entry | Automatic refresh |
| **Recommended for** | Single-site WordPress installs | Multi-site or SaaS platforms |

## Next Steps

After setup:

1. ✅ **Sync Products**: HubSpot Shop → Sync Products
2. ✅ **Create Pages**: HubSpot Shop → Create missing shop pages
3. ✅ **Configure Settings**: HubSpot Shop → Settings → Configure currency, etc.
4. ✅ **Test Checkout**: Place a test order to verify integration

## Need Help?

- [HubSpot Private Apps Documentation](https://developers.hubspot.com/docs/api/private-apps)
- [HubSpot Community Forums](https://community.hubspot.com/)
- [Plugin GitHub Issues](https://github.com/yourusername/hubspot-ecommerce/issues)
