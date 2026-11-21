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
use App\Modules\QuestionBankCore\Actions\Import\ResolveImportImage;
use App\Modules\QuestionBankCore\Models\Passage;
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
        $sheet->removeRow(2, $sheet->getHighestRow() - 1); // drop the template's sample rows
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
        $this->assertSame(0, Passage::count());
        $this->assertSame(0, BankQuestion::whereNotNull('passage_id')->count());
    }

    public function test_passage_with_three_sub_questions_imports_in_order(): void
    {
        $bank = $this->bank();
        $file = $this->clientFile($bank, [
            ['question_code' => 'P1', 'question_type' => 'قطعة', 'subject' => 'الرياضيات', 'question_text' => 'اشترى أحمد ٣ أقلام بـ ٦ ريالات.'],
            ['question_code' => 'S1', 'question_category' => 'passage', 'question_type' => 'mcq', 'question_text' => 'كم قلمًا؟',
                'answer_1_text' => '٢', 'answer_2_text' => '٣', 'correct_answer' => 'ب'],
            ['question_code' => 'S2', 'question_category' => 'قطعة', 'question_type' => 'true_false', 'question_text' => 'سعر القلم ريالان', 'correct_answer' => 'صح'],
            ['question_code' => 'S3', 'question_category' => 'passage', 'question_type' => 'short', 'question_text' => 'كم دفع؟', 'correct_answer' => '٦'],
            ['question_code' => 'N1', 'question_type' => 'true_false', 'question_text' => 'سؤال عادي بعد القطعة', 'correct_answer' => 'خطأ'],
        ]);

        [$rows, $result] = $this->runImport($bank, $file);

        $this->assertSame([], collect($rows)->where('status', '!=', 'valid')->map(fn ($r) => $r['errors'])->all());
        $this->assertSame(4, $result->imported); // the passage row is not a question
        $this->assertSame(1, $result->passages);
        $passage = Passage::sole();
        $this->assertSame(['P1', 'اشترى أحمد ٣ أقلام بـ ٦ ريالات.', $bank->id], [$passage->passage_code, $passage->passage_text, $passage->question_bank_id]);
        $this->assertSame(['S1', 'S2', 'S3'], $passage->questions->pluck('question_code')->all());
        $this->assertSame([1, 2, 3], $passage->questions->pluck('pivot.sort_order')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(3, BankQuestion::where('passage_id', $passage->id)->where('question_category', 'passage')->count());
        $this->assertNull(BankQuestion::where('question_code', 'N1')->value('passage_id'));
    }

    public function test_student_exam_shows_the_passage_once_above_its_sub_questions(): void
    {
        $bank = $this->bank();
        $this->runImport($bank, $this->clientFile($bank, [
            ['question_type' => 'قطعة', 'question_text' => 'نص القطعة التجريبي'],
            ['question_category' => 'passage', 'question_type' => 'true_false', 'question_text' => 'الفرعي الأول', 'correct_answer' => 'صح'],
            ['question_category' => 'passage', 'question_type' => 'true_false', 'question_text' => 'الفرعي الثاني', 'correct_answer' => 'خطأ'],
        ]));
        $class = \App\Models\ClassRoom::factory()->create();
        $student = \App\Models\User::factory()->create(['school_id' => $bank->school_id]);
        $student->roles()->attach(\App\Models\Role::where('slug', 'student')->first());
        \DB::table('class_student')->insert(['class_id' => $class->id, 'student_id' => $student->id]);
        $exam = \App\Models\Exam::factory()->create(['academic_year_id' => \App\Models\AcademicYear::factory()->create()->id, 'class_id' => $class->id,
            'start_time' => now()->subHour(), 'end_time' => now()->addDay(), 'status' => 'active', 'is_published' => true, 'attempts_allowed' => 1]);
        foreach (BankQuestion::whereNotNull('passage_id')->orderBy('id')->get() as $i => $bq) {
            $exam->questions()->create(['question' => $bq->body_ar, 'type' => 'true_false', 'correct_answer' => 'صح', 'marks' => 1,
                'order' => $i + 1, 'source_bank_question_id' => $bq->id]);
        }
        \App\Models\StudentExam::create(['exam_id' => $exam->id, 'student_id' => $student->id, 'started_at' => now(), 'status' => 'in_progress', 'attempt_number' => 1]);

        $html = $this->actingAs($student)->get(route('student.exams.show', $exam))->assertOk()
            ->assertSeeInOrder(['نص القطعة التجريبي', 'الفرعي الأول', 'الفرعي الثاني'])->getContent();
        $this->assertSame(1, substr_count($html, 'نص القطعة التجريبي'));
    }

    public function test_passage_structure_errors_name_the_rows(): void
    {
        $bank = $this->bank();
        $file = $this->clientFile($bank, [
            ['question_type' => 'قطعة', 'question_text' => 'قطعة بلا أسئلة'],                                                  // row 2
            ['question_type' => 'true_false', 'question_text' => 'عادي', 'correct_answer' => 'صح'],                              // row 3
            ['question_category' => 'passage', 'question_type' => 'true_false', 'question_text' => 'يتيم', 'correct_answer' => 'صح'], // row 4
            ['question_type' => 'passage', 'question_text' => 'قطعة سؤالها الثاني خاطئ'],                                       // row 5
            ['question_category' => 'passage', 'question_type' => 'true_false', 'question_text' => 'سليم', 'correct_answer' => 'صح'], // row 6
            ['question_category' => 'passage', 'question_type' => 'mcq', 'question_text' => 'بلا خيارات', 'correct_answer' => 'أ'],  // row 7
            ['question_code' => 'D1', 'question_type' => 'true_false', 'question_text' => 'عادي', 'correct_answer' => 'صح'],     // row 8
            ['question_code' => 'D1', 'question_category' => 'passage', 'question_type' => 'true_false', 'question_text' => 'يتيم مكرر', 'correct_answer' => 'صح'], // row 9
        ]);

        [$rows, $result] = $this->runImport($bank, $file);
        $byRow = collect($rows)->keyBy('rowNumber');

        $this->assertSame('valid', $byRow[3]['status']);
        foreach ([2, 4, 5, 6, 7, 9] as $r) {
            $this->assertSame('invalid', $byRow[$r]['status'], "row {$r}");
        }
        $this->assertStringContainsString('قطعة بدون أسئلة فرعية', implode(' ', $byRow[2]['errors']));
        $this->assertStringContainsString('بدون صف قطعة قبله', implode(' ', $byRow[4]['errors']));
        $this->assertStringContainsString('(الصف 5)', implode(' ', $byRow[6]['errors']));
        $this->assertStringContainsString('7', implode(' ', $byRow[5]['errors']));
        $this->assertSame(2, $result->imported);
        $this->assertSame(0, Passage::count());
    }

    public function test_template_example_passage_is_well_formed(): void
    {
        $bank = $this->bank();
        $path = (new GenerateImportTemplate)->execute($bank);
        $rows = collect(app(ParseQuestionsExcel::class)->execute(new UploadedFile($path, 't.xlsx', null, null, true), $bank, $bank->school_id))->keyBy('rowNumber');

        $this->assertTrue($rows[4]['is_passage'] ?? false);
        $this->assertSame([4, 4], [$rows[5]['passage_row'], $rows[6]['passage_row']]);
        $this->assertSame([1, 2], [$rows[5]['passage_sort'], $rows[6]['passage_sort']]);
        $this->assertSame([], preg_grep('/قطعة/u', $rows->pluck('errors')->flatten()->all()));
    }

    /** Hermetic DNS: every host resolves to $ips. */
    private function dns(array $ips): void
    {
        $this->app->instance(ResolveImportImage::class, new ResolveImportImage(fn () => $ips));
    }

    private function png(): string
    {
        ob_start();
        imagepng(imagecreatetruecolor(2, 2));

        return ob_get_clean();
    }

    public function test_image_url_is_downloaded_and_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        $this->dns(['93.184.216.34']);
        Http::fake(['https://cdn.example.org/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png'])]);
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
        $this->dns(['93.184.216.34']);
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

    public function test_url_to_internal_or_odd_targets_is_never_fetched(): void
    {
        Storage::fake('public');
        Http::fake(['*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png'])]);
        $cases = [
            'http://127.0.0.1/a.png' => [],
            'http://[::1]/a.png' => [],
            'http://169.254.169.254/latest/meta-data/a.png' => [],
            'http://[::ffff:10.0.0.1]/a.png' => [],
            'https://cdn.example.org:8443/a.png' => ['93.184.216.34'],   // port
            'https://user:pw@cdn.example.org/a.png' => ['93.184.216.34'], // userinfo
            'https://rebind.example.org/a.png' => ['93.184.216.34', '10.1.2.3'], // ANY private record
            'https://v6.example.org/a.png' => ['fd00::1'],
            'https://nat64.example.org/a.png' => ['64:ff9b::a00:1'],
            'https://nxdomain.example.org/a.png' => [],
        ];
        foreach ($cases as $url => $ips) {
            try {
                (new ResolveImportImage(fn () => $ips))->execute($url, 1);
                $this->fail("fetched {$url}");
            } catch (\RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
        Http::assertNothingSent();
    }

    public function test_non_image_body_is_refused_even_with_an_image_content_type(): void
    {
        Storage::fake('public');
        Http::fake(['*' => Http::response('<svg onload=alert(1)>', 200, ['Content-Type' => 'image/png'])]);
        $this->expectException(\RuntimeException::class);
        (new ResolveImportImage(fn () => ['93.184.216.34']))->execute('https://cdn.example.org/x.png', 1);
    }

    public function test_typed_public_reference_is_rejected_and_forged_paths_never_resolve(): void
    {
        Storage::fake('public');
        $bank = $this->bank();
        $file = $this->clientFile($bank, [
            ['question_code' => 'F1', 'question_type' => 'short', 'question_text' => 'x', 'correct_answer' => 'a',
                'question_image' => 'public:bank-questions/'.$bank->id.'/imports/0123456789abcdef0123.png'],
        ]);

        [$rows, $result] = $this->runImport($bank, $file);

        $this->assertSame('invalid', $rows[0]['status']);
        $this->assertSame(0, $result->imported);
        foreach (['public:../../.env', 'public:bank-questions/'.($bank->id + 1).'/imports/0123456789abcdef0123.png', 'public:bank-questions/'.$bank->id.'/imports/0123456789abcdef0123.svg'] as $ref) {
            try {
                (new ResolveImportImage)->execute($ref, $bank->id);
                $this->fail("resolved {$ref}");
            } catch (\RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
