# GitHub Actions Setup - Quick Start

Complete guide to setting up automated deployments for the HubSpot companion app.

---

## 1. Initial Repository Setup (5 minutes)

### Create GitHub Repository

```bash
cd hubspot-ecommerce-app

# Initialize git (if not already done)
git init
git add .
git commit -m "Initial commit: HubSpot companion app with GitHub Actions"

# Create repository on GitHub.com
# Then push:
git remote add origin https://github.com/YOUR_USERNAME/hubspot-ecommerce-app.git
git branch -M main
git push -u origin main
```

---

## 2. Configure GitHub Secrets (10 minutes)

### Development/Testing Secrets

Go to: **Settings → Secrets and variables → Actions → New repository secret**

Add these secrets:

| Secret Name | Value | Description |
|-------------|-------|-------------|
| `HUBSPOT_PORTAL_ID` | `42045718` | Your development HubSpot Portal ID |
| `HUBSPOT_PERSONAL_ACCESS_KEY` | `YOUR_PAK_HERE` | Your Personal Access Key from HubSpot |

### How to Get Personal Access Key

1. Go to **HubSpot → Settings → Integrations → Private Apps**
2. Click **Create private app**
3. Name: "Development Deployment"
4. Scopes needed:
   - `developer.app.deploy`
   - `developer.app.read`
5. Click **Create app**
6. Copy the Personal Access Key
7. Add to GitHub Secrets

---

## 3. Add Customer Secrets (Per Customer)

For each customer you'll deploy to:

1. Get customer's Portal ID (8-digit number)
2. Create Private App in their HubSpot (same scopes as above)
3. Add secret with name: `CUSTOMER_{PORTAL_ID}_PAK`

**Example:**
- Customer Portal ID: `12345678`
- Secret Name: `CUSTOMER_12345678_PAK`
- Secret Value: (their Personal Access Key)

---

## 4. Test Deployment Workflow (5 minutes)

### Automatic Deployment (Push to Main)

```bash
# Make a small change
echo "# Test" >> README.md

# Commit and push
git add .
git commit -m "test: trigger deployment"
git push origin main
```

**What happens:**
- GitHub Actions automatically runs
- Validates code
- Deploys to development HubSpot portal
- You'll see progress in **Actions** tab

### Manual Deployment

1. Go to **Actions** tab
2. Select "Deploy to HubSpot" workflow
3. Click **Run workflow**
4. Select environment: `development`
5. Click **Run workflow**
6. Watch progress in real-time

---

## 5. Deploy to Customer (Manual)

### First Time Customer Setup

**Prerequisites:**
- Customer's Portal ID
- Customer's Personal Access Key (from their Private App)
- Added to GitHub Secrets as `CUSTOMER_{PORTAL_ID}_PAK`

**Deploy:**

1. Go to **Actions** tab
2. Select **"Deploy to Customer Tenant"**
3. Click **Run workflow**
4. Fill in:
   ```
   Customer Portal ID: 12345678
   Customer Name: Acme Corp
   Deployment Type: initial_deployment
   Notify Customer: ✅
   ```
5. Click **Run workflow**
6. Monitor progress (~2-5 minutes)

---

## 6. Verify Deployment Success

### Check GitHub Actions

- Green checkmark ✅ = Success
- Red X ❌ = Failed (click for logs)
- Download deployment logs from Artifacts

### Check Customer's HubSpot

1. Log into customer's HubSpot portal
2. Go to **Marketplace → Manage Apps**
3. Verify "HubSpot Ecommerce for WordPress" appears
4. Status should be "Active"

### Test OAuth Connection

From WordPress plugin:
1. Go to **HubSpot Shop → Connect to HubSpot**
2. Click "Connect"
3. Should redirect to HubSpot authorization
4. Grant permissions
5. Should redirect back to WordPress with success

---

## 7. Environments (Optional)

For staging/production separation:

1. Go to **Settings → Environments**
2. Create environments:
   - `development`
   - `staging`
   - `production`

3. Add environment-specific secrets:
   - Each environment has its own `HUBSPOT_PORTAL_ID`
   - Each environment has its own `HUBSPOT_PERSONAL_ACCESS_KEY`

4. Enable protection rules:
   - Production: Require approval
   - Staging: Auto-deploy on tag

---

## 8. Automated Releases

### Create Release Workflow

Push a tag to trigger production deployment:

