<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Services\Academics\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Assessment::with(['subject', 'schoolClass'])->latest();

        // Admin sees every assessment at their school (Assessment's BelongsToSchool scope
        // already limits the query to that school); a teacher only has teaching assignments
        // to filter by, not a school-wide view, so this branch would otherwise show nothing.
        if (! $request->user()->hasRole('admin')) {
            $classIds = $request->user()->teacherSubjectAssignments()->pluck('school_class_id');
            $query->whereIn('school_class_id', $classIds);
        }

        return view('assessments.index', ['assessments' => $query->get()]);
    }

    public function create(Request $request): View
    {
        $assignments = $request->user()->teacherSubjectAssignments()->with(['subject', 'schoolClass'])->get();

        return view('assessments.create', compact('assignments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'type' => ['required', 'in:AoI,MOT,EOT'],
            'term' => ['required', 'string', 'max:50'],
            'max_score' => ['required', 'integer', 'min:1'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $assignment = $request->user()->teacherSubjectAssignments()->findOrFail($validated['assignment_id']);

        $assessment = Assessment::create([
            'subject_id' => $assignment->subject_id,
            'school_class_id' => $assignment->school_class_id,
            'type' => $validated['type'],
            'term' => $validated['term'],
            'max_score' => $validated['max_score'],
            'weight' => $validated['weight'] ?? 1,
        ]);

        return redirect()->route('assessments.show', $assessment)->with('status', 'Assessment created — enter scores below.');
    }

    public function show(Request $request, Assessment $assessment, GradingService $grading): View
    {
        $isAssigned = $request->user()->teacherSubjectAssignments()
            ->where('subject_id', $assessment->subject_id)
            ->where('school_class_id', $assessment->school_class_id)
            ->exists();

        abort_unless($isAssigned || $request->user()->hasRole('admin'), 403);

        $assessment->load(['subject', 'schoolClass.students' => fn ($q) => $q->orderBy('first_name')]);

        return view('assessments.show', [
            'assessment' => $assessment,
            'students' => $assessment->schoolClass->students,
            'existingScores' => $assessment->scores()->get()->keyBy('student_id'),
            'classMean' => $grading->classMeanFor($assessment),
        ]);
    }
}
