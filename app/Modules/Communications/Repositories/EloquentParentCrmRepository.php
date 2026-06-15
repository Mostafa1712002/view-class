<?php

namespace App\Modules\Communications\Repositories;

use App\Modules\Communications\Models\ParentComplaint;
use App\Modules\Communications\Models\ParentScheduledCall;
use App\Modules\Communications\Models\ParentSchoolVisit;
use App\Modules\Communications\Repositories\Contracts\ParentCrmRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentParentCrmRepository implements ParentCrmRepository
{
    public function complaints(int $parentId, ?int $schoolId): Collection
    {
        return ParentComplaint::query()
            ->where('parent_id', $parentId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student:id,name,username', 'assignee:id,name,username'])
            ->orderByDesc('complaint_date')
            ->orderByDesc('id')
            ->get();
    }

    public function visits(int $parentId, ?int $schoolId): Collection
    {
        return ParentSchoolVisit::query()
            ->where('parent_id', $parentId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student:id,name,username', 'metStaff:id,name,username'])
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->get();
    }

    public function calls(int $parentId, ?int $schoolId): Collection
    {
        return ParentScheduledCall::query()
            ->where('parent_id', $parentId)
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['assignee:id,name,username'])
            ->orderByDesc('call_date')
            ->orderByDesc('id')
            ->get();
    }

    public function timeline(int $parentId, ?int $schoolId, array $commLogs): array
    {
        $events = [];

        foreach ($this->complaints($parentId, $schoolId) as $c) {
            $events[] = [
                'kind' => 'complaint',
                'icon' => 'exclamation-triangle',
                'title' => 'شكوى — '.($c->purpose ?: $c->code),
                'meta' => 'الحالة: '.self::complaintStatusLabel($c->status),
                'at' => optional($c->created_at)->toDateTimeString() ?: ($c->complaint_date?->toDateString()),
            ];
        }

        foreach ($this->visits($parentId, $schoolId) as $v) {
            $events[] = [
                'kind' => 'visit',
                'icon' => 'geo-alt',
                'title' => 'زيارة مدرسة — '.($v->reason ?: '—'),
                'meta' => trim(($v->visit_time ? (string) $v->visit_time.' · ' : '').self::visitStatusLabel($v->status)),
                'at' => optional($v->created_at)->toDateTimeString() ?: ($v->visit_date?->toDateString()),
            ];
        }

        foreach ($this->calls($parentId, $schoolId) as $call) {
            $events[] = [
                'kind' => 'call',
                'icon' => 'telephone',
                'title' => 'اتصال مجدول — '.($call->purpose ?: '—'),
                'meta' => ($call->answered ? 'تم الرد' : 'لم يتم الرد').' · '.self::callStatusLabel($call->status),
                'at' => optional($call->created_at)->toDateTimeString() ?: ($call->call_date?->toDateString()),
            ];
        }

        foreach (($commLogs['mail'] ?? collect()) as $m) {
            $events[] = [
                'kind' => 'mail',
                'icon' => 'envelope',
                'title' => 'بريد المنصة — '.($m->subject ?: '—'),
                'meta' => 'من: '.($m->sender_name ?: '—'),
                'at' => $m->created_at,
            ];
        }

        foreach (($commLogs['whatsapp'] ?? collect()) as $w) {
            $events[] = [
                'kind' => 'whatsapp',
                'icon' => 'whatsapp',
                'title' => 'واتساب — '.\Illuminate\Support\Str::limit($w->message_text ?: '—', 60),
                'meta' => 'الحالة: '.($w->status ?: '—'),
                'at' => $w->sent_at ?: $w->created_at,
            ];
        }

        foreach (($commLogs['notifications'] ?? collect()) as $n) {
            $events[] = [
                'kind' => 'notification',
                'icon' => 'bell',
                'title' => 'إشعار — '.($n->title ?: '—'),
                'meta' => \Illuminate\Support\Str::limit($n->body ?: '', 60),
                'at' => $n->created_at,
            ];
        }

        usort($events, static function ($a, $b) {
            $ta = $a['at'] ? Carbon::parse($a['at'])->timestamp : 0;
            $tb = $b['at'] ? Carbon::parse($b['at'])->timestamp : 0;
            return $tb <=> $ta;
        });

        return $events;
    }

    public function createComplaint(array $data): ParentComplaint
    {
        return ParentComplaint::create($data);
    }

    public function createVisit(array $data): ParentSchoolVisit
    {
        return ParentSchoolVisit::create($data);
    }

    public function createCall(array $data): ParentScheduledCall
    {
        return ParentScheduledCall::create($data);
    }

    public function nextComplaintCode(): string
    {
        $last = ParentComplaint::withTrashed()->max('id');

        return 'CMP-'.str_pad((string) (((int) $last) + 1), 6, '0', STR_PAD_LEFT);
    }

    private static function complaintStatusLabel(?string $s): string
    {
        return [
            'new' => 'جديدة', 'in_progress' => 'قيد المعالجة',
            'awaiting_parent' => 'بانتظار رد ولي الأمر', 'resolved' => 'تم الحل', 'closed' => 'مغلقة',
        ][$s] ?? ($s ?: '—');
    }

    private static function visitStatusLabel(?string $s): string
    {
        return ['open' => 'مفتوحة', 'done' => 'منتهية', 'followup' => 'تحتاج متابعة'][$s] ?? ($s ?: '—');
    }

    private static function callStatusLabel(?string $s): string
    {
        return ['scheduled' => 'مجدول', 'done' => 'تم', 'missed' => 'فائت'][$s] ?? ($s ?: '—');
    }
}
