<?php

namespace App\Modules\RawaniHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HubPackage extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2', 'is_purchasable' => 'boolean', 'is_active' => 'boolean'];

    public function items()
    {
        return $this->hasMany(HubPackageItem::class, 'package_id');
    }
}
