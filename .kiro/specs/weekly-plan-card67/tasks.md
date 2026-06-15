# Tasks: Weekly Plan — Card 67 edits

## Done (slice shipped)
- [x] Add lesson_title + exams columns (migration wp_add_lesson_and_exams_to_weekly_plans)
- [x] Add lesson_title/exams to model fillable, create/edit/show forms
- [x] Free-text content search (q) across lesson/topics/objectives/homework/exams/notes + teacher/subject names
- [x] Date filter (pins week containing the date)
- [x] Advanced-search collapse + auto-search toggle (localStorage)
- [x] Excel export (.xlsx via PhpSpreadsheet) honoring filters
- [x] Column customization dropdown (show/hide, localStorage-persisted)
- [x] lesson + exams columns in grid + PDF export
- [x] Local e2e verify + live deploy + live verify

## QA fixes (2026-06-09)
- [x] PDF Arabic/RTL: replace dompdf with mPDF (same pattern as user-cards #162) — Arabic glyph shaping + RTL bidi now correct
- [x] PDF redesign: gold header bar, week-range strip, status-coloured cells; landscape A4; mPDF-compatible CSS (tables only)
- [x] Column-customize button: decouple toggle from table existence (was dead on empty weeks due to `if (table && ...)` guard)
- [x] Column-customize dropdown: fix clipping by `.app-content overflow:hidden` — switched to `position:fixed` + JS coordinates

## Deferred (noted on Trello card)
- [ ] Per-day / per-period / time fields via weekly_plan_items child table (true days×periods grid)
- [ ] Custom print header from school settings
