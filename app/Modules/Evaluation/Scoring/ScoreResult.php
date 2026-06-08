<?php

namespace App\Modules\Evaluation\Scoring;

/**
 * Immutable result of scoring a single evaluation against its frozen snapshot.
 *
 * `total` / `max` / `percentage` are the headline numbers. `breakdown` is a
 * per-item array detailed enough that "how was this computed" is fully
 * reproducible (it carries the inputs each scorer used, e.g. chosen rank +
 * level count for rubric, chosen value + max value for rating scale, met +
 * total for checklist).
 *
 * Scoring is a pure computation: nothing here is persisted. The execution flow
 * is responsible for writing total/percentage/grade_label/breakdown onto the
 * Evaluation.
 */
final readonly class ScoreResult
{
    /**
     * @param float       $total      Sum of earned points across items.
     * @param float       $max        Maximum achievable points (Σ weights for
     *                                rubric/rating = 100; indicator count for checklist).
     * @param float       $percentage total / max × 100 (0 when max is 0).
     * @param string|null $gradeLabel Arabic grade label derived from percentage.
     * @param array<int,array<string,mixed>> $breakdown Per-item detail.
     */
    public function __construct(
        public float $total,
        public float $max,
        public float $percentage,
        public ?string $gradeLabel,
        public array $breakdown,
    ) {
    }

    /**
     * Build a ScoreResult from raw totals, rounding to 2dp (matching the
     * `decimal:2` casts on the Evaluation/EvaluationResponse models) and
     * deriving the grade label from the percentage.
     *
     * @param array<int,array<string,mixed>> $breakdown
     */
    public static function make(float $total, float $max, array $breakdown): self
    {
        $total      = round($total, 2);
        $max        = round($max, 2);
        $percentage = $max > 0 ? round(($total / $max) * 100, 2) : 0.0;

        return new self(
            total: $total,
            max: $max,
            percentage: $percentage,
            gradeLabel: self::gradeFor($percentage),
            breakdown: $breakdown,
        );
    }

    /**
     * Default grade scale (Arabic). Simple and overridable: callers that need a
     * different scale can post-process the percentage. Plain strings are used so
     * no lang file is required.
     */
    public static function gradeFor(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'ممتاز',
            $percentage >= 80 => 'جيد جداً',
            $percentage >= 70 => 'جيد',
            $percentage >= 60 => 'مقبول',
            default           => 'ضعيف',
        };
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'total'       => $this->total,
            'max'         => $this->max,
            'percentage'  => $this->percentage,
            'grade_label' => $this->gradeLabel,
            'breakdown'   => $this->breakdown,
        ];
    }
}
