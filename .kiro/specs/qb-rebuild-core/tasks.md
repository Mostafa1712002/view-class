# Tasks: Question-Bank Rebuild — Core Screens (#249, #250, #253)

New, additive screens under `app/Modules/QuestionBankCore/` + `resources/views/admin/qb/`,
new URL prefix `admin/qb`, new route names `admin.qb.*`. The legacy `admin/question-banks`
feature is untouched. Reuses the foundation (models/repos) and the existing
`BankQuestion::TYPES` enum + answer mapping (no new question types, no schema migrations
beyond an optional permission seed).

## Phase 1: Foundation wiring

### Task 1.1: Scope/taxonomy helpers + Question repository
- [x] `Repositories/Contracts/QuestionRepository` + `EloquentQuestionRepository` (bound)
- [x] `Services/QbScopeService` (school → compounds → schools → grade-levels → classes; terms → weeks)
- [x] Reuse `extractAnswerData()` mapping, lifted into `Actions/MapAnswerData`

## Phase 2: #249 scope selector
- [x] `ScopeSelectorController@index` (GET admin/qb/scope) — picks school, returns cascade JSON
- [x] AJAX endpoints: grades, classes, semesters, weeks (scoped)
- [x] Blade: compound→school grouping, grade/class/semester pickers, gender filter, search
- [x] Emits compound_id, school_id, school_type, grade_id, class_id, semester_id

## Phase 3: #250 add normal question
- [x] `QuestionController@create/store/edit/update`
- [x] `Actions/CreateQuestion`, `Actions/UpdateQuestion`
- [x] Writes bank_questions + question_answers (normalized) + answer_data (legacy sync)
- [x] Validation (full-image→code required, min-2 options + correct marked) + activity log
- [x] Types: mcq, true_false, essay, short, fill_blank, matching

## Phase 4: #253 list + filters + actions
- [x] `QuestionController@index` — filters subject/grade/type/difficulty/status/category/search/code/skill
- [x] Columns + row actions (view/edit/delete/archive/duplicate) gated by canDo
- [x] used-in-exam → archive guard (copied from legacy destroy)
- [x] classes modal + answer modal + pagination + empty state

## Phase 5: Permissions + verification
- [x] add `question_banks.archive` key (idempotent seed + MODULES)
- [x] Playwright: 200 admin / 200 super-admin(null school) / 403 out-of-role
- [x] create question → persists bank_questions + question_answers; list/filter/edit/archive
- [x] legacy /admin/question-banks still 200; route:list no name clash

## Deferred (reported, not built)
- per-option/per-side IMAGE answers, rubric, "preview before save", "add another"
- move-to-another-bank, approve/reject workflow, edit-history (سجل التعديلات)
- tahsili/passage question screens (#251/#252), Excel import wiring (#254)
- reusable selector as a true shared Blade component across all 8 host screens

## Progress Tracking
| Phase | Status |
|-------|--------|
| 1–5 | 100% |
