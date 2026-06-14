<?php

namespace App\Modules\Whatsapp\Repositories\Contracts;

use App\Models\School;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Models\WhatsappLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WhatsappSettingsRepository
{
    public function findOrCreateForSchool(School $school): SchoolWhatsappSetting;

    public function saveSettings(SchoolWhatsappSetting $setting, array $data): SchoolWhatsappSetting;

    public function getLogsForSchool(School $school, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findLogForAbsence(int $attendanceId, int $parentId, string $type): ?WhatsappLog;
}
