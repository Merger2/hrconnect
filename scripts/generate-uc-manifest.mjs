// Generate MANIFEST.md failure-aware untuk screenshots/use-cases/
// Membaca .status/*.json yang ditulis tests/e2e/use-case-screenshots.spec.ts.
// Exit code 1 kalau ada entri FAILED — dipakai CI/manual gate.
import * as fs from 'fs';
import * as path from 'path';

const DIR = 'screenshots/use-cases';
const STATUS_DIR = path.join(DIR, '.status');

const LABELS = {
  UC01: 'Halaman Login',
  UC02: 'Dashboard Employee',
  UC03: 'Halaman Presensi (GPS/geofencing + face recognition)',
  UC04: 'Halaman Face Enrollment',
  UC05: 'Halaman Riwayat Presensi',
  UC06: 'Halaman Koreksi Presensi',
  UC07: 'Halaman Pengajuan Cuti',
  UC08: 'Halaman Pengajuan Lembur',
  UC09: 'Halaman Pengajuan Reimbursement',
  UC10: 'Halaman Pengajuan WFH/WFA',
  UC11: 'Halaman Jadwal Kerja',
  UC12: 'Halaman Payslip',
  UC13: 'Halaman Chat Knowledge Base (RAG)',
  UC14: 'Halaman Data Karyawan Tim (Manager, scope tim langsung)',
  UC15: 'Halaman Informasi Kehadiran & Pengajuan Tim (tab Team Attendance)',
  UC16: 'Halaman Approval Manager (cuti/lembur/reimbursement/WFH — mewakili approval L1)',
  UC17: 'Daftar & Proses Payroll (komponen gaji/potongan — mewakili PPh21 TER, BPJS, generate payslip)',
  UC18: 'Halaman Konfigurasi Pajak/BPJS',
  UC19: 'Approval Reimbursement tahap Finance/L2',
  UC20: 'Daftar Data Karyawan (Admin)',
  UC21: 'Form Tambah/Edit Karyawan + pembuatan akun',
  UC22: 'Kelola Master Data (divisi/jabatan/shift)',
  UC23: 'Kelola Presensi',
  UC24: 'Impor/Ekspor Data',
  UC25: 'Approval Cuti tahap Admin/L2',
  UC26: 'Approval Lembur tahap Admin/L2',
  UC27: 'Kelola Jenis Cuti dan Kuota',
  UC28: 'Kelola Hari Libur',
  UC29: 'Kelola Knowledge Base',
  UC30: 'Generate Laporan (Report Center)',
  UC31: 'Kelola HR Checklist',
  UC32: 'Kelola User, Role, dan Permission (Super Admin)',
  UC33: 'Konfigurasi Sistem (Super Admin)',
  UC34: 'Monitoring Activity Log (Super Admin)',
  UC35: 'Kelola Master Data Sistem/Perusahaan (Super Admin)',
};

const ROLE_OF = (uc) => {
  const n = parseInt(uc.slice(2), 10);
  if (n === 1) return 'Public';
  if (n <= 13) return 'Employee (ESS)';
  if (n <= 16) return 'Manager';
  if (n <= 19) return 'Finance';
  if (n <= 31) return 'Admin';
  return 'Super Admin';
};

const files = fs.readdirSync(DIR).filter((f) => f.endsWith('.png')).sort();

const rows = [];
const failures = [];
const noStatus = [];

for (const f of files) {
  const uc = f.slice(0, 4);
  const base = f.replace(/\.png$/, '');
  const mode = f.includes('-mobile') ? 'mobile' : 'desktop';
  const size = (fs.statSync(path.join(DIR, f)).size / 1024).toFixed(0);

  let st = null;
  const statusFile = path.join(STATUS_DIR, `${base}.json`);
  if (fs.existsSync(statusFile)) {
    try {
      st = JSON.parse(fs.readFileSync(statusFile, 'utf-8'));
    } catch {
      st = null;
    }
  }

  let mark;
  if (st === null) {
    mark = '⚠️ NO-STATUS';
    noStatus.push(base);
  } else if (st.ok === true) {
    mark = '✅ OK';
  } else {
    mark = '❌ FAILED';
    failures.push({ base, error: st.error || 'unknown' });
  }

  rows.push(`| ${rows.length + 1} | ${uc} | ${LABELS[uc] || ''} | ${ROLE_OF(uc)} | ${mode} | \`${f}\` | ${size} KB | ${mark} |`);
}

const now = new Date().toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'medium' });
const okCount = rows.length - failures.length - noStatus.length;

const failureSection = failures.length
  ? `\n## ❌ Kegagalan (${failures.length}) — screenshot PNG-nya adalah artifact diagnosis, bukan bukti OK\n\n${failures.map((f) => `- **${f.base}**: ${f.error}`).join('\n')}\n`
  : '';

const noStatusSection = noStatus.length
  ? `\n## ⚠️ Tanpa status (${noStatus.length}) — PNG dari run lama / test di-skip\n\n${noStatus.map((f) => `- ${f}`).join('\n')}\n`
  : '';

const verdict =
  failures.length === 0 && noStatus.length === 0
    ? '**SEMUA OK** — aman dipakai sebagai bukti QA/audit.'
    : `**BELUM AMAN** — ${failures.length} gagal, ${noStatus.length} tanpa status. Perbaiki, re-run test terkait, lalu regenerate manifest.`;

const content = `# HRConnect — Screenshot 35 Use Case Inti (Release 1)

Generated: ${now}
Total: ${files.length} file PNG — ✅ ${okCount} OK, ❌ ${failures.length} gagal, ⚠️ ${noStatus.length} tanpa status
Verdict: ${verdict}

Sumber: \`tests/e2e/use-case-screenshots.spec.ts\` → project Playwright \`chromium-usecase-shots\`.
Semua halaman diverifikasi sebelum dianggap sukses: HTTP <400, bukan redirect login, bukan error page, body visible.
Regenerate: \`node scripts/generate-uc-manifest.mjs\` (setelah re-run test).
Use case proses otomatis tidak dipotret terpisah (terlihat di layar lain): validasi GPS/face → UC03;
PPh21 TER/BPJS/generate payslip → UC17 & UC12; approval L1 cuti/lembur/reimb/WFH → UC16.
${failureSection}${noStatusSection}
| # | UC | Use Case | Role | Mode | File | Ukuran | Status |
|---|----|----------|------|------|------|--------|--------|
${rows.join('\n')}
`;

fs.writeFileSync(path.join(DIR, 'MANIFEST.md'), content);
console.log(`MANIFEST.md written: ${files.length} entries — ${okCount} OK, ${failures.length} FAILED, ${noStatus.length} NO-STATUS`);
if (failures.length > 0) process.exit(1);
