<?php

namespace App\Modules\SchoolSchedule\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\ScheduleEntry;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SchoolScheduleController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        [$slots, $entries, $filters] = $this->loadSchedule($request);

        $teachers = User::query()
            ->where('school_id', $this->activeSchoolId())
            ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))
            ->orderBy('name')->get();
        $subjects = Subject::query()->where('school_id', $this->activeSchoolId())->orderBy('name')->get();
        $classes = ClassRoom::query()->orderBy('grade_level')->orderBy('division')->get();

        return view('admin.school-schedule.index', compact('slots', 'entries', 'filters', 'teachers', 'subjects', 'classes'));
    }

    public function pdf(Request $request): Response
    {
        [$slots, $entries, $filters] = $this->loadSchedule($request);

        $html = view('admin.school-schedule.pdf', [
            'slots'   => $slots,
            'entries' => $entries,
            'filters' => $filters,
        ])->render();

        $tmp = storage_path('app/mpdf');
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'orientation'      => 'L',
            'default_font'     => 'xbriyaz',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'tempDir'          => $tmp,
            'margin_top'       => 12,
            'margin_bottom'    => 12,
            'margin_left'      => 10,
            'margin_right'     => 10,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetHTMLFooter(
            '<div style="text-align:center;font-size:8px;color:#94a3b8;font-family:dejavusans;">'
            . 'صفحة {PAGENO} من {nb}'
            . '</div>'
        );
        $mpdf->WriteHTML($html);

        return response(
            $mpdf->Output('school-schedule.pdf', \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="school-schedule.pdf"',
            ]
        );
    }

    private function loadSchedule(Request $request): array
    {
        $schoolId = $this->activeSchoolId();

        $filters = [
            'grade_level' => $request->input('grade_level'),
            'class_id' => $request->input('class_id'),
            'teacher_id' => $request->input('teacher_id'),
            'subject_id' => $request->input('subject_id'),
        ];

        $slots = TimeSlot::query()->where('school_id', $schoolId)->orderBy('period_no')->get();

        $query = ScheduleEntry::query()
            ->with(['classPeriod.teacher', 'classPeriod.subject', 'classPeriod.classRoom', 'timeSlot'])
            ->where('school_id', $schoolId);

        if (! empty($filters['grade_level'])) {
            $query->whereHas('classPeriod', fn ($q) => $q->where('grade_level', (int) $filters['grade_level']));
        }
        if (! empty($filters['class_id'])) {
            $query->whereHas('classPeriod', fn ($q) => $q->where('class_id', (int) $filters['class_id']));
        }
        if (! empty($filters['teacher_id'])) {
            $query->whereHas('classPeriod', fn ($q) => $q->where('teacher_id', (int) $filters['teacher_id']));
        }
        if (! empty($filters['subject_id'])) {
            $query->whereHas('classPeriod', fn ($q) => $q->where('subject_id', (int) $filters['subject_id']));
        }

        $entries = $query->get()->groupBy(fn ($e) => $e->day_of_week . '-' . $e->time_slot_id);

        return [$slots, $entries, $filters];
    }
}
