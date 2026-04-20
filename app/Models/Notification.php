<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'icon',
        'color',
        'action_url',
        'action_text',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public const TYPES = [
        'grade_published' => 'نشر درجة',
        'attendance_alert' => 'تنبيه حضور',
        'exam_scheduled' => 'اختبار مجدول',
        'exam_result' => 'نتيجة اختبار',
        'message_received' => 'رسالة جديدة',
        'weekly_plan' => 'خطة أسبوعية',
        'announcement' => 'إعلان',
        'system' => 'نظام',
    ];

    public const COLORS = [
        'primary' => 'أزرق',
        'success' => 'أخضر',
        'warning' => 'برتقالي',
        'danger' => 'أحمر',
        'info' => 'سماوي',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getIcon(): string
    {
        return $this->icon ?? match($this->type) {
            'grade_published' => 'bi-award',
            'attendance_alert' => 'bi-exclamation-triangle',
            'exam_scheduled' => 'bi-calendar-event',
            'exam_result' => 'bi-clipboard-check',
            'message_received' => 'bi-envelope',
            'weekly_plan' => 'bi-journal-text',
            'announcement' => 'bi-megaphone',
            default => 'bi-bell',
        };
    }

    // Static factory methods for creating notifications
    public static function sendGradePublished(User $student, string $subjectName, float $grade): self
    {
        return self::create([
            'user_id' => $student->id,
            'type' => 'grade_published',
            'title' => 'تم نشر درجة جديدة',
            'body' => "تم نشر درجتك في مادة {$subjectName}: {$grade}",
            'color' => $grade >= 50 ? 'success' : 'warning',
            'action_url' => route('dashboard'),
            'action_text' => 'عرض الدرجات',
        ]);
    }

    public static function sendAttendanceAlert(User $parent, User $student, string $status): self
    {
        $statusLabels = ['absent' => 'غائب', 'late' => 'متأخر'];
        $statusLabel = $statusLabels[$status] ?? $status;
        return self::create([
            'user_id' => $parent->id,
            'type' => 'attendance_alert',
            'title' => 'تنبيه حضور',
            'body' => "الطالب {$student->name} {$statusLabel} اليوم",
            'color' => 'warning',
        ]);
    }

    public static function sendExamScheduled(User $student, string $subjectName, string $date): self
    {
        return self::create([
            'user_id' => $student->id,
            'type' => 'exam_scheduled',
            'title' => 'اختبار قادم',
            'body' => "تم جدولة اختبار {$subjectName} بتاريخ {$date}",
            'color' => 'info',
        ]);
    }

    public static function sendMessageReceived(User $user, User $sender, Conversation $conversation): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'message_received',
            'title' => 'رسالة جديدة',
            'body' => "لديك رسالة جديدة من {$sender->name}",
            'color' => 'primary',
            'action_url' => route('messages.show', $conversation),
            'action_text' => 'عرض الرسالة',
            'data' => ['conversation_id' => $conversation->id],
        ]);
    }

    public static function sendAnnouncement(User $user, string $title, string $body): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => 'announcement',
            'title' => $title,
            'body' => $body,
            'color' => 'primary',
        ]);
    }
}
