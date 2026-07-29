#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "PasPapan iOS release preflight"
echo "Node: $(node --version 2>/dev/null || echo 'missing')"
echo "Bun: $(bun --version 2>/dev/null || echo 'missing')"

bunx cap --version >/dev/null

if [[ "$(uname -s)" != "Darwin" ]]; then
  echo "iOS build requires macOS + Xcode. This host can only verify Capacitor package readiness."
  echo "Run this command on a macOS runner or release workstation before TestFlight/App Store delivery."
  exit 0
fi

if ! command -v xcodebuild >/dev/null 2>&1; then
  echo "xcodebuild is missing. Install Xcode and run xcode-select before iOS release."
  exit 1
fi

if [[ ! -d ios ]]; then
  echo "iOS platform directory is not generated yet."
  echo "Run: bunx cap add ios"
  echo "Then commit the reviewed ios/ project if PasPapan starts shipping iOS builds."
  exit 0
fi

bun run ios:assets
bunx cap sync ios
xcodebuild -list -project ios/App/App.xcodeproj >/dev/null
xcodebuild \
  -project ios/App/App.xcodeproj \
  -scheme App \
  -configuration Debug \
  -sdk iphonesimulator \
  -destination 'generic/platform=iOS Simulator' \
  CODE_SIGNING_ALLOWED=NO \
  build

echo "iOS preflight completed."
