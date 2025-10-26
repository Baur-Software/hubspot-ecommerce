# Monorepo Migration - COMPLETED

**Status:** ✅ COMPLETE
**Completion Date:** October 24, 2024
**Document Type:** Historical Reference

This document originally outlined the plan to reorganize the HubSpot Ecommerce project into a unified monorepo structure. The migration has been successfully completed.

---

## Original Structure (Before Migration)

```
wp-plugins/
├── hubspot-ecommerce/              # WordPress plugin
└── hubspot-ecommerce-app/          # HubSpot companion app
```

---

## Implemented Monorepo Structure (Current)

```
hubspot-ecommerce/                  # Root monorepo
├── .github/
│   └── workflows/
│       ├── test-wordpress-plugin.yml
│       ├── deploy-hubspot-app.yml
│       ├── deploy-to-customer.yml
│       ├── release.yml
│       └── validate.yml
├── wordpress/                      # WordPress plugin (main product)
│   ├── hubspot-ecommerce.php
│   ├── includes/
│   ├── templates/
│   ├── assets/
│   ├── tests/
│   ├── composer.json
│   ├── package.json
│   ├── README.md
│   └── LICENSE
├── hubspot-app/                    # HubSpot companion app
│   ├── src/
│   ├── hsproject.json
│   ├── package.json
│   ├── README.md
│   └── *.js (test utilities)
├── docs/                           # Shared documentation
│   ├── getting-started.md
│   ├── architecture.md
│   ├── deployment.md
│   ├── api-scopes.md
│   └── troubleshooting.md
├── scripts/                        # Build and deployment scripts
│   ├── build-plugin.sh
│   ├── create-zip.sh
│   ├── deploy-all.sh
│   └── test-all.sh
├── .claude/                        # Claude AI context
│   ├── agents/
│   └── settings.json
├── package.json                    # Root workspace config
├── .gitignore                      # Root gitignore
├── README.md                       # Main project readme
├── LICENSE                         # Project license
├── CHANGELOG.md                    # Unified changelog
└── CONTRIBUTING.md                 # Contribution guide
```

---

## Benefits

### 1. Unified Version Management

- Single version number for both plugin and app
- Coordinated releases
- Clear compatibility matrix

### 2. Shared Dependencies

- Common dev dependencies (Playwright, ESLint, etc.)
- Shared configuration files
- Centralized CI/CD

### 3. Easier Development

- Single `git clone` gets everything
- Test both components together
- Shared documentation

### 4. Better GitHub Actions

- Deploy both components in one workflow
- Coordinated releases
- Single build pipeline

### 5. Simplified Customer Onboarding

- One repository to fork/download
- Clear project structure
- Comprehensive documentation in one place

---

## Migration Steps

### Phase 1: Prepare New Structure (30 min)

```bash
cd hubspot-ecommerce

# Create new directory structure
mkdir -p .github/workflows
mkdir -p docs
mkdir -p scripts

# Create root package.json
cat > package.json << 'EOF'
{
  "name": "hubspot-ecommerce-monorepo",
  "version": "1.0.0",
  "description": "Complete HubSpot ecommerce integration for WordPress",
  "private": true,
  "workspaces": [
    "wordpress",
    "hubspot-app"
  ],
  "scripts": {
    "test": "npm run test:wordpress && npm run test:hubspot",
    "test:wordpress": "cd wordpress && npm test",
    "test:hubspot": "cd hubspot-app && npm test",
    "build": "npm run build:wordpress && npm run build:hubspot",
    "build:wordpress": "cd wordpress && composer install && npm install",
    "build:hubspot": "cd hubspot-app && npm install",
    "deploy:hubspot": "cd hubspot-app && npm run deploy:dev",
    "lint": "npm run lint:wordpress && npm run lint:hubspot",
    "clean": "rm -rf */node_modules */vendor",
    "zip": "bash scripts/create-zip.sh"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/baursoftware/hubspot-ecommerce"
  },
  "keywords": [
    "hubspot",
    "wordpress",
    "ecommerce",
    "woocommerce",
    "oauth",
    "crm"
  ],
  "author": "Baur Software",
  "license": "GPL-2.0-or-later",
  "devDependencies": {
    "@playwright/test": "^1.40.0"
  },
  "engines": {
    "node": ">=20.0.0",
    "npm": ">=10.0.0"
  }
}
EOF
```

### Phase 2: Reorganize WordPress Plugin (15 min)

