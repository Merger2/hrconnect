<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\KnowledgeBaseStatus;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeBaseCategory;
use App\Services\Security\EmbeddingService;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Seed knowledge base with HR policies and procedures.
     *
     * Run: php artisan db:seed --class=KnowledgeBaseSeeder
     * Skip vector embedding (set null) — pg_trgm fallback will handle text search.
     */
    public function run(): void
    {
        // ─── Categories ───────────────────────────────────────────────
        $categories = [
            ['name' => 'Kepegawaian', 'slug' => 'kepegawaian', 'description' => 'Kebijakan dan prosedur kepegawaian', 'sort_order' => 1],
            ['name' => 'Absensi', 'slug' => 'absensi', 'description' => 'Aturan dan prosedur absensi', 'sort_order' => 2],
            ['name' => 'Cuti', 'slug' => 'cuti', 'description' => 'Kebijakan cuti dan izin', 'sort_order' => 3],
            ['name' => 'Payroll & Penggajian', 'slug' => 'payroll', 'description' => 'Informasi penggajian dan kompensasi', 'sort_order' => 4],
            ['name' => 'Lembur', 'slug' => 'lembur', 'description' => 'Aturan lembur dan overtime', 'sort_order' => 5],
            ['name' => 'Reimbursement', 'slug' => 'reimbursement', 'description' => 'Kebijakan reimbursement dan klaim', 'sort_order' => 6],
            ['name' => 'Kasbon', 'slug' => 'kasbon', 'description' => 'Informasi kasbon/pinjaman karyawan', 'sort_order' => 7],
            ['name' => 'Fasilitas & Aset', 'slug' => 'fasilitas', 'description' => 'Fasilitas dan aset perusahaan', 'sort_order' => 8],
            ['name' => 'Penilaian Kinerja', 'slug' => 'kinerja', 'description' => 'Informasi appraisal dan KPI', 'sort_order' => 9],
            ['name' => 'Teknis Aplikasi', 'slug' => 'teknis', 'description' => 'Panduan penggunaan aplikasi perusahaan', 'sort_order' => 10],
        ];

        foreach ($categories as $data) {
            KnowledgeBaseCategory::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $catKepegawaian = KnowledgeBaseCategory::where('slug', 'kepegawaian')->first()->id;
        $catAbsensi = KnowledgeBaseCategory::where('slug', 'absensi')->first()->id;
        $catCuti = KnowledgeBaseCategory::where('slug', 'cuti')->first()->id;
        $catPayroll = KnowledgeBaseCategory::where('slug', 'payroll')->first()->id;
        $catLembur = KnowledgeBaseCategory::where('slug', 'lembur')->first()->id;
        $catReimbursement = KnowledgeBaseCategory::where('slug', 'reimbursement')->first()->id;
        $catKasbon = KnowledgeBaseCategory::where('slug', 'kasbon')->first()->id;
        $catFasilitas = KnowledgeBaseCategory::where('slug', 'fasilitas')->first()->id;
        $catKinerja = KnowledgeBaseCategory::where('slug', 'kinerja')->first()->id;
        $catTeknis = KnowledgeBaseCategory::where('slug', 'teknis')->first()->id;

        // ─── Knowledge Base Entries ───────────────────────────────────
        $entries = [
            // ── Kepegawaian ──────────────────────────────────
            [
                'category_id' => $catKepegawaian,
                'title' => 'Jam Kerja Karyawan',
                'content' => 'Jam kerja perusahaan adalah Senin-Jumat pukul 08.00 - 17.00 WIB dengan istirahat 1 jam (12.00-13.00 WIB). Jam kerja fleksibel dapat diatur dengan persetujuan atasan langsung. Karyawan wajib mengisi absensi masuk sebelum pukul 08.00 WIB dan absensi pulang setelah pukul 17.00 WIB. Keterlambatan di atas 15 menit akan dicatat sebagai keterlambatan.',
            ],
            [
                'category_id' => $catKepegawaian,
                'title' => 'Kontrak Kerja dan Masa Percobaan',
                'content' => 'Masa percobaan (probation) berlaku selama 3 bulan untuk karyawan baru. Setelah menyelesaikan masa percobaan, karyawan akan dievaluasi oleh atasan langsung sebelum dikonfirmasi sebagai karyawan tetap. Karyawan kontrak dapat diperpanjang maksimal 1 tahun sesuai dengan ketentuan Undang-Undang Ketenagakerjaan.',
            ],
            [
                'category_id' => $catKepegawaian,
                'title' => 'Struktur Organisasi',
                'content' => 'Perusahaan memiliki struktur organisasi yang terdiri dari: Direktur Utama, Direktur, General Manager, Manager, Supervisor, Staff. Setiap divisi dipimpin oleh seorang Manager yang bertanggung jawab kepada Direktur. Karyawan dapat melihat struktur organisasi lengkap melalui menu Company Directory di aplikasi.',
            ],
            [
                'category_id' => $catKepegawaian,
                'title' => 'Pengajuan Dokumen Kepegawaian',
                'content' => 'Karyawan dapat mengajukan permintaan dokumen kepegawaian melalui menu Document Request. Dokumen yang tersedia antara lain: Surat Keterangan Kerja, Slip Gaji, Surat Rekomendasi. Admin HR akan memproses permintaan dan dokumen siap diunduh setelah status berubah menjadi Ready.',
            ],
            [
                'category_id' => $catKepegawaian,
                'title' => 'Data Pribadi Karyawan',
                'content' => 'Karyawan wajib memperbarui data pribadi secara berkala melalui menu Profile. Data yang harus dijaga akurasinya meliputi: alamat domisili, nomor telepon, status pernikahan, jumlah tanggungan (untuk perhitungan PPh 21), dan data keluarga. Perubahan status pernikahan dan kelahiran anak harus dilaporkan maksimal 30 hari.',
            ],
            [
                'category_id' => $catKepegawaian,
                'title' => 'Profil Perusahaan PT Daya Cipta Mandiri Solusi',
                'content' => 'Berikut profil perusahaan tempat Anda bekerja. Nama perusahaan: PT Daya Cipta Mandiri Solusi. Alamat kantor / lokasi kantor pusat: Jl. Pegambiran No. 292B, RT.15/RW.8, Rawamangun, Kec. Pulo Gadung, Kota Jakarta Timur, DKI Jakarta 13220. Perusahaan ini bergerak sebagai penyedia solusi teknologi informasi (software house) dengan bidang usaha utama pengembangan perangkat lunak, integrasi sistem, dan layanan teknologi informasi untuk mendukung operasional bisnis klien. Seluruh kebijakan kepegawaian yang berlaku bagi karyawan mengacu pada peraturan perusahaan dan ketentuan perundang-undangan ketenagakerjaan Indonesia.',
            ],
            // ── Absensi ──────────────────────────────────────
            [
                'category_id' => $catAbsensi,
                'title' => 'Cara Absensi (Check In/Out)',
                // 2026-08-06: face-ONLY (keputusan Fikih 2026-08-05 — PIN fallback
                // dihapus dari AttendanceService). Konten lama masih menyebut PIN.
                'content' => 'Absensi dilakukan melalui aplikasi dengan metode Face ID — verifikasi wajah (wajib, tanpa PIN fallback). Karyawan wajib melakukan check in saat datang dan check out saat pulang. Absensi menggunakan teknologi geolokasi GPS untuk memastikan karyawan berada di lokasi yang ditentukan. Check in dapat dilakukan mulai pukul 06.00 WIB. Pastikan wajah terdaftar di menu Face Enrollment sebelum absen.',
            ],
            [
                'category_id' => $catAbsensi,
                'title' => 'Absensi Work From Anywhere (WFA)',
                'content' => 'WFA (Work From Anywhere) dapat diajukan dengan persetujuan atasan. Karyawan yang sudah disetujui WFA dapat melakukan check in dari lokasi mana pun tanpa terikat geofence kantor. Fasilitas WFA tidak bersifat permanen. Pastikan koneksi internet stabil dan siap dihubungi selama jam kerja.',
            ],
            [
                'category_id' => $catAbsensi,
                'title' => 'Koreksi Absensi',
                'content' => 'Jika terjadi kesalahan pencatatan absensi, karyawan dapat mengajukan koreksi melalui menu Attendance Correction. Koreksi dapat diajukan untuk: check in terlewat, check out terlewat, shift salah, atau waktu salah. Setiap pengajuan koreksi harus disertai alasan yang jelas. Koreksi akan diverifikasi oleh atasan langsung.',
            ],
            [
                'category_id' => $catAbsensi,
                'title' => 'Sanksi Keterlambatan',
                'content' => 'Keterlambatan lebih dari 15 menit tanpa pemberitahuan akan dicatat sebagai late. Akumulasi keterlambatan dapat mempengaruhi penilaian kinerja bulanan. Karyawan yang terlambat lebih dari 1 jam dapat dianggap tidak masuk (absent) tanpa pemberitahuan resmi. Hubungi atasan langsung jika terjadi keadaan darurat yang menyebabkan keterlambatan.',
            ],
            // ── Cuti ──────────────────────────────────────────
            [
                'category_id' => $catCuti,
                'title' => 'Jenis Cuti yang Tersedia',
                'content' => 'Jenis cuti yang tersedia: 1) Cuti Tahunan — 12 hari per tahun (menggunakan kuota), 2) Cuti Sakit — tidak terbatas (tanpa kuota), 3) Cuti Khusus — pernikahan, kelahiran, dll (tanpa kuota). Cuti tahunan yang tidak digunakan dapat di-carry-over ke tahun berikutnya maksimal 6 hari. Pengajuan cuti dilakukan melalui menu Leave Request.',
            ],
            [
                'category_id' => $catCuti,
                'title' => 'Prosedur Pengajuan Cuti',
                'content' => 'Pengajuan cuti dilakukan melalui menu Leave Request dengan mengisi: jenis cuti, tanggal mulai, tanggal selesai, alasan, dan lampiran (jika diperlukan). Cuti tahunan minimal diajukan 3 hari sebelumnya. Cuti sakit dapat diajukan pada hari yang sama dengan melampirkan surat dokter. Persetujuan cuti dilakukan oleh atasan langsung melalui menu Approvals.',
            ],
            [
                'category_id' => $catCuti,
                'title' => 'Cuti Tahunan',
                'content' => 'Setiap karyawan mendapat jatah cuti tahunan 12 hari per tahun. Cuti tahunan bersifat paid leave (tetap dibayar). Karyawan dapat mengajukan cuti tahunan minimal 1 hari dan maksimal 12 hari berturut-turut. Cuti tahunan yang tidak terpakai pada akhir tahun dapat di-carry-over ke tahun berikutnya dengan maksimal 6 hari.',
            ],
            [
                'category_id' => $catCuti,
                'title' => 'Cuti Darurat dan Izin',
                'content' => 'Cuti darurat dapat diajukan untuk kondisi mendesak seperti: anggota keluarga inti sakit keras atau meninggal dunia. Cuti izin dapat diajukan untuk keperluan pribadi yang tidak terjadwal. Hubungi atasan langsung melalui telepon untuk kondisi darurat, lalu ajukan cuti secara resmi di aplikasi setelah situasi terkendali.',
            ],
            // ── Payroll ───────────────────────────────────────
            [
                'category_id' => $catPayroll,
                'title' => 'Jadwal Penggajian',
                'content' => 'Penggajian dilakukan setiap bulan pada tanggal 25. Jika tanggal 25 jatuh pada hari libur, pembayaran dilakukan pada hari kerja sebelumnya. Slip gaji dapat diunduh melalui menu My Payslips setelah status gaji berstatus Paid. Karyawan dapat mengatur password payslip untuk keamanan dokumen gaji.',
            ],
            [
                'category_id' => $catPayroll,
                'title' => 'Komponen Gaji',
                'content' => 'Komponen gaji terdiri dari: Gaji Pokok, Tunjangan Tetap (transport, makan), Tunjangan Tidak Tetap (bonus, lembur), Potongan Tetap (BPJS, PPh 21), Potongan Tidak Tetap (kasbon, pinjaman). Detail komponen gaji dapat dilihat di slip gaji bulanan. Untuk informasi lebih lanjut hubungi Finance.',
            ],
            [
                'category_id' => $catPayroll,
                'title' => 'BPJS Kesehatan dan Ketenagakerjaan',
                'content' => 'Perusahaan mendaftarkan seluruh karyawan tetap ke program BPJS Kesehatan dan BPJS Ketenagakerjaan. Iuran BPJS Kesehatan: 4% dari gaji (perusahaan 3%, karyawan 1%). Iuran BPJS Ketenagakerjaan: JKK (0.24%-1.74%), JKM (0.3%), JHT (5.7% — perusahaan 3.7%, karyawan 2%), JP (3% — perusahaan 2%, karyawan 1%).',
            ],
            [
                'category_id' => $catPayroll,
                'title' => 'PPh 21 dan Perpajakan',
                'content' => 'Perhitungan PPh 21 menggunakan tarif progresif Pasal 17 UU PPh: penghasilan hingga Rp60 juta/tahun (5%), Rp60-250 juta (15%), Rp250-500 juta (25%), di atas Rp500 juta (30%). PTKP (Penghasilan Tidak Kena Pajak) untuk wajib pajak lajang Rp54 juta/tahun, tambahan Rp4,5 juta untuk tanggungan (maksimal 3). Status PTKP: TK/0, TK/1, TK/2, TK/3, K/0, K/1, K/2, K/3.',
            ],
            // ── Lembur ────────────────────────────────────────
            [
                'category_id' => $catLembur,
                'title' => 'Aturan Lembur',
                'content' => 'Lembur adalah pekerjaan yang dilakukan di luar jam kerja normal. Lembur harus mendapat persetujuan atasan langsung melalui menu Overtime Request. Kompensasi lembur dihitung sesuai ketentuan: jam pertama lembur 1.5x upah, jam berikutnya 2x upah. Lembur maksimal 3 jam per hari dan 14 jam per minggu.',
            ],
            [
                'category_id' => $catLembur,
                'title' => 'Cara Mengajukan Lembur',
                'content' => 'Pengajuan lembur dilakukan sebelum atau pada hari yang sama melalui menu Overtime. Isi tanggal, jam mulai, jam selesai, dan alasan lembur. Lembur yang telah disetujui atasan akan diproses oleh Finance untuk perhitungan kompensasi. Lembur tanpa persetujuan tidak akan dibayarkan.',
            ],
            // ── Reimbursement ─────────────────────────────────
            [
                'category_id' => $catReimbursement,
                'title' => 'Jenis Reimbursement',
                'content' => 'Reimbursement yang dapat diklaim: biaya pengobatan (rawat jalan, obat-obatan), biaya perjalanan dinas (transportasi, akomodasi), biaya pendidikan dan pelatihan, biaya operasional lain yang disetujui atasan. Setiap klaim harus disertai bukti pembayaran (struk/kwitansi/faktur) yang sah.',
            ],
            [
                'category_id' => $catReimbursement,
                'title' => 'Prosedur Klaim Reimbursement',
                'content' => 'Pengajuan reimbursement melalui menu Reimbursement Request: pilih jenis klaim, isi jumlah, upload bukti, dan beri deskripsi. Klaim akan diverifikasi oleh atasan langsung, kemudian diproses Finance. Pembayaran reimbursement masuk ke slip gaji bulan berikutnya. Klaim maksimal 30 hari setelah tanggal pengeluaran.',
            ],
            // ── Kasbon ────────────────────────────────────────
            [
                'category_id' => $catKasbon,
                'title' => 'Ketentuan Kasbon',
                'content' => 'Kasbon (pinjaman karyawan) dapat diajukan dengan ketentuan: maksimal 50% dari gaji pokok, tenor maksimal 6 bulan, dipotong dari gaji setiap bulan. Pengajuan kasbon dilakukan melalui menu Cash Advance. Kasbon memerlukan persetujuan atasan dan Finance.',
            ],
            [
                'category_id' => $catKasbon,
                'title' => 'Cara Mengajukan Kasbon',
                'content' => 'Ajukan kasbon melalui menu Cash Advance/Kasbon: isi jumlah, alasan, dan tenor pembayaran. Kasbon akan melalui approval: atasan langsung → Finance → Payroll. Kasbon yang disetujui akan dipotong dari gaji setiap bulan sesuai tenor yang dipilih. Pastikan jumlah angsuran tidak melebihi 30% dari gaji bulanan.',
            ],
            // ── Fasilitas ─────────────────────────────────────
            [
                'category_id' => $catFasilitas,
                'title' => 'Peminjaman Aset Perusahaan',
                'content' => 'Karyawan dapat meminjam aset perusahaan (laptop, kendaraan, alat kerja) melalui menu Asset Request. Peminjaman harus disetujui atasan dan dicatat oleh HR. Pengembalian aset dilakukan dengan verifikasi OTP. Aset yang rusak atau hilang karena kelalaian akan menjadi tanggungan karyawan.',
            ],
            [
                'category_id' => $catFasilitas,
                'title' => 'Fasilitas Kantor',
                'content' => 'Fasilitas yang tersedia di kantor: ruang kerja, ruang meeting, pantry, mushola, parkir kendaraan. Pemesanan ruang meeting dilakukan melalui menu Collaboration. Setiap karyawan mendapat fasilitas standar: meja, kursi, laptop, dan akses internet. Untuk kebutuhan khusus, ajukan melalui atasan langsung.',
            ],
            // ── Kinerja ───────────────────────────────────────
            [
                'category_id' => $catKinerja,
                'title' => 'Penilaian Kinerja (Appraisal)',
                'content' => 'Penilaian kinerja dilakukan setiap 6 bulan (semester). Penilaian terdiri dari: Self Assessment (20%), Atasan (50%), dan Sistem (30% — berdasarkan absensi dan KPI). KPI ditentukan di awal periode dan dievaluasi di akhir periode. Hasil appraisal mempengaruhi kenaikan gaji, promosi, dan bonus tahunan.',
            ],
            [
                'category_id' => $catKinerja,
                'title' => 'Sistem KPI',
                'content' => 'KPI (Key Performance Indicator) ditetapkan oleh atasan dan karyawan bersama di awal periode. Setiap KPI memiliki bobot dan target yang jelas. KPI dapat bersifat kuantitatif (angka) dan kualitatif (deskripsi). Total bobot seluruh KPI harus 100%. Pencapaian KPI dievaluasi setiap bulan.',
            ],
            // ── Teknis ────────────────────────────────────────
            [
                'category_id' => $catTeknis,
                'title' => 'Cara Reset Password',
                'content' => 'Jika lupa password, klik "Forgot your password?" di halaman login. Masukkan email terdaftar, lalu klik link reset yang dikirim ke email. Password baru minimal 8 karakter dengan kombinasi huruf dan angka. Hubungi IT Support jika tidak menerima email reset dalam 5 menit.',
            ],
            [
                'category_id' => $catTeknis,
                'title' => 'Cara Registrasi Face ID',
                'content' => 'Face ID digunakan untuk verifikasi absensi yang aman. Registrasi dilakukan melalui menu Face Enrollment di aplikasi. Pastikan pencahayaan cukup, hadap ke kamera, dan ikuti instruksi gerakan kepala. Proses registrasi membutuhkan waktu sekitar 10-15 detik. Face ID hanya disimpan di database perusahaan dan tidak dibagikan ke pihak ketiga.',
            ],
            [
                'category_id' => $catTeknis,
                'title' => 'Aplikasi Mobile Karyawan',
                'content' => 'Aplikasi ini tersedia sebagai PWA (Progressive Web App) yang dapat diinstal di perangkat Android dan iOS. Buka aplikasi melalui browser Chrome/Safari, lalu pilih "Install" atau "Add to Home Screen". Fitur yang tersedia: absensi Face ID, GPS tracking, pengajuan cuti/lembur, payslip, notifikasi, dan asisten AI tanya-jawab.',
            ],
            // ── SOP (Standard Operating Procedure) PT Daya Cipta Mandiri Solusi ──
            [
                'category_id' => $catAbsensi,
                'title' => 'SOP Absensi Karyawan',
                'content' => 'Prosedur standar (SOP) absensi karyawan PT Daya Cipta Mandiri Solusi: 1) Pastikan wajah sudah terdaftar di menu Face Enrollment. 2) Buka aplikasi pada perangkat masing-masing. 3) Lakukan check in dengan verifikasi wajah (Face ID) saat tiba di lokasi kantor — absensi tanpa PIN fallback. 4) Pastikan GPS aktif karena geolokasi memverifikasi Anda berada dalam radius 50 meter dari kantor. 5) Lakukan check out dengan verifikasi wajah yang sama saat pulang. 6) Jika terjadi kendala teknis, ajukan koreksi absensi melalui menu Attendance Correction maksimal 1x24 jam.',
            ],
            [
                'category_id' => $catCuti,
                'title' => 'SOP Pengajuan Cuti',
                'content' => 'Prosedur standar pengajuan cuti di PT Daya Cipta Mandiri Solusi: 1) Buka menu Leave Request. 2) Pilih jenis cuti (tahunan, sakit, khusus, darurat, atau izin). 3) Isi tanggal mulai dan selesai, alasan, serta lampiran pendukung (jika diperlukan). 4) Cuti tahunan diajukan minimal 3 hari sebelumnya; cuti sakit dapat diajukan di hari yang sama dengan melampirkan surat dokter. 5) Kirim pengajuan — akan diverifikasi atasan langsung melalui menu Approvals. 6) Pantau status pengajuan; cuti dianggap sah setelah disetujui.',
            ],
            [
                'category_id' => $catLembur,
                'title' => 'SOP Pengajuan Lembur',
                'content' => 'Prosedur standar pengajuan lembur di PT Daya Cipta Mandiri Solusi: 1) Buka menu Overtime. 2) Isi tanggal, jam mulai, jam selesai, dan alasan lembur. 3) Ajukan sebelum atau pada hari yang sama sebelum jam kerja berakhir. 4) Lembur harus disetujui atasan langsung agar dibayarkan. 5) Setelah disetujui, Finance memproses kompensasi sesuai ketentuan (jam pertama 1.5x upah, jam berikutnya 2x upah). 6) Lembur tanpa persetujuan tidak akan dibayarkan.',
            ],
            [
                'category_id' => $catReimbursement,
                'title' => 'SOP Klaim Reimbursement',
                'content' => 'Prosedur standar klaim reimbursement di PT Daya Cipta Mandiri Solusi: 1) Buka menu Reimbursement Request. 2) Pilih jenis klaim (pengobatan, perjalanan dinas, pendidikan/pelatihan, operasional). 3) Isi jumlah dan deskripsi, lalu unggah bukti pembayaran (struk/kwitansi/faktur) yang sah. 4) Klaim diajukan maksimal 30 hari setelah tanggal pengeluaran. 5) Klaim diverifikasi atasan langsung, lalu diproses Finance. 6) Pembayaran masuk ke slip gaji bulan berikutnya.',
            ],
            [
                'category_id' => $catKasbon,
                'title' => 'SOP Pengajuan Kasbon',
                'content' => 'Prosedur standar pengajuan kasbon di PT Daya Cipta Mandiri Solusi: 1) Buka menu Cash Advance. 2) Isi jumlah yang diajukan (maksimal 50% dari gaji pokok), alasan, dan tenor pembayaran (maksimal 6 bulan). 3) Kirim pengajuan — melalui approval atasan langsung → Finance → Payroll. 4) Kasbon yang disetujui dipotong dari gaji setiap bulan sesuai tenor. 5) Pastikan total angsuran tidak melebihi 30% dari gaji bulanan.',
            ],
        ];

        foreach ($entries as $data) {
            KnowledgeBase::updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, [
                    'knowledgeable_type' => 'App\\Models\\Company',
                    'knowledgeable_id' => 1,
                    'is_indexed' => false,
                    'embedding' => null,
                    'status' => KnowledgeBaseStatus::READY,
                ])
            );
        }

        $this->command?->info('Knowledge base seeded: '.count($categories).' categories, '.count($entries).' entries.');

        // ─── Generate embeddings synchronously (no queue worker needed) ───
        $this->command?->info('Generating embeddings for KB entries...');

        $embeddingService = app(EmbeddingService::class);
        $kbEntries = KnowledgeBase::whereNull('embedding')
            ->where('status', KnowledgeBaseStatus::READY)
            ->get();

        foreach ($kbEntries as $kb) {
            try {
                $embeddingService->processKnowledgeBase($kb);
                $this->command?->line("  ✓ {$kb->title}");
            } catch (\Throwable $e) {
                $this->command?->warn("  ✗ Embedding skipped for '{$kb->title}': {$e->getMessage()}");
            }
        }

        $indexedCount = KnowledgeBase::whereNotNull('embedding')->count();
        $this->command?->info('Embeddings generated for '.$indexedCount.'/'.$kbEntries->count().' entries.');
    }
}
