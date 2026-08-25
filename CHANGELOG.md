# Changelog

Running log of what's been built, in plain language. Newest first.

## 2026-08-25 (3)

- Built a weekly timetable (`PeriodController`) — the app previously had no concept of *when* a
  teacher teaches, only what they teach. One slot per teacher/subject/class assignment, with
  clash checking so the same teacher, class, or room can't be double-booked on the same day.
- Turned the librarian role from generic inventory into a real library (`BookLoanController`) —
  borrow/return per copy, due dates, a flat per-day overdue fine. Reuses the existing inventory
  transaction ledger to keep the shelf count in sync instead of a second stock path.
- Built a student directory + profile page (`StudentController`) so staff can actually look a
  student up by name/admission number, instead of every module having its own picker. The same
  page lets a librarian/bursar/nurse/teacher flag a student from their own perspective (library
  defaulter, fee defaulter, medical alert, academic concern), and shows a student's composite
  score per subject per term across every term on record — "track one child's performance over
  the years" — to admin, the assigned teacher, the child's own parent, or the learner themselves.
- Rewrote the nav menu as a real off-canvas drawer with a blurred backdrop and slide transition —
  the old one was an in-flow block that shoved all page content down when opened, which read as
  dated and, per feedback, affected the whole screen.
- 13 new tests (`PeriodTest`, `BookLoanTest`, `StudentDirectoryTest`). 165/165 passing.

## 2026-08-25 (2)

- Added PDF generation (`barryvdh/laravel-dompdf`) — the third piece of the global-parity audit's
  highest-leverage items: report cards, payslips, and payment receipts were all webpages-only
  before this, meaning nobody could hand someone an actual document. Report cards
  (`ReportCardController`) reuse `GradingService::compositeScore`, the same MOT/EOT/AoI weighting
  the assessment screens already compute, scoped to admin/the assigned teacher/the child's own
  parent/the learner themselves. Payslips (added to `PayrollRunController`) are downloadable by
  hr/admin or the staff member the payslip belongs to. Receipts (added to `InvoiceController`)
  are downloadable by bursar/admin or the guardian who made the payment, for completed payments
  only. Download links added to the learner assessments page, parent dashboard, payroll run show
  page, and both the bursar's and guardian's payment views.
- 10 new tests (`ReportCardTest` plus new cases in `PayrollRunTest`/`InvoiceManagementTest`).

## 2026-08-25

- Ran a global-parity audit against real-world school-management platforms (PowerSchool, Fedena,
  openSIS) per explicit feedback that the system "doesn't compare to global systems yet" —
  written up in `docs/GLOBAL_PARITY_AUDIT.md` with a per-role gap list rather than leaving it as
  a feeling. One correction along the way: a learner already had full result-history access
  (`/my/assessments`), contrary to the "same as the parent" framing — the actual asymmetry runs
  the other way (parent dashboard is summary-only, learner has full history).
