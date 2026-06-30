<?php

namespace App\Modules\Qr\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use App\Modules\Qr\Models\QrAttendanceGroup;
use App\Modules\Qr\Models\QrCard;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #265 — Link students to attendance groups (and issue cards on the fly).
 * Linking = setting qr_cards.group_id for the student's card; a missing card
 * is created so the student is immediately scannable.
 */
class QrLinkController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        $classes = ClassRoom::with('section')
            ->when($schoolId !== null, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->orderBy('name')->get();

        $groups = QrAttendanceGroup::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)->orderBy('title')->get();

        $students = collect();
        $cardsByStudent = collect();
        if ($request->filled('class_id') || $request->filled('name') || $request->filled('group_id')) {
            $students = User::query()
                ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
                ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
                ->when($request->filled('class_id'), fn ($q) => $q->where('class_room_id', (int) $request->class_id))
                ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->name.'%'))
                ->when($request->filled('group_id'), fn ($q) => $q->whereIn('id', QrCard::where('group_id', (int) $request->group_id)->pluck('student_id')))
                ->with('classRoom')
                ->orderBy('name')->paginate(30)->withQueryString();

            $cardsByStudent = QrCard::with('group')
                ->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        }

        return view('admin.qr.link', compact('classes', 'groups', 'students', 'cardsByStudent'));
    }

    /** Assign (or clear) a group for the selected students' cards; create card if missing. */
    public function assign(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'group_id'      => ['nullable', 'integer', 'exists:qr_attendance_groups,id'],
        ]);

        // Guard the target group belongs to this school (null = unlink, always allowed).
        $group = null;
        if (! empty($data['group_id'])) {
            $group = QrAttendanceGroup::findOrFail($data['group_id']);
            abort_if($schoolId !== null && (int) $group->school_id !== $schoolId, 403, 'خارج نطاق صلاحيتك.');
        }

        $count = 0;
        foreach (User::whereIn('id', $data['student_ids'])->get() as $student) {
            // Skip students outside the active school scope (fail-closed).
            if ($schoolId !== null && (int) $student->school_id !== $schoolId) {
                continue;
            }

            $card = QrCard::withTrashed()->firstOrNew(['student_id' => $student->id]);
            $card->fill([
                'school_id' => $student->school_id,
                'group_id'  => $group?->id,
                'token'     => $card->token ?: QrCard::generateToken(),
                'card_code' => $card->card_code ?: QrCard::generateCardCode(),
            ]);
            if (! $card->exists) {
                $card->is_active = true;
            }
            if ($card->trashed()) {
                $card->deleted_at = null;
            }
            $card->save();
            $count++;
        }

        ActivityLog::log('qr.link_students', $group
            ? "ربط {$count} طالب بمجموعة الحضور: {$group->title}"
            : "إلغاء ربط {$count} طالب من مجموعات الحضور");

        return back()->with('success', "تم تحديث ربط {$count} طالب.");
    }
}
