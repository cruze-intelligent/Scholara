# Architecture

## Shape

Scholara is a single Laravel monolith (Blade + Livewire/Alpine for interactivity) backed by
MySQL, deployed as a subdomain on shared/cPanel hosting
(`scholara.cruzeintelligentsystems.com`). This trades the plan's original
Node/FastAPI + React/Next.js split for a stack that runs entirely on standard PHP hosting with
no Node process required in production — chosen because dedicated backend infrastructure
(a Node/Python app server, managed Postgres, etc.) isn't available yet.

A single Laravel app can still expose a JSON API (`routes/api.php`) alongside the Blade UI, so a
future Flutter/React Native mobile app or an offline-sync edge client can talk to the same
backend without a rewrite.

## Roles

Implemented via `spatie/laravel-permission` on top of Laravel's default `users` table, with a
`profile` polymorphic/role-specific table per role (a Teacher has TRN + subjects, a Parent has
NIN + linked students, etc.):

- **Learner** — read-mostly: own AoI/assessment scores, noticeboard, resources, issue reporting.
- **Parent/Guardian** — one account, many linked Learners (via `student_guardian` pivot):
  academic view, financial center, safety/attendance alerts, health tracker, messaging.
- **Teacher** — marksheet entry, lesson planning, attendance, notices, health-alert logging.
  Identified by a 7-digit TRN.
- **Nurse** — EHR: medical history, eMAR, clinic visit logs, emergency protocols.
- **HR/Payroll** — staff profiles, payroll periods, PAYE/NSSF deductions.
- **Bursar/Accountant** — invoices, fee payments, SchoolPay/mobile money reconciliation.
- **Librarian/Store** — inventory: library books, canteen/equipment stock.
- **Admin** — overall access: everything above, plus disciplinary/grievance oversight and
  strategic analytics.

A `School` model scopes every role and every record (multi-tenant from day one, even though
phase 1 targets a single school), since the plan's pricing models (per-student/term, enterprise
license, NGO grant) all imply eventually hosting more than one institution.

## Module map (matches the plan's stakeholder sections)

| Module | Core tables | Status |
|---|---|---|
| Auth & roles | `users`, `roles`, `permissions`, `schools` | built |
| Student/guardian records | `students`, `guardians`, `student_guardian`, `school_classes` | scaffolded |
| Academics (AoI/MOT/EOT) | `subjects`, `assessments`, `assessment_scores`, `teacher_subject_assignments` | built |
| Noticeboard | `notices` | built |
| Issue reporting (RTRR/VAC) | `incident_reports` | built |
| Learning resources | `resources` | scaffolded |
| Attendance | `attendance_records` | built (incl. gender-based stats) |
| Lesson planning | `lesson_plans` | scaffolded |
| Nursery daily logs | `daily_activity_logs`, `milestone_checklists`, `wow_moments` | built |
| Health/EHR/eMAR | `health_records`, `medication_administrations`, `clinic_visits` | built |
| HR/Payroll | `staff_profiles`, `payroll_runs`, `payslips` | built |
| Inventory/store | `inventory_items`, `inventory_transactions` | built |
| Financial center | `invoices`, `payments` | scaffolded |
| Predictive analytics | `assessment_scores` (derived) | built (wired into teacher/parent/learner dashboards) |
| Gate pass / safety | `gate_passes` | scaffolded |
| Audit trail | `audit_logs` | built (write-side only — see docs/DECISIONS.md) |
| 2FA / NIRA / SchoolPay | — | **interfaces/stubs only**, see below |
| Offline-first sync | — | **not built**, see below |

"Scaffolded" = migrations, models, relationships, and a minimal read-only Blade screen exist per
role, with no create/edit workflow yet. "Built" = a real CRUD workflow exists (create, view, and
the relevant role-scoped authorization) — see `docs/DECISIONS.md` for the product-decision
defaults (grading weights, PAYE/NSSF rates, etc.) adopted where the plan didn't specify a
formula.

## Deliberately stubbed, not built

These need external accounts/credentials or hardware the project doesn't have yet. Each has an
interface so the rest of the app can be built against it without blocking:

- **NIRA TPI (NIN verification)** — `App\Services\Identity\NiraVerifier` interface with a fake
  implementation that always "verifies." Swap in a real HTTP client once NIRA TPI credentials
  exist.
- **SMS/USSD OTP (2FA)** — `App\Services\Notifications\OtpSender` interface, currently logs the
  OTP instead of sending SMS. Swap in an SMS gateway (e.g. Africa's Talking) later.
- **SchoolPay / mobile money** — `App\Services\Payments\PaymentGateway` interface with a fake
  "always succeeds" implementation. Real integration needs a SchoolPay/MTN/Airtel merchant
  account.
- **Offline-first "School-in-a-Box"** — the plan's Raspberry Pi + SQLite/IndexedDB delta-sync
  layer is a separate edge deployment, not part of this Laravel app. Out of scope until the core
  system is stable; when built, it syncs against the `api.php` routes with a `synced_at` /
  `dirty` column pattern already present on syncable tables.

## Predictive analytics

`App\Services\Analytics\PerformancePredictor` implements the plan's weighted moving average:

```
P_pred = Σ (1-λ)^(i-1) · x_i  /  Σ (1-λ)^(i-1)     for i = 1..n, i=1 most recent
```

Run per student per subject over their `assessment_scores`, compared against the class mean.
When `P_pred` drops meaningfully below the student's own baseline or the class mean, it raises a
`support_strategy_alert` visible to the teacher and the linked parent(s).

Wired into `DashboardController`: the teacher dashboard lists Support Strategy alerts across all
of a teacher's subject/class assignments; the parent and learner dashboards show each subject's
predicted trend for their own child/themselves.
