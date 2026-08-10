<?php

use App\Models\Wilayah;
use Database\Seeders\WilayahSeeder;

// RefreshDatabase sudah global untuk semua Feature test (tests/Pest.php).

test('wilayah seeder mengisi 4 level dengan format kode titik', function () {
    $this->seed(WilayahSeeder::class);

    expect(Wilayah::count())->toBeGreaterThan(90000);

    // Level dideteksi via LENGTH(kode): 2 = provinsi, 5 = kabupaten/kota,
    // 8 = kecamatan, 13 = desa/kelurahan.
    expect(Wilayah::whereRaw('LENGTH(kode) = 2')->count())->toBe(38)
        ->and(Wilayah::whereRaw('LENGTH(kode) = 5')->count())->toBe(514)
        ->and(Wilayah::whereRaw('LENGTH(kode) = 8')->count())->toBeGreaterThan(7000)
        ->and(Wilayah::whereRaw('LENGTH(kode) = 13')->count())->toBeGreaterThan(80000);

    // Spot check cascade DKI Jakarta: 31 → 31.71 (Jakarta Pusat) →
    // 31.71.01 (Gambir) → 31.71.01.1002 (Cideng).
    expect(Wilayah::where('kode', '31')->value('nama'))->toBe('DAERAH KHUSUS IBUKOTA JAKARTA')
        ->and(Wilayah::where('kode', '31.71')->value('nama'))->toBe('KOTA ADMINISTRASI JAKARTA PUSAT')
        ->and(Wilayah::where('kode', '31.71.01')->value('nama'))->toBe('GAMBIR')
        ->and(Wilayah::where('kode', '31.71.01.1002')->value('nama'))->toBe('CIDENG');
});

test('wilayah seeder idempoten — run kedua tidak menambah baris', function () {
    $this->seed(WilayahSeeder::class);

    $before = Wilayah::count();

    $this->seed(WilayahSeeder::class);

    expect(Wilayah::count())->toBe($before);
});
