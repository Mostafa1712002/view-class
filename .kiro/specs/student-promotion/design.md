# Design: Student Promotion (ترحيل الطلاب)

## What already exists vs what is new

| Area | Exists today | This feature adds |
|------|--------------|-------------------|
| Year rollover of **classes** | `AcademicYearMigrationService::migrateClasses()` (additive, idempotent) | Unchanged; reused as the step that materializes next year's classes. |
| Class→class student copy | `AcademicYearMigrationService::promoteStudents()` (pivot `syncWithoutDetaching`) | Kept for manual moves; the new engine supersedes it for school-wide promotion. |
| Migrate page | `SchoolAcademicYearController@migrate` + `migrate.blade.php` | New tabs/screens for pass-fail, preview, execute-with-password, rollback history. |
| Grade (section) | `sections`: `name`, `level`, `gender` | `grade_number` ordinal 1..12 + standard-list dropdown. |
| Class | `classes`: `name`, `grade_level`, `division`, `capacity` | `number` (رقم الصف) unique per grade + uniqueness rule. |
| Enrollment result | none | `class_student.result` (pending/passed/failed). |
| Graduated | hack: `section.name LIKE %خريج%` | real `users.graduated_at` + batch link; filter extended. |
| Undo/audit | none | `promotion_batches` + `promotion_batch_items`. |
| Notifications | `notifications` table (Laravel) exists | `ClassCapacityExceeded` notification to `school-admin`. |

Everything new follows the project's **Module / Repository / Action** rules. A
new module `app/Modules/Promotion/` owns the engine; the small schema tweaks to
grades/classes/pivot stay with their existing controllers (legacy area) but the
promotion logic never reaches into Eloquent directly — it goes through a
repository.

---

## Architecture overview

```
Admin (school-admin)
   │  /admin/schools/{school}/academic-years/migrate  (existing page, extended)
   ▼
SchoolAcademicYearController (existing)  ── delegates promotion concerns to ──►  Promotion module
                                                                                  │
  app/Modules/Promotion/                                                          │
  ├─ Controllers/PromotionController.php        (preview / execute / rollback / pass-fail screens)
  ├─ Actions/
  │   ├─ PreviewPromotionAction.php             (pure compute, no writes)  ──┐
  │   ├─ ExecutePromotionAction.php             (password gate → plan → apply → batch)
  │   ├─ RollbackPromotionAction.php            (restore from batch items)
  │   └─ SetEnrollmentResultAction.php          (pass/fail set/confirm)
  ├─ Services/PromotionPlanner.php              (the algorithm → produces a Plan DTO)
  ├─ DTOs/  (PromotionPlan, PlannedMove, GradePlan)
  ├─ Repositories/
  │   ├─ Contracts/PromotionRepository.php
  │   └─ EloquentPromotionRepository.php        (all Eloquent lives here)
  ├─ Notifications/ClassCapacityExceeded.php
  └─ Routes/web.php  (registered under the existing school-admin group)
```

The **planner is a pure function**: `plan(school, sourceYear, destYear) → PromotionPlan`.
Preview renders the plan; Execute re-plans then applies inside one transaction and
writes the batch. This keeps the destructive apply and the safe preview using the
exact same algorithm (no drift).

---

## Data model changes

### 1. `sections` — grade ordinal (NEW column)

```sql
ALTER TABLE sections
  ADD COLUMN grade_number TINYINT UNSIGNED NULL AFTER level; -- 1..12, standard ordinal
-- uniqueness is per school + gender track (a school may run boys & girls grade 7)
CREATE UNIQUE INDEX sections_school_gender_grade_unique
  ON sections (school_id, gender, grade_number);
```

**Decision — put the ordinal on `sections`, not derive it.**
`classes.grade_level` already holds 1..12, but a class is the wrong home for the
grade's identity (promotion is grade→grade, and a grade with zero classes still
needs an ordinal to be a promotion target). Tradeoff: one nullable column + a
backfill; the payoff is a single source of truth the planner reads directly.
`classes.grade_level` stays as legacy display; new code reads `section.grade_number`.

