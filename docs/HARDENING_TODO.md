# Hardening & depth pass — todo

Working list for the "make the system proper" pass agreed 2026-08-23: fix scoping/security gaps,
give every role real authority in their own area, add user management, bring every module to full
CRUD depth, add notifications + dark theme, and run tests throughout rather than at the end. Ticked
off as work lands; see [CHANGELOG.md](../CHANGELOG.md) for the detailed log of what changed and
when. Findings below are from a three-agent audit on 2026-08-23 (RBAC, module CRUD, theming/notifs).

## Phase 0 — Security & scoping fixes — done 2026-08-23

**Correction to the initial audit**: most of the "unscoped" findings below turned out to be false
positives once `app/Models/Concerns/BelongsToSchool.php` came to light — a trait (used by
`Assessment`, `Student`, `Subject`, `SchoolClass`, `PayrollRun`, `InventoryItem`,
`IncidentReport`, `Notice`) that adds a *global* query scope to the current user's `school_id`
plus auto-stamps it on create. Since it's a global scope, it also protects implicit
route-model-binding (`Student $student` 404s if it's not in the acting user's school) — so
`HealthRecordController`, `PayrollRunController`, `InventoryItemController`, and
`IncidentReportController::updateStatus` were already safe; no changes needed there. Left this
note in place so a future audit doesn't re-flag the same non-bugs without checking for the trait
first.

Two models genuinely had no scoping — `MedicationAdministration` and `ClinicVisit` have no
`school_id` column and don't use the trait, so their `index()` listings and `student_id` input
were checked against every school, not just the acting user's:

- [x] `MedicationAdministrationController::index` — scoped via `whereHas('student', ...)`;
      `store()`'s `student_id` validation now uses `Rule::exists(...)->where('school_id', ...)`
      instead of a bare `exists:students,id`
- [x] `ClinicVisitController::index` — same two fixes
- [x] `AssessmentController::index` — admin was "authorized" via `role:teacher|admin` but the
      query filtered by teaching assignments admin doesn't have, silently returning nothing;
      admin now sees every assessment at their school (already scoped by `Assessment`'s
      `BelongsToSchool`), teachers still see only their own assignments
- [ ] `NoticeController::publish` — reconsidered, not fixing: any teacher/admin can publish any
      notice *at their own school* (cross-school is already blocked by the trait). Left as-is —
      a shared school noticeboard being editable by any staff member is plausibly intended
      behavior, not a bug. Revisit if that assumption is wrong.
- [x] Add regression tests for the two real fixes above (medication/clinic-visit cross-school
      listing + input validation) — plus one for the `AssessmentController::index` admin fix.
      57/57 tests passing.

## Phase 1 — User management (admin authority) — done 2026-08-23

