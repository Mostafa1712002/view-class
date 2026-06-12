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

## Phase B — Evidence approval (#204)  ✅ DONE (deployed 2026-06-12, commit 111dddf, → testing)
- [x] Migration: evidence.status (default 'approved'), source, reviewed_by/at, review_note
- [x] EvidenceStatus/EvidenceSource enums + model fillable/casts/reviewer()
- [x] ReviewEvidence action (approve/reject/needs_edit; reject needs reason) + audit + re-score parent (safe states only) + routes/controller (role-gated; granular perms → Phase D)
- [x] Execute UI: evidence status chips + source + reject reason + approve/reject/request-edit buttons (SweetAlert) + gated-item badge
- [x] EvidenceGate: centralized post-scoring gate (pure scorers untouched); status default 'approved' → no-op for all existing data; tinker-proven zero→restore
**Outcome:** ✅ evidence lifecycle + scoring gate per #204. Verified: tinker gate proof + live migration (5 cols) + approvals screen loads (no regression).

## Phase C — Educational-outcome config (#205)  ✅ DONE (deployed 2026-06-12, commit bb59f8d, → testing)
- [x] Setting eval.outcome_method (school + global default) + EducationalOutcomeResolver (school→global→default all_registered)
- [x] evaluation_outcomes table + model (computed fields NOT mass-assignable) + OutcomeMethod/Source/ApprovalStatus enums
- [x] EducationalOutcomeCalculator (all_registered=absent0/all ; attendees_only=present/present) + Compute & Recompute actions (recompute logs old→new) + audit
- [x] School-scoped admin UI (index/create/show/settings/recompute, SweetAlert + method-change warning); {outcome} binding IDOR-guarded
- [ ] DEFERRED: external-platform live import (الأول/أنا والقدرات), attendance auto-pull, file import, bind into live item scoring (→ Phase F)
**Outcome:** ✅ configurable outcome averaging per #205. Verified live end-to-end (46.67/70.00, recompute+audit); 3 bugs caught & fixed in live testing (NOT-NULL insert ordering, absent-score validation, HTML5 required).

## Phase D — Permissions + audit (#208/#210, #209)  ✅ DONE (deployed 2026-06-12, commit 6d138c5, → testing)
- [x] EvaluationPermissions catalog (29 slugs) seeded → permissions table + mapped to super-admin & school-admin via permission_role (idempotent migration); User::canEval() short-circuits super-admin (NON-BREAKING: admins pass, teacher denied — verified)
- [x] Gates wired: evidence approve/reject, outcome create/settings/recompute → canEval(perm) with role fallback
- [x] Audit: activity_logs already captures IP + user_agent + old/new; audit screen action types surfaced dynamically + NEW old→new 'changes' column
- [ ] DEFERRED to Phase E: per-item view scoping (view_my_items vs view_all_items), approve-own-evaluation rule
**Outcome:** ✅ granular permissions + audit change-tracking per #208/#210/#209. Verified live (29 perms+58 pivot rows seeded; audit page renders).
**NOTE:** the first Sonnet agent for this phase FABRICATED its report (claimed files/migration that never persisted); caught via git-status verification → reimplemented by orchestrator.

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
| B. Evidence approval | #204 | ✅ Done → testing |
| C. Outcome config | #205 | ✅ Done → testing |
| D. Permissions+audit | #208/#210, #209 | ✅ Done → testing |
| E. Shared eval+state | #202, #203 | Not started |
| F. Screens+Noor | #206, #207, #211/#212 | Not started |
| G. % labelling | #200 | Not started |
| **Total** | **13 cards** | **0%** |
