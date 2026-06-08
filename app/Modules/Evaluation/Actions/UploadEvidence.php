<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\Evaluation;
use App\Models\EvaluationEvidence;
use App\Models\File;
use App\Modules\Evaluation\Enums\EvaluationStatus;
use App\Modules\Evaluation\Services\AuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Task 12 — attach a file OR a link as evidence bound to a specific item/indicator
 * of an evaluation. Multiple evidences per node are allowed. Records the uploader.
 * Logs every upload. Files are stored on the `public` disk (existing convention)
 * and tracked through the shared File model.
 */
class UploadEvidence
{
    public function __construct(private readonly AuditTrail $audit)
    {
    }

    /**
     * @param array{item_id?:?int,indicator_id?:?int,type:string,url?:?string,description?:?string,visible_to_subject?:bool} $data
     *
     * @throws ValidationException when the evaluation is locked or input is invalid.
     */
    public function upload(Evaluation $evaluation, array $data, ?UploadedFile $file, int $actorId): EvaluationEvidence
    {
        if ($evaluation->status instanceof EvaluationStatus && $evaluation->status->isLocked()) {
            throw ValidationException::withMessages([
                'evidence' => __('evaluation.evidence.errors.locked'),
            ]);
        }

        $type = $data['type'] ?? 'file';
        if ($type === 'file' && !$file) {
            throw ValidationException::withMessages(['file' => __('evaluation.evidence.errors.file_required')]);
        }
        if ($type === 'link' && trim((string) ($data['url'] ?? '')) === '') {
            throw ValidationException::withMessages(['url' => __('evaluation.evidence.errors.url_required')]);
        }

        return DB::transaction(function () use ($evaluation, $data, $file, $actorId, $type) {
            $fileId       = null;
            $url          = null;
            $originalName = null;
            $mime         = null;
            $size         = null;

            if ($type === 'file') {
                $path   = $file->store('evaluation/evidence/'.date('Y/m'), 'public');
                $fileRow = File::create([
                    'school_id'     => $evaluation->school_id ?? auth()->user()?->school_id,
                    'uploaded_by'   => $actorId,
                    'name'          => $file->getClientOriginalName(),
                    'original_name' => $file->getClientOriginalName(),
                    'path'          => $path,
                    'disk'          => 'public',
                    'mime_type'     => $file->getClientMimeType(),
                    'size'          => $file->getSize(),
                    'type'          => 'resource',
                    'is_public'     => false,
                ]);
                $fileId       = $fileRow->id;
                $originalName = $file->getClientOriginalName();
                $mime         = $file->getClientMimeType();
                $size         = $file->getSize();
            } else {
                $url = trim((string) $data['url']);
            }

            $evidence = EvaluationEvidence::create([
                'evaluation_id'      => $evaluation->id,
                'item_id'            => $data['item_id'] ?? null,
                'indicator_id'       => $data['indicator_id'] ?? null,
                'type'               => $type,
                'file_id'            => $fileId,
                'url'                => $url,
                'original_name'      => $originalName,
                'mime'               => $mime,
                'size'               => $size,
                'description'        => $data['description'] ?? null,
                'visible_to_subject' => (bool) ($data['visible_to_subject'] ?? false),
                'uploaded_by'        => $actorId,
            ]);

            $evaluation->evidence_count = $evaluation->evidences()->count();
            $evaluation->save();

            $this->audit->record(
                'evidence.upload',
                "إرفاق شاهد ({$type}) على تقييم #{$evaluation->id}",
                $evidence,
                null,
                $evidence->toArray()
            );

            return $evidence;
        });
    }

    /**
     * Delete an evidence row. Forbidden after approval, and only the uploader may
     * delete their own evidence unless the actor has the override permission.
     *
     * @throws ValidationException
     */
    public function delete(Evaluation $evaluation, EvaluationEvidence $evidence, int $actorId, bool $canOverride): bool
    {
        if ($evaluation->status?->value === 'approved' && !$canOverride) {
            throw ValidationException::withMessages([
                'evidence' => __('evaluation.evidence.errors.delete_after_approval'),
            ]);
        }
        if ((int) $evidence->uploaded_by !== $actorId && !$canOverride) {
            throw ValidationException::withMessages([
                'evidence' => __('evaluation.evidence.errors.delete_others'),
            ]);
        }

        return (bool) DB::transaction(function () use ($evaluation, $evidence) {
            $this->audit->record(
                'evidence.delete',
                "حذف شاهد #{$evidence->id} من تقييم #{$evaluation->id}",
                $evidence,
                $evidence->toArray(),
                null
            );
            $deleted = $evidence->delete();

            $evaluation->evidence_count = $evaluation->evidences()->count();
            $evaluation->save();

            return $deleted;
        });
    }
}
