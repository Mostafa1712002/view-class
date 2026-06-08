# Design: Sprint 8 — Evaluation Engine

## Status: DRAFT — awaiting user approval before implementation

## 1. Architecture overview

A single polymorphic, config-driven engine under `app/Modules/Evaluation/`. The same form → execute → score → approve pipeline serves every usage domain. Type-specific behaviour (input rendering + scoring) is isolated behind a **Strategy** so new form types are additive, not invasive.

```
                    ┌─────────────────── AUTHORING ───────────────────┐
EvaluationForm ──< Level                                              │
      │           Item ──< Indicator                                  │
      │                                                               │
      ├──< EvaluationTarget        (who is evaluated)                 │
      ├──< EvaluationAssignment ──< AssignmentTarget (who evals whom) │
      └──> FormSnapshot (frozen on publish) ────────────────┐        │
                    └──────────────────────────────────────┐│        │
                    ┌─────────────────── EXECUTION ─────────▼▼────────┐
Evaluation (evaluator × subject × form, bound to snapshot)            │
      ├──< EvaluationResponse  (per item/indicator: level/value/score)│
      ├──< EvaluationEvidence  (files/links per node)                 │
      └──< EvaluationComment   (subject's comment on result)          │
ClassVisit ──> Evaluation (1:1 on execution)                          │
ActivityLog (reused) · Notification (reused)                          │
```

Scoring is delegated:
```
ScoringStrategy (interface)
 ├─ RubricScorer        item = weight × (levelIndex / levelCount)
 ├─ RatingScaleScorer   indicator = (itemWeight / indicatorCount) × (levelValue / maxLevel); item = Σ indicators
 └─ ChecklistScorer     percentage = met / total × 100
ScoringStrategyFactory::for($form->type)
```

## 2. Data model (new tables)

All tenant-owned tables carry `school_id` (nullable for global/company forms) + soft deletes. PKs are `int` per legacy convention.

| Table | Key columns |
|---|---|
| `evaluation_forms` | school_id?, created_by, title, description, internal_notes, **type** (rubric\|rating_scale\|checklist), **usage_domain**, **status** (draft\|ready\|published\|closed\|archived), levels_count, start_date, close_date, is_class_visit_only, links_to_job_performance, **settings** json, **job_perf_settings** json, published_at, closed_at, archived_at, timestamps, softDeletes |
| `evaluation_levels` | form_id, label, value (numeric rank), percentage (rubric auto), sort_order |
| `evaluation_items` | form_id, name, description, sort_order, weight, max_score, is_required, needs_evidence, evidence_required, allow_note, visible_to_evaluator_only, visible_to_subject_after_result, status (active\|disabled), timestamps |
| `evaluation_indicators` | item_id, form_id, level_id?, text, description, sort_order, is_required, needs_note, needs_evidence, evidence_required, status, timestamps |
| `evaluation_form_snapshots` | form_id, version, payload json (full frozen tree), published_by, published_at |
| `evaluation_targets` | form_id, target_type, target_id (poly; usually User), meta json (school_id, stage, subject for filter), added_after_publish, added_by, timestamps |
| `evaluation_assignments` | form_id, evaluator_id, status, assigned_at, timestamps |
| `evaluation_assignment_targets` | assignment_id, target_id (→ evaluation_targets) |
| `evaluations` | form_id, snapshot_id, evaluator_id, subject_type, subject_id (poly), school_id, class_visit_id?, status (draft\|completed\|pending_approval\|approved\|rejected\|needs_review\|locked), total_score, max_score, percentage, grade_label, items_completed, indicators_completed, evidence_count, general_notes, submitted_at, approved_by?, approved_at?, rejection_reason?, timestamps, softDeletes |
| `evaluation_responses` | evaluation_id, item_id, indicator_id?, level_id?, checklist_value?, score, note, timestamps |
| `evaluation_evidences` | evaluation_id, item_id?, indicator_id?, type (file\|link), file_id?/url?, original_name, mime, size, description, internal_notes, visible_to_subject, uploaded_by, timestamps |
| `evaluation_comments` | evaluation_id, user_id, body, timestamps |
| `class_visits` | school_id, supervisor_id, teacher_id, subject_id, stage?, class_room_id, section_id, period_id, form_id, evaluation_id?, visit_type, notify_teacher, pre_notes, visit_date, visit_time, status (scheduled\|secret\|teacher_notified\|in_progress\|completed\|postponed\|cancelled\|missed), timestamps, softDeletes |

**Reused, not rebuilt**: `activity_logs` (via `ActivityLog::log()`) for the Audit Log; `notifications` (via `App\Models\Notification`) for all 13 triggers; `files` for evidence storage; `school_role_permissions`/`roles`/`permissions` for the capability matrix.

## 3. State machines

- **Form**: draft → ready → published → closed → archived. (draft↔ready on completeness; publish freezes snapshot; archive when evaluations exist instead of delete.)
- **Evaluation**: draft → completed → (pending_approval → approved | rejected | needs_review) → locked. reopen (perm) → draft.
- **Class visit**: scheduled/secret → teacher_notified → in_progress → completed; side states postponed/cancelled/missed.
- **Assignment**: assigned → in_progress → completed (derived from its evaluations).

Transitions are guarded in Actions; illegal transitions throw and are surfaced as validation errors.

