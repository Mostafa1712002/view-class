# Tasks: البرامج التعليمية الخاصة (قدرات · تحصيلي · نافس) — Trello #368

Read `design.md` first (section refs **D§x**) and `requirements.md`. Module root `M = app/Modules/SpecialPrograms`, views root `V = resources/views/special-programs`.

## Rules for implementers

- **File ownership:** every path below belongs to exactly one task. Do not edit another task's file. **All repository interfaces except `BankRepository` (T3.3, sole consumer) are created in T1.2** from the signatures below; feature tasks write only the `Eloquent*` implementation. If a method is missing, stop and ask the planner.
- **Same-wave dependencies:** when your code or test needs an implementation that a same-wave task is still writing, type-hint the contract and **bind a fake** in your test (`$this->app->instance(Contract::class, $fake)`).
- **Routes and names are pinned in design.md §11.3** (table). Use exactly those URIs and names: `partials/program-tabs` and the sidebar link to them. `{program}` is always `->whereIn('program', ['qiyas','tahsili','nafis'])`, and super-admin-only GET routes take no path params.
- **Shared files are touched only by T1.3 (wiring).** That means `routes/web.php`, `routes/console.php`, `app/Providers/RepositoryServiceProvider.php`, `app/Modules/Users/Support/PermissionCatalog.php`, `resources/views/components/sidebar.blade.php`, `lang/{ar,en}/shell.php`, and the existing QuestionBank files. Wiring runs early, so later tasks can test end-to-end (`routes/web.php` globs `M/Routes/*.php`, and a binding to a class that does not exist yet is harmless until it is resolved).
- **Route files:** one per task. Each route file declares its own middleware group (D§11.3) and uses the name prefix `manage.special-programs.` / `student.programs.` / `parent.programs.`. Ids are `{id}` with `->whereNumber()`, resolved in repositories, and use no implicit model binding.
- **Lang:** shared labels go in `lang/{ar,en}/special_programs.php` (T1.2). Each UI task owns `lang/{ar,en}/special_programs/<area>.php`, called as `__('special_programs/<area>.key')`.
- Arabic labels come from D§8/§9 and requirements. Use `<x-svg-icon>`, `@extends('layouts.app')` and design-system tokens. No `ad-` CSS prefix.
- **Tests:** use sqlite `:memory:` with a per-class migration list (trait from T1.1). Never require `bank_questions` migrations in tests (D§14). Run tests from the main checkout with `--filter`, **never the full suite on the dev DB**.
- Commits follow the conventional format. **No AI or session attribution lines** in commits or PRs. Do not push or deploy except in T9.3.

## Shared contracts (signatures fixed here so parallel tasks agree)

