# Tasks: Student Promotion (ترحيل الطلاب)

Phases are ordered so the **safe, foundational** parts land first. The
**destructive** promotion engine (Phase 5) must not start until the password
gate + batch/rollback safety (Phases 5.1–5.2) exist. Destructive phases are
marked ⚠️.

---

## Phase 1: Grade naming standardization (safe)

### Task 1.1: Standard grade lookup + ordinal column
- [ ] Migration: add `sections.grade_number` (tinyint 1..12, nullable) + unique index `(school_id, gender, grade_number)`
- [ ] `App\Modules\Promotion\Support\StandardGrades` constant (12 entries → name, level, ordinal)
- [ ] `Section` model: fillable `grade_number`, cast int, helper to resolve label from ordinal

**Outcome:** grades can carry a standard ordinal.
**Dependencies:** None

### Task 1.2: Grade create/edit uses the standard dropdown
- [ ] `SchoolGradeLevelController@storeSection`: accept a standard-entry choice; set `name`, `level`, `grade_number`
- [ ] grade-levels blade: replace free-text name with "نوع الصف الدراسي" dropdown
- [ ] Allow renaming/mapping an existing grade to a standard entry (assign ordinal) without touching its classes/students
- [ ] Validation: reject duplicate ordinal per school+gender

**Outcome:** US-001 satisfied.
**Dependencies:** 1.1

### Task 1.3: Propagate standard options to subjects + Excel import
- [ ] Subjects create (`/admin/subjects/create`): grade options from `StandardGrades`
- [ ] StudentImport template + preview validation: standardized grade options; flag non-standard rows
- [ ] Regenerate `resources/templates/students_import.xlsx` header/options as needed

