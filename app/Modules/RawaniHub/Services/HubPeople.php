<?php

namespace App\Modules\RawaniHub\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/** Read-only lookups for the admin screens (grant form and person page). */
class HubPeople
{
    /** @param array<int,string> $nationalIds @return Collection<int,User> */
    public function byNationalIds(array $nationalIds): Collection
    {
        return User::whereIn('national_id', array_filter($nationalIds))->get();
    }

    /** @return Collection<int,User> */
    public function childrenOfNationalId(string $nationalId): Collection
    {
        $parent = User::where('national_id', $nationalId)->first();

        return $parent ? $parent->children()->get() : collect();
    }

    public function find(int $userId): User
    {
        return User::with(['children', 'parents'])->findOrFail($userId);
    }
}
