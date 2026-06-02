# Requirements: استيراد الطلاب من ملف إكسل (Trello #108)

## Overview
The "استيراد من Excel" button on the students page wrongly routes to the Noor importer
(`/admin/noor?type=students`). Build a dedicated Excel-import feature that uses the
official `students_import.xlsx` template (30 English-headed columns), validates the rows,
shows a preview, imports the students, and records each operation in an archive.

## Reported bug (verified live 2026-06-02)
- As super-admin → الطلاب → "إضافة طالب" dropdown → "استيراد من Excel".
- It opens **"استيراد بيانات نظام نور"** (the Noor importer) instead of an Excel importer.

## User Stories

### US-001: Dedicated Excel import page
**As a** school admin / super-admin
**I want** a dedicated "رفع ملف إكسل" page reachable from the students screen
**So that** I can bulk-import students using the platform's own Excel template (not Noor).

**Acceptance Criteria:**
- WHEN I click "استيراد من Excel" THE SYSTEM SHALL open the Excel import page (not Noor).
- THE SYSTEM SHALL show: upload card, "أرشيف العمليات" link, steps, template download link,
  grade copy-buttons, and a columns reference table.

### US-002: Template download
**As a** user
**I want** to download the exact Excel template
**So that** my columns match what the importer expects.
- WHEN I click "تحميل نموذج الملف" THE SYSTEM SHALL download `students_import.xlsx`.

### US-003: Preview + validation before import
- WHEN I upload a filled template THE SYSTEM SHALL parse it and show a preview table.
- THE SYSTEM SHALL classify each row: new | update | duplicate | invalid, with a reason.
- THE SYSTEM SHALL mark invalid when a required field is missing
  (Identity Number, First Name, Last Name, Father Name).
- THE SYSTEM SHALL mark invalid when the Grade does not exist in the school,
  or the Class does not exist inside that grade.
- THE SYSTEM SHALL mark invalid when an explicit Username collides with an existing user.
- THE SYSTEM SHALL mark duplicate when the same Identity Number appears twice in the file.
- THE SYSTEM SHALL mark update when a student with that Identity Number already exists.

### US-004: Execute import
- WHEN I confirm THE SYSTEM SHALL create new students (+ profile, class link, parent links)
  and update existing students by Identity Number.
- IF a student already exists THEN THE SYSTEM SHALL NOT change their password.
- WHERE a Password cell is provided on a NEW student THE SYSTEM SHALL use it; otherwise
  it SHALL generate one from the Identity Number.

### US-005: Operations archive
- THE SYSTEM SHALL record every upload (file name, date, uploader, totals, status).
- THE SYSTEM SHALL let the user download a CSV error report for rows that failed.

## Non-Functional Requirements
### NFR-001 Multi-tenant
- Every lookup (dedupe, grade/class match, archive) SHALL be scoped by school_id.
### NFR-002 Safety
- Existing passwords SHALL never be overwritten (global hard rule).
- The Excel and Noor archives SHALL remain separate (no cross-contamination).
