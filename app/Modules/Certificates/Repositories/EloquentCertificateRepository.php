<?php

namespace App\Modules\Certificates\Repositories;

use App\Models\Certificate;
use App\Modules\Certificates\Repositories\Contracts\CertificateRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentCertificateRepository implements CertificateRepository
{
    public function listForSchool(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Certificate::query()
            ->with(['recipient:id,name', 'issuer:id,name'])
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId));

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', '%' . $q . '%')
                    ->orWhereHas('recipient', fn ($r) => $r->where('name', 'like', '%' . $q . '%'));
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function find(int $id): ?Certificate
    {
        return Certificate::with(['recipient:id,name', 'issuer:id,name'])->find($id);
    }

    public function create(array $data): Certificate
    {
        return Certificate::create($data);
    }

    public function update(Certificate $certificate, array $data): Certificate
    {
        $certificate->update($data);

        return $certificate->fresh(['recipient', 'issuer']);
    }

    public function delete(Certificate $certificate): void
    {
        $certificate->delete();
    }

    public function publish(Certificate $certificate): Certificate
    {
        $certificate->update(['status' => 'published']);

        return $certificate->fresh();
    }

    public function publishedForRecipient(?int $schoolId, int $userId): Collection
    {
        return Certificate::query()
            ->published()
            ->forSchool($schoolId)
            ->where('recipient_user_id', $userId)
            ->with(['issuer:id,name'])
            ->orderByDesc('issue_date')
            ->get();
    }

    public function forTeacher(?int $schoolId, int $userId): Collection
    {
        return Certificate::query()
            ->forSchool($schoolId)
            ->where(function ($w) use ($userId) {
                $w->where('recipient_user_id', $userId)
                    ->orWhere('issued_by', $userId);
            })
            ->with(['recipient:id,name', 'issuer:id,name'])
            ->orderByDesc('issue_date')
            ->get();
    }

    public function publishedForRecipients(?int $schoolId, array $recipientIds): Collection
    {
        if (empty($recipientIds)) {
            return collect();
        }

        return Certificate::query()
            ->published()
            ->forSchool($schoolId)
            ->whereIn('recipient_user_id', $recipientIds)
            ->with(['recipient:id,name', 'issuer:id,name'])
            ->orderByDesc('issue_date')
            ->get();
    }
}
