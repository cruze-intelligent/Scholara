<?php

namespace App\Services\Academics;

use App\Models\Assessment;
use Illuminate\Support\Collection;

/**
 * MOT/EOT/AoI weighting and raw->scaled auto-scaling. docs/ROADMAP.md names
 * this requirement without a formula — the split below (AoI 20% / MOT 30% /
 * EOT 50%) is a documented default, not a researched Scholara-specific
 * policy. See docs/DECISIONS.md. Change the constants below if the real
 * school policy differs; nothing else needs to change.
 */
class GradingService
{
    public const TYPE_WEIGHTS = [
        'AoI' => 0.20,
        'MOT' => 0.30,
        'EOT' => 0.50,
    ];

    public function scaleScore(float $rawScore, float $maxScore): float
    {
        return $maxScore > 0 ? round(($rawScore / $maxScore) * 100, 2) : 0.0;
    }

    /**
     * @param  Collection<int, \App\Models\AssessmentScore>  $scores  one student's scores for a
     *                                                                single subject/term, each with
     *                                                                `assessment` eager-loaded.
     */
    public function compositeScore(Collection $scores): ?float
    {
        $byType = $scores->groupBy(fn ($score) => $score->assessment->type);

        $weightedSum = 0.0;
        $weightUsed = 0.0;

        foreach (self::TYPE_WEIGHTS as $type => $weight) {
            $typeScores = $byType->get($type);

            if (! $typeScores || $typeScores->isEmpty()) {
                continue;
            }

            $weightedSum += $typeScores->avg('scaled_score') * $weight;
            $weightUsed += $weight;
        }

        return $weightUsed > 0 ? round($weightedSum / $weightUsed, 2) : null;
    }

    public function classMeanFor(Assessment $assessment): ?float
    {
        return $assessment->scores()->avg('scaled_score');
    }
}
