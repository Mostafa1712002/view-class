# Requirements: Roles & Permissions Management

## Overview

The client wants a proper **roles + permissions management dashboard** (like the sibling
project `t3ahdo`, which uses Spatie laravel-permission). A QA card
(«صلاحيات المعلم لا تظهر عند وجهة المعلم», URLs `/teacher/exams` and
`/admin/libraries/public`) revealed the underlying gap: ViewClass already ships a custom
`Permission` / `Role` / `permission_role` model and a `User::canDo()` gate, but:

1. There is **no UI** to manage which permissions a role holds (permissions today are
   editable only *per job title*, not per role).
2. Module route groups such as **exams** (`/admin/exams`, `/teacher/exams`) and
   **libraries** (`/admin/libraries/public`, `/teacher/libraries`) are guarded by
   `role:super-admin,school-admin,teacher` middleware only — **any** teacher passes, so
   the granular `exams.*` / `libraries.*` permissions are **never enforced** there.

This feature adds a Roles management dashboard on top of the **existing** custom
permission system (reuse, do not rebuild), and closes the enforcement gap for exams and
libraries — **without locking out any currently-working user**.

### Non-goals (YAGNI)

- Not adopting Spatie laravel-permission (see `design.md` for the reasoning).
- Not migrating the deployed `int`-PK schema to UUIDs.
- Not replacing the per-job-title permission matrix — it stays; roles are a second,
  coarser layer that already feeds `canDo()`.
- Not re-guarding every module in the app in one pass — exams + libraries are the concrete
  cases from the card; other modules follow the same recipe later, one route group at a time.

---

## Glossary

| Term | Meaning in ViewClass |
|------|----------------------|
| Role | Row in `roles` (`super-admin`, `school-admin`, `teacher`, `student`, `parent`). Users get roles via `role_user`. |
| Permission | Row in `permissions` (`name`, `slug` e.g. `exams.create`, `group`, `description`). |
| `permission_role` | Pivot: which permissions a role holds. Read by `Role::hasPermission()` → `User::hasPermission()` → `User::canDo()`. |
| Job Title | Finer per-school assignment (`job_titles` + `job_title_permissions`) already managed by `JobTitlePermissionsController`. Orthogonal to roles. |
| MODULES catalog | The authoritative module→actions map in `JobTitlePermissionsController::MODULES` used to render the permission grid. |
| Default-allow-`.view` | `canDo()` rule: an unconfigured user still passes `*.view` (read) but fails every write action. Protects existing users from read lockout. |

---

## User Stories

### US-001: List roles
**As a** super-admin
**I want to** see all roles with their user counts and active state
**So that** I have a single place to manage what each role can do.

**Acceptance Criteria:**
- WHEN a super-admin opens `/admin/roles` THE SYSTEM SHALL list every role in `roles` with name, slug, description, active state, and the number of assigned users.
- WHEN a user without `roles.view` permission requests `/admin/roles` THE SYSTEM SHALL respond `403`.
- THE SYSTEM SHALL reuse the existing `roles.view` / `roles.create` / `roles.edit` / `roles.delete` permissions already present in `PermissionSeeder` (no new permission slugs for role management).

### US-002: Edit a role's permissions via a grid
**As a** super-admin
**I want to** open a role and toggle its permissions on a module × action grid
**So that** I can grant or revoke capabilities without editing the database.

**Acceptance Criteria:**
- WHEN a super-admin opens `/admin/roles/{role}/permissions` THE SYSTEM SHALL render a grid built from the shared MODULES catalog (same source the job-title matrix uses), with a checkbox per `group.action`, pre-checked for permissions the role currently holds in `permission_role`.
- WHEN the super-admin saves the grid THE SYSTEM SHALL `sync()` the selected `permission_id`s into `permission_role` for that role (attaching newly-checked, detaching unchecked).
- WHERE a checked action has no matching row in `permissions` THE SYSTEM SHALL ignore it (only real permission slugs are synced), so the grid can list actions ahead of their seeding.
- WHEN the save completes THE SYSTEM SHALL redirect back to the grid with a success message.

### US-003: Protect the super-admin role
**As a** system owner
**I want** the `super-admin` role to keep full access regardless of the grid
**So that** no admin can accidentally lock the platform's owners out.

