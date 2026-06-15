<?php

namespace App\Modules\Admissions\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\Notification;
use App\Models\School;
use App\Models\Section;
use App\Modules\Admissions\Actions\ConvertToStudentAction;
use App\Modules\Admissions\Models\AdmissionApplication;
use App\Modules\Admissions\Repositories\Contracts\AdmissionRepository;
use App\Modules\Admissions\Services\AdmissionSettingsService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admissions / Registration — staff side (#268). All actions are school-scoped
 * via HasSchoolScope (fail-closed) and gated by permission:admissions.* in the
 * route definitions.
 */
class AdmissionController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private AdmissionRepository $applications,
        private AdmissionSettingsService $settings,
    ) {}

    /** Applications index (table + filters + counts + settings buttons). */
    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        $filters = [
            'q'      => $request->input('q'),
            'status' => $request->input('status'),
            'city'   => $request->input('city'),
        ];

        $applications = $this->applications->paginate($schoolId, $filters);
        $counts       = $this->applications->statusCounts($schoolId);

        $companyLink = $this->companyLink($schoolId);
        $schoolLink  = $schoolId ? route('admissions.public.school', $schoolId) : null;

        return view('admissions.admin.index', compact(
            'applications', 'counts', 'filters', 'companyLink', 'schoolLink', 'schoolId'
        ));
    }

    public function show(int $id): View
    {
        $application = $this->findOrFail($id);

        return view('admissions.admin.show', [
            'application' => $application,
            'classes'     => $this->scopedClasses(),
            'sections'    => $this->scopedSections(),
        ]);
    }

    public function edit(int $id): View
    {
        return view('admissions.admin.edit', ['application' => $this->findOrFail($id)]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $application = $this->findOrFail($id);

        $data = $request->validate([
            'student_name'  => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:32'],
            'email'         => ['nullable', 'email', 'max:255'],
            'national_id'   => ['nullable', 'string', 'max:32'],
            'hijri_code'    => ['nullable', 'string', 'max:32'],
            'birth_date'    => ['nullable', 'date'],
            'city'          => ['nullable', 'string', 'max:255'],
            'track'         => ['nullable', 'string', 'max:255'],
            'stage'         => ['nullable', 'string', 'max:255'],
            'grade'         => ['nullable', 'string', 'max:255'],
            'nationality'   => ['nullable', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $application->getOriginal();
        $this->applications->update($application, $data);
        ActivityLog::logUpdate($application, "تعديل طلب القبول {$application->code}", $old);

        return redirect()->route('admissions.show', $application->id)->with('success', 'تم حفظ التعديلات.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $application = $this->findOrFail($id);
        $code = $application->code;
        $this->applications->delete($application);
        ActivityLog::logDelete($application, "حذف طلب القبول {$code}");

        return redirect()->route('admissions.index')->with('success', 'تم حذف الطلب.');
    }

    /** Change application status (covers review / accept / reject / etc.). */
    public function changeStatus(Request $request, int $id): RedirectResponse
    {
        $application = $this->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(AdmissionApplication::STATUSES))],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        $old = $application->getOriginal();
        $this->applications->update($application, [
            'status'      => $data['status'],
            'status_note' => $data['note'] ?? $application->status_note,
            'reviewed_by' => $request->user()->id,
        ]);

        ActivityLog::log(
            'admissions.change_status',
            "تغيير حالة طلب القبول {$application->code} إلى ".$application->fresh()->statusLabel(),
            $application, ['status' => $old['status']], ['status' => $data['status']]
        );

        return back()->with('success', 'تم تغيير حالة الطلب.');
    }

    /** Schedule an interview/appointment — stores appointment_at + sets status. */
    public function schedule(Request $request, int $id): RedirectResponse
    {
        $application = $this->findOrFail($id);
        $data = $request->validate(['appointment_at' => ['required', 'date']]);

        $this->applications->update($application, [
            'appointment_at' => Carbon::parse($data['appointment_at']),
            'status'         => 'scheduled',
            'reviewed_by'    => $request->user()->id,
        ]);

        ActivityLog::log('admissions.schedule', "تحديد موعد لطلب القبول {$application->code}", $application);

        return back()->with('success', 'تم تحديد الموعد.');
    }

    /** Send an in-app message to the applicant's converted student (if any). */
    public function message(Request $request, int $id): RedirectResponse
    {
        $application = $this->findOrFail($id);
        $data = $request->validate(['message' => ['required', 'string', 'min:3', 'max:1000']]);

        if ($application->converted_student_id) {
            Notification::create([
                'user_id' => $application->converted_student_id,
                'type'    => 'general',
                'title'   => 'رسالة بخصوص طلب القبول',
                'body'    => $data['message'],
                'icon'    => 'bi-envelope',
                'color'   => 'info',
            ]);
        }

        ActivityLog::log('admissions.message', "إرسال رسالة بخصوص طلب القبول {$application->code}", $application);

        return back()->with('success', $application->converted_student_id
            ? 'تم إرسال الرسالة.'
            : 'تم تسجيل الرسالة (لا يوجد حساب طالب مرتبط بعد).');
    }

    /** Convert an application to a real student account. */
    public function convert(Request $request, int $id, ConvertToStudentAction $action): RedirectResponse
    {
        $application = $this->findOrFail($id);

        if ($application->converted_student_id) {
            return back()->with('error', 'تم تحويل هذا الطلب إلى طالب مسبقًا.');
        }

        $schoolId = $this->scopedSchoolId();
        $options = $request->validate([
            'class_room_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where(
                fn ($q) => $schoolId ? $q->whereIn('section_id', DB::table('sections')->where('school_id', $schoolId)->select('id')) : $q
            )],
            'section_id'    => ['nullable', 'integer', Rule::exists('sections', 'id')->where(
                fn ($q) => $schoolId ? $q->where('school_id', $schoolId) : $q
            )],
        ]);

        $student = $action->execute($application, $options);

        return redirect()->route('admissions.show', $application->id)
            ->with('success', "تم تحويل الطلب إلى طالب: {$student->name}.");
    }

    /** CSV export of applications matching the current filter ("تصدير بحسب البحث"). */
    public function export(Request $request): StreamedResponse
    {
        $schoolId = $this->scopedSchoolId();
        $rows = $this->applications->all($schoolId, [
            'q'      => $request->input('q'),
            'status' => $request->input('status'),
            'city'   => $request->input('city'),
        ]);

        ActivityLog::log('admissions.export', "تصدير {$rows->count()} طلب قبول");

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="admissions-'.now()->format('Ymd_His').'.csv"',
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, ['كود الطلب', 'تاريخ الطلب', 'اسم الطالب', 'اسم ولي الأمر', 'الجوال',
                'الهوية', 'الكود الهجري', 'المدينة', 'المسار', 'المرحلة', 'الصف', 'الحالة', 'الموعد']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->code, optional($r->created_at)->format('Y-m-d'), $r->student_name, $r->guardian_name,
                    $r->phone, $r->national_id, $r->hijri_code, $r->city, $r->track, $r->stage, $r->grade,
                    $r->statusLabel(), optional($r->appointment_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /** Print-friendly single application. */
    public function print(int $id): View
    {
        return view('admissions.admin.print', ['application' => $this->findOrFail($id)]);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function findOrFail(int $id): AdmissionApplication
    {
        $application = $this->applications->find($id, $this->scopedSchoolId());
        abort_if($application === null, 404, 'الطلب غير موجود أو خارج نطاق صلاحيتك.');

        return $application;
    }

    private function companyLink(?int $schoolId): ?string
    {
        $companyId = $schoolId
            ? School::whereKey($schoolId)->value('educational_company_id')
            : null;

        return $companyId ? route('admissions.public.company', $companyId) : null;
    }

    private function scopedClasses()
    {
        $schoolId = $this->scopedSchoolId();

        return ClassRoom::with('section')
            ->when($schoolId !== null, fn (Builder $q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->orderBy('name')->get();
    }

    private function scopedSections()
    {
        $schoolId = $this->scopedSchoolId();

        return Section::when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get();
    }
}
