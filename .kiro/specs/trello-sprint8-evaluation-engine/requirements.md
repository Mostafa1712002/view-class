# Requirements: Sprint 8 — Evaluation Engine (نماذج التقييم + الزيارات الصفية + الأداء الوظيفي)

## Status: DRAFT — awaiting user approval before implementation

## Source
Trello board فيوكلاس, list `sprint prompt`. Cards: `sprint 8` (umbrella), Task 1–20, `الصلاحيات المطلوبة`, `الإشعارات المطلوبة`, `سجل العمليات Audit Log`, `شروط قبول Sprint 8`, plus 5 QA test cards. No screenshots are attached to any card; the "current page / rebuild" wording in the cards refers to a **mockup/external reference**, not deployed ViewClass code. Verified 2026-06-07: no evaluation module exists in the repo, on any branch, in git history, or on live (only a dead `#` sidebar placeholder `nav_evaluations` / `nav_visits`). **This sprint is greenfield for ViewClass.**

## Overview
Build a reusable, configuration-driven **evaluation engine** inside ViewClass that covers the full cycle:

> create form → add levels → add items → add indicators → set targets → assign evaluators → publish → execute → upload evidence → submit → approve/reject/review → score → reports → link to job performance.

The acceptance card is explicit: this is **not** a single "evaluation forms" page. The same engine must later serve teacher / admin / student / parent / school-environment / class-visit / job-performance evaluations. Therefore types, statuses, weights, evaluation parties, and job-performance linkage must be **data-managed and extensible**, not hard-coded.

## Glossary (Arabic → engine term)
| Arabic | Term |
|---|---|
| نموذج تقييم | Evaluation Form (template) |
| مجال الاستخدام | Usage domain |
| مستوى | Level (performance level) |
| عنصر | Item (weighted criterion) |
| مؤشر | Indicator (sub-criterion) |
| المستهدفون | Targets (who is evaluated) |
| المقيّمون | Evaluators / assignments (who evaluates whom) |
| الشواهد | Evidence |
| التقييم | Evaluation (one evaluator × one subject × one form) |
| الزيارة الصفية | Class visit |
| تقييم الأداء الوظيفي | Job-performance evaluation |

## Form types (Task 3)
- **Rubric** — items carry relative weight (sum = 100%); each item has indicators distributed across levels; evaluator picks one level; level N of M = (N/M)×item weight.
- **Rating Scale** — items carry weight; indicators inside an item share the weight equally (item weight ÷ indicator count); evaluator rates each indicator; item score = sum of indicator scores.
- **Checklist** — no multiple levels; each indicator is met/not-met (نعم/لا); score % = met ÷ total × 100.

## Usage domains (Task 2)
teacher · admin · class_visit · student · parent · school_environment · general · job_performance.

## User Stories (EARS)

### US-1 Form authoring (Tasks 1–5)
- WHEN an authorized user opens نماذج التقييم THE SYSTEM SHALL show a management list with search, filters (type, domain, status, published?, class-visit-linked?, job-performance-linked?, created from/to, close from/to, creator, school/company), column customization, export, and per-row control actions.
- WHEN a user creates a form THE SYSTEM SHALL require title, type, usage domain, and a valid levels configuration, and SHALL reject save if close_date < start_date or class-visit-only conflicts with a non-class-visit domain.
- WHEN a user adds items THE SYSTEM SHALL enforce that total item weights equal 100% for weighted types before publish, and SHALL forbid weight 0 on a required item.
- WHEN a user adds indicators THE SYSTEM SHALL bind them to a level (Rubric) or leave them level-free (Rating Scale) or as met/not-met rows (Checklist).
- THE SYSTEM SHALL forbid deleting an item/indicator already used in an executed evaluation (disable instead).

### US-2 Targets & evaluators (Tasks 6–7)
- WHEN a user sets targets THE SYSTEM SHALL allow selecting individuals or bulk sets (all teachers of a school/company/subject/stage/section) with org filters, SHALL show a pre-save summary (count, schools, subjects, duplicates, inactive accounts), and SHALL forbid duplicating a subject within one form.
- WHEN a user assigns evaluators THE SYSTEM SHALL link each evaluator to a specific set of targets (scope), SHALL hide from each evaluator everything not assigned to them, and SHALL forbid self-evaluation unless the form allows it.

### US-3 Publish & snapshot (Task 8)
- WHEN a user publishes THE SYSTEM SHALL validate completeness (title, type, domain, levels, items, indicators, weight totals, targets, evaluators), show a confirmation summary, then set status=published, **freeze an immutable snapshot of the form structure**, notify evaluators, and lock the structure (editable only with a special permission).
- THE SYSTEM SHALL ensure later edits to a form never alter already-recorded evaluations (they bind to the snapshot).

