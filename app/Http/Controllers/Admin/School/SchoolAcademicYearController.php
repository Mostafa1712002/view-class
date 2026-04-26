<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\School;

class SchoolAcademicYearController extends Controller
{
    public function index(School $school)
    {
        $years = $school->academicYears()->orderByDesc('start_date')->get();
        return view('admin.schools.academic_years.index', compact('school', 'years'));
    }
}