The standard list is a small **PHP lookup** (ordinal → [name, level]) in
`App\Modules\Promotion\Support\StandardGrades`, not a DB table.
**Decision:** it never changes and has 12 fixed rows — a lookup constant beats a
table + seeder + admin CRUD (YAGNI). The grade-create dropdown, subjects page,
and import validation all read this one constant.

### 2. `classes` — class number (NEW column)

```sql
ALTER TABLE classes
  ADD COLUMN number SMALLINT UNSIGNED NULL AFTER division; -- رقم الصف, unique per grade
CREATE UNIQUE INDEX classes_section_number_unique ON classes (section_id, number);
```

**Decision — a dedicated integer `number`, not reuse `division` (أ/ب/ج).**
Class-matching is numeric (class 3 → class 3) and overflow cascades to *k+1*;
a string division can't be incremented cleanly. Tradeoff: a backfill for legacy
classes (derive from `division` order or prompt the admin). The unique index
enforces Rule #1 at the DB level; the request also validates for a friendly error.

### 3. `class_student` — enrollment result (NEW column)

```sql
ALTER TABLE class_student
  ADD COLUMN result ENUM('pending','passed','failed') NOT NULL DEFAULT 'pending';
```

**Decision — result on the pivot, not on `users`.**
Pass/fail is specific to a (student, class, year); the pivot already encodes the
year via `classes.academic_year_id`. This keeps history correct across years and
matches the existing `class_student` grain. Model: expose it via
`->withPivot('result')` on `ClassRoom::students()` / `User` side.

### 4. `users` — graduation flag (NEW column)

```sql
ALTER TABLE users
  ADD COLUMN graduated_at TIMESTAMP NULL AFTER class_room_id;
```

**Decision — a real timestamp, replacing the `section.name LIKE %خريج%` hack.**
The current filter is brittle (depends on a fake section name). Extend the
students filter to `whereNotNull('graduated_at')` OR the legacy match, so old
data still shows while new graduations use the column. `promotion_batch_items`
links each graduation to its batch for clean rollback.

### 5. `promotion_batches` + `promotion_batch_items` (NEW tables)

```sql
CREATE TABLE promotion_batches (
  id BIGINT UNSIGNED PK AUTO_INCREMENT,
  school_id BIGINT UNSIGNED,               -- FK schools, multi-tenant scope
  source_year_id BIGINT UNSIGNED,          -- FK academic_years
  destination_year_id BIGINT UNSIGNED,     -- FK academic_years
  status ENUM('executed','rolled_back') NOT NULL DEFAULT 'executed',
  summary JSON NULL,                       -- counts: promoted/graduated/overflow/unplaced per grade
  executed_by BIGINT UNSIGNED,             -- FK users
  executed_at TIMESTAMP,
  rolled_back_by BIGINT UNSIGNED NULL,
  rolled_back_at TIMESTAMP NULL,
  created_at, updated_at
);

CREATE TABLE promotion_batch_items (
  id BIGINT UNSIGNED PK AUTO_INCREMENT,
  batch_id BIGINT UNSIGNED,                -- FK promotion_batches, cascade
  student_id BIGINT UNSIGNED,              -- FK users
  action ENUM('promoted','graduated','overflow_moved','not_moved') NOT NULL,
  from_section_id BIGINT UNSIGNED NULL,
  from_class_id   BIGINT UNSIGNED NULL,
  to_section_id   BIGINT UNSIGNED NULL,
  to_class_id     BIGINT UNSIGNED NULL,
  reason VARCHAR(255) NULL,               -- e.g. 'no_next_grade', 'capacity_full'
  created_at, updated_at
);
CREATE INDEX pbi_batch_idx ON promotion_batch_items (batch_id);
```

The item row **is** the before/after record: rollback reads `from_*` to restore
and `action` to know whether to clear `graduated_at` or re-attach a pivot.

---

## The promotion algorithm (planner pseudo-code)

`PromotionPlanner::plan(School $school, AcademicYear $src, AcademicYear $dst): PromotionPlan`

