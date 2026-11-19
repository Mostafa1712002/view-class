<?php

namespace App\Modules\SpecialPrograms\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Support\ProgramAudience;
use App\Modules\SpecialPrograms\Support\Programs;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Support\Facades\DB;

class HubController extends Controller
{
    use HasSchoolScope;

    public function __construct(private EnrollmentRepository $enrollments) {}

    /** active question count per program (platform-wide content) */
    private function bankCounts(): array
    {
        return DB::table('sp_questions')->join('sp_subjects', 'sp_subjects.id', '=', 'sp_questions.sp_subject_id')
            ->where('sp_questions.is_active', true)->groupBy('sp_subjects.program')
            ->selectRaw('sp_subjects.program as program, count(*) as total')->pluck('total', 'program')->map(fn ($n) => (int) $n)->all();
    }

    public function index()
    {
        $schoolId = $this->scopedSchoolId();
        $bank = $this->bankCounts();
        $cards = collect(Programs::keys())->mapWithKeys(fn ($p) => [$p => [
            'bank' => $bank[$p] ?? 0,
            'enrolled' => $schoolId ? $this->enrollments->enrolledQuery($schoolId, $p)->count() : null,
        ]]);

        return view('special-programs.staff.hub', ['cards' => $cards, 'hasSchool' => (bool) $schoolId]);
    }

    public function program(string $program)
    {
        abort_unless(in_array($program, Programs::keys(), true), 404);
        $schoolId = $this->scopedSchoolId();
        $subjects = DB::table('sp_subjects')->where('program', $program)->orderBy('id')->get(['id', 'code'])
            ->map(fn ($s) => (object) ['code' => $s->code,
                'total' => DB::table('sp_questions')->where('sp_subject_id', $s->id)->where('is_active', true)->count()]);

        return view('special-programs.staff.program', [
            'program' => $program,
            'subjects' => $subjects,
            'bankEmpty' => $subjects->sum('total') === 0,
            'hasSchool' => (bool) $schoolId,
            'enrolled' => $schoolId ? $this->enrollments->enrolledQuery($schoolId, $program)->count() : null,
            'alerts' => $schoolId ? ProgramAudience::alerts($schoolId, $program) : null,
        ]);
    }
}
