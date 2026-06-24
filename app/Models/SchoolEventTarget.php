<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolEventTarget extends Model
{
    protected $table = 'school_event_targets';

    protected $fillable = ['school_event_id', 'kind', 'target_id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SchoolEvent::class, 'school_event_id');
    }
}
