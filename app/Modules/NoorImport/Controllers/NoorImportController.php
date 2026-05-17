<?php

namespace App\Modules\NoorImport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NoorImport\Actions\ImportNoorUsersAction;
use App\Modules\NoorImport\Actions\ParseNoorExcel;
use App\Modules\NoorImport\Http\Requests\NoorImportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class NoorImportController extends Controller
{
    public function form(): View
    {
        $types = [
            'students'          => __('noor.types.students'),
            'students_academic' => __('noor.types.students_academic'),
            'teachers'          => __('noor.types.teachers'),
            'admins'            => __('noor.types.admins'),
        ];
        return view('admin.noor.form', compact('types'));
    }

    public function submit(
        NoorImportRequest $request,
        ParseNoorExcel $parser,
        ImportNoorUsersAction $importer,
    ): View|RedirectResponse {
        $user = $request->user();
        $schoolId = $user->school_id;

        // Super-admin without a school assignment imports into the first
        // school for now; school-admin must have a school.
        if (! $schoolId) {
            if ($user->isSuperAdmin()) {
                $schoolId = \App\Models\School::query()->orderBy('id')->value('id');
            }
            if (! $schoolId) {
                return back()->withErrors(['file' => __('noor.errors.no_school')])->withInput();
            }
        }

        $file = $request->file('file');
        $type = $request->string('type')->toString();

        // Persist the upload immediately so we never lose it, even if parse fails.
        $stored = $file->store('noor-imports/' . date('Y/m'), 'local');

        $logId = DB::table('noor_imports')->insertGetId([
            'school_id'     => $schoolId,
            'user_id'       => $user->id,
            'type'          => $type,
            'original_name' => $file->getClientOriginalName(),
            'stored_path'   => $stored,
            'status'        => 'processing',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        try {
            $rows = $parser->execute($file);
        } catch (\RuntimeException $e) {
            // Likely the "missing Excel library" case for .xlsx without phpoffice.
            DB::table('noor_imports')->where('id', $logId)->update([
                'status' => 'pending',
                'note'   => $e->getMessage(),
                'updated_at' => now(),
            ]);
            return view('admin.noor.result', [
                'result' => null,
                'pending' => true,
                'note' => $e->getMessage(),
                'log_id' => $logId,
            ]);
        } catch (\Throwable $e) {
            DB::table('noor_imports')->where('id', $logId)->update([
                'status' => 'failed',
                'note'   => $e->getMessage(),
                'updated_at' => now(),
            ]);
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        if (empty($rows)) {
            DB::table('noor_imports')->where('id', $logId)->update([
                'status' => 'failed',
                'note'   => __('noor.errors.empty_file'),
                'updated_at' => now(),
            ]);
            return back()->withErrors(['file' => __('noor.errors.empty_file')])->withInput();
        }

        $result = $importer->execute($rows, $type, (int) $schoolId);

        DB::table('noor_imports')->where('id', $logId)->update([
            'status'         => $result->status,
            'total_rows'     => $result->total,
            'created_count'  => $result->created,
            'updated_count'  => $result->updated,
            'failed_count'   => $result->failed,
            'errors'         => $result->errors ? json_encode($result->errors, JSON_UNESCAPED_UNICODE) : null,
            'updated_at'     => now(),
        ]);

        return view('admin.noor.result', [
            'result'  => $result,
            'pending' => false,
            'note'    => null,
            'log_id'  => $logId,
        ]);
    }
}
