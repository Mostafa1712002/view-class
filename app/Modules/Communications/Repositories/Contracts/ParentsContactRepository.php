<?php

namespace App\Modules\Communications\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-model for the "parents as a contact" communications view.
 *
 * Lists parents (role=parent) within the active school scope and decorates
 * each row with the number of recorded interactions (internal mail, WhatsApp,
 * in-app notifications). Backed by the existing parent list query so the
 * multi-tenant null-safe school filter (super-admin = all schools) is reused
 * instead of re-derived.
 */
interface ParentsContactRepository
{
    /**
     * Paginated parents with interaction counts.
     *
     * @param  int|null  $schoolId  active school; null = all schools (super-admin)
     */
    public function paginate(?int $schoolId, ?string $search = null, int $perPage = 25): LengthAwarePaginator;

    /**
     * A single parent scoped to the school, with children eager-loaded.
     */
    public function findScoped(int $id, ?int $schoolId): ?User;

    /**
     * Interaction logs for a single parent (mail / whatsapp / notifications),
     * each returned as a normalised array keyed by channel.
     *
     * @return array{mail: \Illuminate\Support\Collection, whatsapp: \Illuminate\Support\Collection, notifications: \Illuminate\Support\Collection}
     */
    public function interactionLogs(User $parent): array;
}
