<?php

namespace Tests\Unit\SpecialPrograms;

use App\Modules\SpecialPrograms\Actions\Picking\Apportion;
use PHPUnit\Framework\TestCase;

class ApportionTest extends TestCase
{
    public function test_sums_to_n_and_follows_shares(): void
    {
        $r = Apportion::bySkill([1 => 50, 2 => 30, 3 => 20], 10);
        $this->assertSame([1 => 5, 2 => 3, 3 => 2], $r);
    }

    public function test_rounding_remainder_goes_to_largest(): void
    {
        $r = Apportion::bySkill([1 => 34, 2 => 33, 3 => 33], 10);
        $this->assertSame(10, array_sum($r));
        $this->assertSame(4, $r[1]);
    }

    public function test_each_skill_gets_at_least_one(): void
    {
        $r = Apportion::bySkill([1 => 97, 2 => 2, 3 => 1], 10);
        $this->assertSame(10, array_sum($r));
        $this->assertGreaterThanOrEqual(1, min($r));
    }

    public function test_more_skills_than_questions_picks_largest_and_never_negative(): void
    {
        $r = Apportion::bySkill([1 => 10, 2 => 40, 3 => 30, 4 => 20], 2);
        $this->assertSame(2, array_sum($r));
        $this->assertSame([1 => 0, 2 => 1, 3 => 1, 4 => 0], $r);
        $this->assertGreaterThanOrEqual(0, min($r));
    }

    public function test_shares_not_summing_to_100_are_normalised(): void
    {
        $r = Apportion::bySkill([1 => 30, 2 => 10], 8);
        $this->assertSame([1 => 6, 2 => 2], $r);
    }

    public function test_zero_total_splits_equally(): void
    {
        $this->assertSame([1 => 2, 2 => 2], Apportion::bySkill([1 => 0, 2 => 0], 4));
    }
}
