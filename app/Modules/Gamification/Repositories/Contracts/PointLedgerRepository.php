<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\User;
use App\Modules\Gamification\Models\PointTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PointLedgerRepository
{
    /** Rows of one key ordered by revision, locked FOR UPDATE (call inside a transaction). */
    public function keyRowsForUpdate(int $userId, string $ruleKey, string $sourceType, int $sourceId): Collection;

    public function insert(array $row): PointTransaction;

    /** Sum of ALL the person's rows (follows a student who changes school). */
    public function balance(int $userId): int;

    /**
     * Staff list: users with the points they earned in the school.
     * filters: type (student|teacher|vice), section_id, class_id, name, month (Y-m, adds month_points), per_page.
     */
    public function balances(?int $schoolId, array $filters = []): LengthAwarePaginator;

    public function history(int $userId, int $perPage = 25): LengthAwarePaginator;

    /** @return Collection<int, object{user_id:int, rule_key:string, net:int}> keys of a source whose net is not 0 */
    public function sourceKeys(string $sourceType, int $sourceId): Collection;

    public function findUser(int $userId, bool $withTrashed = false): ?User;
}
