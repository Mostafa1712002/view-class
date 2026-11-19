<?php

namespace App\Modules\SpecialPrograms\Models;

use Illuminate\Database\Eloquent\Model;

class SpAttemptItem extends Model
{
    public $timestamps = false;

    protected $table = 'sp_attempt_items';

    protected $guarded = [];

    protected $casts = ['options' => 'array', 'is_correct' => 'boolean', 'answered_at' => 'datetime'];

    public function attempt()
    {
        return $this->belongsTo(SpAttempt::class, 'attempt_id');
    }
}
