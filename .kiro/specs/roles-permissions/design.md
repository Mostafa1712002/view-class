# Design: Roles & Permissions Management

## 1. Decision — Extend the existing system, do NOT adopt Spatie

### Recommendation
**EXTEND** the existing custom `Permission` / `Role` / `permission_role` + `User::canDo()`
stack. Add a Roles management UI on top of it and reuse the current gate. **Do not** adopt
Spatie laravel-permission the way `t3ahdo` does.

### Why (tradeoffs)

| Factor | Extend existing (recommended) | Adopt Spatie (t3ahdo style) |
|--------|-------------------------------|-----------------------------|
| Schema | Reuses `permissions`, `roles`, `permission_role`, `role_user`, `job_title_permissions` already deployed with **int PKs**. Zero migration of live data. | Spatie ships its own tables/columns + morph `model_has_roles`/`model_has_permissions`; would need a data migration of every existing role/permission/pivot on a live int-PK DB. |
| Existing wiring | `canDo()`, `canEval()`, `canViewModule()`, `hasPermission()`, the `permission:` middleware, the job-title matrix, and **15+ seed migrations** all already speak the current model. | All of the above would have to be re-pointed at Spatie's API (`hasPermissionTo`, `assignRole`, guards), a large, risky rewrite for no new capability. |
| The one missing piece | A Roles **UI** — the only real gap. Small, additive. | Same UI still has to be built; plus the migration. |
| t3ahdo parity | We match t3ahdo's **UX** (a roles dashboard with a permission grid) without importing its **stack**. | Matches the stack too, but the client asked for the dashboard behaviour, not the package. |
| Risk | Low: additive controller + views + one baseline seed; enforcement flipped last. | High: dual permission sources during migration, guard mismatches, lockout risk multiplied. |

**One-line reason:** the custom stack is already wired end-to-end across the app on an
int-PK live DB; the only thing missing is a UI, so adding a UI is a small additive change
whereas Spatie is a full-stack migration for no new capability.

`t3ahdo` is still worth mirroring for **shape**: thin `RoleController` returning a view,
single-purpose Action classes (`FetchRolesAction`, `StoreRoleAction`, `UpdateRoleAction`),
and seeders (`PermissionSeeder` + `RoleHasPermissionSeeder`). We adopt that shape on our
own models.

---

## 2. What already exists (reuse map)

```
app/Models/Permission.php        name, slug, group, description; belongsToMany Role via permission_role
app/Models/Role.php              name, slug, description, is_active; permissions(), users(),
                                 hasPermission(), givePermission(), revokePermission()
app/Models/User.php
  ├─ roles()            belongsToMany via role_user
  ├─ hasPermission()    loops roles → Role::hasPermission (reads permission_role)
  ├─ canDo()  (~L281)   super-admin bypass → role permission → job-title matrix
  │                     → default-allow ONLY for '*.view'
  └─ canViewModule()    → canDo("{module}.view")
app/Http/Middleware/CheckPermission.php   'permission:xxx' alias → calls canDo()
  (registered in bootstrap/app.php as 'permission')
app/Modules/Users/Controllers/JobTitlePermissionsController.php
  └─ const MODULES      the module→actions catalog (grid source of truth)
  └─ resources/views/admin/users/job_titles/permissions.blade.php  (grid view to clone)
database/seeders/PermissionSeeder.php     grouped permission catalog
                                          (already includes roles.view/create/edit/delete)
tables: roles, permissions, permission_role, role_user,
        job_titles, job_title_permissions,
        school_role_permissions  (DORMANT — see §6)
```

### The exact gap the card hit

`/admin/exams` (routes/web.php ~L565) and `/teacher/exams` (~L822 group, resource at ~L837)
and the libraries group (`/admin/libraries/...` ~L498, read routes ~L808) are all guarded by:

```php
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])...
```

`role:` only checks membership. The `exams.*` / `libraries.*` permission slugs exist and are
seeded, but nothing calls `canDo('exams.create')` on these routes — so they are unenforced.
That is why "teacher permissions don't show up at the teacher destination": whatever you set
has no effect there.

---

## 3. Roles management UI

### 3.1 Extract the catalog (DRY prerequisite)

Move the `MODULES` (and `SCOPE_LABELS`) constant out of `JobTitlePermissionsController` into a
shared, plain class so both the job-title matrix and the new role editor read one source:

```
app/Modules/Users/Support/PermissionCatalog.php
  public const MODULES = [ ... moved verbatim ... ];
  public const SCOPE_LABELS = [ ... ];
  public static function allSlugs(): array   // flatten 'group.action'
```

