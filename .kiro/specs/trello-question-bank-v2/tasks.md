# Tasks: Question Bank v2 (Trello #213–#217)

Additive enhancement to the existing QuestionBanks module (banks=question_banks,
questions=bank_questions). Keep general/private banks, permissions, and existing
links to exams/assignments/subjects/grades intact. Deployable slices.

## Phase 1 — Schema foundation (#215)  [data structure]
- [x] Additive migration on bank_questions: question_code, question_content_type
  (text|image|mixed), is_full_image_question, unit_id, week_id, skill_id,
  standard_id, domain_id, source, explanation, reviewed_by, reviewed_at,
  rejected_reason, imported_by, import_batch_id, external_platform, external_id,
  sync_status, last_synced_at, metadata (json). (lesson_id, difficulty, points,
  status, type, body_*, answer_data, attachment_path already exist.)
  - Migration: 2026_06_12_210001_card215_add_columns_to_bank_questions_table.php ✅
- [x] question_import_batches table (bank_id, school_id, file, total, imported,
  failed, status, created_by, timestamps) + question_import_errors (batch_id,
  row_number, errors json, raw json).
  - Migration: 2026_06_12_210002_card215_create_question_import_tables.php ✅
  - Models: app/Models/QuestionImportBatch.php + QuestionImportError.php ✅
- [x] BankQuestion model fillable/casts for new fields + reviewer()/importer()/importBatch() relations.
**Outcome:** schema supports manual add + Excel import + images + codes + standards + batches. ✅ DONE 2026-06-12

## Phase 2 — Excel import + official template (#214)
- [ ] Downloadable template (Questions / Instructions / Allowed Values sheets).
- [ ] Import: upload → parse → validate → preview → execute → result + error CSV
  (mirror App\Modules\StudentImport pattern). Buttons gated by import permission.

## Phase 3 — List/search/filters + question display (#216)
- [ ] Bank header (name/type/source/subject/grade/semester/count/status/link).
- [ ] Questions table: code search, text/image/mixed display, full filter set, preview.

## Phase 4 — Permissions + states + linking (#217)
- [ ] Bank-level + question-level permission catalog; question states; exam/assignment links verified.

## Phase 5 — Manual add all types + image/code (#213 remainder)
- [ ] Manual add form supports all types + image head/answers + full-image+code.

## Progress
| Phase | Card | Status |
|-------|------|--------|
| 1 Schema | #215 | ✅ Done → testing |
| 2 Import | #214 | Not started |
| 3 UI | #216 | Not started |
| 4 Perms/states | #217 | Not started |
| 5 Manual add | #213 | Not started |
