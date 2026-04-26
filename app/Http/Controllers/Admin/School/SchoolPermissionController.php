<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolPermissionController extends Controller
{
    public function index(School $school)
    {
        $roles = Role::where('is_active', true)->orderBy('id')->get();
        $groups = Permission::query()
            ->select('group')
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        $permissionsByGroup = Permission::orderBy('id')
            ->get()
            ->groupBy('group');

        $assignments = DB::table('school_role_permissions')
            ->where('school_id', $school->id)
            ->get()
            ->groupBy('role_id')
            ->map(fn ($rows) => $rows->pluck('permission_id')->all())
            ->all();

        $otherSchools = School::where('id', '!=', $school->id)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        return view('admin.schools.permissions.index', compact(
            'school', 'roles', 'groups', 'permissionsByGroup', 'assignments', 'otherSchools'
        ));
    }

    public function toggle(Request $request, School $school)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
            'enabled' => 'required|boolean',
        ]);

        if ($validated['enabled']) {
            DB::table('school_role_permissions')->updateOrInsert(
                [
                    'school_id' => $school->id,
                    'role_id' => $validated['role_id'],
                    'permission_id' => $validated['permission_id'],
                ],
                ['updated_at' => now(), 'created_at' => now()],
            );
        } else {
            DB::table('school_role_permissions')
                ->where('school_id', $school->id)
                ->where('role_id', $validated['role_id'])
                ->where('permission_id', $validated['permission_id'])
                ->delete();
        }

        return response()->json(['success' => true]);
    }

    public function copyFrom(Request $request, School $school)
    {
        $validated = $request->validate([
            'source_school_id' => 'required|exists:schools,id|different:' . $school->id,
        ]);

        $rows = DB::table('school_role_permissions')
            ->where('school_id', $validated['source_school_id'])
            ->get();

        DB::transaction(function () use ($school, $rows) {
            DB::table('school_role_permissions')->where('school_id', $school->id)->delete();
            $now = now();
            $insert = $rows->map(fn ($r) => [
                'school_id' => $school->id,
                'role_id' => $r->role_id,
                'permission_id' => $r->permission_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            if (!empty($insert)) {
                DB::table('school_role_permissions')->insert($insert);
            }
        });

        return back()->with('success', __('schools.permissions_copied'));
    }
}
