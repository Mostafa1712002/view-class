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
 * #265 — QR cards: list, generate (secure token), enable/disable, regenerate.
 */
class QrCardController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        $classes = ClassRoom::with('section')
            ->when($schoolId !== null, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->orderBy('name')->get();

        $students = collect();
        $cardsByStudent = collect();
        if ($request->filled('class_id') || $request->filled('name') || $request->filled('national_id')) {
            $students = User::query()
                ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
                ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
                ->when($request->filled('class_id'), fn ($q) => $q->where('class_room_id', (int) $request->class_id))
                ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->name.'%'))
                ->when($request->filled('national_id'), fn ($q) => $q->where('national_id', 'like', '%'.$request->national_id.'%'))
                ->with('classRoom')
                ->orderBy('name')->paginate(30)->withQueryString();

            $cardsByStudent = QrCard::whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        }

        return view('admin.qr.cards', compact('classes', 'students', 'cardsByStudent'));
    }

    /** Generate (or regenerate) a card for one student. */
    public function generate(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $request->validate(['student_id' => ['required', 'integer', 'exists:users,id']]);

        $student = User::findOrFail($data['student_id']);
        abort_if($schoolId !== null && (int) $student->school_id !== $schoolId, 403, 'خارج نطاق صلاحيتك.');

        $card = QrCard::withTrashed()->firstOrNew(['student_id' => $student->id]);
        $card->fill([
            'school_id' => $student->school_id,
            'token'     => QrCard::generateToken(),
            'card_code' => $card->card_code ?: QrCard::generateCardCode(),
            'is_active' => true,
        ]);
        if ($card->trashed()) {
            $card->deleted_at = null; // un-delete on regenerate
        }
        $card->save();

        ActivityLog::logCreate($card, "إنشاء/إعادة توليد بطاقة QR للطالب {$student->name}");

        return back()->with('success', 'تم إنشاء بطاقة QR بنجاح.');
    }

    public function toggle(QrCard $card): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId !== null && (int) $card->school_id !== $schoolId, 403);
        $old = $card->only('is_active');
        $card->update(['is_active' => ! $card->is_active]);
        ActivityLog::logUpdate($card, $card->is_active ? 'تفعيل بطاقة QR' : 'تعطيل بطاقة QR', $old);

        return back()->with('success', $card->is_active ? 'تم تفعيل البطاقة.' : 'تم تعطيل البطاقة.');
    }

    public function regenerate(QrCard $card): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId !== null && (int) $card->school_id !== $schoolId, 403);
        $old = $card->only('token');
        $card->update(['token' => QrCard::generateToken()]);
        ActivityLog::logUpdate($card, 'إعادة توليد رمز QR', $old);

        return back()->with('success', 'تم إعادة توليد الرمز.');
    }

    /** Printable cards page for selected students. */
    public function print(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $ids = $request->input('ids', []);
        $cards = QrCard::with('student.classRoom')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', (array) $ids))
            ->where('is_active', true)
            ->get();

        return view('admin.qr.cards-print', compact('cards'));
    }
}
