<?php

namespace App\Modules\SmsServices\Repositories;

use App\Models\School;
use App\Modules\SmsServices\Models\SchoolSmsSetting;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Repositories\Contracts\SmsSettingsRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentSmsSettingsRepository implements SmsSettingsRepository
{
    public function paginateSchoolsWithSettings(int $perPage = 15): LengthAwarePaginator
    {
        return School::query()
            ->with(['smsSetting.defaultSender'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findOrCreateForSchool(School $school): SchoolSmsSetting
    {
        return SchoolSmsSetting::firstOrCreate(
            ['school_id' => $school->id],
            ['is_active' => false, 'sms_used' => 0, 'sms_total' => 0, 'provider' => 'generic']
        );
    }

    public function saveSettings(SchoolSmsSetting $setting, array $data): SchoolSmsSetting
    {
        $setting->update(array_filter($data, fn ($v) => $v !== null));
        return $setting->refresh();
    }

    public function toggleActive(SchoolSmsSetting $setting): SchoolSmsSetting
    {
        $setting->update(['is_active' => !$setting->is_active]);
        return $setting->refresh();
    }

    public function listSendersForSchool(School $school): Collection
    {
        return SmsSender::where('school_id', $school->id)
            ->orderByDesc('id')
            ->get();
    }

    public function listApprovedSendersForSchool(School $school): Collection
    {
        return SmsSender::where('school_id', $school->id)
            ->where('status', 'approved')
            ->orderBy('name_ar')
            ->get();
    }

    public function createSender(School $school, array $data): SmsSender
    {
        return SmsSender::create([
            'school_id' => $school->id,
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'status' => 'pending',
        ]);
    }

    public function deleteSender(SmsSender $sender): bool
    {
        return (bool) $sender->delete();
    }
}
