<?php

namespace App\Modules\SpecialEducation\Repositories\Contracts;

use App\Models\SpecialEducationNote;
use App\Models\SpecialEducationPlan;
use App\Models\SpecialEducationStudent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SpecialEducationRepository
{
    /**
     * Paginated list of SE students for a school, optionally filtered.
     * Supported filter keys: category, status, search (name).
     */
    public function studentsForSchool(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Find an SE student by id (no school scope — caller must gate).
     */
    public function findStudent(int $id): SpecialEducationStudent;

    /**
     * Create a new SE student record.
     */
    public function createStudent(array $data): SpecialEducationStudent;

    /**
     * Update an existing SE student record.
     */
    public function updateStudent(int $id, array $data): SpecialEducationStudent;

    /**
     * Soft-delete an SE student record.
     */
    public function deleteStudent(int $id): bool;

    /**
     * All plans for a given SE student, latest first.
     */
    public function plansFor(int $seStudentId): Collection;

    /**
     * Create a new plan for an SE student.
     */
    public function addPlan(array $data): SpecialEducationPlan;

    /**
     * Delete a plan by id.
     */
    public function deletePlan(int $seStudentId, int $id): bool;

    /**
     * All notes for a given SE student, latest first.
     */
    public function notesFor(int $seStudentId): Collection;

    /**
     * Create a new note for an SE student.
     */
    public function addNote(array $data): SpecialEducationNote;

    /**
     * Delete a note by id.
     */
    public function deleteNote(int $seStudentId, int $id): bool;
}
