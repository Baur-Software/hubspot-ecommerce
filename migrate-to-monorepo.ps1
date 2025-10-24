# PowerShell script to migrate to monorepo structure
# Run from: C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "HubSpot Ecommerce Monorepo Migration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check we're in the right directory
if (-not (Test-Path "hubspot-ecommerce.php")) {
    Write-Host "ERROR: Not in hubspot-ecommerce directory!" -ForegroundColor Red
    Write-Host "Please run from: C:\Users\Todd\Projects\wp-plugins\hubspot-ecommerce" -ForegroundColor Yellow
    exit 1
}

# Confirm before proceeding
$confirm = Read-Host "This will reorganize the repository structure. Continue? (yes/no)"
if ($confirm -ne "yes") {
    Write-Host "Migration cancelled." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "[1/7] Creating new directory structure..." -ForegroundColor Green

# Create root directories
if (-not (Test-Path "docs")) {
    New-Item -ItemType Directory -Path "docs" | Out-Null
}
if (-not (Test-Path "scripts")) {
    New-Item -ItemType Directory -Path "scripts" | Out-Null
}
if (-not (Test-Path ".github")) {
    New-Item -ItemType Directory -Path ".github" | Out-Null
}
if (-not (Test-Path ".github\workflows")) {
    New-Item -ItemType Directory -Path ".github\workflows" | Out-Null
}

Write-Host "  ✓ Created docs/, scripts/, .github/workflows/" -ForegroundColor Gray

Write-Host ""
Write-Host "[2/7] Moving WordPress plugin to /wordpress..." -ForegroundColor Green

# Create wordpress directory
if (-not (Test-Path "wordpress")) {
    New-Item -ItemType Directory -Path "wordpress" | Out-Null
}

# Move WordPress plugin files
$wpFiles = @(
    "hubspot-ecommerce.php",
    "includes",
    "templates",
    "assets",
    "tests",
    "composer.json",
    "package.json",
    "package-lock.json",
    "playwright.config.js",
    "exclude-from-zip.txt"
)

foreach ($file in $wpFiles) {
    if (Test-Path $file) {
        try {
            Move-Item -Path $file -Destination "wordpress\" -Force -ErrorAction Stop
            Write-Host "  ✓ Moved $file" -ForegroundColor Gray
        } catch {
            Write-Host "  ⚠ Could not move $file : $_" -ForegroundColor Yellow
        }
    }
}

# Move WordPress-specific docs
$wpDocs = @(
    "README.md",
    "TESTING_GUIDE.md",
    "SUBSCRIPTIONS.md",
    "ISSUES_REPORT.md"
)

foreach ($doc in $wpDocs) {
    if (Test-Path $doc) {
        try {
            Move-Item -Path $doc -Destination "wordpress\" -Force -ErrorAction Stop
            Write-Host "  ✓ Moved $doc to wordpress/" -ForegroundColor Gray
        } catch {
            Write-Host "  ⚠ Could not move $doc : $_" -ForegroundColor Yellow
        }
    }
}

Write-Host ""
Write-Host "[3/7] Integrating HubSpot companion app..." -ForegroundColor Green

# Check if companion app exists
$appSource = "..\hubspot-ecommerce-app"
if (Test-Path $appSource) {
    try {
        # Create hubspot-app directory
        if (-not (Test-Path "hubspot-app")) {
            New-Item -ItemType Directory -Path "hubspot-app" | Out-Null
        }

        # Copy companion app to hubspot-app
        Copy-Item -Path "$appSource\*" -Destination "hubspot-app" -Recurse -Force -ErrorAction Stop
        Write-Host "  ✓ Copied companion app to hubspot-app/" -ForegroundColor Gray

        # Remove .git directory from copied app
        if (Test-Path "hubspot-app\.git") {
            Remove-Item -Path "hubspot-app\.git" -Recurse -Force -ErrorAction SilentlyContinue
            Write-Host "  ✓ Removed hubspot-app/.git (will use root git)" -ForegroundColor Gray
        }
    } catch {
        Write-Host "  ⚠ Error copying companion app: $_" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ⚠ Companion app not found at $appSource" -ForegroundColor Yellow
    Write-Host "  You'll need to manually add it later" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[4/7] Consolidating documentation..." -ForegroundColor Green

# Move shared docs to /docs
$sharedDocs = @(
    "PRODUCTION_SETUP.md",
    "LICENSE_SERVER_WOOCOMMERCE.md",
    "LICENSE_SERVER_SETUP_STEPS.md",
    "OAUTH_INTEGRATION_STATUS.md",
    "IMPLEMENTATION_COMPLETE.md",
    "FEATURE_FLAG_IMPLEMENTATION.md",
    "SECURITY_HARDENING_COMPLETE.md",
    "GO_TO_MARKET_CHECKLIST.md",
    "SCOPE_ANALYSIS.md",
    "API_SCOPE_FINDINGS.md",
    "PRIVATE_APP_SETUP.md",
    "VENDOR_STRATEGY.md"
)

foreach ($doc in $sharedDocs) {
    if (Test-Path $doc) {
        try {
            Move-Item -Path $doc -Destination "docs\" -Force -ErrorAction Stop
            Write-Host "  ✓ Moved $doc to docs/" -ForegroundColor Gray
        } catch {
            Write-Host "  ⚠ Could not move $doc : $_" -ForegroundColor Yellow
        }
    }
}

# Move HubSpot app docs
if (Test-Path "hubspot-app") {
    $appDocs = @("DEPLOYMENT.md", "GITHUB_ACTIONS_SETUP.md")
    foreach ($doc in $appDocs) {
        if (Test-Path "hubspot-app\$doc") {
            try {
                Move-Item -Path "hubspot-app\$doc" -Destination "docs\" -Force -ErrorAction Stop
                Write-Host "  ✓ Moved $doc from hubspot-app to docs/" -ForegroundColor Gray
            } catch {
                Write-Host "  ⚠ Could not move $doc : $_" -ForegroundColor Yellow
            }
        }
    }
}

Write-Host ""
Write-Host "[5/7] Creating root configuration files..." -ForegroundColor Green

# Create root package.json
$packageJson = @'
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
    "lint": "echo \"Linting not yet configured\"",
    "clean": "powershell -Command \"Remove-Item -Recurse -Force wordpress/node_modules, hubspot-app/node_modules -ErrorAction SilentlyContinue\"",
    "zip": "powershell -Command \".\\scripts\\create-zip.ps1\""
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
'@

Set-Content -Path "package.json" -Value $packageJson
Write-Host "  ✓ Created root package.json" -ForegroundColor Gray

# Create root README
$readmeContent = @'
# HubSpot Ecommerce for WordPress

Complete HubSpot ecommerce integration solution with WordPress plugin and HubSpot Marketplace app.

## 📦 What's Included

- **WordPress Plugin** (`/wordpress`) - Full-featured ecommerce plugin using HubSpot as backend
- **HubSpot Companion App** (`/hubspot-app`) - OAuth authentication and marketplace integration
- **Documentation** (`/docs`) - Comprehensive guides and API documentation
- **Scripts** (`/scripts`) - Build and deployment automation

## 🚀 Quick Start

### Install Dependencies

```bash
# Install all workspace dependencies
npm install

# Build WordPress plugin
npm run build:wordpress

# Build HubSpot app
npm run build:hubspot
```

### WordPress Plugin

See [wordpress/README.md](wordpress/README.md) for installation and configuration.

### HubSpot Companion App

See [hubspot-app/README.md](hubspot-app/README.md) for deployment instructions.

## 📚 Documentation

- [Getting Started](docs/GO_TO_MARKET_CHECKLIST.md)
- [Production Setup](docs/PRODUCTION_SETUP.md)
- [License Server Setup](docs/LICENSE_SERVER_SETUP_STEPS.md)
- [OAuth Integration](docs/OAUTH_INTEGRATION_STATUS.md)
- [Security Guide](docs/SECURITY_HARDENING_COMPLETE.md)
- [Deployment Guide](docs/DEPLOYMENT.md)

## 🧪 Testing

```bash
# Run all tests
npm test

# Test WordPress plugin only
npm run test:wordpress

# Test HubSpot app only
npm run test:hubspot
```

## 🔨 Development

```bash
# Watch for changes in WordPress plugin
cd wordpress && npm run watch

# Watch for changes in HubSpot app
cd hubspot-app && npm run watch
```

## 📦 Building for Release

```bash
# Create WordPress plugin zip
npm run zip

# Deploy HubSpot app
npm run deploy:hubspot
```

## 🗂️ Repository Structure

```
hubspot-ecommerce/
├── wordpress/              # WordPress plugin
├── hubspot-app/           # HubSpot companion app
├── docs/                  # Documentation
├── scripts/               # Build scripts
├── .github/workflows/     # CI/CD workflows
└── package.json           # Root workspace config
```

## 📝 License

GPL-2.0-or-later

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

## 💬 Support

- Email: support@baursoftware.com
- Documentation: https://baursoftware.com/docs/hubspot-ecommerce
- Issues: https://github.com/baursoftware/hubspot-ecommerce/issues

---

**Maintained by:** Baur Software
**Version:** 1.0.0
'@

Set-Content -Path "README.md" -Value $readmeContent
Write-Host "  ✓ Created root README.md" -ForegroundColor Gray

# Update root .gitignore
$gitignoreContent = @'
# Root-level ignores
node_modules/
*.log
.DS_Store
.env

# WordPress plugin
wordpress/vendor/
wordpress/composer.lock
wordpress/node_modules/
wordpress/playwright-report/
wordpress/test-results/
wordpress/.env

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
*.swo
*~

# OS
Thumbs.db
._*
.Spotlight-V100
.Trashes
'@

Set-Content -Path ".gitignore" -Value $gitignoreContent
Write-Host "  ✓ Updated root .gitignore" -ForegroundColor Gray

Write-Host ""
Write-Host "[6/7] Creating build scripts..." -ForegroundColor Green

# Create plugin zip script (PowerShell version)
$zipScript = @'
# Create WordPress plugin zip for distribution
$pluginDir = "wordpress"
$outputFile = "hubspot-ecommerce.zip"

Write-Host "Creating plugin zip..." -ForegroundColor Cyan

# Remove old zip if exists
if (Test-Path $outputFile) {
    Remove-Item $outputFile -Force
}

# Create zip excluding development files
$exclude = @(
    "node_modules",
    "vendor",
    "tests",
    "*.log",
    ".git",
    ".github",
    "playwright-report",
    "test-results"
)

# Compress plugin directory
Compress-Archive -Path "$pluginDir\*" -DestinationPath $outputFile -Force

Write-Host "Created $outputFile" -ForegroundColor Green
'@

Set-Content -Path "scripts\create-zip.ps1" -Value $zipScript
Write-Host "  ✓ Created scripts/create-zip.ps1" -ForegroundColor Gray

Write-Host ""
Write-Host "[7/7] Finalizing migration..." -ForegroundColor Green

# Keep migration plan for reference
if (Test-Path "MONOREPO_MIGRATION_PLAN.md") {
    try {
        Move-Item -Path "MONOREPO_MIGRATION_PLAN.md" -Destination "docs\" -Force -ErrorAction Stop
        Write-Host "  ✓ Moved MONOREPO_MIGRATION_PLAN.md to docs/" -ForegroundColor Gray
    } catch {
        Write-Host "  ⚠ Could not move MONOREPO_MIGRATION_PLAN.md: $_" -ForegroundColor Yellow
    }
}

# Copy this migration script to scripts (don't move, we're running it!)
try {
    Copy-Item -Path "migrate-to-monorepo.ps1" -Destination "scripts\migrate-to-monorepo.ps1" -Force -ErrorAction Stop
    Write-Host "  ✓ Copied migration script to scripts/" -ForegroundColor Gray
} catch {
    Write-Host "  ⚠ Could not copy migration script: $_" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Migration Complete! ✓" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "New Structure:" -ForegroundColor Yellow
Write-Host "  wordpress/          - WordPress plugin" -ForegroundColor Gray
Write-Host "  hubspot-app/        - HubSpot companion app" -ForegroundColor Gray
Write-Host "  docs/               - Documentation" -ForegroundColor Gray
Write-Host "  scripts/            - Build scripts" -ForegroundColor Gray
Write-Host "  .github/workflows/  - CI/CD workflows" -ForegroundColor Gray
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "  1. Review the new structure" -ForegroundColor Gray
Write-Host "  2. Run: npm install" -ForegroundColor Gray
Write-Host "  3. Test: npm test" -ForegroundColor Gray
Write-Host "  4. Commit changes: git add . && git commit -m 'refactor: migrate to monorepo structure'" -ForegroundColor Gray
Write-Host ""
Write-Host "Documentation: docs/MONOREPO_MIGRATION_PLAN.md" -ForegroundColor Cyan
Write-Host ""
