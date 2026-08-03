<p align="center">
  <img src="https://img.shields.io/badge/Status-Production%20Ready-22c55e?style=for-the-badge" alt="Status: Production Ready" />
  <img src="https://img.shields.io/badge/Tests-1216%20passing-22c55e?style=for-the-badge" alt="Tests: 1216 passing" />
  <img src="https://img.shields.io/badge/CI-All%20Green-22c55e?style=for-the-badge" alt="CI: All Green" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 13" />
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.5" />
  <img src="https://img.shields.io/badge/Livewire-4-4E56A6?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire 4" />
  <img src="https://img.shields.io/badge/PostgreSQL-15+-336791?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL 15+" />
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License MIT" />
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Security-CipherSweet-00ADEF?style=flat-square" alt="Security: CipherSweet" />
  <img src="https://img.shields.io/badge/AI-Gemini+OpenAI-4285F4?style=flat-square" alt="AI: Gemini + OpenAI" />
  <img src="https://img.shields.io/badge/Face-Recognition-00D4AA?style=flat-square" alt="Face Recognition" />
  <img src="https://img.shields.io/badge/PWA-Mobile_First-5A0FC8?style=flat-square" alt="PWA Mobile First" />
</p>

<br />

<p align="center">
  <h1 align="center">HRConnect — Enterprise HRIS Platform</h1>
  <p align="center">
    Sistem HRIS mutakhir dengan keamanan tingkat bank dan AI terintegrasi.<br />
    Presensi wajah, geofencing anti-fake-GPS, payroll otomatis, dan Knowledge Base berbasis RAG.
  </p>
</p>

<br />

---

## 📖 About The Project

**HRConnect** adalah platform HRIS enterprise-grade yang dibangun oleh **solo developer** dengan filosofi:

> *"Keamanan data karyawan adalah prioritas utama. Otomasi payroll harus sempurna. AI harus bisa diandalkan."*

Dibangun dengan **Laravel 13**, **Livewire 4**, **PostgreSQL + pgvector**, **face-api.js**, dan **Gemini 2.5 Flash** untuk menciptakan ekosistem HRIS lengkap:

- 📱 **PWA Mobile-First** — Employee Self-Service (clock-in/out, cuti, slip gaji, KB chat)
- 🤖 **AI Knowledge Base (RAG)** — Tanya jawab SOP via chat (pgvector 768D + Gemini)
- 🔐 **Bank-Level Encryption** — CipherSweet encrypt PII (NIK, HP, NPWP, bank) dengan blind indexing searchable
- 💰 **Payroll Engine** — PPh21 TER, BPJS, prorata, denda, lembur, THR, payslip PDF otomatis
- 🔄 **Multi-Level Approval** — L1 Manager → L2 HR/Finance, auto-escalation 24 jam
- 🎨 **Modern UI** — HP Electric Blue design system, dark mode, responsive sidebar

**Status: Production Ready** — 3 PRs merged ke `main`, 1,189 BE tests + 27 E2E tests passing, CI all green.

---

## ✨ Key Features

### 🧠 AI & Machine Learning

- **AI Face Recognition Clock-In** — Presensi WFO/WFA menggunakan pencocokan wajah langsung di browser (face-api.js, FaceNet 128D). Tanpa membebani server.
- **AI-Powered Knowledge Base (RAG)** — Chat dengan dokumen SOP perusahaan (PDF). Ditenagai oleh Gemini 2.5 Flash + Vector Database (pgvector 768D). Fallback ke full-text search (pg_trgm) jika AI down.

### 📍 Location & Security

- **Anti-Fake GPS Geofencing** — Validasi lokasi karyawan menggunakan Haversine Formula dengan radius dinamis per cabang. Deteksi mock GPS dari browser.
- **Bank-Level Data Encryption** — Data sensitif karyawan (NIK, No. HP, NPWP) dienkripsi menggunakan **CipherSweet** dengan blind indexing, namun tetap **searchable** di database.

### 💰 Payroll & Finance

