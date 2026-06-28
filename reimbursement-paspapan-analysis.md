# Reimbursement Logic Analysis — PasPapan → HRConnect Port

Hasil studi lengkap business logic reimbursement dari PasPapan (`/home/merger/PasPapan/`) vs existing HRConnect.

---

## Ringkasan Gap

| Area | PasPapan | HRConnect | Priority |
|------|----------|-----------|----------|
| Approval Matrix | Configurable rules (by amount/division/role) | Hardcoded 2-level (L1=Manager, L2=Finance) | **P1** |
| Notifications | 3 notification classes (DB + Email) | **None** | **P1** |
| Accounting Posting | Auto-create journal entry on approval | None | **P2** |
| File Security | MIME validation, double-extension guard, path traversal prevention | Simple `mimes:jpg,jpeg,png,pdf` | **P2** |
| Re-approval Flow | No (same as HRConnect) | No | — |
| Attachment Storage | Private `local` disk | Public `public` disk | **P2** |
| Approval Owner Tracking | `approved_by`, `head_approved_by`, `finance_approved_by` | Only through polymorphic Approval rows | **P3** |
| Multi-company | `MultiCompanyService` gate | Column exists but no enforcement | **P3** |

---

## Key Patterns to Port

### 1. Lock + EnsureReviewable (TOCTOU Fix)

PasPapan `ReimbursementApprovalService.php` pattern — sudah mirip dengan HRConnect, tapi HRConnect ApprovalService lock-nya terpisah. Perbaikan: pastikan `ReimbursementController@store` juga wrap di `DB::transaction`.

**Status**: ✅ Already done in HRConnect (ApprovalService uses `lockForUpdate`)

### 2. Approval Matrix Rules

PasPapan punya tabel `approval_matrix_rules` dengan kolom:
- `workflow` (string) — misal 'reimbursement', 'leave', 'overtime'
- `conditions` (json) — `{ "min_amount": 100000, "division_id": 5 }`
- `steps` (json) — `[{ "key": "direct_manager", "label": "Atasan Langsung", "approver_type": "direct_manager" }, { "key": "finance_head", "label": "Kepala Finance", "approver_type": "finance_head" }]`
- `priority` — untuk menentukan rule mana yang dipakai jika multiple match

`ApprovalMatrixService::matchingRule()` mencocokkan conditions terhadap data reimbursement (amount, division, role). Step terakhir otomatis dianggap final approval.

**Rekomendasi**: Implementasi ApprovalMatrixRule di HRConnect jika nanti butuh multi-level approval yang dinamis. Saat ini hardcoded 2-level sudah cukup, tapi struktur tabel bisa ditambahkan untuk future-proof.

### 3. Notifications (Highest Priority Gap)

PasPapan punya 3 notification classes:

| Class | Channel | Recipient | Trigger |
|-------|---------|-----------|---------|
| `ReimbursementRequested` | Database | Approvers | On create |
| `ReimbursementRequestedEmail` | Email | Approvers | On create |
| `ReimbursementStatusUpdated` | Database + Email | Employee | On approve/reject |

HRConnect: ✅ model `Reimbursement` exists but **zero** notification classes.

**Rekomendasi**: Implementasi notifications setelah flow approval stabil.

### 4. File Upload Security

PasPapan `SecureUploadPolicy`:
- Validasi extension (jpg/jpeg/png/pdf untuk 'document' category)
- Validasi MIME type (image/jpeg, image/png, application/pdf)
- Double extension detection (file.php.pdf → reject)
- Max 10MB untuk dokumen
- `AttachmentPathValidator` — path traversal guard

HRConnect: only `mimes:jpg,jpeg,png,pdf` + `max:5120`.

**Rekomendasi**: Port `SecureUploadPolicy` pattern. Simpan attachment di `local` disk (private), bukan `public`.

### 5. Accounting Posting

PasPapan `AccountingWorkspaceService::postReimbursement()`:
- Debit akun expense (5200 — Employee Reimbursements)
- Credit akun kas
- Reference number format: `RMB-{id}`
- Guard: hanya post sekali (`accounting_journal_entry_id` check)

**Rekomendasi**: P2 — tidak urgent, hanya relevan jika terintegrasi dengan modul akuntansi.

### 6. Cleanup on Failure

PasPapan: tidak ada explicit cleanup di service layer.
HRConnect `ReimbursementController@store`: ✅ Sudah ada cleanup — jika `Reimbursement::create()` gagal, file receipt dihapus dari disk.

### 7. Disk Storage: Public vs Private

PasPapan: simpan di `local` (private) disk → hanya bisa diakses via `FileAccessService` yang audit-log setiap akses.
HRConnect: simpan di `public` disk → direct URL access.

**Rekomendasi**: P2 — pindahkan ke `local` disk dan serve via controller dengan audit log.

---

## Prioritas Implementasi

### P1 — Critical Gaps
1. **ApprovalService**: Pastikan `resolveL2Approver()` untuk Reimbursement sudah benar (Finance, bukan HR Manager) — ✅ sudah
2. **ReimbursementService**: Implementasi `updateReimbursement()` method — saat ini controller tidak punya PATCH/PUT endpoint
3. **API endpoint categories**: Buat `GET /api/v1/reimbursement/categories` — saat ini hanya inline di Blade
4. **Notifications**: Database notification ke approvers saat submit, email ke employee saat approve/reject

### P2 — Security & UX
5. **File Upload**: Port `SecureUploadPolicy` pattern, pindahkan storage dari `public` ke `local`
6. **Single column**: Gabung `attachment_path` + `receipt_file` jadi satu kolom (`attachment_path`)
7. **Re-approval flow**: Jika rejected, buat mekanisme resubmit (clone + edit)

### P3 — Enterprise Features
8. **Approval Matrix Rule**: Tabel + service untuk configurable approval steps
9. **Multi-company gate**: Pastikan reimbursement antar perusahaan terisolasi
10. **Accounting integration**: Journal entry posting

---

## Arsitektur File (Rekomendasi)

```
app/
├── Services/
│   └── ReimbursementService.php        # ✅ Existing — tambah update + resubmit
├── Http/Controllers/Api/
│   └── ReimbursementController.php     # ✅ Existing — tambah update + categories
├── Policies/
│   └── ReimbursementPolicy.php         # ✅ Existing
├── Models/
│   ├── Reimbursement.php               # ✅ Existing
│   └── ReimbursementCategory.php        # ✅ Existing
├── Notifications/
│   ├── ReimbursementRequested.php      # ❌ NEW — database ke approver
│   ├── ReimbursementRequestedMail.php  # ❌ NEW — email ke approver
│   └── ReimbursementStatusUpdated.php  # ❌ NEW — ke employee
├── Support/ (new directory)
│   ├── SecureUploadPolicy.php          # ❌ Port dari PasPapan
│   └── FileAccessService.php           # ❌ Port dari PasPapan
├── Http/Resources/
│   └── ReimbursementResource.php       # ✅ Existing
└── Http/Requests/Api/
    ├── StoreReimbursementRequest.php   # ✅ Existing — update validasi file
    └── UpdateReimbursementRequest.php  # ❌ NEW
```
