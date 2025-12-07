# HubSpot API Scope Findings (January 2025)

## Test Results Summary

### Test Account

- **Portal ID**: 244168038
- **Account Type**: Developer test account with all trials active
- **App ID**: 22355171

### OAuth Flow

✅ **Success** - OAuth authorization flow works perfectly

- Authorization code exchange ✅
- Access token retrieval ✅
- Refresh token received ✅
- Token expires in 30 minutes (1800 seconds)

## Scopes Actually Granted

When we configured the app with 14 scopes, only **9 were actually granted**:

```
✅ crm.objects.contacts.read
✅ crm.objects.contacts.write
✅ crm.objects.deals.read
✅ crm.objects.deals.write
✅ crm.objects.line_items.read
✅ crm.objects.line_items.write
✅ crm.schemas.line_items.read
✅ e-commerce
✅ oauth (automatically added)
```

### Scopes NOT Granted (Requested but Rejected)

```
❌ crm.objects.subscriptions.read
❌ crm.objects.subscriptions.write
❌ crm.objects.invoices.read
❌ crm.objects.invoices.write
❌ communication_preferences.read
❌ communication_preferences.write
```

## API Test Results

### ✅ Working APIs (With Current Scopes)

1. **Products API**
   - Scope: `e-commerce`
   - Endpoint: `/crm/v3/objects/products`
   - Status: ✅ 200 OK
   - Note: 0 products in test account (expected)

2. **Contacts API**
   - Scopes: `crm.objects.contacts.read`, `crm.objects.contacts.write`
   - Endpoint: `/crm/v3/objects/contacts`
   - Status: ✅ 200 OK
   - Results: 2 contacts found

3. **Deals API**
   - Scopes: `crm.objects.deals.read`, `crm.objects.deals.write`
   - Endpoint: `/crm/v3/objects/deals`
   - Status: ✅ 200 OK
   - Note: 0 deals in test account (expected)

4. **Line Items API**
   - Scopes: `crm.objects.line_items.read`, `crm.objects.line_items.write`
   - Endpoint: `/crm/v3/objects/line_items`
   - Status: ✅ 200 OK
   - Note: 0 line items in test account (expected)

### ❌ Failing APIs

1. **Subscriptions API**
   - Requested Scope: `crm.objects.subscriptions.read`
   - Endpoint: `/crm/v3/objects/subscriptions`
   - Status: ❌ 403 MISSING_SCOPES
   - Error: "This app hasn't been granted all required scopes"
   - **Reason**: Scope may not be available for public apps OR incorrect scope name

2. **Invoices API**
   - Requested Scope: `crm.objects.invoices.read`
   - Endpoint: `/crm/v3/objects/invoices`
   - Status: ❌ 403 MISSING_SCOPES
   - Error: "This app hasn't been granted all required scopes"
   - **Reason**: **Invoices API scopes are NOT publicly available** (confirmed by HubSpot community)

3. **Email Subscription Preferences API**
   - Requested Scope: `communication_preferences.read`
   - Endpoint: `/communication-preferences/v4/definitions`
   - Status: ❌ 403 MISSING_SCOPES
   - Error: "This app hasn't been granted all required scopes"
   - **Reason**: Scope name might be different or unavailable for public apps

## Critical Findings

### 🚨 Invoices API - NOT Available for Public Apps

According to HubSpot developer community discussions:

- "The scope needed for invoice API calls **isn't available for public use**"
- Invoice API access is restricted to internal/private apps only
- Public marketplace apps **cannot access Invoices API via OAuth**

**Impact on Plugin:**

- Invoice features (`create_invoice()`, `update_invoice()`, `get_invoice()`, etc.) will **NOT work** with OAuth
- Must remove invoice functionality or only support it with Private App tokens

### ❓ Subscriptions API - Scope Name Unknown

The correct scope for Subscriptions API is unclear:

