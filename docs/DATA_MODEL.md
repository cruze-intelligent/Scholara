# Data model (planned)

Entities the phase-0 migrations will create, grouped by module. Every table is scoped by
`school_id` (multi-tenant from day one). This is the design reference for the migrations —
update it if a migration ends up shaped differently than what's written here.

## Core / identity

- **schools** — id, name, address, settings (JSON: retention window, curriculum level offered).
- **users** — Laravel default + `nin` (nullable, unique per school), `phone`, `two_factor_*`.
  Roles/permissions via `spatie/laravel-permission`.
- **consents** — user_id, type (e.g. `data_processing`), version, accepted_at, ip_address.
- **staff_profiles** — user_id, trn (7-digit Teacher Reference Number, nullable for non-teaching
  staff), role_title, hire_date, monthly_gross_salary (nullable — payroll generation skips staff
  without one set).
- **guardians** — user_id, relationship_to_student.
- **students** — school_id, user_id (nullable — the learner's own login account, if the school
  issues one), admission_no, first_name, last_name, dob, gender, school_class_id,
  curriculum_level (nursery/primary/lower-secondary/upper-secondary).
- **student_guardian** — student_id, guardian_id (pivot; this is the access-control boundary
  described in `COMPLIANCE.md`).
- **school_classes** — school_id, name, level, teacher_id (class teacher).
- **teacher_subject_assignments** — teacher_id, subject_id, school_class_id (which teacher
  teaches which subject in which class — needed for marksheet-entry authorization; a class's
  single `teacher_id` above is only the homeroom teacher, not every subject teacher).
- **audit_logs** — user_id, auditable_type, auditable_id (polymorphic), action
  (view/create/update/delete), changes (JSON). Write-side only for now (model `created`/
  `updated`/`deleted` events) on health and financial models, per the audit-trail requirement in
  `COMPLIANCE.md`; read-side auditing (logging every `show`) is a follow-up.

## Academics

- **subjects** — school_id, name, curriculum_level.
- **assessments** — school_id, subject_id, school_class_id, type (`AoI`, `MOT`, `EOT`), term,
  max_score, weight.
- **assessment_scores** — assessment_id, student_id, raw_score, scaled_score, recorded_by
  (teacher user_id), recorded_at.
- **attendance_records** — school_class_id, student_id, date, status (present/absent/late),
  recorded_by.
- **lesson_plans** — teacher_id, school_class_id, subject_id, date, template_type
  (`standard`/`nursery_milestone`), content (JSON).

## Communication / safety

- **notices** — school_id, author_id, audience (role/class scope), title, body, published_at.
- **incident_reports** — reporter_id (nullable/anonymous flag), student_id, category
  (bullying/violence/other, RTRR-aligned), description, status, assigned_to.
- **resources** — teacher_id, subject_id, school_class_id, title, file_path.
- **gate_passes** — student_id, requested_by, reason, approved_by, departed_at, returned_at.

## Nursery-specific

- **daily_activity_logs** — student_id, date, meals (JSON), nappy_changes (JSON),
  bathroom_breaks (int), sleep_checks (JSON: start/end pairs), logged_by.
- **milestone_checklists** — student_id, domain (physical/cognitive/emotional/health),
  milestone_label, achieved_at, notes.
- **wow_moments** — student_id, teacher_id, caption, media_path, created_at.

## Health / EHR

- **health_records** — student_id, chronic_conditions (JSON), allergies (JSON), vaccinations
  (JSON), emergency_contacts (JSON), family_physician (JSON).
- **medication_administrations** — student_id, medication_name, dose, route, administered_by,
  administered_at, scheduled_time, five discrete `checked_right_*` booleans (patient/drug/dose/
  route/time — the actual nursing Five Rights, replacing the original single
  `five_rights_checked` flag), notes. `five_rights_checked` is now a computed accessor (true only
  when all five checks are true), not a stored column.
- **clinic_visits** — student_id, reason, diagnosis, treatment, outcome
  (returned_to_class/sick_bay/referred_to_hospital), logged_by, occurred_at.

## HR / Payroll / Inventory

- **payroll_runs** — school_id, period_start, period_end, status.
- **payslips** — payroll_run_id, staff_profile_id, gross_pay, paye, nssf, net_pay.
- **inventory_items** — school_id, category (library/canteen/equipment), name, quantity, unit.
- **inventory_transactions** — inventory_item_id, type (in/out), quantity, related_to
  (nullable polymorphic — e.g. a book loan to a student), occurred_at.

## Financial center

- **invoices** — student_id, term, amount_due, due_date, status.
- **payments** — invoice_id, amount, method (mobile_money/schoolpay/bank/cash), reference,
  paid_at, gateway_response (JSON — populated by the stubbed `PaymentGateway` for now).

## Sync (offline-first, phase 4)

Any table the edge layer needs to write offline gets two columns ahead of time so phase 4 isn't
a migration rewrite: `dirty` (bool, default false) and `synced_at` (nullable timestamp).
Candidates: `attendance_records`, `assessment_scores`, `daily_activity_logs`.
