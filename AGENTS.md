# AGENTS.md — HRConnect

**Stack:** Laravel 13 / PHP 8.5+ / PostgreSQL 15+ (pgvector, pg_trgm, pgcrypto) / Livewire 4 / Tailwind v4 / Alpine.js / PWA  
**Dev:** Fikih (solo) — HRIS enterprise: face recognition, GPS geofencing, payroll PPh21, RAG KB

---

## Perintah Penting

| Perintah | Fungsi |
|----------|--------|
| `composer run dev` | Server + queue + logs + Vite concurrently |
| `composer run test` | `config:clear` → `pint --parallel --test` → `php artisan test` |
| `composer run test:pgsql` | Test pake pgsql (`phpunit.pgsql.xml` — cuma ada di CI) |
| `composer run setup` | Bootstrap penuh: install → .env → key → migrate → npm → build |
| `vendor/bin/pint --dirty --format agent` | **Wajib** setelah tiap perubahan PHP (output: `{"result":"pass"}` atau `"fixed"`) |
| `php artisan test --compact --filter=X` | Test spesifik |
| `php artisan route:list --except-vendor` | Lihat routes |
| `npx playwright test` | E2E (server harus jalan di localhost:8000) |
| `php artisan queue:work --queue=default,payroll_high,notifications` | Worker payroll, embeddings, notifikasi |
| `npm run build` | Build assets (kalau error Vite manifest) |

---

## Arsitektur

- `bootstrap/app.php` — `then()` muat `knowledge-base.php` + `jetstream.php`. `routes/web.php` require 5 file: `system`, `files`, `user`, `payroll`, `admin` — masing-masing punya middleware group sendiri.
- 8 middleware aliases: `admin`, `user`, `role`, `permission`, `role_or_permission`, `password.expired`, `attendance.integration.signature`, `feature.lock`.
- `app/helpers.php` — auto-loaded via `composer.json` `files`.
- `app/Livewire/` → komponen. `app/Services/` → business logic. `app/Support/` → helper. `app/Domain/` → domain logic.

---

## Auth & Gates — Rapuh

- **Permission enum** (`App\Enums\Permission`): 38 case. Value = nama gate. Campur `snake_case`/`camelCase` — dua-duanya work via alias di `AuthServiceProvider`.
- **AuthServiceProvider** auto-register semua case sebagai Gates + camelCase alias.
- **HasRolePermissions** trait (bukan Spatie `HasRoles`): `hasPermission()` + `checkPermissionTo()`. Super-admin dapat `['*']` → lolos semua Gates.
- **Custom LoginResponse** (`app/Http/Responses/LoginResponse.php`): admin → `route('admin.dashboard')`, user → `route('home')`. Belum verifikasi → `verification.notice`.
- **`config/jetstream.php`** TIDAK pakai `auth_session` — nambahin bakal break Sanctum.
- **`config/permission.php`** pake `App\Models\Role::class` (bukan default Spatie).
- **AdminMiddleware** izin 8 permission (termasuk `manage_employees`) bypass admin gate.
- **UserMiddleware** (`app/Http/Middleware/UserMiddleware.php`) — middleware terpisah untuk gate level employee.

---

## Env Quirks

- `.npmrc` → `ignore-scripts=true` — postinstall hooks gak jalan.
- `CIPHERSWEET_KEY` (64-char hex) **wajib**. Test: `0123456789abcdef0123456789abcdef`.
- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` — gak pake Redis di dev.
- `pgvector/pgvector` di `composer.json` `extra.laravel.dont-discover` — service provider gak auto-discovered.
- `/.agents/`, `/.hermes/`, `/.claude/`, `/.codex/`, `/.gemini/`, `/docs/`, `/.github/`, `/tests/` — gitignored.
- `RAG_MOCK_MODE=true` hindari API call. `GEMINI_API_KEY` prefer dari `GOOGLE_AI_API_KEY`.
- `SUPER_ADMIN_PASSWORD` di `.env.example` **kosong** — generate: `php artisan tinker --execute='echo \Illuminate\Support\Str::password()'`. Demo: `admin@hrconnect.local` / `ChangeMe!2026` (via `E2eTestSeeder`).
- `FORTIFY_REGISTRATION_ENABLED=true` — set `false` di production buat matiin self-registrasi.

---

## Testing — Kondisi

**phpunit.xml** → pgsql `hris_payroll`, queue=sync, session=array, `CIPHERSWEET_KEY` dummy. BUKAN `hris_testing` (AGENTS.md lama salah — ini fix-nya).

### Gagal yang dikenal (bukan regresi)
| Penyebab | Estimasi | Catatan |
|----------|----------|---------|
| DB `hris_payroll` belum migrate | ~250 | Gagal paling banyak. Abaikan — bukan sinyal regresi. |
| `companies.npwp` NOT NULL (factory) | ~50 | Fix: tambah npwp ke Company factory. |
| Import namespace salah (AttendanceRiskScorerTest, GeofenceServiceTest) | ~63 | Fix: update 2 test file. |
| `ComponentNotFoundException` | ~20 | Path Livewire mismatch. |
| Lainnya (column/model mismatch) | ~100 | Investigasi per-case. |

**Aturan:** Kalau tugas nyentuh PayrollCalculator, PayslipPdfService, atau test di bucket "Fixable" — test itu HARUS pass. Gagal DB-state bukan regresi.

### E2E Playwright
7 role project (employee, HR, manager, finance, admin, audit, PWA, UX, profile, auth). Login sekali per role via `auth.setup.ts` → `.auth/*.json`.

---

## Laravel Boost

`CLAUDE.md` **auto-generated** oleh Boost (`boost.json`). Jangan diedit manual. 7 skills: `ai-sdk-development`, `fortify-development`, `laravel-best-practices`, `livewire-development`, `pest-testing`, `echo-development`, `tailwindcss-development`.

---

## Sebelum Nulis Kode

1. Baca **PRD.md** — pahami WHAT sebelum HOW.
2. Baca **CONVENTIONS.md** — ikuti pola yang ada.
3. Cek **AUDIT + PROGRESS** — jangan re-fix known issues.
4. Cari **1-2 contoh existing** dengan pola yang sama. Referensi eksplisit.
5. Kalau **tidak ada** pola existing → **tanya Fikih** — jangan improvisasi.
6. **Prioritas:** CONVENTIONS.md > PRD.md > guidance Laravel generik.

### Definition of Done

Task selesai KALAU:
1. Semua caller method/field yang diubah udah diupdate (grep dulu — jangan tinggal reference usang).
2. Test terkait pass (skip DB-state failures yang known-red).
3. `vendor/bin/pint --dirty --format agent` pass.
4. Diff summary disertakan: file apa berubah + kenapa.
