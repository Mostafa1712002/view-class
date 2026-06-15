# Tasks: Question-Bank Rebuild — Foundation

## Phase 1: Foundation (this task — DONE)

### Task 1.1: Audit existing QB schema vs #258
- [x] Map existing tables/columns vs #258 proposed schema (design.md decision table)

### Task 1.2: Additive schema migrations (#258)
- [x] compounds + compound_school
- [x] standards
- [x] skills + skill_assignments
- [x] extend question_banks (subject_id, semester_id, bank_type, requires_approval, allow_* flags)
- [x] question_bank_assignments
- [x] extend bank_questions (question_category, subject/grade/class/semester/passage ids, archived_at)
- [x] question_answers (normalized, alongside JSON)
- [x] passages + passage_questions
- [x] extend question_import_batches / question_import_errors (#258 cols)
- [x] migrate ran clean

### Task 1.3: Taxonomy layer (#248)
- [x] Compound / Skill / SkillAssignment / Standard models
- [x] Passage / PassageQuestion / QuestionAnswer / QuestionBankAssignment models
- [x] Compound / Skill / Standard repositories (contract + impl) bound in RepositoryServiceProvider

### Task 1.4: Permissions (#248)
- [x] Seed compounds/skills/weeks/standards permission keys (idempotent migration)
- [x] Add the 4 groups to JobTitlePermissionsController::MODULES (matrix labels)

### Task 1.5: Non-regression verification
- [x] /admin/question-banks (index/create) loads
- [x] /admin/question-banks/{id}/questions/create loads
- [x] /admin/users/job-titles/{id}/permissions matrix renders with new groups
- [x] no laravel.log errors, php -l clean

---

## Deferred to later QB cards (NOT built here)
- #250 normal questions screen, #251 تحصيلي, #252 قطعة, #253 list, #254 excel,
  #255 exams link (incl. #258 electronic/paper exams + question_snapshot), #256 states
- Controllers / Actions / DTOs / Http Requests / views for the above
- Cutover of bank_questions.answer_data JSON → question_answers
- CRUD UI + routes for compounds / skills / weeks / standards / passages
- Multiple-bank-create, dup-prevention, Excel skill import logic

## Progress Tracking
| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Foundation | 5 | 5 | 100% |
