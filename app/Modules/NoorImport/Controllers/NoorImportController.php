<?php

namespace App\Modules\NoorImport\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\School;
use App\Modules\NoorImport\Actions\ClassifyNoorRows;
use App\Modules\NoorImport\Actions\ImportNoorUsersAction;
use App\Modules\NoorImport\Actions\ParseNoorExcel;
use App\Modules\NoorImport\Http\Requests\NoorImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

class NoorImportController extends Controller
{
    public function form(Request $request): View
    {
        $user = $request->user();

        $types = [
            'students'          => __('noor.types.students'),
            'students_academic' => __('noor.types.students_academic'),
            'teachers'          => __('noor.types.teachers'),
            'admins'            => __('noor.types.admins'),
        ];

        // School selection: super-admin picks any school; school-admin is locked
        // to their own.
        if ($user->isSuperAdmin()) {
            $schools = School::query()->orderBy('name')->get(['id', 'name']);
            $lockedSchoolId = null;
        } else {
            $schools = School::query()->whereKey($user->school_id)->get(['id', 'name']);
            $lockedSchoolId = $user->school_id;
        }

        $academicYears = AcademicYear::query()
            ->when($lockedSchoolId, fn ($q) => $q->where('school_id', $lockedSchoolId))
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'school_id', 'is_current']);

        $history = $this->history($user);

        return view('admin.noor.form', compact('types', 'schools', 'lockedSchoolId', 'academicYears', 'history'));
    }

    /**
     * Step 1 — قراءة الملف: parse + classify, persist preview, render معاينة.
     */
    public function preview(
        NoorImportRequest $request,
        ParseNoorExcel $parser,
        ClassifyNoorRows $classifier,
    ): View|RedirectResponse {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request, $user);

        if (! $schoolId) {
            return back()->withErrors(['school_id' => __('noor.errors.no_school')])->withInput();
        }

        $academicYearId = $request->integer('academic_year_id') ?: null;
        $file = $request->file('file');
        $type = $request->string('type')->toString();

        $stored = $file->store('noor-imports/' . date('Y/m'), 'local');

        $logId = DB::table('noor_imports')->insertGetId([
            'school_id'        => $schoolId,
            'academic_year_id' => $academicYearId,
            'user_id'          => $user->id,
            'type'             => $type,
            'original_name'    => $file->getClientOriginalName(),
            'stored_path'      => $stored,
            'status'           => 'previewed',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        try {
            $rows = $parser->execute($file);
        } catch (\RuntimeException $e) {
            DB::table('noor_imports')->where('id', $logId)->update([
                'status' => 'pending', 'note' => $e->getMessage(), 'updated_at' => now(),
            ]);
            return view('admin.noor.preview', [
                'pending' => true, 'note' => $e->getMessage(), 'log_id' => $logId,
                'rows' => [], 'counts' => [], 'type' => $type,
            ]);
        } catch (\Throwable $e) {
            DB::table('noor_imports')->where('id', $logId)->update([
                'status' => 'failed', 'note' => $e->getMessage(), 'updated_at' => now(),
            ]);
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        if (empty($rows)) {
            DB::table('noor_imports')->where('id', $logId)->update([
                'status' => 'failed', 'note' => __('noor.errors.empty_file'), 'updated_at' => now(),
            ]);
            return back()->withErrors(['file' => __('noor.errors.empty_file')])->withInput();
        }

        $classified = $classifier->execute($rows, (int) $schoolId);
        $counts = $this->countStatuses($classified);

        DB::table('noor_imports')->where('id', $logId)->update([
            'total_rows'   => count($classified),
            'preview_data' => json_encode($classified, JSON_UNESCAPED_UNICODE),
            'updated_at'   => now(),
        ]);

        return view('admin.noor.preview', [
            'pending' => false,
            'note'    => null,
            'log_id'  => $logId,
            'rows'    => $classified,
            'counts'  => $counts,
            'type'    => $type,
        ]);
    }

    /**
     * Step 2 — تنفيذ الاستيراد: apply the persisted preview rows.
     */
    public function execute(int $log, Request $request, ImportNoorUsersAction $importer): View|RedirectResponse
    {
        $user = $request->user();
        $row = $this->findOwnedLog($log, $user);

        if (! $row) {
            return redirect()->route('admin.noor.form')->withErrors(['file' => __('noor.errors.log_missing')]);
        }
        if (empty($row->preview_data)) {
            return redirect()->route('admin.noor.form')->withErrors(['file' => __('noor.errors.no_preview')]);
        }

        $previewRows = json_decode($row->preview_data, true) ?: [];
        $result = $importer->executeFromPreview($previewRows, (string) $row->type, (int) $row->school_id);

        DB::table('noor_imports')->where('id', $log)->update([
            'status'               => $result->status,
            'total_rows'           => $result->total,
            'created_count'        => $result->created,
            'updated_count'        => $result->updated,
            'failed_count'         => $result->failed,
            'parent_created_count' => $result->parentCreated,
            'parent_updated_count' => $result->parentUpdated,
            'errors'               => $result->errors ? json_encode($result->errors, JSON_UNESCAPED_UNICODE) : null,
            'updated_at'           => now(),
        ]);

        return view('admin.noor.result', [
            'result' => $result, 'pending' => false, 'note' => null, 'log_id' => $log,
        ]);
    }

    /**
     * Downloadable error report (CSV) for a given import's preview/result.
     */
    public function errorsReport(int $log, Request $request): SymfonyStreamedResponse|RedirectResponse
    {
        $user = $request->user();
        $row = $this->findOwnedLog($log, $user);
        if (! $row) {
            return redirect()->route('admin.noor.form')->withErrors(['file' => __('noor.errors.log_missing')]);
        }

        $bad = [];
        $preview = $row->preview_data ? (json_decode($row->preview_data, true) ?: []) : [];
        foreach ($preview as $pr) {
            $status = $pr['status'] ?? 'new';
            if (in_array($status, ['invalid', 'duplicate'], true)) {
                $bad[] = [
                    'row'    => $pr['rowNumber'] ?? '',
                    'name'   => $pr['name'] ?? '',
                    'id'     => $pr['nationalId'] ?? '',
                    'status' => __('noor.preview.status.' . $status),
                    'reason' => $pr['reason'] ?? '',
                ];
            }
        }
        // Add execution-time failures too.
        foreach (($row->errors ? (json_decode($row->errors, true) ?: []) : []) as $er) {
            $bad[] = ['row' => $er['row'] ?? '', 'name' => '', 'id' => '', 'status' => __('noor.preview.status.invalid'), 'reason' => $er['reason'] ?? ''];
        }

        $filename = 'noor-errors-' . $log . '.csv';

        return response()->streamDownload(function () use ($bad) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel Arabic
            fputcsv($out, [__('noor.result_row'), __('noor.preview.col_name'), __('noor.preview.col_id'), __('noor.preview.col_status'), __('noor.result_reason')]);
            foreach ($bad as $b) {
                fputcsv($out, [$b['row'], $b['name'], $b['id'], $b['status'], $b['reason']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function resolveSchoolId(Request $request, $user): ?int
    {
        if ($user->isSuperAdmin()) {
            $id = $request->integer('school_id') ?: null;
            if ($id && School::whereKey($id)->exists()) {
                return $id;
            }
            // Fallback to session scope or first school.
            return (int) (session('scope.school_id') ?: School::query()->orderBy('id')->value('id')) ?: null;
        }
        return $user->school_id ? (int) $user->school_id : null;
    }

    private function findOwnedLog(int $log, $user): ?object
    {
        $q = DB::table('noor_imports')->where('id', $log);
        if (! $user->isSuperAdmin()) {
            $q->where('school_id', $user->school_id);
        }
        return $q->first();
    }

    private function history($user)
    {
        $q = DB::table('noor_imports')->orderByDesc('id')->limit(20);
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
            if (isset($c[$s])) $c[$s]++;
        }
        return $c;
    }
}