```php
// M/Repositories/Contracts/SettingsRepository.php            (interface T1.2, impl T1.2)
bandsFor(?int $schoolId, string $program): array   // 5 × ['key','name','min','max','color','icon'], resolved school→global→default
gradeLevels(string $program): array                 // int[]
weeksCount(?int $schoolId): int
saveBands(?int $schoolId, string $program, array $bands, int $userId): void
setAllowSchoolEdit(int $schoolId, string $program, bool $allow, int $userId): void
canSchoolEdit(int $schoolId, string $program): bool
saveGradeLevels(string $program, array $levels, int $userId): void
saveWeeksCount(?int $schoolId, int $weeks, int $userId): void

// M/Repositories/Contracts/EnrollmentRepository.php          (interface T1.2, impl T3.1)
enrolledQuery(int $schoolId, string $program): Builder      // users joined to active enrolments
isEnrolled(int $studentId, string $program): bool
programsOf(int $studentId): array                           // program keys
enroll(int $schoolId, string $program, array $studentIds, ?int $groupId, int $byUserId): int
remove(int $schoolId, string $program, int $studentId): void

// M/Repositories/Contracts/GroupRepository.php               (interface T1.2, impl T3.2)
forSchool(int $schoolId, string $program, ?int $teacherId = null): Collection
groupIdsLedBy(int $teacherId, string $program): array
teacherSubjectIds(int $schoolId, int $teacherId, string $program): ?array   // null = all subjects; [] = not assigned

// M/Repositories/Contracts/QuestionPoolRepository.php        (interface T1.2, impl T4.1)
candidates(int $spSubjectId, array $filters): Collection     // sp_questions rows; filters: skill_id, terms, weeks, grade_levels, difficulty, order_range, exclude_ids, passage_only, independent_only, limit, random
passageMembers(array $passageIds): Collection                // sp_questions ordered by passage_id, sub_index
stats(int $spSubjectId, array $filters): array               // ['total','by_skill','by_difficulty','by_level','max_order','in_range']
snapshot(array $spQuestionIds): array                        // id => [bank_question_id, stem, image_path, options[{text,image}], correct_index, sp_skill_id, difficulty, passage_id, sub_index]  (reads bank_questions + question_answers)

// M/Actions/Picking/QuestionPicker.php  (interface T1.2; Qiyas/Nafis/TahsiliPicker impl T4.1)
pick(SpExamSubject $subject, User $student): array           // ordered sp_question ids; passage members contiguous

// M/Repositories/Contracts/ExamRepository.php                (interface T1.2, impl T4.2)
visibleTo(User $user): Builder                               // staff scope: school; teacher = own/teaching
findForUser(int $id, User $user): SpExam                     // 404 outside scope
forStudent(User $student): Collection                        // exams where student ∈ audience (D§6)
audienceQuery(SpExam $exam): Builder                         // users
isInAudience(SpExam $exam, User $student): bool

// M/Repositories/Contracts/AttemptRepository.php             (interface T1.2, impl T5.1)
findForStudent(int $attemptId, User $student): SpAttempt     // 404 otherwise; finalizes if overdue
forExamAndStudent(int $examId, int $studentId): Collection
monitor(SpExam $exam): Collection                            // per subject × audience student status

// M/Repositories/Contracts/ReportRepository.php              (interface T1.2, impl T6.1)
// every method takes M/DTOs/ReportFilters (school, program, grade, class, group, subject, origin, from, to, exam, teacherId)
studentResults(ReportFilters $f): Collection
bySubject(ReportFilters $f): Collection
bySkill(ReportFilters $f): Collection
byDifficulty(ReportFilters $f): Collection
examStats(ReportFilters $f): Collection
distribution(ReportFilters $f): Collection
byClass(ReportFilters $f): Collection
studentReport(int $studentId, string $program): array        // header, trend, subjects, skills|difficulty, history
teacherExamSummary(int $teacherId, ReportFilters $f): Collection
examReport(int $examId, ReportFilters $f): array             // avg/max/min, top10, weak10, levels, per subject, per-student skills

// M/Repositories/Contracts/BankRepository.php                (interface + impl T3.3 — its only consumer)
// list/filter/save/deactivate program questions & passages — used only by T3.3
```

---

## Phase 1 — Schema, models, wiring

### T1.1 Migrations and test helper
- [ ] `database/migrations/2026_11_19_368000_create_sp_content_tables.php`: `sp_subjects`, `sp_skills`, `sp_questions`, `sp_question_levels` (D§2.1)
- [ ] `database/migrations/2026_11_19_368001_seed_sp_subjects.php`: 9 rows (codes and names in D§2.1; `source_subject_id` 21, 22, 62–65, 59–61)
- [ ] `database/migrations/2026_11_19_368002_add_external_index_to_bank_questions.php`: `index(['external_platform','external_id'])` only, guarded with `Schema::hasTable`
- [ ] `database/migrations/2026_11_19_368003_create_sp_school_tables.php`: `sp_settings`, `sp_teachers`, `sp_groups`, `sp_enrollments` (D§2.2)
- [ ] `database/migrations/2026_11_19_368004_create_sp_exam_tables.php`: `sp_exams`, `sp_exam_targets`, `sp_exam_subjects`, `sp_exam_skill_shares` (D§2.3)
- [ ] `database/migrations/2026_11_19_368005_create_sp_attempt_tables.php`: `sp_attempts`, `sp_attempt_items` (D§2.4)
- [ ] `database/migrations/2026_11_19_368009_seed_special_programs_permissions.php`: D§11.2. Seed `permissions` and `permission_role` (school-admin: view, manage, exams, reports, settings; teacher: view, exams, reports). Seed `job_title_permissions` for slugs `supervisor`, `vice`, `school_manager` (view, manage, exams, reports; scope `school`). Write a `down()`.
- [ ] `tests/Feature/SpecialPrograms/Concerns/LoadsSpMigrations.php`: trait that overrides `refreshDatabase()` with the base chain from `CounselingAccessTest` plus `2026_11_19_368*`. Skip `368002` (it needs `bank_questions`). Add a `grantSpPermissions()` helper and call it after `parent::setUp()`. `TestCase::setUp` runs `RoleSeeder`, which `sync()`s `permission_role` and wipes the 368009 grants.
- [ ] Everything must be sqlite-safe: no `ALTER … MODIFY`, no `JSON_CONTAINS`, no FK to `users.id`, enums declared only in new tables.

