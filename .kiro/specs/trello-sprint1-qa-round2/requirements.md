# Requirements: Sprint 1 QA Round 2 — Header/Sidebar + Dashboard Home Gaps

Source cards (فيوكلاس board):
- Card A `69e77711c08cb2b13e0cbb76` — تعديل الهيدر والقائمة الجانبية
- Card B `69e77784c1941782dff3a782` — تعديل الصفحة الرئيسية

## US-A1: Header — Role + Scope Selectors
**As a** logged-in admin
**I want** to see and change my active role and scope (company → school → semester) from the header
**So that** all subsequent data reflects my chosen scope

**Acceptance:**
- WHEN an authenticated user loads any shell page THE SYSTEM SHALL render a role selector in the header showing their current role.
- WHEN the user opens the scope selector THE SYSTEM SHALL present:
  - a list of educational companies they have access to
  - after a company is chosen, a list of schools under that company (plus an "all schools" option)
  - after a school is chosen, a list of academic years/semesters for that school (plus an "all" option)
- WHEN a scope value changes THE SYSTEM SHALL persist it to the user's session and reload the page.

## US-A2: Header — Profile Font Size
**As a** user
**I want** to change the site font size from my profile dropdown
**So that** the UI is more accessible

**Acceptance:**
- WHEN the user opens the profile dropdown THE SYSTEM SHALL show a "حجم الخط" submenu with small/medium/large options.
- WHEN the user selects a font size THE SYSTEM SHALL apply it immediately and persist the choice (localStorage + session).

## US-A3: Sidebar — 4-Section Reorganisation
**As a** user
**I want** the sidebar to be grouped into the four QA-specified sections with the exact items listed
**So that** the information architecture matches the spec

**Acceptance:**
- WHEN an authenticated user loads any shell page THE SYSTEM SHALL render the sidebar with four top-level groups:
  1. برامج نوعية
  2. عمليات تعليمية
  3. عمليات تواصل
  4. إعدادات النظام
- WHEN an item in the spec has sub-items THE SYSTEM SHALL render it as a collapsible group containing those sub-items.
- WHERE a real route exists for a menu entry THE SYSTEM SHALL link to it; WHERE no route exists yet THE SYSTEM SHALL render a disabled/placeholder link (`#`) with the correct label.

## US-B1: Dashboard — Section 2 Interaction Rates
**As a** super admin
**I want** to see login and interaction rate progress bars on the dashboard
**So that** I can gauge engagement at a glance

**Acceptance:**
- WHEN the dashboard loads THE SYSTEM SHALL render a section containing five horizontal progress bars (students login, teachers login, parents login, student→teacher interaction, student→content interaction).
- `GET /api/dashboard/interaction-rates` SHALL return `{ studentsLoginRate, teachersLoginRate, parentsLoginRate, studentTeacherInteraction, studentContentInteraction }` as 0–100 numbers (0 placeholders for now).

## US-B2: Dashboard — Section 3 Content Stats
**Acceptance:**
- WHEN the dashboard loads THE SYSTEM SHALL render counter tiles for: e-tests count, e-assignments count, videos/files count, interaction rate %, watch rate %, interaction count, test submissions, assignment submissions, SMS usage.
- `GET /api/dashboard/content-stats` SHALL return the same keys (all 0 for now).

## US-B3: Dashboard — Section 4 Various Stats
**Acceptance:**
- WHEN the dashboard loads THE SYSTEM SHALL render counter tiles for: discussion rooms, student absences, lesson plans, questions, virtual classes, scheduled virtual classes.

## US-B4: Dashboard — Section 5 Weekly Absence Chart
**Acceptance:**
- WHEN the dashboard loads THE SYSTEM SHALL render a line/bar chart (7 days) using a chart library already bundled or CDN-loadable.
- `GET /api/dashboard/weekly-absence` SHALL return `{ days: [{ day, rate }, ...] }` with seven entries.

## US-B5: Dashboard — Section 6 Most Active
**Acceptance:**
- WHEN the dashboard loads THE SYSTEM SHALL render four lists/tables: active classes in school, active users in school, active classes in company, active users in company.
- `GET /api/dashboard/most-active` SHALL return `{ activeClassesInSchool: [], activeUsersInSchool: [], activeClassesInCompany: [], activeUsersInCompany: [] }` (empty arrays for now).

## US-B6: Dashboard — Section 7 Weekly Activity
**Acceptance:**
- WHEN the dashboard loads THE SYSTEM SHALL render a line chart with three series: parents login rate, students login rate, teachers login rate (weekly).
- `GET /api/dashboard/weekly-activity` SHALL return `{ parentsRate: 0, studentsRate: 0, teachersRate: 0 }` (or richer shape with per-day series).

## NFR
- All new endpoints ride the JWT middleware already in use.
- No architectural deviations from CLAUDE.md (Module + Repository + Action patterns).
- Follows multi-tenant rule: queries filtered by resolved `school_id` in repositories.
