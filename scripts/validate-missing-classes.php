<?php

/**
 * Validasi Kelas PHP yang Benar-Benar Missing
 *
 * Cara pakai:
 *   php scripts/validate-missing-classes.php
 *   php scripts/validate-missing-classes.php --json
 *   php scripts/validate-missing-classes.php --fix
 *
 * Script ini:
 * 1. Scan rekursif semua file .php di app/
 * 2. Ekstrak semua `use App\...` statements
 * 3. Cek apakah file kelas tersebut benar-benar ada di disk
 * 4. Report hanya yang beneran missing
 */
$options = getopt('', ['json', 'fix']);
$useJson = isset($options['json']);
$fixMode = isset($options['fix']);

$appPath = __DIR__.'/../app';
$vendorPath = __DIR__.'/../vendor';

// ─── Step 1: Build class index dari files yang ada ───────────────────────

echo "🔍 Scanning existing classes in app/...\n";

$existingClasses = [];
$directory = new RecursiveDirectoryIterator($appPath);
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($regex as $filePath => $_) {
    $relativePath = str_replace($appPath.'/', '', $filePath);
    $className = 'App\\'.str_replace('/', '\\', preg_replace('/\.php$/', '', $relativePath));
    $existingClasses[$className] = $filePath;
}

echo '   Found '.count($existingClasses)." existing classes.\n";

// ─── Step 2: Scan semua use statements ──────────────────────────────────

echo "🔍 Scanning use App\\... statements in all PHP files...\n";

$referencedClasses = [];
$fileImports = []; // file => [class1, class2, ...]

$phpFiles = new RegexIterator(
    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath)),
    '/^.+\.php$/i',
    RecursiveRegexIterator::GET_MATCH
);

foreach ($phpFiles as $filePath => $_) {
    $content = file_get_contents($filePath);
    $relativeFile = str_replace($appPath.'/', '', $filePath);

    preg_match_all('/^use\s+(App\\\\[^;]+);\s*$/m', $content, $matches);

    foreach ($matches[1] as $fqcn) {
        // Skip traits/contracts/etc yang mungkin di-use tapi bukan class konkret
        $referencedClasses[$fqcn] = ($referencedClasses[$fqcn] ?? 0) + 1;
        $fileImports[$relativeFile][] = $fqcn;
    }
}

echo '   Found '.count($referencedClasses)." unique referenced classes.\n";

// ─── Step 3: Cek mana yang benar-benar missing ──────────────────────────

$missingClasses = [];
$falsePositives = []; // classes yang ternyata ada

foreach ($referencedClasses as $fqcn => $count) {
    $classPath = $appPath.'/'.str_replace('\\', '/', str_replace('App\\', '', $fqcn)).'.php';

    if (! file_exists($classPath)) {
        $missingClasses[$fqcn] = [
            'count' => $count,
            'expected_path' => $classPath,
            'used_by' => [],
        ];
    }
}

// Isi used_by
foreach ($fileImports as $file => $imports) {
    foreach ($imports as $fqcn) {
        if (isset($missingClasses[$fqcn])) {
            $missingClasses[$fqcn]['used_by'][] = $file;
        }
    }
}

// ─── Step 4: Output ─────────────────────────────────────────────────────

if ($useJson) {
    echo json_encode([
        'total_existing' => count($existingClasses),
        'total_referenced' => count($referencedClasses),
        'total_missing' => count($missingClasses),
        'missing' => $missingClasses,
    ], JSON_PRETTY_PRINT)."\n";
    exit(0);
}

// ─── Regular output ─────────────────────────────────────────────────────

echo "\n";
echo str_repeat('═', 72)."\n";
echo "📋 LAPORAN VALIDASI KELAS MISSING\n";
echo str_repeat('═', 72)."\n";
echo 'Total kelas existing : '.count($existingClasses)."\n";
echo 'Total referensi unik : '.count($referencedClasses)."\n";
echo 'Total benar-benar missing : '.count($missingClasses)."\n";
echo str_repeat('─', 72)."\n";

if (empty($missingClasses)) {
    echo "\n✅ TIDAK ADA kelas missing! Semua referensi valid.\n";
    exit(0);
}

// Kelompokkan berdasarkan prefix namespace
$grouped = [];
foreach ($missingClasses as $fqcn => $info) {
    $parts = explode('\\', $fqcn);
    $prefix = $parts[0].'\\'.(isset($parts[1]) ? $parts[1] : '');
    $grouped[$prefix][] = $fqcn;
}

ksort($grouped);

$totalMissing = count($missingClasses);
$fixableCount = 0;

foreach ($grouped as $prefix => $classes) {
    echo "\n🔴 ".$prefix.'\\ ('.count($classes)." missing)\n";
    echo str_repeat('─', 72)."\n";

    foreach ($classes as $fqcn) {
        $info = $missingClasses[$fqcn];
        $shortName = str_replace($prefix.'\\', '', $fqcn);
        $path = $info['expected_path'];
        $usedBy = array_slice($info['used_by'], 0, 3); // Top 3 aja
        $usedByStr = ! empty($usedBy) ? implode(', ', $usedBy) : '(not directly imported)';

        echo "  ❌ {$shortName}\n";
        echo "     Path: {$path}\n";
        echo "     Dipakai oleh: {$usedByStr}\n";
        echo "\n";
    }
}

echo str_repeat('═', 72)."\n";
echo "Total kelas benar-benar missing: {$totalMissing}\n";
echo str_repeat('═', 72)."\n";

// ─── Saran fix ──────────────────────────────────────────────────────────

if ($fixMode && ! empty($missingClasses)) {
    echo "\n🔧 Mode --fix aktif. Membuat file stub untuk kelas missing...\n";

    foreach ($missingClasses as $fqcn => $info) {
        $path = $info['expected_path'];
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $parts = explode('\\', $fqcn);
        $className = end($parts);
        $namespace = implode('\\', array_slice($parts, 0, -1));

        $stub = "<?php\n\nnamespace {$namespace};\n\nclass {$className}\n{\n    // TODO: Implementasi kelas ini\n}\n";

        file_put_contents($path, $stub);
        echo "   ✅ Created: {$path}\n";
        $fixableCount++;
    }

    echo "\n✅ {$fixableCount} file stub telah dibuat.\n";
}

echo "\n💡 Tips:\n";
echo "   - Jalankan dengan --json untuk output machine-readable\n";
echo "   - Jalankan dengan --fix untuk membuat file stub otomatis\n";
echo "   - Hapus file categorized_missing_classes.txt yang sudah tidak akurat\n";
