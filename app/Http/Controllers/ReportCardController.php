<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Academics\GradingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Printable term report card — a PDF instead of a webpage, since a parent/bursar needs a
 * document they can hand someone, not just a URL. Composite per-subject score reuses
 * GradingService::compositeScore, the same MOT/EOT/AoI weighting the assessment screens use.
 */
class ReportCardController extends Controller
{
    public function show(Request $request, Student $student, GradingService $grading): Response
    {
        $this->authorizeView($request, $student);

        $scores = $student->assessmentScores()->with('assessment.subject')->get();
        $terms = $scores->pluck('assessment.term')->unique()->sort()->values();

        $term = $request->query('term', $terms->last());
        $termScores = $scores->filter(fn ($score) => $score->assessment->term === $term);

        $rows = $termScores->groupBy(fn ($score) => $score->assessment->subject->name)
            ->map(fn ($subjectScores) => [
                'subject' => $subjectScores->first()->assessment->subject->name,
                'composite' => $grading->compositeScore($subjectScores),
            ])
            ->sortBy('subject')
            ->values();

        $pdf = Pdf::loadView('report-cards.pdf', [
            'student' => $student,
            'term' => $term,
            'rows' => $rows,
            'terms' => $terms,
        ]);

        return $pdf->download("report-card-{$student->admission_no}.pdf");
    }

    private function authorizeView(Request $request, Student $student): void
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('teacher')) {
            $allowed = $user->teacherSubjectAssignments()->where('school_class_id', $student->school_class_id)->exists();
            abort_unless($allowed, 403);

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
}
