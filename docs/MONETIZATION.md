# What it costs to run Scholara, and what to charge for it

*Internal planning — hosting & monetization*

**Prepared:** 25 Aug 2026 · **Deployment:** scholara-production-nujkni.laravel.cloud · **Status:** planning input, not a commitment

A working model for Laravel Cloud capacity and a suggested per-school subscription structure — free trial, then termly billing — built from the platform's actual published pricing rather than guesswork.

---

## 1. Where the app runs today

Scholara is on [Laravel Cloud](https://laravel.com/cloud), which prices everything on **usage** rather than a fixed server rental — compute only accrues cost while a request is being processed, and idle apps can scale to zero. A staging deployment with no real traffic effectively costs close to nothing beyond the plan floor. That's the right place to be during the current pilot phase, and it means the jump from "free" to "serving paying schools" is gradual, not a cliff.

### The four plans

| Plan | Price | Highlights |
|---|---|---|
| **Starter** *(pilot)* | $5/mo + usage · first month free | $5 usage credits included · scale-to-zero Flex compute · unlimited apps/environments · 10 custom domains · 1-day log retention |
| **Growth** *(where paying schools start)* | $20/mo + usage | Pro compute, autoscale to 10× · preview environments · 10 managed queues/env · team roles & permissions · 7-day log retention |
| **Business** *(once revenue justifies it)* | $200/mo + usage | Unlimited + scheduled autoscale · advanced WAF · largest compute sizes · 30-day log retention |
| **Enterprise** *(national / multi-country scale)* | Custom | Dedicated compute · private networking, VPC peering · 24/7 incident response · dedicated account manager |

### What the meter actually charges for

| Resource | Approx. rate | Notes |
|---|---|---|
| Flex compute, 1 vCPU / 512MB | ≈ $6.40/mo run continuously | Scale-to-zero — pay only while awake |
| Flex compute, 1 vCPU / 1GB | ≈ $10.20/mo run continuously | Realistic floor once real users log in daily |
| Pro compute, 1 vCPU / 2GB | ≈ $25.70/mo run continuously | Growth-tier default; autoscales under load |
| Serverless Postgres compute | $0.16/hr, 0.25 vCPU min | Also scales to zero when idle |
| Postgres storage | ≈ $1.50/GB/mo | Student/assessment/health data — small even at scale (a few GB for thousands of students) |
| Bandwidth | Included allowance scales with compute size | A 1GB compute instance earns ~100GB transfer/mo — comfortable for a Blade app with no video/large media |

> **⚠ Verify before budgeting.** These figures come from Laravel's public pricing page and third-party pricing breakdowns as of August 2026, not from Scholara's own billing history — the account has no paying traffic yet. Laravel Cloud also publishes a [live pricing calculator](https://pricingcalculator.laravel.cloud/); run the real numbers there once there's a usage pattern to plug in, the same way this app already flags PAYE/NSSF rates and grading weights as "documented defaults to verify," not researched facts.

---

## 2. Realistic capacity per plan

A school SIS is **bursty, not constant** — traffic spikes around morning attendance, assessment entry after exams, and fee-payment windows, then goes quiet. That's a good fit for scale-to-zero pricing, and it means "concurrent users" matters more than "total accounts." The estimates below assume typical Laravel CRUD behavior (no video, no heavy background processing beyond notifications/PDFs) — they are engineering judgment, not a load test against this specific app.

| Plan | Schools | Peak concurrent users | Suited for |
|---|---|---|---|
| Starter | 1–3 | ~20–30 | The current pilot: one or two schools proving the product works |
| Growth | 10–30 | ~150–300 | First paying cohort — autoscaling absorbs the morning-attendance spike across schools |
| Business | 50–150+ | ~800–1,500 | A real regional operation, several hundred to low-thousands of students total |
| Enterprise | 150+ | Load-tested per contract | Multi-country or a national ministry deployment |

**The real ceiling won't be compute.** Given how the schema is built today (see `docs/ROADMAP.md`'s multi-tenancy note), every school shares one application and one database with school-scoped queries. That's fine well past the Growth tier — the constraint that shows up first is usually the database connection pool and query plan under multi-school load, not raw CPU. Worth a real load test before signing a contract that promises a specific school count.

---

## 3. Suggested subscription structure

As requested: every school gets a **free 30-day trial**, full feature access, no card required to start. Billing then runs **per term (90 days)**, matching the East African school calendar rather than a generic monthly SaaS cycle — a bursar budgets per term, not per month.

### Who's billed: the school, not its users

Scholara has exactly **one customer per deployment: the school, represented by its admin account.** One subscription — one invoice, one renewal, one signatory — covers every role at that school: admin, teachers, bursar, nurse, librarian, HR, parents, and learners. Nobody else is ever billed separately, and no role's access to Scholara itself depends on a personal payment.

That's a different thing from **fee collection**, which is a service the school runs *through* Scholara for its own money, not Scholara's: bursars raise invoices, and parents pay school fees via SchoolPay/DGateway integration and see payment status/receipts and notices. Scholara facilitates that flow and takes no cut of it — it's the school's money moving from parent to school, with Scholara just providing the access and the paper trail. Keeping these two flows distinct (subscription vs. fee collection) matters for both message design (parents should never think they're paying *Scholara*) and future billing engineering (subscription billing only ever needs to know about one payer per school).

Pricing below is tiered by enrollment, priced in UGX with a USD reference for the "global" ambition. **Suggested starting point** — this is a defensible first number, not market research; see the checklist in §5 for how to pressure-test it.

| Tier | Enrollment | Price | Includes |
|---|---|---|---|
| **Starter** | Up to 150 students | 150,000 UGX/term · ≈ $40 | All core modules · 1 admin + unlimited staff/parent logins · email support |
| **Growth** *(where most schools land)* | 151–500 students | 450,000 UGX/term · ≈ $120 | Everything in Starter · trend reports, PDF report cards · priority email support |
| **Established** | 501–1,500 students | 1,200,000 UGX/term · ≈ $320 | Everything in Growth · multi-campus support · phone + email support |
| **Enterprise** | 1,500+ / multi-campus | Custom | Dedicated onboarding · custom SLA · volume discount across campuses |

### Or, a per-student alternative

If flat tiers feel arbitrary to a prospective school, the same revenue lands at roughly **UGX 900–1,000 per student per term** — simple to explain, scales naturally, and a school can estimate their own cost before ever talking to sales. Worth A/B-ing both framings with the first few prospects rather than deciding from a desk.

---

## 4. Does it actually cover hosting?

A rough breakeven check, using the Starter-tier pricing grid above against the hosting cost from §1–2. Figures assume an even mix of school sizes.

| Paying schools | Cloud plan needed | Est. hosting/mo | Est. revenue/mo | Margin |
|---|---|---|---|---|
| 3 | Starter | ~$15 | ~$120 | Healthy |
| 15 | Growth | ~$60 | ~$600 | Healthy |
| 60 | Business | ~$350 | ~$2,400 | Healthy |

Hosting is a small fraction of revenue at every stage shown — the real cost driver as this grows is **support and onboarding time**, not infrastructure. That's normal for SaaS; it's just worth knowing the bottleneck won't be the server bill.

---

## 5. Before locking in numbers

- Run the actual figures through [Laravel Cloud's calculator](https://pricingcalculator.laravel.cloud/) once there's a real traffic pattern from the pilot school(s).
- Ask 3–5 real Ugandan private schools what they currently pay for any SIS/records tooling (even a spreadsheet-adjacent paid service) before finalizing the grid in §3 — this document's pricing is a defensible starting point, not validated willingness-to-pay.
- Decide the multi-tenancy model (shared DB with school-scoping, as today, vs. per-school database) before the school count in §2 gets tested for real — it's cheaper to decide this at 10 schools than at 100.
- Load-test one school's peak morning-attendance window before promising a specific concurrent-user number to a paying customer.

---

*Sources: Laravel Cloud's published pricing page and pricing calculator (laravel.com/cloud/pricing, pricingcalculator.laravel.cloud), accessed 25 Aug 2026. School pricing and capacity figures are Scholara-specific estimates, not independently verified market data.*
