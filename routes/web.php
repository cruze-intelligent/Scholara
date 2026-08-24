<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentScoreController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClinicVisitController;
use App\Http\Controllers\DailyActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DGatewayWebhookController;
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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolSettingsController;
use App\Http\Controllers\StudentPhotoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WowMomentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// DGateway calls this directly, unauthenticated — verified by HMAC signature inside the
// controller instead of Laravel auth, and excluded from CSRF in bootstrap/app.php.
Route::post('/webhooks/dgateway', [DGatewayWebhookController::class, 'handle'])->name('webhooks.dgateway');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

    // User management — admin-only, scoped to their own school inside the controller.
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('school-settings', [SchoolSettingsController::class, 'edit'])->name('school-settings.edit');
        Route::put('school-settings', [SchoolSettingsController::class, 'update'])->name('school-settings.update');
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

    // Learner's own full-depth views — the dashboard only shows a five-item summary of each.
    Route::middleware('role:learner')->group(function () {
        Route::get('my/assessments', [LearnerController::class, 'assessments'])->name('learner.assessments');
        Route::get('my/attendance', [LearnerController::class, 'attendance'])->name('learner.attendance');
        Route::get('my/notices', [LearnerController::class, 'notices'])->name('learner.notices');
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
    });
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

    // Inventory / store
    Route::middleware('role:librarian|admin')->group(function () {
        Route::resource('inventory-items', InventoryItemController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::post('inventory-items/{inventoryItem}/transactions', [InventoryTransactionController::class, 'store'])
            ->name('inventory-items.transactions.store');
        Route::delete('inventory-items/{inventoryItem}/transactions/{transaction}', [InventoryTransactionController::class, 'void'])
            ->name('inventory-items.transactions.void');
    });

    // Nursery
    Route::middleware('role:teacher|nurse|admin')->group(function () {
        Route::resource('daily-activity-logs', DailyActivityLogController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('milestones', MilestoneChecklistController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::resource('wow-moments', WowMomentController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});

require __DIR__.'/auth.php';
