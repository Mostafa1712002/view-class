<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketStatusLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'from_status',
        'to_status',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fromStatusLabel(): string
    {
        return $this->from_status ? SupportTicket::statusLabelFor($this->from_status) : '—';
    }

    public function toStatusLabel(): string
    {
        return SupportTicket::statusLabelFor($this->to_status);
    }
}
