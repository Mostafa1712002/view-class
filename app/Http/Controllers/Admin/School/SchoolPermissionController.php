<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\School;

class SchoolPermissionController extends Controller
{
    public function index(School $school)
    {
        $roles = Role::orderBy('id')->get();
        return view('admin.schools.permissions.index', compact('school', 'roles'));
    }
}
