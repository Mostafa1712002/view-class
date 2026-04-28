# Tasks: Sprint 4 — Educational Operations

## Phase 0: Sprint 1+2+3 regression sanity (per meta-card)

### Task 0.1: Verify shipped pages render
- [ ] Hit live site, login as developer@midade.com
- [ ] Open dashboard → schools → settings → school years → classes
- [ ] Open all 4 user pages (students, parents, teachers, admins)
- [ ] Open user cards page + generate one PDF
- [ ] Note any 500 / regression in `regression-notes.md`

**Outcome:** Sprint 1+2+3 confirmed green or list of regressions to fix
**Dependencies:** none

---

## Phase 1: Subjects module (US-401)

### Task 1.1: Migrations + models
- [ ] Create migration `subjects`
- [ ] Create migration `subject_units`
- [ ] Create migration `subject_lessons`
- [ ] Create models with relationships and soft deletes
- [ ] Run `php artisan migrate` against local DB

### Task 1.2: Repository + interface
- [ ] `Modules/Subjects/Repositories/Contracts/SubjectRepository.php`
- [ ] `Modules/Subjects/Repositories/EloquentSubjectRepository.php`
- [ ] Bind in `RepositoryServiceProvider`

### Task 1.3: Controller + actions
- [ ] `SubjectController` with index/create/store/edit/update/destroy/lessonTree/creditHours
- [ ] `CreateSubjectAction`, `UpdateSubjectAction`
- [ ] `ImportSubjectsFromExcelAction` (placeholder; Excel handler later)
- [ ] `ImportSubjectsFromTemplateAction` (clones platform-provided subjects)

### Task 1.4: Routes + sidebar
- [ ] Add subjects group in `routes/web.php` under `admin/subjects`
- [ ] Add "إدارة المواد ← المواد" mega-menu in sidebar

### Task 1.5: Views
- [ ] `subjects/index.blade.php` — table with checkbox + 8 cols + 3 buttons + per-row dropdown
- [ ] `subjects/_form.blade.php` — manual add/edit form
- [ ] `subjects/lesson_tree.blade.php` — units + lessons tree CRUD
- [ ] `subjects/credit_hours.blade.php` — bulk credit-hours editor

### Task 1.6: i18n
- [ ] `lang/ar/sprint4.php` — labels, buttons, columns
- [ ] `lang/en/sprint4.php`

### Task 1.7: Verify live
- [ ] Deploy → login → open subjects → add manual → edit → delete
- [ ] Add unit → add lesson → reorder

**Outcome:** Subjects fully usable on live
**Dependencies:** Task 0.1 green

---

## Phase 2: Question Bank module (US-402)

### Task 2.1: Migrations + models
- [ ] `question_banks`, `question_bank_subjects`, `question_bank_users`, `questions` migrations
- [ ] Models with relationships + soft deletes

### Task 2.2: Repositories
- [ ] `QuestionBankRepository` interface + Eloquent impl
- [ ] `QuestionRepository` interface + Eloquent impl
- [ ] Bind in provider

### Task 2.3: Controllers + actions
- [ ] `QuestionBankController` (index/create/store/edit/update/destroy/library/clone)
- [ ] `QuestionController` nested under bank
- [ ] `CreateQuestionBankAction`, `CloneQuestionBankAction`

### Task 2.4: Views
- [ ] `question-banks/index.blade.php` — table + 2 buttons (Add, Bank Library)
- [ ] `question-banks/_form.blade.php` — name + subjects + viewer/editor multi-select
- [ ] `question-banks/library.blade.php` — pre-built banks list with "Clone" CTA
- [ ] `question-banks/questions/index.blade.php` — questions list under a bank

### Task 2.5: Routes + sidebar
- [ ] Add to sidebar under "إدارة المواد ← بنك الأسئلة"

### Task 2.6: Verify live
- [ ] Create bank → assign subjects → add MCQ question → list it

**Outcome:** Question banks usable on live
**Dependencies:** Task 1.7 (subjects must exist first)

---

## Phase 3: Class Periods module (US-403)

### Task 3.1: Migrations + models
- [ ] `class_periods`, `time_slots`, `schedule_entries` migrations
- [ ] Models with relationships + soft deletes

### Task 3.2: Repositories
- [ ] `ClassPeriodRepository`, `TimeSlotRepository`, `ScheduleEntryRepository`
- [ ] `ScheduleConflictDetector` service (teacher + classroom conflicts)

### Task 3.3: Controllers + actions
- [ ] `ClassPeriodController` (index/store/destroy/advanced/substitute)
- [ ] `TimeSlotController` (index/store/destroy)
- [ ] `ScheduleEntryController` (store/destroy with conflict guard)
- [ ] `CreateClassPeriodAction`, `SetSubstituteTeacherAction`, `PlaceScheduleEntryAction`

### Task 3.4: Views
- [ ] `class-periods/index.blade.php` — main grid with 6 buttons
- [ ] `class-periods/advanced.blade.php` — drag-drop builder (jQuery)
- [ ] `class-periods/time_slots.blade.php` — time slots manager

### Task 3.5: Routes + sidebar
- [ ] Add to sidebar under "إدارة المواد ← الحصص"

### Task 3.6: Verify live
- [ ] Define time slots → add class period → place on grid → conflict test

**Outcome:** Schedule builder live
**Dependencies:** Task 1.7 + Task 2.6

---

## Phase 4: School Schedule (US-404, view-only)

### Task 4.1: Read-only repository
- [ ] `ScheduleViewRepository` with filters: grade, section, teacher, subject

### Task 4.2: Controller + view
- [ ] `SchoolScheduleController` (index, pdf)
- [ ] `school-schedule/index.blade.php` — Sun-Thu grid with filters
- [ ] `school-schedule/pdf.blade.php` — A4 landscape print template

### Task 4.3: Routes + sidebar
- [ ] Add as top-level "الجدول المدرسي" sidebar entry

### Task 4.4: Verify live
- [ ] Open with no filters → full grid renders
- [ ] Filter by teacher → grid filters
- [ ] Print PDF → opens valid PDF

**Outcome:** View-only schedule live
**Dependencies:** Task 3.6

---

## Phase 5: Trello close-out

### Task 5.1: Comment + reassign cards
- [ ] Move sprint 4 + 3 feature cards from `sprint prompt` → `testing prompt`
- [ ] Post Arabic QA comment on each
- [ ] Reassign each to its original creator (look up via card actions API)

### Task 5.2: Update Kiro tasks.md
- [ ] Tick all completed sub-tasks
- [ ] Update progress table
- [ ] Final commit + push

**Outcome:** Sprint 4 closed; Trello board reflects testing state
**Dependencies:** all phases above

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 0. Regression check | 1 | 0 | Not Started |
| 1. Subjects | 7 | 0 | Not Started |
| 2. Question Bank | 6 | 0 | Not Started |
| 3. Class Periods | 6 | 0 | Not Started |
| 4. School Schedule | 4 | 0 | Not Started |
| 5. Trello close-out | 2 | 0 | Not Started |
| **Total** | **26** | **0** | **0%** |
