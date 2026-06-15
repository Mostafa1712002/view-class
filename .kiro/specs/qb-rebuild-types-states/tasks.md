# Tasks: QB Rebuild — Tahsili + Passage + States/Review/Permissions (#251, #252, #256)

Additive screens/logic under `app/Modules/QuestionBankCore/` + `resources/views/admin/qb/`,
reusing the existing `admin/qb` core (QuestionController, _form, QbScopeService, MapAnswerData).
No new question types. Schema is already present (question_category enum has tahsili/passage,
passages + passage_questions exist, bank_questions has reviewed_by/reviewed_at/rejected_reason).
Only migration = approve/reject permission seed. Legacy admin/question-banks untouched.

## Phase 1: #256 states, review workflow, permissions

### Task 1.1: permission keys
- [x] migration: seed `question_banks.approve` + `question_banks.reject` (idempotent)
- [x] add `approve`,`reject` to JobTitlePermissionsController::MODULES['question_banks'].actions

### Task 1.2: status clamping (close self-approve hole)
- [x] StoreQuestionRequest / controller: a user lacking .approve/.reject cannot set approved/rejected
- [x] requires_approval bank → new question defaults to pending_review/draft, not approved

### Task 1.3: review transition actions
- [x] submit (→pending_review), approve (→approved), reject (→rejected + reason) — gated by canDo
- [x] reject requires rejected_reason; persists reviewed_by/reviewed_at; activity log each
- [x] index: status badges + transition buttons gated per permission; archive filter

## Phase 2: #251 tahsili

### Task 2.1: tahsili create/list integration
- [x] _form: category dropdown gains tahsili; reveal standard/skill section; fix helper text
- [x] create?category=tahsili entry link from index toolbar
- [x] CreateQuestion/UpdateQuestion already category-aware; index category filter already works

## Phase 3: #252 passage

### Task 3.1: passage model + repo
- [x] PassageRepository contract + Eloquent impl (bound), school-scoped via bank
### Task 3.2: passage CRUD + children
- [x] PassageController index/create/store/show/edit/update/destroy + addQuestion/removeQuestion
- [x] child question reuses _form bound to passage_id; writes pivot + passage_id + category in one txn
- [x] views: index (list w/ question summary), create/edit, show (passage + children accordion)
- [x] routes admin/qb/passages/* (reads in read group, writes in write group), canDo gated

## Phase 4: verification
- [x] Playwright: each screen 200 admin + 200 super-admin(null) + 403 no-perm
- [x] tahsili creates + lists; passage creates + child attached (pivot rows)
- [x] state transitions persist + permission-gated (no-approve user can't approve)
- [x] legacy /admin/question-banks + existing admin/qb still load

## Deferred (reported, not built)
- #252 inline nested mega-form (passage + N sub-questions in one POST) — used pivot/show model instead
- domain/standard CRUD (#251 says مستقبلًا/future) — only existing standard_id/skill_id reused
- Audit Log dedicated viewer (ActivityLog already records the events)
- Excel import / error report download (#254, separate card)

## Progress Tracking
| Phase | Status |
|-------|--------|
| 1–4 | 100% |
