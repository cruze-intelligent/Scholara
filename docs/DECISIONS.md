# Decisions

Product defaults adopted where `docs/` and `School System Development Plan.pdf` named a
requirement without a formula or figure. Each is isolated in one place in the code so it's a
one-line change if the real school policy differs — treat these the same way as the project's
fake `NiraVerifier`/`OtpSender`/`PaymentGateway`: functional and swappable, not researched.

## Marksheet weighting (`app/Services/Academics/GradingService.php`)

`docs/ROADMAP.md` calls for "MOT/EOT weighting and auto-scaling" with no split given anywhere.
Adopted: AoI 20% / MOT 30% / EOT 50%, a common Uganda lower/upper-primary continuous-assessment
split. Auto-scaling is `raw_score / max_score * 100`. Change `GradingService::TYPE_WEIGHTS`.

## PAYE / NSSF (`app/Services/Payroll/PayeCalculator.php`, `NssfCalculator.php`)

No Uganda tax bands or NSSF rate appear anywhere in `docs/`. Adopted: published URA
resident-individual monthly PAYE bands and a 5% employee NSSF contribution (employer's matching
10% isn't modeled — `payslips` only has one `nssf` deduction column). **Verify against current
URA/NSSF guidance before relying on this for real payroll** — same caveat as the project's fake
payment gateway.

## Five Rights (`medication_administrations` table, `MedicationAdministration::RIGHTS`)

`docs/ROADMAP.md` names "Five Rights checks" without defining which five. Adopted the standard
nursing mnemonic — right patient, right drug, right dose, right route, right time — as five
discrete boolean checks plus real `route`/`scheduled_time` columns, replacing the original single
`five_rights_checked` flag the schema had.

## RTRR-aligned issue reporting

RTRR is never expanded or defined in `docs/` (likely only in the source PDF, which isn't
machine-readable in this environment). Kept the existing schema as-is — `category`
(bullying/violence/other), `anonymous`, and a `status` workflow — rather than inventing fields
for an undefined acronym. Revisit if the real RTRR requirements turn out to need more (e.g. a
mandatory external-reporting step or SLA timers).

## Gender-based attendance stats

`docs/ROADMAP.md` names this without a target metric. Adopted: % present per gender per class
(all recorded dates), with a simple flag when the gap between genders is ≥10 points. See
`AttendanceController::stats`.

## Audit trail scope

`docs/COMPLIANCE.md` requires an audit log on read/write of health and financial records, but no
`audit_logs` table existed. Added one (`app/Models/Concerns/Auditable.php`), wired to
`HealthRecord`, `MedicationAdministration`, `ClinicVisit`, `Payslip`, `PayrollRun` via model
events — **write-side only**. Read-side auditing (logging every `show`/view) would need
controller-level instrumentation and hasn't been built yet.
