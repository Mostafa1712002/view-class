<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobTitleController extends Controller
{
    use HasSchoolScope;

    public function index(): View
    {
        $jobTitles = JobTitle::query()
            ->forSchool($this->activeSchoolId())
            ->orderByRaw('school_id IS NULL DESC')
            ->orderBy('sort_order')
            ->get();
        return view('admin.users.job_titles.index', compact('jobTitles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => 'required|string|max:64',
            'name_ar' => 'required|string|max:120',
            'name_en' => 'required|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['school_id'] = $this->activeSchoolId();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['sort_order'] ??= 0;

        JobTitle::create($data);

        return redirect()->route('admin.users.job-titles.index')
            ->with('status', __('users.job_title_created'));
    }

    public function update(Request $request, JobTitle $jobTitle): RedirectResponse
    {
        if ($jobTitle->school_id !== $this->activeSchoolId() && $jobTitle->school_id !== null) {
            abort(403);
        }
        if ($jobTitle->school_id === null) {
            // global title — only super-admin may edit
            abort_unless(auth()->user()?->isSuperAdmin(), 403);
        }
        $data = $request->validate([
            'name_ar' => 'required|string|max:120',
            'name_en' => 'required|string|max:120',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? $jobTitle->is_active);
        $data['sort_order'] ??= $jobTitle->sort_order;
        $jobTitle->update($data);
        return redirect()->route('admin.users.job-titles.index')
            ->with('status', __('users.job_title_updated'));
    }

    public function destroy(JobTitle $jobTitle): RedirectResponse
    {
        if ($jobTitle->school_id === null) {
            abort(403, 'Cannot delete a global job title.');
        }
        if ($jobTitle->school_id !== $this->activeSchoolId()) {
            abort(403);
        }
        $jobTitle->delete();
        return redirect()->route('admin.users.job-titles.index')
            ->with('status', __('users.job_title_deleted'));
    }
}
