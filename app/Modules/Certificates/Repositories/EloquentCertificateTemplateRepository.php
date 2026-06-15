<?php

namespace App\Modules\Certificates\Repositories;

use App\Models\CertificateTemplate;
use App\Modules\Certificates\Repositories\Contracts\CertificateTemplateRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentCertificateTemplateRepository implements CertificateTemplateRepository
{
    public function listForSchool(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = CertificateTemplate::query()
            ->with('creator:id,name')
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId));

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%' . $filters['q'] . '%');
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function allForSchool(?int $schoolId): Collection
    {
        return CertificateTemplate::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'orientation', 'background_path', 'text_color', 'name_color', 'body']);
    }

    public function find(int $id): ?CertificateTemplate
    {
        return CertificateTemplate::with('creator:id,name')->find($id);
    }

    public function create(array $data): CertificateTemplate
    {
        return CertificateTemplate::create($data);
    }

    public function update(CertificateTemplate $template, array $data): CertificateTemplate
    {
        $template->update($data);

        return $template->fresh('creator');
    }

    public function delete(CertificateTemplate $template): void
    {
        $template->delete();
    }
}
