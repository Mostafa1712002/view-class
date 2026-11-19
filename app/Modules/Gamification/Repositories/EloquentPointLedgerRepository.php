<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\User;
use App\Modules\Gamification\Models\PointTransaction;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentPointLedgerRepository implements PointLedgerRepository
{
    public function keyRowsForUpdate(int $userId, string $ruleKey, string $sourceType, int $sourceId): Collection
    {
        return PointTransaction::query()
            ->where('user_id', $userId)->where('rule_key', $ruleKey)
            ->where('source_type', $sourceType)->where('source_id', $sourceId)
            ->orderBy('revision')->lockForUpdate()->get();
    }

    public function insert(array $row): PointTransaction
    {
        return PointTransaction::create($row);
    }

    public function balance(int $userId): int
    {
        return (int) PointTransaction::where('user_id', $userId)->sum('points');
    }

    public function balances(?int $schoolId, array $filters = []): LengthAwarePaginator
    {
        $earned = fn (?string $month = null) => DB::table('point_transactions')
            ->selectRaw('COALESCE(SUM(points), 0)')
            ->whereColumn('point_transactions.user_id', 'users.id')
            ->when($schoolId, fn ($q) => $q->where('point_transactions.school_id', $schoolId))
            ->when($month, fn ($q) => $q->where('point_transactions.created_at', '>=', $month.'-01 00:00:00')
                ->where('point_transactions.created_at', '<', date('Y-m-d', strtotime($month.'-01 +1 month')).' 00:00:00'));

        $query = User::query()
            ->when($schoolId, fn (Builder $q) => $q->where('users.school_id', $schoolId))
            ->when($filters['section_id'] ?? null, fn (Builder $q, $v) => $q->where('users.section_id', $v))
            ->when($filters['class_id'] ?? null, fn (Builder $q, $v) => $q->where('users.class_room_id', $v))
            ->when($filters['name'] ?? null, fn (Builder $q, $v) => $q->where('users.name', 'like', '%'.$v.'%'));

        match ($filters['type'] ?? null) {
            'vice' => $query->whereHas('jobTitle', fn ($q) => $q->where('slug', 'vice')),
            'teacher' => $query->whereHas('roles', fn ($q) => $q->where('slug', 'teacher')),
            'student' => $query->whereHas('roles', fn ($q) => $q->where('slug', 'student')),
            default => null,
        };

        $query->select('users.*')->selectSub($earned(), 'balance');
        if (! empty($filters['month'])) {
            $query->selectSub($earned($filters['month']), 'month_points');
        }

        return $query->orderByDesc('balance')->orderBy('users.name')->paginate($filters['per_page'] ?? 25);
    }

    public function history(int $userId, int $perPage = 25): LengthAwarePaginator
    {
        return PointTransaction::where('user_id', $userId)->orderByDesc('id')->paginate($perPage);
    }

    public function sourceKeys(string $sourceType, int $sourceId): Collection
    {
        return PointTransaction::query()
            ->where('source_type', $sourceType)->where('source_id', $sourceId)
            ->groupBy('user_id', 'rule_key')
            ->havingRaw('SUM(points) <> 0')
            ->selectRaw('user_id, rule_key, SUM(points) as net')
            ->get();
    }

    public function findUser(int $userId, bool $withTrashed = false): ?User
    {
        return ($withTrashed ? User::withTrashed() : User::query())->with('jobTitle')->find($userId);
    }
}
