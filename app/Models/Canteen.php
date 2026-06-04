<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Canteen extends Model
{
    use SoftDeletes;

    protected $table = 'canteens';

    protected $fillable = [
        'school_id',
        'name_ar',
        'name_en',
        'target_grades',
        'manager_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'target_grades' => 'array',
        'is_active' => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CanteenCategory::class, 'canteen_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(CanteenProduct::class, 'canteen_id');
    }

    /**
     * Reasons this canteen can't be activated yet (card #116): a canteen needs a
     * manager + at least one active category + at least one active product.
     * Returns an array of human-readable lang keys; empty means ready.
     */
    public function activationBlockers(): array
    {
        $blockers = [];

        if (! $this->manager_id) {
            $blockers[] = 'canteen.activation.need_manager';
        }

        $hasCategory = \Illuminate\Support\Facades\Schema::hasTable('canteen_categories')
            && \Illuminate\Support\Facades\DB::table('canteen_categories')
                ->where('canteen_id', $this->id)->where('is_active', true)->whereNull('deleted_at')->exists();
        if (! $hasCategory) {
            $blockers[] = 'canteen.activation.need_category';
        }

        $hasProduct = \Illuminate\Support\Facades\Schema::hasTable('canteen_products')
            && \Illuminate\Support\Facades\DB::table('canteen_products')
                ->where('canteen_id', $this->id)->where('is_active', true)->whereNull('deleted_at')->exists();
        if (! $hasProduct) {
            $blockers[] = 'canteen.activation.need_product';
        }

        return $blockers;
    }
}
