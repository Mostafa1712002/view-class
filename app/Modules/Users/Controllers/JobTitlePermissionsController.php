<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use App\Models\Permission;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Users\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobTitlePermissionsController extends Controller
{
    use HasSchoolScope;

    /**
     * Multi-tenant guard mirroring JobTitleController: a non-super-admin may
     * only manage job titles belonging to their own school; global (school_id
     * null) titles are super-admin only.
     */
    private function assertCanManage(JobTitle $jobTitle): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            return;
        }
        abort_if($jobTitle->school_id === null, 403);
        abort_unless((int) $jobTitle->school_id === (int) $this->activeSchoolId(), 403);
    }

    public function index(JobTitle $jobTitle): View
    {
        $this->assertCanManage($jobTitle);
        $jobTitle->load('permissions');

        // Build a lookup: 'group.action' => permission model (with pivot)
        $configured = $jobTitle->permissions->keyBy(fn ($p) => $p->slug);

        // Load all permission models keyed by slug for fast lookup
        $permModels = Permission::whereIn('slug', PermissionCatalog::allSlugs())->get()->keyBy('slug');

        $otherJobTitles = JobTitle::where('id', '!=', $jobTitle->id)
            ->forSchool($this->activeSchoolId())
            ->orderBy('name_ar')
            ->get();

        return view('admin.users.job_titles.permissions', [
            'jobTitle'       => $jobTitle,
            'modules'        => PermissionCatalog::MODULES,
            'scopeLabels'    => PermissionCatalog::SCOPE_LABELS,
            'configured'     => $configured,
            'permModels'     => $permModels,
            'otherJobTitles' => $otherJobTitles,
        ]);
    }

    public function update(Request $request, JobTitle $jobTitle): RedirectResponse
    {
        $this->assertCanManage($jobTitle);

        // A non-super-admin may only grant school-level or narrower data scopes;
        // the system-wide scopes (all/company/group) are super-admin only,
        // otherwise a school-admin could escalate a job title beyond their school.
        $wideScopes   = ['all', 'company', 'group'];
        $narrowScopes = ['school', 'stage', 'class', 'section', 'subject', 'own_students', 'own_subjects', 'own'];
        $allowedScopes = auth()->user()?->isSuperAdmin()
            ? array_merge($wideScopes, $narrowScopes)
            : $narrowScopes;

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
            'scopes'        => 'nullable|array',
            'scopes.*'      => ['string', \Illuminate\Validation\Rule::in($allowedScopes)],
        ]);

        $selectedSlugs = $request->input('permissions', []);
        $scopes        = $request->input('scopes', []);

        // Resolve permission IDs from slugs
        $permMap = Permission::whereIn('slug', $selectedSlugs)->get()->keyBy('slug');

        $syncData = [];
        foreach ($selectedSlugs as $slug) {
            $perm = $permMap->get($slug);
            if ($perm) {
                $scope = $scopes[$slug] ?? 'school';
                if (! in_array($scope, $allowedScopes, true)) {
                    $scope = 'school'; // clamp anything not allowed for this actor
                }
                $syncData[$perm->id] = [
                    'scope'      => $scope,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
        }

        // sync() replaces all existing rows — detaches unselected, attaches new
        $jobTitle->permissions()->sync($syncData);

        return redirect()
            ->route('admin.users.job-titles.permissions.index', $jobTitle)
            ->with('status', 'تم حفظ الصلاحيات بنجاح');
    }

    public function copy(Request $request, JobTitle $jobTitle): RedirectResponse
    {
        $this->assertCanManage($jobTitle);

        $request->validate([
            'copy_from' => 'required|integer|exists:job_titles,id',
        ]);

        // Scope the source title to what this user may read (own school + global);
        // a non-super-admin cannot copy from another school's job title.
        $source = JobTitle::where('id', $request->integer('copy_from'))
            ->forSchool($this->activeSchoolId())
            ->firstOrFail();
        $source->load('permissions');

        $syncData = [];
        foreach ($source->permissions as $perm) {
            $syncData[$perm->id] = [
                'scope'      => $perm->pivot->scope,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        $jobTitle->permissions()->sync($syncData);

        return redirect()
            ->route('admin.users.job-titles.permissions.index', $jobTitle)
            ->with('status', "تم نسخ الصلاحيات من «{$source->name_ar}» بنجاح");
    }
}
