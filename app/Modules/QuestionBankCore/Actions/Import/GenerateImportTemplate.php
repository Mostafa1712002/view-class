<?php

namespace App\Modules\QuestionBankCore\Actions\Import;

use App\Models\QuestionBank;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * #254 — builds the downloadable Excel template for the QB-rebuild importer.
 *
 * The "Questions" sheet carries the full منصة الأول column set from the card,
 * plus an Instructions and an Allowed-Values sheet. Only the Questions sheet is
 * consumed. Passages live in the same sheet: a «قطعة» row followed by its
 * sub-questions (question_category = passage) — see ParseQuestionsExcel. A
 * question_category=tahsili row still imports as a normal question with no
 * domain/standard handling.
 * PhpSpreadsheet 5.x: string-coordinate setCellValue('A1', …).
 */
final class GenerateImportTemplate
{
    /** Columns of the Questions sheet, in order (matches the card spec). */
    public const QUESTION_COLUMNS = [
        'question_code',
        'question_category',
        'subject',
        'grade',
        'class',
        'semester',
        'week',
        'skill',
        'difficulty_level',
        'question_type',
        'question_text',
        'question_image',
        'is_full_image_question',
        'score',
        'answer_1_text',
        'answer_1_image',
        'answer_2_text',
        'answer_2_image',
        'answer_3_text',
        'answer_3_image',
        'answer_4_text',
        'answer_4_image',
        'answer_5_text',
        'answer_5_image',
        'correct_answer',
        'explanation',
        'tags',
        'notes',
    ];

    public function execute(QuestionBank $bank): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('QB Import Template')
            ->setDescription(config('app.name').' — قالب استيراد الأسئلة');

        $this->buildQuestionsSheet($spreadsheet);
        $this->buildInstructionsSheet($spreadsheet);
        $this->buildAllowedValuesSheet($spreadsheet);

        $tempFile = tempnam(sys_get_temp_dir(), 'qb_import_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);

        return $tempFile;
    }

    private function buildQuestionsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions');

