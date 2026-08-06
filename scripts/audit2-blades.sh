#!/bin/bash
cd /home/merger/hrconnect
V="resources/views"

echo "############################################################"
echo "A. @include/@extends TIDAK ADA (dot->slash benar)"
echo "############################################################"
grep -rhoE "@(include|extends|includeIf|includeWhen)\([^)]*" "$V" --include='*.blade.php' \
  | grep -oE "'[a-zA-Z0-9._-]+'|\\\"[a-zA-Z0-9._-]+\\\"" | tr -d "'\"" | sort -u | while read -r name; do
  path=$(echo "$name" | tr '.' '/')
  if [ ! -f "$V/$path.blade.php" ]; then
    echo "  REAL MISSING: $name (-> $path.blade.php)"
  fi
done

echo ""
echo "############################################################"
echo "B. validate-missing-classes: file notif yang dimaksud ada?"
echo "############################################################"
ls -1 app/Notifications/PayrollPaid.php app/Notifications/PayrollSubmitted.php app/Notifications/PayrollPublished.php 2>&1
echo "--- listener refs ---"
grep -rn "PayrollPaid\b\|PayrollSubmitted\b\|PayrollPublished\b" app/Listeners/ 2>/dev/null | head -6

echo ""
echo "############################################################"
echo "C. Komponen orphan (namespaced-aware)"
echo "############################################################"
find "$V/components" -name '*.blade.php' | while read -r f; do
  base=$(basename "$f" .blade.php)
  dir=$(basename "$(dirname "$f")")
  used=$(grep -rlE "x-${dir}\.${base}\b|x-${base}\b|@include\([^)]*${base}" "$V" --include='*.blade.php' | grep -v "$f" | wc -l)
  if [ "$used" -eq 0 ]; then
    echo "  ORPHAN?: ${dir}/${base}"
  fi
done

echo ""
echo "############################################################"
echo "D. check-blade-js-syntax pada SEMUA blade (user + admin)"
echo "############################################################"
FILES=$(find "$V" -name '*.blade.php' -not -path '*/vendor/*' -not -path '*/emails/*' | tr '\n' ' ')
php scripts/check-blade-js-syntax.php $FILES 2>&1 | tail -12

echo ""
echo "############################################################"
echo "E. duplikat id (pages + livewire/user)"
echo "############################################################"
grep -rhoE 'id="[a-zA-Z0-9_-]+"' "$V/pages" "$V/livewire/user" --include='*.blade.php' 2>/dev/null | sort | uniq -d | head -8
