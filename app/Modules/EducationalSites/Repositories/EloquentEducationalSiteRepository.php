<?php

namespace App\Modules\EducationalSites\Repositories;

use App\Modules\EducationalSites\Models\EducationalSite;
use App\Modules\EducationalSites\Repositories\Contracts\EducationalSiteRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentEducationalSiteRepository implements EducationalSiteRepository
{
    public function listForManagement(?int $scopeSchoolId, bool $includeGlobals = false, array $filters = []): Collection
    {
        return EducationalSite::query()
            ->when($scopeSchoolId !== null, fn (Builder $q) => $this->applyScope($q, $scopeSchoolId, $includeGlobals))
            ->when(! empty($filters['name']), fn (Builder $q) => $q->where(function (Builder $w) use ($filters) {
                $w->where('name_ar', 'like', '%'.$filters['name'].'%')
                    ->orWhere('name_en', 'like', '%'.$filters['name'].'%');
            }))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn (Builder $q) => $q->where('is_active', (int) $filters['is_active']))
            ->when(! empty($filters['category']), fn (Builder $q) => $q->where('category', $filters['category']))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function listVisible(?int $viewerSchoolId): Collection
    {
        return EducationalSite::query()
            ->where('is_active', true)
            // Globals are always visible; school sites only to that school's members.
            ->where(function (Builder $w) use ($viewerSchoolId) {
                $w->whereNull('school_id');
                if ($viewerSchoolId !== null) {
                    $w->orWhere('school_id', $viewerSchoolId);
                }
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function findManageable(int $id, ?int $scopeSchoolId, bool $includeGlobals = false): ?EducationalSite
    {
        return EducationalSite::query()
            ->whereKey($id)
            ->when($scopeSchoolId !== null, fn (Builder $q) => $this->applyScope($q, $scopeSchoolId, $includeGlobals))
            ->first();
    }

    public function create(array $attributes): EducationalSite
    {
        return EducationalSite::create($attributes);
    }

    public function update(EducationalSite $site, array $attributes): EducationalSite
    {
        $site->update($attributes);

        return $site->refresh();
    }

    public function delete(EducationalSite $site): void
    {
        $site->delete();
    }

    public function reorder(array $orderById, ?int $scopeSchoolId, bool $includeGlobals = false): void
    {
        foreach ($orderById as $id => $order) {
            $site = $this->findManageable((int) $id, $scopeSchoolId, $includeGlobals);
            if ($site !== null) {
                $site->update(['sort_order' => max(0, (int) $order)]);
            }
        }
    }

    /**
     * Restrict a query to the actor's school. Globals (school_id IS NULL) are
     * only included when $includeGlobals is true (super-admin) — a scoped
     * school-admin must never manage a platform-wide site.
     */
    private function applyScope(Builder $query, int $scopeSchoolId, bool $includeGlobals): Builder
    {
        return $query->where(function (Builder $w) use ($scopeSchoolId, $includeGlobals) {
            $w->where('school_id', $scopeSchoolId);
            if ($includeGlobals) {
                $w->orWhereNull('school_id');
            }
        });
    }
}
