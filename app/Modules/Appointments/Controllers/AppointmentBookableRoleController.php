<?php

namespace App\Modules\Appointments\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppointmentBookableRole;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentBookableRoleController extends Controller
{
    use HasSchoolScope;

    public function index(): View
    {
        $schoolId = $this->activeSchoolId();
        $roles = AppointmentBookableRole::query()
            ->forSchool($schoolId)
            ->ordered()
            ->get();

        return view('appointments.settings.index', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);

        AppointmentBookableRole::create([
            'school_id'   => $this->activeSchoolId(),
            'label'       => $data['label'],
            'target_type' => $data['target_type'],
            'target_id'   => $data['target_id'] ?? null,
            'is_active'   => true,
            'sort'        => $data['sort'] ?? 0,
            'created_by'  => auth()->id(),
        ]);

        return redirect()
            ->route('admin.appointment-settings.index')
            ->with('success', __('appointments.flash_role_created'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $role = $this->findScoped($id);
        $data = $this->validateRole($request);

        $role->update([
            'label'       => $data['label'],
            'target_type' => $data['target_type'],
            'target_id'   => $data['target_id'] ?? null,
            'sort'        => $data['sort'] ?? $role->sort,
        ]);

        return redirect()
            ->route('admin.appointment-settings.index')
            ->with('success', __('appointments.flash_role_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = $this->findScoped($id);
        $role->delete();

        return redirect()
            ->route('admin.appointment-settings.index')
            ->with('success', __('appointments.flash_role_deleted'));
    }

    public function toggle(int $id): RedirectResponse
    {
        $role = $this->findScoped($id);
        $role->update(['is_active' => ! $role->is_active]);

        $msg = $role->fresh()->is_active
            ? __('appointments.flash_role_activated')
            : __('appointments.flash_role_deactivated');

        return redirect()
            ->route('admin.appointment-settings.index')
            ->with('success', $msg);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function findScoped(int $id): AppointmentBookableRole
    {
        $schoolId = $this->activeSchoolId();
        $role = AppointmentBookableRole::query()
            ->forSchool($schoolId)
            ->findOrFail($id);

        return $role;
    }

    private function validateRole(Request $request): array
    {
        return $request->validate([
            'label'       => ['required', 'string', 'max:255'],
            'target_type' => ['required', 'in:role,job_title,user,subject_teacher'],
            'target_id'   => ['nullable', 'integer', 'min:1'],
            'sort'        => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
