<?php

namespace App\Modules\Promotion\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\Promotion\Actions\SetEnrollmentResultAction;
use App\Modules\Promotion\Repositories\Contracts\EnrollmentResultRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;

/**
 * Pass/fail roster (US-004). Reachable from the class page and, later, from the
 * promotion preview. Every read/write is bounded to the {school} in the URL and
 * the acting user's managed schools.
 */
class PromotionResultsController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private EnrollmentResultRepository $repo,
        private SetEnrollmentResultAction $action,
    ) {}

    public function show(Request $request, School $school)
    {
        $this->authorizeSchool($school);

        $class = $this->repo->findScopedClass((int) $request->query('class'), $school->id);
        abort_if($class === null, 404);

        $class->load('section');
        $students = $this->repo->rosterStudents($class);

        return view('admin.schools.promotion.results', [
            'school' => $school,
            'section' => $class->section,
            'class' => $class,
            'students' => $students,
        ]);
    }

    public function save(Request $request, School $school)
    {
        $this->authorizeSchool($school);

        $data = $request->validate([
            'class' => 'required|integer',
            'mark_all' => 'nullable|boolean',
            'results' => 'nullable|array',
            'results.*' => 'in:pending,passed,failed',
        ]);

        // Re-verify the class belongs to this school before any write.
        $class = $this->repo->findScopedClass((int) $data['class'], $school->id);
        abort_if($class === null, 404);

        if ($request->boolean('mark_all')) {
            $count = $this->action->markAllPassed($class->id, $school->id);
        } else {
            $count = $this->action->execute($class->id, $school->id, $data['results'] ?? []);
        }

        return redirect()
            ->route('admin.schools.promotion.results', ['school' => $school, 'class' => $class->id])
            ->with('success', __('schools.results_saved', ['count' => $count]));
    }

    /**
     * A user may only touch schools they manage; super-admin manages all.
     * Guards the tenant boundary even if the route later opens to school-admins.
     */
    private function authorizeSchool(School $school): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->isSuperAdmin() || in_array($school->id, $user?->managedSchoolIds() ?? [], true),
            403
        );
    }
}
