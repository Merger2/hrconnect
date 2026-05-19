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
| **PRD** | `PRD.md` | Product Requirements Document — spesifikasi lengkap fitur |
| **PRD Errata** | `PRD-errata.md` | CTO amendments — 9 ERR + 17 CAT items (v1.2), overrides PRD on conflict |
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
| Testing Strategy | `testing/testing-strategy.md` | Feature tests, unit tests, browser tests |

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
| 2 | `PRD.md` | Pahami apa yang dibangun |
| 3 | `PRD-errata.md` | Pahami CTO decisions yang override PRD |
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

> **ERRATA NOTE (2026-05-19):** Semua dokumen sudah diupdate dengan errata dari task.md v3.4 dan PRD-errata.md v1.2. Beberapa fix kritis:
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

> Last Updated: 2026-05-19
