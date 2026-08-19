<?php

namespace App\Services\Analytics;

use App\Models\Student;
use App\Models\Subject;

/**
 * Implements the plan's weighted moving average model:
 *
 *   P_pred = Σ (1-λ)^(i-1) · x_i  /  Σ (1-λ)^(i-1),  i = 1..n, i=1 most recent
 *
 * Used to flag students whose predicted performance has dropped meaningfully
 * below their own baseline or the class mean, for the "Support Strategy"
 * alert described in docs/ARCHITECTURE.md.
 */
class PerformancePredictor
{
    public function __construct(private float $decay = 0.2) {}

    /**
     * @param  array<int, float>  $scoresMostRecentFirst
     */
    public function predict(array $scoresMostRecentFirst): ?float
    {
        if (empty($scoresMostRecentFirst)) {
            return null;
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach (array_values($scoresMostRecentFirst) as $index => $score) {
            $weight = (1 - $this->decay) ** $index;
            $weightedSum += $weight * $score;
            $weightTotal += $weight;
        }

        return $weightTotal > 0 ? $weightedSum / $weightTotal : null;
    }

    public function predictForStudentSubject(Student $student, Subject $subject): ?float
    {
        $scores = $student->assessmentScores()
            ->whereHas('assessment', fn ($q) => $q->where('subject_id', $subject->id))
            ->orderByDesc('recorded_at')
            ->pluck('scaled_score')
            ->map(fn ($v) => (float) $v)
            ->all();

        return $this->predict($scores);
    }

    /**
     * A student needs a Support Strategy alert if their predicted score has
     * dropped notably below either their own baseline (simple average of all
     * scores) or the class mean.
     */
    public function needsSupportStrategyAlert(?float $predicted, float $baseline, float $classMean, float $threshold = 0.15): bool
    {
        if ($predicted === null) {
            return false;
        }

        $referencePoint = min($baseline, $classMean);

        return $referencePoint > 0 && ($referencePoint - $predicted) / $referencePoint >= $threshold;
    }
}
