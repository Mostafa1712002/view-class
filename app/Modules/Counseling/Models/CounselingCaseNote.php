<?php

namespace App\Modules\Counseling\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CounselingCaseNote extends Model
{
    protected $fillable = ['author_id', 'body', 'action_taken'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function counselingCase()
    {
        return $this->belongsTo(CounselingCase::class, 'case_id');
    }
}