```bash
# Create wordpress directory
mkdir wordpress

# Move WordPress plugin files
mv hubspot-ecommerce.php wordpress/
mv includes wordpress/
mv templates wordpress/
mv assets wordpress/
mv tests wordpress/
mv composer.json wordpress/
mv composer.lock wordpress/
mv package.json wordpress/
mv package-lock.json wordpress/
mv playwright.config.js wordpress/
mv exclude-from-zip.txt wordpress/

# Keep root-level docs that apply to both
# Move WordPress-specific docs
mv README.md wordpress/
mv TESTING_GUIDE.md wordpress/
mv SUBSCRIPTIONS.md wordpress/
```

### Phase 3: Integrate Companion App (15 min)

```bash
# Move companion app (from ../hubspot-ecommerce-app)
mv ../hubspot-ecommerce-app hubspot-app

# Or copy if you want to keep both temporarily
cp -r ../hubspot-ecommerce-app hubspot-app
```

### Phase 4: Consolidate Documentation (20 min)

```bash
# Move shared docs to /docs
mkdir docs
mv wordpress/PRODUCTION_SETUP.md docs/
mv wordpress/LICENSE_SERVER_WOOCOMMERCE.md docs/
mv wordpress/OAUTH_INTEGRATION_STATUS.md docs/
mv wordpress/IMPLEMENTATION_COMPLETE.md docs/
mv wordpress/FEATURE_FLAG_IMPLEMENTATION.md docs/
mv wordpress/SECURITY_HARDENING_COMPLETE.md docs/
mv wordpress/GO_TO_MARKET_CHECKLIST.md docs/
mv hubspot-app/DEPLOYMENT.md docs/
mv hubspot-app/GITHUB_ACTIONS_SETUP.md docs/

# Create new root README
cat > README.md << 'EOF'
# HubSpot Ecommerce for WordPress

Complete HubSpot ecommerce integration solution with WordPress plugin and HubSpot Marketplace app.

## Quick Start

### WordPress Plugin
See [wordpress/README.md](wordpress/README.md)

### HubSpot Companion App
See [hubspot-app/README.md](hubspot-app/README.md)

### Documentation
See [docs/](docs/) directory

## Installation

```bash
# Install all dependencies
npm install

# Build WordPress plugin
npm run build:wordpress

# Deploy HubSpot app
npm run deploy:hubspot
```

## Testing

```bash
# Run all tests
npm test

# Test WordPress plugin
npm run test:wordpress

# Test HubSpot app
npm run test:hubspot
```

## License

GPL-2.0-or-later
EOF

```

### Phase 5: Update GitHub Actions (30 min)

```bash
# Copy GitHub Actions from both projects
cp -r wordpress/.github/workflows/* .github/workflows/
cp -r hubspot-app/.github/workflows/* .github/workflows/

# Update paths in workflows
# (paths will be updated in next step)
```

### Phase 6: Create Build Scripts (20 min)

```bash
mkdir scripts

# Create plugin zip script
cat > scripts/create-zip.sh << 'EOF'
#!/bin/bash
# Create WordPress plugin zip for distribution

cd wordpress
zip -r ../hubspot-ecommerce.zip . \
  -x "node_modules/*" \
  -x "vendor/*" \
  -x "tests/*" \
  -x "*.log" \
  -x ".git/*"
cd ..
echo "Created hubspot-ecommerce.zip"
EOF

chmod +x scripts/create-zip.sh

# Create deployment script
cat > scripts/deploy-all.sh << 'EOF'
#!/bin/bash
# Deploy both WordPress plugin and HubSpot app

echo "Building WordPress plugin..."
cd wordpress
composer install --no-dev
npm install --production
cd ..

echo "Deploying HubSpot app..."
cd hubspot-app
npm run deploy:dev
cd ..

echo "Creating plugin zip..."
bash scripts/create-zip.sh

echo "Deployment complete!"
EOF

chmod +x scripts/deploy-all.sh
```

### Phase 7: Update Paths (15 min)

Update all internal references:

**WordPress plugin wp-config references:**

- No changes needed (paths relative to plugin root)

**GitHub Actions workflows:**

- Update `working-directory: wordpress` where needed
- Update `working-directory: hubspot-app` where needed

**Documentation cross-references:**

- Update links to point to new locations
- Update relative paths in markdown

---

## Migration Checklist

### Pre-Migration

- [ ] Backup entire wp-plugins directory
- [ ] Commit all changes in both repos
- [ ] Note any local-only files not in git

### Structure Changes

- [ ] Create new directory structure
- [ ] Move WordPress plugin to /wordpress
- [ ] Move companion app to /hubspot-app
- [ ] Consolidate docs to /docs
- [ ] Create /scripts directory
- [ ] Set up root package.json

### Configuration Updates

