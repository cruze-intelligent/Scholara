<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentScoreController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClinicVisitController;
use App\Http\Controllers\DailyActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthRecordController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\MedicationAdministrationController;
use App\Http\Controllers\MilestoneChecklistController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WowMomentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Academics — teachers create/mark; admins can also view assessments.
    Route::middleware('role:teacher|admin')->group(function () {
        Route::resource('assessments', AssessmentController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('assessments/{assessment}/scores', [AssessmentScoreController::class, 'bulkStore'])
            ->name('assessments.scores.store');

        Route::get('attendance', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance/stats', [AttendanceController::class, 'stats'])->name('attendance.stats');
    });

    // Noticeboard — admin/teacher author, everyone reads via the dashboard.
    Route::middleware('role:admin|teacher')->group(function () {
        Route::resource('notices', NoticeController::class)->only(['index', 'create', 'store']);
        Route::patch('notices/{notice}/publish', [NoticeController::class, 'publish'])->name('notices.publish');
    });

    // Issue reporting — anyone can file/view within their own scope; only
    // staff triage (status/assignment).
    Route::resource('incidents', IncidentReportController::class)->only(['index', 'create', 'store']);
    Route::middleware('role:admin|teacher|nurse')->group(function () {
        Route::patch('incidents/{incident}/status', [IncidentReportController::class, 'updateStatus'])
            ->name('incidents.status');
    });

    // Nurse portal
    Route::middleware('role:nurse|admin')->group(function () {
        Route::get('students/{student}/health-record', [HealthRecordController::class, 'edit'])
            ->name('health-records.edit');
        Route::put('students/{student}/health-record', [HealthRecordController::class, 'update'])
            ->name('health-records.update');

        Route::resource('medications', MedicationAdministrationController::class)->only(['index', 'create', 'store']);
        Route::resource('clinic-visits', ClinicVisitController::class)->only(['index', 'create', 'store']);
    });

    // HR / Payroll
    Route::middleware('role:hr|admin')->group(function () {
        Route::resource('payroll-runs', PayrollRunController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('payroll-runs/{payrollRun}/generate', [PayrollRunController::class, 'generate'])
            ->name('payroll-runs.generate');
    });

    // Inventory / store
    Route::middleware('role:librarian|admin')->group(function () {
        Route::resource('inventory-items', InventoryItemController::class)->only(['index', 'create', 'store']);
        Route::post('inventory-items/{inventoryItem}/transactions', [InventoryTransactionController::class, 'store'])
            ->name('inventory-items.transactions.store');
    });

    // Nursery
    Route::middleware('role:teacher|nurse|admin')->group(function () {
        Route::resource('daily-activity-logs', DailyActivityLogController::class)->only(['index', 'create', 'store']);
        Route::resource('milestones', MilestoneChecklistController::class)->only(['index', 'create', 'store']);
        Route::resource('wow-moments', WowMomentController::class)->only(['index', 'create', 'store']);
    });
});

require __DIR__.'/auth.php';
