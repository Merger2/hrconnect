<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed tabel `wilayah` (flat: kode + nama) dengan data administrasi Indonesia
 * lengkap provinsi → kabupaten/kota → kecamatan → desa/kelurahan.
 *
 * Sumber data: paket composer `laravolt/indonesia` (sudah dependency di
 * composer.json) — CSV resmi di vendor/laravolt/indonesia/resources/csv.
 * Format kode Laravolt tanpa titik ('1101012001') diubah ke format aplikasi
 * dengan titik ('11.01.01.2001') supaya level bisa dideteksi via LENGTH(kode):
 *   2 = provinsi, 5 = kabupaten/kota, 8 = kecamatan, 13 = desa/kelurahan.
 *
 * Idempoten: skip bila tabel sudah terisi (jangan hancurkan referensi).
 * Ukuran: ±91 ribu baris (38 provinsi, ~514 kota, ~7.200 kecamatan, ~83 ribu
 * desa/kelurahan) — seeder ini butuh beberapa detik.
 */
class WilayahSeeder extends Seeder
{
    /** Lokasi CSV paket laravolt/indonesia relatif terhadap base path. */
    private const CSV_DIR = 'vendor/laravolt/indonesia/resources/csv';

    /** Kolom cache WilayahController (prefix key 'wilayah:...'). */
    private const CACHE_KEY_PATTERN = '%wilayah:%';

    public function run(): void
    {
        $existing = Wilayah::count();

        if ($existing > 0) {
            $this->command?->info("wilayah sudah terisi ({$existing} baris) — skip.");

            return;
        }

        $base = base_path(self::CSV_DIR);
        $rows = [];

        // Provinsi: kode '11' → '11'
        foreach ($this->csvRows("{$base}/provinces.csv") as $row) {
            $rows[] = ['kode' => trim((string) $row[0]), 'nama' => trim((string) $row[1])];
        }

        // Kabupaten/Kota: kode '1101' → '11.01'
        foreach ($this->csvRows("{$base}/cities.csv") as $row) {
            $rows[] = ['kode' => $this->dottedKode(trim((string) $row[0])), 'nama' => trim((string) $row[2])];
        }

        // Kecamatan: kode '110101' → '11.01.01'
        foreach ($this->csvRows("{$base}/districts.csv") as $row) {
            $rows[] = ['kode' => $this->dottedKode(trim((string) $row[0])), 'nama' => trim((string) $row[2])];
        }

        // Desa/Kelurahan (satu file per provinsi): '1101012001' → '11.01.01.2001'
        foreach (glob("{$base}/villages/*.csv") ?: [] as $file) {
            foreach ($this->csvRows($file) as $row) {
                $rows[] = ['kode' => $this->dottedKode(trim((string) $row[0])), 'nama' => trim((string) $row[2])];
            }
        }

        // Satu transaksi untuk semua chunk: kalau gagal di tengah (mis. duplikat
        // kode, koneksi putus), semua di-rollback — guard idempotency di atas
        // tidak akan mengunci data parsial selamanya di run berikutnya.
        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 1000) as $chunk) {
                Wilayah::insert($chunk);
            }
        });

        // Cache API wilayah (TTL 1 hari) mungkin sudah menyimpan daftar kosong.
        $this->flushWilayahCache();

        $this->command?->info('wilayah di-seed: '.count($rows).' baris (provinsi '.Wilayah::whereRaw('LENGTH(kode) = 2')->count()
            .', kota '.Wilayah::whereRaw('LENGTH(kode) = 5')->count()
            .', kecamatan '.Wilayah::whereRaw('LENGTH(kode) = 8')->count()
            .', desa/kelurahan '.Wilayah::whereRaw('LENGTH(kode) = 13')->count().').');
    }

    /**
     * Ubah kode digit tanpa titik ('1101012001') ke format titik
     * ('11.01.01.2001') — 2-2-2-4.
     */
    private function dottedKode(string $digits): string
    {
        $out = substr($digits, 0, 2);

        if (strlen($digits) >= 4) {
            $out .= '.'.substr($digits, 2, 2);
        }

        if (strlen($digits) >= 6) {
            $out .= '.'.substr($digits, 4, 2);
        }

        if (strlen($digits) > 6) {
            $out .= '.'.substr($digits, 6);
        }

        return $out;
    }

    /**
     * Baca CSV (headerless) sebagai array baris.
     *
     * @return list<list<string>>
     */
    private function csvRows(string $path): array
    {
        if (! is_readable($path)) {
            $this->command?->warn("CSV tidak ditemukan: {$path}");

            return [];
        }

        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        while (($line = fgetcsv($handle, 4096)) !== false) {
            // Strip UTF-8 BOM (\xEF\xBB\xBF) kalau file pertama memilikinya.
            if ($line && isset($line[0])) {
                $line[0] = ltrim($line[0], "\xEF\xBB\xBF");
            }

            $rows[] = $line;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Hapus cache API wilayah (WilayahController::remember memakai prefix
     * 'wilayah:...' dengan TTL 1 hari) supaya daftar kosong yang ter-cache
     * tidak bertahan setelah seeder jalan. Driver database → LIKE query.
     */
    private function flushWilayahCache(): void
    {
        $store = config('cache.default');
        $table = config("cache.stores.{$store}.table");

        if ($table) {
            DB::table($table)->where('key', 'like', self::CACHE_KEY_PATTERN)->delete();
        }
    }
}
