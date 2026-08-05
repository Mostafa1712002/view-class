# Design: Unified Question Banks

## What already exists (reuse, don't rebuild)
- `question_banks.visibility` (`public|private`) drives all scoping/tabs — the LIVE column.
- Index with عام/خاص tabs: `QuestionBankController::index` + `extractFilters`.
- Whole-bank `promote` (private→public, super-admin) and `copyToMySchool`
  (public→new private) in `EloquentQuestionBankRepository`.
- Question CRUD (`BankQuestionController`), question review states on `bank_questions.status`.
- Roles via slug (`isSuperAdmin`/`isSchoolAdmin`); school scoping via `activeSchoolId()`.

## New concepts

### 1. Owner tier — `question_banks.is_owner_bank` (NEW boolean)
An owner («منصة الأول») bank = super-admin bank with `is_owner_bank=true`
(`school_id` null). Distinct from a public bank: **not** freely copyable, **not**
listed to schools' question views until access is granted. `visibility` stays as
the public/private access driver; `is_owner_bank` is an orthogonal flag that
carves the owner tier out of the super-admin's banks.

Tier resolution (single helper `QuestionBank::tier()`):
- `is_owner_bank` → `owner`
- else `visibility=public` → `public`
- else → `private`

### 2. Access requests — `question_bank_access_requests` (NEW table)
```sql
CREATE TABLE question_bank_access_requests (
  id BIGINT UNSIGNED PK,
  question_bank_id BIGINT UNSIGNED,      -- FK question_banks (an owner bank)
  school_id BIGINT UNSIGNED,             -- requesting school
  requested_by BIGINT UNSIGNED,          -- FK users
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  decided_by BIGINT UNSIGNED NULL,       -- FK users (owner)
  decided_at TIMESTAMP NULL,
  note VARCHAR(255) NULL,
  created_at, updated_at,
  UNIQUE (question_bank_id, school_id)   -- one live request per school per bank
);
```
An `approved` row is the grant: `copyFromOwnerBank` checks it before copying.

## Data flow

### Unified view (US-001/002)
`index` gains a third tab `owner`. `extractFilters` maps `tab=owner` →
`is_owner_bank=true`. Repository `baseQuery`:
- super-admin: all three tiers (owner tab = owner banks).
- school-admin: `public` (as today) + own `private` + `owner` banks shown
  **read-only** with a "طلب الوصول" button; owner-bank questions hidden until an
  approved request exists.
Each row renders a tier badge (عام / خاص / منصة الأول).

### Request + approval (US-004)
- `POST question-banks/{bank}/access-request` (school-admin) → create/refresh a
  `pending` request; notify super-admins (`BankAccessRequested`).
- Super-admin inbox `GET question-banks/access-requests` + `POST
  .../access-requests/{req}/decide` (approve|reject) → set status + notify school
  (`BankAccessDecided`).
- `copyFromOwnerBank(bank, school)` (the school's copy action for owner banks)
  aborts 403 unless an `approved` request exists for (bank, school).
- Public-bank copy path stays gate-free.

### Owner review of school questions (US-006)
`GET question-banks/owner-review` (super-admin): list `visibility=private` banks
across all schools (join school name), question counts, filter by school; link to
the bank's existing question list.

### Per-question promotion (US-007)
- Question list gains checkboxes (super-admin only) + a "نقل إلى" action →
  `POST question-banks/{bank}/questions/copy` with `question_ids[]` +
  `destination_bank_id`.
- `copyQuestions(srcBank, questionIds, destBank)` in the repo: for each selected
  `bank_question` belonging to srcBank, deep-copy the row (+ its `passage` and
  `question_answers` if any) into destBank with a fresh id, `status=approved` (or
  keep source status), `source='promoted'`. Destination must be a public or owner
  bank the super-admin owns. Originals untouched.

## Repository additions (Eloquent stays in the repo — CLAUDE.md)
`QuestionBankRepository` (contract + Eloquent) gains:
- `ownerBanksForSchool(?int schoolId)` / tier-aware `baseQuery`.
- `hasApprovedAccess(int bankId, int schoolId): bool`.
- `createAccessRequest(...)`, `decideAccessRequest(...)`.
- `copyQuestions(int srcBankId, array questionIds, int destBankId, int schoolIdScope): int`.
- `privateBanksAcrossSchools(?int schoolFilter)` for the owner-review screen.

## Models
- `QuestionBank`: `is_owner_bank` fillable + bool cast; `tier()` accessor;
  `accessRequests()` hasMany.
- NEW `QuestionBankAccessRequest` model (fillable, casts, relations bank/school/requester/decider).

## Notifications
- `BankAccessRequested` (to super-admins), `BankAccessDecided` (to the school
  admins of the requesting school) — DB channel, mirror `ClassCapacityExceeded`.

## Routes (inside the existing admin group that holds `question-banks.*`)
| Method | Name | Who | Purpose |
|--------|------|-----|---------|
| POST | question-banks.access-request | school-admin | request an owner bank |
| GET  | question-banks.access-requests | super-admin | requests inbox |
| POST | question-banks.access-requests.decide | super-admin | approve/reject |
| POST | question-banks.copy-from-owner | school-admin | copy from a granted owner bank |
| GET  | question-banks.owner-review | super-admin | school-added questions overview |
| POST | question-banks.questions.copy | super-admin | promote selected questions |

## Phasing (safe → gated)
1. **Owner tier + unified view** (additive: `is_owner_bank`, tabs, badges). Safe.
2. **Owner review screen** (read-only). Safe.
3. **Per-question promotion** (writes new rows, originals intact). Semi-safe.
4. **Access-request + approval workflow** (new table + gate + notifications).

Each phase: local-first (Valet + seeded banks) → verify → deploy → demo.
