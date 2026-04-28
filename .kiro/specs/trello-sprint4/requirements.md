# Requirements: Sprint 4 — Educational Operations

## Overview

Sprint 4 builds the educational scaffolding on top of the user system shipped in Sprint 3. Four modules:

1. **Subjects** (المواد) — academic subjects per grade with lesson trees and credit hours
2. **Question Bank** (بنك الأسئلة) — shared question pools per subject/teacher
3. **Classes/Periods** (الحصص) — teacher↔class↔subject↔period scheduling
4. **School Schedule** (الجدول المدرسي) — read-only weekly grid with PDF print

Source: 4 Trello cards in `sprint prompt` on board `WBHlx52A`:
- `69f065422a1047b53812324e` — sprint 4 (meta + Subjects part 1)
- `69f06564dd8e8ec7c359cc55` — Question Bank
- `69f06567cf8f5c165c8f3b24` — Classes/Periods
- `69f0656b07b148fd33093007` — School Schedule (view-only)

## User Stories

### US-401: Subjects CRUD
**As a** school admin
**I want to** manage academic subjects per grade
**So that** the rest of the educational system has subjects to attach to

**Acceptance Criteria:**
- WHEN admin opens `/admin/subjects` THE SYSTEM SHALL list all subjects scoped to the active school
- WHEN admin clicks "Add Subject ▼" THE SYSTEM SHALL show 3 options: manual, Excel import, ViewClass templates
- WHEN admin saves a manual subject THE SYSTEM SHALL persist `(name_ar, name_en, grade_id, section, certificate_order, source='manual')`
- WHEN admin clicks "Set Approved Values" THE SYSTEM SHALL show a screen to bulk-set credit hours per subject
- WHEN admin deletes a subject with linked classes THE SYSTEM SHALL block the delete and show a clear error
- WHEN admin opens the per-row "Lesson Tree" THE SYSTEM SHALL show units → lessons hierarchy with CRUD

### US-402: Question Bank
**As a** teacher
**I want to** pool questions in shared banks per subject
**So that** colleagues can reuse questions when building exams

**Acceptance Criteria:**
- WHEN admin opens `/admin/question-banks` THE SYSTEM SHALL list banks scoped to school + viewer permission
- WHEN admin creates a bank THE SYSTEM SHALL collect `(name_ar, name_en, subject_ids[], schools[], editor_teacher_ids[], viewer_teacher_ids[])`
- WHEN admin clicks "Bank Library" THE SYSTEM SHALL show platform-provided pre-built banks available for cloning
- WHEN a teacher views a bank THE SYSTEM SHALL show questions only if they're in the bank's viewer or editor list
- WHEN admin deletes a bank with attached questions THE SYSTEM SHALL prompt for confirmation

### US-403: Classes/Periods (Schedule Builder)
**As a** school admin
**I want to** build the weekly class schedule
**So that** teachers, students, and timetables stay in sync

**Acceptance Criteria:**
- WHEN admin opens `/admin/class-periods` THE SYSTEM SHALL list classes (teacher × grade × section × subject)
- WHEN admin creates a class manually THE SYSTEM SHALL require `(teacher, grade, section, subject)` and validate teacher has the subject
- WHEN admin opens "Manage Time Slots" THE SYSTEM SHALL allow defining day-of-week × period number × start/end time
- WHEN admin uses "Advanced Schedule" drag-drop THE SYSTEM SHALL prevent placing two classes for the same teacher in the same time slot (conflict block)
- WHEN admin clicks "Substitute Teacher" THE SYSTEM SHALL allow assigning a fallback teacher per class
- WHEN admin imports a TimeTable file THE SYSTEM SHALL guide them through period-mapping → teacher-mapping → subject-mapping
- WHEN admin views "Teacher Workloads" THE SYSTEM SHALL show counts per teacher (already built in Sprint 3, link to it)

### US-404: School Schedule (read-only)
**As a** school admin or viewer
**I want to** see the full weekly schedule with filters
**So that** I can monitor coverage without editing

**Acceptance Criteria:**
- WHEN user opens `/admin/school-schedule` THE SYSTEM SHALL render a Sun–Thu × period grid
- WHEN user filters by grade/section/teacher/subject THE SYSTEM SHALL apply server-side filtering
- WHEN user clicks "Print PDF" THE SYSTEM SHALL stream a paginated A4 landscape PDF of the filtered grid

### US-405: Sprint 1+2+3 regression sanity
**As a** PM
**I want to** confirm shipped Sprints 1+2+3 still work after Sprint 4 changes
**So that** Sprint 4 doesn't regress the foundation

**Acceptance Criteria:**
- WHEN Sprint 4 deploys THE SYSTEM SHALL still render Sprint 1+2+3 pages without 500
- WHEN a tester logs in as `developer@midade.com` THE SYSTEM SHALL load: dashboard, schools list, settings, school years, classes, all 4 user pages, user cards
- WHEN a User Card is generated THE SYSTEM SHALL still produce a valid PDF

## Non-Functional Requirements

### NFR-401: Multi-tenant scope
- Every read query MUST filter by the authenticated user's `school_id` (or be globally unscoped only for ViewClass-platform-owned content like the bank library)

### NFR-402: Soft delete
- All new tables (subjects, units, lessons, question_banks, class_periods, time_slots) use soft deletes so accidental deletion is reversible

### NFR-403: i18n
- Every page label, button, validation message, and table header lives in `lang/ar/sprint4.php` + `lang/en/sprint4.php`

### NFR-404: Module pattern
- Code lives under `app/Modules/Subjects/`, `app/Modules/QuestionBanks/`, `app/Modules/ClassPeriods/`, `app/Modules/SchoolSchedule/` with the project's standard Action + Repository + Controller layout per CLAUDE.md

### NFR-405: API envelope
- All `/api/sprint4/*` endpoints return the standard `{success, data, message}` / `{success, error}` envelope via `App\Support\ApiResponse`
