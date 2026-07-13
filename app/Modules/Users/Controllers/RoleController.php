<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Modules\Users\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Global roles → permissions editor (.kiro/specs/roles-permissions §3.2).
 *
 * Roles (super-admin/school-admin/teacher/…) are GLOBAL — shared by every
 * school — so this editor is super-admin only. The `permission:roles.*` route
 * middleware already restricts it, but `roles.view` is a ".view" slug that the
 * canDo() default-allow rule would otherwise leak to school-admins; the explicit
 * isSuperAdmin() guard below is the belt-and-braces that satisfies US-007
 * (school-admin → 403) without touching canDo().
 */
class RoleController extends Controller
{
    private function assertSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function index(): View
    {
        $this->assertSuperAdmin();

        $roles = Role::withCount('users')->orderBy('id')->get();

        return view('admin.roles.index', ['roles' => $roles]);
    }

    public function editPermissions(Role $role): View
    {
        $this->assertSuperAdmin();
        $role->load('permissions');

        // Currently-held permissions, keyed by slug for fast pre-checking.
        $configured = $role->permissions->keyBy(fn ($p) => $p->slug);

        // Real permission rows behind the catalog, keyed by slug.
        $permModels = Permission::whereIn('slug', PermissionCatalog::allSlugs())->get()->keyBy('slug');

        return view('admin.roles.permissions', [
            'role'        => $role,
            'modules'     => PermissionCatalog::MODULES,
            'configured'  => $configured,
            'permModels'  => $permModels,
        ]);
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $this->assertSuperAdmin();

        // Never let the super-admin bypass be revoked from the UI (US-003).
        if ($role->slug === 'super-admin') {
            return redirect()
                ->route('admin.roles.permissions.edit', $role)
                ->with('status', 'دور مدير النظام يملك صلاحيات كاملة دائماً ولا يمكن تعديلها.');
        }

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        // Only real permission slugs are synced; unknown slugs are ignored so the
        // grid may list actions ahead of their seeding (US-002).
        $selectedSlugs = $request->input('permissions', []);
        $selectedIds = Permission::whereIn('slug', $selectedSlugs)->pluck('id');

        // Preserve any permission_role rows the grid does NOT render (slugs outside
        // the catalog, e.g. legacy classes.*/sections.* grants). sync() replaces the
        // whole pivot, so without this the grid would silently drop permissions it
        // never displayed — keep the editor scoped to what it actually manages.
        $catalogIds = Permission::whereIn('slug', PermissionCatalog::allSlugs())->pluck('id');
        $preservedIds = $role->permissions()->pluck('permissions.id')->diff($catalogIds);

        $role->permissions()->sync($selectedIds->merge($preservedIds)->unique()->values()->all());

        return redirect()
            ->route('admin.roles.permissions.edit', $role)
            ->with('status', 'تم حفظ صلاحيات الدور بنجاح');
    }
}
