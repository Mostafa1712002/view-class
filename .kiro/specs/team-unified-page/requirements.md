# Requirements: فريق العمل — unified team page

Trello card f5xZh5z9 (board فيوكلاس, 2026-10-22): in the sidebar section «الإضافات والإسنادات», the «فريق العمل» item (was a coming-soon link) becomes ONE page that manages teachers, admins (الإداريون), job titles (المسميات الوظيفية) and roles & permissions (الأدوار والصلاحيات), with a simple UI and sensible table filters.

## US-001 — one table of all staff accounts
- WHEN an admin opens «فريق العمل» THE SYSTEM SHALL list teachers and admins together in one paginated table (name/email, type, job title, school for super-admin, status, last activity, actions).
- WHEN the admin filters by search text, type (معلم/إداري), job title, or status THE SYSTEM SHALL narrow the table accordingly and keep filters across pages.
- THE SYSTEM SHALL scope rows to the active school exactly like the old teachers/admins lists (super-admin on "all schools" sees every school + a school column).

## US-002 — add / edit / delete accounts from the page
- WHEN the admin clicks «إضافة» THE SYSTEM SHALL offer «إضافة معلم» and «إضافة إداري» (with a job-title picker) using the existing create forms.
- Row actions SHALL open the existing view/edit/permissions screens for that account type; delete SHALL use the shared password-confirm modal and return to the team page.

## US-003 — job titles tab
- WHERE the user may view job titles THE SYSTEM SHALL show a «المسميات الوظيفية» tab with the same table + create form as the old job-titles page (shared partial), with a link to each title's permission matrix; create/delete SHALL return to the page they came from.

## US-004 — roles & permissions tab
- WHERE the user is super-admin THE SYSTEM SHALL show a «الأدوار والصلاحيات» tab listing the system roles with user counts and a link to each role's permission editor. School-admins SHALL NOT see it and the page SHALL NOT 403 for them.

## Non-goals
- No role/permission creation: roles are fixed system roles, permissions are a fixed catalog; custom access is done via job titles + their permission matrix (already exists).
- Old pages (teachers, admins, job titles, roles) and their routes stay.
- No migration.
