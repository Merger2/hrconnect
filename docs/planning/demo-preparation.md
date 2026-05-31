# HRConnect - Demo Preparation Guide

> **Dokumen ini berisi panduan persiapan demo/presentasi skripsi HRConnect.**
> Pastikan semua item di checklist sudah siap SEBELUM hari presentasi.

---

## 1. MOCK MODE SETUP

### Tujuan
Menghindari ketergantungan pada koneksi internet saat presentasi. Semua API external (Gemini Embedding, Gemini 2.5 Flash) akan di-mock.

### Konfigurasi
```env
# .env.demo (copy ke .env sebelum presentasi)
APP_ENV=production
APP_DEBUG=false

# RAG Mock Mode - WAJIB true untuk demo
RAG_MOCK_MODE=true

# Cache semua response untuk demo
CACHE_DRIVER=file
CACHE_TTL=86400

# Queue tetap jalan tapi pakai database
QUEUE_CONNECTION=database
```

### Mock Responses yang Disiapkan

**RAG KnowledgeBase Chat:**
```
Q: "Berapa hari cuti tahunan karyawan baru?"
A: "Berdasarkan kebijakan perusahaan, karyawan baru mendapatkan 12 hari cuti tahunan per tahun. Cuti ini dapat digunakan setelah masa probation (3 bulan) selesai.\n\nSumber: HR Policy v2.1, Section 4.2"

Q: "Bagaimana cara mengajukan cuti?"
A: "Untuk mengajukan cuti:\n1. Buka menu 'Cuti' di aplikasi\n2. Klik 'Ajukan Cuti Baru'\n3. Pilih tipe cuti, tanggal, dan isi alasan\n4. Upload bukti jika diperlukan (sakit/maternity)\n5. Klik 'Ajukan'\n\nPengajuan akan diproses oleh atasan langsung Anda.\n\nSumber: Panduan Karyawan, Section 3.1"

Q: "Apa saja tunjangan yang saya dapatkan?"
A: "Karyawan tetap mendapatkan:\n- Tunjangan jabatan (sesuai grade)\n- Tunjangan makan Rp 50.000/hari\n- BPJS Kesehatan (perusahaan 4%, karyawan 1%)\n- BPJS Ketenagakerjaan (perusahaan 5.24%, karyawan 2%)\n\nSumber: Kompensasi & Benefit, Section 2.3"
```

**Implementasi Mock Service:**
```php
// app/Services/KnowledgeBaseService.php
public function query(string $question): string
{
    if (config('hrconnect.rag_mock_mode')) {
        return $this->getMockResponse($question);
    }

    return $this->callGeminiAPI($question);
}

private function getMockResponse(string $question): string
{
    $responses = [
        'cuti' => 'Berdasarkan kebijakan perusahaan, karyawan baru mendapatkan 12 hari cuti tahunan...',
        'ajukan' => 'Untuk mengajukan cuti, buka menu Cuti di aplikasi...',
        'tunjangan' => 'Karyawan tetap mendapatkan tunjangan jabatan, makan, BPJS...',
        'gaji' => 'Gaji dibayarkan setiap akhir bulan melalui transfer bank...',
        'lembur' => 'Lembur harus mendapat persetujuan atasan terlebih dahulu...',
    ];

    foreach ($responses as $keyword => $response) {
        if (str_contains(strtolower($question), $keyword)) {
            return $response;
        }
    }

    return 'Berikut informasi yang ditemukan dari knowledge base perusahaan. ' .
           'Untuk pertanyaan lebih lanjut, silakan hubungi tim HRD.';
}
```

---

## 2. DATABASE WARM-UP

### Tujuan
Neon PostgreSQL serverless memiliki "cold start" (2-3 detik delay) jika tidak ada aktivitas beberapa saat. Warm-up mencegah lag saat demo.

### Warm-up Script
```bash
#!/bin/bash
# scripts/warmup.sh

# Hit health endpoint setiap 5 menit
while true; do
    curl -s https://hrconnect.company.com/health > /dev/null
    echo "Warm-up ping at $(date)"
    sleep 300
done
```

### Cron Job untuk Warm-up
```bash
# Tambahkan ke crontab server
*/5 * * * * curl -s https://hrconnect.company.com/health > /dev/null 2>&1
```

### Pre-demo Checklist
```bash
# 30 menit sebelum demo:
curl https://hrconnect.company.com/health
curl https://hrconnect.company.com/login
curl https://hrconnect.company.com/dashboard

# Verifikasi database responsive:
time psql -h ep-xxx.neon.tech -U hrconnect -d hrconnect -c "SELECT 1"
# Target: < 500ms
```

---

## 3. DEMO FLOW (20-21 Menit)

