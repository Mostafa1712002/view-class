<?php

namespace Tests\Feature\SpecialPrograms;

use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class StudentReportPartialTest extends TestCase
{
    use LoadsSpMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');
    }

    private function report(string $program): array
    {
        $lvl = ['key' => 'good', 'name' => 'جيد', 'color' => 'primary', 'icon' => 'check-circle', 'min' => 65, 'max' => 74];
        $row = (object) ['attempt_id' => 7, 'exam_title' => 'اختبار 1', 'subject_name' => 'لفظي', 'correct_count' => 2, 'items_count' => 3, 'score_pct' => 66.67, 'ended_at' => '2026-11-10 10:00:00', 'level' => $lvl];
        $detail = $program === 'tahsili'
            ? ['difficulty' => [(object) ['difficulty' => 'easy', 'answers' => 3, 'correct' => 2, 'success_pct' => 66.67]]]
            : ['skills' => [(object) ['skill_name' => 'مهارة س', 'answers' => 3, 'correct' => 2, 'success_pct' => 66.67]]];

        return [
            'header' => ['student_id' => 1, 'name' => 'طالب تجريبي', 'class_name' => '10/أ', 'group_name' => 'مجموعة 1', 'program' => $program, 'mean_pct' => 66.67, 'level' => $lvl],
            'trend' => [['attempt_id' => 7, 'ended_at' => '2026-11-10 10:00:00', 'pct' => 66.67, 'subject_name' => 'لفظي']],
            'subjects' => [['subject_id' => 1, 'subject_name' => 'لفظي', 'attempts' => 1, 'last_pct' => 66.67, 'best_pct' => 66.67, 'mean_pct' => 66.67]],
            'history' => [$row],
        ] + $detail;
    }

    public function test_partial_renders_header_tables_and_attempt_links(): void
    {
        $html = view('special-programs.partials.student-report', ['report' => $this->report('qiyas'), 'attemptUrl' => fn ($r) => '/x/'.$r->attempt_id])->render();
        foreach (['طالب تجريبي', 'جيد', 'مهارة س', 'اختبار 1', 'href="/x/7"', 'chart.js'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_tahsili_shows_difficulty_and_pdf_has_no_script(): void
    {
        $html = view('special-programs.partials.student-report', ['report' => $this->report('tahsili')])->render();
        $this->assertStringContainsString('سهل', $html);
        $pdf = view('special-programs.partials.student-report-pdf', ['report' => $this->report('tahsili')])->render();
        $this->assertStringContainsString('xbriyaz', $pdf);
        $this->assertStringNotContainsString('<script', $pdf);
    }

    public function test_empty_report_shows_message(): void
    {
        $r = $this->report('qiyas');
        $r['history'] = []; $r['trend'] = []; $r['subjects'] = [];
        $this->assertStringContainsString('لا توجد اختبارات', view('special-programs.partials.student-report', ['report' => $r])->render());
    }
}
