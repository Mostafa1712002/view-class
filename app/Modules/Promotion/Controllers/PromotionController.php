<?php

namespace App\Modules\Promotion\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\School;
use App\Modules\Promotion\Actions\ExecutePromotionAction;
use App\Modules\Promotion\Actions\RollbackPromotionAction;
use App\Modules\Promotion\Services\PromotionPlanner;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Automatic year-end promotion (US-009/010/011). Preview is a pure dry-run;
 * execute and rollback are password-gated. Every action is bounded to the
 * {school} in the URL.
 */
class PromotionController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private PromotionPlanner $planner,
        private ExecutePromotionAction $executeAction,
        private RollbackPromotionAction $rollbackAction,
    ) {}

    public function preview(Request $request, School $school)
    {
        $this->authorizeSchool($school);

        $years = AcademicYear::where('school_id', $school->id)->orderByDesc('id')->get();
        $src = $this->scopedYear($request->query('source'), $school);
        $dst = $this->scopedYear($request->query('destination'), $school);

        $plan = null;
        if ($src && $dst) {
            $plan = $this->planner->plan($school, $src, $dst);
        }

        return view('admin.schools.promotion.preview', [
            'school' => $school,
            'years' => $years,
            'src' => $src,
            'dst' => $dst,
            'plan' => $plan,
        ]);
    }

    public function execute(Request $request, School $school)
    {
        $this->authorizeSchool($school);

        $data = $request->validate([
            'source_year_id' => 'required|integer',
            'destination_year_id' => 'required|integer|different:source_year_id',
            'password' => 'required|string',
        ]);

        $src = $this->scopedYear($data['source_year_id'], $school);
        $dst = $this->scopedYear($data['destination_year_id'], $school);
        abort_if($src === null || $dst === null, 404);

        try {
            $batch = $this->executeAction->execute($school, $src, $dst, $data['password']);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.schools.promotion.preview', ['school' => $school, 'source' => $src->id, 'destination' => $dst->id])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.schools.promotion.batches', $school)
            ->with('success', __('schools.promotion_done', $batch->summary ?? []));
    }

    public function batches(School $school)
    {
        $this->authorizeSchool($school);

        $batches = PromotionBatch::where('school_id', $school->id)
            ->with(['sourceYear', 'destinationYear', 'executor'])
            ->withCount('items')
            ->orderByDesc('id')
            ->get();

        $latestExecutedId = optional($batches->firstWhere('status', 'executed'))->id;

        return view('admin.schools.promotion.batches', [
            'school' => $school,
            'batches' => $batches,
            'latestExecutedId' => $latestExecutedId,
        ]);
    }

    public function rollback(Request $request, School $school, PromotionBatch $batch)
    {
        $this->authorizeSchool($school);
        abort_if($batch->school_id !== $school->id, 404);

        $request->validate(['password' => 'required|string']);

        try {
            $this->rollbackAction->execute($school, $batch, $request->input('password'));
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.schools.promotion.batches', $school)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.schools.promotion.batches', $school)
            ->with('success', __('schools.promotion_rolled_back'));
    }

    private function scopedYear($id, School $school): ?AcademicYear
    {
        if (! $id) {
            return null;
        }

        return AcademicYear::whereKey((int) $id)->where('school_id', $school->id)->first();
    }

    private function authorizeSchool(School $school): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->isSuperAdmin() || in_array($school->id, $user?->managedSchoolIds() ?? [], true),
            403
        );
    }
}
