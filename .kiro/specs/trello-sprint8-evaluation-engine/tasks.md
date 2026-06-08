# Tasks: Sprint 8 — Evaluation Engine

## Status: DRAFT — awaiting user approval. No implementation started.

Build in 7 reviewable phases. Each phase: implement locally → verify locally (Playwright) → deploy → verify live → move its Trello cards to `testing prompt` + Arabic QA comment → pause for QA before next phase. This keeps the engine off the "treadmill" and lets QA catch foundation problems before they compound.

---

## Phase 0: Foundation (no Trello card — enabling work)
- [ ] 0.1 Module scaffold `app/Modules/Evaluation/` (dirs per design §4)
- [ ] 0.2 Migrations for all 14 tables (design §2)
- [ ] 0.3 Models + relations in `app/Models/` (EvaluationForm, EvaluationLevel, EvaluationItem, EvaluationIndicator, EvaluationFormSnapshot, EvaluationTarget, EvaluationAssignment, EvaluationAssignmentTarget, Evaluation, EvaluationResponse, EvaluationEvidence, EvaluationComment, ClassVisit)
- [ ] 0.4 Enums (FormType, FormStatus, UsageDomain, EvaluationStatus, VisitStatus)
- [ ] 0.5 Repository contracts + Eloquent (school-scoped) + bind in RepositoryServiceProvider
- [ ] 0.6 Services skeleton: EvaluationNotifier, AuditTrail, FormCompletenessChecker
- [ ] 0.7 Sidebar: wire العمليات التعليمية ← نماذج التقييم, التقييمات, الزيارات الصفية (replace dead `#`)
- [ ] 0.8 Permission slugs seeded into school_role_permissions; Policies registered
**Outcome:** schema + scaffolding migrated locally; nothing user-facing yet.

## Phase 1: Form authoring (Tasks 1–5)
- [ ] 1.1 Task 1 — forms list (filters, columns, export, statuses, control menu, delete/archive rules)
- [ ] 1.2 Task 2 — create/edit form (fields, type, domain, levels behaviour, settings toggles, validation)
- [ ] 1.3 Task 3 — type logic (Rubric/RatingScale/Checklist input + level rules)
- [ ] 1.4 Task 4 — items management (weights sum=100%, required/evidence flags, ordering, disable-not-delete)
- [ ] 1.5 Task 5 — indicators management (level-bound for rubric; per-type behaviour)
**Outcome:** an admin can fully author a form (still unpublished).

## Phase 2: Targets, evaluators, publish (Tasks 6–8)
- [ ] 2.1 Task 6 — targets (org filters, bulk select, pre-save summary, no duplicates)
- [ ] 2.2 Task 7 — evaluators (evaluator↔targets scope, no self-eval, assignment log)
- [ ] 2.3 Task 8 — publish (completeness gate, confirm modal, **freeze snapshot**, lock, notify)
**Outcome:** a form can be published; evaluators notified; structure frozen.

## Phase 3: Execution + scoring + evidence (Tasks 9–13)
- [ ] 3.1 Task 9 — my evaluations (required / mine tabs, completion stats)
- [ ] 3.2 Task 10 — subject picker (filters, status per subject)
- [ ] 3.3 Task 11 — execution screen (type-aware, draft, submit gates)
- [ ] 3.4 Task 12 — evidence per item/indicator (files+links, mandatory-evidence gate, log)
- [ ] 3.5 Task 13 — scoring strategies + persisted breakdown + multi-evaluator average
**Outcome:** an evaluator can complete and submit a scored evaluation.

## Phase 4: Approval + job-performance linkage (Tasks 14–15)
- [ ] 4.1 Task 14 — approval cycle (approve/reject+reason/request-review, lock, reopen by perm)
- [ ] 4.2 Task 15 — job-performance linkage settings + linked-results view (aggregating module = follow-up)
**Outcome:** evaluations can be approved and surfaced to job performance.

## Phase 5: Class visits (Tasks 16–18)
- [ ] 5.1 Task 16 — class-visits list (filters, columns, statuses, export)
- [ ] 5.2 Task 17 — schedule visit (timetable validation, no duplicates, form filter, notify/secret)
- [ ] 5.3 Task 18 — execute visit (open linked form → evaluation → complete + link + notify)
**Outcome:** full class-visit cycle tied to the engine.

## Phase 6: Reports + GM screen (Tasks 19–20)
- [ ] 6.1 Task 19 — supervisor summary report (KPIs + per-supervisor table, export/print)
- [ ] 6.2 Task 20a — detailed supervisor report (one row per evaluation + actions)
- [ ] 6.3 Task 20b — general-manager screen (cross-org filters, KPIs, per-teacher rows)
**Outcome:** management visibility complete.

## Phase 7: Cross-cutting hardening + acceptance
- [ ] 7.1 Permissions matrix verified per role (الصلاحيات card)
- [ ] 7.2 All 13 notifications firing (الإشعارات card) + close-date scheduled command
- [ ] 7.3 Audit log screen + all 23 sensitive ops logged (سجل العمليات card)
- [ ] 7.4 Export working everywhere; old evaluations unaffected by later form edits
- [ ] 7.5 Walk the 27 acceptance checks (شروط قبول) on live with Playwright evidence
- [ ] 7.6 Map & address the 5 QA test cards
**Outcome:** Sprint 8 acceptance met.

---

## Progress Tracking
| Phase | Cards | Done | Status |
|---|---|---|---|
| 0. Foundation | enabling | 0 | Not started |
| 1. Authoring | Tasks 1–5 | 0 | Not started |
| 2. Target/Publish | Tasks 6–8 | 0 | Not started |
| 3. Execution | Tasks 9–13 | 0 | Not started |
| 4. Approval/JobPerf | Tasks 14–15 | 0 | Not started |
| 5. Class visits | Tasks 16–18 | 0 | Not started |
| 6. Reports | Tasks 19–20 | 0 | Not started |
| 7. Hardening | perms/notif/audit/QA | 0 | Not started |
| **Total** | **20 + 4 cross-cutting + 5 QA** | **0** | **0%** |

## Separate quick-fix track (NOT this engine)
Top of `sprint prompt` — independent bug cards to drain via the normal loop:
- [x] المكتبة الخاصة (#128) — redesigned audience section (panels, count badges, select-all/clear, single hint, placeholder swap; fixed jQuery .data() cache bug blocking placeholder). Live-verified, handed to QA 2026-06-07.
- [x] بطاقات المستخدمين (#162) — cards PDF switched dompdf→mPDF (Arabic shaping + RTL); header → content-header row. Live-verified (real-data PDF), handed to QA 2026-06-07.
- [x] الاختبارات (#163) — (1) loaded Bootstrap-Icons globally (action icons were empty boxes app-wide); (2) fixed "exam not started" by running app in Asia/Riyadh (was UTC, +3h skew); (3) exam-schedule tab already existed. Live-verified, handed to QA 2026-06-07.
