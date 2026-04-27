# Requirements: Sprint 3 — Users Module

## Overview
Sprint 3 adds the human side of the platform: students, parents, teachers, admins, and printable user-cards. All five user types share one `users` table, separated by role (super-admin, school-admin, teacher, parent, student, plus admin sub-roles defined by `job_titles`). Each list page sits behind a sidebar mega-menu and supports search, bulk operations, and CRUD via Add ▼ menus. System administrators can also "login as" any user for support purposes.

## User Stories

### US-301: Sidebar Users mega-menu
**As a** school administrator
**I want to** see a "Users" group in the sidebar with sub-items for Students / Parents / Teachers / Admins / Cards
**So that** I can reach every user-management screen from one consistent place.

**Acceptance Criteria:**
- WHEN the sidebar renders THE SYSTEM SHALL show a Users mega-menu with at least 5 child links: Students, Parents, Teachers, Admins, User Cards.
- WHEN a user lacks `school-admin` or `super-admin` role THE SYSTEM SHALL hide the entire Users group.
- WHEN the active page lives under `/admin/users/*` THE SYSTEM SHALL highlight the matching child link.

---

### US-302: Students index
**As a** school admin
**I want to** see all my school's students in a searchable list with bulk actions
**So that** I can manage hundreds of records without clicking each one.

**Acceptance Criteria:**
- WHEN I open `/admin/users/students` THE SYSTEM SHALL list students scoped to my `school_id` ordered by name.
- WHEN I type in the search bar (national-id / name / username / login) THE SYSTEM SHALL filter the table within 300 ms (debounced GET, no full reload).
- WHEN the table renders THE SYSTEM SHALL show columns: checkbox, national-id, name, grade level, class, gender, last activity, control menu.
- WHEN I open the row "..." menu THE SYSTEM SHALL list at least: Login as, Parents, Schedule, Classes, Absences, Behavior, Medical record.
- WHEN I open the "Add ▼" button THE SYSTEM SHALL list: Add student (manual), Excel import, Noor import, Refresh status, Bulk photo upload.
- WHEN I open "Other Options ▼" THE SYSTEM SHALL list: Graduates / Delete graduates / Advanced list / Counts / Unlinked-to-parents / Global search.
- WHEN I select rows + open "Operations ▼" THE SYSTEM SHALL allow: Hide grades, Show grades, Hide report, Show report, License, Unlicense, Set waiting.

---

### US-303: Student CRUD
**As a** school admin
**I want to** create, edit, view, and soft-delete a student
**So that** the roster reflects reality.

**Acceptance Criteria:**
- WHEN I submit `Add student` THE SYSTEM SHALL persist a `users` row with role `student` and the active `school_id`.
- WHEN I edit a student THE SYSTEM SHALL load existing values into the form and only update changed fields.
- WHEN I delete a student THE SYSTEM SHALL soft-delete (set `deleted_at`) and preserve their `parent_student` and `class_student` history.

---

### US-304: Login-as
**As a** super-admin or school-admin (with permission)
**I want to** assume a user's identity for support
**So that** I can reproduce their reported issues.

**Acceptance Criteria:**
- WHEN I click "Login as" on any user row AND I hold `super-admin` THE SYSTEM SHALL switch the active session to that user and remember my original id.
- WHILE I am impersonating THE SYSTEM SHALL show a top banner with "Stop impersonating" link.
- WHEN I click "Stop impersonating" THE SYSTEM SHALL restore my original session.

---

### US-305: Parents index + linking
**As a** school admin
**I want to** see parents and their children, and link/unlink relationships
**So that** parents see only their own kids' data.

**Acceptance Criteria:**
- WHEN I open `/admin/users/parents` THE SYSTEM SHALL show columns: checkbox, name, username, national-id, gender, mobile, children-count, last activity, control menu.
- WHEN I open a parent's "..." menu THE SYSTEM SHALL list: Students (linked), Permissions, Login as.
- WHEN I open `Students` for a parent THE SYSTEM SHALL allow linking/unlinking via a many-to-many UI scoped to the same school.

---

### US-306: Teachers index + workloads
**As a** school admin
**I want to** see teachers with their employee-id and specialty, and a workload view
**So that** I can assign classes fairly.

**Acceptance Criteria:**
- WHEN I open `/admin/users/teachers` THE SYSTEM SHALL show columns: checkbox, name, username, national-id, employee#, specialization, last activity, control menu.
- WHEN I click "Workloads" THE SYSTEM SHALL show each teacher with their assigned classes count + total weekly periods.
- WHEN I edit a teacher THE SYSTEM SHALL allow assigning roles via a multi-select.

---

### US-307: Admins index + Job titles
**As a** school admin
**I want to** add multiple kinds of administrators (school manager, supervisor, counselor, clinic, canteen…) and manage that vocabulary
**So that** I can describe my org chart precisely.

**Acceptance Criteria:**
- WHEN I open `/admin/users/admins` THE SYSTEM SHALL show columns: name, username, role/job-title, last activity, control menu.
- WHEN I click "Add ▼" THE SYSTEM SHALL list every active job_title plus a "Manage job titles" link.
- WHEN I open "Manage job titles" THE SYSTEM SHALL allow CRUD on `job_titles` (name_ar, name_en, slug, is_active).
- WHEN I open a supervisor's "..." menu THE SYSTEM SHALL allow assigning the teachers they supervise.
- WHEN I open a counselor's "..." menu THE SYSTEM SHALL allow assigning the students they counsel.

---

### US-308: User cards (PDF)
**As a** school admin
**I want to** print user cards for students+parents and for teachers+admins
**So that** I can hand each user their login credentials.

**Acceptance Criteria:**
- WHEN I open `/admin/users/cards` THE SYSTEM SHALL render two tabs: Students+Parents, Teachers+Admins.
- WHEN I select a class + click `Generate` THE SYSTEM SHALL stream a PDF with one card per user (username, password, platform name, URL; +grade+class for students).
- WHILE generating THE SYSTEM SHALL not print stored hashes — passwords are only available on initial creation; if no plain password is stored a placeholder ("set by user") is shown.

## Non-Functional Requirements

### NFR-301: Multi-tenant scope
Every Users query must filter by the active user's `school_id` (super-admins use the active scope from session).

### NFR-302: Soft delete
All user rows are soft-deleted; restore is out of scope but record retained.

### NFR-303: i18n
Every label must come from `lang/{ar,en}/users.php` — no hard-coded strings.

### NFR-304: Bulk performance
List pages must render ≤ 50 rows per page; server-side pagination is mandatory once a school exceeds 50 of any role.

### NFR-305: Security
Login-as is gated by `super-admin` only; impersonation is logged in `activity_logs`.