**Outcome:** `php artisan migrate` succeeds on local MySQL, and the trait boots sqlite. **Dependencies:** none.

### T1.2 Models, support, event, shared lang, settings repo
- [ ] `M/Models/` 13 models: `SpSubject`, `SpSkill`, `SpQuestion`, `SpSetting`, `SpTeacher`, `SpGroup`, `SpEnrollment`, `SpExam`, `SpExamTarget`, `SpExamSubject`, `SpSkillShare`, `SpAttempt`, `SpAttemptItem`. Include table names, casts (json and datetimes), relations, and `SoftDeletes` where the table has `deleted_at`.
- [ ] `M/Support/Programs.php`: the `PROGRAMS` const (D§3) and `DEFAULT_BANDS` (D§2.2), with `keys()`, `label()`, `defaultGradeLevels()`.
- [ ] `M/Support/PerformanceLevels.php`: 5 fixed levels (name, color, icon) and `for(float $pct, array $bands): array` (round to 2 dp, highest min ≤ pct, else lowest). Add `validate(array $bands): ?string` (5 bands, 0–100, no overlap, full cover).
- [ ] 7 interfaces in `M/Repositories/Contracts/`, exactly as in "Shared contracts": `SettingsRepository`, `EnrollmentRepository`, `GroupRepository`, `QuestionPoolRepository`, `ExamRepository`, `AttemptRepository`, `ReportRepository`. Also `M/Actions/Picking/QuestionPicker.php` (interface) and `M/DTOs/ReportFilters.php` (readonly; `fromRequest()`).
- [ ] `M/Repositories/EloquentSettingsRepository.php`. A read **never** inserts.
- [ ] `M/Events/ProgramAttemptCompleted.php`: exactly as D§10 (`Dispatchable`, `ShouldDispatchAfterCommit`).
- [ ] `lang/ar/special_programs.php` and `lang/en/special_programs.php`: program names, subject names, level names, attempt statuses, exam types, common buttons.
- [ ] `tests/Unit/SpecialPrograms/PerformanceLevelsTest.php`: defaults per program, school override via the repository, boundaries (64.99 → مقبول/ضعيف for tahsili, 95 → ممتاز).

**Outcome:** models and constants usable by every later task. **Dependencies:** T1.1 (column names). Can run in parallel with T1.1 if both follow D§2 exactly.

### T1.3 Wiring (the only task that edits shared files)
- [ ] `routes/web.php`: append after the Counseling require: `foreach (glob(__DIR__.'/../app/Modules/SpecialPrograms/Routes/*.php') as $spRoutes) { require $spRoutes; }`
- [ ] `app/Providers/RepositoryServiceProvider.php`: bind all 8 contracts → `Eloquent*` (Settings, Enrollment, Group, QuestionPool, Exam, Attempt, Report, Bank)
- [ ] `app/Modules/Users/Support/PermissionCatalog.php`: add `'special_programs' => ['label' => 'البرامج التعليمية الخاصة', 'actions' => ['view','manage','exams','questions','reports','settings']]` next to `counseling`
- [ ] `resources/views/components/sidebar.blade.php`: add `$canViewSpecialPrograms`. Point `:609` and `:543` at `Route::has('manage.special-programs.index') ? … : route('programs.coming-soon')`. Add one **query-free** `<li>` (`Route::has()` only) in each of the teacher, student and parent blocks (D§11.4). Keep the order. Do not query `sp_*` tables in the sidebar: existing tests render layouts without the 368 migrations.
- [ ] `lang/ar/shell.php` and `lang/en/shell.php`: the new nav keys, plus the missing English `section_academic`, `nav_special_programs` and `nav_alawwal`
- [ ] `routes/console.php`: `Schedule::command('special-programs:finalize-overdue')->everyFiveMinutes();`
- [ ] `app/Modules/QuestionBanks/Repositories/EloquentQuestionBankRepository.php` `visibleBankIds()`: exclude `category_type = 'special_program'` (D9)
- [ ] `database/seeders/RoleSeeder.php`: add the `special_programs` grants (school-admin: view, manage, exams, reports, settings; teacher: view, exams, reports). Without this, a fresh seed wipes them; rawaniedu was fresh-seeded.
- [ ] `app/Models/QuestionBank.php`: `CATEGORY_SPECIAL_PROGRAM = 'special_program'`. In `app/Modules/QuestionBanks/Controllers/QuestionBankController.php:572`, add it to the `category_type` validation list.
- [ ] Run `RbacSuperAdminRoutesTest` (green)

