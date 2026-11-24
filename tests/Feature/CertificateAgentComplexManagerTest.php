<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\JobTitle;
use App\Models\School;
use App\Models\User;
use App\Modules\Certificates\Actions\RenderCertificatePdfAction;
use App\Modules\Certificates\Http\Requests\IssueCertificateRequest;
use App\Modules\QuestionBankCore\Models\Compound;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CertificateAgentComplexManagerTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $patterns = [
            '0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*',
            '2026_04_26_*', '2026_04_27_*', '2026_04_28_100001_add_sprint4_fields_to_subjects*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*',
            '2026_06_13_000001_create_certificates*', '2026_06_15_500001_qbcore_create_compounds*',
            '2026_07_04_110000_add_design_fields_to_certificates*',
            '2026_07_09_*', '2026_07_11_*', '2026_07_25_*',
            '2026_10_22_160000_add_manager_agent_stamp*', '2026_11_19_400001_*', '2026_11_24_100000_*',
        ];
        $paths = [];
        foreach ($patterns as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function vice(School $school, string $name): User
    {
        $title = JobTitle::firstOrCreate(['slug' => 'vice'], ['name_ar' => 'وكيل', 'name_en' => 'Vice']);

        return User::factory()->create(['name' => $name, 'school_id' => $school->id, 'job_title_id' => $title->id]);
    }

    private function fields(Certificate $c): array
    {
        $m = new \ReflectionMethod(RenderCertificatePdfAction::class, 'resolveFields');
        $m->setAccessible(true);

        return $m->invoke(new RenderCertificatePdfAction(), $c);
    }

    private function cert(School $school, array $attrs = []): Certificate
    {
        $student = User::factory()->create(['school_id' => $school->id]);

        return Certificate::create($attrs + [
            'school_id' => $school->id, 'type' => 'thanks', 'title' => 't',
            'recipient_user_id' => $student->id, 'issue_date' => '2026-11-19',
        ]);
    }

    public function test_chosen_vice_none_and_default_agent_and_complex_manager(): void
    {
        $school = School::factory()->create();
        $default = $this->vice($school, 'الوكيل الافتراضي');
        $other = $this->vice($school, 'وكيل آخر');
        $school->agents()->sync([$default->id => ['print_on_certificate' => true], $other->id => ['print_on_certificate' => false]]);
        $boss = User::factory()->create(['name' => 'مدير المجمع س']);
        $compound = Compound::create(['name_ar' => 'مجمع', 'status' => 'active', 'manager_user_id' => $boss->id]);
        $compound->schools()->sync([$school->id]);

        $f = $this->fields($this->cert($school));
        $this->assertSame('الوكيل الافتراضي', $f['agent']);
        $this->assertSame('مدير المجمع س', $f['complex_manager']);

        $this->assertSame('وكيل آخر', $this->fields($this->cert($school, ['agent_user_id' => $other->id]))['agent']);
        $this->assertSame('', $this->fields($this->cert($school, ['print_agent' => false]))['agent']);
        // Several agents chosen at issue time → all printed.
        $this->assertSame(['الوكيل الافتراضي', 'وكيل آخر'], $this->fields($this->cert($school, ['agent_user_ids' => [$other->id, $default->id]]))['agents']);
        // School default = every agent flagged «print on certificate».
        $school->agents()->updateExistingPivot($other->id, ['print_on_certificate' => true]);
        $this->assertSame(['الوكيل الافتراضي', 'وكيل آخر'], $this->fields($this->cert($school))['agents']);
    }

    public function test_super_admin_certificate_without_school_uses_the_students_school(): void
    {
        $school = School::factory()->create();
        $vice = $this->vice($school, 'وكيل الطالب');
        $school->agents()->sync([$vice->id]);
        $student = User::factory()->create(['school_id' => $school->id]);
        $c = Certificate::create(['school_id' => null, 'type' => 'thanks', 'title' => 't', 'recipient_user_id' => $student->id, 'issue_date' => '2026-11-24']);

        $this->assertSame('وكيل الطالب', $this->fields($c)['agent']);
    }

    public function test_migration_backfills_the_single_agent_into_the_pivot(): void
    {
        $school = School::factory()->create();
        $vice = $this->vice($school, 'قديم');
        \Illuminate\Support\Facades\Schema::drop('school_agents');
        \Illuminate\Support\Facades\Schema::table('certificates', fn ($t) => $t->dropColumn('agent_user_ids'));
        $school->forceFill(['agent_user_id' => $vice->id])->saveQuietly();
        (require database_path('migrations/2026_11_24_100000_create_school_agents_table.php'))->up();

        $this->assertSame([$vice->id], $school->agents()->pluck('users.id')->all());
        $this->assertTrue((bool) $school->agents()->first()->pivot->print_on_certificate);
    }

    public function test_complex_manager_empty_without_complex(): void
    {
        $this->assertSame('', $this->fields($this->cert(School::factory()->create()))['complex_manager']);
    }

    public function test_agent_must_be_a_vice_of_the_same_school(): void
    {
        $a = School::factory()->create();
        $b = School::factory()->create();
        $viceB = $this->vice($b, 'وكيل مدرسة ب');
        $viceA = $this->vice($a, 'وكيل مدرسة أ');
        $admin = User::factory()->create(['school_id' => $a->id]);
        $this->actingAs($admin);

        $rules = fn () => (new IssueCertificateRequest())->rules();
        $check = function (array $input) use ($admin) {
            $req = IssueCertificateRequest::create('/x', 'POST', $input);
            $req->setUserResolver(fn () => $admin);

            $rules = $req->rules();

            return Validator::make($input, array_intersect_key($rules, array_flip(['agents_choice', 'agent_user_ids', 'agent_user_ids.*'])))->passes();
        };
        $a->agents()->sync([$viceA->id]);
        $b->agents()->sync([$viceB->id]);

        $this->assertFalse($check(['agent_user_ids' => [$viceB->id]]));
        $this->assertFalse($check(['agent_user_ids' => [$admin->id]]));
        $this->assertTrue($check(['agent_user_ids' => [$viceA->id]]));
        $this->assertTrue($check(['agents_choice' => 1]));
        $this->assertTrue($check([]));
    }

    public function test_complex_manager_candidates_are_staff_plus_current_manager(): void
    {
        $school = School::factory()->create();
        $staff = $this->vice($school, 'Staff');
        $student = User::factory()->create(['school_id' => $school->id]);
        $repo = app(\App\Modules\QuestionBankCore\Repositories\Contracts\CompoundRepository::class);

        $ids = $repo->managerCandidates()->pluck('id');
        $this->assertTrue($ids->contains($staff->id));
        $this->assertFalse($ids->contains($student->id));
        $this->assertTrue($repo->managerCandidates($student->id)->pluck('id')->contains($student->id));
    }
}