- **Enterprise Payroll Engine** — Kalkulasi gaji prorata (hari kerja aktual), PPh21 TER (Kategori A/B/C), BPJS Kesehatan/Ketenagakerjaan, denda keterlambatan, dan lembur secara otomatis.
- **Permanent Payroll Lock** — Setelah payroll dipublish, **tidak bisa diubah**. Koreksi hanya melalui adjustment di bulan berikutnya.

### 🔄 Workflow & Approval

- **Multi-Level Approval Workflow** — Sistem persetujuan berjenjang (L1 Manager → L2 HR Manager) untuk cuti, lembur, dan reimbursement.
- **Auto-Escalation** — Approval yang tidak ditindaklanjuti >24 jam otomatis di-eskalasi.
- **Leave Quota Management** — Kuota cuti otomatis, carry-forward tahunan, dan probation block.

### 📱 Employee Self-Service (PWA)

- **Mobile-First PWA** — Semua fitur ESS bisa diakses dari browser HP: clock-in/out, ajukan cuti, lihat slip gaji, cek kuota.
- **Face Enrollment** — Pendaftaran wajah langsung dari kamera HP saat orientasi hari pertama.
- **Device Management** — Registrasi dan verifikasi perangkat. Maksimal 3 device per karyawan.

---

## 🛠️ Tech Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| [Laravel](https://laravel.com) | 13.x | PHP Framework |
| [PHP](https://php.net) | 8.5 | Runtime |
| [Livewire](https://livewire.laravel.com) | 4.x | Reactive UI Components |
| [Flux UI](https://fluxui.dev) | 2.x | UI Component Library |
| [Fortify](https://laravel.com/docs/fortify) | 1.x | Authentication Backend |
| [Permission Trait](https://spatie.be/docs/laravel-permission) | Custom | RBAC via `HasRolePermissions` trait + `permission_keys` JSON column (bukan Spatie pivot) |
| [CipherSweet](https://ciphersweet.paragonie.com) | Latest | Field-Level Encryption + Blind Indexing |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| [Alpine.js](https://alpinejs.dev) | 3.x | Lightweight JS Framework |
| [Tailwind CSS](https://tailwindcss.com) | 4.x | Utility-First CSS |
| [face-api.js](https://github.com/justadudewhohacks/face-api.js) | Latest | Client-Side Face Recognition (FaceNet 128D) |
| PWA | — | Progressive Web App (Mobile-First) |

### Database & Queue
| Technology | Version | Purpose |
|------------|---------|---------|
| [PostgreSQL](https://postgresql.org) | 15+ | Primary Database |
| [pgvector](https://github.com/pgvector/pgvector) | Latest | Vector Similarity Search (Face + RAG) |
| [pg_trgm](https://postgresql.org/docs/current/pgtrgm.html) | Built-in | Full-Text Search (RAG Fallback) |
| [pgcrypto](https://postgresql.org/docs/current/pgcrypto.html) | Built-in | Cryptographic Functions |
| Queue Driver | Database → Redis | Background Jobs |

### AI & ML
| Technology | Purpose |
|------------|---------|
| **face-api.js** (FaceNet) | Client-side face detection & 128D embedding |
| **Google Gemini** (text-embedding-004) | PDF chunk embedding → vector(768) |
| **Google Gemini 2.5 Flash** | RAG Knowledge Base Q&A |

---

## 📋 Prerequisites

Pastikan environment Anda sudah ter-install:

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| **PHP** | 8.5 | 8.5+ |
| **Composer** | 2.5+ | Latest |
| **Node.js** | 20.x | 22.x LTS |
| **npm** | 10.x | Latest |
| **PostgreSQL** | 15+ | 16+ |
| **Required Extensions** | `pgsql`, `mbstring`, `xml`, `ctype`, `json`, `bcmath`, `intl` | — |
| **PostgreSQL Extensions** | `pgvector`, `pg_trgm`, `pgcrypto` | — |

### Install PostgreSQL Extensions

```bash
# Ubuntu/Debian
sudo apt install postgresql-16-pgvector

# macOS (Homebrew)
brew install pgvector

# Enable extensions di PostgreSQL
psql -d hrconnect -c "CREATE EXTENSION IF NOT EXISTS vector;"
psql -d hrconnect -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"
psql -d hrconnect -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;"
```

---

## 🚀 Installation Guide

### 1. Clone Repository

```bash
git clone https://github.com/fikih/hrconnect.git
cd hrconnect
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hrconnect
DB_USERNAME=your_username
DB_PASSWORD=your_password

# AI Configuration (opsional — ada mock mode untuk demo)
RAG_MOCK_MODE=true
GOOGLE_AI_API_KEY=AIza...
GEMINI_MODEL=gemini-2.5-flash
GEMINI_EMBEDDING_MODEL=text-embedding-004
```

### 4. Database Setup

```bash
# Enable PostgreSQL extensions
psql -d hrconnect -c "CREATE EXTENSION IF NOT EXISTS vector;"
psql -d hrconnect -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;"
psql -d hrconnect -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;"

# Run migrations
php artisan migrate

# Seed demo data (roles, employees, demo records)
php artisan db:seed
```

### 5. Build Frontend

```bash
# Development
npm run dev

# Production
npm run build
```

### 6. Start Development Server

```bash
# Option 1: Laravel built-in
php artisan serve

# Option 2: Laravel Sail (Docker)
./vendor/bin/sail up -d

# Option 3: Full dev environment (server + queue + vite)
composer run dev
```

### 7. Start Queue Worker

```bash
php artisan queue:work --queue=default,payroll_high,notifications
```

### 8. Access Application

| URL | Purpose |
|-----|---------|
| `http://localhost:8000` | Dashboard |
| `http://localhost:8000/login` | Login Page |

**Demo Credentials (local dev via `E2eTestSeeder`):**

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@hrconnect.local` | `ChangeMe!2026` |
| HRD Manager | `hr@hrconnect.test` | `password` |
| Employee | `employee@hrconnect.test` | `password` |

> Super Admin seeded via `SuperAdminSeeder` (config: `hrconnect.super_admin_email`). Test users via `E2eTestSeeder`.

---

> 🗺️ **Single Source of Truth: [`docs/PROJECT-MAP.md`](docs/PROJECT-MAP.md)** — Livewire component map, full route list, service layer, database schema, design system, conventions, and latest fixes.
>
> 📋 **Other key references:**
> - [`docs/FEATURES.md`](docs/FEATURES.md) — Feature status (✅/⚠️/❌) with latest fixes
> - [`docs/AUDIT-MAP.md`](docs/AUDIT-MAP.md) — Quick finding index for developers
> - [`AGENTS.md`](AGENTS.md) — **Must-read** for AI coding agents (FK patterns, CSS rules, gotchas)

## 📁 Project Structure (Overview)

```text
hrconnect/
├── app/                          # ~596 PHP files
│   ├── Actions/                  # Fortify, Hr, Reimbursement actions
│   ├── Ai/                       # AI agents (Laravel AI SDK)
│   ├── Console/Commands/         # Artisan commands
│   ├── Enums/                    # 28 PHP enums
│   ├── Exceptions/               # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/          # API & Web controllers
│   │   ├── Middleware/           # 8+ middleware
│   │   └── Requests/             # Form request validation
│   ├── Jobs/                     # Background queue jobs
│   ├── Livewire/                 # 86 reactive components (User/Admin/Shared/Profile/Forms)
│   ├── Models/                   # 101 Eloquent models (CipherSweet encrypted PII)
│   ├── Notifications/            # Mail & database notifications
│   ├── Observers/                # Model observers
│   ├── Policies/                 # 20 authorization policies
│   ├── Providers/                # 6 service providers
│   ├── Queries/                  # Query builder classes
│   └── Services/                 # 11 service directories
│       ├── Attendance/           # Clock-in/out, WFA, face registration
│       ├── Payroll/              # PPh21 TER, BPJS, prorata, THR, payslip PDF
│       ├── Security/             # Face recognition, embeddings
│       ├── KnowledgeBase/        # RAG engine
│       ├── Location/             # Geofencing, Haversine
│       ├── HR/                   # Leave, termination
│       └── ...                   # Admin, Audit, Integrations, Rag, Reporting, Support
├── database/
│   ├── factories/                # Model factories
│   ├── migrations/               # 153 migrations
│   └── seeders/                  # 22 seeders
├── docs/                         # Documentation hub
│   ├── PROJECT-MAP.md            # ← Comprehensive project map (start here)
│   ├── FEATURES.md               # Feature status list
│   ├── FEATURE-INVENTORY.md      # Component existence map
│   ├── AUDIT-MAP.md              # Audit finding index
│   ├── ROLES-PERMISSIONS.md      # RBAC matrix
│   ├── PRD.md                    # Product Requirements Document
│   └── ...                       # Architecture, API, security, testing, deployment
├── lang/
│   ├── en.json                   # English translations
│   └── id.json                   # Indonesian translations
├── resources/
│   ├── css/app.css               # Tailwind v4 + BEM design system (~6600 lines)
│   ├── js/                       # Alpine, face-api, GPS locator, SSE, PWA
│   └── views/                    # Blade templates (layouts, components, livewire, pages)
├── routes/
│   ├── api.php                   # 219 REST API routes
│   ├── web.php                   # 15 web routes (requires 5 sub-files: system, files, user, payroll, admin)
│   ├── jetstream.php             # Jetstream auth (login, 2FA, password)
│   ├── knowledge-base.php        # KB management + RAG chat
│   ├── channels.php              # Reverb broadcasting
│   └── console.php               # Artisan commands
├── tests/
│   ├── Feature/                  # 40+ Pest tests
│   ├── Unit/                     # Unit tests
│   ├── Integration/Postgres/     # PostgreSQL integration tests
│   └── e2e/                      # 8 Playwright E2E specs
├── AGENTS.md                     # Critical guidance for AI coding agents
├── CONVENTIONS.md                # Coding conventions & patterns
├── AUDIT-2026-07-24.md           # Master audit (280+ findings)
└── .github/workflows/            # CI: postgres + sqlite + quality
```

👉 **Start reading [`docs/PROJECT-MAP.md`](docs/PROJECT-MAP.md)** — the single source of truth for all developer reference.

---

## 🔐 Security Features

| Feature | Implementation |
|---------|---------------|
| **Field Encryption** | CipherSweet blind indexing untuk NIK, phone, NPWP, bank account |
| **Role-Based Access** | `HasRolePermissions` trait + `permission_keys` JSON column (6 roles × 38 permission gates + 8 admin bypass) |
| **2FA TOTP** | Laravel Fortify dengan Google Authenticator |
| **Force Password Change** | Middleware `CheckPasswordExpired` — wajib ganti password saat login pertama |
| **Security Headers** | `EnsureSecurityHeaders` — CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy |
| **Rate Limiting** | Fortify `RateLimiter` — 5 login per email/IP, throttled registration |
| **Device Verification** | Registrasi & verifikasi perangkat (max 3 device) |
| **Anti-Fake GPS** | Browser mock detection + accuracy validation |
| **Payroll Lock** | Published payroll permanent — tidak bisa di-unpublish |
| **CIPHERSWEET_KEY** | 64-char hex key in env, all PII columns encrypted at rest |

---

## 🧪 Testing

HRConnect memiliki **triple-layer testing** — unit/service, feature/API, dan E2E browser.

### Backend (Pest PHP)

```bash
# Run all backend tests (sqlite in-memory, 1189 tests)
php artisan test

# Run with coverage
php artisan test --coverage

# PostgreSQL integration tests (requires pgvector, pg_trgm, pgcrypto)
php artisan test --configuration=phpunit.pgsql.xml

# Run specific module
php artisan test --filter=Payroll
```

### E2E (Playwright)

```bash
# Install browsers (sekali)
npx playwright install chromium

# Run all E2E specs (27 tests)
npx playwright test

# Run specific spec
npx playwright test tests/e2e/loans.spec.ts
```

> Auth di-handle via `storageState` (login sekali per role) untuk menghindari Fortify throttle. Lihat `playwright.config.js` + `tests/e2e/auth.setup.ts`.

### CI/CD (GitHub Actions)

Setiap push/PR menjalankan 3 job paralel:

| Job | Scope | Target |
|-----|-------|--------|
| `postgres` | Integration tests | PostgreSQL 15+ (pgvector, pg_trgm, pgcrypto) |
| `sqlite` | Full suite | SQLite in-memory (1189 tests) |
| `quality` | Lint + static analysis | Pint, PHPStan |

**Status:** ✅ All green di `main`.

### Test Coverage per Module

| Module | BE (Unit+API) | E2E | Status |
|--------|---------------|-----|--------|
| Authentication | ✅ | ✅ | Complete |
| Face Recognition | ✅ | ✅ | Complete |
| RAG KnowledgeBase | ✅ | ✅ | Complete |
| Payroll | ✅ | — | Complete (service-level) |
| Attendance | ✅ | ✅ | Complete |
| Leave | ✅ | — | Complete |
| Overtime | ✅ | ✅ | Complete |
| Reimbursement | ✅ | — | Complete |
| Loan/Kasbon | ✅ | ✅ | Complete |
| Approval Workflow | ✅ | — | Complete |
| Master Data | ✅ | ✅ | Complete |
| Roles & Permissions | ✅ | — | Complete |
| Security & Compliance | ✅ | — | Complete (CipherSweet, CSP, 2FA) |

**Totals:** ~1,189 BE tests + 27 E2E tests = **~1,216 passing**.

---

## 📚 Development Quick Reference

### Before Coding

1. **Baca `AGENTS.md`** — kritis: schema FK pattern, Role/Permission, CSS rules, Livewire quirks
2. **Cek `AUDIT-2026-07-24.md`** — jangan re-fix known issues
3. **Cari 1-2 contoh existing** dengan pola yang sama
4. **Lihat `docs/PROJECT-MAP.md`** untuk navigasi komponen

### Commands

```bash
composer run dev              # Full dev: server + queue + logs + Vite
composer run test             # config:clear → pint → php artisan test
vendor/bin/pint --dirty --format agent  # WAJIB setelah tiap perubahan PHP
php artisan test --compact --filter=KnowledgeBase  # Test spesifik
npm run build                 # Build assets
npx playwright test employee-pages.spec.ts --project=chromium-employee  # E2E
```

### Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Employee | `employee@hrconnect.test` | `password` |
| Super Admin | `admin@hrconnect.local` | `ChangeMe!2026` |

---

## 📊 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        PWA (Browser)                        │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────────┐  │
│  │ face-api.js  │  │ Geolocation  │  │  Alpine.js + UI   │  │
│  │ (128D Face)  │  │   (GPS)      │  │   (Livewire 4)    │  │
│  └──────┬───────┘  └──────┬───────┘  └────────┬──────────┘  │
│         │                 │                   │             │
└─────────┼─────────────────┼───────────────────┼─────────────┘
          │                 │                   │
          ▼                 ▼                   ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel 13 Backend                       │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              Services Layer                         │    │
│  │  AttendanceService │ LeaveService │ PayrollCalc     │    │
│  │  GeofenceService   │ ApprovalSvc  │ Termination     │    │
│  └─────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              Jobs & Queue                           │    │
│  │  PayrollGeneration │ Embedding │ Notifications      │    │
│  └─────────────────────────────────────────────────────┘    │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    PostgreSQL 15+                           │
│  ┌────────────┐  ┌────────────┐  ┌───────────────────────┐  │
│  │  pgvector  │  │  pg_trgm   │  │       pgcrypto        │  │
│  │ (128D+768D │  │ (RAG Fallback│ (CipherSweet Encryption)│ │
│  │  vectors)  │  │   search)  │  │                       │  │
│  └────────────┘  └────────────┘  └───────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 👨‍💻 Author

**Fikih**

- Solo Developer
- HRConnect HRIS — Enterprise-grade Human Resource Information System
- Built with Laravel 13, PHP 8.5, PostgreSQL + pgvector

---

## 📄 License

HRConnect is open-sourced software licensed under the [MIT License](LICENSE).

---

<p align="center">
  Made with AI using Laravel, Livewire, and a lot of coffee.
</p>
