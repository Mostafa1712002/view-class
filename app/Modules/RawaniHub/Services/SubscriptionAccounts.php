<?php

namespace App\Modules\RawaniHub\Services;

use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use Illuminate\Support\Facades\DB;

/** Resolves the platform accounts a subscription affects (design §3.2): the beneficiary per platform, plus the primary parent on parent-capable platforms. */
class SubscriptionAccounts
{
    public function __construct(
        private PlatformAccountRepository $accounts,
        private PlatformRepository $platforms,
    ) {}

    /**
     * @param array<int, array{platform_id:int, product_code:string}> $items snapshot
     * @return array<int,int> account ids; $create=false returns only rows that already exist
     */
    public function forBeneficiary(int $userId, array $items, bool $create): array
    {
        $parentId = DB::table('parent_student')->where('student_id', $userId)
            ->orderByDesc('is_primary')->orderBy('id')->value('parent_id');

        $ids = [];
        foreach (array_unique(array_column($items, 'platform_id')) as $platformId) {
            $platform = $this->platforms->find((int) $platformId);
            $targets = [['student', $userId]];
            if ($parentId && ($platform?->capabilities['parents'] ?? false)) {
                $targets[] = ['parent', (int) $parentId];
            }
            foreach ($targets as [$role, $uid]) {
                $row = $create
                    ? $this->accounts->firstOrCreateForUser((int) $platformId, $role, $uid)
                    : $this->accounts->findForUser((int) $platformId, $role, $uid);
                if ($row) {
                    $ids[] = $row->id;
                }
            }
        }

        return $ids;
    }
}
