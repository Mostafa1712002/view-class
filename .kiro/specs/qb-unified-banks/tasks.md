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
- [ ] `privateBanksAcrossSchools(?schoolFilter)` repo method
- [ ] `GET question-banks/owner-review` (super-admin) + blade (school filter, counts, drill-in)

## Phase 3: Per-question promotion (writes new rows, originals intact)
### Task 3.1: Copy selected questions
- [ ] `copyQuestions(srcBankId, questionIds[], destBankId, scope)` repo (deep-copy row + passage + answers)
- [ ] Question list: super-admin checkboxes + "نقل إلى" (destination = public|owner bank)
- [ ] `POST question-banks/{bank}/questions/copy`

## Phase 4: Access request + approval (cross-tenant gate)
### Task 4.1: Schema + model
- [ ] Migration `question_bank_access_requests` (unique bank+school)
- [ ] `QuestionBankAccessRequest` model + relations
### Task 4.2: Request + decide flow
- [ ] `POST question-banks/{bank}/access-request` (school-admin) + `hasApprovedAccess`
- [ ] `GET question-banks/access-requests` inbox + `POST .../decide` (super-admin)
- [ ] Gate `copyFromOwnerBank` on approved request; public copy stays free
- [ ] Notifications: `BankAccessRequested`, `BankAccessDecided`

## Phase 5: Verify
- [ ] Local seed (public + owner + two-school private) → test each tier view, request→approve→copy, per-question promote, tenant isolation
- [ ] Deploy + live demo per phase

---
## Progress
| Phase | Status |
|-------|--------|
| 1 Owner tier + view | ✅ Done |
| 2 Owner review | Not started |
| 3 Per-question promote | Not started |
| 4 Access approval | Not started |
| 5 Verify | Not started |