**Outcome:** the app boots with zero program route files, and each later route file is picked up automatically. **Dependencies:** T1.2 (contract class names for the bindings).

---

## Phase 2 — Import («الأول» bank)

### T2.1 Pure normalisers (no DB)
- [ ] `M/Actions/Import/AlAwwalRowNormalizer.php`: D§4.2 step 3. It decodes in a loop, strips `["`/`"]`, splits passages into sub-items with a per-index skill, derives the correct index (throws unless exactly 1), maps difficulty to 1/2/3, maps `third` → `both`, and recognises generic stems («اختر», «اختر الإجابة الصحيحة», «اختر الاجابة الصحيحة»).
- [ ] `M/Actions/Import/AlAwwalLevelMap.php`: (school_type_id, Name_Class) → grade 1..12 by ordinal word (D§4.2 step 4)
- [ ] `M/Actions/Import/DedupeKey.php`: D§4.4. Normalises tashkeel, tatweel and whitespace, and includes the image-bytes md5.
- [ ] `tests/Unit/SpecialPrograms/AlAwwalRowNormalizerTest.php`, `AlAwwalLevelMapTest.php`, `DedupeKeyTest.php`. Use fixtures copied from SM §8.1 samples (30929, 30953, 33155, 21988, 17968, 17959 double-encoded).

**Outcome:** a fully unit-tested decode layer. **Dependencies:** none (can start day 1).

### T2.2 Import action + command + runbook
- [ ] `M/Actions/Import/ImportAlAwwalBank.php`: D§4.2 steps 1–2 and 5–8, D§4.3 and D§4.4 (survivor = lowest id, level union, term widen, min week, `merged_source_ids`). Write through `MapAnswerData` + `CreateQuestion::syncAnswerRows`, with one `activity_logs` row per run. Support dry-run via an in-memory plan.
- [ ] `app/Console/Commands/SpecialProgramsImportAlAwwal.php`: signature D§4.1. Builds a runtime `alawwal` connection via `config()->set`, prints a summary table, and writes the JSON report.
- [ ] `claudedocs/special-programs-import-runbook.md`: slim extract (D§4.5 steps 1–4, exact `mysqldump` and `rsync --files-from` commands), dry-run, expected totals, verification SQL, cleanup (drop the temp DB, delete the files)
- [ ] Local run: `--dry-run` then real, from `alawal_local` (root/123) + `~/www/alawal` into local `viewclass`. Record the actual totals in the runbook. Before dedupe: qiyas 3,630, nafis 1,714, tahsili 2,704; images 4,993. Dedupe counts are approximate (≈327/149 qiyas, ≈53/26 nafis, D§4.4). A re-run must be all "unchanged".

**Outcome:** a re-runnable import, verified locally. **Dependencies:** T1.1, T1.2, T2.1.

---

## Phase 3 — Staff setup screens (parallel)

### T3.1 Hub, audience, enrolment
- [ ] `M/Support/ProgramAudience.php`: eligible classes plus readiness alerts (D§6), modelled on `PromotionPlanner::readiness`
- [ ] `M/Repositories/EloquentEnrollmentRepository.php`
- [ ] `M/Actions/Enrollment/EnrollStudents.php`, `RemoveEnrollment.php`
- [ ] `M/Http/Requests/EnrollRequest.php`
- [ ] `M/Controllers/Staff/HubController.php`: hub (3 cards + empty-bank state) and program overview page
- [ ] `M/Controllers/Staff/EnrollmentController.php`: list enrolled, «إضافة طالب لبرنامج» (class filter, search, «إضافة كل طلاب الفصل», «عرض كل الفصول»), remove
- [ ] `M/Routes/staff-setup.php`: `index`, `program`, `enrollments.*`. Middleware: view routes `permission:special_programs.view`, write routes `.manage`.
- [ ] `V/partials/program-tabs.blade.php`: tabs «الطلاب المسجلون | مجموعات الأداء | المعلمون | الاختبارات | بنك الأسئلة | التقارير | الإعدادات», each shown only if `Route::has()` (other tasks only `@include` this file)
- [ ] `V/staff/hub.blade.php`, `V/staff/program.blade.php`, `V/staff/enrollments/index.blade.php`, `V/staff/enrollments/create.blade.php`
- [ ] `lang/{ar,en}/special_programs/staff.php`
- [ ] `tests/Feature/SpecialPrograms/EnrollmentTest.php`: enrol, bulk, re-enrol restore, one per (school, program), unnumbered-class alert, school isolation

