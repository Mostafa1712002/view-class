# Tasks: Evaluation Engine v2

Slices are deployable + individually verifiable. One Trello card → testing per slice where possible.
Additive-first: Phases A–D do not touch the live scoring/execution path; Phase E is the gated core change.

## Phase A — Rich item config (#201)  ✅ DONE (deployed 2026-06-12, commit 1ed0609, → testing)
### A.1 Additive migration + model
- [x] Migration: add responsible_role, item_type, calc_method, evidence_needs_approval, editable_after_review, editable_after_approval, min_percentage, internal_notes to evaluation_items (safe defaults)
- [x] EvaluationItem: fillable + casts for new fields
### A.2 Item management UI + request
- [x] Item create/edit form: new fields (role text, type, calc method, evidence-approval toggle, edit-lock toggles, min %, internal notes)
- [x] SaveEvaluationItem action + EvaluationItemController validation for new fields
- [x] Item index: show responsible role + type columns
**Outcome:** ✅ authors can fully configure items per #201; existing forms unaffected. Verified end-to-end locally (item saved with all new fields); live migration ran, columns confirmed.
**Note:** responsible_role is free-text for now; a proper role-catalog dropdown is deferred to Phase E when the shared-eval role list is finalized.

## Phase B — Evidence approval (#204)
- [ ] Migration: evidence.status (default 'approved'), reviewed_by/at, review_note
- [ ] Evidence model + approval Action (approve/reject/needs_edit) + permission gate
- [ ] Execute/approvals UI: evidence status chips + approve/reject buttons
- [ ] Scoring gate: item with evidence_needs_approval contributes 0 until evidence approved; recompute on approval
**Outcome:** evidence lifecycle + gating per #204.

## Phase C — Educational-outcome config (#205)
- [ ] Settings keys eval.outcome_method + eval.outcome_source (+ scope level) + admin UI
- [ ] EducationalOutcomeResolver (company→complex→school precedence; methods: all-students-abs-zero | attendees-only)
- [ ] Outcome item calc_method=auto_platform/external + recompute action
**Outcome:** configurable outcome calc per #205.

## Phase D — Permissions + audit (#208/#210, #209)
- [ ] Permission catalog (constants) for the granular list; map to roles; gate Actions/controllers (fallback to role gates)
- [ ] Expand AuditTrail: old/new value + reason + IP for all listed ops; surface new op types in audit screen
**Outcome:** granular permissions + full audit per #208/#210/#209.

## Phase E — Shared evaluation + per-item state (#202, #203)  ← CORE, form-flag gated
- [ ] Migration: evaluation_forms.shared_mode; evaluation_responses per-item state (status, filled_by, responsible_role, submitted_at, approved_by/at, reject_reason)
- [ ] Execution: in shared_mode, one evaluation per (form,subject); filter items by responsible_role; per-item save/submit
- [ ] Per-item state machine + lock rules (#203); approvals operate per item
- [ ] Final % from all item responses; parity test vs legacy
**Outcome:** one shared evaluation per teacher per #202/#203, behind shared_mode.

## Phase F — Screens + Noor (#206, #207, #211/#212)
- [ ] Job-performance add/edit screen per #206 (eval data section + items table with new columns)
- [ ] GM screen per #207 (shared-entity rows + full filters + stats)
- [ ] Noor system model/template (#211/#212 — detail at implementation)
**Outcome:** new model surfaced in screens; Noor template.

## Phase G — Percentage labelling pass (#200)
- [ ] Re-label درجة/max_score → percentage across item/execute/result/report views; verify two-level display%/weight everywhere
**Outcome:** percentage-first language per #200.

---

## Progress Tracking
| Phase | Card(s) | Status |
|-------|---------|--------|
| A. Item config | #201 | ✅ Done → testing |
| B. Evidence approval | #204 | Not started |
| C. Outcome config | #205 | Not started |
| D. Permissions+audit | #208/#210, #209 | Not started |
| E. Shared eval+state | #202, #203 | Not started |
| F. Screens+Noor | #206, #207, #211/#212 | Not started |
| G. % labelling | #200 | Not started |
| **Total** | **13 cards** | **0%** |
