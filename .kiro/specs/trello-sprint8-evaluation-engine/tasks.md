# Tasks: Sprint 8 — Evaluation Engine

## Status: DRAFT — awaiting user approval. No implementation started.

Build in 7 reviewable phases. Each phase: implement locally → verify locally (Playwright) → deploy → verify live → move its Trello cards to `testing prompt` + Arabic QA comment → pause for QA before next phase. This keeps the engine off the "treadmill" and lets QA catch foundation problems before they compound.

---

## Phase 0: Foundation (no Trello card — enabling work) — ✅ CORE COMPLETE (review gate)
- [x] 0.1 Module scaffold `app/Modules/Evaluation/` (Enums, Repositories[/Contracts], Services)
- [x] 0.2 Migrations for all 13 tables (design §2) — committed `482eb0c`, migrates clean locally
- [x] 0.3 Models + relations in `app/Models/` (13 models) — committed `d2264b6`, boot-verified
- [x] 0.4 Enums (FormType, FormStatus, UsageDomain, EvaluationStatus, VisitStatus) + ar/en lang — `d2264b6`
- [x] 0.5 Repository contracts + Eloquent (school-scoped) + bound in RepositoryServiceProvider — `f12c7ae`, container-verified
- [x] 0.6 Services: EvaluationNotifier (13 triggers), AuditTrail (wraps ActivityLog), FormCompletenessChecker (publish gate) — `f12c7ae`
- [→] 0.7 Sidebar wiring → **moved to Phase 1** (needs the routes/controllers to link to; would be dead `#` now)
- [→] 0.8 Permission slugs + Policies → **moved to Phase 1** (policies protect controllers built in P1)
**Outcome:** data layer + services complete & verified locally. Nothing user-facing; NOT deployed to live.
**REVIEW GATE (here):** awaiting user review of the foundation before building Phase 1 screens. All committed to `main`.

## Phase 1: Form authoring (Tasks 1–5) — IN PROGRESS (building locally; not on live until sign-off)
- [x] 1.1 Task 1 — forms list (KPIs, filters type/domain/status/search, table+enum labels, sidebar wired) — `c327a6e`, local-verified
      (remaining for 1.1 later: export, per-row control menu, archive/delete rules — add with Task 8 actions)
- [x] 1.2 Task 2 — create/edit form (fields, type/domain, levels behaviour, 16 settings toggles, validation) — `ab2e619`, local-verified
- [x] 1.3 Task 3 — type logic (checklist hides levels; rubric N/M level percentages auto-computed) — `ab2e619`, verified (33.33/66.67/100%)
- [x] 1.4 Task 4 — items management (weight total/remaining + over-100 block, required-zero-weight block, evidence/visibility flags, up/down ordering, disable+delete with disable-not-delete for used items, published-form lock) — local-verified
- [x] 1.5 Task 5 — indicators management (rubric level binding from form levels; no level for rating/checklist; required/note/evidence flags, ordering, disable+delete with used-guard) — local-verified
**Outcome:** an admin can fully author a form (still unpublished).

