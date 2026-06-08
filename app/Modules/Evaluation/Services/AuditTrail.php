<?php

namespace App\Modules\Evaluation\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Thin wrapper over ActivityLog for the evaluation engine's sensitive operations
 * (سجل العمليات). Namespaces actions under "evaluation." so the audit screen can
 * filter the module's entries.
 */
class AuditTrail
{
    public const PREFIX = 'evaluation.';

    public function record(string $action, string $description, ?Model $model = null, ?array $old = null, ?array $new = null): ActivityLog
    {
        return ActivityLog::log(self::PREFIX.$action, $description, $model, $old, $new);
    }

    public function created(Model $model, string $description): ActivityLog
    {
        return $this->record('create', $description, $model, null, $model->toArray());
    }

    public function updated(Model $model, string $description, array $old): ActivityLog
    {
        return $this->record('update', $description, $model, $old, $model->toArray());
    }

    public function deleted(Model $model, string $description): ActivityLog
    {
        return $this->record('delete', $description, $model, $model->toArray(), null);
    }
}
