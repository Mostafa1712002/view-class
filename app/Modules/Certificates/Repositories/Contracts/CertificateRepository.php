<?php

namespace App\Modules\Certificates\Repositories\Contracts;

use App\Models\Certificate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CertificateRepository
{
    /**
     * Paginated list of certificates scoped to a school with optional filters.
     *
     * @param array $filters Optional: type, q (title/recipient search)
     */
    public function listForSchool(?int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a certificate by ID (no scope — caller applies scope checks).
     */
    public function find(int $id): ?Certificate;

    /**
     * Create a new certificate.
     */
    public function create(array $data): Certificate;

    /**
     * Update an existing certificate.
     */
    public function update(Certificate $certificate, array $data): Certificate;

    /**
     * Soft-delete a certificate.
     */
    public function delete(Certificate $certificate): void;

    /**
     * Set the certificate status to published.
     */
    public function publish(Certificate $certificate): Certificate;

    /**
     * Get published certificates for a specific recipient (student/parent view).
     */
    public function publishedForRecipient(?int $schoolId, int $userId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get certificates related to a teacher (recipient or issuer).
     */
    public function forTeacher(?int $schoolId, int $userId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get published certificates whose recipient is one of the given user IDs (parent view).
     *
     * @param array<int> $recipientIds
     */
    public function publishedForRecipients(?int $schoolId, array $recipientIds): \Illuminate\Database\Eloquent\Collection;
}
