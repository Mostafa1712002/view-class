<?php

namespace App\Modules\Certificates\Repositories\Contracts;

use App\Models\CertificateTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CertificateTemplateRepository
{
    /**
     * Paginated list of templates scoped to a school with optional filters.
     *
     * @param array $filters Optional: type, q (name search)
     */
    public function listForSchool(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Flat collection of templates for a school (used to populate select boxes).
     */
    public function allForSchool(?int $schoolId): Collection;

    public function find(int $id): ?CertificateTemplate;

    public function create(array $data): CertificateTemplate;

    public function update(CertificateTemplate $template, array $data): CertificateTemplate;

    public function delete(CertificateTemplate $template): void;
}
