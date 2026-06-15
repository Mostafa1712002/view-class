# Design: Question-Bank Rebuild — Foundation (#258, #247, #248)

Foundation only: schema + taxonomy + module structure + permissions. No question-type
screens (#250–#256). Additive & non-destructive: the live `admin/question-banks`
feature must keep working.

## Extend vs Create decision (per #258 table)

| #258 table | Decision | Why |
|---|---|---|
| `question_banks` | **EXTEND** (nullable/defaulted cols) | live table; add `subject_id`, `semester_id`(→academic_terms), `bank_type`, `requires_approval`, `allow_excel_import`, `allow_images`, `allow_passage_questions`, `allow_tahsili_questions`. Keep `question_bank_subjects` pivot (many-subjects legacy) — `subject_id` is the new single-subject primary. |
| `question_bank_assignments` | **CREATE** (new) | school/grade/class/teacher targeting per bank. |
| `questions` | **MAP → extend `bank_questions`** | `bank_questions` is the live store and a near-match (week/skill/standard/domain/code/content_type/status already present). Add `subject_id`, `grade_id`, `class_id`, `semester_id`, `question_category`. Avoids a parallel table to keep in sync. |
| `question_answers` | **CREATE** (new, alongside JSON) | normalized answers table; legacy `bank_questions.answer_data` JSON stays untouched. No data migration now (later card). |
| `passages` | **CREATE** | passage (قطعة) support. |
| `passage_questions` | **CREATE** | passage↔question link. |
| `skills` | **CREATE** | educational skill taxonomy (#248). |
| `skill_assignments` | **CREATE** | skill → compound/school/grade/class. |
| `academic_weeks` | **MAP → reuse `study_weeks`** | `study_weeks` already exists (term-scoped, start/end, sort). No second weeks table. `week_id` everywhere → `study_weeks.id`. |
| `question_import_batches` | **EXTEND** | live table; add #258 cols (`import_type`, `images_zip_path`, `valid_rows`, `invalid_rows`, `imported_by`, `started_at`, `finished_at`, `error_report_path`, `settings`). |
| `question_import_errors` | **EXTEND** | add `question_code`, `error_field`, `error_message`, `error_type`. |
| `exams` / `exam_questions` | **LEAVE LEGACY ALONE** | existing `exams` is the teacher class-exam feature (quiz/midterm). #258's electronic/paper exams + `question_snapshot` are coupled to the deferred exams-link card (#255). Not built here. |

### Taxonomy FK reuse map (#248 → existing)
- `semester_id` / الفصل الدراسي → **`academic_terms`** (school-scoped via academic_years). No new `semesters` table.
- `week_id` → **`study_weeks`**.
- `domain_id` → **`domains`** (subject-scoped, exists).
- `standard_id` → **CREATE `standards`** (none exists).
- compound / مجمع → **CREATE `compounds`** (judgment call: `educational_companies` is a
  billing/company concept and `school_branches` is unrelated; a dedicated educational
  grouping is cleaner and additive. A later card may reconcile compound ↔ educational_company.)
- `grade_id` / الصف → nullable unsigned, **NO hard FK** (the `grades` table is the
  gradebook, not grade-levels; grade-level lives as `classes.grade_level` int). Stored
  loosely so screen cards can resolve against the real grade-level source.

## New tables created
compounds, compound_school (pivot), standards, skills, skill_assignments,
passages, passage_questions, question_answers, question_bank_assignments.

## Module
`app/Modules/QuestionBankCore/` — Models + Repositories (contract+impl, bound in
RepositoryServiceProvider) for the new taxonomy (Compound, Skill, Standard) and the
new question structures. Actions/DTOs/Controllers/screens belong to #250–#256 (deferred);
no empty stubs created (no-stub rule).

## Permissions
- `question_banks`, `exams`, `subjects` groups already exist — untouched.
- New matrix groups added to `JobTitlePermissionsController::MODULES` + seeded:
  `compounds`, `skills`, `weeks`, `standards`.
