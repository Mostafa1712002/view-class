# Requirements: Evaluation Engine v2 (Trello #200–#212)

## Overview
The client reviewed the deployed Sprint-8 teacher performance-evaluation engine and requested
conceptual changes. These are **modifications to existing logic, NOT a rebuild** (#200 states this
explicitly and supersedes conflicting parts of the original prompt). The deployed engine is already
weighted/percentage-based with items, indicators, evidence, snapshots, states, approvals, reports,
and audit. v2 refines the model toward: percentage-first language, ONE shared evaluation per teacher
with per-role item assignment, richer item config, evidence approval gating, configurable
educational-outcome calculation, granular permissions, and fuller audit.

Cards covered: #200 scoring→percentage, #201 item management+fields, #202 shared-evaluation,
#203 states/lock, #204 evidence approval, #205 educational-outcome config, #206 job-perf add screen,
#207 GM screen, #208/#210 permissions, #209 audit, #211/#212 Noor model.

## User Stories

### US-200: Percentage-first scoring
**As a** system admin **I want** the final evaluation expressed as a **percentage** (not a raw score)
**so that** results are comparable regardless of item count.
- THE SYSTEM SHALL express each item with two distinct numbers: `display_percentage` (the % the
  evaluator scores the item out of 100) and `weight` (the item's share of the final %).
- THE SYSTEM SHALL compute final = Σ(item display% × item weight) / Σ(weights), surfaced as a percentage + grade label.
- WHERE legacy "max_score / درجة" labels appear THE SYSTEM SHALL re-label them in percentage terms.
- NOTE: current scorers already yield a percentage; this is mostly a labelling + two-level (display vs weight) refinement, **not** a scorer rewrite.

### US-201: Rich item management + per-item config
**As a** form author **I want** each evaluation item to carry weight, display %, responsible role,
type, evidence rules, edit-lock rules, calc method, min %, and internal notes.
- THE SYSTEM SHALL add to each item: `responsible_role`, `item_type` (manual|auto|evidence_only|mixed),
  `evidence_needs_approval`, `editable_after_review`, `editable_after_approval`, `calc_method`
  (manual|auto_platform|after_evidence|external), `min_percentage`, `internal_notes`.
- THE SYSTEM SHALL keep existing fields (weight, is_required, needs_evidence, evidence_required, status).

### US-202: One shared evaluation, items assigned per role
**As an** evaluator **I want** to see only the items assigned to my role within the **single** shared
evaluation of a teacher (not a separate evaluation per evaluator).
- THE SYSTEM SHALL represent a teacher's evaluation for a period as ONE evaluation entity composed of items.
- THE SYSTEM SHALL assign each item (group) to a responsible role; each evaluator sees/edits only their items.
- WHERE a user has "view all items" permission THE SYSTEM SHALL show all items read-only.
- THE SYSTEM SHALL compute the final % from all items once every responsible role's items are in.

### US-203: Item/evaluation states with lock rules
- THE SYSTEM SHALL support states: draft, completed, pending_review, approved, rejected — at **item** granularity (not only whole-evaluation).
- WHEN an item is sent for review THE SYSTEM SHALL lock it (no edits to value or evidence by the submitter).
- WHEN an item is approved THE SYSTEM SHALL freeze its percentage + stamp approver/time; edits require "edit after approval" permission.

### US-204: Evidence approval gating
- THE SYSTEM SHALL give each evidence a status: uploaded, pending_approval, approved, rejected, needs_edit.
- IF an item's `evidence_needs_approval` THEN THE SYSTEM SHALL NOT count the item's percentage until its evidence is approved.
- THE SYSTEM SHALL support evidence types: file, image, pdf, link, document, system-evidence, auto-platform (platform points, educational outcome, student-satisfaction survey, weekly-plan execution, attendance).

### US-205: Configurable educational-outcome calculation
- THE SYSTEM SHALL provide a setting "educational-outcome averaging method" at company/complex/school level (per permission).
- THE SYSTEM SHALL support methods: average over all students (absentees=0) vs average over attendees only (+ other documented variants).
- THE SYSTEM SHALL support outcome sources: external platforms (الأول، أنا والقدرات) import + internal/manual/imported results.

### US-206: Job-performance add/edit screen
- THE SYSTEM SHALL present an add/edit screen with: company/complex/school/stage/section/subject/teacher/period/date/form/overall-status, and an items table showing name, description, responsible role, weight, display %, computed %, type, manual/auto, evidence flags, evidence approved, evidence button, notes.

### US-207: General-manager screen as one shared entity
- THE SYSTEM SHALL treat the GM screen's row as the single shared evaluation with items distributed across responsibles.
- THE SYSTEM SHALL provide the full filter set (org hierarchy, role, responsible, evaluator, states, period, date range, final % range, evidence flags, missing items, needs-review, not-started, uncomputed outcome) + top stats.

### US-208/210: Granular permissions
- THE SYSTEM SHALL define granular permissions: view/create/edit/delete-unapproved evaluation; view-all-items vs view-my-items; evaluate/edit my item; send-to-review; approve/reject/return item; approve/reject final; reopen after submit; edit after approval; manage item weights; manage forms; manage+recompute outcome; upload/approve/reject/delete evidence; delete others' evidence; edit evidence after approval; view audit; export; print.

### US-209: Activity & audit log
- THE SYSTEM SHALL log every sensitive operation (create/edit eval, enter/edit item %, draft/submit/approve/reject/return item, upload/edit/delete/approve/reject evidence, change weight, change responsible, change/recompute outcome, approve/reject final, reopen, edit-after-approve) with: operation, user, role, datetime, evaluation, item, old value, new value, reason, IP.

### US-211/212: Noor model
- THE SYSTEM SHALL support a "Noor system" evaluation model/template variant (details in cards #211/#212 — to be detailed at implementation).

## Non-Functional Requirements
- **NFR-compat**: MUST NOT break the deployed engine. All schema changes additive with safe defaults; existing evaluations keep scoring. The shared-evaluation model (US-202) is introduced behind item-assignment without deleting the per-evaluator path until parity is verified.
- **NFR-tenant**: every query filters by `school_id` via repositories (existing `HasSchoolScope`).
- **NFR-deploy**: deliver in deployable, individually-verifiable slices; one Trello card → testing per slice where possible.
