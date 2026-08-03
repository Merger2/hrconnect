# AGENTS.md — HRConnect

**Stack:** Laravel 13 / PHP 8.5+ / PostgreSQL 15+ (pgvector, pg_trgm, pgcrypto) / Livewire 4 / Tailwind v4 / Alpine.js / PWA  
**Dev:** Fikih (solo) — HRIS enterprise: face recognition, GPS geofencing, payroll PPh21, RAG KB

---

## Status & Progress

- **~104 features** · **89 Livewire components** · **92 models** · **72 controllers**
- Progress/status: `docs/PROGRESS.md` + `docs/FEATURE-INVENTORY.md` (di-update per session)
- Audit terbaru: `AUDIT-2026-07-30.md` (root) — jangan re-fix issue yang sudah tercatat
- ⚠️ **`docs/**`, `AUDIT-*.md` (root), `.audit-cache/` — gitignored/untracked.** Isi lokal doang: jangan commit, jangan harap ada di CI/checkout fresh.

---

## Perintah Penting

| Perintah | Fungsi |
|----------|--------|
| `composer run dev` | Server + queue + logs + Vite concurrently |
| `composer run test` | `config:clear` → `lint:check` → `php artisan test` |
| `composer run ci:check` | Alias `composer run test` (script-nya sama persis) |
| `composer run lint` | `pint --parallel` (auto-fix) |
| `composer run lint:check` | `pint --parallel --test` (dry-run) |
| `vendor/bin/pint --dirty --format agent` | **Wajib** setelah tiap perubahan PHP |
| `vendor/bin/pest` | Direct test runner (bypasses config:clear — lebih cepat buat iterasi) |
| `php artisan test --compact --filter=X` | Test spesifik (filter sesuai modul yang diubah) |
| `vendor/bin/phpstan analyse` | PHPStan level 5 (scans `app/`, baseline di `phpstan-baseline.neon`) |
| `npx playwright test --project=chromium-employee` | E2E role employee |
| `npx playwright test` | Full E2E suite (semua project) |
| `npm run test:e2e:headed` / `:debug` / `:report` | Playwright headed / debug / HTML report |
| `npm run build` | Build assets |
| `php artisan db:seed --class=KnowledgeBaseSeeder` | **Wajib** — isi KB entries + 768D embeddings. RAG gak jalan tanpa ini |

---

## Arsitektur

- `routes/web.php` require **6 file**: `web/system.php`, `web/files.php`, `web/user.php`, `web/payroll.php`, `web/admin.php` + `routes/knowledge-base.php` (prefix route `knowledge-base.`). Semua route didefinisikan di file-file ini, bukan inline di `web.php`.
- `app/Livewire/` → komponen. `app/Services/` → business logic. `app/Support/` → helper. `app/Domain/` → domain logic.
- `app/helpers.php` — auto-loaded via `composer.json` `files`.
- `design.md` — locked design system. **Baca sebelum ngerjain UI.**
- `CLAUDE.md` — Laravel Boost guidelines (aktif via MCP `laravel-boost` di `opencode.json`). Untuk hal Laravel generik, ikuti ini.

---

## ⚠️ Critical: Schema FK Pattern

**Jangan tebak FK column. Ini pola tetap:**

| Tabel | Kolom | Cara query |
|:------|:-----:|:-----------|
| `attendances`, `payrolls`, `face_descriptors`, `overtimes`, `reimbursements`, `attendance_corrections`, `employee_document_requests` | `employee_id` | `whereHas('employee', fn ($q) => $q->where('user_id', $user->id))` |
| `schedules`, `cash_advances`, `shift_swap_requests`, `work_from_home_requests` | **`user_id`** | `where('user_id', $user->id)` langsung |

`attendance_corrections` punya KEDUA kolom (`employee_id` + `user_id`).

---

## ⚠️ Critical: Role/Permission BUKAN Spatie Default

**`HasRolePermissions` trait** (`app/Models/Concerns/HasRolePermissions.php`) baca dari `$role->permission_keys` — kolom JSON di tabel `roles`, BUKAN dari Spatie `role_has_permissions` pivot.

Kalo nambah permission ke role, update `permission_keys` JSON column langsung:
```php
$role->permission_keys = ['view_dashboard', 'view_knowledgebase', ...];
$role->save();
```

Permission enum: `App\Enums\Permission` (**94 case**). Auto-register sebagai Gates di `AuthServiceProvider`.

---

## ⚠️ Critical: CSS — JANGAN pake `@apply`

Tailwind v4 JIT tree-shake utility class yang cuma dipanggil di `@apply` dalam CSS (gak kedeteksi di template). **Pake CSS properti langsung:**

```css
/* ✅ BENAR */
.user-bottom-navigation {
  position: fixed;
  inset-inline: 0;
  bottom: 0;
  z-index: 40;
}

/* ❌ SALAH — kena tree-shake */
.user-bottom-navigation {
  @apply fixed inset-x-0 bottom-0 z-40;
}
```

`resources/css/app.css` hybrid HRConnect + PasPapan (~6700 baris). Kalo nambah style, taro di `@layer components` yang udah ada.

---

## ⚠️ Livewire Quirks

- `$wire` di Alpine `init()` → pake **`this.$wire`**, bukan `$wire`
- `config/livewire.php`: `max_components` = 200, `max_size` = 5MB
- `@stack('scripts')` WAJIB ada di `layouts/app.blade.php` — kalo gak, semua `@push('scripts')` gak ke-render (face-enrollment JS dll)
- `ClockInAction` dulu orphaned → skrg di route `/scan`

### ⚠️ Alpine $watch dengan $wire

