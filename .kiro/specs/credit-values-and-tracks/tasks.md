# Tasks: Credit Values & Subject Tracks (Trello 6a01ce3cd7ad703b9ca30fd5)

## Phase 1: Schema

### Task 1.1: Migration `61_` add subjects.credit_hours_active + create subject_tracks
- [x] Add `credit_hours_active` boolean (default true) to subjects
- [x] Create `subject_tracks` table (id, school_id FK, name, name_en, sort_order, notes, is_active, timestamps, softDeletes)

### Task 1.2: Models
- [x] Extend Subject `$fillable` + `$casts` for `credit_hours_active`
- [x] Create `SubjectTrack` model

## Phase 2: Credit Values page

### Task 2.1: Controller methods
- [x] Replace `creditHours` controller — accept `?grade_level=N`, return subjects in that grade with computed value
- [x] Update `saveCreditHours` to also save `credit_hours_active[]`

### Task 2.2: Repository
- [x] Add `gradeSubjects(schoolId, level)` + `bulkSetCreditValues(schoolId, hoursMap, activeMap)`

### Task 2.3: Blade
- [x] Replace `credit-hours.blade.php` with grade-picker + table (subject | weekly | computed | toggle)
- [x] Show "غير محدد" for null weekly_lessons
- [x] Light theme + RTL responsive

## Phase 3: Subject Tracks CRUD

### Task 3.1: Controller `SubjectTrackController` (in Subjects module)
- [x] index (with search), create, store, edit, update, destroy

### Task 3.2: Blades
- [x] index, create, edit forms (light theme)

### Task 3.3: Routes
- [x] `/admin/subjects/tracks` resource

### Task 3.4: Lang
- [x] `lang/{ar,en}/subject_tracks.php`

### Task 3.5: Sidebar
- [x] Add one entry "شعب المواد" with `// === Sections card 61 ===` marker

## Phase 4: Deploy & verify

### Task 4.1
- [x] Commit + push
- [x] SSH deploy
- [x] Playwright verify

## Progress

| Phase | Status |
|-------|--------|
| 1 schema | done |
| 2 credit values | done |
| 3 tracks CRUD | done |
| 4 deploy | done |
