# Design: Student Account Shell (#170 "هيكل حساب الطالب")

Status captured 2026-06-15.

## What already exists (do NOT rebuild)
- Shared `<x-navbar>` (resources/views/components/navbar.blade.php) — avatar, mail icon,
  notifications bell + **real unread counter** (`customNotifications()->unread()->count()`,
  user-scoped), global-search icon, language switcher, role dropdown, scope form
  (company/school/year via POST `/scope` → `session('scope')`).
- Shared `<x-sidebar>` (resources/views/components/sidebar.blade.php) — role-branched; a
  `بوابة الطالب` section already lists schedule/exams/grades/attendance/books/subjects/
  reports/portfolio (hardcoded items).
- Student pages: dashboard, schedule, grades, exams, attendance, weekly-plans, books,
  subjects (index+show), reports (absence + exam-schedule), portfolio, special-education —
  all under `student.*` routes (middleware `role:student`).
- Subject resolution: `StudentSubjectController::studentSubjects($student)` — Subjects where
  `school_id = student.school_id`, `is_active = 1`, `grade_levels` JSON contains the
  student's `classRoom.grade_level`. Ordered by certificate_order, name.
- Scope: `SetScopeAction` writes `session('scope')` = {company_id, school_id, academic_year_id}.
  `ScopeRepository` (companiesFor/schoolsFor/yearsFor). NO term concept.
- `AcademicTerm` model + `academic_terms` table (academic_year_id, name, is_current,
  sort_order). `Setting::get/set` (school-scoped, cached).

## Data reality — what term/year scoping is actually possible (verified via DB)
`academic_term_id` column exists ONLY on: `study_weeks`, `grade_reports`, `books`.
It does NOT exist on: exams, grades, weekly_plans, schedules, attendances, assignments.
Those tables carry `academic_year_id`.

Therefore:
- **Year scoping is real** for subjects-context, schedule, weekly-plans, grades, exams,
  attendance — all filter by `academic_year_id`.
- **Term scoping is mostly a no-op at the data level.** Only books and grade_reports are
  term-stamped. The term selector is surfaced and stored in scope, books are term-filtered,
  but exams/grades/attendance/schedule cannot be term-scoped because the rows are not
  term-stamped. This is reported honestly, not faked.
- Subjects resolve from `classRoom.grade_level`, not from year — so switching year does not
  change the subject list unless the student's class enrollment differs by year (it does not
  in current data). Documented as such.

## The gaps to build
1. **Setting `allow_previous_periods`** (boolean, school-scoped, default `false`). Controls
   whether a student may switch to a non-current year/term. Enforced server-side in
   `SetScopeAction` for students (UI gate is secondary; a direct POST is rejected too).
2. **Term in scope**: add `academic_term_id` to session/SetScopeAction; add `termsFor()` to
   ScopeRepository. No admin regression (nothing read term from scope before).
3. **Student navbar branch**: account-type label "طالب"; company/school shown as static
   context (not a switcher); year + term selectors that are *disabled* when
   `allow_previous_periods` is off (current period only).
4. **Sidebar student groups**: a dynamic **"مواد الطالب"** group (subject names from
   `studentSubjects()`, deduped) + named groups عمليات تعليمية / تقارير / مكتبات / تواصل /
   الدعم. Gate items by `Route::has()` + role. Libraries (#173) and communication (Sprint 9)
   link to placeholder/coming-soon where no route exists — noted.
5. **Effective period resolver** (`ResolvesStudentScope` trait): one place that returns the
   effective AcademicYear (+ term) from `session('scope')` falling back to is_current, and
   gated so a student locked out of previous periods always gets the current one. Student
   controllers route through it instead of repeating `is_current ?? request` logic.

## Dedup rule for subjects
`studentSubjects()` already returns distinct rows. For the sidebar group, collapse rows that
are exact duplicates by (name + track) — keep genuinely distinct subjects (different code/
track) as separate links. Implemented with a `unique()` on a name+track key.

## Files
- NEW  app/Modules/Users/Controllers/Concerns/ResolvesStudentScope.php (trait)
- EDIT app/Modules/Scope/Actions/SetScopeAction.php (term + previous-period gate)
- EDIT app/Modules/Scope/Repositories/Contracts/ScopeRepository.php (+termsFor, +termExistsFor)
- EDIT app/Modules/Scope/Repositories/EloquentScopeRepository.php (impl)
- EDIT resources/views/components/navbar.blade.php (student branch: type label, term select, gating)
- EDIT resources/views/components/sidebar.blade.php (student groups + dynamic subjects)
- EDIT app/Http/Controllers/StudentController.php (route schedule/weekly-plans/grades/exams/attendance through scope)
- EDIT lang/ar/shell.php + lang/en/shell.php (new keys)
- NEW migration: settings default seed not required (Setting::get default handles absence)
