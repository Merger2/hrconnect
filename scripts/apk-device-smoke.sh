#!/usr/bin/env bash
set -euo pipefail

PACKAGE_NAME="${PACKAGE_NAME:-com.pandanteknik.paspapan}"
ACTIVITY_NAME="${ACTIVITY_NAME:-com.pandanteknik.paspapan/.MainActivity}"
APK_PATH="${APK_PATH:-android/app/build/outputs/apk/release/app-release.apk}"
SCREENSHOT_PATH="${SCREENSHOT_PATH:-screenshots/apk-device-smoke.png}"
LAUNCH_WAIT_SECONDS="${LAUNCH_WAIT_SECONDS:-8}"
SMOKE_LATITUDE="${SMOKE_LATITUDE:-"-6.200000"}"
SMOKE_LONGITUDE="${SMOKE_LONGITUDE:-"106.816666"}"

if ! command -v adb >/dev/null 2>&1; then
  echo "adb is required but was not found in PATH." >&2
  exit 1
fi

device_count="$(adb devices | awk 'NR > 1 && $2 == "device" { count++ } END { print count + 0 }')"

if [ "$device_count" -lt 1 ]; then
  echo "No authorized Android device found. Run adb devices and approve USB debugging on the device." >&2
  exit 1
fi

if [ "$device_count" -gt 1 ] && [ -z "${ANDROID_SERIAL:-}" ]; then
  echo "Multiple devices found. Set ANDROID_SERIAL to choose one." >&2
  adb devices
  exit 1
fi

if [ "${SKIP_INSTALL:-0}" != "1" ]; then
  if [ ! -f "$APK_PATH" ]; then
    echo "APK not found at $APK_PATH. Build it first or set APK_PATH=/path/to/app.apk." >&2
    exit 1
  fi

  adb install -r "$APK_PATH" >/dev/null
fi

for permission in \
  android.permission.CAMERA \
  android.permission.ACCESS_FINE_LOCATION \
  android.permission.ACCESS_COARSE_LOCATION \
  android.permission.READ_EXTERNAL_STORAGE \
  android.permission.READ_MEDIA_IMAGES \
  android.permission.READ_MEDIA_VIDEO \
  android.permission.READ_MEDIA_VISUAL_USER_SELECTED \
  android.permission.POST_NOTIFICATIONS
do
  adb shell pm grant "$PACKAGE_NAME" "$permission" >/dev/null 2>&1 || true
done

permission_dump="$(adb shell dumpsys package "$PACKAGE_NAME" 2>/dev/null || true)"

for permission in \
  android.permission.CAMERA \
  android.permission.ACCESS_FINE_LOCATION \
  android.permission.ACCESS_COARSE_LOCATION
do
  if ! printf '%s\n' "$permission_dump" | grep -q "$permission: granted=true"; then
    echo "Warning: $permission is not granted. Runtime prompt may still appear during manual smoke." >&2
  fi
done

if ! adb shell pm list features | grep -q 'android.hardware.camera'; then
  echo "Warning: device does not advertise a camera feature; barcode/photo attendance smoke is limited." >&2
fi

adb shell settings put secure location_mode 3 >/dev/null 2>&1 || true
adb shell appops set "$PACKAGE_NAME" COARSE_LOCATION allow >/dev/null 2>&1 || true
adb shell appops set "$PACKAGE_NAME" FINE_LOCATION allow >/dev/null 2>&1 || true

if [ "${SET_MOCK_GPS:-1}" = "1" ]; then
  adb emu geo fix "$SMOKE_LONGITUDE" "$SMOKE_LATITUDE" >/dev/null 2>&1 || true
fi

adb logcat -c
adb shell am force-stop "$PACKAGE_NAME" >/dev/null 2>&1 || true
adb shell am start -W -n "$ACTIVITY_NAME" >/tmp/paspapan-apk-smoke-start.txt
sleep "$LAUNCH_WAIT_SECONDS"

focused_window="$(
  {
    adb shell dumpsys activity activities 2>/dev/null
    adb shell dumpsys window 2>/dev/null
    adb shell dumpsys window windows 2>/dev/null
  } | grep -E 'ResumedActivity|topResumedActivity|mCurrentFocus|mFocusedApp|mFocusedWindow' || true
)"

if ! printf '%s\n' "$focused_window" | grep -q "$PACKAGE_NAME"; then
  echo "App did not become the focused foreground app." >&2
  printf '%s\n' "$focused_window" >&2
  exit 1
fi

mkdir -p "$(dirname "$SCREENSHOT_PATH")"
adb exec-out screencap -p > "$SCREENSHOT_PATH"

if [ ! -s "$SCREENSHOT_PATH" ]; then
  echo "Screenshot was not captured." >&2
  exit 1
fi

if adb logcat -d -t 400 | grep -E 'FATAL EXCEPTION|AndroidRuntime' | grep -q "$PACKAGE_NAME"; then
  echo "Fatal Android crash detected for $PACKAGE_NAME." >&2
  adb logcat -d -t 400 | grep -E 'FATAL EXCEPTION|AndroidRuntime|com.pandanteknik.paspapan' >&2 || true
  exit 1
fi

echo "APK smoke test passed."
echo "Package: $PACKAGE_NAME"
echo "Screenshot: $SCREENSHOT_PATH"
echo "Checked: launch, camera permission, GPS permission, barcode/photo camera readiness, crash log."
