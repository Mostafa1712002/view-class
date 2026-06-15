<?php

namespace App\Modules\Announcements\DTOs;

final class AnnouncementDto
{
    public function __construct(
        public readonly int $schoolId,
        public readonly ?int $createdBy,
        public readonly string $title,
        public readonly ?string $body,
        public readonly string $type,
        public readonly string $targetType,
        public readonly array $gradeLevels,
        public readonly array $classIds,
        public readonly array $subjectIds,
        public readonly array $userTargetIds,
        public readonly array $roleTargetIds,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
        public readonly bool $showOnLogin,
        public readonly bool $requireReadAck,
        public readonly bool $notifyInternal,
        public readonly bool $notifySms,
        public readonly bool $notifyWhatsapp,
        public readonly string $status, // draft | published
    ) {}

    /**
     * @param array $v validated request data
     */
    public static function fromArray(array $v, int $schoolId, ?int $createdBy, string $status): self
    {
        return new self(
            schoolId:       $schoolId,
            createdBy:      $createdBy,
            title:          (string) $v['title'],
            body:           $v['body'] ?? null,
            type:           $v['type'] ?? 'normal',
            targetType:     $v['target_type'] ?? 'all',
            gradeLevels:    array_map('intval', $v['grade_levels'] ?? []),
            classIds:       array_map('intval', $v['class_ids'] ?? []),
            subjectIds:     array_map('intval', $v['subject_ids'] ?? []),
            userTargetIds:  array_map('intval', $v['user_target_ids'] ?? []),
            roleTargetIds:  array_map('intval', $v['role_target_ids'] ?? []),
            startsAt:       $v['starts_at'] ?? null,
            endsAt:         $v['ends_at'] ?? null,
            showOnLogin:    (bool) ($v['show_on_login'] ?? false),
            requireReadAck: (bool) ($v['require_read_ack'] ?? false),
            notifyInternal: (bool) ($v['notify_internal'] ?? false),
            notifySms:      (bool) ($v['notify_sms'] ?? false),
            notifyWhatsapp: (bool) ($v['notify_whatsapp'] ?? false),
            status:         $status,
        );
    }

    /** Columns for Announcement::create / ->update (excludes target pivots). */
    public function toModelArray(): array
    {
        return [
            'school_id'        => $this->schoolId,
            'created_by'       => $this->createdBy,
            'title'            => $this->title,
            'body'             => $this->body,
            'type'             => $this->type,
            'target_type'      => $this->targetType,
            'grade_levels'     => $this->gradeLevels ?: null,
            'class_ids'        => $this->classIds ?: null,
            'subject_ids'      => $this->subjectIds ?: null,
            'status'           => $this->status,
            'starts_at'        => $this->startsAt,
            'ends_at'          => $this->endsAt,
            'show_on_login'    => $this->showOnLogin,
            'require_read_ack' => $this->requireReadAck,
            'notify_internal'  => $this->notifyInternal,
            'notify_sms'       => $this->notifySms,
            'notify_whatsapp'  => $this->notifyWhatsapp,
        ];
    }
}
