# Requirements: Redesign Slice 1 — Login + Role Dashboards

## Overview
Apply the Al-Awwal "luxury dark + gold" brand to authentication and the five role dashboards (Admin, Manager, Teacher, Student, Parent) per Trello card *الديزاين* (`69fc731114265ca3fa23a0fb`). This is the first of five slices in the platform-wide design overhaul.

## User Stories

### US-001: Branded login page
**As a** logged-out user
**I want to** see a luxury, on-brand login page
**So that** my first impression of the platform matches the new identity

**Acceptance Criteria:**
- WHEN the visitor opens `/login` THE SYSTEM SHALL render a dark-gradient page background with gold radial highlights.
- WHEN the form is rendered THE SYSTEM SHALL show a glass-morphism card (translucent, blurred, gold-tinted border) carrying the form.
- WHEN the locale is `ar` THE SYSTEM SHALL apply Cairo as primary type and keep `dir="rtl"`.
- WHEN the locale is `en` THE SYSTEM SHALL apply Playfair Display on headings and Inter (or system sans) on body.
- WHEN the user hovers the submit button THE SYSTEM SHALL animate a subtle lift (`translateY(-1px)`) with gold shadow.

### US-002: Branded admin / manager / teacher dashboard
**As an** admin, manager, or teacher
**I want to** see KPI cards and recent-activity panels in the new luxury style
**So that** the management surface feels premium and aligns with the brand

**Acceptance Criteria:**
- WHEN any of these roles open the dashboard THE SYSTEM SHALL render a dark page background (no plain white).
- WHEN KPI cards render THE SYSTEM SHALL show them as glass cards (translucent surface, blurred backdrop, gold-tinted border, light text).
- WHEN stat numbers render THE SYSTEM SHALL emphasise them with the gold palette.
- WHEN tables/lists render THE SYSTEM SHALL keep them legible on dark surface (light text, dark-tinted thead).

### US-003: Branded student dashboard
**As a** student
**I want to** see my grades / attendance / schedule snapshots in the new style
**So that** my view of the platform matches the rest of the new identity

**Acceptance Criteria:**
- WHEN the student opens `/student/dashboard` THE SYSTEM SHALL apply the same luxury theme as US-002.
- WHEN performance data renders THE SYSTEM SHALL keep all existing metrics intact (visual change only).

### US-004: Branded parent dashboard
**As a** parent
**I want to** see my children's summaries in the new style
**So that** my view aligns with the new platform identity

**Acceptance Criteria:**
- WHEN the parent opens `/parent/dashboard` THE SYSTEM SHALL apply the luxury theme.
- WHEN child cards render THE SYSTEM SHALL surface name + key stats with gold accents.

## Non-Functional Requirements

### NFR-001: Visual consistency
- Every restyled surface SHALL use the existing `--gold-*` and `--black-*` tokens declared in `layouts/app.blade.php`.
- The theme SHALL be opt-in via a body class so non-Slice-1 pages remain on the existing light theme.

### NFR-002: Responsive
- Restyled surfaces SHALL render correctly on viewports as small as 375 px wide and as wide as 1440 px (matching the existing responsive pass).

### NFR-003: Functional preservation
- No controller, repository, route, or migration SHALL be modified for Slice 1. View + CSS only.
- All existing data bindings and links SHALL continue to function.

### NFR-004: Direction
- Both `ar` (RTL) and `en` (LTR) SHALL render the new theme correctly.
