<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Book;
use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Meeting4BooksExamsTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*',
            '2026_05_25_130002_*', '2026_06_15_100000_*', '2026_05_18_120065_*', '2026_04_28_100001_*', '2026_11_19_1000*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function student(School $school, ?ClassRoom $class = null): User
    {
        $u = User::factory()->create(['school_id' => $school->id]);
        $u->roles()->attach(Role::where('slug', 'student')->first());
        if ($class) {
            DB::table('class_student')->insert(['class_id' => $class->id, 'student_id' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $u;
    }

    private function book(School $school, bool $allow): Book
    {
        Storage::fake('public');
        Storage::disk('public')->put('books/x.pdf', '%PDF-1.4 test');

        return Book::create(['school_id' => $school->id, 'subject_id' => Subject::factory()->create()->id, 'title' => 'B',
            'source' => 'file', 'file_path' => 'books/x.pdf', 'is_active' => true, 'allow_download' => $allow]);
    }

    public function test_download_allowed_streams_file(): void
    {
        $school = School::factory()->create();
        $book = $this->book($school, true);
        $this->actingAs($this->student($school))->get(route('student.books.download', $book->id))->assertOk();
    }

    public function test_download_blocked_but_reading_works(): void
    {
        $school = School::factory()->create();
        $book = $this->book($school, false);
        $s = $this->student($school);
        $this->actingAs($s)->get(route('student.books.download', $book->id))->assertForbidden();
        $this->actingAs($s)->get(route('student.books.file', $book->id))->assertOk();
        $this->actingAs($s)->get(route('student.books.read', $book->id))->assertOk()
            ->assertDontSee(route('student.books.download', $book->id));
    }

    public function test_download_button_shown_when_allowed(): void
    {
        $this->withoutExceptionHandling();
        $school = School::factory()->create();
        $book = $this->book($school, true);
        $this->actingAs($this->student($school))->get(route('student.books.read', $book->id))
            ->assertSee(route('student.books.download', $book->id));
    }

    private function exam(ClassRoom $class, array $targets = []): Exam
    {
        $exam = Exam::factory()->create(['academic_year_id' => AcademicYear::factory()->create()->id, 'class_id' => $class->id,
            'start_time' => now()->subHour(), 'end_time' => now()->addDay(), 'status' => 'active', 'attempts_allowed' => 1]);
        $exam->targetStudents()->sync($targets);

        return $exam;
    }

    public function test_class_exam_unchanged_and_targeted_exam_limited(): void
    {
        $school = School::factory()->create();
        $class = ClassRoom::factory()->create();
        $a = $this->student($school, $class);
        $b = $this->student($school, $class);
        $classWide = $this->exam($class);
        $targeted = $this->exam($class, [$a->id]);

        $this->assertTrue($classWide->isVisibleTo($a) && $classWide->isVisibleTo($b));
        $this->assertTrue($targeted->isVisibleTo($a));
        $this->assertFalse($targeted->isVisibleTo($b));
        $this->assertEqualsCanonicalizing([$classWide->id, $targeted->id], Exam::visibleToStudent($a->id)->pluck('id')->all());
        $this->assertSame([$classWide->id], Exam::visibleToStudent($b->id)->pluck('id')->all());

        $this->actingAs($a)->get(route('student.exams.show', $targeted))->assertOk();
        $this->actingAs($b)->get(route('student.exams.show', $targeted))->assertForbidden();
        $this->actingAs($b)->post(route('student.exams.start', $targeted))->assertForbidden();
        $this->actingAs($b)->get(route('student.exams.show', $classWide))->assertOk();
    }

    public function test_targeted_exam_stays_hidden_when_its_only_target_is_soft_deleted(): void
    {
        $school = School::factory()->create();
        $class = ClassRoom::factory()->create();
        $a = $this->student($school, $class);
        $b = $this->student($school, $class);
        $targeted = $this->exam($class, [$a->id]);
        $a->delete();

        $this->assertFalse($targeted->isVisibleTo($b));
        $this->assertSame([], Exam::visibleToStudent($b->id)->pluck('id')->all());
    }

    public function test_admin_targets_students_and_rejects_cross_school_ids(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $class = ClassRoom::factory()->create();
        $mine = $this->student($school, $class);
        $foreign = $this->student($other, $class);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->roles()->attach(Role::where('slug', 'super-admin')->first());

        $payload = fn (array $ids) => [
            'title' => 'T', 'subject_id' => Subject::factory()->create()->id, 'class_id' => $class->id,
            'academic_year_id' => AcademicYear::factory()->create()->id, 'type' => 'quiz', 'total_marks' => 10,
            'attempts_allowed' => 1, 'max_exit_attempts' => 3, 'target_mode' => 'students', 'student_ids' => $ids,
        ];

        $this->actingAs($admin)->post(route('admin.exams.store'), $payload([$mine->id, $foreign->id]))->assertStatus(422);
        $this->assertSame(0, Exam::count());

        $this->actingAs($admin)->post(route('admin.exams.store'), $payload([$mine->id]));
        $exam = Exam::firstOrFail();
        $this->assertSame([$mine->id], $exam->targetStudents()->pluck('users.id')->all());
    }
}
