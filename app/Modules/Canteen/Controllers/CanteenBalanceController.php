<?php

namespace App\Modules\Canteen\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CanteenBalance;
use App\Models\CanteenBalanceTransaction;
use App\Models\User;
use App\Modules\Canteen\Services\CanteenBalanceService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CanteenBalanceController extends Controller
{
    use HasSchoolScope;

    public function __construct(private CanteenBalanceService $balances) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $q = trim((string) $request->get('q', ''));

        $students = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
            ->when($schoolId, fn ($w) => $w->where('users.school_id', $schoolId))
            ->when($q !== '', fn ($w) => $w->where('users.name', 'like', '%'.$q.'%'))
            ->leftJoin('sections', 'sections.id', '=', 'users.section_id')
            ->leftJoin('classes', 'classes.id', '=', 'users.class_room_id')
            ->orderBy('users.name')
            ->limit(1000)
            ->get(['users.id', 'users.name', 'sections.name as grade_name', 'classes.name as class_name']);

        $ids = $students->pluck('id');
        $balances = CanteenBalance::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereIn('student_id', $ids)->get()->keyBy('student_id');

        $lastTx = CanteenBalanceTransaction::query()
            ->whereIn('student_id', $ids)
            ->selectRaw('student_id, MAX(created_at) as last_at')
            ->groupBy('student_id')->pluck('last_at', 'student_id');

        $students->each(function ($s) use ($balances, $lastTx) {
            $b = $balances->get($s->id);
            $s->balance = $b ? $b->balance : '0.00';
            $s->daily_limit = $b?->daily_limit;
            $s->last_tx = $lastTx->get($s->id);
        });

        return view('admin.canteens.balances.index', compact('students', 'q'));
    }

    public function edit(int $studentId): View
    {
        $student = $this->findStudent($studentId);
        $schoolId = $this->activeSchoolId();
        $balance = CanteenBalance::where('school_id', $schoolId)->where('student_id', $student->id)->first();

        return view('admin.canteens.balances.edit', [
            'student' => $student,
            'balance' => $balance,
        ]);
    }

    public function update(Request $request, int $studentId): RedirectResponse
    {
        $student = $this->findStudent($studentId);
        $schoolId = $this->activeSchoolId();

        $data = $request->validate([
            'type' => ['required', Rule::in(['add', 'deduct', 'set'])],
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->balances->apply(
                $student->id,
                $schoolId,
                $data['type'],
                (float) $data['amount'],
                $data['note'] ?? null,
                'admin',
                auth()->id(),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', __('canteen.balances.flash.insufficient'));
        }

        return redirect()->route('admin.canteen-balances.index')
            ->with('status', __('canteen.balances.flash.updated'));
    }

    public function history(int $studentId): View
    {
        $student = $this->findStudent($studentId);
        $schoolId = $this->activeSchoolId();

        $transactions = CanteenBalanceTransaction::query()
            ->with('performer')
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('admin.canteens.balances.history', compact('student', 'transactions'));
    }

    private function findStudent(int $id): User
    {
        $schoolId = $this->activeSchoolId();

        return User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereKey($id)
            ->firstOrFail();
    }
}