**Outcome:** US-ADM-1, US-ADM-2. **Dependencies:** T1.x.

### T3.2 Performance groups + program teachers
- [ ] `M/Repositories/EloquentGroupRepository.php` (includes the `sp_teachers` queries)
- [ ] `M/Actions/Enrollment/SaveGroup.php`, `AssignToGroup.php`, `AssignTeacher.php`
- [ ] `M/Http/Requests/GroupRequest.php`
- [ ] `M/Controllers/Staff/GroupController.php`: CRUD, add/move/remove members (enrolled students only, filter by current level via `SettingsRepository::bandsFor` plus the latest attempt %)
- [ ] `M/Controllers/Staff/TeacherController.php`: «معلمو البرنامج» list/assign/remove (teacher picker = school teachers; subject = all or one `sp_subjects` row)
- [ ] `M/Routes/staff-groups.php` (`groups.*`, `teachers.*`; `permission:special_programs.manage`)
- [ ] `V/staff/groups/index.blade.php`, `form.blade.php`, `show.blade.php`, and `V/staff/teachers/index.blade.php`
- [ ] `lang/{ar,en}/special_programs/groups.php`
- [ ] `tests/Feature/SpecialPrograms/GroupsTest.php`: members must be enrolled, one group per student per program, teacher assignment per subject

**Outcome:** US-ADM-3, US-ADM-4. **Dependencies:** T1.x. Uses the `EnrollmentRepository` contract (T3.1) and can run in parallel with it.

### T3.3 Program question bank screens
- [ ] `M/Repositories/Contracts/BankRepository.php`, `M/Repositories/EloquentBankRepository.php`: filtered list (tashkeel-insensitive search), passages list, save, deactivate when used by `sp_attempt_items`, else delete
- [ ] `M/Actions/Bank/SaveProgramQuestion.php`, `SaveProgramPassage.php`, `DeleteProgramQuestion.php`. Write through `CreateQuestion` / `UpdateQuestion` / `MapAnswerData`, plus `sp_questions` and levels. Renumber tahsili «أضف السؤال بعد رقم» inside one transaction.
- [ ] `M/Http/Requests/QuestionRequest.php`, `PassageRequest.php`
- [ ] `M/Controllers/Staff/BankController.php`: SA or `special_programs.questions`; subject limited by `GroupRepository::teacherSubjectIds` when the user is not SA
- [ ] `M/Routes/staff-bank.php` (`bank.*`, `permission:special_programs.questions`)
- [ ] `V/staff/bank/index.blade.php`, `passages.blade.php`, `question-form.blade.php`, `passage-form.blade.php`, `show.blade.php`
- [ ] `lang/{ar,en}/special_programs/bank.php`
- [ ] `tests/Feature/SpecialPrograms/BankAccessTest.php`: QE limited to the verbal subject cannot open or save quant; non-holder gets 403. Access only — the saves need MySQL; verify them manually.

**Outcome:** US-QE-1, US-QE-2. **Dependencies:** T1.x, T2.2 (banks exist locally for the manual checks).

### T3.4 Settings screens
- [ ] `M/Actions/Settings/SaveLevelBands.php`, `SaveProgramSettings.php` (grade levels, weeks count, allow-school-edit)
- [ ] `M/Http/Requests/LevelsRequest.php`
- [ ] `M/Controllers/Staff/SettingsController.php`: per-program tabs; global = SA; school = `special_programs.settings` and `canSchoolEdit`, else read-only
- [ ] `M/Routes/staff-settings.php`. Global pages and the target-grades page carry **route-level `role:super-admin`**.
- [ ] `V/staff/settings/levels.blade.php`, `V/staff/settings/program.blade.php`
- [ ] `lang/{ar,en}/special_programs/settings.php`
- [ ] `tests/Feature/SpecialPrograms/SettingsTest.php`: overlap rejected, school edit blocked unless allowed, viewing writes no row

