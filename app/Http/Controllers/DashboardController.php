<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\ClinicVisit;
use App\Models\IncidentReport;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\LessonPlan;
use App\Models\Notice;
use App\Models\PayrollRun;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSubscription;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\User;
use App\Services\Analytics\PerformancePredictor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $user->load('pinnedItems');
        $role = $user->getRoleNames()->first() ?? 'learner';

        $view = "dashboards.{$role}";

        $data = match ($role) {
            'admin' => $this->adminData($user),
            'teacher' => $this->teacherData($user),
            'parent' => $this->parentData($user),
            'learner' => $this->learnerData($user),
            'nurse' => $this->nurseData($user),
            'hr' => $this->hrData($user),
            'bursar' => $this->bursarData($user),
            'librarian' => $this->librarianData($user),
            'super_admin' => $this->superAdminData(),
            default => [],
        };

        // Pinned-calendar preview, shared across every role's dashboard (see dashboards._pinned)
        // — skipped for super_admin, who has no single school for it to make sense against.
        if ($role !== 'super_admin') {
            $data['upcomingEvents'] = CalendarEvent::where('start_date', '>=', today())
                ->orderBy('start_date')->take(5)->get();
        }

        return view($view, $data);
    }

    private function adminData(User $user): array
    {
        $schoolId = $user->school_id;

        return [
            'studentCount' => Student::where('school_id', $schoolId)->count(),
            'staffCount' => StaffProfile::whereHas('user', fn ($q) => $q->where('school_id', $schoolId))->count(),
            'openIncidents' => IncidentReport::where('status', '!=', 'resolved')->count(),
            'recentNotices' => Notice::latest('published_at')->take(5)->get(),
        ];
    }

    private function teacherData(User $user): array
    {
        $classes = SchoolClass::where('teacher_id', $user->id)->withCount('students')->get();

        $upcomingLessonPlans = LessonPlan::where('teacher_id', $user->id)
            ->whereDate('date', '>=', now())
            ->orderBy('date')
            ->take(5)
            ->get();

        $assignments = $user->teacherSubjectAssignments()->with(['subject', 'schoolClass.students'])->get();

        return [
            'classes' => $classes,
            'upcomingLessonPlans' => $upcomingLessonPlans,
            'supportAlerts' => $this->supportStrategyAlerts($assignments),
        ];
    }

    private function parentData(User $user): array
    {
        $guardian = $user->guardian;
        $students = $guardian ? $guardian->students()->with(['assessmentScores.assessment.subject', 'invoices'])->get() : collect();

        $students->each(fn ($student) => $student->subjectPredictions = $this->subjectPredictions($student));

        return ['students' => $students];
    }

    private function learnerData(User $user): array
    {
        $student = Student::where('user_id', $user->id)->with('assessmentScores.assessment.subject')->first();

        return [
            'student' => $student,
            'subjectPredictions' => $student ? $this->subjectPredictions($student) : collect(),
            'notices' => $student ? Notice::latest('published_at')->take(5)->get() : collect(),
        ];
    }

    private function nurseData(User $user): array
    {
        return [
            'recentVisits' => ClinicVisit::whereHas('student', fn ($q) => $q->where('school_id', $user->school_id))
                ->latest('occurred_at')->take(10)->get(),
        ];
    }

    private function hrData(User $user): array
    {
        $schoolId = $user->school_id;

        return [
            'staffCount' => StaffProfile::whereHas('user', fn ($q) => $q->where('school_id', $schoolId))->count(),
            'latestPayrollRun' => PayrollRun::latest('period_end')->first(),
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
        return [
            'items' => InventoryItem::orderBy('quantity')->get(),
        ];
    }

    /**
     * Aggregate/grouped only — see SuperAdminController's class doc comment for why this never
     * runs a raw per-student query despite a super_admin's school_id being null (which would
     * otherwise leave BelongsToSchool's scope wide open).
     */
    private function superAdminData(): array
    {
        return [
            'schoolCounts' => School::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'totalStudents' => Student::count(),
            'totalSchools' => School::count(),
            'pendingSchools' => School::where('status', 'pending_review')->latest()->take(5)->get(),
            'revenueThisTerm' => SchoolSubscription::where('status', 'paid')
                ->where('period_start', '<=', today())->where('period_end', '>=', today())
                ->sum('amount'),
        ];
    }

    /**
     * A student's predicted-trend per subject they have scores in, for the
     * parent/learner dashboards — thin wrapper around PerformancePredictor.
     */
    private function subjectPredictions(Student $student): Collection
    {
        $predictor = app(PerformancePredictor::class);

        return $student->assessmentScores->pluck('assessment.subject')->filter()->unique('id')
            ->map(fn ($subject) => [
                'subject' => $subject,
                'predicted' => $predictor->predictForStudentSubject($student, $subject),
            ])
            ->values();
    }

    /**
     * Support Strategy alerts (docs/ARCHITECTURE.md) for every student a
     * teacher's subject/class assignments cover — flags students whose
     * predicted performance has dropped notably below their own baseline
     * or the class mean.
     */
    private function supportStrategyAlerts(Collection $assignments): array
    {
        $predictor = app(PerformancePredictor::class);
        $alerts = [];

        foreach ($assignments as $assignment) {
            $subject = $assignment->subject;
            $students = $assignment->schoolClass->students;

            $classMean = $students
                ->map(fn ($student) => $predictor->predictForStudentSubject($student, $subject))
                ->filter()
                ->avg();

            if ($classMean === null) {
                continue;
            }

            foreach ($students as $student) {
                $scores = $student->assessmentScores()
                    ->whereHas('assessment', fn ($q) => $q->where('subject_id', $subject->id))
                    ->orderByDesc('recorded_at')
                    ->pluck('scaled_score')
                    ->filter()
                    ->map(fn ($v) => (float) $v)
                    ->all();

                if (count($scores) < 2) {
                    continue;
                }

                $baseline = array_sum($scores) / count($scores);
                $predicted = $predictor->predict($scores);

                if ($predictor->needsSupportStrategyAlert($predicted, $baseline, $classMean)) {
                    $alerts[] = [
                        'student' => $student,
                        'subject' => $subject,
                        'predicted' => round($predicted, 1),
                        'baseline' => round($baseline, 1),
                    ];
                }
            }
        }

        return $alerts;
    }
}
