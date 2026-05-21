# 📚 HRConnect Documentation Index

> Panduan lengkap semua dokumentasi project HRConnect.

---

## 🗂️ Struktur Dokumen

```
docs/
├── PRD.md                              ← Product Requirements Document
├── 📐 architecture/                    ← Desain arsitektur
├── 📋 planning/                        ← Rencana & jadwal
├── 🔌 api/                             ← Kontrak API
├── 🎨 ui/                              ← Desain UI
├── 🔒 security/                        ← Keamanan & performa
├── 🧪 testing/                         ← Testing strategy
├── 🚀 deployment/                      ← Deployment guide
└── 📖 reference/                       ← Referensi lengkap
```

---

## 📄 Daftar Dokumen

### Core
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| **PRD** | `PRD.md` | Product Requirements Document v3.1 — 35 koreksi konsolidasi (K1-K5 + M1-M8 + S1-S18 + N1-N12 + Glossary) di atas v3.0 |
| **PRD Errata** | `PRD-errata.md` | READ-ONLY changelog — 9 ERR + 19 CAT items (v2.0), semua sudah di-merge ke PRD v3.0 |
| **Task Spec** | `planning/task.md` | Executable specification v3.4 — 49 errata, 9-day execution schedule |
| **README** | `../README.md` | Project overview, installation guide, tech stack |

### 📐 Architecture (8 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| ERD (DBML) | `architecture/erd.dbml` | Entity Relationship Diagram — 48 tabel database (source of truth) |
| ~~ERD (MD)~~ | ~~`architecture/erd.md`~~ | ~~Dihapus — digantikan erd.dbml~~ |
| Class Diagram | `architecture/class-diagram.md` | UML class diagram — models, services, relationships |
| Sequence Diagrams | `architecture/sequence-diagrams.md` | Flow interaksi antar komponen |
| Activity Diagrams | `architecture/activity-diagrams.md` | Activity flow per modul |
| Data Flow Diagram | `architecture/data-flow-diagram.md` | Alur data dalam sistem |
| Deployment Diagram | `architecture/deployment-diagram.md` | Infrastruktur & deployment |
| Use Case Diagram | `architecture/use-case-diagram.md` | Use case per role |
| Folder Structure | `architecture/folder-structure.md` | Struktur direktori project |

### 📋 Planning (4 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Execution Schedule | `planning/execution-schedule.md` | Jadwal 12 minggu development |
| Sprint & Branch Strategy | `planning/sprint-branch-strategy.md` | 35 sprint + branch naming |
| Notion Kanban | `planning/notion-kanban.md` | 263 card siap import ke Notion |
| Demo Preparation | `planning/demo-preparation.md` | Panduan presentasi skripsi |

### 🔌 API (1 file)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| API Contracts | `api/api-contracts.md` | 43 endpoint API dengan request/response JSON |

### 🎨 UI (1 file)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Wireframes | `ui/wireframes.md` | Wireframe semua halaman |

### 🔒 Security (3 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Security Config | `security/security-config.md` | CipherSweet, 2FA, RBAC, OAuth |
| Caching Strategy | `security/caching-strategy.md` | Cache driver, keys, invalidation |
| Error Handling | `security/error-handling-strategy.md` | Fallback logic, mock mode, payroll lock |

### 🧪 Testing (1 file)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Testing Strategy | `testing/testing-strategy.md` | Feature tests, unit tests, browser tests, §9 Database Testing Strategy |

### 🚀 Deployment (1 file)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Deployment Guide | `deployment/deployment-guide.md` | VPS setup, SSL, CI/CD |

### 📖 Reference (1 file)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Complete File Blueprint | `reference/complete-file-blueprint.md` | Semua file path yang akan dibuat |

---

## 🎯 Urutan Baca (untuk Developer Baru)

| Urutan | Dokumen | Alasan |
|--------|---------|--------|
| 1 | `README.md` | Overview project & cara install |
| 2 | `PRD.md` | Pahami apa yang dibangun (v3.0 — semua koreksi sudah inline) |
| 3 | `PRD-errata.md` | (Optional) Referensi historis — semua koreksi sudah di-merge ke PRD v3.0 |
| 4 | `planning/task.md` | Pahami executable specification & errata |
| 5 | `architecture/erd.dbml` | Pahami struktur database (source of truth) |
| 6 | `architecture/folder-structure.md` | Pahami struktur project |
| 7 | `planning/execution-schedule.md` | Pahami timeline pengerjaan |
| 8 | `planning/sprint-branch-strategy.md` | Pahami cara kerja branch |
| 9 | `reference/complete-file-blueprint.md` | Referensi file yang dibuat |
| 10 | `api/api-contracts.md` | Referensi endpoint API |
| 11 | `planning/notion-kanban.md` | Import task ke Notion |

