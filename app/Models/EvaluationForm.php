<?php

namespace App\Models;

use App\Modules\Evaluation\Enums\FormStatus;
use App\Modules\Evaluation\Enums\FormType;
use App\Modules\Evaluation\Enums\UsageDomain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'created_by', 'title', 'description', 'internal_notes',
        'type', 'usage_domain', 'status', 'levels_count', 'start_date', 'close_date',
        'is_class_visit_only', 'links_to_job_performance', 'settings', 'job_perf_settings',
        'published_at', 'closed_at', 'archived_at',
    ];

    protected $casts = [
        'type'                     => FormType::class,
        'usage_domain'             => UsageDomain::class,
        'status'                   => FormStatus::class,
        'levels_count'             => 'integer',
        'start_date'               => 'datetime',
        'close_date'               => 'datetime',
        'is_class_visit_only'      => 'boolean',
        'links_to_job_performance' => 'boolean',
        'settings'                 => 'array',
        'job_perf_settings'        => 'array',
        'published_at'             => 'datetime',
        'closed_at'                => 'datetime',
        'archived_at'              => 'datetime',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function levels(): HasMany { return $this->hasMany(EvaluationLevel::class, 'form_id')->orderBy('sort_order'); }
    public function items(): HasMany { return $this->hasMany(EvaluationItem::class, 'form_id')->orderBy('sort_order'); }
    public function indicators(): HasMany { return $this->hasMany(EvaluationIndicator::class, 'form_id'); }
    public function snapshots(): HasMany { return $this->hasMany(EvaluationFormSnapshot::class, 'form_id'); }
    public function targets(): HasMany { return $this->hasMany(EvaluationTarget::class, 'form_id'); }
    public function assignments(): HasMany { return $this->hasMany(EvaluationAssignment::class, 'form_id'); }
    public function evaluations(): HasMany { return $this->hasMany(Evaluation::class, 'form_id'); }

    /** Settings helper with default fallback. */
    public function setting(string $key, mixed $default = false): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function isEditable(): bool
    {
        return $this->status instanceof FormStatus && $this->status->isEditable();
    }
}
