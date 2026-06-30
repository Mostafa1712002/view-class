<?php

namespace App\Modules\QuestionBanks\Actions;

use App\Models\QuestionBank;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds a downloadable question_bank_import_template.xlsx with three sheets:
 *   1. Questions  — importable columns with a sample row
 *   2. Instructions — Arabic how-to guide
 *   3. Allowed Values — reference tables (types, difficulty, etc.)
 */
final class GenerateQuestionImportTemplate
{
    public function execute(QuestionBank $bank): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Question Import Template')
            ->setDescription('المنصة الذهبية — قالب استيراد بنك الأسئلة');

        $this->buildQuestionsSheet($spreadsheet, $bank);
        $this->buildInstructionsSheet($spreadsheet);
        $this->buildAllowedValuesSheet($spreadsheet, $bank);

        // Write to a temp file and return the path
        $tempFile = tempnam(sys_get_temp_dir(), 'qb_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    private function buildQuestionsSheet(Spreadsheet $spreadsheet, QuestionBank $bank): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions');

        $headers = [
            'question_code',
            'question_type',
            'question_content_type',
            'question_text',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_answer',
            'difficulty',
            'explanation',
            'grade',
            'semester',
        ];

        // Header row
        foreach ($headers as $i => $header) {
            $sheet->setCellValue(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '1',
                $header
            );
        }

        // Style the header row
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample row for mcq
        $sample = [
            'Q001',         // question_code
            'mcq',          // question_type
            'text',         // question_content_type
            'ما هو عاصمة المملكة العربية السعودية؟', // question_text
            'الرياض',       // option_a
            'جدة',          // option_b
            'مكة المكرمة',  // option_c
            'الدمام',       // option_d
            'A',            // correct_answer (A|B|C|D or true|false)
            'سهل',          // difficulty
            '',             // explanation
            '5',            // grade
            '1',            // semester
        ];

        foreach ($sample as $i => $value) {
            $sheet->setCellValue(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '2',
                $value
            );
        }

        // Auto-size columns
        for ($i = 1; $i <= count($headers); $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Freeze header row
        $sheet->freezePane('A2');
    }

    private function buildInstructionsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Instructions');

        $instructions = [
            ['التعليمات', ''],
            ['', ''],
            ['1. افتح ورقة "Questions" وأضف أسئلتك بدءًا من الصف الثاني.', ''],
            ['2. عمود question_code: كود مرجعي اختياري للسؤال (مثل: Q001).', ''],
            ['3. عمود question_type: نوع السؤال. القيم المسموح بها في ورقة "Allowed Values".', ''],
            ['4. عمود question_content_type: نوع محتوى السؤال (text | image | mixed).', ''],
            ['5. عمود question_text: نص السؤال. إلزامي ما لم يكن السؤال صورة كاملة (full_image).', ''],
            ['6. أعمدة option_a إلى option_d: خيارات الإجابة لأسئلة الاختيار من متعدد (mcq).', ''],
            ['7. عمود correct_answer:', ''],
            ['   - لأسئلة mcq: أدخل A أو B أو C أو D.', ''],
            ['   - لأسئلة true_false: أدخل true أو false.', ''],
            ['   - لأسئلة short/essay: أدخل الإجابة النموذجية.', ''],
            ['8. عمود difficulty: مستوى الصعوبة. القيم: سهل | متوسط | صعب.', ''],
            ['9. عمود explanation: شرح الإجابة (اختياري).', ''],
            ['10. عمود grade: الصف الدراسي (رقم).', ''],
            ['11. عمود semester: الفصل الدراسي (رقم).', ''],
            ['', ''],
            ['ملاحظات مهمة:', ''],
            ['- لا تحذف الصف الأول (صف العناوين).', ''],
            ['- لا تغيّر أسماء الأعمدة.', ''],
            ['- للأسئلة الصورية الكاملة (full_image): يمكن ترك question_text فارغًا مع ملء question_code.', ''],
            ['- الحد الأقصى لحجم الملف: 10 ميجابايت.', ''],
        ];

        foreach ($instructions as $i => $row) {
            $sheet->setCellValue('A' . ($i + 1), $row[0]);
        }

        // Style title
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1D4ED8']],
        ]);

        $sheet->getColumnDimension('A')->setWidth(80);
    }

    private function buildAllowedValuesSheet(Spreadsheet $spreadsheet, QuestionBank $bank): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Allowed Values');

        $sections = [
            ['عمود question_type — القيم المسموح بها', null],
            ['mcq', 'اختيار من متعدد'],
            ['true_false', 'صح أو خطأ'],
            ['short', 'إجابة قصيرة'],
            ['essay', 'مقالي'],
            ['matching', 'توصيل'],
            ['fill_blank', 'اكمل الفراغ'],
            [null, null],
            ['عمود question_content_type — القيم المسموح بها', null],
            ['text', 'سؤال نصي'],
            ['image', 'سؤال بالصورة'],
            ['mixed', 'نصي + صوري'],
            [null, null],
            ['عمود difficulty — القيم المسموح بها', null],
            ['سهل', '1'],
            ['متوسط', '2'],
            ['صعب', '3'],
            [null, null],
            ['عمود correct_answer — تنسيق الإجابة', null],
            ['mcq', 'A أو B أو C أو D'],
            ['true_false', 'true أو false'],
            ['short / essay', 'نص الإجابة النموذجية'],
        ];

        $row = 1;
        foreach ($sections as $section) {
            $sheet->setCellValue('A' . $row, $section[0] ?? '');
            $sheet->setCellValue('B' . $row, $section[1] ?? '');

            // Style section headers (null value in col B = header)
            if ($section[1] === null && $section[0] !== null) {
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                ]);
            }

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(40);
    }
}