**Outcome:** US-ADM-6. **Dependencies:** T1.x.

---

## Phase 4 — Exam building

### T4.1 Question pool + pickers
- [ ] `M/Repositories/EloquentQuestionPoolRepository.php`. Level filter via `whereExists` on `sp_question_levels`. `snapshot()` reads `bank_questions` + `question_answers` + `attachment_path` (images: `answer_image`).
- [ ] `M/Actions/Picking/Apportion.php`, `QiyasPicker.php`, `NafisPicker.php`, `TahsiliPicker.php`: D§5 exactly (whole passages, `term IN (x,'both')`, fallbacks, clamped apportion, tahsili buckets, range and shortfall)
- [ ] `M/Actions/Picking/PickerFor.php`: program → picker resolver
- [ ] `tests/Unit/SpecialPrograms/ApportionTest.php`
- [ ] `tests/Feature/SpecialPrograms/PickersTest.php` (sqlite, `sp_questions` + levels rows only): qiyas term/both/weeks fallback/whole passage/level filter on and off; level drop when the target grade holds fewer than N (a grade-10 target with 2 questions still yields N); nafis reading picks N passages and every sub-item; tahsili 30/40/30 with a short range gives fewer items; no duplicate ids

**Outcome:** deterministic, tested selection. **Dependencies:** T1.x.

### T4.2 Exam builder (admin + teacher)
- [ ] `M/Repositories/EloquentExamRepository.php`: staff scope plus audience (D§6)
- [ ] `M/Actions/Exams/CreateProgramExam.php`: validates all of D§8 before inserting, saves `expires_at`, enforces teacher limits (`GroupRepository::teacherSubjectIds`, `groupIdsLedBy`, `teachingClassIds`)
- [ ] `M/Actions/Exams/DeleteProgramExam.php`: soft delete, creator or school-admin
- [ ] `M/Http/Requests/StoreExamRequest.php`
- [ ] `M/Controllers/Staff/ExamController.php`: index (columns D§8), create/store, show (config + monitor via `AttemptRepository::monitor`), destroy, `bank-stats` JSON (`QuestionPoolRepository::stats`), results Excel (`ExportsReports::exportExcel`)
- [ ] `M/Routes/staff-exams.php` (`exams.*`, `permission:special_programs.exams`)
- [ ] `V/staff/exams/index.blade.php`, `create.blade.php` (repeatable subject rows, live «المجموع/المتبقي», level counts, range helper, tahsili weekly-count card), `show.blade.php`
- [ ] `lang/{ar,en}/special_programs/exams.php`
- [ ] `tests/Feature/SpecialPrograms/ExamBuilderTest.php`: share > 100 rejected with no rows written; tahsili ≠ 100 rejected; teacher cannot use a foreign subject, class or group, or more than 100 questions; `expires_at` saved; delete keeps attempts; audience = enrolled ∩ (class ∪ group)

**Outcome:** US-ADM-5, US-TCH-1 (create part). **Dependencies:** T1.x, contracts of T3.1/T3.2/T4.1/T5.1 (it can run in parallel with them).

---

## Phase 5 — Student attempt

### T5.1 Attempt engine
- [ ] `M/Repositories/EloquentAttemptRepository.php`
- [ ] `M/Actions/Attempts/StartSubject.php`: server-side enrolment + audience check (`ExamRepository::isInAudience`, bind a fake in tests), sequential lock and expiry check. `firstOrCreate` sets `deadline_at`. Generation runs once inside a transaction: picker, then `QuestionPoolRepository::snapshot`, then insert items.
- [ ] `M/Actions/Attempts/SaveAnswer.php`: deadline + 5 s grace; returns no correctness
- [ ] `M/Actions/Attempts/LogExit.php`
- [ ] `M/Actions/Attempts/FinishAttempt.php`: a conditional UPDATE, then the event after commit only when 1 row changed; computes `examCompleted`
- [ ] `M/Actions/Attempts/FinalizeOverdue.php`
- [ ] `app/Console/Commands/SpecialProgramsFinalizeOverdue.php` (`special-programs:finalize-overdue`)
- [ ] `tests/Feature/SpecialPrograms/AttemptLifecycleTest.php`. Bind a fake `QuestionPoolRepository` (in-memory snapshot) so no `bank_questions` is needed. Cover: double start gives one item set; the deadline is stable across calls; a late answer is rejected; the answer response has no `is_correct`; finish scores with unanswered counted wrong; overdue becomes `timed_out` through both the read path and the command; anti-cheat auto-end; lock and expiry; `Event::fake` sees exactly one `ProgramAttemptCompleted` with the D§10 payload, including after a second finish call.