---

## 📊 Statistik Dokumen

| Kategori | Jumlah File |
|----------|-------------|
| Core | 4 |
| Architecture | 8 (1 deleted: erd.md → erd.dbml) |
| Planning | 4 |
| API | 1 |
| UI | 1 |
| Security | 3 |
| Testing | 1 |
| Deployment | 1 |
| Reference | 1 |
| **TOTAL** | **23** |

> **PRD v3.1 NOTE (2026-05-21):** 35 koreksi konsolidasi di atas v3.0:
> - **5 Kontradiksi Kritis (K1-K5):** LLM Gemini Pro→Flash, embedding dim 768, face distance threshold semantic, ERD source-of-truth, 33 enum lengkap
> - **8 Gap Modul (M1-M8):** Permission Matrix expand, WFA flow, Reimbursement state machine, 4 method PayrollCalculator baru (Pesangon/LeaveCashOut/UangKompensasi/Penghargaan), Salary type variants (monthly/daily/hourly), Special employment types (probation/intern/contract), PHK 3 variant, Unpaid leave impact
> - **18 Spec Area Baru (S1-S18):** §29 Validation Rules, §30 Locale & Format, §31 Data Retention & PDP Compliance (UU 27/2022), File Upload Specs, Audit Log Coverage, PWA expand, Accessibility WCAG 2.1 AA, Tax & Compliance Reports, Concurrency Control, KB pg_trgm fallback, 2FA Recovery, PPh 21 Annual Reconciliation, Branch-level overrides, Holiday management, Shift scheduling UX
> - **12 Cleanup Minor (N1-N12):** Reimbursement V2→V1, CipherSweet status sync, ERD as source-of-truth reference, env var seeder, Component Standards, payroll:generate command, OAuth payslip re-auth, PTKP keys, password-protected Excel export, Migration list expand, STRICT RULES klarifikasi, V2 list refactor
> - **§28 Glossary baru** (44 istilah)

> **ERRATA NOTE (2026-05-20):** PRD-errata.md sekarang READ-ONLY changelog — semua koreksi sudah di-merge ke PRD.md v3.0. Perubahan besar:
> - **PRD v3.0**: 28 koreksi (9 ERR + 19 CAT) di-merge inline dengan tag referensi
> - **PRD-errata v2.0**: Status berubah dari LOCKED → HISTORICAL CHANGELOG
> - **CAT-018**: Defensive Migration pattern — `DB::getDriverName() === 'pgsql'` guard di 3 migration
> - **CAT-019**: Dilarang hardcode SQL Error Code 23505 — WAJIB `UniqueConstraintViolationException`
> - **Testing §9**: Database Testing Strategy — SQLite vs PostgreSQL, defensive migration, mocking approach
> - **Code fixes**: 3 migration guards, AttendanceService clockOut columns fix, .env.example + config default → pgsql

> **PREVIOUS NOTE (2026-05-19):** Beberapa fix kritis yang sudah di-merge ke PRD v3.0:
> - **C1:** ApprovalLevel enum comparison bug — gunakan `$approval->level->value === 1`
> - **C2:** Payroll forceDelete — gunakan `forceDelete()` bukan `delete()` untuk regenerate
> - **C3:** Sanctum belum terinstall — perlu install sebelum API auth bisa bekerja
> - **C4:** Permission enum + seeders belum dibuat — `$user->can()` selalu false
> - **SEC-5:** BusinessRuleException → 422, FaceNotRegisteredException → 422, NotClockedInException → 409
> - **ERR-008:** KnowledgeBase::processEmbedding() crash — kolom `status` belum ada di migration
> - **ERR-009:** Observer directory kosong — EmployeeObserver + AttendanceObserver belum dibuat
> - **CAT-005:** Password expiry 90 hari (Security Config §1.5) menang atas PRD §4
> - **CAT-012:** AttendanceService::invalidateCache() dead code — Cache::tags() tidak didukung database driver
> - **CAT-013:** `holiday_*` cache tanpa invalidation — stale 30 hari
> - **CAT-014:** PTKP magic numbers hardcoded — harus dari CompanySetting
> - **CAT-015:** VerificationMethod enum missing + bug `'pin'` vs `'pin_verified'` — clock-out PIN logic broken
> - **CAT-016:** 22 hari kerja hardcoded — harus pakai `countWorkingDays()`
> - **CAT-017:** FaceNotRecognizedException ditelan — security hole, perlu tiered fallback
> - `erd.md` dihapus — hanya `erd.dbml` yang jadi source of truth

---

> Last Updated: 2026-05-21
