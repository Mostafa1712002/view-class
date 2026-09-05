# Requirements: Roles & Permissions — card #348

## Overview
Card "صفحة الأدوار والصلاحيات" (`/admin/roles`, creator: Mohamed Farouk). Four asks, mixed
explanation + real work. The existing `roles-permissions` spec already built the role→permission
toggle UI; this card is the follow-up.

## The four asks
1. **"لا يوجد امكانية اضافة صلحيات جديدة. اريد امكانية اضافة صلحيات."** — add ability to create permissions.
2. **Explain** the difference between المسميات الوظيفية (job titles) / الأدوار (roles) / الصلاحيات (permissions).
3. **Explain** the "الدور الرئيسي" (primary role) field in the add-job-title form — what it does.
4. **Audit** the permission options: is any page missing from the permission list?

## Grounding
- Custom perm system (not spatie). Master ability list = `app/Modules/Users/Support/PermissionCatalog.php`
  `MODULES` const (~45 groups). Both role + job-title grids render from it.
- `/admin/roles` = super-admin only; toggles existing permissions onto roles; cannot create roles/permissions.
- `job_titles.role_id` ("primary role") = **display-only**; does NOT grant or copy permissions. Effective
  perms come from the job-title's own `job_title_permissions` pivot (`User::canDo`).
- **Seeder gap**: `PermissionSeeder` seeds ~14 groups; catalog has ~45 → catalog slugs not seeded render as
  checkboxes but silently fail to sync on save (`RoleController.php:74-77`). This is a real bug.

## Decisions (per advisor)
- **Asks 2 & 3 are pure explanation** → answer directly on the Trello card in Arabic (per CLAUDE.md:
  explanation cards answered, not coded). No code.
- **Ask 1 "add permission UI" is mostly a trap**: a permission slug the code never `canDo()`-checks does
  nothing. Do NOT build a free-text "create arbitrary permission" box. Instead deliver the *useful* version:
  - **Ask 4 = the real fix**: audit `PermissionCatalog::MODULES` vs actual sidebar/admin routes; add any
    missing page groups so they appear as toggle options.
  - **Fix the seeder gap**: seed every catalog slug so checkboxes actually persist (closes the silent-save bug).
  - Offer, in the comment, to wire `role_id` → copy permissions (do not build unasked).

## Acceptance criteria
- WHEN a role's permissions are saved THE SYSTEM SHALL persist every ticked catalog permission (no silent drop).
- WHEN the roles/job-title grid renders THE SYSTEM SHALL include a `{group}.view` option for every admin page
  reachable from the sidebar.
- Explanations 2 & 3 posted as an Arabic comment on the card.