```javascript
// ❌ SALAH — $wire magic property bukan Alpine data path, bakal crash
this.$watch('$wire.messages', callback);

// ✅ BENAR — pake Livewire $watch method
this.$wire.$watch('messages', callback);
```

### ⚠️ canSend() di Alpine — pake $wire, bukan DOM ref

```javascript
// ❌ SALAH — DOM value gak reactive di Alpine
canSend() { return this.$refs.input.value.length >= 5; }

// ✅ BENAR — $wire.question trigger Alpine re-evaluate
canSend() { return this.$wire?.question?.trim()?.length >= 5; }
```

---

## ⚠️ Fixed Bugs (jangan di-fix ulang)

1. **`CommunityService::registerFace()`** — pake `employee_id` + `embedding`, bukan `user_id` + `descriptor`. Strips 129→128 dimensi.
2. **Face descriptor 129 elements** — quality score di index[0] harus di-`array_slice()` sebelum disimpan. Kolom `face_descriptors.embedding` adalah `vector(128)`.
3. **Fortify config** — butuh `Features::updateProfileInformation()` dan `Features::updatePasswords()` di `config/fortify.php` `features` array.
4. **KB Chat sendMessage** — Alpine crash `$watch('$wire.messages', ...)` → `$wire.$watch('messages', ...)`. `canSend()` DOM ref → `$wire.question`.
5. **Flatpickr date pickers** — JS di-import di `resources/js/app.js` tapi gak pernah di-init. `[data-ui-picker]` elements jadi readonly text. Tambah `initFlatpickr()` + Livewire `morph.updated` hook.
6. **Appraisal `period_year`/`period_month`** — kolom gak ada, ganti pake `period` (single "YYYY-MM").
7. **ShiftSwapRequest `job_title_id`** — kolom gak ada di `users` table, hapus dari SELECT.
8. **UserAssetService `user_id`** — `company_asset_histories` pake `from_employee_id`/`to_employee_id`.
9. **KB Chat route name** — route name adalah `knowledge-base.chat`, BUKAN `kb.chat`.

---

## E2E Playwright

- Auth state pake `storageState` (5 role states): employee, hr, manager, finance, admin — di `tests/e2e/.auth/`
- Employee creds: `employee@hrconnect.test` / `password`
- E2eTestSeeder bikin user: `E2eTestSeeder.php`
- Bottom nav: Beranda, Jadwal, Absen (`/scan`), Tasks, Profil
- Auth setup di `auth.setup.ts`; konfig di `playwright.config.js` (camera + geolocation permissions)
- Project lain: `chromium-hr`, `chromium-manager`, `chromium-finance`, `chromium-admin`, `chromium-audit`, `chromium-pwa`, `chromium-ux`, `chromium-profile`, `chromium-auth` — pilih sesuai role yang disentuh

---

## Env Quirks

- `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`
- `CIPHERSWEET_KEY` (64-char hex) **wajib**. Test: `0123456789abcdef0123456789abcdef`.
- `GEMINI_API_KEY` prefer dari `GOOGLE_AI_API_KEY`; model RAG di `.env.example`: `GEMINI_MODEL=gemini-2.5-flash`, embedding `text-embedding-004` 768D.
- `docs/**`, `.audit-cache/`, `AUDIT-*.md` root, `/.agents/`, `/.claude/` — gitignored.

---

## Testing Quirks

- **`phpunit.xml` hardcode PostgreSQL** (`DB_DATABASE=hris_payroll`, `postgres`/`password`) — `composer run test` butuh pgsql lokal jalan. Tidak ada `.env.testing` lokal.
- `phpunit.pgsql.xml` cuma ada di CI. CI punya 2 job: sqlite + postgres (extension `vector`, `pg_trgm`, `pgcrypto` wajib dibuat).
- `vendor/bin/pest` langsung jalan tanpa `config:clear` prefix (lebih cepet buat iterasi).
- **Feature lock middleware** — pake `middleware('feature.lock:{module},{user|gate:ability},{fallback_route}')` (contoh: `cash_advance`, `assets`, `appraisal`).

---

## Stack Tambahan

| Library | Fungsi |
|---------|--------|
| **Jetstream 5.x** | Profile pages, API tokens, 2FA forms |
| **Laravel Reverb + Echo** | Real-time broadcasting (pusher-js + laravel-echo) |
| **Capacitor 8** | Native mobile app (`capacitor.config.ts`) |
| **spatie/laravel-activitylog** | Activity trail |
| **spatie/laravel-backup** | Automated backups |
| **sentry/sentry-laravel** | Error tracking |
| **laravel/ai** | AI SDK (RAG KB, agents — lihat skill `ai-sdk-development`) |

---

## Sebelum Nulis Kode

1. Baca **CONVENTIONS.md** — ikuti pola yang ada.
2. Cek **AUDIT-2026-07-30.md + docs/PROGRESS.md** — jangan re-fix known issues.
3. Cari **1-2 contoh existing** dengan pola yang sama. Referensi eksplisit.
4. Kalau **tidak ada** pola existing → **tanya Fikih** — jangan improvisasi.
5. **Prioritas:** CONVENTIONS.md > CLAUDE.md (Boost guidelines) > guidance Laravel generik.

### Definition of Done

1. Semua caller method/field yang diubah udah diupdate (grep dulu).
2. `vendor/bin/pint --dirty --format agent` pass.
3. `php artisan test --compact --filter=X` pass (filter sesuai modul yang diubah).
4. `npx playwright test --project=chromium-employee` pass (kalau perubahan nyentuh UI user).

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, use the installed graphify skill or instructions before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