```
# Preconditions (block execution if violated)
assert every promotable section has grade_number
assert every class in play has a number

# Build grade index for this school, per gender track
grades = sections(school) keyed by (gender, grade_number)   # ordinal → section

plan = []
# Process HIGH → LOW so a grade's students leave before the lower grade arrives.
for N in 12 .. 1:                                            # descending
  for track in gender_tracks(school):
    srcGrade = grades[(track, N)]        ; continue if none
    dstGrade = grades[(track, N+1)]      # may be null → graduation

    # eligible = passed students enrolled in srcGrade's classes for the SOURCE year
    for srcClass in classes(srcGrade, year=src) ordered by number:
      passed = students(srcClass) where pivot.result == 'passed'
               order by name ASC                             # deterministic

      if dstGrade == null:                                   # Rule #2 / #3
        for s in passed: plan += Move(s, from=srcClass, to=null, action='graduated')
        continue

      # Rule #5 class-number matching: class k → class k in dstGrade/year=dst
      place(passed, dstGrade, targetNumber = srcClass.number)

# Rule #4 falls out for free: nothing targets ordinal 1 (no N=0 source).
return PromotionPlan(plan, summaryCounts)
```

### `place(students, dstGrade, targetNumber)` — capacity + overflow (Rule #9)

```
remaining = students                       # already sorted by name ASC
k = targetNumber
while remaining not empty:
  dstClass = class in dstGrade (year=dst) with number == k
  if dstClass == null:
    # no class k to receive them → cannot place
    for s in remaining: plan += Move(s, to=null, action='not_moved', reason='capacity_full')
    notifyPrincipal(dstGrade, k, count=remaining.size)      # US-008
    return

  # occupants who STAY in dstClass = students already failed/held there for dest year
  staying = students(dstClass, year=dst) where pivot.result != 'passed'   # they keep their seat
  free = dstClass.capacity - staying.count

  if remaining.size <= free:
    for s in remaining: plan += Move(s, to=dstClass, action='promoted'); return

  # overflow: keep the FIRST `free` (alphabetically first), push the LAST to k+1
  keep     = remaining[0 : free]                            # names sort first
  overflow = remaining[free : ]                             # alphabetically LAST → relocate
  for s in keep: plan += Move(s, to=dstClass, action='promoted')
  remaining = overflow
  for s in overflow: mark action='overflow_moved'
  k = k + 1                                                 # cascade to next class
```

**Alphabetical-last selection** honors the card: capacity 20, 10 staying + 20
incoming = 30 would-be → `free = 10`, the alphabetically-**first** 10 stay in
class 3, the alphabetically-**last** 10 relocate to class 4; if class 4 is full
or absent, they cascade / become `not_moved` and the principal is notified.

**Open decision (flagged):** overflow is drawn from the **incoming promoted
cohort only**; staying (failed) students keep their seat. The card's example is
consistent with this. The alternative — pooling staying + incoming and relocating
the alphabetical-last of the union — would move failed students too. Defaulting
to "incoming-only" is safer and matches the wording; confirm with the user.

### Apply (ExecutePromotionAction)

```
verify Hash::check(request.password, auth_user.password)   # Rule #5, US-010
guard: no un-rolled-back batch already exists for (school, src→dst)
DB::transaction:
  plan = PromotionPlanner::plan(school, src, dst)           # re-plan under lock
  batch = create promotion_batch(executed_by, years, status='executed')
  for move in plan:
    record from_section/from_class (student's current)      # before-state
    case move.action:
      promoted, overflow_moved:
        detach student from old class pivot; attach to move.to (result='pending')
        update users.section_id = dstGrade.id, class_room_id = move.to.id
      graduated:
        detach from old class pivot; users.class_room_id = null; graduated_at = now()
        (keep users.section_id or set to null — see open question)
      not_moved:
        no enrollment change (student stays); record reason
    insert promotion_batch_item(batch, student, action, from_*, to_*, reason)
  batch.summary = counts
```

### Rollback (RollbackPromotionAction) — Rule #6

```
verify password (US-010); batch must be the latest 'executed' for the school
DB::transaction:
  for item in batch.items (reverse order):
    if student missing/deleted: record skip; continue
    case item.action:
      promoted, overflow_moved:
        detach from item.to_class; re-attach to item.from_class
        users.section_id = item.from_section_id; class_room_id = item.from_class_id
      graduated:
        graduated_at = null; re-attach to item.from_class; class_room_id = item.from_class_id
      not_moved: nothing to reverse
  batch.status = 'rolled_back'; rolled_back_by/at = now()
```

