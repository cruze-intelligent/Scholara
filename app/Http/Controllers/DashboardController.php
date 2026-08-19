<?php

namespace App\Http\Controllers;

use App\Models\ClinicVisit;
use App\Models\IncidentReport;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\LessonPlan;
use App\Models\Notice;
use App\Models\PayrollRun;
use App\Models\SchoolClass;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $user->getRoleNames()->first() ?? 'learner';

        $view = "dashboards.{$role}";

        return view($view, match ($role) {
            'admin' => $this->adminData($user),
            'teacher' => $this->teacherData($user),
            'parent' => $this->parentData($user),
            'learner' => $this->learnerData($user),
            'nurse' => $this->nurseData($user),
            'hr' => $this->hrData($user),
            'bursar' => $this->bursarData($user),
            'librarian' => $this->librarianData($user),
            default => [],
        });
    }

    private function adminData(User $user): array
    {
        $schoolId = $user->school_id;

        return [
            'studentCount' => Student::where('school_id', $schoolId)->count(),
            'staffCount' => StaffProfile::whereHas('user', fn ($q) => $q->where('school_id', $schoolId))->count(),
            'openIncidents' => IncidentReport::where('school_id', $schoolId)->where('status', '!=', 'resolved')->count(),
            'recentNotices' => Notice::where('school_id', $schoolId)->latest('published_at')->take(5)->get(),
        ];
    }

    private function teacherData(User $user): array
    {
        return [
            'classes' => SchoolClass::where('teacher_id', $user->id)->withCount('students')->get(),
            'upcomingLessonPlans' => LessonPlan::where('teacher_id', $user->id)
                ->whereDate('date', '>=', now())
                ->orderBy('date')
                ->take(5)
                ->get(),
        ];
    }

    private function parentData(User $user): array
    {
        $guardian = $user->guardian;
        $students = $guardian ? $guardian->students()->with(['assessmentScores.assessment', 'invoices'])->get() : collect();

        return ['students' => $students];
    }

    private function learnerData(User $user): array
    {
        $student = Student::where('user_id', $user->id)->with('assessmentScores.assessment.subject')->first();

        return [
            'student' => $student,
            'notices' => $student ? Notice::where('school_id', $student->school_id)->latest('published_at')->take(5)->get() : collect(),
        ];
    }

    private function nurseData(User $user): array
    {
        $schoolId = $user->school_id;

        return [
            'recentVisits' => ClinicVisit::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
                ->latest('occurred_at')->take(10)->get(),
        ];
    }

    private function hrData(User $user): array
    {
        $schoolId = $user->school_id;

        return [
            'staffCount' => StaffProfile::whereHas('user', fn ($q) => $q->where('school_id', $schoolId))->count(),
            'latestPayrollRun' => PayrollRun::where('school_id', $schoolId)->latest('period_end')->first(),
        ];
    }

    private function bursarData(User $user): array
    {
        $schoolId = $user->school_id;

        return [
            'unpaidTotal' => Invoice::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
                ->where('status', '!=', 'paid')->sum('amount_due'),
            'unpaidInvoices' => Invoice::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
                ->where('status', '!=', 'paid')->with('student')->take(10)->get(),
        ];
    }

    private function librarianData(User $user): array
    {
        $schoolId = $user->school_id;

        return [
            'items' => InventoryItem::where('school_id', $schoolId)->orderBy('quantity')->get(),
        ];
    }
}