**Acceptance Criteria:**
- THE SYSTEM SHALL keep the existing `User::canDo()` short-circuit: `isSuperAdmin()` returns `true` before any permission lookup.
- WHERE a super-admin opens the `super-admin` role editor THE SYSTEM SHALL show its grid as read-only (or omit the row from editable saving), so the bypass cannot be revoked from the UI.

### US-004: Enforce exam permissions on teacher & admin routes
**As a** school-admin
**I want** a teacher to reach `/teacher/exams` **only** when their role grants `exams.*`
**So that** the QA card («صلاحيات المعلم لا تظهر عند وجهة المعلم») is resolved: teacher access reflects granted permissions, not just the `teacher` role.

**Acceptance Criteria:**
- WHEN enforcement is enabled AND a teacher lacking `exams.view` requests `/teacher/exams` THE SYSTEM SHALL respond `403`.
- WHEN a teacher holding `exams.view` requests `/teacher/exams` (list/show) THE SYSTEM SHALL allow it.
- WHEN a teacher lacking `exams.create` submits the exam create form THE SYSTEM SHALL respond `403`, while a teacher holding `exams.create` succeeds.
- THE SYSTEM SHALL apply the same `exams.*` gates to the `/admin/exams` route group.
- IF the user is a super-admin THEN the request SHALL always pass (bypass preserved).

### US-005: Enforce library permissions on public/private library routes
**As a** school-admin
**I want** library write actions gated by `libraries.*` permissions
**So that** `/admin/libraries/public` create/edit/delete reflect granted permissions.

**Acceptance Criteria:**
- WHEN a user lacking `libraries.view` requests the library index THE SYSTEM SHALL respond `403`; holding `libraries.view` allows read.
- WHEN a user lacking `libraries.create` posts to `/admin/libraries/public` THE SYSTEM SHALL respond `403`; `libraries.edit` / `libraries.delete` gate the corresponding update/destroy routes.
- IF the user is a super-admin THEN library requests SHALL always pass.

### US-006: No existing user is locked out when enforcement turns on
**As a** platform operator
**I want** every user who can use exams/libraries today to keep that access the moment enforcement flips on
**So that** switching from `role:` to `permission:` gating causes zero regressions.

**Acceptance Criteria:**
- BEFORE any `permission:` middleware is added to exams/libraries, THE SYSTEM SHALL seed baseline `permission_role` rows so each role holds the permissions matching its **current effective access** (e.g. `teacher` → `exams.view/create/edit/delete` + `libraries.view` + own private-library writes; `school-admin` → the full module set).
- WHEN enforcement is enabled after the baseline seed THE SYSTEM SHALL grant every previously-working teacher and admin the same exam/library access they had before (verified against a checklist before flipping).
- THE SYSTEM SHALL preserve the default-allow-`.view` rule as a read-only safety net during and after the transition, so navigation/read pages never hard-lock even for a role whose grid is empty.
- WHERE the baseline seed is missing a permission slug THE SYSTEM SHALL fail closed for that write action only (never silently grant a write).

### US-007: Multi-tenant safety
**As a** school-admin
**I want** to tailor access for staff in **my** school without affecting other schools
**So that** tenant isolation holds.

**Acceptance Criteria:**
- THE SYSTEM SHALL keep the global Role→permission editor (`permission_role`) **super-admin only**, because those roles are shared across all schools.
- WHERE a school-admin needs to tailor access within their own school THE SYSTEM SHALL direct them to the existing per-job-title matrix, which is already school-scoped (`JobTitlePermissionsController::assertCanManage()`), not to the global role editor.
- WHEN a school-admin requests any global role-management route THE SYSTEM SHALL respond `403`.

---

## Non-Functional Requirements

### NFR-001: Least disruption
- THE SYSTEM SHALL build on the existing `Permission` / `Role` / `permission_role` / `canDo()` stack; it SHALL NOT introduce Spatie laravel-permission or a parallel permission store.

### NFR-002: Reuse the permission catalog (DRY)
- THE grid data source (MODULES catalog) SHALL exist in exactly one place, shared by the role editor and the job-title matrix — no second hand-maintained copy.

### NFR-003: Safe, reversible rollout
- Enforcement (route-middleware changes) SHALL be the **last** phase, applied only after the baseline seed is verified on staging/local, so it can be reverted by removing the middleware without data loss.

### NFR-004: Security — fail closed on writes
- Every write action (create/edit/delete/…) SHALL remain denied unless explicitly granted by role or job title; only `*.view` benefits from the default-allow safety net.
