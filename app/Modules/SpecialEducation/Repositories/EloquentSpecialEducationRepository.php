<?php

namespace App\Modules\SpecialEducation\Repositories;

use App\Models\SpecialEducationNote;
use App\Models\SpecialEducationPlan;
use App\Models\SpecialEducationStudent;
use App\Modules\SpecialEducation\Repositories\Contracts\SpecialEducationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentSpecialEducationRepository implements SpecialEducationRepository
{
    // ── Students ─────────────────────────────────────────────────────────────

    public function studentsForSchool(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SpecialEducationStudent::query()
            ->where('school_id', $schoolId)
            ->with(['student:id,name,name_ar', 'specialist:id,name,name_ar']);

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('name_ar', 'like', '%' . $search . '%');
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findStudent(int $id): SpecialEducationStudent
    {
        return SpecialEducationStudent::with([
            'student:id,name,name_ar',
            'specialist:id,name,name_ar',
        ])->findOrFail($id);
    }

    public function createStudent(array $data): SpecialEducationStudent
    {
        return SpecialEducationStudent::create($data);
    }

    public function updateStudent(int $id, array $data): SpecialEducationStudent
    {
        $student = SpecialEducationStudent::findOrFail($id);
        $student->update($data);
        return $student->fresh();
    }

    public function deleteStudent(int $id): bool
    {
        $student = SpecialEducationStudent::findOrFail($id);
        return (bool) $student->delete();
    }

    // ── Plans ────────────────────────────────────────────────────────────────

    public function plansFor(int $seStudentId): Collection
    {
        return SpecialEducationPlan::query()
            ->where('se_student_id', $seStudentId)
            ->latest('id')
            ->get();
    }

    public function addPlan(array $data): SpecialEducationPlan
    {
        return SpecialEducationPlan::create($data);
    }

    public function deletePlan(int $seStudentId, int $id): bool
    {
        $plan = SpecialEducationPlan::where('id', $id)
            ->where('se_student_id', $seStudentId)
            ->firstOrFail();
        return (bool) $plan->delete();
    }

    // ── Notes ────────────────────────────────────────────────────────────────

    public function notesFor(int $seStudentId): Collection
    {
        return SpecialEducationNote::query()
            ->where('se_student_id', $seStudentId)
            ->with(['author:id,name,name_ar'])
            ->latest('id')
            ->get();
    }

    public function addNote(array $data): SpecialEducationNote
    {
        return SpecialEducationNote::create($data);
    }

    public function deleteNote(int $seStudentId, int $id): bool
    {
        $note = SpecialEducationNote::where('id', $id)
            ->where('se_student_id', $seStudentId)
            ->firstOrFail();
        return (bool) $note->delete();
    }
}
