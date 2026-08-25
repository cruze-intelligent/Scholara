# Global parity audit — 2026-08-25

Requested explicitly: compare Scholara's 8 roles against real-world school-management platforms
(PowerSchool, Fedena, openSIS, ManageBac/Blackbaud-style systems) and produce a concrete gap list,
rather than leaving "we're not there yet" as a feeling. This is the reference this repeated
question should now point back to — update it as gaps close instead of re-deriving it each time.

Method: read every route/controller currently wired (`routes/web.php`, `app/Http/Controllers/*`)
against the feature set those platforms ship as standard, for each of the 8 roles plus
cross-cutting modules that don't belong to one role. Gaps are tagged **High** (a real school would
consider this table-stakes, not a nice-to-have), **Medium** (common, meaningfully useful, not
blocking), or **Low** (present in some platforms, lower value for a single-school Ugandan deployment).

## Cross-cutting gaps (not owned by one role)

These are the ones most responsible for the "doesn't compare to global systems yet" feeling —
missing entirely, not partially built:

- **[High] Document management.** Nothing today lets anyone attach an arbitrary file to a record —
  only three narrow, hardcoded upload paths exist (`StudentPhotoController`,
  `staff_profiles.photo_path`, `WowMoment` photos). No lesson-plan/teaching-notes repository for
  teachers, no way to attach a scanned prescription or dosage sheet to a `HealthRecord`, no way
  for admin to bulk-attach documents to a student file (birth certificate, prior transcripts), no
  bulk CSV import/export of student or staff lists (`UserController` only does one-at-a-time
  creation). Every global SIS treats "attach a file to X" as generic infrastructure, not a
  per-feature special case.
- **[High] Report/transcript generation (PDF).** `ReportController` produces two in-browser trend
  visualizations (academics, health) — there is no printable report card, no term transcript, no
  payslip PDF, no invoice/receipt PDF. A parent or bursar in a real deployment needs a document to
  hand someone, not just a webpage.
  - **Reframing note, not a gap**: a student's own live results *are* already fully browsable
    (`LearnerController::assessments`, linked in nav) — this item is specifically about a
    printable/downloadable artifact, not about visibility, which already exists.
- **[High] Timetable/scheduling.** No `Timetable`/`Period`/`Room` model at all — a teacher's
  `teacher_subject_assignments` say *what* they teach, never *when*. Nearly every real SIS leads
  with a weekly timetable grid (teacher view, class view, room-clash detection).
- **[Medium] Library circulation vs. generic inventory.** `InventoryItemController` treats a
  library book the same as a canteen supply — one `quantity` counter, no per-copy tracking, no
  borrower/due-date/fine workflow. A librarian's actual job (who has book X, is it overdue) isn't
  represented; that whole module is really "stock control," not "library."
- **[Medium] Academic-year rollover.** No concept of promoting a whole class/cohort to the next
  curriculum year, archiving the old term's data, or reassigning `school_classes` at year-end —
  everything assumes the current term forever. Every real school does this once a year and it's
  currently a manual DB job, not a feature.
- **[Medium] Bulk communication.** `NoticeController` is one-notice-at-a-time to everyone at the
  school; no way to message just "Parents of P5," no SMS channel at all (stubbed in
  `docs/ROADMAP.md` Phase 3, still unbuilt), no parent-initiated message *to* a teacher (comms are
  one-directional: admin/teacher → everyone).
- **[Low] Admissions/enrollment workflow.** New students only enter via admin's `UserController`
  (an internal action). No public/semi-public "apply for admission" intake with a waitlist —
  reasonable to skip for a single-school internal tool, listed for completeness only.
- **[Low] Expense tracking.** `InvoiceController`/`Payment` model income (fees) only; no
  school-expense ledger, so there's no P&L view, only a revenue view.

## Per-role depth

### Admin
Real authority: user management with role-appropriate onboarding, school settings/curriculum
levels, cross-module oversight via every `role:...|admin` group. **Gap**: no dashboard-level KPI
rollup (enrollment trend, revenue-collected-vs-invoiced, staff headcount) — admin currently sees
the same five-item lists as everyone else, not an executive view. **[Medium]**. No CSV
export/import anywhere admin operates. **[High]**, ties to the cross-cutting document-management
gap above.

