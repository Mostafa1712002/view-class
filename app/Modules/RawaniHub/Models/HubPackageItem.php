<?php

namespace App\Modules\RawaniHub\Models;

use Illuminate\Database\Eloquent\Model;

class HubPackageItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function package()
    {
        return $this->belongsTo(HubPackage::class, 'package_id');
    }

    public function platform()
    {
        return $this->belongsTo(HubPlatform::class, 'platform_id');
    }
}
