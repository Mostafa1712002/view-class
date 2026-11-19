<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\SpecialPrograms\Actions\Settings\SaveLevelBands;
use App\Modules\SpecialPrograms\Actions\Settings\SaveProgramSettings;
use App\Modules\SpecialPrograms\Http\Requests\LevelsRequest;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Support\Programs;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SettingsRepository $settings) {}

    /** Super-admin on «all schools» has no school bands to show: send to the global page. */
    private function schoolOrRedirect(string $program): int|RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();

        return $schoolId ?? redirect()->route('manage.special-programs.settings.global', ['program' => $program])
            ->with('error', __('special_programs/settings.pick_school'));
    }

    private function canEdit(int $schoolId, string $program): bool
    {
        return ! auth()->user()->isSuperAdmin() && $this->settings->canSchoolEdit($schoolId, $program);
    }

    public function index(string $program)
    {
        $schoolId = $this->schoolOrRedirect($program);
        if ($schoolId instanceof RedirectResponse) {
            return $schoolId;
        }

        return view('special-programs.staff.settings.levels', [
            'program' => $program,
            'bands' => $this->settings->bandsFor($schoolId, $program),
            'weeks' => $this->settings->weeksCount($schoolId),
            'editable' => $this->canEdit($schoolId, $program),
            'global' => false,
        ]);
    }

    public function update(LevelsRequest $request, string $program, SaveLevelBands $bands, SaveProgramSettings $prog): RedirectResponse
    {
        $schoolId = $this->schoolOrRedirect($program);
        if ($schoolId instanceof RedirectResponse) {
            return $schoolId;
        }
        abort_unless($this->canEdit($schoolId, $program), 403);
        $bands->execute($schoolId, $program, $request->input('bands'), $request->user()->id);
        $prog->executeSchool($schoolId, $program, $request->filled('weeks_count') ? (int) $request->input('weeks_count') : null, $request->user()->id);

        return back()->with('success', __('special_programs/settings.saved'));
    }

    public function global(Request $request)
    {
        $program = $request->query('program', 'qiyas');
        abort_unless(in_array($program, Programs::keys(), true), 404);
        $schools = School::orderBy('sort_order')->orderBy('name_ar')->get(['id', 'name', 'name_ar', 'name_en']);

        return view('special-programs.staff.settings.program', [
            'program' => $program,
            'bands' => $this->settings->bandsFor(null, $program),
            'weeks' => $this->settings->weeksCount(null),
            'gradeLevels' => $this->settings->gradeLevels($program),
            'schools' => $schools,
            'allowed' => $schools->filter(fn ($s) => $this->settings->canSchoolEdit($s->id, $program))->pluck('id')->all(),
            'global' => true,
        ]);
    }

    public function globalUpdate(LevelsRequest $request, SaveLevelBands $bands, SaveProgramSettings $prog): RedirectResponse
    {
        $program = $request->program();
        abort_unless(in_array($program, Programs::keys(), true), 404);
        $userId = $request->user()->id;
        $bands->execute(null, $program, $request->input('bands'), $userId);
        $prog->executeGlobal(
            $program,
            array_map('intval', $request->input('grade_levels', [])),
            $request->filled('weeks_count') ? (int) $request->input('weeks_count') : null,
            School::pluck('id')->map(fn ($i) => (int) $i)->all(),
            array_map('intval', $request->input('allow_schools', [])),
            $userId,
        );

        return redirect()->route('manage.special-programs.settings.global', ['program' => $program])
            ->with('success', __('special_programs/settings.saved'));
    }
}
