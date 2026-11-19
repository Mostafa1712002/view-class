<?php

namespace Tests\Feature;

use App\Models\BankQuestion;
use App\Models\QuestionBank;
use App\Models\QuestionImportBatch;
use App\Models\School;
use App\Models\Subject;
use App\Modules\QuestionBankCore\Actions\Import\GenerateImportTemplate;
use App\Modules\QuestionBankCore\Actions\Import\ImportFromPreview;
use App\Modules\QuestionBankCore\Actions\Import\ParseQuestionsExcel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/** Question-bank Excel import, driven from the official template like a teacher would. */
class QuestionImportTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        // A few historical migrations are MySQL-only (ALTER ... MODIFY); none touch the import tables.
        foreach (glob(database_path('migrations/*.php')) as $path) {
            try {
                (require $path)->up();
            } catch (\Throwable $e) {
                if (! str_contains($e->getMessage(), 'MODIFY')) {
                    throw $e;
                }
            }
        }
    }

    private function bank(): QuestionBank
    {
        $school = School::factory()->create();
        Subject::create(['name' => 'الرياضيات', 'school_id' => $school->id, 'is_active' => true]);

        return QuestionBank::create(['school_id' => $school->id, 'name_ar' => 'بنك', 'visibility' => 'private', 'status' => 'active']);
    }

    /** Fill the generated template with $rows (header => value) and return it as an upload. */
    private function clientFile(QuestionBank $bank, array $rows, callable $afterFill = null, array $headerOverride = []): UploadedFile
    {
        $spreadsheet = IOFactory::load((new GenerateImportTemplate)->execute($bank));
        $sheet = $spreadsheet->getSheetByName('Questions');
        $sheet->removeRow(2, 2); // drop the template's sample rows
        $cols = GenerateImportTemplate::QUESTION_COLUMNS;
        foreach ($rows as $i => $row) {
            foreach ($row as $header => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex(array_search($header, $cols) + 1).($i + 2), $value);
            }
        }
        foreach ($headerOverride as $header => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(array_search($header, $cols) + 1).'1', $label);
        }
        $afterFill && $afterFill($sheet);
        $path = tempnam(sys_get_temp_dir(), 'qbt').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'questions.xlsx', null, null, true);
    }

    private function runImport(QuestionBank $bank, UploadedFile $file): array
    {
        $rows = app(ParseQuestionsExcel::class)->execute($file, $bank, $bank->school_id);
        $batch = QuestionImportBatch::create(['question_bank_id' => $bank->id, 'import_type' => 'questions',
            'school_id' => $bank->school_id, 'original_filename' => 'questions.xlsx', 'status' => 'previewed', 'preview_data' => $rows]);
        $result = app(ImportFromPreview::class)->execute($bank, $batch, $batch->fresh()->preview_data, 'draft', 'skip', null);

        return [$rows, $result];
    }

    public function test_arabic_client_file_with_every_question_type_imports(): void
    {
        $bank = $this->bank();
        $file = $this->clientFile($bank, [
            ['question_code' => 'A1', 'question_type' => 'mcq', 'subject' => 'الرياضيات', 'difficulty_level' => 'سهل', 'score' => '٢',
                'question_text' => 'ما ناتج ٢ + ٣ ؟', 'answer_1_text' => '٤', 'answer_2_text' => '٥', 'answer_3_text' => '٦', 'correct_answer' => 'ب'],
            ['question_code' => 'A2', 'question_type' => 'mcq', 'question_text' => 'اختر', 'answer_1_text' => 'x', 'answer_2_text' => 'y', 'answer_3_text' => 'z', 'correct_answer' => 'ج.'],
            ['question_code' => 'A3', 'question_type' => 'true_false', 'question_text' => 'العدد 7 أولي', 'correct_answer' => 'صح'],
            ['question_code' => 'A4', 'question_type' => 'short', 'question_text' => 'جمع كتاب؟', 'correct_answer' => 'كتب'],
            ['question_code' => 'A5', 'question_type' => 'essay', 'question_text' => 'اشرح', 'correct_answer' => 'نموذج'],
            ['question_code' => 'A6', 'question_type' => 'fill_blank', 'question_text' => 'مصر ___ والسعودية ___', 'correct_answer' => 'القاهرة|الرياض'],
            ['question_code' => 'A7', 'question_type' => 'matching', 'question_text' => 'صل', 'correct_answer' => 'قطة=>ميو|كلب=>هو'],
        ], null, ['question_text' => 'Question Text']);

        [$rows, $result] = $this->runImport($bank, $file);

        $this->assertSame([], collect($rows)->where('status', '!=', 'valid')->map(fn ($r) => $r['errors'])->all());
        $this->assertSame(7, $result->imported);
        $mcq = BankQuestion::where('question_code', 'A1')->first();
        $this->assertSame(2.0, (float) $mcq->points);
        $this->assertTrue((bool) $mcq->answers()->where('answer_text', '٥')->first()->is_correct);
        $this->assertTrue((bool) BankQuestion::where('question_code', 'A2')->first()->answers()->where('answer_text', 'z')->first()->is_correct);
    }

    public function test_image_url_is_downloaded_and_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        Http::fake(['https://cdn.example.org/*' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);
        $bank = $this->bank();
        $file = $this->clientFile($bank, [[
            'question_code' => 'I1', 'question_type' => 'mcq', 'question_text' => 'ما الشكل؟',
            'question_image' => 'https://cdn.example.org/q.png', 'answer_1_text' => 'مثلث', 'answer_2_image' => 'https://cdn.example.org/a2.png',
            'correct_answer' => '2',
        ]]);

        [, $result] = $this->runImport($bank, $file);

        $this->assertSame(1, $result->imported);
        $q = BankQuestion::where('question_code', 'I1')->first();
        $this->assertStringStartsWith('bank-questions/', $q->attachment_path);
        Storage::disk('public')->assertExists($q->attachment_path);
        $opt = $q->answers()->orderBy('sort_order')->get();
        $this->assertSame('image', $opt[1]->answer_content_type);
        Storage::disk('public')->assertExists($opt[1]->answer_image);
        $this->assertTrue((bool) $opt[1]->is_correct);
    }

    public function test_picture_pasted_into_the_sheet_is_imported_and_bare_filenames_are_rejected(): void
    {
        Storage::fake('public');
        $png = tempnam(sys_get_temp_dir(), 'png').'.png';
        imagepng(imagecreatetruecolor(4, 4), $png);
        $bank = $this->bank();
        $imageCol = Coordinate::stringFromColumnIndex(array_search('question_image', GenerateImportTemplate::QUESTION_COLUMNS) + 1);
        $file = $this->clientFile($bank, [
            ['question_code' => 'P1', 'question_type' => 'true_false', 'question_text' => 'هل الصورة مربع؟', 'correct_answer' => 'true'],
            ['question_code' => 'P2', 'question_type' => 'true_false', 'question_text' => 'صورة بالاسم', 'question_image' => 'pic.png', 'correct_answer' => 'true'],
        ], function ($sheet) use ($png, $imageCol) {
            $d = new Drawing;
            $d->setPath($png);
            $d->setCoordinates($imageCol.'2');
            $d->setWorksheet($sheet);
        });

        [$rows, $result] = $this->runImport($bank, $file);

        $this->assertSame('valid', $rows[0]['status']);
        $this->assertSame('invalid', $rows[1]['status']);
        $this->assertSame(1, $result->imported);
        $q = BankQuestion::where('question_code', 'P1')->first();
        $this->assertSame('mixed', $q->question_content_type);
        Storage::disk('public')->assertExists($q->attachment_path);
    }

    public function test_unreachable_image_fails_only_that_row(): void
    {
        Storage::fake('public');
        Http::fake(['*' => Http::response('nope', 404)]);
        $bank = $this->bank();
        $file = $this->clientFile($bank, [
            ['question_code' => 'B1', 'question_type' => 'short', 'question_text' => 'x', 'question_image' => 'https://cdn.example.org/missing.png', 'correct_answer' => 'a'],
            ['question_code' => 'B2', 'question_type' => 'short', 'question_text' => 'y', 'correct_answer' => 'b'],
        ]);

        [, $result] = $this->runImport($bank, $file);

        $this->assertSame([1, 1], [$result->imported, $result->failed]);
        $this->assertNull(BankQuestion::where('question_code', 'B1')->first());
    }
}
