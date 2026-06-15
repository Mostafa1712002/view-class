# Requirements: Support / Tickets (Trello #267, card NMUP6iOz)

## Overview
Extend the existing `app/Modules/Support` module into a complete support-ticket
experience: create ticket (with type, department, priority, problem link,
attachment), list + stat-card filtering, view + reply thread, status workflow
(open / in_progress / resolved / closed), assignment, close / reopen / delete,
status-change log, notifications, permission gating, and school+user scoping.

EXTEND the existing tables (`support_tickets`, `support_ticket_replies`).
Additive migrations only. The 9 seeded `support.*` permission slugs are the fixed
vocabulary (seeder is not touched).

## User Stories

### US-001: Create a ticket
**As a** platform user (staff/teacher/student/parent)
**I want to** open a support ticket with a type, department, priority, title,
description, optional problem link and attachment
**So that** the support team can triage and respond.

**Acceptance Criteria:**
- WHEN a user submits the create form with required fields THE SYSTEM SHALL create
  a ticket scoped to the active school and `created_by` = the user.
- WHEN an attachment is supplied THE SYSTEM SHALL store it and persist its path.
- WHEN the ticket is created THE SYSTEM SHALL log the activity and notify staff.

### US-002: List + stat-card filtering (admin)
**As a** support agent / admin
**I want to** see stat cards (new / in-progress / admin-replied / user-replied /
closed) with totals, and click a card to filter the table
**So that** I can triage by state.

**Acceptance Criteria:**
- WHEN the admin index loads THE SYSTEM SHALL show counts per state out of the total.
- WHEN a stat card is clicked THE SYSTEM SHALL filter the table to that state.
- "admin-replied" / "user-replied" are DERIVED from the last reply's `is_staff`
  (not enum statuses). The canonical status enum stays open/in_progress/resolved/closed.

### US-003: View + reply thread
**As a** user or agent
**I want to** view ticket details, attachments, the conversation, and post a reply
**So that** the issue can be resolved.

**Acceptance Criteria:**
- WHEN a reply is posted THE SYSTEM SHALL append it, bump `last_reply_at`, log the
  activity, and notify the other party.
- WHEN a ticket is resolved/closed THE SYSTEM SHALL prevent further replies in the UI.

### US-004: Status workflow + assignment
**As an** agent
**I want to** change status, assign the ticket, close, reopen, and delete it
**So that** I can run the workflow.

**Acceptance Criteria:**
- WHEN status changes THE SYSTEM SHALL record a status-log row (old→new, by, at)
  and notify the creator.
- WHEN a ticket is closed/reopened THE SYSTEM SHALL update status + log + notify.
- WHEN delete is invoked with `support.delete` THE SYSTEM SHALL soft-delete it.

### US-005: Permissions + scope
**As the** platform
**I want** every action gated by `support.*` (canDo + route middleware) and every
query school-scoped via `scopedSchoolId()` (fail-closed; null = super-admin only)
**So that** tenants are isolated and roles are enforced.

**Acceptance Criteria:**
- WHEN a non-super-admin without the needed grant attempts a write THE SYSTEM SHALL 403.
- WHEN a super-admin (null school) loads the admin index THE SYSTEM SHALL show all
  schools' tickets (200, no crash).
- WHEN a normal user loads `my/support` THE SYSTEM SHALL show only their own tickets.

## Non-Functional
- NFR-001: RTL Arabic UI, design-system.css, x-svg-icon, empty/loading states.
- NFR-002: Keep the existing support flow working (non-regression).
