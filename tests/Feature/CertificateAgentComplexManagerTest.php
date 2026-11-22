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
            '2026_10_22_160000_add_manager_agent_stamp*', '2026_11_19_400001_*',
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
        $school->update(['agent_user_id' => $default->id]);
        $boss = User::factory()->create(['name' => 'مدير المجمع س']);
        $compound = Compound::create(['name_ar' => 'مجمع', 'status' => 'active', 'manager_user_id' => $boss->id]);
        $compound->schools()->sync([$school->id]);

        $f = $this->fields($this->cert($school));
        $this->assertSame('الوكيل الافتراضي', $f['agent']);
        $this->assertSame('مدير المجمع س', $f['complex_manager']);

        $this->assertSame('وكيل آخر', $this->fields($this->cert($school, ['agent_user_id' => $other->id]))['agent']);
        $this->assertSame('', $this->fields($this->cert($school, ['print_agent' => false]))['agent']);
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

            return Validator::make($input, ['agent_user_id' => $req->rules()['agent_user_id']])->passes();
        };

        $this->assertFalse($check(['agent_user_id' => $viceB->id]));
        $this->assertFalse($check(['agent_user_id' => $admin->id]));
        $this->assertTrue($check(['agent_user_id' => $viceA->id]));
        $this->assertTrue($check(['agent_user_id' => 'none']));
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
