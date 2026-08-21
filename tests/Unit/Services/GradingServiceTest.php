<?php

namespace Tests\Unit\Services;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Services\Academics\GradingService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class GradingServiceTest extends TestCase
{
    public function test_scale_score_is_a_percentage_of_max_score(): void
    {
        $service = new GradingService;

        $this->assertSame(75.0, $service->scaleScore(15, 20));
        $this->assertSame(0.0, $service->scaleScore(5, 0));
    }

    public function test_composite_score_weights_by_assessment_type(): void
    {
        $service = new GradingService;

        $mot = new Assessment(['type' => 'MOT']);
        $eot = new Assessment(['type' => 'EOT']);

        $scores = new Collection([
            (new AssessmentScore(['scaled_score' => 60]))->setRelation('assessment', $mot),
            (new AssessmentScore(['scaled_score' => 90]))->setRelation('assessment', $eot),
        ]);

        // 0.30 weight for MOT (60) + 0.50 weight for EOT (90), renormalized
        // over the 0.80 weight actually present (no AoI score).
        $expected = round((60 * 0.30 + 90 * 0.50) / 0.80, 2);

        $this->assertSame($expected, $service->compositeScore($scores));
    }

    public function test_composite_score_is_null_with_no_scores(): void
    {
        $service = new GradingService;

        $this->assertNull($service->compositeScore(new Collection));
    }
}
