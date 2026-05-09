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

**HRConnect** bukan sekadar aplikasi HRIS biasa. Ini adalah platform manajemen sumber daya manusia **skala enterprise** yang dirancang oleh **solo developer** dengan filosofi:

> *"Keamanan data karyawan adalah prioritas utama. Otomasi payroll harus sempurna. AI harus bisa diandalkan."*

HRConnect menggabungkan **Laravel 13**, **Livewire 4**, **PostgreSQL dengan pgvector**, dan **face-api.js** untuk menciptakan ekosistem HRIS yang mencakup:

- 📱 **PWA Mobile-First** untuk Employee Self-Service (ESS) — clock-in/out dari mana saja
- 🤖 **AI Knowledge Base** — tanya jawab SOP perusahaan via chat (RAG + Gemini 2.5 Flash)
- 🔐 **Bank-Level Encryption** — NIK, No. HP, NPWP terenkripsi tapi tetap bisa dicari
- 💰 **Payroll Engine** — kalkulasi PPh21 TER, BPJS, prorata, denda, lembur secara otomatis

Proyek ini dikembangkan sebagai **skripsi** dan **proof-of-concept** bahwa solo developer bisa membangun sistem HRIS enterprise-grade dengan tech stack modern.

---

## ✨ Key Features

### 🧠 AI & Machine Learning

- **AI Face Recognition Clock-In** — Presensi WFO/WFA menggunakan pencocokan wajah langsung di browser (face-api.js, FaceNet 128D). Tanpa membebani server.
- **AI-Powered Knowledge Base (RAG)** — Chat dengan dokumen SOP perusahaan (PDF). Ditenagai oleh Gemini 2.5 Pro + Vector Database (pgvector 1536D). Fallback ke full-text search (pg_trgm) jika AI down.

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
| **OpenAI** (text-embedding-3-small) | PDF chunk embedding → vector(1536) |
| **Google Gemini 2.5 Pro** | RAG Knowledge Base Q&A |

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
OPENAI_API_KEY=sk-...
GEMINI_API_KEY=AIza...
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

**Demo Credentials:**

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@company.com` | `password` |
| HRD Manager | `hrd@company.com` | `password` |
| Finance | `finance@company.com` | `password` |
| Employee | `employee@company.com` | `password` |

---

## 📁 Project Structure

```
hrconnect/
├── app/
│   ├── Console/Commands/     # Artisan commands (detect-alpha, reset-quota)
│   ├── Enums/               # 30 enum files (EmploymentType, RequestStatus, dll)
│   ├── Http/
│   │   ├── Middleware/      # DeviceDetection, GeofenceValidation, ForcePasswordChange
│   │   └── Requests/        # Form request validation
│   ├── Jobs/                # Background jobs (payroll, embeddings, notifications)
│   ├── Livewire/            # Reactive components (ESS, HRD, Finance, Admin)
│   ├── Models/              # Eloquent models (32 total)
│   ├── Notifications/       # Mail & database notifications
│   ├── Observers/           # Model observers (Employee, Attendance, Leave)
│   ├── Policies/            # Authorization policies
│   └── Services/            # Business logic layer
│       ├── AttendanceService
│       ├── LeaveService
│       ├── PayrollCalculatorService
│       ├── ApprovalService
│       ├── GeofenceService
│       └── EmployeeTerminationService
├── database/
│   ├── factories/           # 25 model factories
│   ├── migrations/          # 50 migrations (34 existing + 16 new)
│   └── seeders/             # 12 seeders
├── docs/                    # 20 documentation files
│   ├── architecture/        # ERD, class diagrams, sequence diagrams
│   ├── planning/            # Sprint strategy, kanban, execution schedule
│   ├── api/                 # API contracts
│   ├── security/            # Security config, caching, error handling
│   ├── testing/             # Testing strategy
│   ├── deployment/          # Deployment guide
│   └── reference/           # Complete file blueprint
├── resources/
│   ├── js/                  # face-detection.js, gps-locator.js, face-enrollment.js
│   └── views/               # Blade templates (ESS, HRD, Finance layouts)
├── routes/
│   ├── web.php              # Main routes
│   ├── employee.php         # ESS routes
│   ├── hrd.php              # HRD admin routes
│   ├── finance.php          # Finance admin routes
│   ├── admin.php            # Super admin routes
│   └── api.php              # PWA API routes
└── tests/                   # Feature & unit tests
```

---

## 🔐 Security Features

| Feature | Implementation |
|---------|---------------|
| **Field Encryption** | CipherSweet blind indexing untuk NIK, phone, NPWP |
| **Role-Based Access** | Spatie Permission (5 roles: Super Admin, HRD, Finance, Manager, Employee) |
| **2FA TOTP** | Laravel Fortify dengan Google Authenticator |
| **Google OAuth** | Social login untuk akses cepat |
| **Force Password Change** | Wajib ganti password saat login pertama |
| **Device Verification** | Registrasi & verifikasi perangkat (max 3 device) |
| **Anti-Fake GPS** | Browser mock detection + accuracy validation |
| **Payroll Lock** | Published payroll permanent — tidak bisa di-unpublish |
| **Session Security** | Configurable timeout, concurrent session control |

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/Payroll/PayrollGenerationTest.php

# Run with filter
php artisan test --filter=Payroll
```

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
│  │ (128D+1536D│  │ (RAG Fallback│ (CipherSweet Encryption)│ │
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