        $headers = self::QUESTION_COLUMNS;
        foreach ($headers as $i => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.'1', $header);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:'.$lastCol.'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B8860B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample rows: mcq, true_false, then a passage («قطعة») with two sub-questions.
        $samples = [
            [
                'Q001', 'normal', 'الرياضيات', '5', '', '1', '', '',
                'سهل', 'mcq', 'كم ناتج 2 + 2 ؟', '', 'no', '1',
                '3', '', '4', '', '5', '', '6', '', '', '',
                'B', 'لأن 2+2=4', '', '',
            ],
            [
                'Q002', 'normal', 'الرياضيات', '5', '', '1', '', '',
                'متوسط', 'true_false', 'العدد 7 عدد أولي.', '', 'no', '1',
                '', '', '', '', '', '', '', '', '', '',
                'true', '', '', '',
            ],
            [
                'P001', '', 'الرياضيات', '5', '', '1', '', '',
                'متوسط', 'قطعة', 'اشترى أحمد 3 أقلام بسعر 2 ريال للقلم، ودفع للبائع 10 ريالات.', '', '', '',
                '', '', '', '', '', '', '', '', '', '',
                '', '', '', '',
            ],
            [
                'Q003', 'passage', 'الرياضيات', '5', '', '1', '', '',
                'سهل', 'mcq', 'كم دفع أحمد ثمنًا للأقلام؟', '', 'no', '1',
                '5', '', '6', '', '8', '', '', '', '', '',
                'ب', '3 × 2 = 6', '', '',
            ],
            [
                'Q004', 'passage', 'الرياضيات', '5', '', '1', '', '',
                'متوسط', 'short', 'كم الباقي الذي استرده أحمد؟', '', 'no', '1',
                '', '', '', '', '', '', '', '', '', '',
                '4 ريالات', '10 − 6 = 4', '', '',
            ],
        ];
        foreach ($samples as $r => $row) {
            foreach (array_values($row) as $i => $value) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $sheet->setCellValueExplicit(
                    $col.($r + 2),
                    (string) $value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
            }
        }

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->freezePane('A2');
    }

    private function buildInstructionsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Instructions');

        $lines = [
            'تعليمات الاستيراد',
            '',
            '1. أضف الأسئلة في ورقة "Questions" بدءًا من الصف الثاني. لا تحذف صف العناوين.',
            '2. question_code: كود مرجعي للسؤال (إلزامي لأسئلة الصورة الكاملة).',
            '3. question_category: normal أو tahsili أو passage (أو بالعربي: عادي / تحصيلي / قطعة).',
            '4. subject: اسم المادة كما هو مسجّل في النظام (مطابقة بالاسم).',
            '5. grade: رقم الصف (grade_level). class / semester / week / skill اختيارية.',
            '6. difficulty_level: سهل | متوسط | صعب أو 1 | 2 | 3.',
            '7. question_type: mcq | true_false | short | essay | matching | fill_blank.',
            '8. question_text: نص السؤال (إلزامي ما لم يكن السؤال صورة كاملة).',
            '9. is_full_image_question: yes/no — إذا yes فالكود إلزامي وتُقبل بدون نص.',
            '10. أعمدة answer_1..5_text: خيارات الاختيار من متعدد (mcq).',
            '11. correct_answer:',
            '    - mcq: حرف الخيار الصحيح A أو B أو C ... أو أ / ب / ج / د / هـ، أو رقم 1..5.',
            '    - true_false: true أو false.',
            '    - short / essay: نص الإجابة النموذجية.',
            '    - fill_blank: إجابات الفراغات مفصولة بـ |',
            '    - matching: أزواج بصيغة يمين=>يسار مفصولة بـ |',
            '12. score: درجة السؤال (رقم). explanation / tags / notes اختيارية.',
            '',
            'القطعة (نص قرائي تتبعه أسئلة):',
            '- اكتب صف القطعة أولًا: question_type = قطعة، ونص القطعة في question_text (أو صورتها في question_image). question_code هنا كود القطعة (اختياري). أعمدة الإجابات تُترك فارغة.',
            '- اكتب أسئلة القطعة في الصفوف التالية مباشرة، وفي كل سؤال منها question_category = passage. لكل سؤال اختياراته وإجابته الصحيحة كأي سؤال عادي.',
            '- تنتهي القطعة عند أول صف ليس passage. الأسئلة تُحفظ بنفس ترتيبها في الملف.',
            '- قطعة بلا أسئلة بعدها، أو سؤال passage بلا قطعة قبله = خطأ. وإذا كان في أحد أسئلة القطعة خطأ لا تُستورد القطعة كلها حتى يُصحَّح.',
            '- مثال: الصفوف 4 و5 و6 في ورقة Questions.',
            '',
            'ملاحظات:',
            '- لا يتم حفظ أي سؤال قبل الفحص والمعاينة.',
            '- الصفوف الخاطئة تظهر في تقرير الأخطاء ولا يتم استيرادها.',
            '- الصور: اكتب في أعمدة *_image رابطًا كاملًا (https://…) للصورة، أو الصق الصورة نفسها داخل الخلية المناسبة. أسماء الملفات المجردة غير مقبولة.',
        ];
        foreach ($lines as $i => $line) {
            $sheet->setCellValue('A'.($i + 1), $line);
        }
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'B8860B']]]);
        $sheet->getColumnDimension('A')->setWidth(90);
    }

    private function buildAllowedValuesSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Allowed Values');

        $sections = [
            ['question_type — القيم المسموح بها', null],
            ['mcq', 'اختيار من متعدد'],
            ['true_false', 'صح أو خطأ'],
            ['short', 'إجابة قصيرة'],
            ['essay', 'مقالي'],
            ['matching', 'توصيل'],
            ['fill_blank', 'املأ الفراغ'],
            ['قطعة', 'صف قطعة (نص قرائي) — أسئلتها في الصفوف التالية مع question_category = passage'],
            [null, null],
            ['difficulty_level', null],
            ['سهل', '1'],
            ['متوسط', '2'],
            ['صعب', '3'],
            [null, null],
            ['question_category', null],
            ['normal', 'عادي'],
            ['tahsili', 'تحصيلي'],
            ['passage', 'سؤال تابع للقطعة التي فوقه'],
            [null, null],
            ['correct_answer — التنسيق', null],
            ['mcq', 'A/B/C/D/E أو 1..5'],
            ['true_false', 'true / false'],
            ['short / essay', 'نص الإجابة'],
            ['fill_blank', 'إجابات مفصولة بـ |'],
            ['matching', 'يمين=>يسار | يمين=>يسار'],
        ];
        $row = 1;
        foreach ($sections as $s) {
            $sheet->setCellValue('A'.$row, $s[0] ?? '');
            $sheet->setCellValue('B'.$row, $s[1] ?? '');
            if ($s[1] === null && $s[0] !== null) {
                $sheet->getStyle('A'.$row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'B8860B']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8E6']],
                ]);
            }
            $row++;
        }
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(40);
    }
}
