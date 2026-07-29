#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DEVICE_NAME="${IOS_SIMULATOR_NAME:-iPhone 17 Pro}"
SCREENSHOT_DIR="${IOS_SCREENSHOT_DIR:-screenshots/ios-simulator}"
SCREENSHOT_NAME="${IOS_SCREENSHOT_NAME:-01-ios-launch.png}"
DERIVED_DATA="${IOS_DERIVED_DATA:-/tmp/paspapan-ios-simulator-build}"
BUNDLE_ID="com.pandanteknik.paspapan"
USES_CUSTOM_SERVER_URL=0

if [[ -n "${CAP_SERVER_URL:-}" ]]; then
  USES_CUSTOM_SERVER_URL=1
fi

restore_default_capacitor_config() {
  if [[ "$USES_CUSTOM_SERVER_URL" == "1" ]]; then
    unset CAP_SERVER_URL
    bunx cap sync ios >/dev/null
  fi
}

trap restore_default_capacitor_config EXIT

if [[ "$(uname -s)" != "Darwin" ]]; then
  echo "iOS simulator screenshots require macOS."
  exit 1
fi

if ! command -v xcodebuild >/dev/null 2>&1; then
  echo "xcodebuild is missing. Install Xcode and select it with xcode-select."
  exit 1
fi

DEVICE_ID="$(
  xcrun simctl list devices available \
    | grep "$DEVICE_NAME" \
    | grep -E 'Shutdown|Booted' \
    | head -n 1 \
    | sed -E 's/.*\(([0-9A-F-]+)\).*/\1/'
)"

if [[ -z "$DEVICE_ID" ]]; then
  echo "Simulator device not found: $DEVICE_NAME"
  xcrun simctl list devices available
  exit 1
fi

bun run ios:assets
bunx cap sync ios

xcodebuild \
  -project ios/App/App.xcodeproj \
  -scheme App \
  -configuration Debug \
  -sdk iphonesimulator \
  -destination "id=$DEVICE_ID" \
  -derivedDataPath "$DERIVED_DATA" \
  CODE_SIGNING_ALLOWED=NO \
  build >/tmp/paspapan-ios-screenshot-xcodebuild.log

xcrun simctl boot "$DEVICE_ID" >/dev/null 2>&1 || true
xcrun simctl bootstatus "$DEVICE_ID" -b
xcrun simctl install "$DEVICE_ID" "$DERIVED_DATA/Build/Products/Debug-iphonesimulator/App.app"
xcrun simctl terminate "$DEVICE_ID" com.apple.mobilesafari >/dev/null 2>&1 || true
xcrun simctl launch "$DEVICE_ID" com.apple.springboard >/dev/null 2>&1 || true
sleep 1
xcrun simctl launch "$DEVICE_ID" "$BUNDLE_ID"

sleep "${IOS_SCREENSHOT_WAIT_SECONDS:-8}"

mkdir -p "$SCREENSHOT_DIR"
xcrun simctl io "$DEVICE_ID" screenshot "$SCREENSHOT_DIR/$SCREENSHOT_NAME"

cat > "$SCREENSHOT_DIR/manifest.json" <<JSON
{
  "device": "$DEVICE_NAME",
  "device_id": "$DEVICE_ID",
  "bundle_id": "$BUNDLE_ID",
  "screenshot": "$SCREENSHOT_NAME"
}
JSON

echo "Saved iOS simulator screenshot: $SCREENSHOT_DIR/$SCREENSHOT_NAME"
