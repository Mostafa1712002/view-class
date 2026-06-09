<?php

namespace App\Modules\Evaluation\Actions;

use App\Models\ClassVisit;
use App\Modules\Evaluation\Enums\VisitStatus;
use App\Modules\Evaluation\Repositories\Contracts\ClassVisitRepository;
use App\Modules\Evaluation\Services\AuditTrail;
use App\Modules\Evaluation\Services\EvaluationNotifier;
use Illuminate\Validation\ValidationException;

/**
 * Task 17 — schedule (or reschedule) a class visit.
 *
 * Rules enforced here (domain concern, not HTTP):
 *  - no duplicate visit for the same teacher + period + date (ClassVisitRepository::existsForSlot);
 *  - a secret visit never notifies the teacher and is stored with status=secret;
 *  - an announced visit with notify_teacher=true fires EvaluationNotifier::visitScheduled.
 */
class ScheduleClassVisit
{
    public function __construct(
        private readonly ClassVisitRepository $visits,
        private readonly EvaluationNotifier $notifier,
        private readonly AuditTrail $audit,
    ) {
    }

    /**
     * @param array<string,mixed> $data validated payload
     *
     * @throws ValidationException when the teacher+period+date slot is already taken.
     */
    public function create(array $data, ?int $schoolId, int $supervisorId): ClassVisit
    {
        $teacherId = (int) $data['teacher_id'];
        $periodId  = isset($data['period_id']) && $data['period_id'] !== '' ? (int) $data['period_id'] : null;
        $visitDate = (string) $data['visit_date'];

        if ($this->visits->existsForSlot($teacherId, $periodId, $visitDate)) {
            throw ValidationException::withMessages([
                'period_id' => __('class_visits.errors.duplicate_slot'),
            ]);
        }

        $secret = ($data['visit_type'] ?? 'announced') === 'secret';

        $visit = $this->visits->create([
            'school_id'      => $schoolId,
            'supervisor_id'  => $supervisorId,
            'teacher_id'     => $teacherId,
            'subject_id'     => $this->intOrNull($data, 'subject_id'),
            'stage_id'       => $this->intOrNull($data, 'stage_id'),
            'class_room_id'  => $this->intOrNull($data, 'class_room_id'),
            'section_id'     => $this->intOrNull($data, 'section_id'),
            'period_id'      => $periodId,
            'form_id'        => $this->intOrNull($data, 'form_id'),
            'visit_type'     => $secret ? 'secret' : 'announced',
            'notify_teacher' => !$secret && (bool) ($data['notify_teacher'] ?? false),
            'pre_notes'      => $data['pre_notes'] ?? null,
            'visit_date'     => $visitDate,
            'visit_time'     => $data['visit_time'] ?? null,
            'status'         => $this->initialStatus($secret, !$secret && (bool) ($data['notify_teacher'] ?? false)),
        ]);

        $this->notifyIfNeeded($visit);

        $this->audit->created($visit, "جدولة زيارة صفية للمعلّم #{$teacherId} بتاريخ {$visitDate}");

        return $visit->refresh();
    }

    /**
     * @param array<string,mixed> $data validated payload
     *
     * @throws ValidationException when the visit is completed or the new slot collides.
     */
    public function update(ClassVisit $visit, array $data, ?int $schoolId, int $supervisorId): ClassVisit
    {
        if ($visit->status === VisitStatus::Completed) {
            throw ValidationException::withMessages([
                'visit' => __('class_visits.errors.completed_locked'),
            ]);
        }

        $teacherId = (int) $data['teacher_id'];
        $periodId  = isset($data['period_id']) && $data['period_id'] !== '' ? (int) $data['period_id'] : null;
        $visitDate = (string) $data['visit_date'];

        if ($this->visits->existsForSlot($teacherId, $periodId, $visitDate, $visit->id)) {
            throw ValidationException::withMessages([
                'period_id' => __('class_visits.errors.duplicate_slot'),
            ]);
        }

        $secret      = ($data['visit_type'] ?? 'announced') === 'secret';
        $notify      = !$secret && (bool) ($data['notify_teacher'] ?? false);
        $wasNotified = $visit->status === VisitStatus::TeacherNotified;
        $old         = $visit->toArray();

        $this->visits->update($visit, [
            'school_id'      => $schoolId,
            'teacher_id'     => $teacherId,
            'subject_id'     => $this->intOrNull($data, 'subject_id'),
            'stage_id'       => $this->intOrNull($data, 'stage_id'),
            'class_room_id'  => $this->intOrNull($data, 'class_room_id'),
            'section_id'     => $this->intOrNull($data, 'section_id'),
            'period_id'      => $periodId,
            'form_id'        => $this->intOrNull($data, 'form_id'),
            'visit_type'     => $secret ? 'secret' : 'announced',
            'notify_teacher' => $notify,
            'pre_notes'      => $data['pre_notes'] ?? null,
            'visit_date'     => $visitDate,
            'visit_time'     => $data['visit_time'] ?? null,
            // Keep a non-pre-execution status sane (scheduled/secret/teacher_notified only).
            'status'         => $this->initialStatus($secret, $notify),
        ]);

        // Notify only on a fresh (re)notification request that wasn't already sent.
        if ($notify && !$wasNotified) {
            $this->notifyIfNeeded($visit->refresh());
        }

        $this->audit->updated($visit->refresh(), "تعديل زيارة صفية #{$visit->id}", $old);

        return $visit->refresh();
    }

    private function notifyIfNeeded(ClassVisit $visit): void
    {
        // The notifier already guards: notify_teacher + not secret.
        $this->notifier->visitScheduled($visit);
    }

    private function initialStatus(bool $secret, bool $notify): string
    {
        if ($secret) {
            return VisitStatus::Secret->value;
        }

        return $notify ? VisitStatus::TeacherNotified->value : VisitStatus::Scheduled->value;
    }

    private function intOrNull(array $data, string $key): ?int
    {
        return isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null ? (int) $data[$key] : null;
    }
}
