# HubSpot Ecommerce Companion App

HubSpot Marketplace companion application for the HubSpot Ecommerce for WordPress plugin.

## Overview

This companion app provides OAuth authentication and HubSpot Marketplace integration for the WordPress plugin. It enables:

- ✅ One-click OAuth connection
- ✅ Automatic token refresh
- ✅ Scope management
- ✅ HubSpot Marketplace listing
- ✅ Customer tenant deployment

---

## Quick Start

### For Development

```bash
# Install dependencies
npm install

# Install HubSpot CLI
npm install -g @hubspot/cli

# Configure HubSpot portal
hs init

# Deploy to development portal
npm run deploy:dev

# Watch for changes
npm run watch
```

### For Customer Deployment

Use GitHub Actions for automated deployment:

1. Go to **Actions** tab
2. Select **"Deploy to Customer Tenant"**
3. Fill in customer details
4. Click **"Run workflow"**

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete guide.

---

## Project Structure

```
hubspot-ecommerce-app/
├── .github/
│   └── workflows/              # GitHub Actions workflows
│       ├── deploy-to-hubspot.yml
│       ├── deploy-to-customer.yml
│       └── validate-and-test.yml
├── src/
│   └── app/
│       ├── app-hsmeta.json    # App metadata and OAuth config
│       ├── extensions/         # (Future) Custom cards
│       └── functions/          # (Future) Serverless functions
├── config.js                   # Configuration and scopes
├── oauth-test-flow.js          # OAuth testing utility
├── test-app-api.js            # API testing utility
├── seed-products.js           # HubSpot product seeder
├── hsproject.json             # HubSpot project config
├── package.json               # NPM dependencies
├── DEPLOYMENT.md              # Deployment documentation
└── README.md                  # This file
```

---

## Features

### OAuth 2.0 Integration ✅

Provides OAuth credentials for WordPress plugin:

- **Client ID:** Configure via `HUBSPOT_OAUTH_CLIENT_ID` in wp-config.php
- **Client Secret:** Configure via `HUBSPOT_OAUTH_CLIENT_SECRET` in wp-config.php
- **Scopes:**
  - `e-commerce` - Product sync
  - `crm.objects.contacts.*` - Contact management
  - `crm.objects.deals.*` - Deal/order tracking
  - `crm.objects.line_items.*` - Line item management
  - `oauth` - OAuth token refresh

### Testing Utilities ✅

**OAuth Flow Tester:**
```bash
node oauth-test-flow.js
```

**API Endpoint Tester:**
```bash
node test-app-api.js YOUR_ACCESS_TOKEN
```

**Scope Validator:**
```bash
node check-token-scopes.js YOUR_ACCESS_TOKEN
```

**Product Seeder:**
```bash
node seed-products.js YOUR_ACCESS_TOKEN
```

### Future Features (Roadmap)

- [ ] Settings Card UI (React component)
- [ ] Dashboard with sync statistics
- [ ] Webhook handlers for HubSpot events
- [ ] Custom CRM cards
- [ ] Serverless functions for data processing

---

## OAuth Scopes

### Current Scopes (Free Tier)

```javascript
{
  "e-commerce": "Product sync from HubSpot",
  "crm.objects.contacts.read": "Read customer data",
  "crm.objects.contacts.write": "Create/update customers",
  "crm.objects.deals.read": "Read orders/deals",
  "crm.objects.deals.write": "Create orders/deals",
  "crm.objects.line_items.read": "Read order line items",
  "crm.objects.line_items.write": "Create line items",
  "crm.schemas.line_items.read": "Read line item schema",
  "oauth": "Token refresh capability"
}
```

### Pro Tier Scopes (Private App Only)

Pro features require Private App authentication in WordPress:

```javascript
{
  "crm.objects.invoices.read": "Read invoices",
  "crm.objects.invoices.write": "Create invoices (HubSpot Payments)",
  "crm.objects.subscriptions.read": "Read subscriptions",
  "crm.objects.subscriptions.write": "Manage subscriptions",
  "communication_preferences.read": "Read email preferences",
  "communication_preferences.write": "Update email opt-in/out"
}
```

**Why separate?** HubSpot OAuth doesn't support invoice/subscription scopes for public apps. See [API_SCOPE_FINDINGS.md](../hubspot-ecommerce/API_SCOPE_FINDINGS.md) for details.

---

## Deployment

### Manual Deployment (Local)

