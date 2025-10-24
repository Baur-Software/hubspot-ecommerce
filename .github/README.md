# GitHub Configuration

This directory contains GitHub-specific configuration for the HubSpot Ecommerce plugin.

## Contents

### Workflows (`workflows/`)

Automated CI/CD pipelines using GitHub Actions:

- **[ci.yml](workflows/ci.yml)** - Continuous integration testing on every push/PR
- **[release.yml](workflows/release.yml)** - Automated releases when version tags are pushed
- **[security-scan.yml](workflows/security-scan.yml)** - Security scanning for dependencies and secrets

See [WORKFLOW_GUIDE.md](workflows/WORKFLOW_GUIDE.md) for detailed documentation.

### Templates

- **[PULL_REQUEST_TEMPLATE.md](PULL_REQUEST_TEMPLATE.md)** - PR template with checklist and structure

## Quick Start

### Running CI Locally

Before pushing, test that CI will pass:

```bash
# Test WordPress plugin
cd wordpress
npm run test:no-wp

# Test HubSpot app
cd hubspot-app
npm test
```

### Creating a Release

```bash
# 1. Update version in wordpress/hubspot-ecommerce.php
# 2. Commit changes
git commit -am "Bump version to 1.0.0"

# 3. Create and push tag
git tag v1.0.0
git push origin v1.0.0

# The release workflow will automatically build and publish
```

### Viewing Workflow Results

- Go to the **Actions** tab in GitHub
- Click on any workflow run to see details
- Download build artifacts from successful release runs

## Branch Protection (Recommended)

Set up branch protection for `main`:

1. Go to Settings → Branches → Add rule
2. Branch name pattern: `main`
3. Enable:
   - Require pull request reviews before merging
   - Require status checks to pass before merging
   - Select the CI workflows as required checks
   - Require branches to be up to date before merging

## Security

The workflows are configured with minimal required permissions. They use:
- `GITHUB_TOKEN` - Automatically provided by GitHub, scoped to the repository
- No additional secrets required for basic functionality

## Support

For issues or questions:
- Workflow issues: Check [WORKFLOW_GUIDE.md](workflows/WORKFLOW_GUIDE.md)
- Plugin issues: See main [README.md](../README.md)
- Report bugs: [GitHub Issues](https://github.com/baursoftware/hubspot-ecommerce/issues)