**Outcome:** US-STU-2 server side, US-INT-1. **Dependencies:** T1.x, T4.1 (contract).

### T5.2 Student screens
- [ ] `M/Controllers/Student/ProgramController.php`: «البرامج التعليمية» list (`ExamRepository::forStudent`), exam show with subject cards and lock state
- [ ] `M/Controllers/Student/AttemptController.php`: start, take, answer, heartbeat, exit, finish, subject result, final result. Result pages only when closed; the review includes the passage text from `passages`.
- [ ] `M/Routes/student.php` (`auth`, `role:student`)
- [ ] `V/student/index.blade.php`, `exam.blade.php`, `take.blade.php` (one question/passage group per screen, dots, timer synced to the server remaining time, 5/1-minute warnings, autosave on change, `beforeunload`, blur/visibility → exit beacon, finish confirm «تأكيد إنهاء الاختبار», zoomable image), `subject-result.blade.php`, `final-result.blade.php`
- [ ] `public/js/special-programs/player.js` (vanilla JS, no new dependency)
- [ ] `lang/{ar,en}/special_programs/student.php`

**Outcome:** US-STU-1, US-STU-2 UI, US-STU-3 (results). **Dependencies:** T5.1, T4.2 (`forStudent`).

---

## Phase 6 — Reports

### T6.1 Report repository
- [ ] `M/Repositories/EloquentReportRepository.php`: every method from the contract, using SQL aggregates on `sp_attempts` / `sp_attempt_items`. Only closed attempts of non-deleted exams. School/teacher scope applied inside. Levels from `SettingsRepository::bandsFor`. Distribution bands per program (D§9).
- [ ] `tests/Feature/SpecialPrograms/ReportsTest.php`: by-skill credits only the item's own skill (2 skills, 1 attempt); tahsili difficulty rows non-zero; level uses the program bands; date filter; teacher sees only his exams; school isolation; the student report trend is ordered by `ended_at`

**Outcome:** correct numbers for every screen. **Dependencies:** T1.x.

### T6.2 Staff report screens + exports
- [ ] `M/Controllers/Staff/ReportController.php`: filter page, sections, `?section=` Excel export via `ExportsReports::exportExcel`, print/PDF via `exportPdf`, and the staff route for «تقرير أداء الطالب» (student of the active school; teacher only for his students)
- [ ] `M/Routes/staff-reports.php` (`reports.*`, `permission:special_programs.reports`)
- [ ] `V/reports/index.blade.php` (filters + Chart.js level bar + the «مستويات الأداء المعتمدة» strip), `V/reports/sections/*.blade.php` (7 section tables), `V/reports/pdf.blade.php`
- [ ] `lang/{ar,en}/special_programs/reports.php`

**Outcome:** US-REP-1, plus the staff side of US-REP-2. **Dependencies:** T6.1, T6.3 (partial).

### T6.3 Student performance report partial (shared view)
- [ ] `V/partials/student-report.blade.php`: header + level badge, Chart.js trend, per-subject table, per-skill/difficulty table, history with links passed in as a closure or route names
- [ ] `V/partials/student-report-pdf.blade.php` (mPDF-safe, xbriyaz font)
- [ ] `lang/{ar,en}/special_programs/student_report.php` (labels; the shared `special_programs.php` belongs to T1.2)

**Outcome:** one report view rendered for staff, student and parent. **Dependencies:** T6.1 (data shape `studentReport()`).

---

## Phase 7 — Teacher and parent screens

