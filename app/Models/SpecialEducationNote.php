<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialEducationNote extends Model
{
    protected $table = 'special_education_notes';

    protected $fillable = [
        'se_student_id',
        'school_id',
        'body',
        'note_date',
        'created_by',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function seStudent(): BelongsTo
    {
        return $this->belongsTo(SpecialEducationStudent::class, 'se_student_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