### Segment 1: Employee Mobile PWA (4 menit)
```
1. Buka aplikasi di HP (atau Chrome DevTools mobile mode)
2. Login sebagai John (Staff IT)
3. Clock In → tunjukkan face capture + GPS validation ✅
4. Buka riwayat absensi → tunjukkan summary ✅
5. Buka slip gaji → tunjukkan breakdown ✅
```

### Segment 2: Face Recognition (3 menit)
```
1. Tunjukkan face capture saat clock in
2. Jelaskan: face-api.js, 128D embedding, threshold 85%
3. Tunjukkan fallback: trigger kamera gagal → PIN verification
4. Tunjukkan database: face_embedding column (vector 128)
5. Tunjukkan: "Jika kamera bermasalah, karyawan tetap bisa absen via PIN"
```

### Segment 3: GPS Geofencing (2 menit)
```
1. Tunjukkan GPS validation saat clock in
2. Jelaskan: Haversine formula, radius 100m per cabang
3. Tunjukkan database: lat_in, long_in, is_mocked_gps, gps_accuracy
4. Jelaskan anti-fake GPS: "Sistem detect GPS palsu dari developer options"
```

### Segment 4: Leave + Approval Workflow (3 menit)
```
1. Login sebagai Jane (Staff HR) → ajukan cuti 2 hari
2. Tunjukkan: form, quota check otomatis, tanggal picker
3. Tunjukkan status: "Menunggu Persetujuan"
4. Login sebagai Bob (Manager HR) → approve leave Jane
5. Tunjukkan: approval timeline, notifikasi ke Jane
6. Login sebagai HRD → tunjukkan approval sudah lengkap
7. Tunjukkan leave calendar dengan warna berbeda per tipe
```

### Segment 5: Overtime + Approval (2 menit)
```
1. Login sebagai John → ajukan lembur 3 jam (deploy sistem)
2. Tunjukkan: form lembur, auto-calculate perkiraan upah
3. Login sebagai Alice (IT Manager) → approve lembur
4. Tunjukkan: lembur masuk ke payroll sebagai penghasilan tambahan
```

### Segment 6: Reimbursement (2 menit) ← TAMBAHAN BARU
```
1. Login sebagai John → ajukan reimbursement transport
2. Tunjukkan: form, upload bukti foto Struk taxi
3. Tunjukkan status: "Menunggu Persetujuan"
4. Login sebagai Alice (Manager) → approve reimbursement
5. Tunjukkan: status berubah "Disetujui", akan masuk ke payroll berikutnya
```

### Segment 7: AI KnowledgeBase RAG (2 menit)
```
1. Buka halaman Knowledge Base chat
2. Tanya: "Berapa hari cuti tahunan?"
3. Tunjukkan response AI dengan source citation ✅
4. Jelaskan: PDF → chunks → embeddings (text-embedding-004) → Gemini 2.5 Flash
5. Tunjukkan database: knowledge_bases.embedding (vector 768)
```

### Segment 8: HRD Dashboard (2 menit)
```
1. Login sebagai HRD
2. Tunjukkan dashboard: total karyawan, hadir hari ini, pending approvals ✅
3. Tunjukkan employee management (search, filter, detail) ✅
4. Tunjukkan leave calendar view ✅
```

### Segment 9: Finance Dashboard (2 menit)
```
1. Login sebagai Finance
2. Tunjukkan payroll generation untuk periode Mei 2026 ✅
3. Tunjukkan breakdown: gaji pokok, tunjangan, BPJS, PPh21, net salary ✅
4. Tunjukkan lock mechanism: "Setelah lock, tidak bisa edit — adjustment only" ✅
5. Jelaskan: "Jika ada kesalahan, adjustment dilakukan bulan berikutnya"
```

---

## 4. DEMO DATA YANG HARUS ADA

