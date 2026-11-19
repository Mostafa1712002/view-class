<?php

namespace App\Modules\RawaniHub\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HubProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
