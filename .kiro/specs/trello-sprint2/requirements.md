# Requirements: Trello Sprint 2 — School Setup

Source list: https://trello.com/b/WBHlx52A — list "sprint 2 prompt" (5 cards)

## Card 20 — Sprint 2 / Schools enhancement
**As a** system admin
**I want to** view and manage all schools with full meta and a control menu per school
**So that** I can configure each tenant before adding users

**Acceptance:**
- WHEN admin opens `/admin/schools` THE SYSTEM SHALL show a table with: name, sort_order, sections count, classes count, students count, licensed students count, control menu (settings / years / grades / permissions / show / edit / delete / dropdown).
- WHEN admin clicks "Add school" THE SYSTEM SHALL show a form with: name_ar*, name_en*, branch, sort_order, educational_track, stage*, logo, city*, default_language*.
- WHEN admin opens a school's control menu THE SYSTEM SHALL link to: general settings, academic years, grade levels, permissions.

## Card 21 — General school settings
**As a** school admin
**I want to** configure all school-wide settings in one place
**So that** the school behaves the way we want.

**Acceptance:**
- WHEN admin opens `/admin/schools/{id}/settings` THE SYSTEM SHALL show grouped sections: school info, social, user-edit permissions, weekly plan, classes, attendance, discussion rooms, grade reports, messages, virtual classes, WhatsApp.
- WHEN admin saves THE SYSTEM SHALL persist values into `schools.settings` JSON.

## Card 22 — Academic years
**As a** school admin
**I want to** create academic years with terms and weeks, mark current, and promote to next year
**So that** the calendar drives schedules and reports.

**Acceptance:**
- WHEN admin opens `/admin/academic-years` THE SYSTEM SHALL show current year + terms with start/end + actions.
- WHEN admin clicks "Add academic year" THE SYSTEM SHALL accept name + start/end and inline term creation.
- WHEN admin clicks "Set current" on a term THE SYSTEM SHALL mark exactly one term per school as current.
- WHEN admin clicks "Promote" THE SYSTEM SHALL stub the action (full migration logic out of scope, but the route + UI exist).

## Card 23 — Grade levels & sections
**As a** school admin
**I want to** define which grades each school offers and manage sections inside each grade
**So that** students can be enrolled into the right section.

**Acceptance:**
- WHEN admin opens `/admin/schools/{id}/grade-levels` THE SYSTEM SHALL show the grades enabled for that school + an "Assign" button to enable/disable grades.
- WHEN admin opens a grade's sections THE SYSTEM SHALL show: section name, grade, capacity, vacancies, student count, control menu.
- WHEN admin clicks "Add section" THE SYSTEM SHALL accept: name, grade, lead teacher (optional), max capacity.
- WHEN admin opens a section's students THE SYSTEM SHALL show student rows with id, name, grade, section, gender + edit/transfer actions.

## Card 24 — Permissions matrix
**As a** school admin
**I want to** assign granular function permissions per role
**So that** each role only sees what it should.

**Acceptance:**
- WHEN admin opens `/admin/schools/{id}/permissions` THE SYSTEM SHALL show three columns: roles, main functions, sub-functions (checkboxes).
- WHEN admin toggles a checkbox THE SYSTEM SHALL persist immediately (auto-save) without a save button.
- WHEN admin clicks "Copy permissions" THE SYSTEM SHALL copy from one school to another.

## Non-functional
- All pages bilingual (AR + EN) via `lang/{ar,en}/`.
- All new code follows Module + Repository + Action patterns under `app/Modules/`.
- Multi-tenant scope enforced in repositories.
- No raw `dd`, no TODOs, no mock data.
