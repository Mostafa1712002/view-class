<?php

namespace App\Modules\Communications\Repositories\Contracts;

use App\Modules\Communications\Models\ParentComplaint;
use App\Modules\Communications\Models\ParentScheduledCall;
use App\Modules\Communications\Models\ParentSchoolVisit;
use Illuminate\Support\Collection;

/**
 * Data access for the parent-CRM layer (complaints / visits / scheduled calls)
 * plus the unified timeline. All reads are school-scoped (null = super-admin
 * see-all); writes denormalize the resolved school_id onto each row.
 */
interface ParentCrmRepository
{
    /** Complaints for a parent within scope, newest first. */
    public function complaints(int $parentId, ?int $schoolId): Collection;

    /** School visits for a parent within scope, newest first. */
    public function visits(int $parentId, ?int $schoolId): Collection;

    /** Scheduled calls for a parent within scope, newest first. */
    public function calls(int $parentId, ?int $schoolId): Collection;

    /**
     * Unified chronological timeline merging the CRM records above with the
     * communication logs (mail / whatsapp / notifications) for this parent.
     *
     * @param array $commLogs ['mail'=>..., 'whatsapp'=>..., 'notifications'=>...]
     * @return array<int,array{kind:string,icon:string,title:string,meta:string,at:?string}>
     */
    public function timeline(int $parentId, ?int $schoolId, array $commLogs): array;

    public function createComplaint(array $data): ParentComplaint;

    public function createVisit(array $data): ParentSchoolVisit;

    public function createCall(array $data): ParentScheduledCall;

    /** Next complaint code, e.g. CMP-000123. */
    public function nextComplaintCode(): string;
}
