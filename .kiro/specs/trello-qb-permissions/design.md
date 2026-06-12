# Design: Question-bank permissions, states & exam/assignment integration (#217)

Status captured 2026-06-13 after investigating the live qb module.

## Already DONE (from qb v2 / #213)
- **Visibility + cross-school scoping** (the card's key safety rules): `EloquentQuestionBankRepository`
  scopes index + `find()` so a school sees its own banks (any visibility) + library templates +
  same-company public banks + banks explicitly shared with it. **Another school's PRIVATE bank
  never matches** (private only matches `school_id = activeSchoolId`). Route-model-binding is
  guarded via the scoped `find()`. ✓
- **Question states**: `bank_questions.status enum(draft,pending_review,approved,rejected,archived)`
  exists; index filters by status; create/edit set it. ✓
- **Manual add + Excel import + template + code/image** (#213) — done & gated by role middleware.
- **Copy public→private** + **promote private→public** exist in the repository.

## REMAINING (large — needs a focused build + product decisions)

### 1. Granular permission system (24+ permissions)
Today qb actions are gated only by **route role middleware** (super-admin/school-admin), not by a
per-action permission set. #217 wants bank-level (view/create/edit/archive/delete/manage-perms/
copy/promote/link-schools/link-teachers/use-in-exam/use-in-assignment) and question-level
(view/add/import/template/edit/delete/archive/duplicate/approve/reject/send-review/edit-approved/
edit-used/upload-image/delete-image/edit-code/search-by-code/export/error-report/link-standard/
create-category/use-in-exam/use-in-assignment) permissions. Requires:
- A permission registry (extend the app's existing role/permission infra — check `role_user` /
  any Spatie-style tables) + seeding.
- UI gating: hide add/import/approve/etc. buttons unless permitted.
- Controller gating: `authorize()`/Gate checks on every action (currently absent in
  `BankQuestionController`).

### 2. State-usage rules
- "Non-approved questions are not available for general use if the bank requires approval" →
  needs a per-bank `requires_approval` setting + filtering questions to `approved` when serving
  them for exam/assignment building.
- "Can't edit an approved question without special permission"; "send for review" / "approve" /
  "reject" flows on the question (the actions exist on bank questions? verify — approval columns
  `reviewed_by/reviewed_at/rejected_reason` exist on `bank_questions`, wire the transitions).

### 3. ⚠️ Bank↔exam/assignment integration — DOES NOT EXIST YET (prerequisite)
`exam_questions` has **no `bank_question_id`** (columns: exam_id, question, type, options,
correct_answer, marks, explanation, order). Exam questions are standalone copies; bank questions
are NOT referenced by exams or assignments. So:
- "Use bank question in exam/assignment" must be **built** (a picker that copies/links a bank
  question into an exam/assignment, ideally storing `source_bank_question_id`).
- "Can't delete a used question (archive instead)" and "can't edit a used question except by copy"
  are **un-enforceable until that linkage exists** — there is currently no notion of a bank
  question being "used".

## Recommendation
Tackle in this order as its own focused effort: (a) add `source_bank_question_id` to
exam_questions + a bank-question picker (integration), (b) the per-bank `requires_approval` +
approved-only-serving, (c) the granular permission registry + UI/controller gating, (d) the
used-question delete→archive / edit→copy guards (now enforceable). Each is testable independently.
