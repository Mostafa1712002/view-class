<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachment_path',
        'attachment_name',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    // Relationships
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    // Scopes
    public function scopeUnreadBy($query, User $user)
    {
        return $query->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
    }

    // Helper Methods
    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function markAsReadBy(User $user): void
    {
        if (!$this->isReadBy($user)) {
            $this->reads()->create([
                'user_id' => $user->id,
                'read_at' => now(),
            ]);
        }
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function isSentBy(User $user): bool
    {
        return $this->sender_id === $user->id;
    }

    public function getReadCount(): int
    {
        return $this->reads()->count();
    }

    public function getAttachmentExtension(): ?string
    {
        if (!$this->hasAttachment()) {
            return null;
        }

        return pathinfo($this->attachment_path, PATHINFO_EXTENSION);
    }

    public function isImage(): bool
    {
        $extension = strtolower($this->getAttachmentExtension() ?? '');
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function isPdf(): bool
    {
        return strtolower($this->getAttachmentExtension() ?? '') === 'pdf';
    }

    // Boot method for updating conversation
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            $message->conversation->update(['last_message_at' => now()]);
        });
    }
}