Restricting rollback to the **latest** batch avoids reversing onto a state a
later batch already changed (US-011 AC). Newer-first is the only safe order.

---

## Pass/fail confirmation (US-004)

- `SetEnrollmentResultAction::execute(classId, [studentId => result])` writes
  `class_student.result` via the repository (scoped to school).
- UI: a per-class roster with a passed/failed toggle and "mark all passed" bulk
  action, reachable from the class page and from the promotion preview screen.
- Preview/Execute count only `result == 'passed'`; a grade with any `pending`
  raises a non-blocking warning the admin must acknowledge.

## Graduated students query (US-006)

Extend `StudentController@applyFilters` (and `studentCounts`) so the `graduates`
filter is:

```php
$builder->where(fn ($w) => $w
    ->whereNotNull('users.graduated_at')
    ->orWhereHas('section', fn ($s) => $s->where('name', 'like', '%خريج%')));
```

Wire the existing "الطلاب الخريجون" item in "خيارات أخرى" (already links to
`?filter=graduates`) — no new route needed; only the query broadens.

## Principal notification (US-008)

`ClassCapacityExceeded` (DB notification) sent to school users with role
`school-admin` for the acting school, payload = grade name, class number, count
unplaced. Uses the existing `notifications` table / Notifiable `User`.

---

## Affected files & routes

**New (Promotion module)** — `app/Modules/Promotion/**` as in the diagram, plus
`Routes/web.php`:

| Method | Route name | Purpose |
|--------|-----------|---------|
| GET  | `admin.schools.promotion.results` | pass/fail roster (US-004) |
| POST | `admin.schools.promotion.results.save` | set/confirm results |
| GET  | `admin.schools.promotion.preview` | dry-run plan (US-009) |
| POST | `admin.schools.promotion.execute` | password-gated apply (US-010) |
| GET  | `admin.schools.promotion.batches` | batch history |
| POST | `admin.schools.promotion.batches.rollback` | undo latest batch (US-011) |

Bind `PromotionRepository → EloquentPromotionRepository` in
`App\Providers\RepositoryServiceProvider`.

**Extended (existing, legacy area):**
- `SchoolGradeLevelController@storeSection` / grade-levels blade — standard-list dropdown + `grade_number` (US-001).
- `SchoolGradeLevelController@storeClass` / `@updateClass` / classes blade — `number` field + per-grade uniqueness (US-003), show "رقم الصف" column.
- `StudentController@applyFilters` + `studentCounts` — graduated filter broadened (US-006).
- Subjects create (`/admin/subjects/create`) — grade options from `StandardGrades` (US-002).
- `StudentImport` module template + preview validation — standardized grade options (US-002).
- `migrate.blade.php` — add links/tabs into the promotion screens.

**Migrations (new):** `sections.grade_number`, `classes.number` (+unique),
`class_student.result`, `users.graduated_at`, `promotion_batches`,
`promotion_batch_items`. All additive; each has a `down()`.

## API envelope

These are session Blade admin routes, not `/api/*`, so the JSON envelope rule
does not apply. If any AJAX preview endpoint is added it returns the standard
`ApiResponse::ok(...)` envelope.

## Open questions for the user

1. **Gender tracks**: do promotions run per gender track (boys grade 7 → boys grade 8) — assumed yes, since `sections.gender` exists — or ignore gender? Uniqueness index above assumes per-track.
2. **Overflow pool**: relocate from incoming-only (default, chosen) vs. pool incoming+staying? (See planner note.)
3. **Graduated `section_id`**: on graduation, keep the student's last `section_id` (so they still show under their final grade) or null it? Default: keep it, only null `class_room_id` + set `graduated_at`.
4. **Destination classes**: must next year's classes already exist (created via the existing `migrateClasses`) before promotion, or should Execute auto-materialize matching class numbers in the destination grade/year? Default: require them to exist; preview blocks with a clear message if a target class number is missing.
5. **Legacy backfill**: how to assign `grade_number` to existing free-text grades and `number` to existing classes — one-time guided screen vs. best-effort auto-map from `division`/`grade_level`? Affects migration scope.
