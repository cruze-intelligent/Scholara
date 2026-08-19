# Changelog

Running log of what's been built, in plain language. Newest first.

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
