#!/bin/bash
cd /home/merger/hrconnect
V="resources/views"
echo "############################################################"
echo "A. SCRIPT AUDIT BAWAAN PROJECT"
echo "############################################################"
for s in check-blade-js-syntax validate-missing-classes check-ui-rules check-modern-stack check-enterprise-boundary; do
  echo ""
  echo "======== $s ========"
  php "scripts/$s.php" 2>&1 | tail -20
done

echo ""
echo "############################################################"
echo "B. @include / @extends yang file-nya TIDAK ADA"
echo "############################################################"
grep -rhoE '@(include|extends|includeIf|includeWhen)\s*\([^)]*' "$V" --include='*.blade.php' \
  | grep -oE "'[a-z0-9._-]+'" | tr -d "'" | sort -u | while read -r name; do
  # cari file dengan nama tsb (partial naming, abai namespace/extension)
  hit=$(find "$V" -name "$name.blade.php" | wc -l)
  if [ "$hit" -eq 0 ]; then
    echo "  MISSING VIEW ref: $name"
  fi
done | head -20

echo ""
echo "############################################################"
echo "C. Livewire components: class tidak ada / view tidak ada"
echo "############################################################"
# <livewire:x.y /> -> app/Livewire/X/Y.php
grep -rhoE '<livewire:[a-z0-9._-]+' "$V" --include='*.blade.php' | sed 's/<livewire://' | sort -u | while read -r comp; do
  cls=$(echo "$comp" | sed 's/\./\//g' | sed 's/\([a-z0-9]\)-\([a-z]\)/\1\U\2/g')
  # simple heuristic: convert dot path to class
  phpclass="app/Livewire/$(echo "$comp" | awk -F. '{out=""; for(i=1;i<=NF;i++){out=out toupper(substr($i,1,1)) tolower(substr($i,2)) "/"} print out}' | sed 's|/$||').php"
  # cek class file
  if [ ! -f "$phpclass" ]; then
    echo "  LIVE class not found: $comp (coba: $phpclass)"
  fi
done | head -25

echo ""
echo "############################################################"
echo "D. Komponen x-* yang TIDAK ter-resolve (namespaced-aware)"
echo "############################################################"
# x-user.foo -> components/user/foo.blade.php ; x-foo -> components/foo.blade.php
grep -rhoE 'x-[a-z][a-z0-9.-]*' "$V" --include='*.blade.php' | sort -u | while read -r ref; do
  n="${ref#x-}"
  # skip Alpine directives & dynamic
  case "$n" in
    data|show|if|for|cloak|init|effect|model|text|html|bind|on|transition|teleport|ignore|ref) continue ;;
    *) ;;
  esac
  if echo "$n" | grep -q '\.'; then
    ns="${n%%.*}"; base="${n##*.}"
    if [ ! -f "$V/components/$ns/$base.blade.php" ]; then
      echo "  MISSING: $ref (components/$ns/$base.blade.php)"
    fi
  else
    if [ ! -f "$V/components/$n.blade.php" ] && [ ! -d "$V/components/$n" ]; then
      # heroicon / app logo vendor?
      echo "  MISSING?: $ref"
    fi
  fi
done | grep -vE 'heroicon|app-logo|app-application|branding' | head -20

echo ""
echo "############################################################"
echo "E. Komponen di components/ yang TIDAK pernah dipakai"
echo "############################################################"
find "$V/components" -name '*.blade.php' | while read -r f; do
  base=$(basename "$f" .blade.php)
  dir=$(basename "$(dirname "$f")")
  used=0
  if [ "$dir" = "components" ]; then
    used=$(grep -rl "x-$base\b" "$V" --include='*.blade.php' | grep -v "$f" | wc -l)
  else
    used=$(grep -rlE "x-$dir\.$base\b|<livewire:$dir\.$base\b|$dir\.$base" "$V" --include='*.blade.php' | grep -v "$f" | wc -l)
  fi
  if [ "$used" -eq 0 ]; then
    echo "  maybe-orphan: $dir/$base.blade.php"
  fi
done | head -25

echo ""
echo "############################################################"
echo "F. Pitfall Alpine/Livewire di blade"
echo "############################################################"
echo "-- pola salah \$watch('\$wire (harus this.\$wire.\$watch) --"
grep -rn "watch('\$wire\|watch(\$wire" "$V" --include='*.blade.php' | head -8
echo "-- this.\$refs untuk reaktivitas (canSend pattern) --"
grep -rn "this\.\$refs\.\w*\.value" "$V" --include='*.blade.php' | head -8

echo ""
echo "############################################################"
echo "G. Aksesibilitas sampel: <img tanpa alt, <button tanpa type"
echo "############################################################"
echo "-- img tanpa alt: $(grep -rE '<img[^>]*>' "$V" --include='*.blade.php' | grep -v 'alt=' | wc -l) file-line"
grep -rnE '<img[^>]*>' "$V" --include='*.blade.php' | grep -v 'alt=' | head -6
echo "-- button tanpa type: $(grep -rE '<button[^>]*>' "$V" --include='*.blade.php' | grep -v 'type=' | wc -l) file-line"
