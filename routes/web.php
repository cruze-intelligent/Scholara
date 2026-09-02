<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentScoreController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BookLoanController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ClinicVisitController;
use App\Http\Controllers\DailyActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DGatewayWebhookController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GatePassController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoicePaymentController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\MedicationAdministrationController;
use App\Http\Controllers\MilestoneChecklistController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\PinnedItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\SchoolStatusController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentCsvController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\StudentPhotoController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WowMomentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public — reachable from the login/register pages as well as the main nav once logged in.
Route::get('/faq', [FaqController::class, 'index'])->name('faq');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'school.approved'])
    ->name('dashboard');

// DGateway calls this directly, unauthenticated — verified by HMAC signature inside the
// controller instead of Laravel auth, and excluded from CSRF in bootstrap/app.php.
Route::post('/webhooks/dgateway', [DGatewayWebhookController::class, 'handle'])->name('webhooks.dgateway');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Deliberately outside school.approved — this is the page a blocked school's users land on.
    Route::get('school-status', [SchoolStatusController::class, 'show'])->name('school-status.show');

    // Every role's own audit trail — what they personally did, not the whole school's.
    Route::get('my-activity', [ActivityLogController::class, 'index'])->name('activity-log.index');
});

Route::middleware(['auth', 'verified', 'school.approved'])->group(function () {
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

    // Dashboard pins — every role, scoped to the acting user's own pins inside the controller.
    Route::post('pins/{key}', [PinnedItemController::class, 'store'])->name('pins.store')->where('key', '.*');
    Route::delete('pins/{key}', [PinnedItemController::class, 'destroy'])->name('pins.destroy')->where('key', '.*');

    // Academic calendar — readable by every role, admin-authored.
    Route::get('calendar', [CalendarEventController::class, 'index'])->name('calendar.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('calendar/create', [CalendarEventController::class, 'create'])->name('calendar.create');
        Route::post('calendar', [CalendarEventController::class, 'store'])->name('calendar.store');
        Route::get('calendar/{calendarEvent}/edit', [CalendarEventController::class, 'edit'])->name('calendar.edit');
        Route::put('calendar/{calendarEvent}', [CalendarEventController::class, 'update'])->name('calendar.update');
        Route::delete('calendar/{calendarEvent}', [CalendarEventController::class, 'destroy'])->name('calendar.destroy');
    });

    // User management — admin-only, scoped to their own school inside the controller.
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('students/export', [StudentCsvController::class, 'export'])->name('students.export');
        Route::get('students/import', [StudentCsvController::class, 'importCreate'])->name('students.import');
        Route::post('students/import', [StudentCsvController::class, 'importStore'])->name('students.import.store');
        Route::get('school-settings', [SchoolSettingsController::class, 'edit'])->name('school-settings.edit');
        Route::put('school-settings', [SchoolSettingsController::class, 'update'])->name('school-settings.update');
        Route::get('streams', [StreamController::class, 'index'])->name('streams.index');
        Route::post('streams', [StreamController::class, 'store'])->name('streams.store');
        Route::delete('streams/{stream}', [StreamController::class, 'destroy'])->name('streams.destroy');
    });

    // Student photo — admin (any student, own school) or a parent (their own children only);
    // ownership is checked inside the controller since the two roles have different scopes.
    Route::middleware('role:admin|parent')->group(function () {
        Route::post('students/{student}/photo', [StudentPhotoController::class, 'update'])->name('students.photo.update');
    });

    // Academics — teachers create/mark; admins can also view assessments.
    Route::middleware('role:teacher|admin')->group(function () {
        Route::resource('assessments', AssessmentController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('assessments/{assessment}/scores', [AssessmentScoreController::class, 'bulkStore'])
            ->name('assessments.scores.store');

        Route::get('attendance', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance/stats', [AttendanceController::class, 'stats'])->name('attendance.stats');

        Route::get('reports/academics', [ReportController::class, 'academics'])->name('reports.academics');
    });

    // Timetable — any authenticated role can view (scoped inside the controller); only admin
    // schedules/removes periods.
    Route::get('periods', [PeriodController::class, 'index'])->name('periods.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('periods/create', [PeriodController::class, 'create'])->name('periods.create');
        Route::post('periods', [PeriodController::class, 'store'])->name('periods.store');
        Route::delete('periods/{period}', [PeriodController::class, 'destroy'])->name('periods.destroy');
    });

    // Report card PDF — admin/assigned teacher/own-child parent/self learner (checked inside
    // the controller, since visibility genuinely differs per role here).
    Route::get('students/{student}/report-card', [ReportCardController::class, 'show'])->name('students.report-card');

    // Student directory/profile — staff can look any student up by name/admission number; a
    // guardian/learner can view their own profile page too (checked inside the controller).
    // Tagging (defaulter flags etc.) is role-scoped per StudentController::TAGS_BY_ROLE.
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::post('students/{student}/tags', [StudentController::class, 'storeTag'])->name('students.tags.store');
    Route::delete('student-tags/{tag}', [StudentController::class, 'destroyTag'])->name('students.tags.destroy');

    // Enrollment — admin-only. Creating a student auto-provisions its guardian's login (see
    // StudentController); editing an existing student was previously not possible anywhere.
    Route::middleware('role:admin')->group(function () {
        Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('students', [StudentController::class, 'store'])->name('students.store');
        Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::post('students/{student}/guardians', [StudentController::class, 'storeGuardian'])->name('students.guardians.store');
    });

    // Fee payments — guardian self-serve checkout (card or mobile money via DGateway).
    // Ownership of the invoice's student is checked in the controller, not just the role.
    Route::middleware('role:parent')->group(function () {
        Route::get('invoices/{invoice}/pay', [InvoicePaymentController::class, 'create'])->name('invoices.pay');
        Route::post('invoices/{invoice}/pay', [InvoicePaymentController::class, 'store'])->name('invoices.pay.store');
        Route::get('invoices/{invoice}/payments/{payment}', [InvoicePaymentController::class, 'status'])
            ->name('invoices.pay.status');
        Route::get('invoices/{invoice}/payments/{payment}/check', [InvoicePaymentController::class, 'statusCheck'])
            ->name('invoices.pay.status-check');
    });

    // Financial center — bursar manages invoices and records cash/bank payments; card/mobile
    // money is the guardian's own self-serve checkout above.
    Route::middleware('role:bursar|admin')->group(function () {
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])
            ->name('invoices.record-payment');
    });

    // Receipt PDF — bursar/admin or the paying guardian (checked inside the controller).
    Route::get('invoices/{invoice}/payments/{payment}/receipt', [InvoiceController::class, 'receipt'])
        ->name('invoices.payments.receipt');

    // Learner's own full-depth views — the dashboard only shows a five-item summary of each.
    Route::middleware('role:learner')->group(function () {
        Route::get('my/assessments', [LearnerController::class, 'assessments'])->name('learner.assessments');
        Route::get('my/attendance', [LearnerController::class, 'attendance'])->name('learner.attendance');
        Route::get('my/notices', [LearnerController::class, 'notices'])->name('learner.notices');
    });

    // Teaching resources — any authenticated role can view/download (scoped inside the
    // controller to the classes they teach/their own children/their own class); only
    // teacher/admin can upload or delete.
    Route::get('resources', [ResourceController::class, 'index'])->name('resources.index');
    Route::get('resources/{resource}/download', [ResourceController::class, 'download'])->name('resources.download');
    Route::middleware('role:teacher|admin')->group(function () {
        Route::get('resources/create', [ResourceController::class, 'create'])->name('resources.create');
        Route::post('resources', [ResourceController::class, 'store'])->name('resources.store');
        Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');
    });

    // Noticeboard — admin/teacher author, everyone reads via the dashboard.
    Route::middleware('role:admin|teacher')->group(function () {
        Route::resource('notices', NoticeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::patch('notices/{notice}/publish', [NoticeController::class, 'publish'])->name('notices.publish');
    });

    // Issue reporting — anyone can file/view within their own scope; only
    // staff triage (status/assignment).
    Route::resource('incidents', IncidentReportController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::middleware('role:admin|teacher|nurse')->group(function () {
        Route::patch('incidents/{incident}/status', [IncidentReportController::class, 'updateStatus'])
            ->name('incidents.status');
    });
    Route::middleware('role:admin')->group(function () {
        Route::delete('incidents/{incident}', [IncidentReportController::class, 'destroy'])->name('incidents.destroy');
    });

    // Nurse portal
    Route::middleware('role:nurse|admin')->group(function () {
        Route::get('students/{student}/health-record', [HealthRecordController::class, 'edit'])
            ->name('health-records.edit');
        Route::put('students/{student}/health-record', [HealthRecordController::class, 'update'])
            ->name('health-records.update');

        Route::resource('medications', MedicationAdministrationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('clinic-visits', ClinicVisitController::class)->only(['index', 'create', 'store', 'edit', 'update']);

        Route::get('reports/health', [ReportController::class, 'health'])->name('reports.health');

        Route::post('students/{student}/documents', [DocumentController::class, 'studentStore'])->name('students.documents.store');
    });

    // Medical dosage/prescription attachments on a student's record — read access also extends
    // to the child's own parent (checked inside the controller), so index isn't nurse/admin-only.
    Route::get('students/{student}/documents', [DocumentController::class, 'studentIndex'])->name('students.documents.index');

    // Staff document repository (contracts, certificates) — hr/admin manage; a staff member can
    // view their own file (checked inside the controller).
    Route::get('users/{user}/documents', [DocumentController::class, 'staffIndex'])->name('users.documents.index');
    Route::middleware('role:hr|admin')->group(function () {
        Route::post('users/{user}/documents', [DocumentController::class, 'staffStore'])->name('users.documents.store');
    });

    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::middleware('role:admin')->group(function () {
        Route::delete('medications/{medication}', [MedicationAdministrationController::class, 'destroy'])->name('medications.destroy');
        Route::delete('clinic-visits/{clinic_visit}', [ClinicVisitController::class, 'destroy'])->name('clinic-visits.destroy');
    });

    // HR / Payroll
    Route::middleware('role:hr|admin')->group(function () {
        Route::resource('payroll-runs', PayrollRunController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::post('payroll-runs/{payrollRun}/generate', [PayrollRunController::class, 'generate'])
            ->name('payroll-runs.generate');
    });

    // Payslip PDF — hr/admin or the staff member it belongs to (checked inside the controller).
    Route::get('payroll-runs/{payrollRun}/payslips/{payslip}/pdf', [PayrollRunController::class, 'payslipPdf'])
        ->name('payroll-runs.payslips.pdf');

    // Inventory / store
    Route::middleware('role:librarian|admin')->group(function () {
        Route::resource('inventory-items', InventoryItemController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::post('inventory-items/{inventoryItem}/transactions', [InventoryTransactionController::class, 'store'])
            ->name('inventory-items.transactions.store');
        Route::delete('inventory-items/{inventoryItem}/transactions/{transaction}', [InventoryTransactionController::class, 'void'])
            ->name('inventory-items.transactions.void');

        Route::get('book-loans/create', [BookLoanController::class, 'create'])->name('book-loans.create');
        Route::post('book-loans', [BookLoanController::class, 'store'])->name('book-loans.store');
        Route::patch('book-loans/{loan}/return', [BookLoanController::class, 'returnBook'])->name('book-loans.return');
    });

    // Library loan history — librarian/admin see everything; a guardian/learner sees only their
    // own (checked inside the controller).
    Route::get('book-loans', [BookLoanController::class, 'index'])->name('book-loans.index');

    // Gate passes — anyone can request/view their own scope (checked in the controller);
    // approve/depart/return restricted to admin/teacher.
    Route::get('gate-passes', [GatePassController::class, 'index'])->name('gate-passes.index');
    Route::get('gate-passes/create', [GatePassController::class, 'create'])->name('gate-passes.create');
    Route::post('gate-passes', [GatePassController::class, 'store'])->name('gate-passes.store');
    Route::middleware('role:admin|teacher')->group(function () {
        Route::patch('gate-passes/{gatePass}/approve', [GatePassController::class, 'approve'])->name('gate-passes.approve');
        Route::patch('gate-passes/{gatePass}/depart', [GatePassController::class, 'depart'])->name('gate-passes.depart');
        Route::patch('gate-passes/{gatePass}/return', [GatePassController::class, 'returned'])->name('gate-passes.return');
    });

    // Nursery
    Route::middleware('role:teacher|nurse|admin')->group(function () {
        Route::resource('daily-activity-logs', DailyActivityLogController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('milestones', MilestoneChecklistController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('wow-moments', WowMomentController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});

// Platform-operator territory — not nested under school.approved (a super_admin has no school
// for that check to apply to; EnsureSchoolApproved already bypasses the role unconditionally).
Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('schools', [SuperAdminController::class, 'schools'])->name('schools');
    Route::post('schools/{school}/approve', [SuperAdminController::class, 'approveSchool'])->name('schools.approve');
    Route::post('schools/{school}/reject', [SuperAdminController::class, 'rejectSchool'])->name('schools.reject');
    Route::post('schools/{school}/suspend', [SuperAdminController::class, 'suspendSchool'])->name('schools.suspend');
    Route::post('schools/{school}/reactivate', [SuperAdminController::class, 'reactivateSchool'])->name('schools.reactivate');
    Route::post('schools/{school}/subscriptions', [SuperAdminController::class, 'generateSubscription'])->name('schools.subscriptions.generate');
    Route::post('subscriptions/{subscription}/mark-paid', [SuperAdminController::class, 'markSubscriptionPaid'])->name('subscriptions.mark-paid');
    Route::get('activity', [SuperAdminController::class, 'activity'])->name('activity');
});

require __DIR__.'/auth.php';
