<?php

namespace Database\Seeders;

use App\Models\QuestionBank;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Modules\QuestionBankCore\Actions\CreateQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Card #303 — seed sample question banks with approved questions across every
 * bank criterion (category, visibility) and every question type + difficulty,
 * so the banks are immediately callable from exams (/admin/question-banks +
 * the exam "add from bank" picker, which requires status = approved).
 *
 * Idempotent: banks are matched by (school_id, name_ar); a bank that already has
 * questions is skipped. Safe to re-run.
 */
class QuestionBankCard303Seeder extends Seeder
{
    public function run(): void
    {
        $school = School::find(1) ?? School::query()->first();
        if (! $school) {
            $this->command?->warn('Card #303: no school found — nothing seeded.');
            return;
        }

        // CreateQuestion uses auth()->id() (created_by + activity log); log a
        // super-admin in for the duration of the seed.
        $admin = User::where('email', 'developer@midade.com')->first()
            ?? User::query()->orderBy('id')->first();
        if ($admin) {
            Auth::login($admin);
        }

        $subjectId = Subject::where('school_id', $school->id)->value('id'); // optional

        $create = app(CreateQuestion::class);

        // Banks spanning the category + visibility criteria.
        $banks = [
            ['name_ar' => 'بنك أسئلة المواد — الصف الأول', 'category_type' => 'school', 'visibility' => 'private', 'grade_level' => 1, 'subject' => true],
            ['name_ar' => 'بنك أسئلة المواد — الصف الثاني', 'category_type' => 'school', 'visibility' => 'private', 'grade_level' => 2, 'subject' => true],
            ['name_ar' => 'بنك القدرات — القسم اللفظي', 'category_type' => 'verbal', 'visibility' => 'public', 'grade_level' => null, 'subject' => false],
            ['name_ar' => 'بنك القدرات — القسم الكمي', 'category_type' => 'quantitative', 'visibility' => 'public', 'grade_level' => null, 'subject' => false],
        ];

        $created = 0;
        foreach ($banks as $b) {
            $isPublic = $b['visibility'] === 'public';
            $bank = QuestionBank::firstOrCreate(
                ['school_id' => $isPublic ? null : $school->id, 'name_ar' => $b['name_ar']],
                [
                    'name_en'        => null,
                    'description'    => 'بنك أسئلة تجريبي (بيانات أولية) — جاهز للاستدعاء في الاختبارات.',
                    'is_library'     => false,
                    'visibility'     => $b['visibility'],
                    'status'         => QuestionBank::STATUS_ACTIVE,
                    'source'         => QuestionBank::SOURCE_MANUAL,
                    'grade_level'    => $b['grade_level'],
                    'category_type'  => $b['category_type'],
                    'bank_type'      => $b['visibility'],
                    'requires_approval' => false,
                    'subject_id'     => $b['subject'] ? $subjectId : null,
                    'created_by'     => $admin?->id,
                    'metadata'       => ['seed' => 'card_303'],
                ]
            );

            // Skip if this bank already has questions (idempotent).
            if ($bank->questions()->exists()) {
                continue;
            }

            foreach ($this->questionSet($b['subject'] ? $subjectId : null, $b['grade_level']) as $q) {
                $create->execute($bank, $q);
                $created++;
            }
        }

        Auth::logout();
        $this->command?->info("Card #303: seeded {$created} approved questions across " . count($banks) . ' banks.');
    }

    /**
     * One question per type, spread across difficulties, all approved.
     *
     * @return array<int,array<string,mixed>>
     */
    private function questionSet(?int $subjectId, ?int $gradeLevel): array
    {
        $base = [
            'question_content_type' => 'text',
            'status'                => 'approved',
            'subject_id'            => $subjectId,
            'question_category'     => 'normal',
        ];

        return [
            $base + [
                'type'        => 'mcq',
                'body_ar'     => 'ما هو ناتج ٧ + ٥ ؟',
                'options_ar'  => ['١٠', '١١', '١٢', '١٣'],
                'correct_index' => 2,
                'difficulty'  => 1,
                'points'      => 1,
                'explanation' => '٧ + ٥ = ١٢.',
            ],
            $base + [
                'type'      => 'true_false',
                'body_ar'   => 'الشمس تشرق من جهة الشرق.',
                'correct'   => 'true',
                'difficulty' => 1,
                'points'    => 1,
                'explanation' => 'الشمس تشرق من الشرق وتغرب في الغرب.',
            ],
            $base + [
                'type'         => 'short',
                'body_ar'      => 'اذكر عاصمة المملكة العربية السعودية.',
                'short_answer' => 'الرياض',
                'difficulty'   => 2,
                'points'       => 2,
                'explanation'  => 'عاصمة المملكة العربية السعودية هي الرياض.',
            ],
            $base + [
                'type'         => 'essay',
                'body_ar'      => 'اشرح بأسلوبك أهمية القراءة في بناء المعرفة.',
                'essay_answer' => 'القراءة توسّع المدارك وتنمّي التفكير وتزوّد القارئ بالمعلومات والخبرات.',
                'difficulty'   => 3,
                'points'       => 5,
                'explanation'  => 'يُقيّم الجواب وفق شمول الفكرة وسلامة اللغة.',
            ],
            $base + [
                'type'           => 'matching',
                'body_ar'        => 'صِلْ كل دولة بعاصمتها.',
                'matching_left'  => ['مصر', 'السعودية', 'الأردن'],
                'matching_right' => ['القاهرة', 'الرياض', 'عمّان'],
                'difficulty'     => 2,
                'points'         => 3,
            ],
            $base + [
                'type'      => 'fill_blank',
                'body_ar'   => 'أكمل الفراغ: عدد أيام الأسبوع ______ أيام.',
                'blanks'    => ['سبعة'],
                'difficulty' => 1,
                'points'    => 1,
            ],
        ];
    }
}
