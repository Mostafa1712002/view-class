# Tasks: QB rebuild — Excel import (#254) + Exams link (#255)

Additive, layered over `app/Modules/QuestionBankCore`. Legacy `/admin/question-banks`,
`admin/qb` core+passages, AND the legacy `/admin/exams` feature stay intact.

## Card #254 — Excel import (admin/qb)

### Phase 1: Foundation
- [x] Audit legacy `QuestionImportController` / `GenerateQuestionImportTemplate` /
      `ImportQuestionsAction` / `ParseQuestionsExcel` (reuse patterns, PhpSpreadsheet 5.x)
- [x] Confirm `question_import_batches` / `question_import_errors` already extended
      (import_type, valid/invalid_rows, settings, error_report_path, etc.)
- [x] Extend `QuestionImportBatch` / `QuestionImportError` $fillable + $casts for new cols

### Phase 2: Actions
- [x] `GenerateImportTemplate` — Questions sheet (full #254 columns) + Instructions + Allowed Values
- [x] `ParseQuestionsExcel` — parse Questions sheet → normalized rows w/ status valid|invalid|duplicate
- [x] Validation matrix (~18 checks per card): missing cols, unknown subject/grade/semester/skill,
      bad difficulty, unsupported type, no text+no image, full-image w/o code, dup code in file,
      dup code in bank, missing correct answer, mcq w/o options, true_false bad correct, image-ext
- [x] `ImportFromPreview` — valid rows → `CreateQuestion` action (writes bank_questions +
      question_answers + answer_data) + batch counters + per-row errors

### Phase 3: Controller + routes + views
- [x] `ImportController` (canDo question_banks.import) — form, template, preview, confirm,
      errorReport, history. scopedSchoolId fail-closed.
- [x] Routes under admin/qb (writes role middleware) in QuestionBankCore Routes/web.php
- [x] Views: import/index (upload), import/preview, import/result + button on questions index

### Phase 4: Verify
- [x] template downloads; valid+invalid xlsx → preview flags; confirm imports valid;
      assert bank_questions + question_answers rows; error report for invalid
- [x] 200 admin + super-admin(null); 403 no-permission

## Card #255 — Exams link (electronic + paper, snapshot)

### Phase 1: Schema (new module-owned, NON-colliding)
- [x] Confirm legacy `exams` single NOT NULL FKs + type enum → build NEW tables
- [x] migration: `qb_exams`, `qb_exam_targets` (schools/grades), `qb_exam_questions` (snapshot JSON)
- [x] Models in module

### Phase 2: Controller + picker
- [x] `ExamController` (qb) — index w/ info box + filters + electronic/paper buttons, create,
      store, show, destroy, publish/unpublish; results (basic)
- [x] Bank-question picker (approved-only, scoped) → snapshot copy into qb_exam_questions
- [x] Routes admin/qb/exams (exams.* perms + question_banks.view for picker)

### Phase 3: Verify
- [x] create electronic + paper; pick approved bank questions; assert persistence + snapshot
- [x] legacy /admin/exams + bankPicker/addFromBank untouched & working

## Progress

| Phase | Status |
|-------|--------|
| #254 import | Done |
| #255 exams  | Done |