### User Accounts untuk Demo (dengan Relasi)
```
Super Admin (tanpa parent — top level):
  Email: admin@demo.com | Password: Demo@1234
  Name: Super Admin | Role: super-admin | parent_id: null

HRD Manager (L3 approval):
  Email: hrd@demo.com | Password: Demo@1234
  Name: Siti HRD | Role: hr-manager | parent_id: admin_id
  Department: HR | Position: HR Manager

Finance Manager:
  Email: finance@demo.com | Password: Demo@1234
  Name: Dedi Finance | Role: finance | parent_id: admin_id
  Department: Finance | Position: Finance Manager

Alice - IT Manager (L2 approval untuk tim IT):
  Email: alice@demo.com | Password: Demo@1234
  Name: Alice Manager | Role: supervisor | parent_id: hrd_id
  Department: IT | Position: IT Manager
  → BISA approve lembur & reimbursement tim IT

Bob - HR Manager (L2 approval untuk tim HR):
  Email: bob@demo.com | Password: Demo@1234
  Name: Bob Manager | Role: supervisor | parent_id: hrd_id
  Department: HR | Position: HR Supervisor
  → BISA approve cuti tim HR

John - Staff IT (untuk demo clock in, lembur, reimbursement):
  Email: john@demo.com | Password: Demo@1234
  Name: John Doe | Role: employee | parent_id: alice_id
  Department: IT | Position: Staff IT
  → Face registered, sudah clock in beberapa kali

Jane - Staff HR (untuk demo leave request):
  Email: jane@demo.com | Password: Demo@1234
  Name: Jane Smith | Role: employee | parent_id: bob_id
  Department: HR | Position: Staff HR
  → Sudah punya leave quota, pernah ajukan cuti

Rina - Staff Finance (tanpa leave pending — clean demo):
  Email: rina@demo.com | Password: Demo@1234
  Name: Rina Finance | Role: employee | parent_id: finance_id
  Department: Finance | Position: Staff Finance

Mark - Intern (untuk demo intern exempt):
  Email: mark@demo.com | Password: Demo@1234
  Name: Mark Intern | Role: employee | parent_id: alice_id
  Department: IT | Position: Intern
  Employment Type: INTERN → exempt BPJS, PPh21, no leave quota
```

### Relasi Approval Chain (PENTING untuk demo)
```
John (Staff IT)
  → L1: Alice (IT Manager)
  → L2: Siti (HRD Manager)

Jane (Staff HR)
  → L1: Bob (HR Supervisor)
  → L2: Siti (HRD Manager)

Rina (Staff Finance)
  → L1: Dedi (Finance Manager)
  → L2: Siti (HRD Manager)

Mark (Intern)
  → L1: Alice (IT Manager)
  → L2: Siti (HRD Manager)
  → Note: tidak punya leave quota
```

### Data yang Harus Pre-populated
```
Company: 1 company dengan logo (PT. HRConnect Indonesia)
Branches: 2 cabang (Jakarta Pusat, Bandung)
Departments: 4 departemen (IT, HR, Finance, Operations)
Positions: 6 posisi dengan grade (Intern, Staff, Supervisor, Manager, Head, Director)
Employees: 8 karyawan dengan relasi parent_id (lihat tabel di atas)
Shifts: 3 shift (Pagi 08-17, Siang 14-23, Malam 23-08)
Leave Types: 5 tipe (Tahunan, Sakit, Menstruasi, Penting, Maternity)
Holidays: 10 hari libur nasional 2026
Attendances: 20+ record (John: 15 record, Jane: 10 record, lainnya: 5 record)
  → Include: 1 face_bypassed, 1 low_accuracy, 1 late
Leaves: 3 leave requests
  → Jane: 1 approved (Tahunan, 2 hari, bulan lalu)
  → Jane: 1 pending (Sakit, 1 hari, hari ini — untuk demo approve)
  → Rina: 1 rejected (Penting, alasan quota habis)
Overtimes: 2 records
  → John: 1 approved (3 jam, deploy sistem — untuk demo)
  → John: 1 pending (4 jam, maintenance server)
Reimbursements: 2 records
  → John: 1 approved (Transport, Rp 150K — untuk demo)
  → John: 1 pending (Makan klien, Rp 250K)
Payrolls: 1 payroll published (periode April 2026, semua karyawan)
  → Include breakdown: gaji pokok, tunjangan, BPJS, PPh21
  → Mark (Intern): tanpa BPJS & PPh21 (untuk demo exempt)
KnowledgeBase: 3 artikel (HR Policy v2.1 PDF, IT Guide PDF, Company Rules PDF)
  → Sudah indexed, embeddings generated
```

---

## 5. BACKUP PLAN

### Skenario 1: Internet Mati Total
```
1. TIDAK BISA demo live tanpa koneksi (PWA offline mode DITUNDA)
2. Tunjukkan screenshot/wireframe yang sudah disiapkan
3. Jelaskan arsitektur via ERD diagram & deployment diagram
4. Tunjukkan code structure & dokumentasi
5. Katakan: "Fitur ini akan diaktifkan penuh setelah PWA offline mode selesai"
```

### Skenario 2: Database Down
```
1. Tunjukkan screenshot halaman yang sudah di-capture sebelumnya
2. Jelaskan arsitektur dan tunjukkan code
3. Show ERD diagram dari dokumentasi
4. Tunjukkan warm-up script & cron job yang sudah disiapkan
```

