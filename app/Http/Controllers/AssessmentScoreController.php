<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Services\Academics\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssessmentScoreController extends Controller
{
    public function bulkStore(Request $request, Assessment $assessment, GradingService $grading): RedirectResponse
    {
        $isAssigned = $request->user()->teacherSubjectAssignments()
            ->where('subject_id', $assessment->subject_id)
            ->where('school_class_id', $assessment->school_class_id)
            ->exists();

        abort_unless($isAssigned, 403);

        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($validated['scores'] as $studentId => $rawScore) {
            if ($rawScore === null || $rawScore === '') {
                continue;
            }

            AssessmentScore::updateOrCreate(
                ['assessment_id' => $assessment->id, 'student_id' => $studentId],
                [
                    'raw_score' => $rawScore,
                    'scaled_score' => $grading->scaleScore((float) $rawScore, (float) $assessment->max_score),
                    'recorded_by' => $request->user()->id,
                    'recorded_at' => now(),
                ]
            );
        }

        return back()->with('status', 'Scores saved.');
    }
}
