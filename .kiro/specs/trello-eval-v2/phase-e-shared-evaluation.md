# Phase E — Shared Evaluation + Per-Item State (#202, #203)

**The highest-risk slice.** It changes the evaluation engine from "multiple separate
evaluations per teacher (one per evaluator), averaged in reports" to "ONE shared
evaluation per teacher per form, whose items are assigned to different responsible
roles, each evaluator filling only their items." It MUST be non-breaking: deployed
legacy evaluations keep working unchanged. The new behaviour is gated by a per-form
`shared_mode` flag (default 0 = legacy).

## Schema (additive only)
```sql
ALTER TABLE evaluation_forms ADD shared_mode TINYINT(1) NOT NULL DEFAULT 0;
-- per-item state on responses (each item of the shared evaluation has its own lifecycle)
ALTER TABLE evaluation_responses ADD responsible_role VARCHAR(40) NULL;  -- denormalized from item at fill time
ALTER TABLE evaluation_responses ADD filled_by      BIGINT UNSIGNED NULL;
ALTER TABLE evaluation_responses ADD item_status    VARCHAR(20) NOT NULL DEFAULT 'draft'; -- draft|completed|pending_review|approved|rejected
ALTER TABLE evaluation_responses ADD submitted_at   TIMESTAMP NULL;
ALTER TABLE evaluation_responses ADD approved_by    BIGINT UNSIGNED NULL;
ALTER TABLE evaluation_responses ADD approved_at    TIMESTAMP NULL;
ALTER TABLE evaluation_responses ADD reject_reason  TEXT NULL;
```
NOTE: column is `item_status` (NOT `status`) to avoid colliding with any existing
response column; default 'draft' keeps legacy responses inert to the new logic.

## Model of the two modes
- **Legacy (`shared_mode=0`, default):** UNCHANGED. One `evaluations` row per
  (form, evaluator, subject). SubmitEvaluation scores it; ReportAggregator averages
  across evaluators. Existing forms + all deployed data stay on this path. Do NOT
  touch the legacy code paths beyond adding `if ($form->shared_mode)` branches.
- **Shared (`shared_mode=1`):** ONE `evaluations` row per (form, subject) — the
  "shared evaluation". `evaluator_id` on that row is nullable/ignored. Items carry
  `responsible_role` (added Phase A). Each staff user fills responses ONLY for items
  whose `responsible_role` matches one of their roles (or items individually assigned),
  setting `evaluation_responses.filled_by` + `responsible_role` + `item_status`.

## Execution (the core change) — EvaluationExecutionController + StartEvaluation
- `start`: in shared_mode, find-or-create the SINGLE evaluation for (form, subject)
  (not per evaluator). Many evaluators resolve to the same evaluation row.
- `show`: render only the items the current user is responsible for, UNLESS the user
  has `eval.view_all_items` (then show all, others read-only). Use the form snapshot
  items filtered by `responsible_role ∈ user roles`.
- `draft`/`submit`: write/update only the current user's responses (their items).
  "Submit" here means "submit MY items" → set those responses' `item_status` to
  `completed` (or `pending_review` if the item/form requires approval) + `submitted_at`
  + `filled_by`. It does NOT lock the whole evaluation — only the submitter's items.
- The shared evaluation's overall `status` becomes `completed` only when every active
  item has a response in `completed`/`approved`; `pending_approval` while any item is
  pending_review; etc. Compute this aggregate from the item_status set.

## Per-item lock rules (#203)
- draft: editable by the responsible filler; evidence editable.
- completed: filler can still edit until sent for review.
- pending_review: item LOCKED for the filler (no value/evidence edits); reviewer sees
  approve/reject/return per item.
- approved: item frozen + percentage fixed + approver/time stamped; edit needs
  `eval.edit_after_approval`.
- rejected: returns to draft for the filler with reject_reason.
Enforce in SaveEvaluationDraft/SubmitEvaluation (shared branch) + a new per-item
approval path on EvaluationApprovalController (approve/reject/return a single
response/item, not the whole evaluation) — reuse EvaluationPermissions (approve_item,
reject_item, return_item, approve_final).

## Scoring (reuse, don't rewrite)
The existing scorers read ALL responses of an evaluation and weight by item. In
shared_mode the single evaluation already holds all items' responses (from multiple
fillers), so `ScoringStrategyFactory::for($type)->score($evaluation, $payload)` works
unchanged. Apply `EvidenceGate` (Phase B) as today. Final % = same weighted formula
over all items. Recompute on each item submit/approve. A gated/incomplete item
contributes 0 + a "not yet computed" flag until done.

## Reports / GM screen
GeneralManagerController (#207, Phase F) reads the shared evaluation as ONE row with
per-responsible breakdown — in shared_mode, stop averaging across evaluator rows
(there's one row); show per-item responsible + status instead. Keep the legacy
averaging branch for shared_mode=0 forms.

## Build order (Sonnet codes each; Opus verifies)
1. Migration (shared_mode + response per-item columns) + EvaluationForm/EvaluationResponse fillable/casts + a `shared_mode` toggle on the form create/edit screen.
2. StartEvaluation + ExecutionController shared branch (one eval per subject; item filtering by responsible_role; per-item save/submit). Legacy branch untouched.
3. Per-item approval actions + EvaluationApprovalController per-item approve/reject/return; aggregate status computation.
4. Parity test: a shared_mode form with all items filled by one user must yield the SAME final % as the legacy path would. Build a tinker parity harness.

## Non-breaking guarantees (verify each)
- Every new code path guarded by `if ($form->shared_mode)`; `shared_mode=0` forms hit
  ZERO new logic.
- New response columns default to inert values; existing responses unaffected.
- No change to the scorers, EvidenceGate, or legacy Submit/Approve paths.
- Migrations additive; existing approved evaluations stay frozen.
