<?php

namespace App\Modules\SpecialPrograms\Actions\Picking;

final class PickerFor
{
    public static function program(string $program): QuestionPicker
    {
        return app(match ($program) {
            'qiyas' => QiyasPicker::class,
            'nafis' => NafisPicker::class,
            'tahsili' => TahsiliPicker::class,
        });
    }
}
