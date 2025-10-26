# HubSpot Companion App - Deployment Guide

Complete guide for deploying the HubSpot Ecommerce companion app to customer tenants using GitHub Actions.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [GitHub Actions Setup](#github-actions-setup)
3. [Deployment Workflows](#deployment-workflows)
4. [Customer Onboarding](#customer-onboarding)
5. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Prerequisites

- GitHub repository with Actions enabled
- HubSpot Developer Account
- Customer HubSpot Portal ID and Personal Access Key
- Node.js 18+ installed locally (for testing)

### Initial Setup (5 minutes)

1. **Fork or clone this repository**
2. **Set up GitHub Secrets** (see below)
3. **Trigger deployment via GitHub Actions**
4. **Verify in customer's HubSpot portal**

---

## GitHub Actions Setup

### Required GitHub Secrets

Go to **Settings → Secrets and variables → Actions** and add:

#### For Development/Testing

```
HUBSPOT_PORTAL_ID=42045718
HUBSPOT_PERSONAL_ACCESS_KEY=your-personal-access-key-here
```

#### For Customer Deployments

For each customer, create secrets with their Portal ID:

```
CUSTOMER_12345678_PAK=customer-personal-access-key
CUSTOMER_87654321_PAK=another-customer-pak
```

**Naming Convention:** `CUSTOMER_{PORTAL_ID}_PAK`

### How to Get Personal Access Key

1. Go to customer's HubSpot account
2. Navigate to **Settings → Integrations → Private Apps**
3. Click **Create private app**
4. Name: "HubSpot Ecommerce App Deployment"
5. Scopes required:
   - `developer.app.deploy`
   - `developer.app.read`
6. Click **Create app**
7. Copy the **Personal Access Key**
8. Add to GitHub Secrets

---

## Deployment Workflows

### 1. Deploy to Development (Automatic)

**Trigger:** Push to `main` branch

```bash
git add .
git commit -m "feat: add new feature"
git push origin main
```

**What happens:**

- Automatically validates code
- Runs security checks
- Deploys to development HubSpot portal
- Creates deployment log

**View Progress:** GitHub → Actions → "Deploy to HubSpot"

---

### 2. Deploy to Customer (Manual)

**Trigger:** Manual workflow dispatch

**Steps:**

1. Go to **Actions** tab in GitHub
2. Select **"Deploy to Customer Tenant"** workflow
3. Click **"Run workflow"**
4. Fill in the form:
   - **Customer Portal ID:** `12345678`
   - **Customer Name:** `Acme Corp`
   - **Deployment Type:** `initial_deployment` or `update` or `hotfix`
   - **Notify Customer:** ✅ (sends email notification)
5. Click **"Run workflow"**

**What happens:**

- Validates portal ID
- Creates pre-deployment backup
- Deploys app to customer's HubSpot
- Verifies deployment succeeded
- Creates deployment record
- Sends notification to customer (if enabled)
- Uploads deployment logs as artifacts

---

### 3. Deploy on Release (Automatic)

**Trigger:** Create a GitHub release

```bash
# Create and push a tag
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0

# Or create release via GitHub UI
# Releases → Draft a new release → Tag version: v1.0.0
```

**What happens:**

- Deploys to production environment
- Creates deployment artifact (tar.gz)
- Attaches artifact to GitHub release
- Available for manual download/deployment

---

## Deployment Workflows Reference

### Available Workflows

| Workflow | File | Trigger | Purpose |
|----------|------|---------|---------|
| Deploy to HubSpot | `deploy-to-hubspot.yml` | Push to main, Manual | Deploy to dev/staging/prod |
| Deploy to Customer | `deploy-to-customer.yml` | Manual only | Deploy to specific customer |
| Validate & Test | `validate-and-test.yml` | Pull requests | Run tests and validation |

### Workflow Inputs

#### deploy-to-hubspot.yml

```yaml
environment:
  type: choice
  options: [development, staging, production]

customer_name:
  type: string
  required: false
```

#### deploy-to-customer.yml

```yaml
customer_portal_id:
  type: string
  required: true
  example: "12345678"

customer_name:
  type: string
  required: true
  example: "Acme Corp"

deployment_type:
  type: choice
  options: [initial_deployment, update, hotfix]

notify_customer:
  type: boolean
  default: true
```

---

## Customer Onboarding

### Step-by-Step Customer Setup

#### 1. Obtain Customer Information

Collect from customer:

- ✅ HubSpot Portal ID
- ✅ Contact email for notifications
- ✅ Deployment window preferences (timezone)

#### 2. Create Customer Private App

**In Customer's HubSpot Account:**

1. Go to **Settings → Integrations → Private Apps**
2. Click **Create private app**
3. App details:
   - Name: **HubSpot Ecommerce Integration**
   - Description: **WordPress ecommerce integration**
4. Required scopes:

   ```
   developer.app.deploy
   developer.app.read
   crm.objects.contacts.read
   crm.objects.contacts.write
   crm.objects.deals.read
   crm.objects.deals.write
   crm.objects.line_items.read
   crm.objects.line_items.write
   e-commerce
   ```

5. Click **Create app** and copy Personal Access Key

#### 3. Add Customer to GitHub Secrets

1. Go to GitHub repo → **Settings → Secrets → Actions**
2. Click **New repository secret**
3. Name: `CUSTOMER_{PORTAL_ID}_PAK`
   - Example: `CUSTOMER_12345678_PAK`
4. Value: (paste Personal Access Key)
5. Click **Add secret**

#### 4. Initial Deployment

1. Go to **Actions** tab
2. Select **"Deploy to Customer Tenant"**
3. Click **Run workflow**
4. Enter:
   - Portal ID: `12345678`
   - Customer Name: `Acme Corp`
   - Deployment Type: `initial_deployment`
   - Notify: ✅
5. Monitor deployment progress

#### 5. Verify in Customer Portal

1. Log into customer's HubSpot
2. Go to **Marketplace → Manage Apps**
3. Verify "HubSpot Ecommerce for WordPress" is listed
4. Check app is active and connected

#### 6. Send Onboarding Materials

- ✅ WordPress plugin download link
- ✅ Setup documentation
- ✅ OAuth connection guide
- ✅ Support contact information

---

## Deployment Scripts (npm)

Add these to `package.json`:

```json
{
  "scripts": {
    "deploy:dev": "hs project upload",
    "deploy:prod": "hs project upload --portal=production",
    "validate": "hs project validate",
    "watch": "hs project watch",
    "test": "node test-all-scopes.js"
  }
}
```

### Local Deployment Commands

```bash
# Deploy to default portal (development)
npm run deploy:dev

# Deploy to specific portal
hs project upload --portal=customer-name

# Watch for changes and auto-deploy
npm run watch

# Validate before deploying
npm run validate
```

---

## Environment Variables

### Local Development (.env)

Create `.env` file (already in `.gitignore`):

```bash
HUBSPOT_PORTAL_ID=42045718
HUBSPOT_PERSONAL_ACCESS_KEY=your-pak-here
CUSTOMER_NAME=development
```

### GitHub Actions (Secrets)

Set in GitHub repository settings:

**Development:**

```
HUBSPOT_PORTAL_ID
HUBSPOT_PERSONAL_ACCESS_KEY
```

**Per-Customer:**

```
CUSTOMER_{PORTAL_ID}_PAK
```

**Optional (for notifications):**

```
SLACK_WEBHOOK_URL
EMAIL_API_KEY
SENDGRID_API_KEY
```

---

## Deployment Checklist

### Before Each Deployment

- [ ] Code reviewed and approved
- [ ] Tests passing (validation workflow)
- [ ] No secrets in code
- [ ] Customer notified of deployment window
- [ ] Backup of current version available
- [ ] Rollback plan documented

### During Deployment

- [ ] Monitor GitHub Actions progress
- [ ] Check for errors in logs
- [ ] Verify deployment artifact created
- [ ] Confirm customer portal updated

### After Deployment

- [ ] Verify app functionality in customer portal
- [ ] Test OAuth connection
- [ ] Check WordPress plugin connection
- [ ] Send completion notification to customer
- [ ] Update deployment log

---

## Troubleshooting

### Deployment Fails: Authentication Error

**Error:**

```
Error: Unable to authenticate with HubSpot
```

**Solution:**

1. Verify Personal Access Key is correct in GitHub Secrets
2. Check PAK has required scopes (`developer.app.deploy`)
3. Ensure PAK hasn't expired
4. Regenerate PAK if needed

---

### Deployment Fails: Invalid Portal ID

**Error:**

```
Error: Portal ID not found
```

**Solution:**

1. Verify Portal ID is correct (8-digit number)
2. Check customer's HubSpot account is active
3. Ensure you're deploying to correct environment

---

### App Not Showing in Customer's HubSpot

**Issue:** App deployed successfully but not visible

**Solution:**

1. Log into customer's HubSpot portal
2. Go to **Marketplace → Manage Apps**
3. Check if app is in "Installed" section
4. If not visible, try:
   - Refresh the page
   - Clear cache (Ctrl+Shift+R)
   - Check app is published (not draft)
   - Verify customer has required plan tier

---

### OAuth Connection Fails After Deployment

**Issue:** WordPress plugin can't connect via OAuth

**Solution:**

1. Verify OAuth scopes in `src/app/app-hsmeta.json`
2. Check redirect URIs are correct
3. Ensure customer app is activated
4. Test OAuth flow with test credentials

---

### Rollback Procedure

If deployment causes issues:

**Option 1: Redeploy Previous Version**

```bash
# Check out previous release
git checkout v1.0.0

# Manually trigger deployment
# Actions → Deploy to Customer → Run workflow
```

**Option 2: Manual Rollback in HubSpot**

1. Log into customer's HubSpot
2. Go to **Marketplace → Manage Apps**
3. Find "HubSpot Ecommerce for WordPress"
4. Click **Uninstall** (temporarily)
5. Redeploy from previous commit

---

## Advanced: Multi-Tenant Deployment

### Deploy to Multiple Customers at Once

Create a workflow for batch deployments:

```yaml
# .github/workflows/batch-deploy.yml
name: Batch Deploy to Customers

on:
  workflow_dispatch:
    inputs:
      customer_list:
        description: 'Comma-separated Portal IDs'
        required: true
        type: string
        # Example: "12345678,87654321,11223344"

jobs:
  deploy-all:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        portal_id: ${{ fromJson(format('[{0}]', github.event.inputs.customer_list)) }}

    steps:
      - uses: actions/checkout@v4
      # ... deployment steps for each portal
```

---

## Monitoring & Logs

### Deployment Logs

**Location:** GitHub Actions → Workflow run → Artifacts

**Download:**

```bash
# Via GitHub CLI
gh run download <run-id>

# Or via web UI
Actions → Workflow run → Artifacts section
```

**Log Format:**

```json
{
  "customer": "Acme Corp",
  "portal_id": "12345678",
  "deployment_type": "update",
  "timestamp": "2025-01-24T12:00:00Z",
  "commit_sha": "abc123",
  "deployed_by": "github-username",
  "status": "success"
}
```

### Set Up Monitoring

**Slack Notifications:**

Add to workflow:

```yaml
- name: Notify Slack
  uses: slackapi/slack-github-action@v1
  with:
    webhook: ${{ secrets.SLACK_WEBHOOK_URL }}
    payload: |
      {
        "text": "Deployed to ${{ inputs.customer_name }}"
      }
```

---

## Security Best Practices

### Secret Management

✅ **DO:**

- Store all credentials in GitHub Secrets
- Use separate PAKs per customer
- Rotate PAKs every 90 days
- Limit PAK scopes to minimum required
- Use environment-specific secrets

❌ **DON'T:**

- Commit credentials to code
- Share PAKs across customers
- Use admin-level PAKs for deployment
- Store secrets in plain text files

### Access Control

- Limit who can trigger deployments
- Use GitHub environment protection rules
- Require manual approval for production
- Enable 2FA for all team members

---

## Support & Resources

### Documentation

- [HubSpot CLI Documentation](https://developers.hubspot.com/docs/cli/getting-started)
- [HubSpot App Deployment](https://developers.hubspot.com/docs/platform/create-an-app)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)

### Common Commands

```bash
# List configured portals
hs portals list

# Switch portal
hs portals use <portal-name>

# Validate project
hs project validate

# Upload project
hs project upload

# View app status
hs project status

# Watch for changes
hs project watch
```

---

## FAQ

**Q: How long does deployment take?**
A: Typically 2-5 minutes for a full deployment.

**Q: Can I deploy to multiple environments?**
A: Yes, use GitHub Environments feature with environment-specific secrets.

**Q: What if a customer wants to self-deploy?**
A: Provide them with the deployment artifact from GitHub Releases.

**Q: How do I know if deployment succeeded?**
A: Check GitHub Actions logs, verify in customer's HubSpot portal, test OAuth connection.

**Q: Can I schedule deployments?**
A: Yes, add a `schedule` trigger to the workflow with cron syntax.

---

## Changelog

### v1.0.0 (2025-01-24)

- Initial deployment workflows
- Customer onboarding process
- Validation and testing pipeline
- Documentation

---

**Last Updated:** 2025-01-24
**Maintained By:** Baur Software Development Team
