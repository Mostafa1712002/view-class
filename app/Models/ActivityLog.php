<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getModelAttribute()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $user = Auth::user();

        return static::create([
            'user_id' => $user?->id,
            'school_id' => $user?->school_id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logCreate(Model $model, string $description): self
    {
        return static::log('create', $description, $model, null, $model->toArray());
    }

    public static function logUpdate(Model $model, string $description, array $oldValues): self
    {
        return static::log('update', $description, $model, $oldValues, $model->toArray());
    }

    public static function logDelete(Model $model, string $description): self
    {
        return static::log('delete', $description, $model, $model->toArray(), null);
    }

    public static function logLogin(User $user): self
    {
        return static::log('login', "تسجيل دخول: {$user->name}", $user);
    }

    public static function logLogout(User $user): self
    {
        return static::log('logout', "تسجيل خروج: {$user->name}", $user);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'view' => 'عرض',
            'export' => 'تصدير',
            'import' => 'استيراد',
            default => $this->action,
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'create' => 'success',
            'update' => 'warning',
            'delete' => 'danger',
            'login' => 'info',
            'logout' => 'secondary',
            default => 'primary',
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'create' => 'la-plus',
            'update' => 'la-edit',
            'delete' => 'la-trash',
            'login' => 'la-sign-in-alt',
            'logout' => 'la-sign-out-alt',
            'view' => 'la-eye',
            'export' => 'la-download',
            'import' => 'la-upload',
            default => 'la-circle',
        };
    }
}
