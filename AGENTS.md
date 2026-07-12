# AGENTS.md — HRConnect Multi-Role Agent Orchestration

**Project:** HRConnect HRIS (Enterprise HR Management System)  
**Stack:** Laravel 13 + Livewire 4 + PostgreSQL + Tailwind v4 + Material Design 3  
**Strategy:** E2E-first development with autonomous role-based agents

---

## Role Definitions

### 1. FE Agent (Frontend Specialist)
**File:** `.agents/FE-AGENT.md`  
**Trigger:** Livewire components, Tailwind styling, Alpine.js, face-api.js, browser APIs  
**Skills:** `livewire-development`, `tailwindcss-development`, `pest-testing`

**Responsibilities:**
- Build Livewire 4 components (PasPapan pattern)
- Apply Material Design 3 tokens via Tailwind CSS v4
- Integrate face-api.js for client-side face recognition
- Implement browser APIs (Geolocation, Camera, Notifications)
- Write Playwright E2E tests for UI flows
- PWA optimizations

**Constraints:**
- ❌ Do NOT modify database schemas
- ❌ Do NOT create API endpoints
- ❌ Do NOT change authentication logic

---

### 2. BE Agent (Backend Specialist)
**File:** `.agents/BE-AGENT.md`  
**Trigger:** Models, migrations, API endpoints, services, business logic, database queries  
**Skills:** `laravel-best-practices`, `fortify-development`, `ai-sdk-development`, `pest-testing`

**Responsibilities:**
- Create database migrations & models
- Build RESTful API endpoints (Sanctum auth)
- Implement service layer (PayrollService, FaceRecognitionService, RAGService)
- Database optimization (indexes, pgvector queries)
- Job queuing & async processing
- Write Pest feature & unit tests

**Constraints:**
- ❌ Do NOT modify Livewire components
- ❌ Do NOT skip database validation
- ❌ Do NOT hardcode secrets
- ✅ ALWAYS use transactions for critical operations

---

### 3. QA Agent (Quality Assurance Specialist)
**File:** `.agents/QA-AGENT.md`  
**Trigger:** Testing, test coverage, E2E validation, bug verification, regression tests  
**Skills:** `pest-testing`

**Responsibilities:**
- Write & maintain Playwright E2E tests
- Create Pest feature & unit tests
- Bug reproduction & verification
- Test coverage analysis
- Smoke & regression testing
- CI/CD quality gates

**Constraints:**
- ❌ Do NOT modify production code without approval
- ❌ Do NOT skip test documentation
- ✅ ALWAYS verify bug fixes with reproducible tests

---

## Task Assignment Logic

Tasks are automatically routed to appropriate agents based on type:

| Task Type | Assigned Agent | Example |
|-----------|---------------|---------|
| `frontend` | FE Agent | Fix face-api.js import, Build clock-in UI |
| `backend` | BE Agent | Add missing Loan trait, Fix Pph21 calculation |
| `e2e` | QA Agent | Test face enrollment flow, Test clock-in GPS |
| `unit` | QA Agent | Run full test suite |

---

## Collaboration Workflow

### Scenario 1: New Feature Development
1. **BE Agent** creates API endpoint + service logic
2. **BE Agent** writes feature tests (Pest)
3. **FE Agent** builds Livewire component consuming API
4. **FE Agent** styles with Tailwind + MD3
5. **QA Agent** writes E2E test (Playwright)
6. **QA Agent** validates full flow (happy path + errors)

### Scenario 2: Bug Fix
1. **QA Agent** reproduces bug with failing test
2. **BE/FE Agent** fixes the issue
3. **QA Agent** verifies test passes
4. **QA Agent** runs regression suite

### Scenario 3: Refactoring
1. **QA Agent** ensures full test coverage exists
2. **BE/FE Agent** refactors code
3. **QA Agent** verifies all tests still pass

---

## Communication Protocol

### FE ↔ BE Contract
- **API contracts documented** in Postman/OpenAPI
- **Frontend validates** user input before API call
- **Backend validates** all inputs (never trust client)
- **Response format standardized:**
  ```json
  {
    "data": { ... },
    "message": "Success",
    "errors": []
  }
  ```

### BE ↔ QA Contract
- **Feature tests cover** all API endpoints
- **Service tests validate** business logic
- **Factories provide** test data
- **Database seeded** with roles/permissions per test