```bash
# Tag the release
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

**What happens:**
- GitHub Actions deploys to production
- Creates release on GitHub
- Attaches deployment artifact (tar.gz)
- Available for download

---

## Common Workflows

### Development Cycle

```bash
# 1. Make changes
git checkout -b feature/new-feature
# ... make changes ...

# 2. Commit
git add .
git commit -m "feat: add new feature"

# 3. Push to branch (doesn't trigger deployment)
git push origin feature/new-feature

# 4. Create PR
# GitHub → Pull Requests → New PR

# 5. Merge to main (triggers automatic deployment to dev)
```

### Deploying Updates to All Customers

```bash
# Option 1: One by one via Actions UI
# - Go to Actions → Deploy to Customer
# - Run for each customer

# Option 2: Batch deploy (future feature)
# Use batch-deploy workflow with comma-separated Portal IDs
```

### Rollback

If deployment causes issues:

```bash
# Check out previous commit
git checkout v1.0.0

# Manually trigger deployment via Actions UI
# Actions → Deploy to Customer → Run workflow
```

---

## Troubleshooting

### Deployment Fails: Authentication Error

**Error in logs:**
```
Error: Unable to authenticate with HubSpot
```

**Fix:**
1. Check GitHub Secret `HUBSPOT_PERSONAL_ACCESS_KEY` is correct
2. Verify PAK hasn't expired
3. Ensure PAK has `developer.app.deploy` scope

### Workflow Doesn't Trigger

**Issue:** Push to main doesn't trigger deployment

**Fix:**
1. Check `.github/workflows/` directory exists
2. Verify workflow files are valid YAML
3. Check GitHub Actions is enabled:
   - Settings → Actions → General
   - Allow all actions

### Customer Secret Not Found

**Error:**
```
Error: Secret CUSTOMER_12345678_PAK not found
```

**Fix:**
1. Go to Settings → Secrets → Actions
2. Add secret: `CUSTOMER_12345678_PAK`
3. Re-run workflow

---

## Security Best Practices

### ✅ DO:
- Use GitHub Secrets for all credentials
- Rotate PAKs every 90 days
- Use separate PAKs per environment
- Enable 2FA on GitHub account
- Limit workflow permissions to minimum needed

### ❌ DON'T:
- Commit credentials to code
- Share PAKs between customers
- Use production PAK for development
- Allow public workflows to access secrets

---

## Monitoring & Alerts

### Set Up Notifications

**Slack Integration:**

```yaml
# Add to workflow
- name: Notify Slack
  if: failure()
  uses: slackapi/slack-github-action@v1
  with:
    webhook: ${{ secrets.SLACK_WEBHOOK_URL }}
    payload: |
      {
        "text": "❌ Deployment failed for ${{ inputs.customer_name }}"
      }
```

**Email Notifications:**

Go to: **Settings → Notifications**
- Enable: "Actions" notifications
- Send to: your email

---

## Deployment Checklist

Before deploying to customer:

- [ ] Code reviewed and approved
- [ ] Tests passing (validation workflow)
- [ ] Customer PAK added to GitHub Secrets
- [ ] Customer notified of deployment window
- [ ] Backup plan documented
- [ ] Monitor deployment in Actions tab
- [ ] Verify in customer's HubSpot portal
- [ ] Test OAuth connection from WordPress
- [ ] Send completion notification to customer

---

## Quick Reference

### GitHub Actions Commands

```bash
# View workflow runs
gh run list

# View specific run
gh run view <run-id>

# Download artifacts
gh run download <run-id>

# Trigger workflow manually
gh workflow run deploy-to-hubspot.yml
```

### npm Scripts

```bash
npm run deploy:dev      # Deploy to development
npm run deploy:prod     # Deploy to production
npm run validate        # Validate project
npm run watch          # Watch for changes
npm test               # Run tests
```

### HubSpot CLI Commands

```bash
hs portals list        # List configured portals
hs project upload      # Upload to default portal
hs project validate    # Validate project
hs project status      # Check deployment status
```

---

## Next Steps

1. ✅ Set up GitHub repository
2. ✅ Add development secrets
3. ✅ Test deployment to development
4. ⏳ Add first customer secret
5. ⏳ Deploy to first customer
6. ⏳ Document customer-specific configurations
7. ⏳ Set up monitoring and alerts
8. ⏳ Create runbook for common issues

---

**Last Updated:** 2025-01-24
**Node Version:** 20.x LTS
**GitHub Actions:** Enabled and tested
