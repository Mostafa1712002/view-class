<?php

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use App\Modules\Admissions\Models\AdmissionFormField;
use App\Modules\Admissions\Models\AdmissionInfoSection;
use App\Modules\Admissions\Services\AdmissionSettingsService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admissions settings surfaces (#268): school availability, form fields,
 * registration-info sections. All school-scoped + permission-gated in routes.
 */
class AdmissionSettingsController extends Controller
{
    use HasSchoolScope;

    public function __construct(private AdmissionSettingsService $settings) {}

    // ── إعدادات المدرسة (schools available for registration) ─────────────────

    public function schoolSettings(): View
    {
        $schoolId = $this->scopedSchoolId();

        // Super-admin (null scope): show all schools; school-admin: own school only.
        $schools = School::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('id', $schoolId))
            ->orderBy('name')->get();

        $enabled = \App\Modules\Admissions\Models\AdmissionSchoolSetting::pluck('registration_enabled', 'school_id');

        return view('admissions.admin.school_settings', compact('schools', 'enabled'));
    }

    public function saveSchoolSettings(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $request->validate([
            'schools'   => ['nullable', 'array'],
            'schools.*' => ['integer'],
        ]);

        $allowed = School::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('id', $schoolId))
            ->pluck('id');

        $enabledIds = collect($data['schools'] ?? [])->map(fn ($v) => (int) $v);

        foreach ($allowed as $id) {
            $this->settings->settings($id)->update([
                'registration_enabled' => $enabledIds->contains($id),
            ]);
        }

        ActivityLog::log('admissions.edit_school_settings', 'تعديل المدارس المتاحة بالتسجيل');

        return back()->with('success', 'تم حفظ إعدادات المدارس.');
    }

    // ── إعدادات التسجيل (form fields) ────────────────────────────────────────

    public function formSettings(): View
    {
        $schoolId = $this->requireSchool();
        $fields   = $this->settings->fields($schoolId);
        $setting  = $this->settings->settings($schoolId);

        return view('admissions.admin.form_settings', compact('fields', 'setting'));
    }

    public function saveFormSettings(Request $request): RedirectResponse
    {
        $schoolId = $this->requireSchool();
        $data = $request->validate([
            'form_title'           => ['nullable', 'string', 'max:255'],
            'fields'               => ['required', 'array'],
            'fields.*.id'          => ['required', 'integer'],
            'fields.*.is_visible'  => ['nullable', 'boolean'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.sort_order'  => ['nullable', 'integer'],
        ]);

        $this->settings->settings($schoolId)->update(['form_title' => $data['form_title'] ?? null]);

        foreach ($data['fields'] as $row) {
            AdmissionFormField::where('school_id', $schoolId)->where('id', $row['id'])->update([
                'is_visible'  => (bool) ($row['is_visible'] ?? false),
                'is_required' => (bool) ($row['is_required'] ?? false),
                'sort_order'  => (int) ($row['sort_order'] ?? 0),
            ]);
        }

        ActivityLog::log('admissions.edit_settings', 'تعديل إعدادات استمارة التسجيل');

        return back()->with('success', 'تم حفظ إعدادات الاستمارة.');
    }

    // ── معلومات التسجيل (info sections) ──────────────────────────────────────

    public function infoIndex(): View
    {
        $schoolId = $this->requireSchool();
        $sections = $this->settings->sections($schoolId);

        return view('admissions.admin.info_index', compact('sections'));
    }

    public function infoEdit(int $id): View
    {
        $schoolId = $this->requireSchool();
        $section  = AdmissionInfoSection::where('school_id', $schoolId)->findOrFail($id);

        return view('admissions.admin.info_edit', compact('section'));
    }

    public function infoUpdate(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->requireSchool();
        $section  = AdmissionInfoSection::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'content'    => ['nullable', 'string'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $section->update([
            'title'      => $data['title'],
            'sort_order' => (int) ($data['sort_order'] ?? $section->sort_order),
            'content'    => $data['content'] ?? null,
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ]);

        ActivityLog::log('admissions.edit_info', "تعديل قسم معلومات التسجيل: {$section->title}", $section);

        return redirect()->route('admissions.info.index')->with('success', 'تم حفظ القسم.');
    }

    /**
     * Settings that target a single school need a concrete school id. A
     * super-admin viewing "all schools" (null scope) must pick one first.
     */
    private function requireSchool(): int
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId === null, 400, 'اختر مدرسة محددة أولًا لتعديل إعدادات الاستمارة.');

        return $schoolId;
    }
}
