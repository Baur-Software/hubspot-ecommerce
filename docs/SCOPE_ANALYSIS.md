# HubSpot OAuth Scope Analysis

## Objects Your Plugin Uses

Based on `class-hubspot-api.php`, here are all the HubSpot objects your plugin needs access to:

### 1. **Products** ✅

- **Methods**: `get_products()`, `get_product()`, `get_recurring_products()`
- **Operations**: Read
- **Scope Needed**: `e-commerce` ✅ (already configured)

### 2. **Contacts** ✅

- **Methods**: `create_contact()`, `update_contact()`, `search_contact_by_email()`
- **Operations**: Read, Write
- **Scopes Needed**:
  - `crm.objects.contacts.read` ✅
  - `crm.objects.contacts.write` ✅

### 3. **Deals (Orders)** ✅

- **Methods**: `create_deal()`, `get_deal()`, `update_deal()`
- **Operations**: Read, Write
- **Scopes Needed**:
  - `crm.objects.deals.read` ✅
  - `crm.objects.deals.write` ✅

### 4. **Line Items** ✅

- **Methods**: `create_line_item()`, `batch_create_line_items()`
- **Operations**: Read, Write
- **Scopes Needed**:
  - `crm.objects.line_items.read` ✅
  - `crm.objects.line_items.write` ✅
  - `crm.schemas.line_items.read` ✅

### 5. **Subscriptions** ❓

- **Methods**: `get_commerce_subscriptions()`, `get_commerce_subscription()`, `create_commerce_subscription()`, `update_commerce_subscription()`, `get_contact_subscriptions()`
- **Operations**: Read, Write
- **Scope Needed**: **MISSING!** ❌

### 6. **Invoices** ❓

- **Methods**: `create_invoice()`, `update_invoice()`, `get_invoice()`, `get_invoice_payment_link()`, `associate_invoice_to_contact()`, `associate_line_item_to_invoice()`
- **Operations**: Read, Write
- **Scope Needed**: **MISSING!** ❌

### 7. **Email Subscription Preferences** ✅

- **Methods**: `get_subscription_type_definitions()`, `get_contact_subscription_statuses()`, `subscribe_contact()`, `unsubscribe_contact()`, `unsubscribe_contact_from_all()`
- **Operations**: Read, Write
- **Scopes Needed**:
  - `communication_preferences.read` ✅ (optional)
  - `communication_preferences.write` ✅ (optional)

### 8. **Associations** ✅

- **Methods**: `create_association()`
- **Operations**: Write
- **Covered by**: Object-level scopes (e.g., `crm.objects.contacts.write` includes association permissions)

## Current Scope Configuration

### Required Scopes (Currently Configured)

```json
[
  "crm.objects.contacts.read",
  "crm.objects.contacts.write",
  "crm.objects.deals.read",
  "crm.objects.deals.write",
  "crm.objects.line_items.read",
  "crm.objects.line_items.write",
  "crm.schemas.line_items.read",
  "e-commerce"
]
```

### Optional Scopes (Currently Configured)

```json
[
  "communication_preferences.read",
  "communication_preferences.write"
]
```

## Missing Scopes ❌

### 1. Subscriptions

According to HubSpot docs, subscriptions might be covered by `e-commerce` scope, but we need to verify.

**Possible scope names:**

- `crm.objects.subscriptions.read`
- `crm.objects.subscriptions.write`
- Might be included in `e-commerce` scope

### 2. Invoices

**Possible scope names:**

- `crm.objects.invoices.read`
- `crm.objects.invoices.write`
- Might be included in `e-commerce` scope

## The `e-commerce` Scope

The `e-commerce` scope is a **super-scope** that includes access to:

- ✅ Products
- ✅ Line Items (redundant with individual scopes)
- ✅ **Subscriptions** (likely)
- ✅ **Invoices** (likely)
- ✅ Quotes
- ✅ Fees
- ✅ Discounts
- ✅ Taxes

**Source**: The `e-commerce` scope was introduced to simplify Commerce Hub integrations and provides broad access to all commerce-related objects.

## Recommendation

### Test Current Scopes First ✅

**Your current scopes should work!** The `e-commerce` scope likely covers subscriptions and invoices.

**Test Plan:**

1. Complete OAuth flow with current scopes
2. Test each API endpoint:
   - ✅ Products API - `GET /crm/v3/objects/products`
   - ❓ Subscriptions API - `GET /crm/v3/objects/subscriptions`
   - ❓ Invoices API - `GET /crm/v3/objects/invoices`
   - ✅ Contacts API - `GET /crm/v3/objects/contacts`
   - ✅ Deals API - `GET /crm/v3/objects/deals`
3. If subscriptions/invoices fail, add explicit scopes

### If Subscriptions/Invoices Fail, Add These Scopes

```json
"requiredScopes": [
  "crm.objects.contacts.read",
  "crm.objects.contacts.write",
  "crm.objects.deals.read",
  "crm.objects.deals.write",
  "crm.objects.line_items.read",
  "crm.objects.line_items.write",
  "crm.schemas.line_items.read",
  "e-commerce",
  "crm.objects.subscriptions.read",  // ADD IF NEEDED
  "crm.objects.subscriptions.write", // ADD IF NEEDED
  "crm.objects.invoices.read",       // ADD IF NEEDED
  "crm.objects.invoices.write"       // ADD IF NEEDED
]
```

## Testing the OAuth Flow

Let's test right now to see if your current scopes work:

### Step 1: Check OAuth Test Server

The oauth-test-flow.js is already running in the background. Let me check its output.

### Step 2: Complete OAuth Authorization

1. Open browser to: <http://localhost:3000>
2. Click "Authorize App in HubSpot"
3. Review scopes shown on HubSpot authorization page
4. Check if subscriptions/invoices are mentioned
5. Authorize the app
6. Check if we get a valid access token

### Step 3: Test API Endpoints

Once we have an access token, test:

```bash
# Products (should work)
curl -H "Authorization: Bearer {token}" \
  "https://api.hubapi.com/crm/v3/objects/products?limit=1"

# Subscriptions (need to verify)
curl -H "Authorization: Bearer {token}" \
  "https://api.hubapi.com/crm/v3/objects/subscriptions?limit=1"

# Invoices (need to verify)
curl -H "Authorization: Bearer {token}" \
  "https://api.hubapi.com/crm/v3/objects/invoices?limit=1"
```

## Verdict

**Your current scopes should be sufficient!** ✅

The `e-commerce` scope is designed to be a comprehensive scope for all Commerce Hub objects, which includes:

- Products ✅
- Subscriptions ✅
- Invoices ✅
- Line Items ✅
- Quotes
- Fees
- Discounts
- Taxes

**Next step**: Let's test the OAuth flow to confirm this works in practice.