- Tried: `crm.objects.subscriptions.read` ❌
- Might be: `crm.schemas.subscriptions.read` (suggested by community)
- Might be: Included in `e-commerce` but requires Commerce Hub subscription
- Needs further investigation

### ❓ Communication Preferences API - Scope Name Unknown

The correct scope for Communication Preferences API is unclear:

- Tried: `communication_preferences.read` ❌
- Scope might have different name
- Might require special permissions

## Recommendations

### Option 1: OAuth + Private App Hybrid (Recommended)

**For OAuth (Marketplace App):**

- ✅ Products sync (`e-commerce` scope)
- ✅ Contact creation/update
- ✅ Deal (order) creation
- ✅ Line items
- ❌ Subscriptions (remove from OAuth flow)
- ❌ Invoices (remove from OAuth flow)

**For Private App (Manual Setup):**

- ✅ All of the above
- ✅ Subscriptions (if account has Commerce Hub)
- ✅ Invoices (if account has Commerce Hub)
- ✅ Full control over all scopes

**Implementation:**

1. Support both authentication methods in plugin
2. OAuth for easy setup (marketplace)
3. Private App for advanced features (manual setup)
4. Detect which method is used and show appropriate features

### Option 2: Private App Only

- Remove OAuth client entirely
- Require users to create Private Apps manually
- Document setup process thoroughly
- Simpler architecture, more manual setup

### Option 3: Contact HubSpot Support

- Request access to Invoices API scopes for marketplace apps
- Get clarification on Subscriptions API scope names
- May require special approval or partnership

## Next Steps

### Immediate (Testing)

1. ✅ Test Products API - **WORKING**
2. ✅ Test Contacts API - **WORKING**
3. ✅ Test Deals API - **WORKING**
4. ✅ Test Line Items API - **WORKING**
5. ❌ Test Subscriptions API - **BLOCKED** (missing scope)
6. ❌ Test Invoices API - **BLOCKED** (scope not publicly available)

### Short Term (Development)

1. Decide on authentication strategy (hybrid vs Private App only)
2. Remove or conditionally hide invoice features
3. Research alternative subscription access methods
4. Update WordPress plugin to reflect scope limitations

### Long Term (Product)

1. Launch with OAuth for basic ecommerce features
2. Add Private App support for advanced features
3. Monitor HubSpot API changes for new scope availability
4. Consider HubSpot partnership for enhanced API access

## Working Plugin Feature Set (OAuth Only)

### ✅ Core Features (OAuth Compatible)

- Product sync from HubSpot to WordPress
- Contact creation when customers checkout
- Deal (order) creation in HubSpot
- Line item association with deals
- Basic ecommerce workflow

### ❌ Advanced Features (Requires Private App)

- Subscription management
- Invoice creation and management
- Email subscription preferences sync

## Conclusion

**The OAuth flow works perfectly** for the core ecommerce features (products, contacts, deals, line items). However, advanced Commerce Hub features (subscriptions, invoices) are either:

1. **Not available for public OAuth apps** (invoices)
2. **Using different/unknown scope names** (subscriptions)
3. **Requiring premium account features** (Commerce Hub subscription)

**Recommended path forward:**

- Launch with OAuth supporting core features
- Add Private App support for users who need subscriptions/invoices
- Provide clear documentation on which features require which auth method
- Monitor HubSpot API for scope availability changes

---

## Files Created During Testing

1. **oauth-test-flow.js** - OAuth authorization flow test server
2. **test-all-scopes.js** - Comprehensive API scope testing
3. **check-token-scopes.js** - Token scope inspection tool
4. **test-hubspot-api.js** - Basic API connectivity test

## OAuth Credentials

- **Client ID**: Configure via `HUBSPOT_OAUTH_CLIENT_ID` in wp-config.php
- **Client Secret**: Configure via `HUBSPOT_OAUTH_CLIENT_SECRET` in wp-config.php
- **Test Portal ID**: `244168038`
- **Production Portal ID**: `42045718`
