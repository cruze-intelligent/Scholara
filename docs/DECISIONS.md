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

## Payment gateway: DGateway (`app/Services/Payments/`)

`docs/ROADMAP.md`'s Phase 3 named "SchoolPay / MTN & Airtel mobile money integration" without a
chosen vendor. Adopted [DGateway](https://dgateway.desispay.com) instead — one API that routes
`currency: "UGX"` to Iotec/Relworx (mobile money) and other currencies to Stripe (card), so the
parent-facing checkout only shows "Mobile Money" / "Card" and never the gateway name itself, per
the product ask. Chosen over integrating SchoolPay/MTN/Airtel directly because it's a single
integration surface for both mobile money and card rather than several.

- **No account exists yet.** The `PaymentGateway` interface `InvoicePaymentController` depends on
  is bound to `DGatewayPaymentGateway` only when `DGATEWAY_API_KEY` is set
  (`AppServiceProvider::register`); until then it auto-falls-back to `FakePaymentGateway`, same
  swappable-fake pattern as `NiraVerifier`/`OtpSender`. No code change needed to go live later —
  just set the env vars in `.env`/Laravel Cloud's dashboard. Get keys/secret from
  `https://dgatewayadmin.desispay.com` → My Apps → your app.
- **Amount/currency**: fees are charged in whole UGX (DGateway amounts are integers in the
  smallest currency unit; UGX has no minor unit, so `amount` is passed as-is, not ×100). Currency
  defaults to `UGX` for both mobile money and card (`DGATEWAY_DEFAULT_CURRENCY` — one-line change
  if card payments should instead be quoted in USD).
  **Unverified**: whether DGateway's Stripe leg actually accepts UGX-denominated card charges
  hasn't been tested against a real account — the "135+ currencies via Stripe" the docs mention
  may or may not include it. Would surface as an `INVALID_CURRENCY` error from DGateway.
- **No subscriptions.** DGateway supports recurring billing (plans/trial days/grace days), but
  Scholara's fee model is per-term invoices created by the bursar, not month-to-month
  subscriptions — so only the one-time `/v1/payments/collect` + status-check flow is wired up.
  `subscription.*` webhook events are intentionally ignored (`DGatewayWebhookController`).
- **Status polling endpoint**: DGateway's own reference (`llms.txt`) documents this as
  `POST /v1/webhooks/verify` with `{"reference": "..."}` — an unusual path for what is really a
  transaction status check, not a webhook operation, but that's what the live docs say; don't
  "fix" it to something more intuitive without re-checking the docs first.
- **Card UI is unfinished.** The checkout screen offers "Card" as an option and will call
  `/v1/payments/collect`, but doesn't yet load Stripe.js / Stripe Elements to actually confirm the
  `client_secret` DGateway would return for a real card charge — untestable without a live
  account. Mobile money is the complete, testable path today.
- **Guardian-initiated only.** No bursar-recorded/in-person payment flow exists; a parent pays
  their own child's invoice from their dashboard. `InvoicePaymentController::authorizeGuardian`
  checks the invoice's student belongs to the acting user's `Guardian`.

## Audit trail scope

`docs/COMPLIANCE.md` requires an audit log on read/write of health and financial records, but no
`audit_logs` table existed. Added one (`app/Models/Concerns/Auditable.php`), wired to
`HealthRecord`, `MedicationAdministration`, `ClinicVisit`, `Payslip`, `PayrollRun` via model
events — **write-side only**. Read-side auditing (logging every `show`/view) would need
controller-level instrumentation and hasn't been built yet.
