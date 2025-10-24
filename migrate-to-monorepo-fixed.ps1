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
if (-not (Test-Path "docs")) { New-Item -ItemType Directory -Path "docs" | Out-Null }
if (-not (Test-Path "scripts")) { New-Item -ItemType Directory -Path "scripts" | Out-Null }
if (-not (Test-Path ".github")) { New-Item -ItemType Directory -Path ".github" | Out-Null }
if (-not (Test-Path ".github\workflows")) { New-Item -ItemType Directory -Path ".github\workflows" | Out-Null }

Write-Host "  ✓ Created docs/, scripts/, .github/workflows/" -ForegroundColor Gray

Write-Host ""
Write-Host "[2/7] Moving WordPress plugin to /wordpress..." -ForegroundColor Green

# Create wordpress directory
if (-not (Test-Path "wordpress")) { New-Item -ItemType Directory -Path "wordpress" | Out-Null }

# Move WordPress plugin files
$wpFiles = @("hubspot-ecommerce.php", "includes", "templates", "assets", "tests", "composer.json", "package.json", "package-lock.json", "playwright.config.js", "exclude-from-zip.txt")

foreach ($file in $wpFiles) {
    if (Test-Path $file) {
        try {
            Move-Item -Path $file -Destination "wordpress\" -Force -ErrorAction Stop
            Write-Host "  ✓ Moved $file" -ForegroundColor Gray
        } catch {
            Write-Host "  ⚠ Could not move $file" -ForegroundColor Yellow
        }
    }
}

# Move WordPress-specific docs
$wpDocs = @("README.md", "TESTING_GUIDE.md", "SUBSCRIPTIONS.md", "ISSUES_REPORT.md")
foreach ($doc in $wpDocs) {
    if (Test-Path $doc) {
        try {
            Move-Item -Path $doc -Destination "wordpress\" -Force -ErrorAction Stop
            Write-Host "  ✓ Moved $doc to wordpress/" -ForegroundColor Gray
        } catch {}
    }
}

Write-Host ""
Write-Host "[3/7] Integrating HubSpot companion app..." -ForegroundColor Green

