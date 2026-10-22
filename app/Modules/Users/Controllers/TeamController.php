<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use App\Models\Role;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Repositories\Contracts\TeamRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly TeamRepository $team)
    {
    }

    public function index(Request $request): View
    {
        $user = auth()->user();

        $tabs = ['accounts'];
        if ($user->canDo('job_titles.view')) {
            $tabs[] = 'job-titles';
        }
        if ($user->isSuperAdmin()) {
            $tabs[] = 'roles';
        }
        $tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'accounts';

        $data = ['tabs' => $tabs, 'tab' => $tab];

        if ($tab === 'accounts') {
            $schoolId = $this->listSchoolId();
            $filters = [
                'type'         => $request->query('type'),
                'q'            => $request->string('q')->toString(),
                'job_title_id' => (int) $request->query('job_title_id') ?: null,
                'status'       => $request->query('status'),
            ];
            $data += [
                'members'    => $this->team->paginate($schoolId, $filters),
                'stats'      => $this->team->stats($schoolId),
                'filters'    => $filters,
                'jobTitles'  => JobTitle::query()->forSchool($this->activeSchoolId())->active()->orderBy('sort_order')->get(),
            ];
        } elseif ($tab === 'job-titles') {
            $data += [
                'jobTitles' => JobTitle::query()
                    ->with(['role', 'permissions'])
                    ->withCount('users')
                    ->forSchool($this->activeSchoolId())
                    ->orderByRaw('school_id IS NULL DESC')
                    ->orderBy('sort_order')
                    ->get(),
                'roles' => Role::orderBy('name')->get(),
            ];
        } else {
            $data['roleRows'] = Role::withCount('users')->orderBy('id')->get();
        }

        return view('admin.users.team.index', $data);
    }
}
