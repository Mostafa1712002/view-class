<?php

namespace App\Modules\SpecialPrograms\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Controllers\Concerns\ExportsReports;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\ReportRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    use ExportsReports;

    public function __construct(private ReportRepository $reports, private EnrollmentRepository $enrollments) {}

    public function show(string $program): View
    {
        return view('special-programs.student.report', [
            'program' => $program,
            'report' => $this->reportFor($program),
        ]);
    }

    public function pdf(string $program): Response
    {
        return $this->exportPdf('special-programs.partials.student-report-pdf', ['report' => $this->reportFor($program)], "performance-report-{$program}.pdf");
    }

    private function reportFor(string $program): array
    {
        $student = auth()->user();
        abort_unless($this->enrollments->isEnrolled($student->id, $program), 404);

        return $this->reports->studentReport($student->id, $program);
    }
}
