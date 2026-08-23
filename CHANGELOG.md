# Changelog

Running log of what's been built, in plain language. Newest first.

## 2026-08-23 (3)

- Moved the public staging preview from Render to [Laravel Cloud](https://cloud.laravel.com) —
  real persistent MySQL instead of SQLite-in-a-container, no Dockerfile to maintain, and it's the
  platform the user was already trying to use. Removed `Dockerfile`, `docker/entrypoint.sh`,
  `render.yaml`, `.dockerignore`; rewrote `docs/STAGING.md` around Laravel Cloud.
- The Laravel Cloud environment existed but was never actually configured: no database attached
  (every DB-touching request 500'd), and after attaching one, `DB_CONNECTION` was still unset so
  the app kept trying to open a SQLite file that didn't exist (`Laravel Cloud auto-injects
  DB_HOST`/`DB_USERNAME`/etc. when you attach a database, but not `DB_CONNECTION` itself — an easy
  trap). Fixed by installing the Laravel Cloud CLI (`laravel/cloud-cli`, needs PHP >=8.4 — this
  machine only had 8.3, so a standalone PHP 8.4.24 was set up alongside it with `zip` and
  `sockets` extensions enabled, both required by the CLI and its browser-based OAuth login) and
  driving the rest from the terminal: set `DB_CONNECTION=mysql`, `SEED_DEMO_DATA=true`, `APP_URL`,
  and added `php artisan db:seed --force` to the deploy command. Verified live via `cloud tinker`
  querying the real production database directly (`User::count()` → 8, matching the demo seed).
- Live at https://scholara-production-nujkni.laravel.cloud — 8 demo accounts (one per role,
  `<role>@scholara.test` / `password`), documented in `docs/STAGING.md`.

## 2026-08-23 (2)

- Built the Financial Center's actual payment collection — previously `PaymentGateway` was a
  dead interface nothing called. Chose [DGateway](https://dgateway.desispay.com) (mobile money +
  card behind one API) over integrating SchoolPay/MTN/Airtel/Stripe separately — see
  `docs/DECISIONS.md`. No DGateway account exists yet, so it's wired end-to-end but bound behind
  `FakePaymentGateway` (`AppServiceProvider` auto-switches to the real `DGatewayPaymentGateway`
  the moment `DGATEWAY_API_KEY` is set — no code change needed later).
- Redesigned `PaymentGateway` from a synchronous `charge()` that returns success immediately to
  an async `collect()`/`checkStatus()` pair, matching how real gateways actually work (charge
  returns "pending", final result arrives via webhook or polling). Added `status`/`provider`/
  `currency` columns to `payments` and made `paid_at` nullable to support that.
  `Invoice::syncPaymentStatus()` recomputes unpaid/partially_paid/paid off completed payments.
- New guardian-facing checkout: pick mobile money or card on `invoices/{invoice}/pay`, then a
  status page that polls every 5s until the payment resolves. Added a "Pay" link next to unpaid
  invoices on the parent dashboard, which previously only listed them read-only.
- New `POST /webhooks/dgateway` — verifies `X-DGateway-Signature` (HMAC-SHA256, constant-time
  compare) before touching the body, rejects bad/missing signatures with 401, and is idempotent
  (skips already-resolved payments, since DGateway retries deliveries 2-3 times). Excluded from
  Laravel's CSRF check in `bootstrap/app.php` since DGateway can't send a session token.
  Subscription webhook events are ignored on purpose — Scholara charges per-term invoices, not
  recurring subscriptions, so only the one-time collect flow is implemented.
- Added `tests/Feature/InvoicePaymentTest.php`: full happy path (guardian pays → webhook
  confirms → invoice marked paid) against a faked DGateway HTTP response, plus signature
  rejection and cross-guardian authorization checks.
- Known gap: the "Card" checkout option calls DGateway but doesn't yet load Stripe.js to confirm
  the `client_secret` a real card charge would return — untestable without a live account, so
  mobile money is the only complete path today.

## 2026-08-23

- The Render staging link went live but rendered unstyled — browser console showed "Mixed
  Content" errors blocking `http://` asset URLs on the `https://` page. Cause: Render terminates
  TLS at its edge and forwards plain HTTP to the container, and Laravel wasn't told to trust that
  proxy, so it generated `http://` links even though the page loaded over `https://`. Fixed with
  `trustProxies(at: '*')` in `bootstrap/app.php` and an explicit `https://` `APP_URL` in
  `render.yaml`. Documented the symptom/fix in `docs/STAGING.md` for next time.
- Decided to keep Phase 3's NIRA/OTP/SchoolPay integrations as placeholder fakes for now while
  real API credentials are sourced — no code change, just confirming the existing stub approach
  from Phase 0 stands until then.

## 2026-08-22

- Verified the Aug 21 Phase 1/2 work end-to-end for the first time, as flagged as outstanding
  below — it turned out nothing had actually run against a live database on this machine yet;
  `php artisan migrate` had never succeeded here.
- Root cause of the earlier `artisan` hang and the `intl` extension warning: this machine was
  missing the Microsoft Visual C++ Redistributable (x64) (`msvcp140.dll` specifically). That
  silently broke two unrelated things at once — PHP's `intl` extension failed to load, and
  `mysqld.exe` couldn't start at all (`STATUS_DLL_NOT_FOUND`). User installed the redistributable;
  both cleared up immediately.
- Found and fixed a second, independent MySQL problem underneath that: Laragon's `my.ini` (which
  it regenerates from `usr/laragon.ini` on every start, so it's not hand-editable) pointed
  `datadir` at `C:/laragon/data/mysql-8.4`, an empty/never-initialized folder, while the real
  seeded data from Aug 20 sat in a differently-named `data/mysql` folder nothing pointed to
  anymore. Initialized a fresh MySQL system schema in the directory Laragon actually expects
  (`mysqld --initialize-insecure`) rather than hand-relocating a live InnoDB datadir — the old
  data was disposable demo/seed data, not worth the corruption risk of moving it live. Also had to
  disable MySQL's auto-generated SSL/RSA certs (`--auto_generate_certs=OFF`); with them on,
  `--initialize` hung for minutes generating keys against this machine's slow entropy source.
- Created the `scholara` MySQL database and app user matching `.env`, then ran `migrate`,
  `db:seed`, and the full test suite for real: 47/48 passed, one genuine bug —
  `AssessmentTest` expected `raw_score` back as the string `"25.00"` but got the integer `25`.
  Cause: `AssessmentScore` had no cast on its `decimal(6,2)` columns, so the value round-trips
  differently depending on DB driver — MySQL returns decimal columns as strings, SQLite (used by
  `phpunit.xml` for tests, and by the Render staging deploy in production) doesn't enforce the
  declared precision and hands back whatever numeric type it stored. Added `decimal:2` casts so
  the value is consistent regardless of driver, then audited every other undecorated `decimal()`
  column for the same gap: `Payment.amount`, `Invoice.amount_due`, and all four `Payslip` money
  columns (`gross_pay`, `paye`, `nssf`, `net_pay`) had the identical latent bug, just not yet
  caught by a test. Full suite now 48/48.

## 2026-08-21

- Fixed the Render staging build (`libsqlite3-dev` was missing for `pdo_sqlite`'s headers) —
  committed; still needs a `git push` from a normal terminal since this sandboxed session can't
  complete GitHub's interactive credential prompt.
- Built out Phase 1 and Phase 2 from `docs/ROADMAP.md` in one pass, on top of the Phase 0
  read-only scaffold:
  - **Academics**: real marksheet entry (`AssessmentController`/`AssessmentScoreController`) with
    MOT/EOT/AoI weighting and raw→scaled auto-scaling (`GradingService`), noticeboard
    (draft/publish), issue reporting with a status/assignment workflow, attendance-taking with
    gender-based stats, and `PerformancePredictor` wired into the teacher (Support Strategy
    alerts) and parent/learner (predicted trend) dashboards — it existed since Phase 0 but was
    never called from anywhere.
  - **Health**: nurse portal — health record editing, medication administration with a real
    five-discrete-check Five Rights UI (replacing the old single boolean), clinic visit logging,
    and mail notifications to guardians on both events.
  - **HR/Payroll**: `PayeCalculator`/`NssfCalculator` (Uganda URA bands / 5% NSSF — see
    `docs/DECISIONS.md`), payroll run creation and payslip generation from each staff member's
    new `monthly_gross_salary` field.
  - **Inventory**: stock in/out transactions that actually keep `InventoryItem.quantity` in sync
    (via an observer — previously nothing did this).
  - **Nursery**: daily activity logs, milestone checklists (static catalog), WOW moments with
    photo upload.
  - **Foundational**: registered Spatie's `role` middleware (was installed but never wired into
    `bootstrap/app.php`), a `BelongsToSchool` trait replacing repeated ad hoc school-scoping, a
    write-side `Auditable` trait + `audit_logs` table for health/financial models per
    `docs/COMPLIANCE.md`, and a `teacher_subject_assignments` table (the schema previously had no
    way to express "this teacher teaches this subject in this class" beyond one homeroom
    teacher per class).
  - Extended `DemoDataSeeder` with subjects, assessments/scores, attendance, a notice, and
    inventory items so the new screens have real data on first login, and added a representative
    Feature/Unit test suite for the new controllers and calculators.
  - Documented every formula/rate that had no source in the plan's docs (grading weights,
    PAYE/NSSF, Five Rights, gender-stat metric) as explicit, swappable defaults — see
    `docs/DECISIONS.md`.
  - **Not verified end-to-end this session**: local PHP CLI execution hit environment issues in
    this sandboxed shell (missing VC++ runtime, then `artisan` hanging even after working around
    it) — unlike Phase 0, this work has not been manually clicked through yet. Run
    `php artisan migrate && php artisan db:seed && php artisan test` from a normal terminal before
    treating this as verified.

## 2026-08-20

- Installed Laragon (PHP 8.3.33, Composer, MySQL 8.4) and enabled OPcache — the dev server was
  taking 5+ seconds per request with it off; ~300ms with it on. See `docs/PERFORMANCE.md`.
- Scaffolded the actual Laravel 13 project (previously just planned) and merged it into this
  repo alongside the docs, without touching `README.md`/`.gitignore` already written here.
- Installed `spatie/laravel-permission` (roles: admin, teacher, parent, learner, nurse, hr,
  bursar, librarian) and Laravel Breeze (Blade + Alpine.js auth — login, register, password
  reset, email verification, profile editing).
- Built all 28 core migrations from `docs/DATA_MODEL.md` (schools, students, guardians, staff,
  classes, subjects, assessments/scores, attendance, lesson plans, notices, incident reports,
  resources, gate passes, nursery daily logs/milestones/WOW moments, health records/eMAR/clinic
  visits, payroll/payslips, inventory, invoices/payments) plus matching Eloquent models with
  relationships.
- Added the stubbed `NiraVerifier`, `OtpSender`, and `PaymentGateway` interfaces (with fake
  implementations bound in `AppServiceProvider`) and the `PerformancePredictor` service
  implementing the plan's weighted-moving-average formula.
- Seeded roles and a demo school with one user per role; built a `DashboardController` plus one
  Blade dashboard per role, each showing real data pulled from the seeded records.
- Verified the whole stack end-to-end: logged in as all 8 demo roles via HTTP and confirmed each
  dashboard renders real seeded data with no errors (caught and fixed a `ViteManifestNotFoundException`
  along the way — `npm run build` hadn't actually run during Breeze's install).
- Not yet committed to git — holding off until asked, per the "only commit when explicitly
  requested" rule.

## 2026-08-19

- Read `School System Development Plan.pdf` in full; distilled it into `docs/ARCHITECTURE.md`,
  `docs/COMPLIANCE.md`, `docs/ROADMAP.md`.
- Decided the stack with the project owner: Laravel (PHP) monolith, Blade + Livewire/Alpine
  frontend, MySQL, deployed to the `scholara.cruzeintelligentsystems.com` subdomain on existing
  cPanel hosting. Documented the deploy steps in `docs/DEPLOYMENT.md` (subdomain not yet
  created on the actual host — steps are ready for when it is).
- Initialized the local git repo, added `origin` → `github.com/cruze-intelligent/Scholara`
  (repo existed but was empty).
- No PHP/Composer/MySQL were present on the dev machine; installing Laragon to get a working
  local Laravel environment.
