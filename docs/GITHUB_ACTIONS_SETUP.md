# GitHub Actions CI/CD Setup

Complete guide to the automated testing, validation, and release workflows for the HubSpot Ecommerce WordPress plugin.

## Overview

This project uses GitHub Actions for continuous integration (CI), security scanning, and automated releases. The workflows ensure code quality, security, and reliable plugin distribution.

## Workflows

### 1. CI Workflow (ci.yml)

**Triggers:**

- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

**Jobs:**

**test-wordpress** - Tests the WordPress plugin

- Runs on Ubuntu latest
- Node.js 20 with npm caching
- Installs Playwright browsers
- Runs `npm run test:no-wp` (tests that don't require WordPress environment)

**test-hubspot-app** - Tests the HubSpot companion app

- Runs on Ubuntu latest
- Node.js 20 with npm caching
- Installs dependencies and runs tests
- Currently continues on error until tests are implemented

**lint** - Code quality checks

- Checks for lint scripts in all workspaces
- Runs linting for root, wordpress, and hubspot-app
- Continues on error (informational only)

**validate-structure** - Validates plugin structure

- Checks required files exist:
  - `wordpress/hubspot-ecommerce.php`
  - `wordpress/composer.json`
  - `wordpress/README.md`
  - `package.json`
- Validates plugin header contains required fields

**Example CI Run:**

```bash
# Push to main triggers CI
git push origin main

# Or create PR
git checkout -b feature/my-feature
git push origin feature/my-feature
# Create PR on GitHub
```

### 2. Security Scan Workflow (security-scan.yml)

**Triggers:**

- Push to `main` branch
- Pull requests to `main` branch
- Weekly schedule (Mondays at 9 AM UTC)

**Jobs:**

**dependency-scan** - Scans dependencies for vulnerabilities

- Runs `npm audit` on all workspaces (root, wordpress, hubspot-app)
- Runs `composer audit` on WordPress plugin
- Continues on error (informational)

**secret-scan** - Scans for exposed secrets

- Uses TruffleHog to scan entire repository
- Only reports verified secrets
- Checks commit history for leaked credentials

**Example:**

```bash
# Security scan runs automatically on push to main
git push origin main

# Or manually trigger via Actions tab:
# Actions → Security Scan → Run workflow
```

### 3. Release Workflow (release.yml)

**Triggers:**

- Version tags matching `v*.*.*` (e.g., `v1.0.0`, `v1.2.3`)

**Process:**

1. **Build preparation**
   - Checks out code
   - Sets up Node.js 20 and PHP 8.1
   - Extracts version from git tag

2. **Dependency installation**
   - Installs npm dependencies in all workspaces
   - Runs `composer install --no-dev --optimize-autoloader`

3. **Version update**
   - Updates version in plugin header
   - Updates `HUBSPOT_ECOMMERCE_VERSION` constant

4. **Build plugin**
   - Creates `build/hubspot-ecommerce/` directory
   - Copies WordPress plugin files
   - Removes excluded files per `exclude-from-zip.txt`
   - Verifies plugin structure

5. **Create artifacts**
   - Creates ZIP archive: `hubspot-ecommerce-{version}.zip`
   - Generates SHA256 checksum

6. **GitHub Release**
   - Creates GitHub release with auto-generated notes
   - Attaches ZIP and checksum files
   - Marks as prerelease if tag contains `alpha`, `beta`, or `rc`
   - Uploads artifact (retained for 30 days)

**Example Release:**

```bash
# Tag a new version
git tag -a v1.0.0 -m "Release v1.0.0: Initial public release"

# Push the tag (triggers release workflow)
git push origin v1.0.0

# GitHub Actions will:
# - Build the plugin
# - Create a ZIP file
# - Create a GitHub release
# - Attach the ZIP and checksum
```

**Release Assets:**

- `hubspot-ecommerce-1.0.0.zip` - Plugin ZIP ready for WordPress installation
- `hubspot-ecommerce-1.0.0.zip.sha256` - Checksum for integrity verification

## Monorepo Structure

The project uses npm workspaces with three components:

```text
hubspot-ecommerce/
├── package.json                    # Root workspace
├── wordpress/                      # WordPress plugin
│   ├── package.json
│   └── composer.json
└── hubspot-app/                    # HubSpot OAuth companion app
    └── package.json
```

**npm Caching:**

All workflows use npm caching with wildcard pattern:

```yaml
cache: 'npm'
cache-dependency-path: '**/package-lock.json'
```

This caches dependencies for all three workspaces, significantly speeding up workflow runs.

## Dependabot Configuration

**Automated dependency updates** via `.github/dependabot.yml`:

**npm packages:**

- Root workspace
- WordPress workspace
- HubSpot app workspace

**Composer packages:**

- WordPress plugin (Guzzle, PHPUnit)

**GitHub Actions:**

- All action versions (checkout, setup-node, etc.)

**Schedule:** Weekly on Mondays

**Auto-reviewers:** Assigned based on configuration

**Example Dependabot PR:**

```text
Bump actions/checkout from 4 to 5
Bump actions/setup-node from 5 to 6
Bump guzzlehttp/guzzle from 7.8.0 to 7.8.1
```

## Requirements

**Node.js:** 20.x LTS or later

**PHP:** 8.1 or later

**Composer:** 2.x

**GitHub Actions permissions:**

- `contents: write` (for releases)
- Standard permissions for other workflows

## Local Testing with Act

You can test workflows locally using [nektos/act](https://github.com/nektos/act):

```bash
# Install act
# See: https://github.com/nektos/act#installation

# Test CI workflow
act push

# Test specific job
act -j test-wordpress

# Test with specific event
act pull_request
```

**Note:** Some features may not work in act (e.g., Node.js 24 actions). Workflows are tested and verified on GitHub Actions.

## Workflow Status

Check workflow status:

**GitHub UI:**

1. Go to repository **Actions** tab
2. View recent workflow runs
3. Click any run for detailed logs

**GitHub CLI:**

```bash
# List recent runs
gh run list

# View specific run
gh run view <run-id>

# Download artifacts
gh run download <run-id>

# Re-run failed workflow
gh run rerun <run-id>
```

**Status badges** (add to README):

```markdown
![CI](https://github.com/baursoftware/hubspot-ecommerce/\
actions/workflows/ci.yml/badge.svg)
![Security](https://github.com/baursoftware/hubspot-ecommerce/\
actions/workflows/security-scan.yml/badge.svg)
```

## Troubleshooting

### npm Cache Miss

**Issue:** "Dependencies lock file is not found"

**Fix:** Ensure `package-lock.json` exists in workspace directories:

```bash
npm install  # in root
cd wordpress && npm install
cd ../hubspot-app && npm install
git add **/package-lock.json
git commit -m "fix: add package-lock.json files"
```

### Composer Not Found

**Issue:** "composer: command not found"

**Fix:** Workflow includes `shivammathur/setup-php@v2` with
`tools: composer`. If issue persists, check PHP version compatibility.

### TruffleHog: Commits Are the Same

**Issue:** "BASE and HEAD commits are the same"

**Fix:** Already resolved. Workflow scans entire repository instead of
commit range:

```yaml
- uses: trufflesecurity/trufflehog@main
  with:
    path: ./
    extra_args: --only-verified
```

### Validation Failure

**Issue:** Required files missing

**Fix:** Ensure these files exist in correct locations:

- `wordpress/hubspot-ecommerce.php` - Main plugin file
- `wordpress/composer.json` - PHP dependencies
- `wordpress/README.md` - WordPress readme
- `package.json` - Root workspace config

### Release Build Fails

**Issue:** ZIP missing files or wrong structure

**Fix:**

1. Check `exclude-from-zip.txt` patterns
2. Verify plugin structure in workflow logs
3. Test build locally:

```bash
cd wordpress
composer install --no-dev --optimize-autoloader
cd ..
mkdir -p build/hubspot-ecommerce
cp -r wordpress/* build/hubspot-ecommerce/
cd build && zip -r ../test.zip hubspot-ecommerce/
```

## Security Best Practices

**Secrets management:**

- Never commit credentials
- Use GitHub Secrets for sensitive data
- Rotate secrets regularly

**Dependency security:**

- Review Dependabot PRs before merging
- Monitor security scan results
- Update dependencies promptly

**Workflow security:**

- Limit workflow permissions
- Use specific action versions (not `@latest`)
- Review workflow changes in PRs

**Code review:**

- Require PR reviews before merge
- Run CI checks on all PRs
- Block merge on failing checks

## Common Tasks

### Adding a New Dependency

```bash
# Add npm dependency
cd wordpress
npm install --save new-package
git add package.json package-lock.json
git commit -m "deps: add new-package"

# Add composer dependency
cd wordpress
composer require vendor/package
git add composer.json composer.lock
git commit -m "deps: add vendor/package"

# Dependabot will monitor these automatically
```

### Creating a Release

```bash
# Update version and changelog
# Then tag:
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0

# Monitor release: Actions tab
# Download from: Releases page
```

### Updating GitHub Actions

Dependabot automatically creates PRs for action updates. Manually update:

```yaml
# Before
uses: actions/checkout@v4

# After
uses: actions/checkout@v5
```

Commit, push, and verify workflows still pass.

## Next Steps

1. Review workflow configurations in `.github/workflows/`
2. Enable branch protection rules for `main`
3. Configure required status checks
4. Set up GitHub Environments (staging/production)
5. Add workflow status badges to README
6. Configure notification preferences
7. Review and merge Dependabot PRs

## References

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Workflow Syntax](https://docs.github.com/en/actions/using-workflows/workflow-syntax-for-github-actions)
- [Dependabot Configuration](https://docs.github.com/en/code-security/dependabot/dependabot-version-updates/configuration-options-for-the-dependabot.yml-file)
- [nektos/act (Local Testing)](https://github.com/nektos/act)

**Last Updated:** 2025-01-26

**GitHub Actions Version:** v5 (checkout), v6 (setup-node)

**Workflows:** CI, Security Scan, Release