- Built the highest-leverage gap from that audit: document management, which didn't exist as a
  general concept before this (only three hardcoded upload paths: student photo, staff photo,
  WOW-moment photo). Landed as three pieces: teaching-resource uploads (`ResourceController`,
  built on a pre-existing but never-wired `Resource` model — teacher uploads scoped to their
  actual teaching assignment, readable by the students/parents of that class), medical/staff
  document attachments (`DocumentController`, one new polymorphic `Document` model instead of a
  bespoke path per attachment target — a nurse can now attach a dosage sheet to a student's
  health record, hr can attach a staff contract, each readable by the person it's about), and
  bulk student-list CSV import/export (`StudentCsvController`) so an admin doesn't have to add
  students one at a time via the user-management screen.
- Finished Phase 4 (gate passes) — was migration + model only before this, now a full
  request→approve/reject→depart→return workflow (`GatePassController`), scoped so a guardian
  only sees their own children's passes, with a notification to the requester on the decision.
- 11 new tests across `ResourceTest`, `DocumentTest`, `StudentCsvTest`, `GatePassTest`. 144/144
  passing.

## 2026-08-24 (2)

- Brought every module up to full CRUD depth (Phase 3) — before this, everything except
  `ProfileController` topped out at list/create with no way to fix a mistake or remove a bad
  entry. Added edit/update/destroy, each gated by the specific rule that module needed:
  Assessments get edit-only (no destroy — an audit trail matters for grades); Notices, Incident
  reports, Medications, and Clinic visits are editable by their own author/recorder (or admin),
  with destroy admin-only on the medical ones; Payroll runs lock once approved/paid, editable
  only while still a draft; Inventory items get a new `show` page (detail + transaction history)
  and destroy is blocked if the item has any transaction history; Inventory transactions get a
  `void` action that reverses the quantity effect and soft-voids the row instead of deleting it,
  so the ledger stays intact; the nursery trio (daily activity logs, milestones, WOW moments) get
  edit/destroy restricted to the same day's own entries (admin can touch any day).
- The nursery trio had **zero test coverage at all** before this pass — added
  `DailyActivityLogTest`, `MilestoneChecklistTest`, `WowMomentTest`, plus a new
  `InventoryItemTest` (item CRUD had never been tested, only the transaction ledger had).
- Caught two real `Route::resource` parameter-naming bugs while wiring this up
  (`ClinicVisitController`, `InventoryItemController`) — Laravel derives the bound route
  parameter from the kebab-case resource name, not the model class, so both needed
  `$clinic_visit`/`$inventory_item`, not the camelCase names they were first written with.
- Caught one real pre-existing bug via the new daily-activity-log test:
  `DailyActivityLogController` crashed with `Undefined array key "sleep_checks"` whenever that
  optional field was left out of the request entirely — nullable validation doesn't add missing
  keys, so the code needed an `empty()` check before reading it, not just a `nullable` rule.
- 129/129 tests passing.

## 2026-08-24

- Built the real notification system, pulled forward out of plan order per explicit feedback
  ("notifications should be clearable") — the nav bell was a lightweight recency-badge
  placeholder before this, not an actual notification center. Added the `notifications` table,
  extended `ClinicVisitLogged`/`MedicationAdministered` with a database channel (and removed
  `ShouldQueue` from both — that would defer the in-app row to a queue worker nothing guarantees
  is running), and added three new notifications wired into real triggers:
  `PaymentReceived` (guardian, from both the bursar's manual payment and the DGateway webhook —
  the Financial Center had no payment confirmation at all before this), `IncidentStatusUpdated`
  (the reporter, correctly skipped for anonymous reports), and `NoticePublished` (every
  guardian/learner at the school, database-only — mailing everyone on every routine notice would
  be noisy). The nav bell is now a real dropdown: unread badge, last 10 notifications, and
  "clearable" taken literally — a per-item delete button, plus mark-all-read/clear-all.
- Did a full UI polish pass per feedback that the UI was "sub-standard" — migrated all 8 role
  dashboards and every module list view from raw `bg-white` divs to `<x-card>`, added a new
  `<x-empty-state>` component (icon + message) replacing plain gray "No X yet" text everywhere,
  and added colored status badges where things were previously plain text (payroll/invoice
  status, low-stock warnings).
- Added `DashboardTest` (one request per role) — the original audit flagged zero coverage on
  `/dashboard`, and every dashboard view had just been touched by the polish pass. Caught a real
  bug immediately: PHPUnit 11 dropped docblock `@dataProvider` in favor of the `#[DataProvider]`
  attribute — the docblock form parses without error but silently runs nothing.
- 99/99 tests passing.

## 2026-08-23 (7)

- Rewrote the nav a second time: the `xl:` breakpoint fix from earlier today still left a gap
  where neither the flat row nor the hamburger showed cleanly, and a 9+ item single row was
  never going to be "clear and easy to follow" regardless of where it broke. Nav is now always
  the hamburger/off-canvas menu at every width — header is just logo, a noticeboard bell (badge
  = notices from the last 3 days, not true per-user read tracking), the user menu, and the
  toggle. Removed the now-unused `<x-nav-dropdown>` component from the earlier attempt.
- Modernized the shared card/button components — they were still the stock Breeze defaults
  (`bg-gray-800` primary buttons, not even the app's own indigo accent; uppercase tiny
  tracked-out text; hard `rounded-md` borders). Cards get a softer `rounded-xl` +
  `ring-1 ring-gray-950/5`; buttons get `rounded-lg`, normal-case text, a `hover:shadow` lift,
  and a real disabled state on all three (previously only secondary had one).
- Enriched `DemoDataSeeder` for real testing: 8 students with a genuine weak/average/strong
  spread trending upward Term 1 → Term 2 (deterministic formulas, not `rand()`, so re-seeding
  stays stable), 15 days of attendance with a realistic absent/late mix, 8 clinic visits and 3
  medication administrations spread over 2 months with varied reasons/outcomes.
- Added trend reports: `ReportController::academics()` (average score by subject per term, plus
  a students-below-60% list) and `::health()` (clinic visits by reason/outcome, medications
  administered, last 90 days) — simple CSS-bar visualizations, no chart library.
- 85/85 tests passing.

## 2026-08-23 (6)

- Fixed real nav overflow: the desktop/mobile breakpoint switched at 640px, too early for the
  12 items admin can now see. Raised to 1280px and grouped Academics/Health into dropdown menus
  instead of just widening — a flat 9+ item row wasn't "clear and easy to follow" either way.
- Trimmed the landing page's role grid to Admin/Teacher/Parent/Learner — Nurse/HR/Bursar/
  Librarian read as an internal staff directory on a public page, not a product pitch.
- Schools can now declare which curriculum levels they actually run (`School.settings.levels`,
  no migration needed — reused the existing JSON column) via a new admin-only School Settings
  page, gating level-specific nav (Nursery, so far) for schools that don't offer it.
- Added photo uploads for students and staff: admin can upload for anyone at their school,
  parents only for their own children. Staff photos stay admin-only. Known gap: uploads go to
  the local disk, which won't survive a Laravel Cloud redeploy — needs S3 before this is
  production-real, same category of "needs real credentials" gap as DGateway/NIRA/OTP.
- Implemented the logo: the "Open Book, Networked" concept from the earlier design pass now
  replaces the default Breeze mark everywhere, plus a matching favicon.
- 82/82 tests passing.

## 2026-08-23 (5)

- Phase 2 of the hardening/depth pass (`docs/HARDENING_TODO.md`): bursar and learner both had
  zero routes of their own before this — dashboard-only. `InvoiceController` gives bursar real
  invoice creation and manual cash/bank payment recording (completes immediately, unlike the
  guardian DGateway checkout which starts pending); `LearnerController` gives learners their full
  assessment/attendance/notice history instead of the dashboard's five-item summaries. Caught one
  real bug along the way: `InvoiceController::show`/`recordPayment` 500'd instead of returning 403
  for a cross-school invoice, because `Student`'s own school-scope silently nulls out the
  relation rather than throwing — fixed with a nullsafe check.
- Cleaned up now-stale Render references across `CHANGELOG.md`/`docs/DECISIONS.md` — the older
  entries about setting up and fixing the Render staging deploy are trimmed to what's still
  relevant now that it's retired, rather than left as clutter about infrastructure that no longer
  exists.

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

- Fixed a mixed-content bug on the (since-retired, see the entry above) staging preview — a PaaS
  host terminating TLS at its edge and forwarding plain HTTP confused Laravel into generating
  `http://` asset links on an `https://` page. Fix (`trustProxies(at: '*')` in `bootstrap/app.php`
  + an explicit `https://` `APP_URL`) is host-agnostic and still applies to Laravel Cloud.
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
  `phpunit.xml` for tests) doesn't enforce the declared precision and hands back whatever numeric
  type it stored. Added `decimal:2` casts so
  the value is consistent regardless of driver, then audited every other undecorated `decimal()`
  column for the same gap: `Payment.amount`, `Invoice.amount_due`, and all four `Payslip` money
  columns (`gross_pay`, `paye`, `nssf`, `net_pay`) had the identical latent bug, just not yet
  caught by a test. Full suite now 48/48.

## 2026-08-21

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
