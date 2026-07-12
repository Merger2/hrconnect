#!/usr/bin/env bash
# GitHub Automation Setup for HRConnect
# Installs gh CLI and configures GitHub credentials for PR automation

set -e

echo "🔧 GitHub Automation Setup for HRConnect"
echo "=========================================="

# Install gh CLI
echo "✓ Installing gh CLI..."
if ! command -v gh &> /dev/null; then
  # Ubuntu/Debian
  curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.sources > /dev/null
  sudo apt update
  sudo apt install -y gh
fi

echo "✅ gh CLI installed: $(gh --version)"

# Verify GitHub auth
echo "✓ Checking GitHub authentication..."

if gh auth status 2>/dev/null; then
  echo "✅ GitHub authenticated"
else
  echo "⚠️ Not authenticated. Running: gh auth login"
  gh auth login --scopes repo,workflow
fi

# Verify repo access
echo "✓ Verifying repository access..."
REPO=$(git remote get-url origin | sed 's/.*github.com[:/]\(.*\)\.git/\1/')
if gh repo view "$REPO" > /dev/null 2>&1; then
  echo "✅ Repository access verified: $REPO"
else
  echo "❌ Cannot access repository: $REPO"
  exit 1
fi

echo ""
echo "✅ GitHub Setup Complete!"
echo ""
echo "Next: Update cron automation for PR workflow"
echo "Run: bash /home/merger/hrconnect/.hermes/update-automation-pr.sh"