### Teacher
Real authority: assessments/scores, attendance, nursery logging, notices. **Gap**: no lesson-plan/
resource upload (the "teaching notes" the user named explicitly) — ties to the document-management
gap. **[High]**. No timetable of their own periods. **[Medium]**, ties to the timetable gap. No
homework/assignment posting distinct from a generic notice (submission tracking, due dates).
**[Medium]**.

### Parent
Real authority: DGateway checkout, own children's photo upload, dashboard summaries. **Gap**: no
own-initiated message to a teacher (one-directional comms gap above). **[Medium]**. No downloadable
receipt after a payment (report-generation gap). **[Medium]**. No visibility into a document a
teacher shares (once that exists). Follows from the document gap, not a separate one.

### Learner
Real authority: full assessment/attendance/notice history via `LearnerController` — **this is
already at real depth**, contrary to the "same as the parent" framing in the request; a learner
sees more raw historical data than the parent dashboard currently surfaces (parent gets a
five-item dashboard summary, not the full paginated history learner has). **Gap**: parent should
arguably get the same full-history views learner does, scoped to each of their children — currently
asymmetric in the *wrong* direction. **[Medium]**.

### Nurse
Real authority: health records, Five Rights eMAR, clinic visits, health trend report. **Gap**: no
way to attach a scanned prescription/dosage sheet to a `HealthRecord` or `MedicationAdministration`
— the "medical dosage" upload the user named explicitly. **[High]**, ties to document-management.
No allergy/medical-alert flag surfaced anywhere outside the health record itself (e.g., not shown
to a teacher taking a nursery student on a trip). **[Medium]**.

### HR
Real authority: payroll runs with real PAYE/NSSF, draft→approved lifecycle. **Gap**: no staff leave
management (request/approve/balance) — standard in every HR module of a real SIS. **[Medium]**. No
payslip PDF download for a staff member to keep. **[Medium]**, ties to report-generation. No staff
document repository (contracts, certificates). **[Medium]**, ties to document-management.

### Bursar
Real authority: invoice creation, cash/bank payment recording, payment history. **Gap**: no fee
*structure* concept (per-class/term fee heads, discounts/scholarships) — every invoice is
currently ad hoc rather than generated from a school's actual fee schedule. **[Medium]**. No
receipt PDF. **[Medium]**, ties to report-generation.

### Librarian
Real authority: item CRUD, transaction ledger with void. **Gap**: this is the role furthest from
its real-world counterpart — see the library-circulation cross-cutting gap above. **[Medium]**.

## Roles not yet represented

Not a "these must be built" list — flagging what real SIS platforms commonly add beyond the 8
Scholara already has, for a deliberate decision rather than a silent gap:

- **Principal/Head of School** — an oversight-only role (read access across every module, no
  create/edit authority) distinct from `admin`'s full CRUD power. Common where the person running
  the school day-to-day isn't the same person doing data entry.
- **Front desk/receptionist** — visitor log, walk-in gate-pass approval; only relevant once gate
  passes (Phase 4, in progress) exist to approve.
- **Transport coordinator** — not in `docs/ROADMAP.md` at all (no vehicle/route data model
  exists); flagged for completeness, not recommended without a concrete ask, since it's a
  significant new subsystem with no current data to build on.

## Suggested build order (not yet started — pending your call)

Roughly highest-leverage first, grouped so related work lands together:

1. **Document management module** — closes three explicitly-named gaps at once (teaching notes,
   student-list import/export, medical dosage attachments) plus the generic staff-document and
   admission-document gaps. One `Document` model (polymorphic `documentable`), one controller,
   role-scoped upload/download, reused everywhere instead of bespoke per-feature upload code.
2. **PDF generation** (report cards, transcripts, payslips, receipts) — same underlying tool
   (`barryvdh/laravel-dompdf` or similar) serves every one of these at once.
3. **Timetable/scheduling** — new subsystem, meaningful modeling work (`Period`, room-clash
   detection), benefits teacher + admin + (eventually) parent/learner views.
4. **Library circulation** — turn the librarian role from generic inventory into real
   checkout/due-date/fine tracking.
5. Smaller, role-local items (leave management, fee structures, parent full-history views,
   dashboard KPI rollup) — can land opportunistically alongside the above rather than as their own
   phases.

Gate passes (Phase 4 in `docs/HARDENING_TODO.md`) continues in parallel — it was already queued
before this audit and doesn't conflict with anything above.
