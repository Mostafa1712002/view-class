<?php

namespace Tests\Feature;

use App\Modules\Dashboard\Repositories\EloquentDashboardStatsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class DashboardStatsRepositoryTest extends TestCase
{
    private string $demoStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-18 12:00:00', 'Asia/Riyadh'));
        \Illuminate\Support\Carbon::setTestNow(CarbonImmutable::now());
        $this->demoStorage = sys_get_temp_dir().'/vc-dashboard-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->demoStorage);
        $this->app->useStoragePath($this->demoStorage);
        // Deliberately small schema: optional module tables are absent.
        foreach ([
            'schools' => ['educational_company_id'], 'sections' => ['school_id'],
            'classes' => ['section_id', 'academic_year_id'], 'users' => ['school_id'],
            'roles' => [], 'role_user' => ['role_id', 'user_id'],
            'class_student' => ['class_id', 'student_id'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $t) use ($name, $columns) {
                $t->id();
                foreach ($columns as $column) {
                    $t->unsignedBigInteger($column)->nullable();
                }
                if (in_array($name, ['classes', 'users'])) {
                    $t->string('name');
                }
                if ($name === 'users') {
                    $t->timestamp('last_login_at')->nullable();
                    $t->softDeletes();
                }
                if ($name === 'roles') {
                    $t->string('slug');
                }
            });
        }
        foreach ([
            '2025_11_30_200000_create_activity_logs_table.php',
            '2025_11_30_180731_create_attendances_table.php',
            '2025_11_30_174402_create_student_exams_table.php',
            '2025_11_30_184403_create_assignment_submissions_table.php',
        ] as $migration) {
            // Parent fixtures below supply the relevant IDs; no production migrations run.
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('academic_years', fn (Blueprint $t) => $t->id());
        Schema::create('subjects', fn (Blueprint $t) => $t->id());
        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('class_id');
        });
        Schema::create('assignments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('class_id');
            $t->unsignedBigInteger('school_id');
        });
        DB::table('academic_years')->insert(['id' => 1]);
        DB::table('roles')->insert([['id' => 1, 'slug' => 'student'], ['id' => 2, 'slug' => 'teacher'], ['id' => 3, 'slug' => 'parent']]);
        foreach ([1, 2, 3] as $id) {
            DB::table('schools')->insert(['id' => $id, 'educational_company_id' => $id === 3 ? 2 : 1]);
            DB::table('sections')->insert(['id' => $id, 'school_id' => $id]);
            DB::table('classes')->insert(['id' => $id, 'section_id' => $id, 'academic_year_id' => 1, 'name' => "Class $id"]);
            DB::table('users')->insert(['id' => $id, 'school_id' => $id, 'name' => "Student $id", 'last_login_at' => now()]);
            DB::table('role_user')->insert(['role_id' => 1, 'user_id' => $id]);
            DB::table('class_student')->insert(['class_id' => $id, 'student_id' => $id]);
            DB::table('exams')->insert(['id' => $id, 'class_id' => $id]);
            DB::table('assignments')->insert(['id' => $id, 'class_id' => $id, 'school_id' => $id]);
            $this->log($id, '2026-11-15 12:00:00');
            DB::table('attendances')->insert(['student_id' => $id, 'class_id' => $id, 'teacher_id' => $id,
                'academic_year_id' => 1, 'date' => '2026-11-15', 'status' => 'absent']);
            DB::table('student_exams')->insert(['exam_id' => $id, 'student_id' => $id,
                'status' => 'submitted', 'submitted_at' => now(), 'created_at' => now()]);
        }
    }

    private function log(int $school, string $date): void
    {
        DB::table('activity_logs')->insert(['school_id' => $school, 'user_id' => $school,
            'action' => 'view', 'description' => 'Real activity', 'created_at' => $date]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        \Illuminate\Support\Carbon::setTestNow();
        if (isset($this->demoStorage)) {
            File::deleteDirectory($this->demoStorage);
        }
        parent::tearDown();
    }

    public function test_real_metrics_are_scoped_and_use_distinct_users_and_riyadh_week(): void
    {
        $this->log(1, '2026-11-15 13:00:00');
        $this->log(1, '2026-10-01 12:00:00');
        $repo = new EloquentDashboardStatsRepository;
        $this->assertSame(100, $repo->interactionRates(1)['studentsLoginRate']);
        $this->assertSame(100, $repo->interactionRates(1)['studentTeacherInteraction']);
        $this->assertSame(100, $repo->interactionRates(1)['studentContentInteraction']);
        $this->assertSame(2, $repo->contentStats(1)['contentInteractions']);
        $this->assertSame(4, $repo->contentStats(null)['contentInteractions']);
        $this->assertSame(1, $repo->contentStats(1)['examSubmissions']);
        $this->assertSame(3, $repo->contentStats(null)['electronicExams']);
        $this->assertSame(0, $repo->contentStats(1)['smsUsage']);
        $this->assertSame(1, $repo->variousStats(1)['absences']);
        $this->assertSame(['day' => 'sun', 'rate' => 100], $repo->weeklyAbsenceRate(1)[0]);
        $this->assertSame(0, $repo->weeklyAbsenceRate(1)[1]['rate']);
        $this->assertSame(100, $repo->weeklyActivity(1)['series'][0]['students']);
        $this->assertSame(20, $repo->weeklyActivity(1)['studentsRate']);
        $ranking = $repo->mostActive(1, 1);
        $this->assertSame([['name' => 'Class 1', 'score' => 2]], $ranking['activeClassesInSchool']);
        $this->assertCount(2, $ranking['activeUsersInCompany']);
        $this->assertCount(3, $repo->mostActive(null, null)['activeUsersInSchool']);
        $this->assertSame(0, $repo->interactionRates(99)['studentsLoginRate']);
    }

    public function test_optional_content_tables_and_role_rates(): void
    {
        foreach ([
            '2026_06_12_235000_create_discussion_rooms_table.php',
            '2026_06_12_235002_create_discussion_comments_table.php',
            '2026_06_13_130001_create_subject_contents_table.php',
            '2026_06_13_000100_create_virtual_classes_table.php',
            '2025_11_30_173218_create_weekly_plans_table.php',
            '2026_04_28_110001_create_question_banks_table.php',
            '2026_04_28_110004_create_bank_questions_table.php',
            '2026_05_25_sm04_create_sms_messages_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('sms_senders', fn (Blueprint $t) => $t->id());
        DB::table('subjects')->insert(['id' => 1]);
        foreach ([1, 2] as $school) {
            DB::table('sms_messages')->insert(['school_id' => $school, 'recipient' => '0500000000', 'body' => 'Message', 'status' => 'sent']);
            DB::table('discussion_rooms')->insert(['school_id' => $school, 'title' => 'Discussion', 'created_by' => $school]);
            DB::table('subject_contents')->insert(['school_id' => $school, 'subject_id' => 1, 'type' => 'video', 'title' => 'Video', 'views_count' => 2]);
            DB::table('virtual_classes')->insert(['school_id' => $school, 'teacher_id' => $school, 'created_by' => $school, 'title' => 'Lesson', 'scheduled_at' => now()->addDay()]);
            DB::table('weekly_plans')->insert(['teacher_id' => $school, 'subject_id' => 1, 'class_id' => $school, 'week_start_date' => '2026-11-15', 'week_end_date' => '2026-11-19']);
            DB::table('question_banks')->insert(['id' => $school, 'school_id' => $school, 'name_ar' => 'Bank']);
            DB::table('bank_questions')->insert(['question_bank_id' => $school, 'body_ar' => 'Question']);
        }
        DB::table('student_exams')->delete();
        DB::table('discussion_comments')->insert(['school_id' => 1, 'user_id' => 1, 'topic_id' => 1, 'body' => 'Comment', 'created_at' => now()]);
        DB::table('users')->insert(['id' => 4, 'school_id' => 1, 'name' => 'Inactive student']);
        DB::table('role_user')->insert(['role_id' => 1, 'user_id' => 4]);
        $repo = new EloquentDashboardStatsRepository;
        $this->assertSame(50, $repo->interactionRates(1)['studentsLoginRate']);
        $this->assertSame(50, $repo->interactionRates(1)['studentTeacherInteraction']);
        $this->assertSame(50, $repo->contentStats(1)['contentInteractionRate']);
        $this->assertSame(100, $repo->contentStats(1)['viewRate']);
        $this->assertSame(1, $repo->contentStats(1)['videosFiles']);
        $this->assertSame(1, $repo->contentStats(1)['smsUsage']);
        foreach (['discussionRooms', 'preparationPlans', 'questionsCount', 'virtualClasses', 'scheduledVirtualClasses'] as $key) {
            $this->assertSame(1, $repo->variousStats(1)[$key]);
            $this->assertSame(2, $repo->variousStats(null)[$key]);
        }
    }

    public function test_riyadh_day_boundaries_when_storage_timezone_is_utc(): void
    {
        config(['app.timezone' => 'UTC']);
        DB::table('activity_logs')->delete();
        $this->log(1, '2026-11-14 21:00:00'); // Sunday midnight in Riyadh.
        $this->log(2, '2026-11-14 20:59:59'); // Saturday in Riyadh.
        $repo = new EloquentDashboardStatsRepository;
        $this->assertSame(100, $repo->weeklyActivity(1)['series'][0]['students']);
        $this->assertSame(0, $repo->weeklyActivity(2)['series'][0]['students']);
    }

    public function test_demo_command_requires_school_and_production_force(): void
    {
        $this->artisan('vc:demo-data')->assertFailed();
        $this->artisan('vc:demo-data', ['--school' => 999])->assertFailed();
        $this->app->instance('env', 'production');
        $this->artisan('vc:demo-data', ['--school' => 1])->expectsOutput('Production requires --force.')->assertFailed();
    }

    public function test_missing_tables_return_empty_metrics(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['users', 'activity_logs', 'attendances', 'exams', 'assignments', 'student_exams', 'assignment_submissions'] as $table) {
            Schema::drop($table);
        }
        $repo = new EloquentDashboardStatsRepository;
        $this->assertSame(0, array_sum($repo->counts(null)));
        $this->assertSame(0, array_sum($repo->interactionRates(1)));
        $this->assertSame(0, array_sum($repo->contentStats(1)));
        $this->assertSame(0, array_sum($repo->variousStats(1)));
        $this->assertSame([], $repo->mostActive(1, 1)['activeUsersInSchool']);
        $this->assertSame(0, $repo->weeklyActivity(1)['studentsRate']);
        $this->assertSame(0, $repo->weeklyAbsenceRate(1)[0]['rate']);
    }

    public function test_demo_command_is_idempotent_and_removes_only_its_data(): void
    {
        $path = storage_path('app/demo-data-1.json');
        $this->assertFileDoesNotExist($path);
        DB::table('users')->insert(['id' => 4, 'school_id' => 1, 'name' => 'Teacher', 'last_login_at' => null]);
        DB::table('role_user')->insert(['user_id' => 4, 'role_id' => 2]);
        DB::table('exams')->insert(['id' => 4, 'class_id' => 1]);
        try {
            $this->artisan('vc:demo-data', ['--school' => 1])->assertSuccessful();
            $counts = array_map(fn ($table) => DB::table($table)->count(), ['activity_logs', 'attendances', 'student_exams', 'assignment_submissions']);
            $this->assertGreaterThan(3, $counts[0]);
            $this->assertGreaterThan(3, $counts[1]);
            $this->assertSame(1, $counts[3]);
            $this->assertSame(4, $counts[2]);
            $this->artisan('vc:demo-data', ['--school' => 1])->assertSuccessful();
            $this->assertSame($counts, array_map(fn ($table) => DB::table($table)->count(), ['activity_logs', 'attendances', 'student_exams', 'assignment_submissions']));
            $this->artisan('vc:demo-data', ['--school' => 1, '--remove' => true])->assertSuccessful();
            $this->assertSame(3, DB::table('activity_logs')->count());
            $this->assertSame(3, DB::table('attendances')->count());
            $this->assertSame(3, DB::table('student_exams')->count());
            $this->assertSame(0, DB::table('assignment_submissions')->count());
            $this->assertNull(DB::table('users')->where('id', 4)->value('last_login_at'));
            $this->assertFileDoesNotExist($path);
        } finally {
            File::delete([$path, $path.'.lock']);
        }
    }
}