`JobTitlePermissionsController` then references `PermissionCatalog::MODULES` instead of its
own copy — behaviour-preserving refactor, no functional change to job titles.

### 3.2 Controller

```
app/Modules/Users/Controllers/RoleController.php
  index()                     list roles + user counts (roles.view)
  editPermissions(Role $role) render grid, pre-check permission_role  (roles.edit)
  updatePermissions(Request, Role $role)  sync permission_role         (roles.edit)
```

Mirror `JobTitlePermissionsController` closely (it is the proven pattern):
- Build `$allSlugs` from `PermissionCatalog::allSlugs()`, load `Permission::whereIn('slug',…)`
  keyed by slug.
- Pre-check = `$role->permissions->keyBy('slug')`.
- `update` resolves selected slugs → `permission_id`s → `$role->permissions()->sync($ids)`.
  (Role grid has **no per-permission scope** — scope is a job-title concept; roles are the
  coarse global layer. Keep the grid to plain checkboxes.)
- **Super-admin role guard:** in `updatePermissions`, if `$role->slug === 'super-admin'`
  skip the sync (or render the grid disabled) so its bypass can never be revoked from the UI.

Optionally add single-purpose Actions to match the module conventions in the project
`CLAUDE.md` (`SyncRolePermissionsAction`), but a thin controller is acceptable for parity
with the existing `JobTitlePermissionsController` — do not over-build.

### 3.3 Views

```
resources/views/admin/roles/index.blade.php        table of roles + "الصلاحيات" button
resources/views/admin/roles/permissions.blade.php  cloned from
    resources/views/admin/users/job_titles/permissions.blade.php,
    minus the scope <select> column (roles have no scope), posting to
    admin.roles.permissions.update
```

### 3.4 Routes (add to the existing `admin` group in routes/web.php)

```php
// Global roles management — super-admin only in practice (see §6).
Route::middleware(['permission:roles.view'])->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
});
Route::middleware(['permission:roles.edit'])->group(function () {
    Route::get('roles/{role}/permissions',  [RoleController::class, 'editPermissions'])->name('roles.permissions.edit');
    Route::put('roles/{role}/permissions',  [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
});
```

Because `roles.*` is not in any role's `permission_role` and is not a `.view` default-allow
write, `canDo('roles.edit')` returns `true` only for super-admins → the multi-tenant
requirement (US-007) is satisfied by the gate itself; no extra school check needed for the
global editor. (Optionally add an explicit `abort_unless(auth()->user()->isSuperAdmin())`
belt-and-braces in the controller.)

### 3.5 Navbar

Add a "الأدوار والصلاحيات" link in `resources/views/components/navbar.blade.php`, wrapped in
`@if(auth()->user()->canViewModule('roles'))` (or `canDo('roles.view')`) so it only shows for
super-admins.

---

## 4. Enforcement change (the card's actual fix) — LAST phase

Two complementary levers; do **both** for defence in depth, but only after §5 baseline seed.

### 4.1 Route middleware (primary)
Add `permission:` middleware to the exams and libraries route groups. Because these groups
mix read + write verbs, gate at the sub-group / per-route level, not the whole `role:` group:

- `/admin/exams` and `/teacher/exams`:
  - index/show/results → `permission:exams.view`
  - store/update/destroy/publish/unpublish/activate/complete/reopen → `permission:exams.create`
    (or split create vs edit vs delete if the client wants finer control; the catalog only
    defines `exams` view/create/edit/delete).
- `/admin/libraries` public/private:
  - read (index/show/items) → `permission:libraries.view`
  - create/store → `permission:libraries.create`; edit/update → `permission:libraries.edit`;
    destroy → `permission:libraries.delete`.

Keep the outer `role:...` group intact (students/parents still must not reach teacher/admin
areas); `permission:` is layered **inside** it. `CheckPermission` already returns `403` with
the Arabic message and preserves the super-admin bypass via `canDo()`.

### 4.2 `canDo()` — no change needed
`canDo()` already: super-admin → allow; role has permission → allow; else job-title matrix;
else default-allow only for `.view`. This is exactly the behaviour we want. **Do not tighten
the `.view` default-allow** — it is the read safety net that keeps menus/read pages alive for
roles whose grid is still empty. Writes already fail closed.

### 4.3 Controller-level guard (optional belt-and-braces)
For write actions reachable by multiple routes, an in-action `abort_unless($user->canDo(
'exams.create'), 403)` is a cheap second line, but the middleware in §4.1 is the primary and
sufficient control. Do not scatter checks — prefer the route group.

---

## 5. Safe migration / anti-lockout strategy (CRITICAL)

