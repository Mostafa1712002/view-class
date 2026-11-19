<?php

namespace Tests\Unit\SpecialPrograms;

use App\Modules\SpecialPrograms\Actions\Import\AlAwwalLevelMap;
use PHPUnit\Framework\TestCase;

class AlAwwalLevelMapTest extends TestCase
{
    public function test_third_class_per_stage(): void
    {
        $m = new AlAwwalLevelMap;
        $this->assertSame(3, $m->grade(1, 'الصف الثالث'));
        $this->assertSame(9, $m->grade(2, 'الصف الثالث'));
        $this->assertSame(12, $m->grade(3, 'الصف الثالث'));
    }

    public function test_all_primary_and_first_of_each_stage(): void
    {
        $m = new AlAwwalLevelMap;
        $names = ['الأول' => 1, 'الثاني' => 2, 'الثالث' => 3, 'الرابع' => 4, 'الخامس' => 5, 'السادس' => 6];
        foreach ($names as $w => $n) {
            $this->assertSame($n, $m->grade(1, "الصف {$w}"), $w);
        }
        $this->assertSame(7, $m->grade(2, 'الصف الأول'));
        $this->assertSame(10, $m->grade(3, 'الصف الاول'));
    }

    public function test_unknown_returns_null(): void
    {
        $m = new AlAwwalLevelMap;
        $this->assertNull($m->grade(2, 'الصف الرابع'));   // no 4th in intermediate
        $this->assertNull($m->grade(3, 'الصف السادس'));
        $this->assertNull($m->grade(9, 'الصف الثالث'));
        $this->assertNull($m->grade(1, 'روضة'));
    }
}
