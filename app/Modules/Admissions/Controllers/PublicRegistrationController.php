<?php

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Modules\Admissions\Http\Requests\SubmitApplicationRequest;
use App\Modules\Admissions\Repositories\Contracts\AdmissionRepository;
use App\Modules\Admissions\Services\AdmissionSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Public (guest) registration. School comes from the LINK param — NOT from
 * session scope — because there is no authenticated user here. No auth, no
 * permission middleware, no scopedSchoolId().
 */
class PublicRegistrationController extends Controller
{
    public function __construct(
        private AdmissionRepository $applications,
        private AdmissionSettingsService $settings,
    ) {}

    /** Registration form for a specific school. */
    public function school(School $school): View|RedirectResponse
    {
        $setting = $this->settings->settings($school->id);
        if (! $setting->registration_enabled) {
            return view('admissions.public.closed', ['school' => $school]);
        }

        return view('admissions.public.form', [
            'school'   => $school,
            'fields'   => $this->settings->visibleFields($school->id),
            'sections' => $this->settings->sections($school->id)->where('is_active', true),
            'setting'  => $setting,
        ]);
    }

    /** Company-wide registration: list of schools open for registration. */
    public function company(int $company): View
    {
        $schools = School::where('educational_company_id', $company)
            ->where('is_active', true)
            ->whereIn('id', function ($q) {
                $q->select('school_id')->from('admission_school_settings')->where('registration_enabled', true);
            })
            ->orderBy('name')
            ->get();

        // Schools with no settings row default to enabled — include them too.
        $configured = \App\Modules\Admissions\Models\AdmissionSchoolSetting::pluck('school_id')->all();
        $unconfigured = School::where('educational_company_id', $company)
            ->where('is_active', true)
            ->whereNotIn('id', $configured)
            ->orderBy('name')->get();

        return view('admissions.public.company', [
            'company' => $company,
            'schools' => $schools->merge($unconfigured)->unique('id')->sortBy('name')->values(),
        ]);
    }

    /** Persist an application from the public form. */
    public function store(SubmitApplicationRequest $request, School $school): RedirectResponse
    {
        $setting = $this->settings->settings($school->id);
        abort_unless($setting->registration_enabled, 403, 'التسجيل مغلق لهذه المدرسة.');

        $validated = $request->validated();

        $application = $this->applications->create([
            'code'                   => $this->applications->nextCode(),
            'school_id'              => $school->id,
            'educational_company_id' => $school->educational_company_id,
            'academic_year_id'       => AcademicYear::where('school_id', $school->id)->where('is_current', true)->value('id'),
            'student_name'           => $validated['student_name'] ?? null,
            'guardian_name'          => $validated['guardian_name'] ?? null,
            'phone'                  => $validated['phone'] ?? null,
            'email'                  => $validated['email'] ?? null,
            'national_id'            => $validated['national_id'] ?? null,
            'hijri_code'             => $validated['hijri_code'] ?? null,
            'birth_date'             => $validated['birth_date'] ?? null,
            'city'                   => $validated['city'] ?? null,
            'nationality'            => $validated['nationality'] ?? null,
            'address'                => $validated['address'] ?? null,
            'stage'                  => $validated['stage'] ?? null,
            'grade'                  => $validated['grade'] ?? null,
            'data'                   => $validated['data'] ?? null,
            'status'                 => 'new',
            'submitted_ip'           => $request->ip(),
        ]);

        return redirect()
            ->route('admissions.public.success', ['school' => $school->id, 'code' => $application->code]);
    }

    /** Thank-you page after submission. */
    public function success(School $school, string $code): View
    {
        return view('admissions.public.success', ['school' => $school, 'code' => $code]);
    }
}
