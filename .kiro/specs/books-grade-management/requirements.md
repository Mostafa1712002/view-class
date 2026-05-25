# Requirements: Books — Grade Management (إدارة كتب الصفوف)

## Overview
Trello card "الكتب" (6a0b0e38049ba5762ccaff81). The existing Books page only adds ONE
book at a time. The card asks for a bulk management screen (like the legacy `books/add_books`)
to link books to the grades (صفوف) of the active school, organised by educational stage
(مرحلة) → grade (صف) → available books as checkboxes, saved via a transaction into a
`school_grade_books` pivot.

Mapping (from codebase + live DB inspection):
- مرحلة (stage)  = `Section` (school-scoped via `sections.school_id`)
- صف (grade)     = `ClassRoom` / `classes` (belongs to a section)
- الكتب المتاحة  = ministry books (`school_id IS NULL`) ∪ the active school's own active books
- pivot          = `school_grade_books (school_id, class_id, book_id)` unique triple

## User Stories

### US-001: Open grade-books management for the active school
**As a** system admin (super-admin / school-admin)
**I want to** open a page that lists my school's stages, the grades under each, and the
available books as checkboxes under each grade
**So that** I can link/unlink several books to several grades at once.

**Acceptance Criteria:**
- WHEN an admin opens `/manage/books/grades` THE SYSTEM SHALL resolve the active school via the school scope.
- WHEN no school is resolvable (super-admin with no scope) THE SYSTEM SHALL show an empty state asking to pick a school, not crash.
- WHEN the active school has stages and grades THE SYSTEM SHALL render each stage as an accordion section, each grade under it, and the available books as checkboxes under each grade.
- WHEN a book is already linked to a grade THE SYSTEM SHALL render its checkbox checked.

### US-002: Save the school ↔ grade ↔ book mapping
**As a** system admin
**I want to** check/uncheck books per grade and press Save
**So that** the links are persisted only for my current school.

**Acceptance Criteria:**
- WHEN the admin presses Save THE SYSTEM SHALL replace the active school's grade↔book links with the submitted selection inside ONE DB transaction.
- IF any error occurs during save THEN THE SYSTEM SHALL roll back so no partial save happens.
- THE SYSTEM SHALL never touch links belonging to other schools.
- THE SYSTEM SHALL prevent duplicate links via a unique index on (school_id, class_id, book_id).

### US-003: Convenience controls
**As a** system admin
**I want to** per-grade "select all" / "clear all" buttons and an accordion layout
**So that** a long page stays manageable.

**Acceptance Criteria:**
- WHERE a grade has books THE SYSTEM SHALL offer per-grade "تحديد الكل" / "إلغاء التحديد" buttons.
- THE SYSTEM SHALL show clear success / error flash messages after save.

## Non-Functional Requirements
- NFR-001 Multi-tenant: every query filters by the active school_id; save affects only that school.
- NFR-002 Theme: light theme, Bootstrap 4 (+ existing module class conventions), responsive.
- NFR-003 Architecture: module pattern (Action + Repository interface), ApiResponse only for /api/*.
