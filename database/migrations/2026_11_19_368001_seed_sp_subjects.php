<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** code => [program, name_ar, name_en, source_subject_id, picks_passages] */
    private const SUBJECTS = [
        'qiyas_verbal'     => ['qiyas', 'القدرات اللفظية', 'Qiyas Verbal', 21, false],
        'qiyas_quant'      => ['qiyas', 'القدرات الكمية', 'Qiyas Quantitative', 22, false],
        'tahsili_physics'  => ['tahsili', 'تحصيلي فيزياء', 'Tahsili Physics', 62, false],
        'tahsili_chemistry' => ['tahsili', 'تحصيلي كيمياء', 'Tahsili Chemistry', 63, false],
        'tahsili_biology'  => ['tahsili', 'تحصيلي أحياء', 'Tahsili Biology', 64, false],
        'tahsili_math'     => ['tahsili', 'تحصيلي رياضيات', 'Tahsili Math', 65, false],
        'nafis_reading'    => ['nafis', 'نافس قراءة', 'Nafis Reading', 59, true],
        'nafis_math'       => ['nafis', 'نافس رياضيات', 'Nafis Math', 60, false],
        'nafis_science'    => ['nafis', 'نافس علوم', 'Nafis Science', 61, false],
    ];

    public function up(): void
    {
        $order = 0;
        foreach (self::SUBJECTS as $code => [$program, $ar, $en, $source, $passages]) {
            DB::table('sp_subjects')->updateOrInsert(['code' => $code], [
                'program' => $program, 'name_ar' => $ar, 'name_en' => $en,
                'source_subject_id' => $source, 'picks_passages' => $passages, 'sort_order' => ++$order,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('sp_subjects')->whereIn('code', array_keys(self::SUBJECTS))->delete();
    }
};
