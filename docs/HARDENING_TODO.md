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

## Phase 1.5 — Landing page — done 2026-08-23

Not in the original plan — added mid-build per feedback that the stock Laravel/Breeze splash
page (`resources/views/welcome.blade.php`: "Let's get started", Laracasts links, a version
number and changelog link) was confusing for a real product to land on.

- [x] Replaced with a real Scholara landing page: hero + a role card per one of the 8 roles,
      copy pulled from `docs/ARCHITECTURE.md`'s own role descriptions rather than invented
      marketing text, single "Log in" CTA
- [x] Dropped the "Register" link from the login/landing flow (not the route itself, just
      stopped surfacing it) — now that admin-driven `UserController` exists and always links a
      role + child/profile, an unlinked self-registered account is a confusing dead end, not a
      real path into the app
- [x] Also deleted a large amount of dead weight: the old page's fallback `<style>` block was a
      hardcoded ~30KB Tailwind v4 stylesheet, left over from `@tailwindcss/vite` being listed in
      `package.json` as a devDependency but never actually wired into `vite.config.js` — the app
      genuinely runs Tailwind v3 (see `docs/HARDENING_TODO.md` Phase 6 below). Worth removing that
      unused package entirely when Phase 6 touches `tailwind.config.js`.
- [x] Strengthened `ExampleTest` (the existing `GET /` smoke test) to assert real content, not
      just a 200 status — it would have caught a syntax bug I introduced and fixed in the same
      pass (unescaped apostrophes inside a single-quoted PHP string)

## Phase 2 — Give bursar and learner a real area — done 2026-08-23

Both had **zero routes** gated to them before this — dashboard-only.

- [x] Bursar: `InvoiceController` — create invoices (previously only ever existed via the seeder),
      a `show` page listing payments against one invoice, and `recordPayment` for the two
      `Payment.method` values with no UI path before (cash/bank) — completes immediately since the
      bursar is confirming money already in hand, unlike the guardian DGateway checkout which
      starts "pending." `Invoice`/`Payment` have no `BelongsToSchool` scope of their own (tied to
      a school only via `student_id`), so every query filters through the student relation
      explicitly — same pattern as the Phase 0 medication/clinic-visit fixes.
- [x] Learner: `LearnerController` — full assessment-score history, full attendance history
      (paginated), and a full notice archive (paginated), vs. the dashboard's five-item summaries
- [x] Feature tests: `InvoiceManagementTest`, `LearnerPortalTest` — caught one real bug along the
      way (`InvoiceController::show`/`recordPayment` crashed 500 instead of returning 403 for a
      cross-school invoice, because `$invoice->student` itself comes back `null` — `Student`'s own
      `BelongsToSchool` scope already filters it out before the explicit school check ever runs)

## Phase 2.5 — Nav polish, school levels, photo uploads, logo — done 2026-08-23

Not in the original plan — mid-build feedback after Phase 2 landed.

- [x] **Nav overflow fix**: the desktop nav switched from hamburger to a flat link row at 640px
      (`sm:`), which by this point had grown to as many as 12 items for admin — neither the
      hamburger nor the full row fit cleanly on tablet/small-laptop widths. Raised the breakpoint
      to `xl:` (1280px) and grouped the two 2-3-item clusters (Academics, Health) into dropdown
      menus (`<x-nav-dropdown>`, new component reusing the existing `<x-dropdown>`) rather than
      just widening — a flat 9+ item row still wouldn't have been "clear and easy to follow"
      even if it technically fit.
- [x] **Landing page trim**: cut Nurse/HR/Bursar/Librarian from the public role grid — internal
      operational roles read as an internal staff directory, not a product page. Kept
      Admin/Teacher/Parent/Learner, with a one-line footnote that the other roles exist once an
      admin sets up an account.
- [x] **School-configurable curriculum levels**: `School.settings` (existing JSON column, no
      migration needed) now optionally holds `levels` — which of nursery/primary/lower_secondary/
      upper_secondary the school actually runs. `School::offersLevel()` gates level-specific nav
      items (currently just Nursery) so a primary-only school doesn't see modules that don't apply
      to it. Admin-editable anytime via a new `SchoolSettingsController`/`/school-settings` page —
      **not** tied to the public self-registration flow, since that flow is de-emphasized/unused in
      this app's one-school-per-deployment model (see the Phase 1.5 landing-page note above). An
      unconfigured school offers every level by default, so nothing disappears before an admin has
      set this.
