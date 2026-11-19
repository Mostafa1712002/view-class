<?php

namespace App\Modules\SpecialPrograms\Actions\Import;

/**
 * (school_type_id, «الصف الثالث») → ViewClass grade 1..12 (D§4.2 step 4).
 * Stage 1 = ابتدائية 1..6, stage 2 = متوسطة 7..9, stage 3 = ثانوية 10..12.
 */
final class AlAwwalLevelMap
{
    private const ORDINALS = ['الاول' => 1, 'الثاني' => 2, 'الثالث' => 3, 'الرابع' => 4, 'الخامس' => 5, 'السادس' => 6];

    private const STAGES = [1 => [0, 6], 2 => [6, 3], 3 => [9, 3]]; // stage => [offset, max ordinal]

    public function grade(int $schoolTypeId, string $nameClass): ?int
    {
        $stage = self::STAGES[$schoolTypeId] ?? null;
        if (! $stage) {
            return null;
        }
        $name = strtr($nameClass, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي']);
        foreach (self::ORDINALS as $word => $n) {
            // whole word, so «الثاني» does not match inside «الثانية عشر» style names
            if (preg_match('/(^|\s)'.$word.'(\s|$)/u', $name) && $n <= $stage[1]) {
                return $stage[0] + $n;
            }
        }

        return null;
    }
}
