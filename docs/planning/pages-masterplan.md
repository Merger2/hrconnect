# HRConnect Pages Masterplan (Realistis)

**Referensi:** PasPapan 80 models → 151 halaman fungsional (55 pages + 96 Livewire) + 58 komponen = 251 Blade files
**HRConnect:** 33 models → ~56 halaman + 43 partial/component/modal = **~99 Blade files target**

---

## Ringkasan

| Modul | Halaman Standalone | Modal/Partial | Total Blade | ✅ Sekarang | ❌ Baru | Day |
|-------|:------------------:|:-------------:|:-----------:|:-----------:|:------:|:---:|
| Auth | 8 | — | 8 | 7 | 1 (lock) | ✅ |
| Dashboard | 2 | — | 2 | 0 | 2 | 6 |
| Attendance | 6 | 2 | 8 | 3 | 5 | 3 |
| Leave | 4 | 2 | 6 | 2 | 4 | 4 |
| Overtime | 2 | 2 | 4 | 2 | 2 | 4 |
| Reimbursement | 2 | 2 | 4 | 2 | 2 | 4 |
| Payroll | 8 | 3 | 11 | 2 | 9 | 5 |
| Approvals | 1 | 1 | 2 | 2 | 0 | ✅ |
| Employee | 3 | 2 | 5 | 0 | 5 | 2 |
| Organization | 4 | 2 | 6 | 0 | 6 | 3 |
| Asset | 2 | 1 | 3 | 0 | 3 | 4 |
| Loan | 2 | 1 | 3 | 0 | 3 | 5 |
| Reports | 6 | — | 6 | 0 | 6 | 6 |
| Knowledge Base | 2 | — | 2 | 2 | 0 | ✅ |
| Notifications | 2 | 1 | 3 | 0 | 3 | 6 |
| Settings | 2 | 3 | 5 | 3 | 2 | ✅ |
| Email templates | — | 8 | 8 | 0 | 8 | 7 |
| Shared components | — | 12 | 12 | 3 | 9 | 1 |
| **Total** | **56** | **43** | **99** | **28** | **71** | **7 hari** |

---

## Detail Per Modul

### 1. AUTH (8 Blade — 7✅ 1❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Login | `/login` | page | ✅ |
| 2 | Register | `/register` | page | ✅ |
| 3 | Forgot Password | `/forgot-password` | page | ✅ |
| 4 | Reset Password | `/reset-password/{token}` | page | ✅ |
| 5 | Verify Email | `/verify-email` | page | ✅ |
| 6 | 2FA Challenge | `/two-factor-challenge` | page | ✅ |
| 7 | Confirm Password | `/confirm-password` | page | ✅ |
| 8 | Lock Screen | `/lock` | page | ❌ Day 6 |

### 2. DASHBOARD (2 Blade — 2❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | ESS Dashboard | `/dashboard` | page | ❌ Day 6 |
| 2 | Admin Dashboard | `/admin/dashboard` | page | ❌ Day 6 |

### 3. EMPLOYEE (5 Blade — 5❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Employee list (grid+table toggle) | `/admin/employees` | page | ❌ Day 2 |
| 2 | Employee detail + tab (profile/bank/family/documents) | `/admin/employees/{id}` | page | ❌ Day 2 |
| 3 | Employee create/edit | — | modal | ❌ Day 2 |
| 4 | Terminate employee | — | confirm modal | ❌ Day 2 |
| 5 | Bulk import/export | — | modal | ❌ Day 2 |

**Catatan:** Employee tidak perlu page create/edit terpisah. Form ±15 field muat di modal. Detail page pakai tabs. Total Blade: 3 pages + 2 modals = **5**.

### 4. ORGANIZATION (6 Blade — 6❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Department list | `/admin/departments` | page | ❌ Day 3 |
| 2 | Department create/edit | — | modal | ❌ Day 3 |
| 3 | Position list | `/admin/positions` | page | ❌ Day 3 |
| 4 | Position create/edit | — | modal | ❌ Day 3 |
| 5 | Company settings | `/admin/company` | page | ❌ Day 3 |
| 6 | Org chart | `/admin/org-chart` | page | ❌ Day 3 |

