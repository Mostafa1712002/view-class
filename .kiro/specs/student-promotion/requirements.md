# Requirements: Student Promotion (ترحيل الطلاب)

## Overview

Promotion moves a school's students **up one grade** at the turn of the academic
year. A student in grade *N* becomes a student in grade *N+1*, keeping the same
class **number** where possible; students in the highest grade **graduate**
(exit); the lowest grade ends **empty**; only students marked **passed** move;
class **capacity** is respected with an alphabetical overflow rule; the whole
operation is **password-confirmed**, **reversible**, and **audited**.

This feature **extends** the existing `App\Services\AcademicYearMigrationService`
and the migrate page at `admin.schools.academic-years.migrate`
(`/admin/schools/{school}/academic-years/migrate`). It does **not** replace the
year-rollover of classes/time-slots/lessons already there — it adds the
grade-increment student engine on top of it.

### Domain vocabulary (as it exists in code today)

| Card term | Arabic | Table / column | Notes |
|-----------|--------|----------------|-------|
| Grade | الصف | `sections` | `name` (free Arabic), `level` enum, `gender`. **No ordinal 1..12 today.** |
| Class | الفصل | `classes` | `name`, `grade_level` (int 1..12, currently the grade ordinal, mislabeled), `division` (أ/ب/ج), `capacity`, `gender`, `academic_year_id`, `section_id`. **No per-grade class number today.** |
| Enrollment | — | `class_student` pivot **and** `users.class_room_id` / `users.section_id` | Students are linked **both** ways — a known dual-source-of-truth that promotion must keep in sync. |
| Graduated | الطلاب الخريجون | *(hack)* students whose `section.name` LIKE `%خريج%` | Today there is **no** graduated flag; the filter matches a fake section name. |
| Principal | مدير المدرسة | `role.slug = school-admin` | Recipient of the capacity-overflow notification. |

### Cross-cutting constraints (apply to every story)

- **NFR — Multi-tenant scope**: every query, count, and write is scoped to the
  acting user's `school_id`. Promotion never reads or moves another school's
  students. (RULES: scope enforced in repositories, not controllers.)
- **NFR — Destructive & reversible**: executing a promotion mutates enrollment
  for the whole school. Every execution is recorded as an auditable **batch**
  that can be fully rolled back (US-011). No promotion may run without the
  password gate (US-010).
- **NFR — Soft deletes / audit**: `users` already soft-delete. Graduation and
  promotion set columns / write audit rows rather than deleting enrollment
  history. Batches and batch items are retained for audit.
- **NFR — Idempotent foundations**: the existing `migrateClasses` /
  `promoteStudents` stay additive and idempotent; the new engine adds a
  one-shot, batch-tracked school-wide operation guarded against double-run.

---

## User Stories

### Group A — Grade naming standardization

#### US-001: Standard grade list with ordinal number
**As a** school admin
**I want** grades to come from a fixed standard list (1..12) with an ordinal number
**So that** promotion can reliably compute "the next grade".

Standard list (ordinal → name):
1 الصف الأول الابتدائي · 2 الصف الثاني الابتدائي · 3 الصف الثالث الابتدائي ·
4 الصف الرابع الابتدائي · 5 الصف الخامس الابتدائي · 6 الصف السادس الابتدائي ·
7 الصف الأول المتوسط · 8 الصف الثاني المتوسط · 9 الصف الثالث المتوسط ·
10 الصف الأول الثانوي · 11 الصف الثاني الثانوي · 12 الصف الثالث الثانوي.

**Acceptance Criteria:**
- WHEN the admin creates a grade at `/admin/schools/{school}/grade-levels` THE SYSTEM SHALL present a "نوع الصف الدراسي" dropdown of the 12 standard entries instead of a free-text name.
- WHEN a standard entry is chosen THE SYSTEM SHALL persist its ordinal number (1..12), its standard Arabic name, and derive its `level` (primary/intermediate/secondary) from the entry.
- WHILE the standard list defines the level for each ordinal THE SYSTEM SHALL keep the ordinal unique per school (a school cannot have two "grade 7" grades of the same gender track).
- WHERE an existing grade has a legacy free-text name THE SYSTEM SHALL allow renaming/mapping it to a standard entry (assigning its ordinal) without losing its classes or students.
- IF a grade has no ordinal assigned THEN THE SYSTEM SHALL exclude it from the promotion engine and surface it as "needs standardization" before a promotion can run.

