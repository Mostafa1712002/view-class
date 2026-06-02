<?php

namespace App\Modules\StudentImport\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Section;
use App\Modules\StudentImport\Actions\ClassifyStudentRows;
use App\Modules\StudentImport\Actions\ImportStudentsAction;
use App\Modules\StudentImport\Actions\ParseStudentExcel;
use App\Modules\StudentImport\Http\Requests\StudentImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

class StudentImportController extends Controller
{
    public function form(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $schools = School::query()->orderBy('name')->get(['id', 'name']);
            $lockedSchoolId = null;
        } else {
            $schools = School::query()->whereKey($user->school_id)->get(['id', 'name']);
            $lockedSchoolId = $user->school_id;
        }

        // Grades (sections) for the copy-buttons. Super-admin: scope by session school.
        $scopeSchoolId = $lockedSchoolId ?: (int) (session('scope.school_id') ?: 0);
        $grades = $scopeSchoolId
            ? Section::where('school_id', $scopeSchoolId)->orderBy('name')->pluck('name')
            : collect();

        $columns = $this->templateColumns();
        $history = $this->history($user);

        return view('admin.students_import.form', compact(
            'schools', 'lockedSchoolId', 'grades', 'columns', 'history'
        ));
    }

    /** Download the official Excel template committed in the repo. */
    public function template(): BinaryFileResponse
    {
        $path = resource_path('templates/students_import.xlsx');

        return response()->download($path, 'students_import.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Step 1 — قراءة الملف: parse + classify, persist preview, render معاينة. */
    public function preview(
        StudentImportRequest $request,
        ParseStudentExcel $parser,
        ClassifyStudentRows $classifier,
    ): View|RedirectResponse {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        if (! $schoolId) {
            return back()->withErrors(['school_id' => __('student_import.errors.no_school')])->withInput();
        }

        $file = $request->file('file');
        $stored = $file->store('student-imports/'.date('Y/m'), 'local');

        $logId = DB::table('student_imports')->insertGetId([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $stored,
            'status' => 'previewed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $rows = $parser->execute($file);
        } catch (\Throwable $e) {
            DB::table('student_imports')->where('id', $logId)->update([
                'status' => 'failed', 'note' => $e->getMessage(), 'updated_at' => now(),
            ]);

            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        if (empty($rows)) {
            DB::table('student_imports')->where('id', $logId)->update([
                'status' => 'failed', 'note' => __('student_import.errors.empty_file'), 'updated_at' => now(),
            ]);

            return back()->withErrors(['file' => __('student_import.errors.empty_file')])->withInput();
        }

        $classified = $classifier->execute($rows, (int) $schoolId);
        $counts = $this->countStatuses($classified);

        // Never persist the raw Password cell in cleartext. Encrypt it inside the
        // stored preview; execute() decrypts it back. The in-memory $classified
        // (passed to the view, which never renders the password) stays as-is.
        $forStorage = array_map(function ($r) {
            if (! empty($r['password'])) {
                $r['password'] = Crypt::encryptString((string) $r['password']);
            }

            return $r;
        }, $classified);

        DB::table('student_imports')->where('id', $logId)->update([
            'total_rows' => count($classified),
            'preview_data' => json_encode($forStorage, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);

        return view('admin.students_import.preview', [
            'log_id' => $logId,
            'rows' => $classified,
            'counts' => $counts,
        ]);
    }

    /** Step 2 — تنفيذ الاستيراد: apply the persisted preview rows. */
    public function execute(int $log, Request $request, ImportStudentsAction $importer): View|RedirectResponse
    {
        $user = $request->user();
        $row = $this->findOwnedLog($log, $user);

        if (! $row) {
            return redirect()->route('admin.users.students.import.form')->withErrors(['file' => __('student_import.errors.log_missing')]);
        }
        if (empty($row->preview_data)) {
            return redirect()->route('admin.users.students.import.form')->withErrors(['file' => __('student_import.errors.no_preview')]);
        }

        $previewRows = json_decode($row->preview_data, true) ?: [];
        $result = $importer->executeFromPreview($previewRows, (int) $row->school_id);

        // Drop the (encrypted) password from the stored preview now that the
        // import has run — it is no longer needed and must not linger at rest.
        $purged = array_map(function ($r) {
            unset($r['password']);

            return $r;
        }, $previewRows);

        DB::table('student_imports')->where('id', $log)->update([
            'status' => $result->status,
            'total_rows' => $result->total,
            'created_count' => $result->created,
            'updated_count' => $result->updated,
            'failed_count' => $result->failed,
            'parent_created_count' => $result->parentCreated,
            'preview_data' => json_encode($purged, JSON_UNESCAPED_UNICODE),
            'errors' => $result->errors ? json_encode($result->errors, JSON_UNESCAPED_UNICODE) : null,
            'updated_at' => now(),
        ]);

        return view('admin.students_import.result', [
            'result' => $result, 'log_id' => $log,
        ]);
    }

    /** Downloadable CSV error report. */
    public function errorsReport(int $log, Request $request): SymfonyStreamedResponse|RedirectResponse
    {
        $user = $request->user();
        $row = $this->findOwnedLog($log, $user);
        if (! $row) {
            return redirect()->route('admin.users.students.import.form')->withErrors(['file' => __('student_import.errors.log_missing')]);
        }

        $bad = [];
        $preview = $row->preview_data ? (json_decode($row->preview_data, true) ?: []) : [];
        foreach ($preview as $pr) {
            $status = $pr['status'] ?? 'new';
            if (in_array($status, ['invalid', 'duplicate'], true)) {
                $bad[] = [
                    'row' => $pr['rowNumber'] ?? '',
                    'name' => $pr['name'] ?? '',
                    'id' => $pr['nationalId'] ?? '',
                    'reason' => $pr['reason'] ?? '',
                ];
            }
        }
        foreach (($row->errors ? (json_decode($row->errors, true) ?: []) : []) as $er) {
            $bad[] = ['row' => $er['row'] ?? '', 'name' => '', 'id' => '', 'reason' => $er['reason'] ?? ''];
        }

        $filename = 'student-import-errors-'.$log.'.csv';

        return response()->streamDownload(function () use ($bad) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $safe = fn ($v) => $this->csvSafe($v);
            fputcsv($out, array_map($safe, [__('student_import.result.row'), __('student_import.preview.col_name'), __('student_import.preview.col_id'), __('student_import.result.reason')]));
            foreach ($bad as $b) {
                fputcsv($out, array_map($safe, [$b['row'], $b['name'], $b['id'], $b['reason']]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralise CSV/Excel formula injection from user-supplied cell values. */
    private function csvSafe($value): string
    {
        $s = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $s) ? "'".$s : $s;
    }

    private function resolveSchoolId(Request $request, $user): ?int
    {
        if ($user->isSuperAdmin()) {
            $id = $request->integer('school_id') ?: null;
            if ($id && School::whereKey($id)->exists()) {
                return $id;
            }

            return (int) (session('scope.school_id') ?: School::query()->orderBy('id')->value('id')) ?: null;
        }

        return $user->school_id ? (int) $user->school_id : null;
    }

    private function findOwnedLog(int $log, $user): ?object
    {
        $q = DB::table('student_imports')->where('id', $log);
        if (! $user->isSuperAdmin()) {
            $q->where('school_id', $user->school_id);
        }

        return $q->first();
    }

    private function history($user)
    {
        $q = DB::table('student_imports')->orderByDesc('id')->limit(20);
        if (! $user->isSuperAdmin() && $user->school_id) {
            $q->where('school_id', $user->school_id);
        }

        return $q->get();
    }

    private function countStatuses(array $rows): array
    {
        $c = ['new' => 0, 'update' => 0, 'duplicate' => 0, 'invalid' => 0];
        foreach ($rows as $r) {
            $s = $r['status'] ?? 'new';
            if (isset($c[$s])) {
                $c[$s]++;
            }
        }

        return $c;
    }

    /** The 30 template columns for the on-page reference table. */
    private function templateColumns(): array
    {
        return [
            'Identity Number *', 'Acceptance Year', 'First Name *', 'Last Name *',
            'Father Name *', 'Grand father name', 'Username', 'Password', 'Grade', 'Class',
            'Gender', 'Mobile Number', 'Email', 'Birthdate', 'Birth Place', 'Nationality',
            'Passport ID', 'Academic ID', 'Previous School', 'Fingerprint ID',
            'Father Identity Number', 'Father Mobile number', 'Mother Identity Number',
            'Mother Full Name', 'mother mobile number', 'First name (English)',
            'Father name (English)', 'Grand father name (English)', 'Last name (English)',
            'Sit Number',
        ];
    }
}