### 5. ATTENDANCE (8 Blade — 3✅ 5❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | ESS Attendance index | `/attendance` | page | ✅ |
| 2 | Clock-in (face + GPS) | `/attendance/clock-in` | page | ✅ |
| 3 | Face registration | `/attendance/face-registration` | page | ✅ |
| 4 | Attendance history/calendar | `/attendance/history` | page | ❌ Day 3 |
| 5 | Admin attendance matrix | `/admin/attendance` | page | ❌ Day 3 |
| 6 | Shift CRUD | `/admin/shifts` | page + modal | ❌ Day 3 |
| 7 | Holiday CRUD | `/admin/holidays` | page + modal | ❌ Day 3 |
| 8 | Holiday calendar | `/admin/holidays/calendar` | page | ❌ Day 3 |

### 6. LEAVE (6 Blade — 2✅ 4❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | ESS Leave index | `/leaves` | page | ✅ |
| 2 | Leave apply | — | modal | ✅ (form modal) |
| 3 | Admin leave view | `/admin/leaves` | page | ❌ Day 4 |
| 4 | Leave types CRUD | `/admin/leave-types` | page + modal | ❌ Day 4 |
| 5 | Leave balance CRUD | `/admin/leave-balances` | page + modal | ❌ Day 4 |
| 6 | Leave calendar (team) | `/admin/leaves/calendar` | page | ❌ Day 4 |

### 7. OVERTIME (4 Blade — 2✅ 2❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | ESS Overtime index | `/overtimes` | page | ✅ |
| 2 | Overtime apply | — | modal | ✅ (form modal) |
| 3 | Admin overtime view | `/admin/overtimes` | page | ❌ Day 4 |
| 4 | Overtime rates config | `/admin/overtime-rates` | page + modal | ❌ Day 4 |

### 8. REIMBURSEMENT (4 Blade — 2✅ 2❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | ESS Reimbursement index | `/reimbursements` | page | ✅ |
| 2 | Reimbursement apply | — | modal | ✅ (form modal with upload) |
| 3 | Admin reimbursement view | `/admin/reimbursements` | page | ❌ Day 4 |
| 4 | Categories CRUD | `/admin/reimbursement-categories` | page + modal | ❌ Day 4 |

### 9. PAYROLL (12 Blade — 2✅ 9❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | ESS Payroll index | `/payroll` | page | ✅ |
| 2 | Payslip detail | `/payroll/{id}` | page | ✅ |
| 3 | Payslip PDF | `/payroll/{id}/pdf` | PDF | ❌ Day 5 |
| 4 | Payroll generation wizard | `/admin/payroll/generate` | page | ❌ Day 5 |
| 5 | Payroll approval L1 (HR) | `/admin/payroll/approve` | page + modal | ❌ Day 5 |
| 6 | Payroll approval L2 (Finance) | — | page + modal | ❌ Day 5 |
| 7 | Payroll detail (admin) | `/admin/payroll/{id}` | page | ❌ Day 5 |
| 8 | Batch payslip PDF | `/admin/payroll/{id}/pdf-batch` | PDF | ❌ Day 5 |
| 9 | Allowances CRUD | `/admin/payroll/allowances` | page + modal | ❌ Day 5 |
| 10 | Deductions CRUD | `/admin/payroll/deductions` | page + modal | ❌ Day 5 |
| 11 | PTKP/TER config | `/admin/payroll/tax` | page | ❌ Day 5 |
| 12 | BPJS config | `/admin/payroll/bpjs` | page | ❌ Day 5 |

### 10. LOAN (3 Blade — 3❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | ESS Loan index | `/loans` | page | ❌ Day 5 |
| 2 | Loan apply | — | modal | ❌ Day 5 |
| 3 | Admin loan view | `/admin/loans` | page | ❌ Day 5 |

### 11. ASSET (3 Blade — 3❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Asset list (grid+table) | `/assets` | page | ❌ Day 4 |
| 2 | Asset create/edit | — | modal | ❌ Day 4 |
| 3 | Asset detail | `/assets/{id}` | page | ❌ Day 4 |

### 12. APPROVALS (2 Blade — 2✅)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Approvals index (Pending+History) | `/approvals` | page | ✅ |
| 2 | Approval detail modal | — | modal | ✅ |
| 3 | Notification bell (layout) | — | layout partial | ❌ Day 6 |

