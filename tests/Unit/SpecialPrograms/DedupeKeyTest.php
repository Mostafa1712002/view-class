<?php

namespace Tests\Unit\SpecialPrograms;

use App\Modules\SpecialPrograms\Actions\Import\DedupeKey;
use PHPUnit\Framework\TestCase;

class DedupeKeyTest extends TestCase
{
    private function item(string $stem = 'كِتاب : ورقة', array $opts = ['أ', 'ب', 'ج'], int $c = 1, ?int $skill = 6216): array
    {
        return ['stem' => $stem, 'options' => $opts, 'correct_index' => $c, 'skill_source_id' => $skill];
    }

    public function test_insensitive_to_tashkeel_tatweel_and_whitespace(): void
    {
        $a = DedupeKey::make(1, [$this->item('كِتاب : ورقة')], null, null);
        $b = DedupeKey::make(1, [$this->item("  كتـاب   :\nورقة ")], null, null);
        $this->assertSame($a, $b);
    }

    public function test_image_bytes_subject_skill_correct_option_and_passage_are_part_of_key(): void
    {
        $base = DedupeKey::make(1, [$this->item()], 'aaa', null);
        $this->assertNotSame($base, DedupeKey::make(1, [$this->item()], 'bbb', null));
        $this->assertNotSame($base, DedupeKey::make(2, [$this->item()], 'aaa', null));
        $this->assertNotSame($base, DedupeKey::make(1, [$this->item(skill: 6217)], 'aaa', null));
        $this->assertNotSame($base, DedupeKey::make(1, [$this->item(c: 2)], 'aaa', null));
        $this->assertNotSame($base, DedupeKey::make(1, [$this->item()], 'aaa', 'قطعة'));
    }

    public function test_groups_are_sorted_and_singletons_dropped(): void
    {
        $g = DedupeKey::duplicateGroups([30 => 'k1', 10 => 'k2', 20 => 'k1', 40 => 'k1']);
        $this->assertSame(['k1' => [20, 30, 40]], $g);
    }

    public function test_merge_survivor_is_lowest_id_with_level_union(): void
    {
        $m = DedupeKey::merge([
            ['id' => 50, 'levels' => [4, 5], 'term' => 'second', 'week' => 7],
            ['id' => 20, 'levels' => [5, 6], 'term' => 'first', 'week' => 9],
            ['id' => 35, 'levels' => [4], 'term' => 'first', 'week' => null],
        ]);
        $this->assertSame(20, $m['survivor_id']);
        $this->assertSame([4, 5, 6], $m['levels']);
        $this->assertSame('both', $m['term']);
        $this->assertSame(7, $m['week']);
        $this->assertSame([35, 50], $m['merged_source_ids']);
    }

    public function test_merge_keeps_term_when_all_equal(): void
    {
        $m = DedupeKey::merge([
            ['id' => 2, 'levels' => [1], 'term' => 'first', 'week' => null],
            ['id' => 1, 'levels' => [1], 'term' => 'first', 'week' => null],
        ]);
        $this->assertSame('first', $m['term']);
        $this->assertNull($m['week']);
    }
}