### US-4 Execution (Tasks 9–13)
- WHEN an evaluator opens التقييمات THE SYSTEM SHALL show "required of me" and "my own results" tabs with completion stats.
- WHEN an evaluator opens a form THE SYSTEM SHALL list the subjects assigned to them with status and filters.
- WHEN an evaluator executes THE SYSTEM SHALL render items/indicators per the form type, allow save-as-draft anytime, and forbid submit while required items/indicators or required evidence are incomplete.
- WHEN an evaluation is submitted THE SYSTEM SHALL compute the score per type, persist score breakdown, and lock the evaluation (reopen only with permission).

### US-5 Evidence (Task 12)
- WHERE an item/indicator needs evidence THE SYSTEM SHALL allow multiple file or link evidences bound to that node, record uploader, and forbid submit if mandatory evidence is missing.
- THE SYSTEM SHALL forbid deleting evidence after approval or evidence uploaded by another user, except with permission, and SHALL log every upload/delete.

### US-6 Approval (Task 14)
- WHEN configured, submission SHALL transition to pending_approval; otherwise to completed.
- WHEN an approver acts THE SYSTEM SHALL support approve / reject (reason required) / request-review (flag specific items), lock on approval, and log every action.

### US-7 Job-performance linkage (Task 15)
- WHERE a form opts into job performance THE SYSTEM SHALL capture linkage settings (linked performance item, weight, count-on submit vs approve, last vs average, specific party only) and surface linked results in the job-performance view.
- THE SYSTEM SHALL exclude draft/rejected evaluations from job-performance aggregation.

### US-8 Class visits (Tasks 16–18)
- WHEN a supervisor schedules a class visit THE SYSTEM SHALL capture school/stage/class/section/teacher/subject/period/date/time/form/type/notify/pre-notes, only offer published class-visit forms, forbid scheduling outside the teacher's timetable, forbid duplicate visit for same teacher+period+date, and notify the teacher unless the visit is secret.
- WHEN a supervisor executes a visit THE SYSTEM SHALL open the linked form, and on submit set visit=completed, persist the evaluation, link it to the teacher and (if enabled) job performance, and notify the teacher if allowed.

### US-9 Reports (Tasks 19–20)
- THE SYSTEM SHALL provide a supervisor summary report (per-supervisor aggregates + KPIs), a detailed supervisor report (one row per evaluation), and a general-manager screen (cross-org filters, KPIs, per-teacher rows) — all with export/print.

### US-10 Permissions, notifications, audit (cross-cutting)
- THE SYSTEM SHALL enforce the per-role capability matrix (system admin / supervisor / school manager / complex manager / general manager / teacher).
- THE SYSTEM SHALL emit notifications on the 13 defined triggers.
- THE SYSTEM SHALL write an audit log entry for every sensitive operation (create/edit/publish/close/archive form, item/indicator CRUD, evaluator change, execute/draft/submit, approve/reject/reopen, evidence upload/delete, score edit, post-approval edit).

## Non-Functional Requirements
- **NFR-1 Multi-tenant**: every query touching school-owned data filters by the authenticated user's `school_id` (enforced in repositories). Global/company forms (`school_id = null`) follow the existing super-admin scoping rules.
- **NFR-2 Conventions**: follow `app/Modules/<Name>/` module + repository + action patterns from CLAUDE.md; Bootstrap 4 + jQuery + Select2 admin UI; reuse `ActivityLog::log()`, `App\Models\Notification`, `ApiResponse`, existing `school_role_permissions`.
- **NFR-3 Immutability**: published structure is frozen via snapshot; historical evaluations never change.
- **NFR-4 Soft deletes** on tenant-owned entities; disable-not-delete for used items/indicators.
- **NFR-5 Extensibility**: form types, statuses, domains, evaluation parties live in config/enum classes + reference data, not scattered literals.

## Acceptance (شروط قبول — 27 checks)
Mapped 1:1 to the acceptance card; tracked in `tasks.md` verification phase. Sprint counts complete only when all 27 pass on live with Playwright evidence.

## Out of scope / to confirm with user
- A full standalone **job-performance module** (Task 15 builds the *linkage + results view* on the evaluation side only; the aggregating performance module itself is not on the board).
- The 3 unrelated bug cards at the top of `sprint prompt` (المكتبة الخاصة, بطاقات المستخدمين, الاختبارات) are a separate quick-fix track, not part of this engine.
