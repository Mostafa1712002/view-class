# Requirements: Parent CRM / Relationship Management (Sprint 10, Trello #269)

## Overview
An internal CRM layer over the existing Communications "parents-as-contact" page. It documents
complaints, school visits and scheduled calls per parent, surfaces interaction counts in the
parent list, and renders a unified timeline (CRM records + mail + WhatsApp + notifications).
No new permissions, no new module — extends `app/Modules/Communications`.

## User Stories

### US-001: Parent list with relationship data
**As a** school staff member with `parents_contact.view`
**I want to** see a parents list that includes complaint / visit / call counts
**So that** I can gauge each parent's relationship history at a glance.

**Acceptance Criteria:**
- WHEN I open `admin.parents-contact.index` THE SYSTEM SHALL show, per parent, the count of complaints, visits and scheduled calls (school-scoped) alongside the existing children/mail/whatsapp/notification counts.
- WHEN I am a super-admin with no active school THE SYSTEM SHALL show all schools' data (null scope).
- WHEN I am a non-super-admin resolving to a null school THE SYSTEM SHALL `abort(403)`.

### US-002: Parent CRM detail with 3 tabs + timeline
**As a** staff member
**I want to** open a parent's CRM profile with complaints, visits and scheduled-call tabs plus a unified timeline
**So that** I can review and act on the full relationship.

**Acceptance Criteria:**
- WHEN I open `admin.parents-contact.show` THE SYSTEM SHALL render tabs: شكاوى، زيارات مدرسة، اتصالات مجدولة، الخط الزمني.
- THE SYSTEM SHALL list each tab's records school-scoped and newest-first.
- THE timeline SHALL merge complaints, visits, calls, mail, WhatsApp and notifications in one chronological feed.

### US-003: Add complaint
**As a** staff member with `parents_contact.manage`
**I want to** add a complaint (نوع، تاريخ، غرض، تفاصيل، إجراء مطلوب، أولوية، موظف مسؤول، حالة، مرفق)
**So that** complaints are tracked with a code and status.

**Acceptance Criteria:**
- WHEN I submit a valid complaint THE SYSTEM SHALL persist it with a generated complaint code, denormalized `school_id`, and log the create in `ActivityLog`.
- IF I lack `parents_contact.manage` THEN the store endpoint SHALL `abort(403)` (backend re-check, not UI-only).
- WHEN I provide an attachment THE SYSTEM SHALL store it and keep `attachment_path`.

### US-004: Add school visit
**As a** staff member with `parents_contact.manage`
**I want to** record a visit (تاريخ، وقت، سبب، طالب مرتبط، الموظف المقابل، ملخص، الإجراء التالي، تاريخ المتابعة، الحالة)
**So that** in-person visits are documented.

**Acceptance Criteria:**
- WHEN I submit a valid visit THE SYSTEM SHALL persist it school-scoped and log the create.

### US-005: Add scheduled call
**As a** staff member with `parents_contact.manage`
**I want to** record a call (تاريخ، وقت، نوع، غرض، نتيجة، هل تم الرد، ملاحظات، موعد متابعة، موظف مسؤول)
**So that** phone outreach is tracked with follow-up.

**Acceptance Criteria:**
- WHEN I submit a valid call THE SYSTEM SHALL persist it school-scoped and log the create.

## Non-Functional Requirements

### NFR-001: Multi-tenant scope
- Every CRM query filters by `school_id` in the repository layer; null scope only for super-admins (fail-closed via `scopedSchoolId()`).

### NFR-002: Permissions (no new perms)
- Read pages gated by `parents_contact.view`; writes by `parents_contact.manage`, re-checked in controller.
- Routes keep `role:super-admin,school-admin` middleware (matches existing group).

### NFR-003: Additive schema only
- 3 new tables (`parent_complaints`, `parent_school_visits`, `parent_scheduled_calls`), soft deletes, no edits to existing tables. Migrations timestamped after `2026_06_16_710000`.

### NFR-004: UI
- design-system.css classes, `x-svg-icon`, RTL, mobile-friendly — reuse existing parents-contact look.
