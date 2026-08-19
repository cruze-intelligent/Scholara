# Compliance

Summarized from the plan's regulatory research. This is engineering-facing guidance, not legal
advice — a real DPO/lawyer should sign off before launch.

## Data Protection and Privacy Act 2019 (Uganda)

- **Mandatory consent** — parents/guardians must explicitly consent before the school processes
  a child's data. Implemented as a digital click-wrap agreement recorded at parent registration
  (`consents` table: user, version of terms, timestamp, IP).
- **Data minimization / purpose limitation** — only collect what a module needs; don't add
  free-text fields "just in case." Retention: dormant accounts get flagged for deletion after
  the school's configured retention window (Dalton's precedent: 10 years of inactivity).
- **Security safeguards (Section 20)** — "appropriate measures" against unauthorized access.
  Concretely: MFA on every role (see below), role-scoped queries (a Teacher can never query
  another school's students), encrypted-at-rest health/financial fields, audit log on
  read/write of health and financial records.
- **Registration** — the operating school (not just the software vendor) must register with the
  Personal Data Protection Office (PDPO) and appoint a Data Protection Officer. Out of scope for
  the codebase; track as an onboarding checklist item per school.

## Identity verification (NIRA)

- **NIN matching** — parents and teachers register with NIN + card number + DOB, checked against
  NIRA's Third Party Interface (TPI). Any org calling the TPI must itself be PDPO-registered.
  **Stubbed** in this codebase (`App\Services\Identity\NiraVerifier`) until TPI credentials are
  obtained — see [ARCHITECTURE.md](./ARCHITECTURE.md#deliberately-stubbed-not-built).
- **Student ID mapping** — a parent's verified NIN is linked to specific Student IDs
  (`student_guardian` pivot) so a parent can only ever see their own ward's data. This mapping
  is the actual security boundary, not the NIN check itself — enforce it in every query, not
  just at login.
- **Biometric/facial matching** — for staff/teacher high-stakes onboarding (payroll integrity).
  Out of scope for phase 1; revisit once NIRA TPI access exists.

## 2FA

- SMS/USSD OTP as the baseline (works on feature phones, no smartphone/app requirement).
  **Stubbed** (`App\Services\Notifications\OtpSender`) — logs the OTP instead of sending it,
  until an SMS gateway account (e.g. Africa's Talking, which has Uganda coverage) is set up.
- NIN matching functions as a second identity factor for parents/teachers at registration time,
  distinct from the per-login OTP.

## Practical rule of thumb for this codebase

Anything touching a student's health, financial, or identity data goes through role-scoped
Eloquent queries (never a raw "all students" query in a controller), gets an audit log entry,
and assumes it will eventually be read by a PDPO auditor.
