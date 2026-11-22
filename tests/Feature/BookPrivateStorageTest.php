<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Books\Actions\UploadBookAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Book PDFs live on the private disk and are reachable only through permission-checked routes. */
class BookPrivateStorageTest extends TestCase
{
    public function refreshDatabase(): void
    {
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

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function user(string $role, School $school): User
    {
        $u = User::factory()->create(['school_id' => $school->id]);
        $u->roles()->attach(Role::firstOrCreate(['slug' => $role], ['name' => $role]));

        return $u;
    }

    private function book(School $school, bool $allow = true, string $disk = 'local', string $path = 'books/a.pdf'): Book
    {
        Storage::disk($disk)->put($path, '%PDF-1.4 secret');

        return Book::create(['school_id' => $school->id, 'subject_id' => Subject::factory()->create()->id, 'title' => 'B',
            'source' => 'file', 'file_path' => $path, 'is_active' => true, 'allow_download' => $allow]);
    }

    public function test_upload_goes_to_private_disk(): void
    {
        $school = School::factory()->create();
        $book = app(UploadBookAction::class)->execute(
            ['subject_id' => Subject::factory()->create()->id, 'title' => 'T', 'source' => 'file'],
            $school->id, $this->user('school-admin', $school)->id,
            UploadedFile::fake()->create('t.pdf', 10, 'application/pdf'),
        );

        Storage::disk('local')->assertExists($book->file_path);
        Storage::disk('public')->assertMissing($book->file_path);
        $this->assertStringNotContainsString('/storage/', (string) $book->read_url);
        $this->assertSame(route('manage.books.file', $book->id), $book->read_url);
    }

    public function test_student_access_respects_school_and_allow_download(): void
    {
        $school = School::factory()->create();
        $book = $this->book($school, allow: false);
        $student = $this->user('student', $school);

        $this->actingAs($student)->get(route('student.books.file', $book->id))->assertOk();
        $this->actingAs($student)->get(route('student.books.download', $book->id))->assertForbidden();
        $book->update(['allow_download' => true]);
        $this->actingAs($student)->get(route('student.books.download', $book->id))->assertOk();

        $outsider = $this->user('student', School::factory()->create());
        $this->actingAs($outsider)->get(route('student.books.file', $book->id))->assertNotFound();
        $this->actingAs($outsider)->get(route('student.books.download', $book->id))->assertNotFound();
    }

    public function test_graded_book_follows_the_students_class_grade(): void
    {
        $school = School::factory()->create();
        $book = $this->book($school);
        $book->update(['grade_level' => 2]);
        $inGrade = fn (int $grade) => tap($this->user('student', $school))->update(['class_room_id' => \App\Models\ClassRoom::factory()->create(['grade_level' => $grade])->id]);

        $student = $inGrade(2);
        $this->actingAs($student)->get(route('student.books.index'))->assertOk()->assertSee(route('student.books.read', $book->id));
        $this->actingAs($student)->get(route('student.books.read', $book->id))->assertOk();
        $this->actingAs($student)->get(route('student.books.file', $book->id))->assertOk();

        $this->actingAs($inGrade(3))->get(route('student.books.read', $book->id))->assertNotFound();
        $this->actingAs($this->user('student', $school))->get(route('student.books.read', $book->id))->assertNotFound();
    }

    public function test_staff_preview_is_school_scoped_and_role_gated(): void
    {
        $school = School::factory()->create();
        $book = $this->book($school);

        $this->actingAs($this->user('school-admin', $school))->get(route('manage.books.file', $book->id))
            ->assertOk()->assertHeader('content-disposition', 'inline');
        $this->actingAs($this->user('teacher', $school))->get(route('manage.books.file', $book->id))->assertOk();
        $this->actingAs($this->user('school-admin', School::factory()->create()))->get(route('manage.books.file', $book->id))->assertNotFound();
        $this->actingAs($this->user('student', $school))->get(route('manage.books.file', $book->id))->assertForbidden();
        $this->actingAs($this->user('parent', $school))->get(route('manage.books.file', $book->id))->assertForbidden();
    }

    public function test_not_yet_moved_public_file_is_still_served_through_the_route(): void
    {
        $school = School::factory()->create();
        $book = $this->book($school, disk: 'public');

        $this->actingAs($this->user('student', $school))->get(route('student.books.file', $book->id))->assertOk();
    }

    public function test_move_command_dry_run_then_move_then_rerun(): void
    {
        $school = School::factory()->create();
        $book = $this->book($school, disk: 'public');
        Book::create(['school_id' => $school->id, 'subject_id' => $book->subject_id, 'title' => 'L',
            'source' => 'external_url', 'external_url' => 'https://example.com/b.pdf', 'is_active' => true]);
        Storage::disk('public')->put('books/covers/c.png', 'img');

        $this->artisan('books:move-to-private', ['--dry-run' => true])
            ->expectsOutputToContain('moved=1 already_private=0 missing=0 failed=0')->assertSuccessful();
        Storage::disk('public')->assertExists('books/a.pdf');
        Storage::disk('local')->assertMissing('books/a.pdf');

        $this->artisan('books:move-to-private')
            ->expectsOutputToContain('moved=1 already_private=0 missing=0 failed=0')->assertSuccessful();
        Storage::disk('public')->assertMissing('books/a.pdf');
        $this->assertSame('%PDF-1.4 secret', Storage::disk('local')->get('books/a.pdf'));
        Storage::disk('public')->assertExists('books/covers/c.png');
        $this->assertSame('books/a.pdf', $book->fresh()->file_path);

        $this->artisan('books:move-to-private')
            ->expectsOutputToContain('moved=0 already_private=1 missing=0 failed=0')->assertSuccessful();

        // The old public URL no longer serves the PDF (the private disk's /storage route
        // needs a signature: 403 outside production, 404 in production).
        $old = $this->get('/storage/books/a.pdf');
        $this->assertContains($old->status(), [403, 404]);
        $this->assertStringNotContainsString('secret', (string) $old->getContent());

        $this->actingAs($this->user('student', $school))->get(route('student.books.file', $book->id))->assertOk();
    }
}