### FE ↔ QA Contract
- **E2E tests cover** critical user flows
- **No console errors** in browser tests
- **Responsive testing** on mobile viewports
- **Accessibility checks** (basic WCAG validation)

---

## Tech Stack by Role

### FE Agent Stack
- Livewire 4 (reactive components)
- Tailwind CSS v4 (utility-first)
- Alpine.js (interactivity)
- face-api.js (face detection)
- Playwright (E2E tests)
- Material Symbols (icons)
- Rubik + Inter (fonts)

### BE Agent Stack
- Laravel 13 (framework)
- PHP 8.5 (runtime)
- PostgreSQL 15+ (database)
- pgvector (vector similarity)
- CipherSweet (encryption)
- Sanctum (API auth)
- Spatie Permission (RBAC)
- Pest PHP (testing)
- Queue (async jobs)

### QA Agent Stack
- Playwright (E2E browser tests)
- Pest PHP (feature + unit tests)
- Laravel Dusk (browser automation)
- PHPUnit (test runner)

---

## Critical Coordination Points

### Database Changes
**Owner:** BE Agent  
**Coordination Required:**
- Notify FE Agent if API response structure changes
- Notify QA Agent if new test data needed
- Run migrations in local/staging before production

### Face Recognition Feature
**Collaboration:**
- **FE Agent:** face-api.js integration, camera UI
- **BE Agent:** pgvector storage, distance calculation
- **QA Agent:** E2E test (enrollment + verification)

### GPS Geofencing Feature
**Collaboration:**
- **FE Agent:** Browser Geolocation API, map UI
- **BE Agent:** Haversine formula, geofence validation
- **QA Agent:** E2E test (in/out of fence scenarios)

### RAG Knowledge Base
**Collaboration:**
- **FE Agent:** PDF upload UI, chat interface (SSE streaming)
- **BE Agent:** PDF chunking, Gemini embedding, pgvector search
- **QA Agent:** E2E test (upload + query + response)

---

## Autonomous Development Loop

Each 6-hour cycle:
1. **Orchestrator** picks next task from queue
2. **Route to Agent** based on task type
3. **Agent executes** task with appropriate skills
4. **Run tests** (unit/feature/E2E depending on agent)
5. **Auto-commit** if tests pass
6. **Log results** and move to next task

---

## Quality Gates

Before any commit:
- ✅ Relevant tests pass (Pest + Playwright)
- ✅ No lint errors (`vendor/bin/pint --test`)
- ✅ Build succeeds (`npm run build`)
- ✅ No console errors (E2E tests)
- ✅ Database validation (BE Agent only)

---

## Demo-Critical Features (Priority)

| Priority | Feature | FE | BE | QA |
|----------|---------|----|----|-----|
| P0 | Face Recognition | ✅ | ✅ | ✅ |
| P0 | GPS Geofencing | ✅ | ✅ | ✅ |
| P0 | RAG Knowledge Base | ✅ | ✅ | ✅ |
| P1 | Payroll Engine | ⏳ | ✅ | ⏳ |
| P1 | Approval Workflow | ⏳ | ✅ | ⏳ |

---

## Expected Timeline

**Week 1 (Tasks 1-5):** Face enrollment + Clock-in working (all agents)  
**Week 2 (Tasks 6-7):** RAG chat functional (all agents)  
**Week 3 (Tasks 8-10):** Backend stability + full test coverage (BE + QA)

**Demo Ready:** ~3 weeks from now

---

## Contact Points

- **FE Issues:** Check `.agents/FE-AGENT.md`
- **BE Issues:** Check `.agents/BE-AGENT.md`
- **QA Issues:** Check `.agents/QA-AGENT.md`
- **Skills:** Check `.agents/skills/` directory
- **Task Queue:** Check `.hermes/task-queue.json`
- **Logs:** Check `.hermes/cron-logs/YYYYMMDD.log`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `ai-sdk-development` — TRIGGER when working with ai-sdk which is Laravel official first-party AI SDK. Activate when building, editing AI agents, chatbots, text generation, image generation, audio/TTS, transcription/STT, embeddings, RAG, vector stores, reranking, structured output, streaming, conversation memory, tools, queueing, broadcasting, and provider failover across OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama, ElevenLabs, Cohere, Jina, and VoyageAI. Invoke when the user references ai-sdk, the `Laravel\Ai\` namespace, or this project's AI features — not for other AI packages used directly.
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
