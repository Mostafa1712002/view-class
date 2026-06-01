# Requirements: Card #92 — المدارس (Schools module fixes + audit)

Trello card `6a1c3262eecbfb9772462c85` ("المدارس"). The Schools module already
exists (academic years/terms/weeks, grade-levels/sections, classes, students,
permissions, copy). This card has **3 concrete reproducible bugs** plus a broad
"audit the whole module" request. This spec covers the 3 concrete bugs first
(the explicit, verifiable asks); remaining audit gaps are tracked in tasks.md.

## User Stories

### US-001: Arabic labels in school permissions main-functions column
**As a** system admin using an Arabic-language system
**I want** the "الوظائف الرئيسية" (main functions) column to show Arabic labels
**So that** it is consistent with the rest of the (Arabic) UI.

**Acceptance Criteria:**
- WHEN an admin opens `/admin/schools/{id}/permissions` THE SYSTEM SHALL render each main-function group with an Arabic label (e.g. `academic-years` → "السنوات الدراسية"), not the raw English key.
- WHEN the system locale is English THE SYSTEM SHALL render the English label.
- WHEN a group has no mapped label THE SYSTEM SHALL fall back to the raw key (no blank).

### US-002: Books button on grade-levels actions
**As a** school admin
**I want** a "الكتب" (books) button in the grade-levels actions column
**So that** I can reach books management for this school's grades.

**Acceptance Criteria:**
- WHEN an admin opens `/admin/schools/{id}/grade-levels` THE SYSTEM SHALL show a "الكتب" button in each row's actions column.
- WHEN the admin clicks it THE SYSTEM SHALL open the books-per-grade management page scoped to THIS school.

### US-003: Edit + View buttons on classes actions
**As a** school admin
**I want** "تعديل" (edit) and "عرض" (view) buttons in the classes actions column
**So that** I can edit a class and view its details, not only manage students / delete.

**Acceptance Criteria:**
- WHEN an admin opens `/admin/schools/{id}/grade-levels/{section}/classes` THE SYSTEM SHALL show تعديل and عرض buttons per class row, alongside the existing students and delete actions.
- WHEN the admin clicks تعديل THE SYSTEM SHALL open a pre-filled edit form (name, grade number, capacity, lead teacher, academic year).
- WHEN the admin submits the edit form with valid data THE SYSTEM SHALL update the class and return to the classes list with a success message.
- WHEN the admin clicks عرض THE SYSTEM SHALL open a read-only class details page.
- WHEN a class/section does not belong to the school THE SYSTEM SHALL return 404.

## Non-Functional Requirements

### NFR-001: Multi-tenant integrity
- All class/section operations SHALL verify section→school and class→section ownership (abort 404 otherwise), matching existing controller guards.

### NFR-002: Bootstrap 4 markup
- New views/buttons SHALL use Bootstrap 4 markup (the admin theme is BS4 + jQuery 3.2.1), not BS5.

### NFR-003: No regression to Books module
- The `?school=` override on the books-grades page SHALL be additive; default behaviour (no param) SHALL be unchanged.
