# Slice 2 — Weekly Plan + Grade Management + Reports

## Overall Strategy

Build on existing Sprint 4 schema. Three vertical slices, each delivers a working
index page wired into the sidebar so QA can navigate to it; advanced sub-features
(Excel import, drag-drop column editor, complex statistical aggregation) are
documented as deferred.

## A. الخطة الأسبوعية (Weekly Plan) — `app/Modules/WeeklyPlan/`

### Data model
New table `lesson_preparations`:
- `id`, `school_id`, `schedule_entry_id` FK, `week_start` DATE (Sunday)
- `subject_lesson_id` FK nullable (the lesson covered)
- `objectives` text, `homework` text, `notes` text
- `attachments` JSON, `status` enum('not_prepared','prepared')
- `prepared_at` timestamp nullable, `prepared_by` FK users
- soft delete + timestamps
- unique (school_id, schedule_entry_id, week_start)

### Behaviour
- Grid: rows = days (Sun–Thu), columns = time slots, cells = subject + lesson + status icon (yellow ⚪ / green ✅).
- Filters: grade_level, class_id, teacher_id (via schedule_entries → class_periods).
- Week navigator: prev/next/today.
- PDF export (DomPDF, A4 landscape, RTL — same approach as Sprint 4 school-schedule PDF).
- Edit lesson preparation (open lesson → fill objectives/homework/notes → status flips to "prepared").

### Deferred to next round
- Excel export (button stub)
- Customize columns (button stub)
- Ready-notes templates (button stub)
- Advanced search (button stub)
- Custom school header for PDF (uses default header for now)

## B. إدارة الدرجات (Grade Management) — `app/Modules/GradeReports/`

### Data model
New tables:
- `grade_reports`: id, school_id, academic_year_id, term_id, type enum('dynamic','static','gradesheet'),
  title, settings JSON (date ranges, visibility flags), created_by, soft delete.
- `grade_report_columns`: id, grade_report_id FK, title, type enum('numeric','calculated','calculated_horizontal'),
  weight decimal, max_score decimal, formula text nullable, sort_order, is_in_total bool, is_visible bool.
- `grade_report_static_files`: id, grade_report_id FK, student_id FK, file_path, mime, size — for static report uploads.
- `grade_report_ratings`: id, grade_report_id FK, label, min_score, max_score, sort_order — for letter-grade ratings.

(grades table already exists per-student-per-subject; that holds the actual numbers. The new tables only describe the report.)

### Behaviour
- Index: list reports with type pill + last-edited + class.
- Create-Dynamic: 4-step wizard
  - Step 1: type selection (dynamic only working this round; static + gradesheet stubs)
  - Step 2: date settings + visibility checkboxes
  - Step 3: header/footer (text fields only this round; full WYSIWYG deferred)
  - Step 4: column editor (numeric columns only working; calculated stub)
- Show: read-only summary
- Action menu: subjects filter / export Excel (stub) / hide student (stub) / PDF settings (stub) / ratings

### Deferred
- Calculated and calculated-horizontal column types (numeric only this round)
- Static + Gradesheet report types (UI stubs, no save)
- WYSIWYG header editor (plain textarea this round)
- PDF generation (defer with stub button)
- Student ranking & comprehensive review pages (stub)

## C. التقارير (Reports) — `app/Modules/Reports/`

### Data model
None — purely aggregations over existing tables (schools, users, classes, attendances, exams, subjects).

### Behaviour
- Index page with 3 horizontal nav tabs:
  - **Administrative**: schools general (counts), absence by class, absence by period, subject absences, subject general
  - **Statistical**: assignments / teachers / virtual classes / discussion rooms / lesson prep / questions / interactive content (stubs this round, except teachers report)
  - **User**: students / teachers / parents tabs (filter form + list this round)
- Common filter bar: schools (multi-select), date_from, date_to.
- Export buttons: Excel/PDF stubs.

### Deferred
- All statistical reports except headcount aggregation (the more involved
  stuff like "lesson preparation rate" requires events tables we haven't built).
- Per-teacher drilldown with hide-content control (stub link).
- Email/SMS to teachers from report (out of scope).

## Acceptance Criteria — Slice 2

- WHEN admin visits `/manage/weekly-plans` THE SYSTEM SHALL display a week grid scoped by school.
- WHEN admin visits `/admin/grades` THE SYSTEM SHALL list grade reports with create button.
- WHEN admin visits `/admin/reports` THE SYSTEM SHALL show three category tabs (admin/statistical/user).
- WHEN admin selects "Schools General" report THE SYSTEM SHALL show row per school with student/teacher/class counts.
- THE SYSTEM SHALL preserve all Sprint 1-4 functionality.
- THE SYSTEM SHALL persist new modules' data in school-scoped queries.
