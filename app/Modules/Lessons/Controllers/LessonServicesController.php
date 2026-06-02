<?php

namespace App\Modules\Lessons\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "خدمات أخرى" for the lessons module (card #91): conflicts, student
 * re-assignment, bulk schedule/time-slot deletion, course-student export,
 * and timetable (TimeTel) import.
 */
class LessonServicesController extends Controller
{
    use HasSchoolScope;

    private const DAYS = [
        0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
        4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
    ];

    /** Base query of this school's lessons (schedule periods). */
    private function lessonQuery(?int $schoolId)
    {
        return SchedulePeriod::query()
            ->with(['schedule.classRoom.section', 'subject', 'teacher'])
            ->when($schoolId, fn ($q) => $q->whereHas('schedule.classRoom.section', fn ($s) => $s->where('school_id', $schoolId)));
    }

    /** عرض التعارضات — teacher/class double-booked at the same day + period. */
    public function conflicts(): View
    {
        $schoolId = $this->activeSchoolId();
        $periods = $this->lessonQuery($schoolId)->get();

        $byTeacher = [];
        $byClass = [];
        foreach ($periods as $p) {
            $slot = $p->day_of_week.'|'.$p->period_number;
            if ($p->teacher_id) {
                $byTeacher[$p->teacher_id.'|'.$slot][] = $p;
            }
            $classId = optional($p->schedule)->class_id;
            if ($classId) {
                $byClass[$classId.'|'.$slot][] = $p;
            }
        }

        $conflicts = [];
        foreach ($byTeacher as $group) {
            if (count($group) > 1) {
                $conflicts[] = ['type' => 'teacher', 'periods' => $group];
            }
        }
        foreach ($byClass as $group) {
            if (count($group) > 1) {
                $conflicts[] = ['type' => 'class', 'periods' => $group];
            }
        }

        return view('admin.lessons.conflicts', ['conflicts' => $conflicts, 'days' => self::DAYS]);
    }

    /** إعادة إسناد الحصص للطلاب — set each lesson's students to its class roster. */
    public function reassignStudents(): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $periods = $this->lessonQuery($schoolId)->get();

        $updated = 0;
        foreach ($periods as $p) {
            $classId = optional($p->schedule)->class_id;
            if (! $classId) {
                continue;
            }
            $studentIds = DB::table('class_student')->where('class_id', $classId)->pluck('student_id')->all();
            $p->students()->sync($studentIds);
            $updated++;
        }

        return back()->with('status', __('lessons_admin.services.reassigned', ['count' => $updated]));
    }

    /** حذف بيانات الجدول — remove all lessons (schedule periods) for the school. */
    public function destroySchedule(): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $ids = $this->lessonQuery($schoolId)->pluck('schedule_periods.id')->all();

        if ($ids) {
            DB::table('lesson_students')->whereIn('schedule_period_id', $ids)->delete();
            SchedulePeriod::whereIn('id', $ids)->delete();
        }

        return back()->with('status', __('lessons_admin.services.schedule_deleted', ['count' => count($ids)]));
    }

    /** حذف كل الفترات الزمنية — remove the school's global time slots. */
    public function destroyTimeSlots(): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $count = TimeSlot::where('school_id', $schoolId)->count();
        TimeSlot::where('school_id', $schoolId)->delete();

        return back()->with('status', __('lessons_admin.services.timeslots_deleted', ['count' => $count]));
    }

    /** تصدير طلاب المقررات — CSV of every lesson and its enrolled students. */
    public function exportCourseStudents(): StreamedResponse
    {
        $schoolId = $this->activeSchoolId();
        $periods = $this->lessonQuery($schoolId)->with('students')->get();

        return response()->streamDownload(function () use ($periods) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('lessons_admin.table.teacher'), __('lessons_admin.table.subject'),
                __('lessons_admin.table.section'), __('lessons_admin.table.class'),
                __('lessons_admin.export.student_id'), __('lessons_admin.export.student_name'),
            ]);
            foreach ($periods as $p) {
                $teacher = optional($p->teacher)->name ?? '';
                $subject = optional($p->subject)->name ?? '';
                $section = optional(optional(optional($p->schedule)->classRoom)->section)->name ?? '';
                $class = optional(optional($p->schedule)->classRoom)->name ?? '';
                if ($p->students->isEmpty()) {
                    fputcsv($out, [$teacher, $subject, $section, $class, '', '']);

                    continue;
                }
                foreach ($p->students as $st) {
                    fputcsv($out, [$teacher, $subject, $section, $class, $st->national_id, $st->name]);
                }
            }
            fclose($out);
        }, 'course-students-'.date('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** استيراد الجدول — import form. */
    public function importForm(): View
    {
        $columns = ['teacher', 'subject', 'grade', 'class', 'day', 'period_no', 'start_time', 'end_time', 'room'];

        return view('admin.lessons.import', ['columns' => $columns]);
    }

    /** Downloadable CSV template for the timetable import. */
    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['teacher', 'subject', 'grade', 'class', 'day', 'period_no', 'start_time', 'end_time', 'room']);
            fputcsv($out, ['محمد أحمد', 'الرياضيات', 'المرحلة الابتدائية', 'الصف الأول', '1', '1', '08:00', '08:45', 'A1']);
            fclose($out);
        }, 'timetable_import_template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Process the uploaded timetable CSV into schedule periods. */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $schoolId = $this->activeSchoolId();
        if (! $schoolId) {
            return back()->withErrors(['file' => __('lessons_admin.services.no_school')]);
        }

        $rows = $this->readCsv($request->file('file')->getRealPath());
        if (empty($rows)) {
            return back()->withErrors(['file' => __('lessons_admin.services.empty_file')]);
        }

        $dayMap = array_flip(self::DAYS); // arabic name => index
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $r) {
            $rowNo = $i + 2;
            try {
                $section = Section::where('school_id', $schoolId)
                    ->where('name', 'like', '%'.trim($r['grade'] ?? '').'%')->first();
                $class = $section
                    ? ClassRoom::where('section_id', $section->id)->where('name', 'like', '%'.trim($r['class'] ?? '').'%')->first()
                    : null;
                if (! $class) {
                    $skipped++;
                    $errors[] = __('lessons_admin.services.row_class_missing', ['row' => $rowNo]);

                    continue;
                }

                $subject = Subject::where('name', 'like', '%'.trim($r['subject'] ?? '').'%')
                    ->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId))->first();
                $teacher = User::where('school_id', $schoolId)
                    ->where(fn ($q) => $q->where('name', 'like', '%'.trim($r['teacher'] ?? '').'%')->orWhere('username', trim($r['teacher'] ?? '')))
                    ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->first();

                $day = $dayMap[trim($r['day'] ?? '')] ?? (is_numeric($r['day'] ?? null) ? (int) $r['day'] : null);
                if ($day === null) {
                    $skipped++;
                    $errors[] = __('lessons_admin.services.row_day_missing', ['row' => $rowNo]);

                    continue;
                }

                $schedule = Schedule::firstOrCreate(
                    ['class_id' => $class->id, 'academic_year_id' => optional($class)->academic_year_id, 'semester' => 1],
                    ['is_active' => true],
                );

                SchedulePeriod::updateOrCreate(
                    ['schedule_id' => $schedule->id, 'day_of_week' => $day, 'period_number' => (int) ($r['period_no'] ?? 0)],
                    [
                        'subject_id' => optional($subject)->id,
                        'teacher_id' => optional($teacher)->id,
                        'start_time' => $r['start_time'] ?? null,
                        'end_time' => $r['end_time'] ?? null,
                        'room' => $r['room'] ?? null,
                    ],
                );
                $created++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = __('lessons_admin.services.row_error', ['row' => $rowNo, 'msg' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.lessons.index')
            ->with('status', __('lessons_admin.services.imported', ['created' => $created, 'skipped' => $skipped]))
            ->with('import_errors', array_slice($errors, 0, 20));
    }

    /** @return array<int, array<string,string>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $header = null;
        $rows = [];
        while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $line);

                continue;
            }
            if (count(array_filter($line, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($header, array_pad(array_slice($line, 0, count($header)), count($header), ''));
        }
        fclose($handle);

        return $rows;
    }
}
