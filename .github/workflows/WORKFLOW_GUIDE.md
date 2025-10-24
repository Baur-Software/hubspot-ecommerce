# GitHub Actions Workflow Guide

## Overview

This repository uses GitHub Actions for CI/CD automation. Three workflows are configured:

1. **CI** - Continuous Integration testing
2. **Release** - Automated release building and publishing
3. **Security Scan** - Dependency and secret scanning

## Workflows

### CI Workflow (`ci.yml`)

**Triggers:** Push and PR to `main` or `develop` branches

**Jobs:**
- `test-wordpress` - Runs Playwright tests that don't require WordPress
- `test-hubspot-app` - Runs HubSpot app tests
- `lint` - Code linting (if configured)
- `validate-structure` - Validates plugin structure and headers

**Requirements:**
- Tests run on Node 20
- Playwright browsers installed automatically
- No WordPress installation required for basic tests

### Release Workflow (`release.yml`)

**Triggers:** Pushing a version tag (e.g., `v1.0.0`, `v1.2.3`)

**What it does:**
1. Installs all dependencies (Node and Composer)
2. Builds production-ready plugin
3. Excludes files based on `wordpress/exclude-from-zip.txt`
4. Creates ZIP archive
5. Generates SHA256 checksum
6. Creates GitHub Release with:
   - Plugin ZIP file
   - SHA256 checksum file
   - Auto-generated release notes
7. Marks as pre-release if tag contains `alpha`, `beta`, or `rc`

**Creating a release:**
```bash
# Tag the release
git tag v1.0.0

# Push the tag
git push origin v1.0.0

# The workflow will automatically:
# - Build the plugin
# - Create a GitHub release
# - Attach the plugin ZIP
```

**Version format:**
- Stable: `v1.0.0`, `v2.1.5`
- Pre-release: `v1.0.0-alpha`, `v1.0.0-beta.1`, `v1.0.0-rc.1`

### Security Scan Workflow (`security-scan.yml`)

**Triggers:**
- Push to `main`
- Pull requests to `main`
- Weekly on Monday at 9 AM UTC

**What it scans:**
- npm dependencies (`npm audit`)
- Composer dependencies (`composer audit`)
- Secrets and credentials (TruffleHog)

**Note:** Some checks are set to `continue-on-error: true` and won't fail the build, but results should be reviewed.

## Setup Requirements

### For CI/CD to work properly:

1. **Repository Settings:**
   - Enable Actions: Settings → Actions → General → "Allow all actions"
   - Workflow permissions: Settings → Actions → General → "Read and write permissions"

2. **Branch Protection (Recommended):**
   - Protect `main` branch
   - Require status checks to pass before merging
   - Require CI workflow to pass

3. **Secrets (None required currently):**
   - `GITHUB_TOKEN` is automatically provided by GitHub Actions

### Optional: WordPress.org Deployment

If you want to deploy to WordPress.org plugin directory, you'll need to:
1. Add `WP_ORG_USERNAME` and `WP_ORG_PASSWORD` secrets
2. Create a separate workflow (let me know if you need this)

## Testing Locally

Before pushing tags, test the build process locally:

```bash
# Install dependencies
npm ci
cd wordpress && npm ci && composer install --no-dev

# Create a test build
mkdir -p build/hubspot-ecommerce
cp -r wordpress/* build/hubspot-ecommerce/

# Test the ZIP creation
cd build
zip -r hubspot-ecommerce-test.zip hubspot-ecommerce/
```

## Troubleshooting

### CI fails with "Playwright browsers not installed"
- The workflow automatically installs browsers with `npx playwright install --with-deps`
- If issues persist, check the Playwright version in `wordpress/package.json`

### Release workflow doesn't trigger
- Verify tag format: Must be `v*.*.*` (e.g., `v1.0.0`)
- Check Actions permissions in repository settings
- Ensure tag is pushed: `git push origin v1.0.0`

### Security scan finds vulnerabilities
- Review the audit output in the workflow logs
- Update dependencies: `npm audit fix` or `composer update`
- Some vulnerabilities may be in dev dependencies and not affect production

## Best Practices

1. **Before releasing:**
   - Run tests locally: `npm test`
   - Update version in `wordpress/hubspot-ecommerce.php`
   - Update CHANGELOG or README
   - Review `exclude-from-zip.txt` to ensure unnecessary files are excluded

2. **Semantic Versioning:**
   - MAJOR: Breaking changes (v2.0.0)
   - MINOR: New features (v1.1.0)
   - PATCH: Bug fixes (v1.0.1)

3. **Pre-releases:**
   - Use for testing: `v1.0.0-beta.1`
   - Automatically marked as pre-release in GitHub

4. **Commit messages:**
   - Clear, descriptive commit messages help with auto-generated release notes
   - Use conventional commits format (optional but recommended):
     - `feat: Add new feature`
     - `fix: Fix bug`
     - `docs: Update documentation`

## Monitoring

- **Action status:** Check the "Actions" tab in GitHub
- **Failed workflows:** Review logs and re-run if needed
- **Security alerts:** Check "Security" tab for Dependabot alerts
- **Release history:** Check "Releases" page for all published versions

## Future Enhancements

Consider adding:
- Code coverage reporting
- Performance testing
- Automated WordPress.org deployment
- Slack/Discord notifications
- Lighthouse CI for performance metrics
- PHP CodeSniffer for WordPress coding standards
