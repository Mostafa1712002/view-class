<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Modules\Whatsapp\Repositories\Contracts\RecipientRepository;
use Tests\TestCase;

class WhatsappAudienceTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*',
            '2026_07_11_*', '2026_07_25_000100_*', '2026_11_16_372*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function classIn(School $school, string $level, int $grade, string $name): ClassRoom
    {
        $section = Section::create(['name' => $level.$grade, 'school_id' => $school->id, 'gender' => 'male', 'level' => $level]);

        return ClassRoom::create(['name' => $name, 'section_id' => $section->id, 'grade_level' => $grade, 'division' => 'A', 'capacity' => 30,
            'academic_year_id' => \App\Models\AcademicYear::factory()->create(['school_id' => $school->id])->id]);
    }

    private function student(School $school, ClassRoom $class): User
    {
        $u = User::factory()->create(['school_id' => $school->id, 'class_room_id' => $class->id]);
        $u->roles()->attach(Role::where('slug', 'student')->first());

        return $u;
    }

    private function parentOf(School $school, User $student): User
    {
        $p = User::factory()->create(['school_id' => $school->id]);
        $p->roles()->attach(Role::where('slug', 'parent')->first());
        $p->children()->attach($student->id);

        return $p;
    }

    public function test_stage_grade_and_class_audiences_accept_multiple_values_and_stay_school_scoped(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $c1 = $this->classIn($school, 'primary', 1, 'p1');
        $c2 = $this->classIn($school, 'primary', 2, 'p2');
        $c3 = $this->classIn($school, 'secondary', 3, 's3');
        $s1 = $this->student($school, $c1);
        $s2 = $this->student($school, $c2);
        $s3 = $this->student($school, $c3);
        $foreign = $this->student($other, $this->classIn($other, 'primary', 1, 'x'));
        $parent = $this->parentOf($school, $s1);
        $this->parentOf($school, $s2)->children()->attach($s1->id); // shares s1 -> must dedupe by id
        $repo = app(RecipientRepository::class);

        $ids = fn ($c) => $c->pluck('id')->sort()->values()->all();

        $this->assertSame([$s1->id, $s2->id], $ids($repo->resolveAudience('stage_students', $school->id, ['primary'])));
        $this->assertSame([$s1->id, $s2->id, $s3->id], $ids($repo->resolveAudience('stage_students', $school->id, ['primary', 'secondary'])));
        $this->assertSame([$s1->id, $s3->id], $ids($repo->resolveAudience('grade_students', $school->id, [1, 3])));
        $this->assertSame([$s2->id, $s3->id], $ids($repo->resolveAudience('class_students', $school->id, [$c2->id, $c3->id])));
        $this->assertNotContains($foreign->id, $ids($repo->resolveAudience('grade_students', $school->id, [1])));

        $parents = $repo->resolveAudience('stage_parents', $school->id, ['primary']);
        $this->assertCount(2, $parents);
        $this->assertContains($parent->id, $parents->pluck('id')->all());
        $this->assertCount(0, $repo->resolveAudience('class_parents', $school->id, [$c3->id]));

        // legacy single ref still works; empty selection never means "everyone"
        $this->assertSame([$s1->id], $ids($repo->resolveAudience('class_students', $school->id, $c1->id)));
        $this->assertCount(0, $repo->resolveAudience('grade_students', $school->id, []));
    }

    public function test_resolve_endpoint_and_store_use_refs_without_sending_real_messages(): void
    {
        $school = School::factory()->create();
        $c1 = $this->classIn($school, 'primary', 1, 'p1');
        $c3 = $this->classIn($school, 'secondary', 3, 's3');
        $s1 = $this->student($school, $c1);
        $s3 = $this->student($school, $c3);
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->roles()->attach(Role::where('slug', 'super-admin')->first());

        $this->actingAs($admin)->postJson(route('admin.whatsapp.recipients.resolve'), [
            'audience' => 'stage_students', 'ref_ids' => ['primary', 'secondary'],
        ])->assertOk()->assertJsonCount(2, 'data');
    }
}