## 4. Module structure (per CLAUDE.md)
```
app/Modules/Evaluation/
├── Controllers/
│   ├── EvaluationFormController        (Task 1 list, Task 2 create/edit, Task 8 publish/close/archive/clone)
│   ├── EvaluationItemController        (Task 4)
│   ├── EvaluationIndicatorController   (Task 5)
│   ├── EvaluationTargetController      (Task 6)
│   ├── EvaluationAssignmentController  (Task 7)
│   ├── MyEvaluationsController         (Task 9)
│   ├── EvaluationExecutionController   (Task 10 subject picker, Task 11 execute, Task 13 score)
│   ├── EvaluationEvidenceController    (Task 12)
│   ├── EvaluationApprovalController    (Task 14)
│   ├── ClassVisitController            (Tasks 16–18)
│   ├── SupervisorReportController      (Task 19, Task 20 detailed)
│   └── GeneralManagerController        (Task 20 GM screen)
├── Actions/  (CreateForm, UpdateForm, PublishForm, CloseForm, ArchiveForm, CloneForm,
│              SaveItem, SaveIndicator, SetTargets, AssignEvaluators, StartEvaluation,
│              SaveDraft, SubmitEvaluation, ApproveEvaluation, RejectEvaluation,
│              RequestReview, UploadEvidence, DeleteEvidence, ScheduleClassVisit,
│              NotifyTeacher, ExecuteClassVisit)
├── Scoring/  (ScoringStrategy, RubricScorer, RatingScaleScorer, ChecklistScorer, ScoringStrategyFactory, ScoreResult DTO)
├── Repositories/Contracts/ + Eloquent  (FormRepository, EvaluationRepository, ClassVisitRepository — all school-scoped)
├── Services/ (EvaluationNotifier wraps Notification; AuditTrail wraps ActivityLog::log; FormCompletenessChecker; ReportAggregator)
├── DTOs/ · Http/Requests/ · Policies/ (EvaluationFormPolicy, EvaluationPolicy, ClassVisitPolicy)
├── Enums/ (FormType, FormStatus, UsageDomain, EvaluationStatus, VisitStatus)
└── routes.php   (admin.evaluations.*, admin.class-visits.*, admin.eval-reports.*, my.evaluations.*)
```
Models live in `app/Models/` (shared-domain convention) but are authored as part of this module.

## 5. Scoring engine (Task 13) — the extensibility core
`ScoringStrategy::score(Evaluation $e, FormSnapshot $s): ScoreResult` returns total, max, percentage, grade_label, per-item breakdown. Multi-evaluator averaging (when `settings.average_on_multiple`) is applied above the strategy at result-read time. Score breakdown is persisted so "how was this computed" is always reproducible (acceptance rule).

## 6. Permissions (capability matrix → policies)
Map each card-defined capability to a permission slug under the existing `school_role_permissions` system. Roles: super-admin (system admin) · supervisor · school-admin (school manager) · complex-manager · general-manager · teacher. Policies gate controllers; repositories add the school_id scope. Teacher is read-only on own result (+ comment if allowed).

## 7. Notifications (13 triggers → EvaluationNotifier)
publish · evaluator-assigned · class-visit-added · teacher-visit-reminder(non-secret) · draft-saved · submitted · review-requested · approved · rejected · reopened · result-available · teacher-commented · close-date-approaching. Each creates `App\Models\Notification` rows (user_id, type, title, body, icon, color, data). Close-date-approaching needs a scheduled command.

## 8. Audit (سجل العمليات → AuditTrail service)
Thin wrapper over `ActivityLog::log(user, action, model, old, new, reason)` covering the 23 sensitive operations; a read screen filters activity_logs by the evaluation module's action namespace.

## 9. UI conventions
Bootstrap 4 + jQuery + Select2 (global init already present). List pages reuse the existing admin index pattern (search + filter drawer + column customization + export) seen in lessons/policies. Execution screen is the one bespoke view (type-aware rendering). RTL respected via the existing layout.

## 10. Org hierarchy used for targets/filters/visits
EducationalCompany (الشركة/المجمع) → School → ClassRoom (الصف/المرحلة) → Section (الفصل); TeacherProfile/TeacherAssignment (المعلم/المادة); Subject; SchedulePeriod + TimeSlot + ScheduleEntry (الحصة/الجدول) — used to validate "visit only within the teacher's timetable" (Task 17).

## 11. Key risks / decisions to confirm
1. **Scope size** — ~14 tables, ~12 controllers, ~20 actions, 3 scorers, ~15 views, permissions+notifications+audit. Genuinely multi-week. Proposed: build & review in the 7 phases below; deploy + QA each phase rather than big-bang.
2. **Job performance (Task 15)** — no performance module exists. Recommend building linkage settings + a results view only this sprint; flag the aggregating module as a follow-up.
3. **Reference data vs enums** — DECISION (2026-06-08): keep PHP Enums this sprint (user-approved); revisit a DB-managed type registry in a later sprint only if admins need to add types without a deploy.
   Original note: — start with PHP Enums for types/statuses/domains (fast, type-safe) while keeping the data shapes (settings/weights/parties) fully data-driven, as the acceptance card demands. Full DB-managed type registry can be a later refactor.
4. **Export/print** — confirm desired formats (the cards say تصدير/طباعة; existing pages export via the shared helper — match that).