```bash
# Deploy to default portal
hs project upload

# Deploy to specific portal
hs project upload --portal=customer-name

# Validate before deploying
hs project validate
```

### Automated Deployment (GitHub Actions)

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete guide.

**Quick Deploy:**

1. Push to `main` branch → Auto-deploys to development
2. Create release → Auto-deploys to production
3. Manual workflow → Deploy to specific customer

---

## Configuration

### Environment Variables

Create `.env` file (not committed):

```bash
HUBSPOT_PORTAL_ID=42045718
HUBSPOT_PERSONAL_ACCESS_KEY=your-pak-here
CUSTOMER_NAME=development
```

### HubSpot Portal Configuration

Edit `hubspot.config.yml` (not committed):

```yaml
defaultPortal: development
portals:
  - name: development
    portalId: 42045718
    authType: personalaccesskey
    personalAccessKey: your-pak-here
```

**Note:** This file is in `.gitignore` and should never be committed.

---

## Testing

### Run Validation

```bash
# Validate project structure
npm run validate

# Test OAuth flow (requires .env)
node oauth-test-flow.js

# Test all API scopes (requires token)
node test-all-scopes.js YOUR_TOKEN

# Seed test products (requires token)
node seed-products.js YOUR_TOKEN
```

### GitHub Actions Tests

```bash
# Runs automatically on PR
# - Code validation
# - Security scan
# - Dry run deployment
```

---

## Customer Onboarding

### Quick Onboarding Steps

1. **Obtain customer HubSpot Portal ID**
2. **Create Private App in customer's HubSpot** (get PAK)
3. **Add customer PAK to GitHub Secrets**
   - Name: `CUSTOMER_{PORTAL_ID}_PAK`
4. **Run deployment workflow** from GitHub Actions
5. **Verify app in customer's HubSpot portal**
6. **Send WordPress plugin to customer**

See [DEPLOYMENT.md - Customer Onboarding](DEPLOYMENT.md#customer-onboarding) for detailed steps.

---

## Development

### Local Development Workflow

```bash
# 1. Clone repository
git clone https://github.com/yourusername/hubspot-ecommerce-app.git
cd hubspot-ecommerce-app

# 2. Install dependencies
npm install

# 3. Configure HubSpot portal
hs init  # Follow prompts

# 4. Start development with auto-reload
npm run watch

# 5. Make changes to src/app/*

# 6. Deploy when ready
npm run deploy:dev
```

### Adding New Features

**Example: Add Settings Card Component**

1. Create new file: `src/app/extensions/settings-card.jsx`
2. Update `src/app/app-hsmeta.json` to reference card
3. Test locally: `hs project upload`
4. Verify in HubSpot portal settings
5. Commit and push to trigger deployment

---

## Troubleshooting

### OAuth Not Working

**Issue:** WordPress plugin can't connect via OAuth

**Solution:**
1. Verify OAuth credentials in `src/app/app-hsmeta.json`
2. Check redirect URIs match WordPress admin URL
3. Ensure customer has activated the app
4. Test with `node oauth-test-flow.js`

### Deployment Fails

**Issue:** GitHub Actions deployment fails

**Solution:**
1. Check GitHub Secrets are configured correctly
2. Verify PAK has `developer.app.deploy` scope
3. Check portal ID is correct
4. Review deployment logs in Actions tab

### App Not Visible in HubSpot

**Issue:** App deployed but not showing in customer portal

**Solution:**
1. Check app is published (not draft)
2. Verify customer has required HubSpot plan
3. Clear browser cache
4. Check HubSpot Marketplace status

---

## Resources

### Documentation
- [HubSpot CLI](https://developers.hubspot.com/docs/cli/getting-started)
- [HubSpot App Platform](https://developers.hubspot.com/docs/platform)
- [OAuth 2.0 Guide](https://developers.hubspot.com/docs/api/oauth-quickstart-guide)
- [Main Plugin Docs](../hubspot-ecommerce/README.md)

### Support
- GitHub Issues: [Create issue](https://github.com/yourusername/hubspot-ecommerce-app/issues)
- Email: support@baursoftware.com
- Docs: https://baursoftware.com/docs/hubspot-ecommerce

---

## License

See [LICENSE](../LICENSE) file.

---

## Changelog

### v1.0.0 (2025-01-24)
- Initial release
- OAuth 2.0 integration
- GitHub Actions deployment workflows
- Testing utilities
- Documentation

---

**Maintained by:** Baur Software
**Last Updated:** 2025-01-24