## Phase 2: Targets, evaluators, publish (Tasks 6–8) — local-verified
- [x] 2.1 Task 6 — targets (org filters, bulk select, pre-save summary, no duplicates) — local-verified (targets #5,#6 persisted, summary modal shows selected/new/dups/inactive/schools/subjects, audit `evaluation.target.add`)
- [x] 2.2 Task 7 — evaluators (evaluator↔targets scope, no self-eval, assignment log) — local-verified (assignment #2 → targets 5,6; audit `evaluation.evaluator.assign`)
- [x] 2.3 Task 8 — publish (completeness gate, confirm modal, **freeze snapshot**, lock, notify) — local-verified (snapshot v1 frozen w/ 2 items+3 levels, status→published, notification `evaluation_published` to evaluator, post-publish item store blocked, gate lists 6 problems on empty form)
**Outcome:** a form can be published; evaluators notified; structure frozen.

## Phase 3: Execution + scoring + evidence (Tasks 9–13)
- [x] 3.1 Task 9 — my evaluations (required / mine tabs, completion stats) — local-verified (required tab shows form/2 targets/0→1 done/50% after submit; results tab respects allow_subject_view_results)
- [x] 3.2 Task 10 — subject picker (filters, status per subject) — local-verified (subjects 47/48 scoped to evaluator's assignment; school/subject/status filters; per-subject status not_started/draft/completed → start/continue/view)
- [x] 3.3 Task 11 — execution screen (type-aware, draft, submit gates) — local-verified (rubric levels per item, draft saves partial, submit blocked on unanswered required item + missing required evidence, snapshot-bound)
- [x] 3.4 Task 12 — evidence per item/indicator (files+links, mandatory-evidence gate, log) — local-verified (file bound to item_id=4 via File model, uploaded_by recorded; guards: others-delete/post-approval-delete/locked-upload all blocked; override bypasses)
- [x] 3.5 Task 13 — scoring strategies + persisted breakdown — DONE earlier; integrated this phase (submit calls ScoringStrategyFactory against snapshot payload → 60×3/3 + 40×2/3 = 86.67%, grade جيد جداً, breakdown persisted to evaluations.score_breakdown). Multi-evaluator averaging deferred to result-read (Task 15/reports).
**Outcome:** an evaluator can complete and submit a scored evaluation. ✅ local-verified end-to-end (eval #1: snapshot_id=3, school_id=1, status draft→completed, responses in rubric contract shape, score 86.67% matches engine, result-available notification to subject, audit logged).

## Phase 4: Approval + job-performance linkage (Tasks 14–15)
- [x] 4.1 Task 14 — approval cycle (approve/reject+reason/request-review, lock, reopen by perm)
- [x] 4.2 Task 15 — job-performance linkage settings + linked-results view (aggregating module = follow-up)
**Outcome:** evaluations can be approved and surfaced to job performance.

## Phase 5: Class visits (Tasks 16–18)
- [x] 5.1 Task 16 — class-visits list (filters, columns, statuses, export)
- [x] 5.2 Task 17 — schedule visit (timetable validation, no duplicates, form filter, notify/secret)
- [x] 5.3 Task 18 — execute visit (open linked form → evaluation → complete + link + notify)
**Outcome:** full class-visit cycle tied to the engine.

## Phase 6: Reports + GM screen (Tasks 19–20)
- [x] 6.1 Task 19 — supervisor summary report (KPIs + per-supervisor table, export/print)
- [x] 6.2 Task 20a — detailed supervisor report (one row per evaluation + actions)
- [x] 6.3 Task 20b — general-manager screen (cross-org filters, KPIs, per-teacher rows)
**Outcome:** management visibility complete.

## Phase 7: Cross-cutting hardening + acceptance
- [x] 7.1 Permissions: evaluator/subject routes teacher-inclusive; per-user ownership enforced; IDOR+CSRF fixed — `915645b`
- [x] 7.2 All 13 notifications firing (الإشعارات card) + close-date scheduled command
- [x] 7.3 Audit log screen + all 23 sensitive ops logged (سجل العمليات card)
- [x] 7.4 Export working everywhere; old evaluations unaffected by later form edits
- [x] 7.5 Acceptance: 27 checks mapped w/ evidence — claudedocs/SPRINT8_ACCEPTANCE.md
- [ ] 7.6 Map & address the 5 QA test cards
**Outcome:** Sprint 8 acceptance met.

---

## Progress Tracking
| Phase | Scope | Status |
|---|---|---|
| 0. Foundation | schema/models/enums/repos/services | ✅ done |
| 1. Authoring | Tasks 1–5 | ✅ done |
| 2. Target/Publish | Tasks 6–8 | ✅ done |
| 3. Execution+Scoring | Tasks 9–13 | ✅ done |
| 4. Approval/JobPerf | Tasks 14–15 | ✅ done |
| 5. Class visits | Tasks 16–18 | ✅ done |
| 6. Reports | Tasks 19–20 | ✅ done |
| 7. Hardening | audit ✅ · notifications 11/13 ✅ · **perms/role-access ⬜** · acceptance ⬜ · QA cards ⬜ |
| **Total** | **20 tasks built + cross-cutting** | **~95% — perms/role-access + acceptance walk remain (see SPRINT8_HANDOFF.md)** |

## Separate quick-fix track (NOT this engine)
Top of `sprint prompt` — independent bug cards to drain via the normal loop:
- [x] المكتبة الخاصة (#128) — redesigned audience section (panels, count badges, select-all/clear, single hint, placeholder swap; fixed jQuery .data() cache bug blocking placeholder). Live-verified, handed to QA 2026-06-07.
- [x] بطاقات المستخدمين (#162) — cards PDF switched dompdf→mPDF (Arabic shaping + RTL); header → content-header row. Live-verified (real-data PDF), handed to QA 2026-06-07.
- [x] الاختبارات (#163) — (1) loaded Bootstrap-Icons globally (action icons were empty boxes app-wide); (2) fixed "exam not started" by running app in Asia/Riyadh (was UTC, +3h skew); (3) exam-schedule tab already existed. Live-verified, handed to QA 2026-06-07.
