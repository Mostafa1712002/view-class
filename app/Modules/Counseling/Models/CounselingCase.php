<?php

namespace App\Modules\Counseling\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounselingCase extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['academic', 'behavioral', 'social', 'health', 'psychological', 'other'];

    public const STATUSES = ['open', 'in_progress', 'closed'];

    protected $fillable = ['school_id', 'student_id', 'counselor_id', 'category', 'title', 'summary', 'status', 'opened_at', 'closed_at', 'created_by'];

    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function notes()
    {
        return $this->hasMany(CounselingCaseNote::class, 'case_id');
    }
}
