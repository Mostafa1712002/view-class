<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\School;

class SchoolGradeLevelController extends Controller
{
    public function index(School $school)
    {
        $sections = $school->sections()->withCount('classes')->get();
        return view('admin.schools.grade_levels.index', compact('school', 'sections'));
    }
}
