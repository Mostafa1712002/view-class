<?php

namespace App\Modules\SpecialPrograms\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Controllers\Concerns\ExportsReports;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ReportRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class ProgramController extends Controller
{
    use ExportsReports;

    public function __construct(private ReportRepository $reports, private EnrollmentRepository $enrollments) {}

    public function index(): View
    {
        $children = auth()->user()->children()->orderBy('users.name')->get(['users.id', 'users.name'])
            ->each(fn ($c) => $c->setAttribute('sp_programs', $this->enrollments->programsOf($c->id)));

        return view('special-programs.parent.index', ['children' => $children]);
    }

    public function report(int $childId, string $program): View
    {
        return view('special-programs.parent.report', [
            'childId' => $childId,
            'program' => $program,
            'report' => $this->reportFor($childId, $program),
            'children' => auth()->user()->children()->orderBy('users.name')->get(['users.id', 'users.name']),
        ]);
    }

    public function pdf(int $childId, string $program): Response
    {
        return $this->exportPdf('special-programs.partials.student-report-pdf', ['report' => $this->reportFor($childId, $program)], "performance-report-{$childId}-{$program}.pdf");
    }

    /** only the parent's own children; anything else is a 404 (no existence leak). */
    private function reportFor(int $childId, string $program): array
    {
        $child = auth()->user()->children()->where('users.id', $childId)->firstOrFail();
        abort_unless($this->enrollments->isEnrolled($child->id, $program), 404);

        return $this->reports->studentReport($child->id, $program);
    }
}