### T7.1 Teacher reports
- [ ] `M/Controllers/Staff/TeacherReportController.php`: «تقاريري» (`teacherExamSummary`), exam report (`examReport`: avg/max/min, top 10, weakest 10, levels, per subject, per-student skills), Excel
- [ ] `M/Routes/staff-teacher.php` (`teacher-reports.*`, `role:teacher`, `permission:special_programs.reports`)
- [ ] `V/staff/teacher/index.blade.php`, `V/staff/teacher/exam.blade.php`
- [ ] `lang/{ar,en}/special_programs/teacher.php`

**Outcome:** US-TCH-1 (results), US-TCH-2. **Dependencies:** T6.1.

### T7.2 Student «تقرير أدائي» + parent report
- [ ] `M/Controllers/Student/ReportController.php` and `M/Routes/student-report.php` (`student.programs.report`, PDF)
- [ ] `M/Controllers/Parent/ProgramController.php` and `M/Routes/parent.php`: child list → program report, with a `children()` guard (403/404 for other children)
- [ ] `V/parent/index.blade.php`, `V/parent/report.blade.php` (both `@include` the T6.3 partial)
- [ ] `lang/{ar,en}/special_programs/parent.php`

**Outcome:** US-STU-3 (report), US-PAR-1. **Dependencies:** T6.1, T6.3.

---

## Phase 8 — (reserved; wiring was done early in T1.3)

If a feature task finds a shared-file change it needs, it appends the request here. The planner then batches the requests into one follow-up wiring commit.

---

## Phase 9 — Verification, rollout

### T9.1 Cross-cutting access test + review
- [ ] `tests/Feature/SpecialPrograms/AccessTest.php`. Students and parents get 403 on every `manage.special-programs.*` GET route (enumerate the routes by name prefix). School A gets 404 on school B's exam, report and enrolment ids. A teacher sees only his own exams. A parent cannot see another child. Super-admin-only settings return 403 for every other role.
- [ ] Run all `--filter=SpecialPrograms` + `RbacSuperAdminRoutesTest` + `CounselingAccessTest` (regression)
- [ ] Code review of the whole diff (Opus inline, or `/code-review`, or `codex:rescue`). Check it against D§13. Make sure no correct answer reaches the browser before close.

### T9.2 Local end-to-end (local-first rule)
- [ ] Local MySQL: run the import (T2.2), then follow the acceptance flow in a browser (Playwright). As school-admin, enrol a grade-10 student in قدرات, create a qiyas exam (verbal + quant) for his class, and check the empty-level warning. As the student, take the exam, reload mid-way (the timer keeps going), let one subject time out. As school-admin, open the report and «تقرير أداء الطالب», then export Excel and PDF. As the parent, view the report.
- [ ] Check the nafis reading (passages) and tahsili (range) flows once each.
- [ ] Remove local test artefacts. `git diff --stat` must show only the feature.

### T9.3 Deploy
- [ ] Commit and push. **Code goes to both** viewclass.newaves-systems.com and rawaniedu.com: `git pull` → `migrate --force` → `view:cache` (+ check `storage:link` and that cron runs `schedule:run`). Batch the SSH commands into one session per server.
- [ ] **Import on rawaniedu.com only**, following `claudedocs/special-programs-import-runbook.md`: slim extract and image rsync, `--dry-run`, real run, verify totals, drop the temp DB and delete the files.
- [ ] Repeat the T9.2 acceptance walkthrough on rawaniedu.com. On viewclass.newaves-systems.com, check the empty states only.
- [ ] Trello #368: move it to `testing prompt`, add an Arabic QA comment (short, with steps and where to check), and assign the tester.

---

## Parallelism map

| wave | tasks (run in parallel) |
|---|---|
| 1 | T1.1, T1.2 (interfaces first), T2.1 |
| 2 | T1.3, T2.2, T4.1, T6.1 |
| 3 | T3.1, T3.2, T3.3, T3.4, T4.2, T5.1, T6.3 |
| 4 | T5.2, T6.2, T7.1, T7.2 |
| 5 | T9.1 → T9.2 → T9.3 |

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Schema, models, wiring | 3 | 0 | Not Started |
| 2. Import | 2 | 0 | Not Started |
| 3. Staff setup screens | 4 | 0 | Not Started |
| 4. Exam building | 2 | 0 | Not Started |
| 5. Student attempt | 2 | 0 | Not Started |
| 6. Reports | 3 | 0 | Not Started |
| 7. Teacher & parent | 2 | 0 | Not Started |
| 9. Verification & rollout | 3 | 0 | Not Started |
| **Total** | **21** | **0** | **0%** |
