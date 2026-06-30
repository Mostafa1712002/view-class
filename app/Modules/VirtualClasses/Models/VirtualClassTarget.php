<?php

namespace App\Modules\VirtualClasses\Models;

use App\Models\VirtualClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-session targeting row (kind: user | role | job_title) — mirrors
 * announcement_targets / school_event_targets. The grade/class narrowing lives
 * on the virtual_classes row itself; this table holds the explicit
 * user/role/job-title picks.
 */
class VirtualClassTarget extends Model
{
    protected $table = 'virtual_class_targets';

    protected $fillable = ['virtual_class_id', 'kind', 'target_id'];

    public function virtualClass(): BelongsTo
    {
        return $this->belongsTo(VirtualClass::class, 'virtual_class_id');
    }
}
