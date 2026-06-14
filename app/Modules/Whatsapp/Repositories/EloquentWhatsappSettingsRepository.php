<?php

namespace App\Modules\Whatsapp\Repositories;

use App\Models\School;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Models\WhatsappLog;
use App\Modules\Whatsapp\Repositories\Contracts\WhatsappSettingsRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentWhatsappSettingsRepository implements WhatsappSettingsRepository
{
    public function findOrCreateForSchool(School $school): SchoolWhatsappSetting
    {
        return SchoolWhatsappSetting::firstOrCreate(
            ['school_id' => $school->id],
            [
                'provider'   => 'log',
                'is_enabled' => false,
            ]
        );
    }

    public function saveSettings(SchoolWhatsappSetting $setting, array $data): SchoolWhatsappSetting
    {
        $setting->update(array_filter($data, fn ($v) => $v !== null));

        return $setting->refresh();
    }

    public function getLogsForSchool(School $school, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = WhatsappLog::where('school_id', $school->id)
            ->with(['student', 'parent', 'attendance'])
            ->orderByDesc('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['student_name'])) {
            $query->whereHas('student', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['student_name'] . '%');
            });
        }

        return $query->paginate($perPage);
    }

    public function findLogForAbsence(int $attendanceId, int $parentId, string $type): ?WhatsappLog
    {
        return WhatsappLog::where('attendance_id', $attendanceId)
            ->where('parent_id', $parentId)
            ->where('type', $type)
            ->whereIn('status', ['sent', 'pending'])
            ->first();
    }
}
