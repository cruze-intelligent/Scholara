<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Public — reachable from the login/register pages as well as the main nav, so a guest evaluating
 * the product and a logged-in user both land on the same page. Content is static on purpose (no
 * admin-editable FAQ builder was asked for); each role's section is written as if to that role
 * specifically, not a generic paragraph reused everywhere.
 */
class FaqController extends Controller
{
    public function index(): View
    {
        return view('faq.index', ['sections' => $this->sections()]);
    }

    /**
     * @return array<string, array<int, array{q: string, a: string}>>
     */
    private function sections(): array
    {
        return [
            'General' => [
                ['q' => 'What is Scholara?', 'a' => 'A school management system covering academics, attendance, health, HR/payroll, library, fees, and more — one system per school, with everyone (admin, staff, parents, learners) logging into the same app with a role-appropriate view.'],
                ['q' => "I forgot my password — what do I do?", 'a' => "Use \"Forgot your password?\" on the login page. A reset link is emailed to the address on your account. If you signed up with a phone number and no email, ask your school admin to reset it for you."],
                ['q' => 'Is my school\'s data visible to other schools?', 'a' => 'No. Every school\'s records are isolated — nobody outside your school, other than Scholara\'s own platform operators handling support or billing, can see your students, staff, or financial data.'],
                ['q' => 'Who do I contact for help?', 'a' => 'Use the email or WhatsApp icons at the bottom of any page.'],
            ],
            'Admin' => [
                ['q' => 'How do I add staff or enroll a student?', 'a' => 'Users → Add User for staff/learner accounts. For a student, use Students → Enroll — this also creates the guardian\'s login automatically, so there\'s no separate step to add a parent.'],
                ['q' => 'What are "distinction tags" like Class Teacher or Head Librarian?', 'a' => 'Optional extras on top of a staff member\'s base role (Teacher, Librarian, ...) that unlock a bit more access — e.g. a Class Teacher sees their whole homeroom\'s performance, not just their own subject. Toggle them from that person\'s Edit User page any time; they don\'t require recreating the account.'],
                ['q' => 'How does billing work?', 'a' => 'Every school gets a 30-day free trial from approval. After that, it\'s 3,000 UGX per enrolled student per 90-day period. See School Settings for your current status and billing history, and contact support to arrange payment — it\'s confirmed and applied by Scholara, not automated yet.'],
                ['q' => 'How do I add our school logo to documents?', 'a' => 'School Settings → School logo. It then appears automatically on every report card, payslip, and receipt generated from that point on.'],
            ],
            'Teacher' => [
                ['q' => 'How do I enter marks or take attendance?', 'a' => 'Assessments and Attendance, both under the Academics section of the menu — scoped to the classes/subjects you\'re assigned to teach.'],
                ['q' => 'A colleague changed classes — how do I update who\'s the Class Teacher?', 'a' => 'An admin toggles the Class Teacher tag on the Edit User page for whoever holds it now — it\'s independent of the base Teacher role, so this doesn\'t require recreating any account.'],
            ],
            'Parent' => [
                ['q' => 'How was my account created — I never signed up?', 'a' => "Parent accounts are created automatically when your child is enrolled. If you already had an account (e.g. from an older sibling), the same login is reused instead of creating a duplicate."],
                ['q' => 'How do I log in — email or phone?', 'a' => 'Either, whichever was registered for you. Type it into the same field on the login page.'],
                ['q' => 'How do I pay school fees?', 'a' => 'Open the invoice from your dashboard and use Pay — this goes through your school\'s own SchoolPay payment link, not through Scholara directly.'],
                ['q' => "How do I see my child's results?", 'a' => 'Your dashboard shows each child\'s recent scores; open their profile for the full performance history across every term.'],
            ],
            'Learner' => [
                ['q' => 'Where do I check my results?', 'a' => 'My Assessments, from the main menu.'],
                ['q' => 'Where\'s my attendance record?', 'a' => 'My Attendance, from the main menu.'],
            ],
            'Nurse' => [
                ['q' => 'How do I log a clinic visit or medication given?', 'a' => 'Clinic and eMAR, under the Health section. Medication administration requires all five checks (right patient, drug, dose, route, time) before it can be saved.'],
            ],
            'HR' => [
                ['q' => 'How do I run payroll?', 'a' => 'Payroll → New Run — generates a payslip per staff member from their salary on file, with PAYE/NSSF deducted automatically using the rates documented in School Settings/docs.'],
            ],
            'Bursar' => [
                ['q' => 'How do I record a fee payment?', 'a' => "Open the student's invoice and use Record Payment. A receipt PDF is generated automatically once it's confirmed."],
            ],
            'Librarian' => [
                ['q' => 'How do I issue or return a book?', 'a' => 'Library Loans → Issue a Book. Returns are logged from the same loan\'s page, which also calculates any overdue fine.'],
                ['q' => 'How do I add a new book to the catalogue?', 'a' => 'Inventory → New Item, category Library — author, ISBN, publisher, edition, and shelf location are all recorded there for identification.'],
            ],
            'Super Admin' => [
                ['q' => 'How do I approve a new school?', 'a' => 'Schools, under the platform menu — a pending registration shows Approve/Reject; approving starts its 30-day trial immediately.'],
                ['q' => 'How do I mark a school\'s subscription as paid?', 'a' => 'From the same Schools page, once payment is confirmed outside the system (bank transfer, mobile money, etc.), generate the billing period if needed and mark it paid.'],
            ],
        ];
    }
}
