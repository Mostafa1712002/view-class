<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\User;
use App\Modules\Gamification\Models\PointReward;
use App\Modules\Gamification\Models\PointRewardPeriod;
use App\Modules\Gamification\Repositories\Contracts\RewardRepository;
use App\Modules\Gamification\Support\ClassStudents;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentRewardRepository implements RewardRepository
{
    public function periods(?int $schoolId): Collection
    {
        return PointRewardPeriod::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('starts_on')->get();
    }

    public function findPeriod(int $id, ?int $schoolId): ?PointRewardPeriod
    {
        return PointRewardPeriod::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->find($id);
    }

    public function createPeriod(array $attributes): PointRewardPeriod
    {
        return PointRewardPeriod::create($attributes);
    }

    public function updatePeriod(PointRewardPeriod $period, array $attributes): PointRewardPeriod
    {
        $period->update($attributes);

        return $period;
    }

    public function deletePeriod(PointRewardPeriod $period): void
    {
        $period->delete();
    }

    public function activePeriods(int $schoolId, ?int $sectionId, CarbonInterface $date): Collection
    {
        $day = $date->toDateString();

        return PointRewardPeriod::query()
            ->where('school_id', $schoolId)
            ->where(fn ($q) => $q->whereNull('section_id')->when($sectionId, fn ($q) => $q->orWhere('section_id', $sectionId)))
            ->whereDate('starts_on', '<=', $day)->whereDate('ends_on', '>=', $day)
            ->orderByDesc('starts_on')->orderByDesc('id')->get();
    }

    public function periodSum(int $userId, PointRewardPeriod $period): int
    {
        return (int) DB::table('point_transactions')
            ->where('user_id', $userId)->where('school_id', $period->school_id)
            ->where('created_at', '>=', $period->starts_on->toDateString().' 00:00:00')
            ->where('created_at', '<', $period->ends_on->copy()->addDay()->toDateString().' 00:00:00')
            ->sum('points');
    }

    public function createReward(int $schoolId, int $userId, int $periodId, int $points): ?PointReward
    {
        try {
            return PointReward::create([
                'school_id' => $schoolId, 'user_id' => $userId, 'period_id' => $periodId,
                'points_at_award' => $points, 'status' => PointReward::EARNED,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    public function rewards(?int $schoolId, ?string $status = null, int $perPage = 25): LengthAwarePaginator
    {
        return PointReward::query()->with(['user', 'period'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate($perPage);
    }

    public function findReward(int $id, ?int $schoolId): ?PointReward
    {
        return PointReward::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->find($id);
    }

    public function deliver(PointReward $reward, int $deliveredBy): PointReward
    {
        $reward->update(['status' => PointReward::DELIVERED, 'delivered_at' => now(), 'delivered_by' => $deliveredBy]);

        return $reward;
    }

    public function honourList(int $schoolId, string $month, string $type, ?int $sectionId = null, int $limit = 10, ?int $classId = null, ?int $subjectId = null): Collection
    {
        $from = $month.'-01 00:00:00';
        $to = date('Y-m-d', strtotime($month.'-01 +1 month')).' 00:00:00';

        $query = DB::table('point_transactions as t')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('t.school_id', $schoolId)->where('t.created_at', '>=', $from)->where('t.created_at', '<', $to)
            ->whereNull('u.deleted_at')
            ->when($sectionId && $type === 'student', fn ($q) => $q->whereIn('u.id', ClassStudents::inSection(User::query()->select('users.id'), $sectionId)))
            ->when($classId && $type === 'student', fn ($q) => $q->whereIn('u.id', ClassStudents::constrain(User::query()->select('users.id'), [$classId])))
            ->when($subjectId && $type === 'teacher', fn ($q) => $q->whereExists(fn ($e) => $e->from('subject_teacher as st')->whereColumn('st.user_id', 'u.id')->where('st.subject_id', $subjectId)));

        match ($type) {
            'vice' => $query->whereExists(fn ($q) => $q->from('job_titles as j')->whereColumn('j.id', 'u.job_title_id')->where('j.slug', 'vice')),
            default => $query->whereExists(fn ($q) => $q->from('role_user as ru')->join('roles as r', 'r.id', '=', 'ru.role_id')
                ->whereColumn('ru.user_id', 'u.id')->where('r.slug', $type)),
        };

        return $query->groupBy('t.user_id', 'u.name')
            ->havingRaw('SUM(t.points) > 0')
            ->selectRaw('t.user_id, u.name, SUM(t.points) as points')
            ->orderByDesc('points')->orderBy('u.name')->limit($limit)->get();
    }
}
