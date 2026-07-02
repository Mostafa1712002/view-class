<?php

namespace App\Modules\TeacherMaterials\Repositories\Contracts;

use Illuminate\Support\Collection;

/**
 * Data access for the teacher "إدارة المواد" hub (Trello #287).
 *
 * Security invariant: a teacher only ever sees subjects/classes/content that
 * derive from their own teaching assignments. The subject boundary is the
 * gate — every content list is filtered by a subject the teacher owns.
 */
interface TeacherMaterialRepository
{
    /** Subjects the teacher is assigned to teach, in the active school. */
    public function teacherSubjects(int $teacherId, ?int $schoolId): Collection;

    /** True when the subject belongs to the teacher's assigned subject set. */
    public function ownsSubject(int $teacherId, ?int $schoolId, int $subjectId): bool;

    /** Grade levels available for a subject the teacher teaches. @return array<int,array{value:int,label:string}> */
    public function gradesForSubject(int $teacherId, ?int $schoolId, int $subjectId): array;

    /** Classes the teacher teaches the subject in, optionally narrowed to a grade. @return array<int,array{id:int,name:string}> */
    public function classesForSubject(int $teacherId, ?int $schoolId, int $subjectId, ?int $grade): array;

    /** Class ids the teacher teaches the subject in. @return array<int> */
    public function teacherClassIdsForSubject(int $teacherId, ?int $schoolId, int $subjectId): array;

    /** Normalised content items for a content type. @return array<int,array{title:string,badges:array,date:?string,url:?string,icon:string}> */
    public function content(int $teacherId, ?int $schoolId, int $subjectId, ?int $grade, ?int $classId, string $type): array;
}