- [x] `UserController` (admin-only, scoped to the admin's own school): index, create, store, edit,
      update, plus `toggleActive` (deactivate/reactivate rather than hard delete — users have
      FK'd history: assessments recorded, payments made, etc.). Deactivated users are blocked at
      login (`LoginRequest::authenticate`), not just hidden from lists.
- [x] Store/update wire `assignRole`/`syncRoles` — previously only ever called from the seeder.
      A generated password is shown once on the success page (no outbound email/SMS exists yet to
      deliver it another way — see the NIRA/OTP stubs in `docs/DECISIONS.md`).
- [x] **Parents must be linked to a child, admin creates learners the same way** — added per
      product feedback mid-build. `store()`/`update()` require at least one child, either an
      existing `Student` (checkbox list) or a new one entered inline; a `learner` role links to an
      existing student record with no login yet. Both paths require picking the child's
      **curriculum level** (nursery/primary/lower secondary/upper secondary) — also per feedback,
      so a student doesn't show up in modules that don't apply to their level (e.g. nursery daily
      logs for a primary learner). No hardcoded default level.
- [x] Nav entry (admin-only) + a "Manage users" link on the admin dashboard
- [x] Feature tests (`tests/Feature/UserManagementTest.php`): parent+existing child, parent+new
      child, parent-with-no-child fails validation, learner+existing student, staff+profile,
      deactivated-user-blocked-at-login, self-deactivation blocked, cross-school 403, non-admin
      403.
- [ ] **Follow-up, not built here**: editing curriculum level (or any other field) on an
      *existing* student still has no screen — `UserController` only sets it when creating a new
      child inline. Real `Student`/`Guardian` CRUD is still one of the "scaffolded down to nothing"
      gaps noted in Phase 3 — revisit there rather than bolting more onto `UserController`.

## Phase 2 — Give bursar and learner a real area

Both currently have **zero routes** gated to them — dashboard-only.

- [ ] Bursar: create invoices (currently invoices only exist via seeder), record a manual payment
      (cash/bank — the two `Payment.method` enum values with no UI path today), view payment
      history/reconciliation view
- [ ] Learner: view own full assessment history (not just dashboard summary), own attendance
      record, notice archive (not just latest 5)
- [ ] Feature tests for both

## Phase 3 — CRUD depth across every module

Per the audit: every module tops out at `index`/`create`/`store` (routes explicitly
`->only([...])`) — no module except `ProfileController` has real `edit`/`update`/`destroy`.

- [ ] Assessments — edit/update (fix a typo'd score), no destroy (audit trail matters for grades)
- [ ] Notices — edit/update own notice pre-publish, destroy own/any (admin)
- [ ] Incident reports — edit own before triage starts; destroy admin-only
- [ ] Medications, clinic visits — edit/update (correct a mis-entered record), destroy admin-only
- [ ] Payroll runs / payslips — edit before finalized, no destroy once payslips generated
- [ ] Inventory items — edit/update, destroy (with a check: block if it has transaction history)
- [ ] Inventory transactions — a void/reverse action (currently no way to undo a stock movement)
- [ ] Nursery: daily activity logs, milestones, WOW moments — edit/update + destroy own-day entries
- [ ] Feature tests for every new action above

## Phase 4 — Gate passes (build from scratch)

Confirmed: migration + model only. No controller, routes, views, factory, or tests exist at all —
below the docs' own bar for "scaffolded."

- [ ] `GatePassController` — request (learner/parent-initiated or teacher-initiated), approve
      (admin/teacher), log departure/return
- [ ] Routes, views (`resources/views/gate-passes/*`), factory
- [ ] Role scoping mirroring the incident-report pattern (guardian sees own student's passes only)
- [ ] Feature tests

## Phase 5 — Notifications

No `notifications` table exists; two mail-only `Notification` classes already exist
(`ClinicVisitLogged`, `MedicationAdministered`) and `User` already has `Notifiable` — this is glue
work, not a from-scratch system.

- [ ] `php artisan notifications:table` migration
- [ ] Extend `via()` on the two existing notification classes to add `'database'` — **do not**
      mark the database channel `ShouldQueue` (or split channels) so the bell count updates
      immediately rather than waiting on a queue worker that isn't running anywhere in local dev
      or documented for Laravel Cloud
- [ ] New notification events worth adding: new assessment score posted (learner/parent), payment
      received (parent), incident status changed (reporter), low-stock alert (librarian), new
      gate pass request (approver)
- [ ] Bell icon + dropdown notification center in `resources/views/layouts/navigation.blade.php`
      (natural spot: beside the existing `<x-dropdown>` settings menu, same Alpine pattern), mirror
      in the responsive mobile menu block
- [ ] Mark-as-read endpoint
- [ ] Feature tests

## Phase 6 — Dark theme

Zero dark-mode infrastructure exists in the real app today (only the untouched Breeze
`welcome.blade.php` splash page uses `dark:`, and only via OS media query, not a toggle). Tailwind
is v3.4.19 (despite an unused v4 Vite-plugin devDependency) — use v3 config syntax.

- [ ] `tailwind.config.js`: add `darkMode: 'selector'`
- [ ] Alpine `theme` store (`resources/js/app.js`) + toggle, persisted to `localStorage`, with a
      pre-paint inline script in both layouts to avoid a flash of wrong theme
- [ ] Toggle control in `navigation.blade.php` (desktop + mobile blocks)
- [ ] Migrate all 8 dashboards from inline `bg-white overflow-hidden shadow-sm sm:rounded-lg p-6`
      to the existing-but-unused `<x-card>` component, then add `dark:` variants there once
      instead of in 8 places
- [ ] Add `dark:` variants to `<x-badge>`, `<x-dropdown>`, nav bar, both layouts, and every view
      built in Phases 1–5 as they're written (build dark-mode-native going forward, don't do it as
      a second pass over new code)

## Phase 7 — Logo

- [x] Three concepts presented 2026-08-23: https://claude.ai/code/artifact/744fa4a2-4d9f-440d-8c02-7a2ec0e9ef34
- [ ] Direction picked
- [ ] Finalize SVG source, generate favicon sizes, wire into nav bar + auth layout + `<title>`/meta

## Phase 8 — Full QA pass

Test-coverage gaps identified by the audit, to close alongside (not after) the phases above:

- [ ] `HealthRecordController`, `ClinicVisitController` — currently zero feature tests
- [ ] `InventoryItemController` item CRUD (as opposed to `InventoryTransactionController`, which
      is tested) — zero tests
- [ ] Entire nursery trio (`DailyActivityLogController`, `MilestoneChecklistController`,
      `WowMomentController`) — zero tests
- [ ] `DashboardController` — no test hits `/dashboard` for any of the 8 roles
- [ ] Manual click-through of every role's full flow once its module reaches Phase 3/4 depth
- [ ] Full `php artisan test` suite green before considering this pass done