### Skenario 3: Bug Saat Demo
```
1. Jangan panik
2. Refresh page (Ctrl+R)
3. Jika masih error → switch ke account lain
4. Jelaskan: "Sedang maintenance, saya tunjukkan fitur lain"
5. Fallback: tunjukkan dokumentasi/wireframe
```

### Skenario 4: Face Recognition Gagal di Demo
```
1. Ini sudah diantisipasi dengan fallback PIN
2. Tunjukkan: "Kamera bermasalah, menggunakan PIN"
3. Input PIN → clock in berhasil
4. Jelaskan: "Ini adalah fallback mechanism yang kami rancang"
```

### Skenario 5: RAG AI Tidak Response
```
1. Mock mode HARUS aktif (RAG_MOCK_MODE=true)
2. Response akan instant dari local cache
3. Jika mock juga gagal → tunjukkan text search fallback
```

---

## 6. PRE-DEMO CHECKLIST (H-1)

### Technical
- [ ] `RAG_MOCK_MODE=true` di .env
- [ ] Database warm-up script berjalan (30+ menit sebelum demo)
- [ ] Semua 8 user account sudah dibuat dengan relasi parent_id
- [ ] Demo data sudah di-seed (leaves, overtimes, reimbursements, payrolls)
- [ ] Assets sudah di-build (`npm run build`)
- [ ] Queue worker berjalan
- [ ] SSL certificate valid
- [ ] Health check endpoint accessible
- [ ] Backup database terbaru

### Content
- [ ] 3 KnowledgeBase articles sudah di-upload & indexed
- [ ] 1 payroll published April 2026 (termasuk Mark intern tanpa BPJS)
- [ ] 1 leave request pending Jane (untuk demo approve oleh Bob)
- [ ] 1 overtime pending John (untuk demo approve oleh Alice)
- [ ] 1 reimbursement pending John (untuk demo approve)
- [ ] 20+ attendance records (termasuk face_bypassed, low_accuracy, late)
- [ ] Company logo sudah di-upload
- [ ] John sudah face registered (face_embedding terisi)

### Presentation
- [ ] Demo flow 9 segment sudah di-rehearse minimal 2x
- [ ] Timing: 20-21 menit (termasuk buffer)
- [ ] Backup plan sudah dipahami
- [ ] Screenshot/wireframe backup sudah siap (untuk skenario offline)
- [ ] ERD diagram sudah siap ditunjukkan
- [ ] Deployment diagram sudah siap ditunjukkan
- [ ] Jawaban untuk pertanyaan "Bagaimana Jika..." sudah dihafal

---

## 7. POST-DEMO

### Jika Ditanya "Bagaimana Jika...?"
```
Q: "Bagaimana jika internet mati saat clock in?"
A: "Clock in memerlukan koneksi server. Fallback-nya employee bisa request manual ke supervisor. PWA offline mode akan ditambahkan di versi berikutnya."

Q: "Bagaimana jika face-api.js tidak support di browser tertentu?"
A: "Fallback ke PIN verification. Kami mendukung semua browser modern (Chrome, Safari, Firefox, Edge). Untuk browser lama, employee bisa pakai PIN."

Q: "Bagaimana keamanan face embedding?"
A: "Embedding disimpan sebagai vector 128D di database, bukan foto mentah. Tidak bisa di-reverse engineering jadi foto. Akses hanya via authenticated API."

Q: "Berapa biaya operasional per bulan?"
A: "VPS ~Rp 150rb, Neon DB free tier, Gemini API free tier (cukup untuk 150 karyawan), Total ~Rp 150rb/bulan untuk 150 karyawan."

Q: "Apakah bisa scaling ke 1000+ karyawan?"
A: "Ya. Arsitektur menggunakan queue untuk heavy processing (payroll, embeddings). Database Neon serverless auto-scale. Tinggal upgrade VPS."

Q: "Kenapa adjustment payroll di bulan berikutnya, bukan rollback?"
A: "Ini sesuai standar industri. Tidak ada perusahaan yang rollback payroll yang sudah publish. Adjustment di bulan berikutnya adalah cara yang benar dan aman."

Q: "Bagaimana jika GPS akurat tapi karyawan di luar kantor?"
A: "Sistem Haversine menghitung jarak dari koordinat kantor. Jika di luar radius (default 100m), clock in ditolak. Setiap cabang bisa punya radius berbeda."

Q: "Apakah karyawan bisa memalsukan GPS?"
A: "Sistem mendeteksi GPS mocked dari browser API. Jika terdeteksi, clock in ditolak. 3x dalam seminggu → akun di-flag untuk review HRD."
```

---

*Dokumen ini harus diikuti saat persiapan demo/presentasi skripsi.*
*Terakhir diupdate: 2026-05-31*
