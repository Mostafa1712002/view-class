# Design: فريق العمل — unified team page

## Route
`GET admin/users/team` → `App\Modules\Users\Controllers\TeamController@index`, name `admin.users.team.index`, inside the existing `prefix('users')` group (middleware `auth`, `role:super-admin,school-admin`).

## Tabs (`?tab=`)
| tab | shown when | content |
|---|---|---|
| `accounts` (default) | always | unified staff table + filters + KPIs |
| `job-titles` | `canDo('job_titles.view')` | `@include('admin.users.job_titles._panel')` (extracted from job_titles/index) |
| `roles` | `isSuperAdmin()` | roles table (built in TeamController, not via RoleController) → `admin.roles.permissions.edit` |

## Unified staff query
`EloquentTeamListRepository` (`TeamRepository` contract, bound in RepositoryServiceProvider):
- staff = has a role NOT IN (student, parent) AND has no role IN (student, parent)
- type=teacher → has role teacher; type=admin → has no role teacher (same split the old lists use, so the two lists are disjoint)
- school scope: `listSchoolId()`; when set → `school_id = X OR has role super-admin` (same as AdminController)
- filters: `q` (name/email/username/national_id/employee_id), `job_title_id`, `status` (active = is_active=1 AND status<>'inactive')
- eager load `jobTitle, roles, school`, order by name, paginate 25 withQueryString.

## Redirects
`TeacherController::destroy`, `AdminController::destroy`, `JobTitleController` store/update/destroy → `redirect()->back()` (they are only fired from list pages, so back = the page the user was on).

## Sidebar
Only the `nav_team` item in the extras section: link to `admin.users.team.index`, active on `admin.users.team.*`, shown when `$canViewUsers || $canViewTeachers || $canViewJobTitles`.