#### US-002: Standardized options in subjects and Excel import
**As a** school admin
**I want** the subjects create page and the student Excel import to use the same standard grade options
**So that** grades stay consistent across the system (card note #8).

**Acceptance Criteria:**
- WHEN the admin opens `/admin/subjects/create` THE SYSTEM SHALL offer grade selection from the standardized grade list of the active school.
- WHEN the student import template is generated THE SYSTEM SHALL reflect the standardized grade naming/options so imported rows map to standard grades.
- IF an import row names a grade that is not a standard entry THEN THE SYSTEM SHALL flag the row in the import preview rather than silently creating a non-standard grade.

### Group B — Class numbering

#### US-003: Each class has a number, unique within its grade
**As a** school admin
**I want** each class (فصل) to carry a number, unique inside its grade
**So that** promotion can match class *k* → class *k*.

**Acceptance Criteria:**
- THE SYSTEM SHALL store an integer class **number** on every class and show it in the class list column "رقم الصف".
- WHEN the admin creates a class in a grade (`/admin/schools/{school}/grade-levels/{section}/classes`) THE SYSTEM SHALL reject a class number that already exists for another class in that same grade (Rule #1 — unique class-number per grade).
- WHEN the admin edits a class number THE SYSTEM SHALL apply the same uniqueness check, excluding the class being edited.
- WHERE legacy classes have no number THE SYSTEM SHALL require a number to be assigned before that grade participates in promotion.

### Group C — Promotion engine

#### US-004: Confirm pass/fail before promotion (Rule #7)
**As a** school admin
**I want** to mark each student ناجح (passed) or راسب (failed) per class/year
**So that** only passed students are promoted.

**Acceptance Criteria:**
- THE SYSTEM SHALL store a per-enrollment result (`pending` / `passed` / `failed`) on the student's class membership for the source year.
- THE SYSTEM SHALL provide a screen to set/confirm each student's result (individually and in bulk per class) before a promotion is executed.
- WHEN a promotion is previewed or executed THE SYSTEM SHALL treat only `passed` students as eligible to move and SHALL leave `failed` and `pending` students in their current grade/class.
- IF any student in a targeted grade is still `pending` THEN THE SYSTEM SHALL warn the admin (and require an explicit acknowledgement) that unconfirmed students will not be promoted.

#### US-005: Grade-increment promotion with class-number matching (Rule C, #1-matching)
**As a** school admin
**I want** passed students in grade *N* class *k* to move to grade *N+1* class *k*
**So that** the whole school advances one grade in one operation.

**Acceptance Criteria:**
- WHEN a promotion runs THE SYSTEM SHALL, for every grade with ordinal *N*, target the grade with ordinal *N+1* in the same school (same gender track).
- WHEN moving a passed student from grade *N* class number *k* THE SYSTEM SHALL place them in grade *N+1* class number *k* (subject to capacity, US-008).
- WHEN a student is promoted THE SYSTEM SHALL update **both** the `class_student` pivot **and** `users.section_id` / `users.class_room_id` so the students list and the pivot stay consistent.
- THE SYSTEM SHALL process grades from highest ordinal downward so no grade receives students before its own students have left.

#### US-006: Graduation at the top grade (Rule #2, #3)
**As a** school admin
**I want** passed students whose next grade does not exist to graduate (exit)
**So that** final-grade students leave the active roster and are still findable.

**Acceptance Criteria:**
- WHEN a passed student is in grade *N* and no grade *N+1* exists for the school THE SYSTEM SHALL mark the student **graduated** and SHALL NOT move them into any class.
- WHEN a student graduates THE SYSTEM SHALL detach them from their active class (`class_student`) and clear `users.class_room_id`, while recording the batch item for rollback.
- THE SYSTEM SHALL make graduated students visible under the "الطلاب الخريجون" filter on `/admin/users/students` (the existing "خيارات أخرى" item), showing exactly the students graduated by promotion.

#### US-007: Lowest grade ends empty (Rule #4)
**As a** school admin
**I want** the lowest grade to be empty after promotion and never receive promoted students
**So that** grade 1 is ready to intake new enrollments.

**Acceptance Criteria:**
- THE SYSTEM SHALL NOT promote any student **into** the lowest grade (ordinal 1); nothing has an ordinal 0 to come from.
- WHEN promotion completes THE SYSTEM SHALL leave the lowest grade's classes empty of returning students (its passed students moved up to grade 2; failed students stay per US-004).
- Note: "empty" means no students promoted *into* grade 1; failed grade-1 students who stay are the expected exception and are surfaced to the admin.

#### US-008: Capacity overflow with alphabetical-last relocation (Rule #9)
**As a** school admin
**I want** promotion to respect class capacity and relocate overflow deterministically
**So that** no class exceeds its cap and I am told when it cannot be honored.

**Acceptance Criteria:**
- WHEN promoting into grade *N+1* class *k* would exceed that class's `capacity` (counting students who stay there plus incoming passed students) THE SYSTEM SHALL relocate the overflow to class *k+1* of the same grade.
- WHEN selecting which students overflow THE SYSTEM SHALL choose the students whose names sort **last** alphabetically, in the count equal to the overflow (e.g. cap 20, 30 would-be occupants → the alphabetically-last 10 relocate).
- IF class *k+1* is itself full or does not exist THEN THE SYSTEM SHALL cascade the same rule to the next class, and SHALL NOT move students who cannot be placed anywhere.
- WHEN one or more students cannot be placed due to capacity THE SYSTEM SHALL send a notification to the school principal (`school-admin`) naming the grade/class that exceeded capacity and the count left unplaced.
- THE SYSTEM SHALL record every relocation and every unplaced student in the batch (US-011) so the result is auditable and reversible.

#### US-009: Preview before execute
**As a** school admin
**I want** to preview the promotion outcome before committing
**So that** I can verify counts, graduations, overflows, and capacity warnings.

**Acceptance Criteria:**
- WHEN the admin requests a preview THE SYSTEM SHALL compute, without writing any data, the per-grade/per-class movements: promoted counts, graduating counts, overflow relocations, and unplaceable/capacity-exceeded warnings.
- THE SYSTEM SHALL block execution while any blocking precondition holds (grades missing ordinals US-001, classes missing numbers US-003).

#### US-010: Password-confirmed execution (Rule #5)
**As a** school admin
**I want** to type my account password to execute a promotion
**So that** this destructive operation cannot run accidentally.

**Acceptance Criteria:**
- WHEN the admin submits an execution THE SYSTEM SHALL require the admin's **own account password** and SHALL verify it against the authenticated user's credential before doing anything.
- IF the password is missing or wrong THEN THE SYSTEM SHALL reject the execution with an error and change no data.
- THE SYSTEM SHALL throttle execution attempts (consistent with the app's auth throttling) to resist brute forcing the confirmation.

#### US-011: Undo / rollback a promotion (Rule #6)
**As a** school admin
**I want** to fully reverse a promotion I executed
**So that** a mistaken promotion can be undone.

**Acceptance Criteria:**
- THE SYSTEM SHALL record each execution as a promotion **batch** with a per-student before/after item (source grade/class, target grade/class, action: promoted / graduated / overflow-moved / not-moved).
- WHEN the admin rolls back a batch THE SYSTEM SHALL restore every affected student's `section_id` / `class_room_id` and `class_student` pivot to the recorded pre-promotion state, and SHALL clear graduation set by that batch.
- THE SYSTEM SHALL allow rollback only of the **latest** executed, not-yet-rolled-back batch for the school (to avoid restoring onto a state that later batches changed), and SHALL mark the batch `rolled_back`.
- THE SYSTEM SHALL require the same password confirmation (US-010) to roll back.
- IF a student affected by the batch was deleted or moved after execution THEN THE SYSTEM SHALL skip that student during rollback and report it, rather than failing the whole reversal.

---

## Non-Functional Requirements

### NFR-001: Data integrity
- Execution and rollback run inside a DB transaction; a failure rolls the whole operation back with no partial promotion.
- A grade is guarded against double promotion of the same source year into the same destination (batch-level guard).

### NFR-002: Security & authorization
- Only users with the school-management permission (same gate as grade-levels / academic-years today) may preview, execute, or roll back.
- Password confirmation verifies the acting user's credential; the password is never logged or stored.

### NFR-003: Multi-tenancy
- All reads/writes filtered by the acting `school_id`, enforced in the repository layer.

### NFR-004: Auditability
- Batches retain who executed/rolled back, when, the source/destination years, a JSON summary, and per-student items — kept even after rollback.

### NFR-005: Performance
- Preview and execution operate per grade in bulk (no N+1 per student); a full-school promotion of a few thousand students completes within a single request/queue job without timing out.
