<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform-operator territory — a super_admin has no school_id, so BelongsToSchool's scope
 * never filters their queries (see that trait). Deliberately kept to aggregate/grouped queries
 * only (counts, sums), never a raw per-student browse — the same "never a raw all-students
 * query" discipline docs/COMPLIANCE.md asks of every other role, just self-imposed here since
 * there's no school_id to scope by.
 */
class SuperAdminController extends Controller
{
    public function schools(): View
    {
        $schools = School::withCount('students')
            ->with(['subscriptions' => fn ($q) => $q->latest('period_end')->take(1)])
            ->orderByRaw("status = 'pending_review' desc")
            ->orderBy('name')
            ->get();

        return view('super-admin.schools', compact('schools'));
    }

    public function approveSchool(School $school): RedirectResponse
    {
        abort_unless($school->status === 'pending_review', 422, 'Only a pending registration can be approved.');

        $school->update(['status' => 'trial', 'trial_ends_at' => now()->addDays(30)]);

        return back()->with('status', "{$school->name} approved — 30-day trial started.");
    }

    public function rejectSchool(School $school): RedirectResponse
    {
        abort_unless($school->status === 'pending_review', 422, 'Only a pending registration can be rejected.');

        $school->update(['status' => 'rejected']);

        return back()->with('status', "{$school->name} rejected.");
    }

    public function suspendSchool(School $school): RedirectResponse
    {
        $school->update(['status' => 'suspended']);

        return back()->with('status', "{$school->name} suspended.");
    }

    public function reactivateSchool(School $school): RedirectResponse
    {
        $school->update(['status' => 'active']);

        return back()->with('status', "{$school->name} reactivated.");
    }

    /**
     * Opens the next 90-day billing period for a school, priced off its current enrollment —
     * a manual step (see SchoolSubscription's doc comment on why there's no gateway here yet).
     */
    public function generateSubscription(School $school): RedirectResponse
    {
        $studentCount = Student::where('school_id', $school->id)->count();
        $lastPeriodEnd = $school->subscriptions()->latest('period_end')->first()?->period_end;
        $periodStart = $lastPeriodEnd ? $lastPeriodEnd->copy()->addDay() : today();

        SchoolSubscription::create([
            'school_id' => $school->id,
            'period_start' => $periodStart,
            'period_end' => $periodStart->copy()->addDays(SchoolSubscription::PERIOD_DAYS - 1),
            'student_count' => $studentCount,
            'rate_per_student' => SchoolSubscription::RATE_PER_STUDENT_UGX,
            'amount' => $studentCount * SchoolSubscription::RATE_PER_STUDENT_UGX,
        ]);

        return back()->with('status', 'Next billing period generated.');
    }

    public function markSubscriptionPaid(Request $request, SchoolSubscription $subscription): RedirectResponse
    {
        $subscription->update([
            'status' => 'paid',
            'paid_at' => now(),
            'marked_paid_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Marked as paid.');
    }

    public function activity(): View
    {
        return view('super-admin.activity', [
            'logs' => AuditLog::with(['user.school', 'auditable'])->latest()->paginate(30),
        ]);
    }
}
