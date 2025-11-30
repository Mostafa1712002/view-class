<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'subject',
        'school_id',
        'type',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public const TYPES = [
        'private' => 'خاص',
        'group' => 'مجموعة',
        'announcement' => 'إعلان',
    ];

    // Relationships
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'is_muted'])
            ->withTimestamps();
    }

    public function participantRecords(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeWithUnread($query, User $user)
    {
        return $query->withCount(['messages as unread_count' => function ($q) use ($user) {
            $q->whereDoesntHave('reads', function ($r) use ($user) {
                $r->where('user_id', $user->id);
            })->where('sender_id', '!=', $user->id);
        }]);
    }

    public function scopeNotMuted($query, User $user)
    {
        return $query->whereHas('participantRecords', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('is_muted', false);
        });
    }

    // Helper Methods
    public function addParticipant(User $user): void
    {
        if (!$this->participants()->where('user_id', $user->id)->exists()) {
            $this->participants()->attach($user->id);
        }
    }

    public function removeParticipant(User $user): void
    {
        $this->participants()->detach($user->id);
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function getOtherParticipant(User $currentUser): ?User
    {
        if ($this->type !== 'private') {
            return null;
        }

        return $this->participants()->where('user_id', '!=', $currentUser->id)->first();
    }

    public function getUnreadCount(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->count();
    }

    public function markAsRead(User $user): void
    {
        $participant = $this->participantRecords()->where('user_id', $user->id)->first();
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }

        // Mark all messages as read
        $unreadMessages = $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->reads()->create([
                'user_id' => $user->id,
                'read_at' => now(),
            ]);
        }
    }

    public function toggleMute(User $user): bool
    {
        $participant = $this->participantRecords()->where('user_id', $user->id)->first();
        if ($participant) {
            $participant->update(['is_muted' => !$participant->is_muted]);
            return $participant->fresh()->is_muted;
        }
        return false;
    }

    public function getDisplayName(User $currentUser): string
    {
        if ($this->type === 'private') {
            $other = $this->getOtherParticipant($currentUser);
            return $other ? $other->name : $this->subject;
        }

        return $this->subject;
    }

    // Static factory methods
    public static function startPrivate(User $creator, User $recipient, string $subject = null): self
    {
        // Check if conversation already exists
        $existing = self::where('type', 'private')
            ->whereHas('participants', function ($q) use ($creator) {
                $q->where('user_id', $creator->id);
            })
            ->whereHas('participants', function ($q) use ($recipient) {
                $q->where('user_id', $recipient->id);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = self::create([
            'subject' => $subject ?? 'محادثة خاصة',
            'type' => 'private',
            'created_by' => $creator->id,
            'school_id' => $creator->school_id,
        ]);

        $conversation->participants()->attach([$creator->id, $recipient->id]);

        return $conversation;
    }

    public static function startGroup(User $creator, array $participantIds, string $subject): self
    {
        $conversation = self::create([
            'subject' => $subject,
            'type' => 'group',
            'created_by' => $creator->id,
            'school_id' => $creator->school_id,
        ]);

        $allParticipants = array_unique(array_merge([$creator->id], $participantIds));
        $conversation->participants()->attach($allParticipants);

        return $conversation;
    }
}