- [x] **Photo uploads**: `photo_path` added to `students` and `staff_profiles`.
      `StudentPhotoController` — admin can upload for any student at their school, a parent only
      for their own guardian-linked children (checked in the controller, not just route middleware
      — same ownership-check pattern used everywhere else). Staff photos are admin-only, wired
      into `UserController`'s existing create/edit flow (no parent-equivalent for staff). Ran
      `php artisan storage:link` for local dev.
      **Known gap, not fixed here**: uploads go to the local `public` disk, which is fine for local
      dev but ephemeral on Laravel Cloud (and was Render's exact SQLite problem before it — see
      `docs/STAGING.md`) — photos would vanish on the next deploy in production. Needs an S3-
      compatible disk before this is production-real; deferred since it needs real cloud storage
      credentials, same category of gap as the DGateway/NIRA/OTP stubs.
- [x] **Logo implemented**: the "Open Book, Networked" concept from the earlier design pass
      (`https://claude.ai/code/artifact/744fa4a2-4d9f-440d-8c02-7a2ec0e9ef34`) replaces the default
      Breeze mark in `application-logo.blade.php`, plus a matching `public/favicon.svg` wired into
      all three page `<head>`s (main app layout, guest/auth layout, landing page).
- [x] Feature tests: `SchoolSettingsTest`, `StudentPhotoTest`, plus two new cases in
      `UserManagementTest` for staff/new-child photo upload. 82/82 passing.

## Phase 2.6 — Nav rewrite v2, component polish, trend reports — done 2026-08-23

More mid-build feedback, same day: the `xl:` breakpoint fix above still wasn't right — at
in-between widths the flat desktop row and the hamburger could both end up not showing cleanly,
and a 9+ item single row was never going to be "clear and easy to follow" regardless of where it
broke. Went with a more decisive fix instead of another breakpoint tweak.

- [x] **Nav is now always the hamburger/off-canvas menu**, at every screen width — no more
      breakpoint to get wrong. The header itself is now just logo, a noticeboard bell (badge =
      notices published in the last 3 days — a lightweight proxy, not true per-user read
      tracking, which would need its own pivot table), the user menu, and the toggle. The
      previously-added `<x-nav-dropdown>` component is gone — no longer needed once there's no
      flat row to group items within; the off-canvas menu uses small uppercase section labels
      (Academics, My Records, Health, Operations) instead.
