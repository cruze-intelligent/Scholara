<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentTag;
use App\Services\Academics\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A staff-facing student directory + profile — per feedback that staff need to actually look a
 * student up by name/admission number (previously every module had its own scattered student
 * picker, with no single "here's everything about this student" page), and that different roles
 * should be able to flag a student from their own perspective (a librarian marking overdue-book
 * defaulters, etc) rather than only ever editing a health record or an invoice in isolation.
 */
class StudentController extends Controller
{
    private const STAFF_ROLES = ['admin', 'teacher', 'nurse', 'bursar', 'librarian', 'hr'];

    /**
     * Which role can attach which tag — kept as an explicit map rather than free text, so a
     * defaulter flag always means the same thing everywhere it's shown.
     */
    private const TAGS_BY_ROLE = [
        'librarian' => ['library_defaulter' => 'Library defaulter (overdue book/fine)'],
        'bursar' => ['fee_defaulter' => 'Fee defaulter'],
        'nurse' => ['medical_alert' => 'Medical alert'],
        'teacher' => ['academic_concern' => 'Academic concern'],
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(self::STAFF_ROLES), 403);

        $search = $request->query('search');

        $students = Student::with('schoolClass')
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('admission_no', 'like', "%{$search}%")
            ))
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('students.index', compact('students', 'search'));
    }

    public function show(Request $request, Student $student, GradingService $grading): View
    {
        $this->authorizeView($request, $student);

        $student->load(['schoolClass', 'tags.taggedBy']);

        $availableTags = collect();
        foreach (self::TAGS_BY_ROLE as $role => $roleTags) {
            if ($request->user()->hasRole($role) || $request->user()->hasRole('admin')) {
                $availableTags = $availableTags->merge($roleTags);
            }
        }

        $performance = $this->canSeeAnalytics($request, $student) ? $this->performanceOverTime($student, $grading) : null;

        return view('students.show', [
            'student' => $student,
            'availableTags' => $availableTags,
            'performance' => $performance,
        ]);
    }

    public function storeTag(Request $request, Student $student): RedirectResponse
    {
        $user = $request->user();
        $roleTags = collect();
        foreach (self::TAGS_BY_ROLE as $role => $tags) {
            if ($user->hasRole($role) || $user->hasRole('admin')) {
                $roleTags = $roleTags->merge($tags);
            }
        }

        $validated = $request->validate([
            'tag' => ['required', 'in:'.$roleTags->keys()->implode(',')],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        StudentTag::create([
            'student_id' => $student->id,
            'tag' => $validated['tag'],
            'note' => $validated['note'] ?? null,
            'tagged_by' => $user->id,
        ]);

        return back()->with('status', 'Tag added.');
    }

    public function destroyTag(Request $request, StudentTag $tag): RedirectResponse
    {
        abort_unless($tag->tagged_by === $request->user()->id || $request->user()->hasRole('admin'), 403);

        $tag->delete();

        return back()->with('status', 'Tag removed.');
    }

    private function authorizeView(Request $request, Student $student): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(self::STAFF_ROLES)) {
            return;
        }

        if ($user->hasRole('parent')) {
            abort_unless($user->guardian?->students->contains($student->id), 403);

            return;
        }

        if ($user->hasRole('learner')) {
            abort_unless($student->user_id === $user->id, 403);

            return;
        }

        abort(403);
    }

    private function canSeeAnalytics(Request $request, Student $student): bool
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('teacher')) {
            return $user->teacherSubjectAssignments()->where('school_class_id', $student->school_class_id)->exists();
        }

        if ($user->hasRole('parent')) {
            return $user->guardian?->students->contains($student->id) ?? false;
        }

        if ($user->hasRole('learner')) {
            return $student->user_id === $user->id;
        }

        return false;
    }

    /**
     * A student's composite score per subject, per term, across every term on record — "track
     * one child's performance over the years" rather than only the current term's raw scores.
     */
    private function performanceOverTime(Student $student, GradingService $grading): array
    {
        $scores = $student->assessmentScores()->with('assessment.subject')->get();
        $terms = $scores->pluck('assessment.term')->unique()->sort()->values();

        return $terms->map(function ($term) use ($scores, $grading) {
            $termScores = $scores->filter(fn ($score) => $score->assessment->term === $term);

            $bySubject = $termScores->groupBy(fn ($score) => $score->assessment->subject->name)
                ->map(fn ($subjectScores) => $grading->compositeScore($subjectScores));

            return [
                'term' => $term,
                'average' => $bySubject->filter()->isNotEmpty() ? round($bySubject->filter()->avg(), 2) : null,
                'subjects' => $bySubject,
            ];
        })->values()->all();
    }
}
