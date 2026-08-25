# Roadmap

## Phase 0 — Environment & scaffold (current)

- [x] Read and distill the plan into `docs/`.
- [x] Git repo initialized, remote set to `cruze-intelligent/Scholara`.
- [x] Local PHP/Composer/MySQL dev environment (Laragon), with OPcache enabled — see
      [docs/PERFORMANCE.md](./PERFORMANCE.md).
- [x] Laravel project created, `spatie/laravel-permission` installed, roles seeded.
- [x] Core migrations for every module in `docs/ARCHITECTURE.md`'s module map (28 tables).
- [x] One minimal Blade screen per role so every role can log in and see *something* real —
      verified end-to-end for all 8 roles against seeded demo data.
- [x] Stub services for NIRA, OTP/2FA, SchoolPay (interfaces + fake implementations).
- [x] First push to GitHub.

## Phase 1 — Deepen the core (Learner/Parent/Teacher loop) — done 2026-08-21

- [x] Real marksheet entry with MOT/EOT weighting and auto-scaling — see
      [docs/DECISIONS.md](./DECISIONS.md) for the adopted weighting split.
- [x] Noticeboard + issue reporting (RTRR-aligned) fully wired, not just scaffolded.
- [x] Attendance with gender-based stats.
- [x] Predictive analytics (`PerformancePredictor`) running on real assessment data, surfacing
      Support Strategy alerts to teacher + parent/learner dashboards.
- Follow-up: a real teacher-facing composite-grade report (per-subject term grade using
  `GradingService::compositeScore`) isn't surfaced in a screen yet, only the raw per-assessment
  scores — the service exists but only `scaleScore`/`classMeanFor` are wired into views so far.

## Phase 2 — Health, HR, inventory — done 2026-08-21

- [x] Nurse portal: EHR (health record edit), eMAR with real Five Rights checks, clinic visit
      logging, parent notification on medication/visit events (mail notifications).
- [x] HR/Payroll: staff profiles (extended with a salary field), payroll periods, PAYE/NSSF
      deduction calculation — see [docs/DECISIONS.md](./DECISIONS.md) for the rates used.
- [x] Inventory/store: library + canteen + equipment tracking, with stock in/out keeping
      `quantity` in sync automatically.
- [x] Nursery-specific: daily activity logs, milestone checklists (static catalog — no lookup
      table), WOW moments journaling (with photo upload).
- Follow-up: read-side audit logging (every `show`/view, not just writes) and a real teacher↔
  subject assignment admin screen (assignments are currently seeded/DB-only, no CRUD UI for an
  admin to create them).
- Verified 2026-08-22: this code had never actually been migrated/seeded against a running
  database on this machine — MySQL couldn't start (see CHANGELOG.md). Fixed the local
  environment, then ran `migrate` + `db:seed` + the full test suite for the first time end to
  end: 48/48 passing after fixing a decimal-cast bug the mismatch exposed (see below).

## Phase 3 — Integrations (needs external accounts)

- [x] Card + mobile money payments for the Financial Center — started 2026-08-23, via
      [DGateway](https://dgateway.desispay.com) rather than SchoolPay directly (see
      docs/DECISIONS.md for why). Guardian-initiated checkout, webhook + status-poll
      confirmation, `Invoice`/`Payment` status kept in sync. Bound behind `FakePaymentGateway`
      until a real `DGATEWAY_API_KEY` exists — placeholder mode, no account signed up yet.
      Follow-ups: card checkout doesn't load Stripe.js yet (mobile money is the complete path),
      and the UGX-via-Stripe currency assumption is unverified against a real account.
- Real SMS/USSD OTP via an SMS gateway.
- Real NIRA TPI integration (needs TPI credentials + PDPO registration in place first).
- Push notifications / SMS alerts for gate pass, arrival/departure.

## Phase 4 — Offline-first edge layer

- "School-in-a-Box" Raspberry Pi deployment: local Wi-Fi hotspot, SQLite local store.
- Delta-sync protocol against Scholara's `routes/api.php`.
- Conflict resolution strategy for records edited both offline and online.

## Not yet scheduled

- Mobile app (Flutter/React Native) — the plan's original mobile recommendation. Deferred since
  phase 0–2 target the Blade web app; the JSON API groundwork keeps this option open.
- Multi-school/tenant billing (per-student/term, enterprise license, NGO grant pricing models
  from the plan) — revisit once there's more than one paying school.
- **School self-registration + subscriptions** (requested 2026-08-25, explicitly "later stages") —
  a public sign-up flow where a new school registers with verifiable credentials, gets a
  free one-month trial, then moves to per-term (90-day) billing. Today the app is
  single-school-per-deployment with admin-only account creation (see Phase 1.5's landing-page
  note); this would need real multi-tenancy decisions (shared DB with stronger tenant isolation
  vs. per-school database), a billing/subscription state machine, and a trial-expiry enforcement
  path — deliberately not started, flagged here so it isn't lost.

---

Update the checkboxes above as work lands; see [CHANGELOG.md](../CHANGELOG.md) for the detailed
log of what changed and when.
