<?php

namespace App\Modules\SmsServices\Repositories\Contracts;

use App\Models\School;
use App\Modules\SmsServices\Models\SchoolSmsSetting;
use App\Modules\SmsServices\Models\SmsSender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SmsSettingsRepository
{
    public function paginateSchoolsWithSettings(int $perPage = 15): LengthAwarePaginator;

    public function findOrCreateForSchool(School $school): SchoolSmsSetting;

    public function saveSettings(SchoolSmsSetting $setting, array $data): SchoolSmsSetting;

    public function toggleActive(SchoolSmsSetting $setting): SchoolSmsSetting;

    public function listSendersForSchool(School $school): Collection;

    public function listApprovedSendersForSchool(School $school): Collection;

    public function createSender(School $school, array $data): SmsSender;

    public function deleteSender(SmsSender $sender): bool;
}