- [ ] Update .gitignore (root level)
- [ ] Update GitHub Actions workflows
- [ ] Update all path references
- [ ] Update README files
- [ ] Create CONTRIBUTING.md

### Testing

- [ ] Test WordPress plugin still works
- [ ] Test composer install in /wordpress
- [ ] Test npm install in root
- [ ] Test GitHub Actions workflows
- [ ] Test deployment scripts

### Cleanup

- [ ] Remove old ../hubspot-ecommerce-app directory
- [ ] Remove duplicate files
- [ ] Remove old GitHub workflows
- [ ] Update .claude settings if needed

### Post-Migration

- [ ] Update git remote (if needed)
- [ ] Create new release/tag
- [ ] Update documentation links
- [ ] Notify team of new structure

---

## Root .gitignore

```gitignore
# Root-level ignores
node_modules/
*.log
.DS_Store

# WordPress plugin
wordpress/vendor/
wordpress/composer.lock
wordpress/node_modules/
wordpress/playwright-report/
wordpress/test-results/

# HubSpot app
hubspot-app/node_modules/
hubspot-app/hubspot.config.yml
hubspot-app/.env

# Build artifacts
*.zip
dist/

# IDE
.vscode/
.idea/
*.swp

# OS
Thumbs.db
.DS_Store
```

---

## GitHub Actions Updates

### Root Workflow: test-all.yml

```yaml
name: Test All

on:
  pull_request:
  push:
    branches: [main]

jobs:
  test-wordpress:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - name: Test WordPress Plugin
        working-directory: wordpress
        run: |
          npm ci
          npm test

  test-hubspot:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
      - name: Test HubSpot App
        working-directory: hubspot-app
        run: |
          npm ci
          npm run validate
```

### Root Workflow: release.yml

```yaml
name: Release

on:
  release:
    types: [published]

jobs:
  build-and-release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Build WordPress Plugin
        working-directory: wordpress
        run: |
          composer install --no-dev
          npm ci --production

      - name: Create Plugin Zip
        run: bash scripts/create-zip.sh

      - name: Upload Release Asset
        uses: actions/upload-release-asset@v1
        with:
          upload_url: ${{ github.event.release.upload_url }}
          asset_path: ./hubspot-ecommerce.zip
          asset_name: hubspot-ecommerce-${{ github.ref_name }}.zip

      - name: Deploy HubSpot App
        working-directory: hubspot-app
        env:
          HUBSPOT_PORTAL_ID: ${{ secrets.HUBSPOT_PORTAL_ID }}
          HUBSPOT_PERSONAL_ACCESS_KEY: ${{ secrets.HUBSPOT_PERSONAL_ACCESS_KEY }}
        run: npm run deploy:prod
```

---

## Documentation Structure

```
docs/
├── README.md                       # Documentation index
├── getting-started.md              # Quick start guide
├── architecture.md                 # System architecture
├── wordpress-plugin/
│   ├── installation.md
│   ├── configuration.md
│   ├── features.md
│   └── hooks-filters.md
├── hubspot-app/
│   ├── deployment.md
│   ├── oauth-setup.md
│   └── troubleshooting.md
├── production/
│   ├── setup-guide.md
│   ├── license-server.md
│   ├── security.md
│   └── wp-engine.md
├── development/
│   ├── local-setup.md
│   ├── testing.md
│   └── contributing.md
└── api/
    ├── scopes.md
    ├── oauth-flow.md
    └── endpoints.md
```

---

## Version Management

Use semantic versioning with synchronized versions:

```json
{
  "version": "1.0.0",
  "wordpress_plugin": "1.0.0",
  "hubspot_app": "1.0.0"
}
```

When releasing:

1. Update version in root package.json
2. Update version in wordpress/hubspot-ecommerce.php
3. Update version in hubspot-app/package.json
4. Update CHANGELOG.md
5. Create git tag: `v1.0.0`
6. GitHub Actions handles the rest

---

## Timeline

**Total Time:** ~2.5 hours

- Phase 1 (Prepare): 30 min
- Phase 2 (WordPress): 15 min
- Phase 3 (HubSpot App): 15 min
- Phase 4 (Docs): 20 min
- Phase 5 (Actions): 30 min
- Phase 6 (Scripts): 20 min
- Phase 7 (Paths): 15 min
- Testing: 15 min

---

## Rollback Plan

If migration causes issues:

```bash
# Restore from backup
cp -r /path/to/backup/hubspot-ecommerce .
cp -r /path/to/backup/hubspot-ecommerce-app .

# Or revert git commits
git revert HEAD~5..HEAD  # Last 5 commits
```

---

**Ready to execute migration?** Let me know and I'll help with each step!
