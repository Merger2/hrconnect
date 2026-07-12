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
| **PRD** | `PRD.md` | Product Requirements Document v3.1 — 35 koreksi konsolidasi |
| **PRD Errata** | `PRD-errata.md` | READ-ONLY changelog |
| **README** | `../README.md` | Project overview, installation guide |

### 📐 Architecture (7 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| SRS | `architecture/srs.md` | Software Requirements Specification |
| SDD | `architecture/sdd.md` | Software Design Description |
| ERD | `architecture/erd.dbml` | Entity Relationship Diagram — 48 tabel |
| Class Diagram | `architecture/class-diagram.md` | UML class diagram |
| Sequence Diagrams | `architecture/sequence-diagrams.md` | Flow interaksi |
| Folder Structure | `architecture/folder-structure.md` | Struktur direktori project |
| Deployment Diagram | `architecture/deployment-diagram.md` | Infrastruktur & deployment |

### 📋 Planning (4 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Task Spec | `planning/task.md` | Executable specification — per-item status |
| Pages Masterplan | `planning/pages-masterplan.md` | Mapping halaman per modul |
| Sprint Branch Strategy | `planning/sprint-branch-strategy.md` | Strategi branching & sprint |
| Demo Preparation | `planning/demo-preparation.md` | Panduan presentasi skripsi |

### 🔌 API (5 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| API Contracts | `api/api-contracts.md` | 51 endpoint API (v3.0) |
| OpenAPI Spec | `api/api.json` | OpenAPI 3.1 spec (51 operations) |
| Scope v1 | `api/scope-v1.md` | Scope API v1 |
| Service Matrix | `api/service-matrix.md` | Service dependency matrix |
| Study Cases | `api/study-cases.md` | API use case scenarios |

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
| Testing Strategy | `testing/testing-strategy.md` | Strategi testing |

### 🚀 Deployment (3 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Deployment Guide | `deployment/deployment-guide.md` | VPS setup, SSL, queue worker |
| Nginx Config | `deployment/nginx.conf` | Nginx template production-ready |
| Supervisor Config | `deployment/hrconnect-worker.conf` | Supervisor config queue + scheduler |

### 📖 Reference (2 files)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Complete File Blueprint | `reference/complete-file-blueprint.md` | Semua file path project |
| Audit Trail | `reference/audit-trail-migrations-models.md` | Audit trail migrasi vs PRD |

### ⚙️ Operations (1 file)
| Dokumen | Path | Deskripsi |
|---------|------|-----------|
| Readiness | `ops/readiness.md` | Operations readiness checklist (queue, cache, backup) |

---

## 🎯 Urutan Baca (untuk Developer Baru)

| Urutan | Dokumen | Alasan |
|--------|---------|--------|
| 1 | `README.md` | Overview project & cara install |
| 2 | `PRD.md` | Pahami apa yang dibangun (v3.1) |
| 3 | `architecture/srs.md` | Pahami requirement formal |
| 4 | `architecture/sdd.md` | Pahami desain arsitektur |
| 5 | `ops/readiness.md` | Pahami operational readiness |
| 6 | `testing/testing-strategy.md` | Pahami strategi testing |
| 7 | `deployment/deployment-guide.md` | Panduan deployment |

---

## 📊 Statistik Dokumen

| Kategori | Jumlah File |
|----------|-------------|
| Core | 3 |
| Architecture | 7 |
| Planning | 4 |
| API | 5 |
| UI | 1 |
| Security | 3 |
| Testing | 1 |
| Deployment | 3 |
| Reference | 2 |
| Operations | 1 |
| **TOTAL** | **30** |

---

> Last Updated: 2026-07-12
