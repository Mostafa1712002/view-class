<?php

namespace App\Modules\Promotion\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;

class AcademicLandingController extends Controller
{
    use HasSchoolScope;

    public function promotion()
    {
        return $this->landing('admin.schools.promotion.preview');
    }

    public function termsWeeks()
    {
        return $this->landing('admin.schools.academic-years.index');
    }

    private function landing(string $targetRoute)
    {
        $schoolId = $this->scopedSchoolId();
        if ($schoolId !== null) {
            return redirect()->route($targetRoute, School::findOrFail($schoolId));
        }

        return view('admin.schools.academic-picker', [
            'schools' => $this->assignableSchools(),
            'targetRoute' => $targetRoute,
        ]);
    }
}
