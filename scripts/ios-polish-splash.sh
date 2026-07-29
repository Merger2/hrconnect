#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SOURCE="resources/splash.png"
ASSET_DIR="ios/App/App/Assets.xcassets/Splash.imageset"

if [[ ! -f "$SOURCE" ]]; then
  echo "Missing iOS splash source: $SOURCE"
  exit 1
fi

if [[ ! -d "$ASSET_DIR" ]]; then
  echo "Missing iOS splash asset directory: $ASSET_DIR"
  exit 1
fi

if ! command -v magick >/dev/null 2>&1; then
  echo "ImageMagick is required to polish iOS splash padding."
  echo "Install it with: brew install imagemagick"
  exit 1
fi

for file in \
  Default@1x~universal~anyany.png \
  Default@2x~universal~anyany.png \
  Default@3x~universal~anyany.png \
  Default@1x~universal~anyany-dark.png \
  Default@2x~universal~anyany-dark.png \
  Default@3x~universal~anyany-dark.png
do
  magick \
    -size 2732x2732 xc:none \
    \( "$SOURCE" -resize 2240x2240 \) \
    -gravity center \
    -compose over \
    -composite \
    -define png:color-type=6 \
    "$ASSET_DIR/$file"
done

echo "iOS splash assets polished with centered transparent padding."
