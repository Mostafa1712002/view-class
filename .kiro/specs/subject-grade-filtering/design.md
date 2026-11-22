# Design

Link = `subjects.grade_levels` JSON (1–12, strings or ints) — set by the checkboxes on the subject add/edit form. No pivot, no migration.

Grade of a class = `classes.grade_level` (same value the student side already matches against).

## UI cascade (one shared script)
`resources/views/partials/subject-grade-filter.blade.php`, included once in `layouts/app`.
- subject `<option>`/checkbox: `data-grades="1,2"`
- dependent `<select data-grade-source="subject_id">`; options use `data-grade` (classes) or their value (1–12 selects)
- rebuilds the option list (works with select2 / iOS), resets an invalid choice on change, keeps it on load

Pages: online exams create/edit/index, files create/edit/index, assignments create/edit/index, weekly plans create/edit, question banks create/edit/index/batch (checkbox union), books create/edit/index.
Not covered: lessons form/index (class is picked before subject there), qb/exams/create (QuestionBankCore exams, not wired to students).

## Online exams
`Admin\ExamController::formOptions($schoolId)` + `schoolRules($schoolId)` (Rule::exists scoped) used by create/store/edit/update; index list, stats and filter dropdowns scoped by `scopedSchoolId()`. Edit by a super-admin keeps the exam's own school.