**The risk is the opposite of read-lockout.** Today any `teacher` reaches `/teacher/exams`
via `role:` and can create exams because nothing calls `canDo('exams.create')` there. The
moment §4 middleware turns on, `canDo('exams.create')` runs — and since **no role holds
`exams.create` in `permission_role`**, every teacher would suddenly get `403`. That is the
lockout we must prevent.

### Strategy: seed baseline `permission_role` BEFORE flipping enforcement.

Add a migration `seed_baseline_role_permissions` that populates `permission_role` so each role
holds the permissions matching its **current effective access**:

- `school-admin` → the full module action set for every group in the catalog (they are the
  school's admin; they can do everything today).
- `teacher` → `exams.view/create/edit/delete`, `libraries.view` + the private-library write
  actions teachers use today, `weekly-plans.*`, `grades.*`, and the other groups the teacher
  UI currently exposes. Derive the list from what the `teacher`-prefixed route groups + the
  teacher sidebar currently expose (audit in Phase 1) so the grant equals today's reality.
- `student` / `parent` → leave as-is (they were never in the exams/libraries admin groups).
- `super-admin` → no seeding needed (bypass), but harmless to grant all.

Seed idempotently: look up each `permission_id` by slug, `syncWithoutDetaching` onto the role
so re-running never removes an admin's later manual edits. Skip slugs that don't exist yet
(fail closed for those writes, never fabricate a grant).

### Verification gate before enforcement (US-006)
On staging/local, with the baseline seeded but middleware **not yet** added, confirm via a
checklist that a real `teacher` and a real `school-admin` account `canDo()` every exam/library
action they can perform today. Only after that checklist is green does §4 middleware ship.

### Retained safety nets
- Super-admin bypass (`isSuperAdmin()` short-circuit in `canDo()`) — untouched.
- Default-allow-`.view` — kept, so navigation/read never hard-locks even for an
  unseeded/empty role. Writes stay fail-closed.

---

## 6. Multi-tenant note

Roles (`super-admin`, `school-admin`, `teacher`, …) are **global** — they are shared by every
school, and `permission_role` is a global mapping. Therefore:

- The **global role editor is super-admin only** (enforced by the `permission:roles.*` gate,
  which no tenant role holds). Editing a global role there would affect every school, so
  school-admins must not touch it.
- **School-admins tailor access per school through the existing job-title matrix**
  (`JobTitlePermissionsController`), which is already school-scoped via `assertCanManage()`
  and `activeSchoolId()`. That layer is unchanged.

There is a **dormant** `school_role_permissions` table (`school_id, role_id, permission_id`,
migration `2026_04_26_100004`) that could later back true *per-school role overrides* — i.e.
`canDo()` would consult it when a school context is active, letting a school-admin add/remove
a role's permissions only within their tenant. **Out of scope now** (`canDo()` does not read it
today and activating it is a behaviour change touching the hot gate path). Documented here as
the sanctioned upgrade path if the client later wants per-school role customization beyond job
titles. <!-- ponytail: global roles only for v1; school_role_permissions is the upgrade path if per-tenant role edits are needed -->

---

## 7. Files to add / change

### Add
| File | Purpose |
|------|---------|
| `app/Modules/Users/Support/PermissionCatalog.php` | Shared MODULES/SCOPE_LABELS + `allSlugs()` (extracted). |
| `app/Modules/Users/Controllers/RoleController.php` | index / editPermissions / updatePermissions. |
| `resources/views/admin/roles/index.blade.php` | Roles list. |
| `resources/views/admin/roles/permissions.blade.php` | Role permission grid (cloned, scope column removed). |
| `database/migrations/xxxx_seed_baseline_role_permissions.php` | Anti-lockout baseline `permission_role` seed (idempotent). |

### Change
| File | Change |
|------|--------|
| `app/Modules/Users/Controllers/JobTitlePermissionsController.php` | Reference `PermissionCatalog::MODULES` instead of its inline copy (behaviour-preserving). |
| `routes/web.php` | Add role-management routes (§3.4). **Phase last:** add `permission:` middleware to the exams + libraries route groups (§4.1). |
| `resources/views/components/navbar.blade.php` | Add gated "الأدوار والصلاحيات" link. |
| `lang/ar/*.php`, `lang/en/*.php` | Strings for the roles screens (mirror job-title matrix keys). |

### Not changed
- `User::canDo()`, `CheckPermission`, `Permission`, `Role` models — reused as-is.
- The job-title matrix behaviour — untouched (only its catalog source moves).
- `school_role_permissions` — left dormant.

### Migrations
- **One** new data-seed migration (baseline role permissions). No schema migration needed —
  `permission_role`, `roles.*` permissions, and all module permissions already exist.
