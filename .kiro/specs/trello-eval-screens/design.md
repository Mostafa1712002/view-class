# Design: Evaluation screens #206 (add/edit screen) & #207 (GM screen)

Both build on the eval-v2 (#202/#203 shared mode + approval), EvidenceGate, and
percentage (#200) work. Status captured 2026-06-13.

## #206 — تعديل شاشة إضافة تقييم الأداء الوظيفي (execute/fill screen)
Target: `resources/views/admin/evaluation/execute/show.blade.php` + `EvaluationExecutionController`.

### DONE
- §2 per-item: name, description, weight, **percentage entry /100 + live calculated %** (#200),
  evidence button, item notes, required/evidence badges.
- §4 **Percentage summary panel** (#206 this commit): total weights (+unbalanced warning),
  completed/incomplete weight, current final %, items awaiting review, missing items + the
  responsible party that has not completed them. Computed from the unfiltered snapshot.
- §5 partial: save-draft, save-my-items (shared), submit. Shared-mode per-role item gating
  + per-item lock (#203) already enforce "user sees only their items".

### REMAINING (verified ABSENT in the view 2026-06-13 — grep returned 0)
- §2 per-item still missing: **responsible-role label**, **type (item_type)**, **auto/manual
  (calc_method)** indicator, **evidence states** (needs-evidence / mandatory / approved),
  **item status badge**, and the explicit ALERTS: required-incomplete, evidence-mandatory-not-
  uploaded, evidence-uploaded-not-approved, item-locked, item-needs-review.
- §3 Notes block missing: **reviewer notes**, **rejection reason**, **mini audit/movement log**.
- §5 actions missing on this screen (some live only on the approvals screen today):
  **approve item / reject item / reopen item**, **approve final / reject final**,
  **open-for-edit (special permission)**, **clear**, **export**, **print**.
  Rules: final-approve hidden until all required items complete; can't submit an item whose
  mandatory evidence is missing; can't approve an item needing approved evidence; can't edit a
  pending_review/approved item without special permission. (EvidenceGate/approval logic exists in
  actions — this is about surfacing the buttons + state on THIS screen.)

Data available: `$evaluation->responses[*].item_status`, `$evidences` (approval state),
snapshot item `responsible_role/item_type/calc_method/evidence_*`, `$summary`.

## #207 — تعديل شاشة المدير العام (GM index screen)
Target: `EvaluationApprovalController` / `GeneralManagerController` + their index view.
NOT STARTED. Large analytical rebuild:
- ~24 filters (company→complex→school→stage→section→class→subject→specialization→teacher→job
  role→item-responsible→eval party→evaluator→overall status→item status→period→date from/to→
  final % from/to→has-evidence→has-unapproved-evidence→has-missing-items→has-needs-review→
  has-not-started→has-uncounted-outcome).
- ~14 header stats (teachers, completed, incomplete, approved, pending-approval, items-pending-
  review, items-need-review, evidence-pending-approval, evals-with-unapproved-evidence, avg
  performance, max %, min %, evals-missing-required-evidence, evals-with-incomplete-role).
- Table: teacher/school/complex/section/subject/period/current final %/completed-weight/items
  counts (total/completed/missing/pending-review/need-review)/evidence counts/status/date/last
  update/actions.
- Actions: details, items, evidence, responsible parties, approve final, reject, reopen, open-
  for-edit, export, print. "Analytical, not just a data table" + must explain why any eval is
  incomplete (name the responsible party of each missing item; flag unapproved evidence).

Both are multi-section screen builds; tackle each as its own focused turn.