**Outcome:** US-002 satisfied (card note #8).
**Dependencies:** 1.1

---

## Phase 2: Class numbering (safe)

### Task 2.1: Class number column + uniqueness
- [ ] Migration: add `classes.number` (smallint, nullable) + unique index `(section_id, number)`
- [ ] `ClassRoom` model: fillable `number`, cast int
- [ ] `storeClass` / `updateClass`: validate `number` unique within grade (exclude self on edit) — Rule #1
- [ ] classes blade: add `number` input; show "رقم الصف" column in the list

**Outcome:** US-003 satisfied.
**Dependencies:** None

### Task 2.2: Legacy backfill for grade ordinals + class numbers
- [ ] One-time guided screen (or command) to assign `grade_number` to legacy grades and `number` to legacy classes
- [ ] Surface "needs standardization" state; block promotion until resolved

**Outcome:** existing data becomes promotion-ready.
**Dependencies:** 1.1, 2.1

---

## Phase 3: Pass/fail confirmation (safe)

### Task 3.1: Enrollment result field
- [ ] Migration: add `class_student.result` enum(pending/passed/failed) default pending
- [ ] Expose via `withPivot('result')` on `ClassRoom::students()` and the User side

**Outcome:** result stored per enrollment.
**Dependencies:** None

### Task 3.2: Pass/fail UI + action
- [ ] `SetEnrollmentResultAction` + repository method (scoped by school)
- [ ] Roster screen with per-student toggle + "mark all passed" bulk; reachable from class page and promotion preview
- [ ] Routes `promotion.results` / `promotion.results.save`

**Outcome:** US-004 satisfied.
**Dependencies:** 3.1

---

## Phase 4: Graduated students filter (safe)

### Task 4.1: Real graduation flag + broadened filter
- [ ] Migration: add `users.graduated_at` (timestamp nullable)
- [ ] `StudentController@applyFilters` + `studentCounts`: `graduates` = `graduated_at` NOT NULL OR legacy `%خريج%`
- [ ] Confirm "الطلاب الخريجون" item in "خيارات أخرى" points at `?filter=graduates` (already does)

**Outcome:** US-006 filter ready before any promotion can graduate a student.
**Dependencies:** None

---

## Phase 5: Promotion engine (⚠️ DESTRUCTIVE — safety gates first)

### Task 5.1: ⚠️ Batch audit tables + repository
- [ ] Migrations: `promotion_batches`, `promotion_batch_items` (schema in design.md)
- [ ] Models + `PromotionRepository` contract + `EloquentPromotionRepository`; bind in `RepositoryServiceProvider`
- [ ] All Eloquent for promotion lives in the repository (module rule)

**Outcome:** audit/undo storage exists before any write.
**Dependencies:** 1.1, 2.1, 3.1, 4.1

### Task 5.2: ⚠️ Password gate + planner (pure, no writes)
- [ ] `PromotionPlanner::plan()` — high→low, class-number matching, graduation, empty-lowest-grade, capacity overflow with alphabetical-last (design pseudo-code)
- [ ] DTOs: `PromotionPlan`, `GradePlan`, `PlannedMove`
- [ ] Password verification helper (`Hash::check` vs auth user) + auth throttling
- [ ] Unit-test the planner against the card's capacity example (cap 20, 10 staying + 20 incoming → last 10 relocate to class 4)

**Outcome:** the algorithm is proven safe before it can mutate data.
**Dependencies:** 5.1

### Task 5.3: Preview (safe, uses planner)
- [ ] `PreviewPromotionAction` + `promotion.preview` route/screen: per-grade/class promoted/graduated/overflow/unplaced counts, capacity warnings, pending-result warning
- [ ] Block preview→execute while preconditions unmet (missing ordinals/numbers/target classes)

**Outcome:** US-009 satisfied.
**Dependencies:** 5.2

### Task 5.4: ⚠️ Execute (password-confirmed, transactional)
- [ ] `ExecutePromotionAction`: verify password → re-plan under lock → apply (pivot + `users.section_id`/`class_room_id` + `graduated_at`) → write batch + items → summary
- [ ] Guard against double-run of same source→dest with an un-rolled-back batch
- [ ] `ClassCapacityExceeded` notification to `school-admin` for unplaceable students
- [ ] Routes `promotion.execute`

**Outcome:** US-005, US-006(write), US-007, US-008, US-010 satisfied.
**Dependencies:** 5.3 (and 5.1, 5.2)

### Task 5.5: ⚠️ Rollback
- [ ] `RollbackPromotionAction`: password-gated; only latest `executed` batch; restore `from_*`, clear `graduated_at`, re-attach pivots; skip+report missing students; mark `rolled_back`
- [ ] `promotion.batches` history screen + `promotion.batches.rollback` route

**Outcome:** US-011 satisfied.
**Dependencies:** 5.4

---

## Phase 6: Integration & polish

### Task 6.1: Wire into existing migrate page
- [ ] Add tabs/links from `migrate.blade.php` to results/preview/history
- [ ] Localization strings (ar/en) for all new labels

### Task 6.2: End-to-end verification (local first)
- [ ] Seed a two-grade school, mark results, preview, execute, verify students list + graduated filter + capacity notification, then rollback and confirm full restoration
- [ ] Confirm multi-tenant isolation (a second school untouched)

**Outcome:** feature verified locally before deploy.
**Dependencies:** Phase 5

---

## Progress Tracking

| Phase | Tasks | Completed | Destructive | Status |
|-------|-------|-----------|-------------|--------|
| 1. Grade naming | 3 | 0 | No | Not Started |
| 2. Class numbering | 2 | 0 | No | Not Started |
| 3. Pass/fail | 2 | 0 | No | Not Started |
| 4. Graduated filter | 1 | 0 | No | Not Started |
| 5. Promotion engine | 5 | 0 | ⚠️ Yes | Not Started |
| 6. Integration | 2 | 0 | No | Not Started |
| **Total** | **15** | **0** | — | **0%** |

**Safety rule:** Phase 5 tasks 5.4/5.5 (the writes) may only be built after 5.1
(batch tables) and 5.2 (password gate + tested planner) are complete. No
school-wide student mutation ships without the rollback path in place.
