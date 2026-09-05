# Requirements: المواد والمسارات (rename + tabs)

## Overview
Card "صفحة شعب المواد" (creator: Mohamed Farouk). Rename "شعب المواد" → "المواد والمسارات" and
present it as one page with two tabs: **المواد** (subjects) and **المسارات** (the existing subject_tracks).

## Grounding (already exists)
- Subjects: `admin.subjects.index` (`app/Modules/Subjects/Controllers/SubjectController.php`).
- Tracks (= current شعب المواد): `admin.subject-tracks.index`, model `App\Models\SubjectTrack` (table
  `subject_tracks`), views `resources/views/admin/subjects/tracks/*`. Label from `lang/ar/subject_tracks.php:4`.
- Sidebar link at `resources/views/components/sidebar.blade.php:562`.
- Route-tab pattern to reuse: `resources/views/admin/reports/_tabs.blade.php`.

## Acceptance criteria
- WHEN admin opens the page THE SYSTEM SHALL show title "المواد والمسارات" with two tabs.
- WHEN "المواد" tab active THE SYSTEM SHALL show the subjects list (existing subjects UI).
- WHEN "المسارات" tab active THE SYSTEM SHALL show the tracks list (existing subject_tracks UI).
- Existing subjects + tracks CRUD keep working; school scope preserved.
- No data migration; both datasets already exist.

## Decision
Route-based tabs (each tab = its own route + active highlight), reusing `admin/reports/_tabs.blade.php`
idiom. Add a shared `_tabs` partial rendered by both subjects index and tracks index. Update the sidebar
label + the single "إدارة المواد" grouping accordingly (do not break the sidebar-restructure spec's items).