$appSource = "..\hubspot-ecommerce-app"
if (Test-Path $appSource) {
    try {
        if (-not (Test-Path "hubspot-app")) { New-Item -ItemType Directory -Path "hubspot-app" | Out-Null }
        Copy-Item -Path "$appSource\*" -Destination "hubspot-app" -Recurse -Force -ErrorAction Stop
        Write-Host "  ✓ Copied companion app to hubspot-app/" -ForegroundColor Gray

        if (Test-Path "hubspot-app\.git") {
            Remove-Item -Path "hubspot-app\.git" -Recurse -Force -ErrorAction SilentlyContinue
            Write-Host "  ✓ Removed hubspot-app/.git" -ForegroundColor Gray
        }
    } catch {
        Write-Host "  ⚠ Error copying companion app" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ⚠ Companion app not found at $appSource" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[4/7] Consolidating documentation..." -ForegroundColor Green

$sharedDocs = @("PRODUCTION_SETUP.md", "LICENSE_SERVER_WOOCOMMERCE.md", "LICENSE_SERVER_SETUP_STEPS.md", "OAUTH_INTEGRATION_STATUS.md", "IMPLEMENTATION_COMPLETE.md", "FEATURE_FLAG_IMPLEMENTATION.md", "SECURITY_HARDENING_COMPLETE.md", "GO_TO_MARKET_CHECKLIST.md", "SCOPE_ANALYSIS.md", "API_SCOPE_FINDINGS.md", "PRIVATE_APP_SETUP.md", "VENDOR_STRATEGY.md")

foreach ($doc in $sharedDocs) {
    if (Test-Path $doc) {
        try {
            Move-Item -Path $doc -Destination "docs\" -Force -ErrorAction Stop
            Write-Host "  ✓ Moved $doc to docs/" -ForegroundColor Gray
        } catch {}
    }
}

if (Test-Path "hubspot-app") {
    $appDocs = @("DEPLOYMENT.md", "GITHUB_ACTIONS_SETUP.md")
    foreach ($doc in $appDocs) {
        if (Test-Path "hubspot-app\$doc") {
            try {
                Move-Item -Path "hubspot-app\$doc" -Destination "docs\" -Force -ErrorAction Stop
                Write-Host "  ✓ Moved $doc from hubspot-app to docs/" -ForegroundColor Gray
            } catch {}
        }
    }
}

Write-Host ""
Write-Host "[5/7] Creating root configuration files..." -ForegroundColor Green

# Create package.json using Out-File
@"
{
  "name": "hubspot-ecommerce-monorepo",
  "version": "1.0.0",
  "description": "Complete HubSpot ecommerce integration for WordPress",
  "private": true,
  "workspaces": ["wordpress", "hubspot-app"],
  "scripts": {
    "test": "npm run test:wordpress && npm run test:hubspot",
    "test:wordpress": "cd wordpress && npm test",
    "test:hubspot": "cd hubspot-app && npm test"
  },
  "repository": {
    "type": "git",
    "url": "https://github.com/baursoftware/hubspot-ecommerce"
  },
  "author": "Baur Software",
  "license": "GPL-2.0-or-later",
  "engines": {
    "node": ">=20.0.0"
  }
}
"@ | Out-File -FilePath "package.json" -Encoding UTF8
Write-Host "  ✓ Created root package.json" -ForegroundColor Gray

# Create README
@"
# HubSpot Ecommerce for WordPress

Complete HubSpot ecommerce integration solution.

## Structure

- **wordpress/** - WordPress plugin
- **hubspot-app/** - HubSpot companion app
- **docs/** - Documentation

## Quick Start

``````bash
npm install
``````

See [docs/](docs/) for more information.
"@ | Out-File -FilePath "README.md" -Encoding UTF8
Write-Host "  ✓ Created root README.md" -ForegroundColor Gray

# Create .gitignore
@"
node_modules/
*.log
.DS_Store
wordpress/vendor/
hubspot-app/hubspot.config.yml
hubspot-app/.env
*.zip
"@ | Out-File -FilePath ".gitignore" -Encoding UTF8
Write-Host "  ✓ Updated root .gitignore" -ForegroundColor Gray

Write-Host ""
Write-Host "[6/7] Creating build scripts..." -ForegroundColor Green

# Simple zip script
@"
Write-Host "Creating plugin zip..." -ForegroundColor Cyan
if (Test-Path "hubspot-ecommerce.zip") { Remove-Item "hubspot-ecommerce.zip" -Force }
Compress-Archive -Path "wordpress\*" -DestinationPath "hubspot-ecommerce.zip" -Force
Write-Host "Created hubspot-ecommerce.zip" -ForegroundColor Green
"@ | Out-File -FilePath "scripts\create-zip.ps1" -Encoding UTF8
Write-Host "  ✓ Created scripts/create-zip.ps1" -ForegroundColor Gray

Write-Host ""
Write-Host "[7/7] Finalizing migration..." -ForegroundColor Green

if (Test-Path "MONOREPO_MIGRATION_PLAN.md") {
    try {
        Move-Item -Path "MONOREPO_MIGRATION_PLAN.md" -Destination "docs\" -Force -ErrorAction Stop
        Write-Host "  ✓ Moved MONOREPO_MIGRATION_PLAN.md to docs/" -ForegroundColor Gray
    } catch {}
}

try {
    Copy-Item -Path "migrate-to-monorepo-fixed.ps1" -Destination "scripts\" -Force -ErrorAction Stop
    Write-Host "  ✓ Copied migration script to scripts/" -ForegroundColor Gray
} catch {}

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
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "  1. Review the new structure" -ForegroundColor Gray
Write-Host "  2. Run: npm install" -ForegroundColor Gray
Write-Host "  3. Commit: git add . && git commit -m 'refactor: migrate to monorepo'" -ForegroundColor Gray
Write-Host ""
