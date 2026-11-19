<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\School;
use App\Models\User;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

/** Access only: the saves touch bank_questions (MySQL-only migrations), verified manually. */
class BankAccessTest extends TestCase
{
    use LoadsSpMigrations;

    private int $verbal;

    private int $quant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
        $this->verbal = (int) DB::table('sp_subjects')->where('code', 'qiyas_verbal')->value('id');
        $this->quant = (int) DB::table('sp_subjects')->where('code', 'qiyas_quant')->value('id');
    }

    private function editor(array $subjectIds, bool $grant = true): User
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole(\App\Models\Role::where('slug', 'teacher')->first());
        if ($grant) {
            $perm = DB::table('permissions')->where('slug', 'special_programs.questions')->value('id');
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => DB::table('roles')->where('slug', 'teacher')->value('id'), 'permission_id' => $perm],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
        $this->app->instance(GroupRepository::class, new class($subjectIds) implements GroupRepository {
            public function __construct(private array $ids) {}

            public function forSchool(int $schoolId, string $program, ?int $teacherId = null): \Illuminate\Support\Collection { return collect(); }

            public function groupIdsLedBy(int $teacherId, string $program): array { return []; }

            public function teacherSubjectIds(int $schoolId, int $teacherId, string $program): ?array { return $this->ids; }
        });

        return $user;
    }

    public function test_user_without_questions_permission_gets_403(): void
    {
        $user = $this->editor([$this->verbal], false);
        $this->actingAs($user)->get(route('manage.special-programs.bank.index', 'qiyas'))->assertForbidden();
        $this->actingAs($user)->post(route('manage.special-programs.bank.store', 'qiyas'), ['sp_subject_id' => $this->verbal])->assertForbidden();
    }

    public function test_editor_limited_to_verbal_cannot_open_or_save_quant(): void
    {
        $user = $this->editor([$this->verbal]);
        $this->actingAs($user)->get(route('manage.special-programs.bank.index', ['qiyas', 'subject' => $this->quant]))->assertForbidden();
        $this->actingAs($user)->get(route('manage.special-programs.bank.create', ['qiyas', 'subject' => $this->quant]))->assertForbidden();
        $this->actingAs($user)->get(route('manage.special-programs.bank.passages', ['qiyas', 'subject' => $this->quant]))->assertForbidden();
        $this->actingAs($user)->post(route('manage.special-programs.bank.store', 'qiyas'), ['sp_subject_id' => $this->quant, 'body_ar' => 'x'])->assertForbidden();
        $this->actingAs($user)->post(route('manage.special-programs.bank.passages.store', 'qiyas'), ['sp_subject_id' => $this->quant])->assertForbidden();
    }

    public function test_unassigned_editor_gets_403_and_tahsili_has_no_passages(): void
    {
        $this->actingAs($this->editor([]))->get(route('manage.special-programs.bank.index', 'qiyas'))->assertForbidden();
        $sa = User::factory()->create();
        $sa->assignRole(\App\Models\Role::where('slug', 'super-admin')->first());
        $this->actingAs($sa)->get(route('manage.special-programs.bank.passages', 'tahsili'))->assertNotFound();
    }
}