### 13. KNOWLEDGE BASE (2 Blade — 2✅)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Chat AI | `/knowledge-base` | page | ✅ |
| 2 | Manage documents | `/knowledge-base/manage` | page | ✅ |

### 14. REPORTS (6 Blade — 6❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Attendance recap | `/admin/reports/attendance` | page | ❌ Day 6 |
| 2 | Payroll financial | `/admin/reports/payroll` | page | ❌ Day 6 |
| 3 | PPh21 report | `/admin/reports/pph21` | page | ❌ Day 6 |
| 4 | BPJS report | `/admin/reports/bpjs` | page | ❌ Day 6 |
| 5 | Performance report | `/admin/reports/performance` | page | ❌ Day 6 |
| 6 | Custom export | `/admin/reports/export` | modal | ❌ Day 6 |

### 15. NOTIFICATIONS (3 Blade — 3❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Notification center | `/notifications` | page | ❌ Day 6 |
| 2 | Notification dropdown | layout header | partial | ❌ Day 6 |
| 3 | Notification preferences | `/notifications/preferences` | page | ❌ Day 6 |

### 16. SETTINGS (5 Blade — 3✅ 2❌)

| # | Halaman | Route | Tipe | Status |
|---|---------|-------|------|:------:|
| 1 | Profile edit | `/settings/profile` | page | ✅ |
| 2 | Security (password + 2FA) | `/settings/security` | page | ✅ |
| 3 | Appearance | `/settings/appearance` | page | ✅ |
| 4 | Roles & Permissions | `/admin/settings/roles` | page + modal | ❌ (post-skripsi) |
| 5 | Audit log | `/admin/settings/audit` | page | ❌ (post-skripsi) |

### 17. EMAIL TEMPLATES (8 Blade — 8❌ Day 7)

| # | Template | Status |
|---|----------|:------:|
| 1 | Leave approved | ❌ |
| 2 | Leave rejected | ❌ |
| 3 | Leave cancelled | ❌ |
| 4 | Overtime approved | ❌ |
| 5 | Overtime rejected | ❌ |
| 6 | Reimbursement approved | ❌ |
| 7 | Reimbursement rejected | ❌ |
| 8 | Account created | ❌ |

### 18. SHARED COMPONENTS (12 Blade — 3✅ 9❌)

| # | Komponen | Status | Day |
|---|----------|:------:|:---:|
| 1 | `x-layouts::app.sidebar` | ✅ | — |
| 2 | `x-status-badge` | ✅ | — |
| 3 | `x-button` (partial) | ✅ | — |
| 4 | `x-page-shell` | ❌ | 1 |
| 5 | `x-page-toolbar` | ❌ | 1 |
| 6 | `x-simple-table` | ❌ | 1 |
| 7 | `x-card-grid` | ❌ | 1 |
| 8 | `x-form-modal` | ❌ | 1 |
| 9 | `x-confirm-modal` | ❌ | 1 |
| 10 | `x-filter-bar` | ❌ | 1 |
| 11 | `x-loading-skeleton` | ❌ | 1 |
| 12 | `x-pagination` | ❌ | 1 |
| 13 | `x-empty-state` | ❌ | 1 |
| 14 | `x-notification-dropdown` | ❌ | 6 |
| 15 | `x-payroll-pin-modal` | ❌ | 5 |

---

## Perbandingan dengan PasPapan

| Metrik | PasPapan | HRConnect |
|--------|:--------:|:---------:|
| Models | 80 | 33 |
| Total Blade files | 251 | 99 |
| Page views | 55 | 56 |
| Livewire views | 96 | 2 |
| Components | 58 | 15 |
| Layouts | 2 | 2 |
| Auth | 7 | 8 |
| Vendor | 16 | — |
| Errors | 16 | — |
| Halaman fungsional | 151 | ~58 |
| Blade per model | 3.1× | 3.0× |

HRConnect lebih sedikit dari PasPapan karena:
1. **33 models vs 80** — proporsional 99 vs 251 Blade files ≈ 2.5× lebih kecil
2. **Modal pattern** — create/edit bukan halaman terpisah
3. **Tidak ada vendor/error views**
4. **Livewire minimal** — interaktivitas via Alpine, bukan Livewire components
