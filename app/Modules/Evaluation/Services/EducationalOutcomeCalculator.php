<?php

namespace App\Modules\Evaluation\Services;

/**
 * Phase C (#205) — Pure computation engine for educational outcomes.
 *
 * Two supported methods:
 *
 *   all_registered : avg = Σ(scores, absent=0) / total_registered
 *                    Absent students contribute 0 to the numerator and 1 to
 *                    the denominator. Returns 0 when registered_count = 0.
 *
 *   attendees_only : avg = Σ(present scores) / present_count
 *                    Absent students are excluded entirely. Returns 0 when
 *                    present_count = 0 (all absent edge case).
 *
 * No I/O, no DB — safe to unit-test in isolation.
 */
class EducationalOutcomeCalculator
{
    /**
     * Compute aggregate stats from a student array.
     *
     * Each student entry must have:
     *   int   student_id
     *   float score      (0–100)
     *   string status    ('present' | 'absent')
     *
     * @param  array<int, array{student_id: int, score: float, status: string}> $students
     * @param  string $method  'all_registered' | 'attendees_only'
     * @return array{registered: int, present: int, absent: int, sum: float, average: float}
     */
    public function compute(array $students, string $method): array
    {
        $registered = count($students);
        $present    = 0;
        $absent     = 0;
        $sum        = 0.0;

        foreach ($students as $s) {
            $isPresent = ($s['status'] ?? 'absent') === 'present';
            $score     = (float) ($s['score'] ?? 0);

            if ($isPresent) {
                $present++;
                $sum += $score;
            } else {
                $absent++;
                // all_registered: absent counts as 0 in sum — nothing to add
            }
        }

        if ($method === 'all_registered') {
            // Sum already excludes absent (they score 0); registered is the denominator.
            $average = $registered > 0
                ? round($sum / $registered, 2)
                : 0.0;
        } else {
            // attendees_only: only present students in denominator.
            $average = $present > 0
                ? round($sum / $present, 2)
                : 0.0;
        }

        return [
            'registered' => $registered,
            'present'    => $present,
            'absent'     => $absent,
            'sum'        => round($sum, 2),
            'average'    => $average,
        ];
    }
}
