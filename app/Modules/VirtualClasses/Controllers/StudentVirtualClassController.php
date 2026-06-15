<?php

namespace App\Modules\VirtualClasses\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\VirtualClasses\Actions\JoinVirtualClassAction;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Student view of upcoming/live virtual classroom sessions.
 *
 * Join access is gated by ENROLLMENT + the time window (not by canDo) — students
 * have no job-title grant, so a canDo('virtual_classes.join') check would 403
 * every student. The slug exists for STAFF who join to monitor; students enter
 * via their own enrolled/targeted sessions.
 */
class StudentVirtualClassController extends Controller
{
    use HasSchoolScope;

    public function __construct(private VirtualClassRepositoryInterface $repo) {}

    public function index(): View
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $classes  = $this->repo->forStudent($user->id, $schoolId);

        return view('virtual-classes.student.index', compact('classes'));
    }

    public function join(int $id, JoinVirtualClassAction $action): RedirectResponse
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();

        $vc = $this->repo->find($id, $schoolId);
        abort_if(! $vc, 404);

        // Enrollment / targeting check: a class-targeted session is only joinable
        // by students enrolled in that class; school-wide sessions (null class_id)
        // are joinable by any student of the school.
        if ($vc->class_id) {
            $enrolled = DB::table('class_student')
                ->where('class_id', $vc->class_id)
                ->where('student_id', $user->id)
                ->exists();
            abort_unless($enrolled, 403);
        }

        // Time window: the join button only works 5 min before until end.
        abort_unless($vc->isJoinable(), 422, __('virtual_classes.join_not_yet'));

        $result = $action->execute($vc, $user->id);

        if (! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return redirect()
            ->route('my.virtual-classes.index')
            ->with('warning', __('virtual_classes.zoom_not_linked'));
    }
}
