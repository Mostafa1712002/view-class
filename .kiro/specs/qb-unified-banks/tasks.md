# Tasks: Unified Question Banks

## Phase 1: Owner tier + unified 3-tier view (safe/additive)
### Task 1.1: Owner tier column
- [x] Migration: `question_banks.is_owner_bank` boolean default false
- [x] `QuestionBank`: fillable + bool cast + `tier()` accessor (owner/public/private)
### Task 1.2: Create/edit + tabs
- [x] Bank create/edit form: super-admin toggle "بنك منصة الأول (owner)"
- [x] Index: third tab `owner` (منصة الأول); `extractFilters` maps it to `is_owner_bank`
- [x] Tier badge per row (عام / خاص / منصة الأول)
- [x] School-admin view: owner tab read-only + "طلب الوصول" button; own private only

## Phase 2: Owner review of school questions (safe/read-only)
### Task 2.1: Owner-review screen
- [x] `privateBanksAcrossSchools(?schoolFilter)` repo method
- [x] `GET question-banks/owner-review` (super-admin) + blade (school filter, counts, drill-in)

## Phase 3: Per-question promotion (writes new rows, originals intact)
### Task 3.1: Copy selected questions
- [x] `copyQuestions(srcBankId, questionIds[], destBankId, scope)` repo (deep-copy row + passage + answers)
- [x] Question list: super-admin checkboxes + "نقل إلى" (destination = public|owner bank)
- [x] `POST question-banks/{bank}/questions/copy`

## Phase 4: Access request + approval (cross-tenant gate)
### Task 4.1: Schema + model
- [x] Migration `question_bank_access_requests` (unique bank+school)
- [x] `QuestionBankAccessRequest` model + relations
### Task 4.2: Request + decide flow
- [x] `POST question-banks/{bank}/access-request` (school-admin) + `hasApprovedAccess`
- [x] `GET question-banks/access-requests` inbox + `POST .../decide` (super-admin)
- [x] Gate `copyFromOwnerBank` on approved request; public copy stays free
- [x] Notifications: `BankAccessRequested`, `BankAccessDecided`

## Phase 5: Verify
- [x] Local seed (public + owner + two-school private) → test each tier view, request→approve→copy, per-question promote, tenant isolation
- [x] Deploy + live demo per phase

---
## Progress
| Phase | Status |
|-------|--------|
| 1 Owner tier + view | ✅ Done |
| 2 Owner review | ✅ Done |
| 3 Per-question promote | ✅ Done |
| 4 Access approval | ✅ Done |
| 5 Verify | ✅ Done (local 21/21) |
