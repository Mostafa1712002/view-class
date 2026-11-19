<?php

namespace App\Modules\Gamification\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Modules\Gamification\Actions\DeliverReward;
use App\Modules\Gamification\Http\Requests\PeriodRequest;
use App\Modules\Gamification\Models\PointReward;
use App\Modules\Gamification\Repositories\Contracts\RewardRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;

class RewardsController extends Controller
{
    use HasSchoolScope;

    public function __construct(private RewardRepository $rewards) {}

    public function periodsIndex(Request $request)
    {
        $schoolId = $this->scopedSchoolId();
        $periods = $this->rewards->periods($schoolId);
        $sections = $this->sections($schoolId);

        return view('points.manage.periods', compact('periods', 'sections', 'schoolId'));
    }

    public function periodsStore(PeriodRequest $request)
    {
        $schoolId = $this->writeSchoolId();
        $data = $this->periodData($request, $schoolId);
        $created = $this->rewards->createPeriod($data + ['school_id' => $schoolId, 'created_by' => $request->user()->id]);

        return $this->saved($schoolId, $data, $created->id);
    }

    public function periodsUpdate(PeriodRequest $request, int $period)
    {
        $schoolId = $this->writeSchoolId();
        $model = $this->rewards->findPeriod($period, $schoolId) ?? abort(404);
        $data = $this->periodData($request, $schoolId);
        $this->rewards->updatePeriod($model, $data);

        return $this->saved($schoolId, $data, $model->id);
    }

    public function periodsDestroy(Request $request, int $period)
    {
        $model = $this->rewards->findPeriod($period, $this->writeSchoolId()) ?? abort(404);
        $this->rewards->deletePeriod($model);

        return back()->with('success', __('points_manage.period_deleted'));
    }

    public function rewardsIndex(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', 'in:earned,delivered']])['status'] ?? null;
        $rewards = $this->rewards->rewards($this->scopedSchoolId(), $status)->withQueryString();

        return view('points.manage.rewards', compact('rewards', 'status'));
    }

    public function deliver(Request $request, int $reward, DeliverReward $action)
    {
        $model = $this->rewards->findReward($reward, $this->scopedSchoolId()) ?? abort(404);
        $action->execute($model, $request->user()->id);

        return back()->with('success', __('points_manage.delivered'));
    }

    public function honour(Request $request)
    {
        $schoolId = $this->scopedSchoolId();
        $f = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'], 'type' => ['nullable', 'in:student,teacher,vice'],
            'section_id' => ['nullable', 'integer'],
        ]);
        $month = $f['month'] ?? now()->format('Y-m');
        $type = $f['type'] ?? 'student';
        $list = $schoolId ? $this->rewards->honourList($schoolId, $month, $type, $f['section_id'] ?? null) : collect();
        $sections = $this->sections($schoolId);

        return view('points.manage.honour', compact('list', 'month', 'type', 'sections', 'schoolId'));
    }

    /** Writes need a concrete school (a super-admin on "all schools" must pick one first). */
    private function writeSchoolId(): int
    {
        return $this->scopedSchoolId() ?? abort(403);
    }

    private function sections(?int $schoolId)
    {
        return Section::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name']);
    }

    private function periodData(PeriodRequest $request, int $schoolId): array
    {
        $data = $request->validated();
        if (! empty($data['section_id'])) {
            abort_unless(Section::where('school_id', $schoolId)->whereKey($data['section_id'])->exists(), 422);
        }

        return $data + ['section_id' => null];
    }

    private function saved(int $schoolId, array $data, ?int $exceptId = null)
    {
        $overlap = $this->rewards->periods($schoolId)->first(fn ($p) => $p->id !== $exceptId
            && ($p->section_id === null || $data['section_id'] === null || (int) $p->section_id === (int) $data['section_id'])
            && $p->starts_on->toDateString() <= $data['ends_on'] && $p->ends_on->toDateString() >= $data['starts_on']);
        $r = back()->with('success', __('points_manage.period_saved'));

        return $overlap ? $r->with('warning', __('points_manage.period_overlap', ['name' => $overlap->name])) : $r;
    }
}
