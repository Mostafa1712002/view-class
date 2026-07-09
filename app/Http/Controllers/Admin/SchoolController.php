<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalCompany;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::query()
            ->withCount(['users', 'sections', 'classes'])
            ->withCount([
                'users as students_count' => function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->where('slug', 'student'));
                },
                'users as licensed_students_count' => function ($q) {
                    $q->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
                        ->where('status', 'active');
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(15);

        return view('admin.schools.index', compact('schools'));
    }

    public function create()
    {
        $companies = EducationalCompany::orderBy('name_ar')->get();
        $branches = \App\Modules\SchoolBranches\Models\SchoolBranch::where('is_active', true)
            ->orderBy('name_ar')->get();
        $cities = config('saudi_cities', []);
        $timezones = $this->timezoneOptions();
        return view('admin.schools.create', compact('companies', 'branches', 'cities', 'timezones'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSchool($request);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('schools', 'public');
        }

        // Backfill the legacy `name` column from name_ar so old code keeps working.
        $validated['name'] = $validated['name'] ?? $validated['name_ar'];
        // Auto-generate code if not given (e.g. DEMO-AUTO-3).
        $validated['code'] = $validated['code'] ?? $this->generateCode($validated['name_ar']);

        School::create($validated);

        return redirect()->route('admin.schools.index')
            ->with('success', __('common.created_successfully'));
    }

    public function show(School $school)
    {
        $school->load(['sections', 'academicYears', 'educationalCompany']);
        return view('admin.schools.show', compact('school'));
    }

    public function edit(School $school)
    {
        $companies = EducationalCompany::orderBy('name_ar')->get();
        $branches = \App\Modules\SchoolBranches\Models\SchoolBranch::where('is_active', true)
            ->orderBy('name_ar')->get();
        $cities = config('saudi_cities', []);
        $timezones = $this->timezoneOptions();
        return view('admin.schools.edit', compact('school', 'companies', 'branches', 'cities', 'timezones'));
    }

    public function update(Request $request, School $school)
    {
        $validated = $this->validateSchool($request, $school->id);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('schools', 'public');
        }

        $validated['name'] = $validated['name'] ?? $validated['name_ar'];

        $school->update($validated);

        return redirect()->route('admin.schools.index')
            ->with('success', __('common.updated_successfully'));
    }

    public function destroy(School $school)
    {
        if ($school->users()->count() > 0) {
            return back()->with('error', __('schools.cannot_delete_has_users'));
        }

        $school->delete();

        return redirect()->route('admin.schools.index')
            ->with('success', __('common.deleted_successfully'));
    }

    private function validateSchool(Request $request, ?int $schoolId = null): array
    {
        $codeRule = 'nullable|string|max:50|unique:schools,code';
        if ($schoolId) {
            $codeRule .= ',' . $schoolId;
        }

        $cityKeys = array_keys(config('saudi_cities', []));
        $cityRule = $cityKeys ? 'required|in:' . implode(',', $cityKeys) : 'required|string|max:100';

        $tzKeys = array_keys($this->timezoneOptions());
        $tzRule = 'required|in:' . implode(',', $tzKeys);

        return $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'branch_id' => 'nullable|exists:school_branches,id',
            'sort_order' => 'nullable|integer|min:0',
            'educational_track' => 'nullable|in:national,international,general,k12',
            'stage' => 'required|in:primary,intermediate,secondary',
            'city' => $cityRule,
            'student_gender' => 'required|in:boys,girls,mixed',
            'timezone' => $tzRule,
            'default_language' => 'required|in:ar,en',
            'educational_company_id' => 'nullable|exists:educational_companies,id',
            'code' => $codeRule,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'fax' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean',
        ]);
    }

    /**
     * Curated timezone list for the schools form. Defaults are biased toward
     * KSA (the platform's primary market) but include neighbours so multinational
     * companies can use the same select.
     *
     * @return array<string,string>
     */
    private function timezoneOptions(): array
    {
        return [
            'Asia/Riyadh'   => 'Asia/Riyadh (UTC+3)',
            'Asia/Aden'     => 'Asia/Aden (UTC+3)',
            'Asia/Kuwait'   => 'Asia/Kuwait (UTC+3)',
            'Asia/Bahrain'  => 'Asia/Bahrain (UTC+3)',
            'Asia/Qatar'    => 'Asia/Qatar (UTC+3)',
            'Asia/Dubai'    => 'Asia/Dubai (UTC+4)',
            'Asia/Muscat'   => 'Asia/Muscat (UTC+4)',
            'Asia/Baghdad'  => 'Asia/Baghdad (UTC+3)',
            'Asia/Amman'    => 'Asia/Amman (UTC+3)',
            'Asia/Damascus' => 'Asia/Damascus (UTC+3)',
            'Asia/Beirut'   => 'Asia/Beirut (UTC+3)',
            'Asia/Jerusalem'=> 'Asia/Jerusalem (UTC+3)',
            'Africa/Cairo'  => 'Africa/Cairo (UTC+2)',
            'Africa/Khartoum'=> 'Africa/Khartoum (UTC+2)',
            'Africa/Tripoli'=> 'Africa/Tripoli (UTC+2)',
            'Africa/Tunis'  => 'Africa/Tunis (UTC+1)',
            'Africa/Algiers'=> 'Africa/Algiers (UTC+1)',
            'Africa/Casablanca'=> 'Africa/Casablanca (UTC+1)',
            'Europe/Istanbul'=> 'Europe/Istanbul (UTC+3)',
            'UTC'           => 'UTC',
        ];
    }

    private function generateCode(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name) ?: 'SCH', 0, 6));
        $i = 1;
        do {
            $code = $base . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $i++;
        } while (School::withTrashed()->where('code', $code)->exists());
        return $code;
    }
}
