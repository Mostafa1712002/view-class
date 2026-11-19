<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Actions\SaveRuleSettings;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointSettingsRepository;
use App\Modules\Gamification\Support\RuleCatalog;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;

class PointRulesController extends Controller
{
    use HasSchoolScope;

    public function __construct(private PointSettingsRepository $settings) {}

    public function index(Request $request)
    {
        $schoolId = $this->scopedSchoolId();
        $platform = $this->settings->allFor(PointRuleSetting::PLATFORM);
        $school = $schoolId ? $this->settings->allFor($schoolId) : collect();
        $isPlatform = $request->user()->isSuperAdmin();
        $isVice = $request->user()->jobTitle?->slug === 'vice';

        return view('points.manage.rules', compact('platform', 'school', 'isPlatform', 'isVice', 'schoolId') + ['groups' => [
            RuleCatalog::STUDENT => RuleCatalog::forBeneficiary(RuleCatalog::STUDENT),
            RuleCatalog::TEACHER => RuleCatalog::forBeneficiary(RuleCatalog::TEACHER),
            RuleCatalog::VICE => RuleCatalog::forBeneficiary(RuleCatalog::VICE),
        ]]);
    }

    public function updatePlatform(Request $request, SaveRuleSettings $action)
    {
        $action->platform((array) $request->input('rules', []), $request->user()->id);

        return back()->with('success', __('points_manage.rules_saved'));
    }

    public function updateSchool(Request $request, SaveRuleSettings $action)
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId === null, 403);
        $action->school($schoolId, (array) $request->input('rules', []), $request->user()->id);

        return back()->with('success', __('points_manage.rules_saved'));
    }
}
