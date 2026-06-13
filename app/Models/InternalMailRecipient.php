<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMailRecipient extends Model
{
    protected $fillable = [
        'mail_id',
        'recipient_id',
        'is_read',
        'read_at',
        'starred',
        'is_task',
        'archived',
        'trashed',
    ];

    protected function casts(): array
    {
        return [
            'is_read'  => 'boolean',
            'starred'  => 'boolean',
            'is_task'  => 'boolean',
            'archived' => 'boolean',
            'trashed'  => 'boolean',
            'read_at'  => 'datetime',
        ];
    }

    public function mail(): BelongsTo
    {
        return $this->belongsTo(InternalMail::class, 'mail_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