- [x] **Cards and buttons modernized**: `<x-card>` gets a softer `rounded-xl` + subtle
      `ring-1 ring-gray-950/5` instead of a hard border, for a more "floating" feel. The three
      button components were still the stock Breeze defaults — `bg-gray-800` (not even the
      app's indigo accent used everywhere else), uppercase tiny tracked-out text, `rounded-md`.
      Rewrote all three to `rounded-lg`, normal-case `text-sm`, indigo/red at full saturation
      with a `hover:shadow` lift and `transition-all`, and `disabled:opacity-50
      disabled:pointer-events-none` (previously only secondary had a disabled state at all).
      Migrated the admin dashboard's raw `bg-white ... rounded-lg` divs to `<x-card>` while
      touching that file anyway — the other 7 dashboards still need the same treatment (tracked
      in Phase 6 below, since that migration was already planned there for dark-mode reasons).
- [x] **Seed data enriched for real testing**: `DemoDataSeeder` went from 3 students/2 terms
      of sparse data to 8 students across the Primary 5 class with a real spread (weak/average/
      strong, each trending upward Term 1 → Term 2, deterministic formulas not `rand()` so
      re-seeding stays stable), 15 days of attendance with a realistic absent/late mix, 8 clinic
      visits and 3 medication administrations spread over 2 months with varied reasons/outcomes.
- [x] **Trend reports**: `ReportController` — `academics()` (average score by subject per term,
      plus a "students below 60%" list) and `health()` (clinic visits by reason/outcome,
      medications administered, last 90 days), both as simple CSS-bar visualizations, no chart
      library. Every query filters by school explicitly via a join rather than relying on a
      model's `BelongsToSchool` scope, since these are aggregate `DB::table()` queries across
      tables that don't all carry their own `school_id` (same reasoning as the Phase 0 fixes).
- [x] Feature tests: `ReportTest`. 85/85 passing.

## Phase 5 — Real notifications, moved up — done 2026-08-24

Pulled forward out of order per explicit feedback ("notifications should be clearable") — the
nav's recency-badge bell from Phase 2.6 was a lightweight placeholder, not a real notification
center.

- [x] `notifications` table (Laravel's standard polymorphic schema).
- [x] `ClinicVisitLogged`/`MedicationAdministered` extended with a `'database'` channel and
      `toDatabase()`; both had `ShouldQueue` removed — that would defer *every* channel including
      the database row to a queue worker, and nothing guarantees one is running (no
      `queue:work` process exists in local dev or is documented for Laravel Cloud). A small
      school's volume doesn't need queuing; synchronous keeps the bell accurate immediately.
- [x] Three new notification classes wired into real triggers: `PaymentReceived` (guardian, from
      both `InvoiceController::recordPayment` and the DGateway webhook on `transaction.completed`
      — the Financial Center had no payment-confirmation notification at all before this),
      `IncidentStatusUpdated` (the reporter, skipped for anonymous reports by design — see
      `docs/DECISIONS.md`'s RTRR note), `NoticePublished` (every guardian + learner at the school,
      database-only/no mail — mailing everyone on every routine notice would be noisy).
- [x] `NotificationController` — `destroy` (clear one), `destroyAll` (clear all), `markAllRead`.
      "Clearable" means delete, not just mark-read — the literal ask.
- [x] Nav bell replaced with a real dropdown: unread badge, last 10 notifications, per-item clear
      (×) button, "Mark all read" / "Clear all". Server-rendered on each page load, no
      polling/AJAX — consistent with the rest of the app, won't live-update without a refresh,
      a deliberate simplicity trade-off.
- [x] Feature tests: `NotificationTest` (guardian notified on payment, clear one/all, mark-all-
      read, reporter notified on status change, anonymous reporter correctly skipped).

## Phase 2.7 — UI polish pass — done 2026-08-24

Explicit feedback: "polish the UI, it's so sub-standard, we can do better." Swept every dashboard
and module list view rather than a few spot fixes.

- [x] All 8 role dashboards migrated from raw `bg-white ... rounded-lg` divs to `<x-card>`,
      picking up the Phase 2.6 `rounded-xl`/`ring` treatment automatically. Added colored
      `<x-badge>` status pills where a status was previously plain text (payroll run status,
      invoice status on the bursar/parent dashboards, low-stock inventory warning), and gave stat
      numbers more visual weight (`text-3xl font-semibold` consistently, was inconsistent before).
- [x] New `<x-empty-state>` component (icon + message) replacing plain `text-gray-500` "No X yet"
      text across all 8 dashboards plus assessments/notices/incidents/medications/clinic-visits/
      payroll-runs/inventory-items/daily-activity-logs/milestones/wow-moments/invoices/users/
      reports/learner list views — one shared component instead of ad hoc text everywhere.
      Deliberately left the *reassuring* empty state on the academics report ("no students
      currently below 60%") as plain text — that one's good news, not a data gap, so the same
      icon would send the wrong signal; and left two compact inline roster/checkbox empty states
      (`users/edit.blade.php`, `attendance/create.blade.php`) as plain text since the full
      icon+padding component would look oversized in that context.
- [x] Added `DashboardTest` (one request per role) — the original audit flagged zero coverage on
      `/dashboard` for any role, and every dashboard view was just touched, so this is exactly the
      moment a regression would otherwise go unnoticed. Caught one real bug while adding it:
      PHPUnit 11 (this project's version) dropped docblock `@dataProvider` support in favor of the
      `#[DataProvider]` attribute — the docblock form parses but silently does nothing.
- [x] 99/99 tests passing throughout.
- [ ] **Not done in this pass**: the module list views beyond empty-states (edit/destroy actions,
      dark theme) are still Phase 3/Phase 6 work, not pulled forward here.

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

## Phase 5 — Notifications — done, see above

Built earlier than planned (moved up per feedback) — see "Phase 5 — Real notifications, moved up"
above for what actually landed. Left as a follow-up, not done in that pass: a **new assessment
score posted** notification (learner/parent) and a **low-stock alert** (librarian) — both named in
the original plan here but not built; gate passes don't exist yet at all (Phase 4 below), so a
gate-pass-request notification has nothing to attach to yet either.

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
