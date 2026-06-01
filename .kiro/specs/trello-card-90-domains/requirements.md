# Requirements: Card #90 — المواد والمجالات (Subject Domains)

Trello card `6a1bfc2b8cc8e3063045b57a`. The "المجالات والمعايير" item in the
`/admin/subjects` actions dropdown is a dead link (`href="#"`, `disabled`).
Build the Domains (المجالات) management feature it should open, scoped per
subject. (The wider "ensure subjects page complete" is mostly already built —
create form, Excel import, ready-made templates all exist; audit only.)

## User Stories

### US-001: Open subject domains
**As a** system admin
**I want** the "المجالات والمعايير" action to open the domains page for that subject
**So that** I can manage the subject's internal domains.

**Acceptance Criteria:**
- WHEN an admin clicks "المجالات والمعايير" for a subject THE SYSTEM SHALL open `/admin/subjects/{id}/domains`.
- WHEN a subject does not belong to the active school THE SYSTEM SHALL return 404.

### US-002: List + add domains
**As a** system admin
**I want** to see the subject's domains in a table and add a new one
**So that** I can organise the subject.

**Acceptance Criteria:**
- WHEN the domains page loads THE SYSTEM SHALL list the subject's domains (columns: المجالات / القالب / التحكم) or an empty-state.
- WHEN the admin opens "إضافة مجال" and submits a name THE SYSTEM SHALL create the domain under the current subject and show it in the table.
- WHEN the name is empty THE SYSTEM SHALL reject the save (required).
- THE SYSTEM SHALL scope domains to their subject (a subject's domains never appear under another subject).

### US-003: Edit + delete domain
**Acceptance Criteria:**
- WHEN the admin edits a domain THE SYSTEM SHALL update its name.
- WHEN the admin deletes a domain THE SYSTEM SHALL remove it after a confirmation.

### US-004: Domains tree
**Acceptance Criteria:**
- WHEN the admin opens "شجرة المجالات" THE SYSTEM SHALL show the domains under a root node (or an empty root when none).

## Non-Functional
- NFR-1: Bootstrap 4 markup (theme is BS4 + jQuery 3.2.1).
- NFR-2: Multi-tenant — resolve subject via `findScoped($id, activeSchoolId())`.
- NFR-3: Soft-deletes on domains (tenant-owned entity).
