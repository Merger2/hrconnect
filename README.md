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
| [Spatie Permission](https://spatie.be/docs/laravel-permission) | Latest | Role-Based Access Control (RBAC) |
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

## 📁 Project Structure

```text
hrconnect/
├── app/
│   ├── Console/Commands/     # Artisan commands (detect-alpha, reset-quota)
│   ├── Enums/               # 30+ enum files (EmploymentType, RequestStatus, dll)
│   ├── Exceptions/          # Custom exceptions (GeofenceViolation, BusinessRule)
│   ├── Http/
│   │   ├── Middleware/      # CheckPasswordExpired, EnsureSecurityHeaders, DeviceDetection
│   │   └── Requests/        # Form request validation
│   ├── Jobs/                # Background jobs (payroll, embeddings, notifications)
│   ├── Livewire/            # Reactive components (ESS, HRD, Finance, Admin)
│   ├── Models/              # Eloquent models (35 total, CipherSweet encrypted PII)
│   ├── Notifications/       # Mail & database notifications
│   ├── Observers/           # Model observers (Employee, Attendance, Leave)
│   ├── Policies/            # Authorization policies
│   └── Services/            # Business logic layer
│       ├── AttendanceService      # Clock-in/out, WFA, risk scoring
│       ├── LeaveService           # Quota, balance, carry-forward
│       ├── PayrollCalculatorService # PPh21 TER, BPJS, prorata, THR
│       ├── ApprovalService        # Multi-level approval matrix
│       ├── GeofenceService        # Haversine, anti-fake GPS
│       ├── EmbeddingService       # pgvector + RAG search
│       ├── FaceRecognitionService # 128D face descriptors
│       ├── PayslipPdfService      # E-Payslip PDF generation
│       └── EmployeeTerminationService # PHK, pesangon calc
├── database/
│   ├── factories/           # 25+ model factories
│   ├── migrations/          # 50+ migrations (PostgreSQL extensions)
│   └── seeders/             # 12 seeders (RoleAndPermission, SuperAdmin, E2eTest)
├── docs/                    # 20+ documentation files
│   ├── PRD.md               # Product Requirements Document v3.1
│   ├── architecture/        # ERD, SRS, SDD, class/sequence diagrams
│   ├── planning/            # Sprint strategy, pages-masterplan
│   ├── api/                 # OpenAPI spec (63 paths, 84 operations)
│   ├── security/            # Security config, caching strategy
│   ├── testing/             # Testing strategy
│   └── deployment/          # Deployment guide
├── resources/
│   ├── css/app.css          # Tailwind v4 + HP Electric Blue design system
│   ├── js/                  # face-recognition.js, gps-locator.js
│   └── views/               # Blade templates + Livewire components
│       ├── layouts/app/     # Sidebar + header layout
│       ├── pages/auth/      # Login, 2FA, password reset
│       └── livewire/        # Master data CRUD components
├── routes/
│   ├── web.php              # Main routes (dashboard, employee)
│   ├── attendance.php       # Attendance routes (clock-in, face registration)
│   ├── leave.php            # Leave management routes
│   ├── overtime.php         # Overtime routes (apply, history)
│   ├── reimbursement.php    # Reimbursement routes
│   ├── loan.php             # Loan/Kasbon routes
│   ├── payroll.php          # Payroll + E-Payslip routes
│   ├── master-data.php      # Branch, Department, Position, Shift, Holiday, LeaveType
│   ├── knowledge-base.php   # RAG AI chat routes
│   ├── settings.php         # Profile, security, appearance
│   ├── api.php              # REST API routes
│   └── approval.php         # Approval workflow routes
├── tests/
│   ├── Feature/             # 50+ Pest test files (API + feature tests)
│   │   ├── Api/             # ProofTest + EndpointTest (per-module API coverage)
│   │   ├── Auth/            # Auth flows (2FA, password, registration)
│   │   └── Services/        # Service-level tests
│   ├── Unit/                # 25+ unit tests (models, services, jobs)
│   ├── Integration/Postgres/ # PostgreSQL integration tests (41 tests)
│   └── e2e/                 # 27 Playwright E2E tests
└── .github/workflows/       # CI: postgres + sqlite + quality
```

---

## 🔐 Security Features

| Feature | Implementation |
|---------|---------------|
| **Field Encryption** | CipherSweet blind indexing untuk NIK, phone, NPWP, bank account |
| **Role-Based Access** | Spatie Permission (5 roles: Super Admin, HRD, Finance, Manager, Employee) + 44 permissions |
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

**Totals:** 1,189 BE tests + 27 E2E tests = **1,216 passing**.

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
