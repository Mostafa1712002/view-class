# Tasks: Roles & Permissions Management

Ordered so that **enforcement is the LAST phase**: build the UI and seed the anti-lockout
baseline first, verify nobody loses access, then flip `role:` → `permission:` gating behind
that verification.

---

## Phase 1: Audit & shared catalog

### Task 1.1: Audit current effective access
- [x] List every route group guarded by `role:...,teacher` that will move to `permission:`
      (start with exams `/admin/exams` ~L565, `/teacher/exams` ~L822/837; libraries `/admin/libraries` ~L498, ~L808).
- [x] For `teacher` and `school-admin`, write down the exact `group.action` permissions each
      role must hold to keep today's access (this list feeds the Phase 2 seed).
- [x] Confirm `permission_role` currently has **no** `exams.*` / `libraries.*` rows for
      `teacher` (i.e. enforcement would lock them out) — proves the seed is required.

**Audit findings (2026-07-13):**
- Exams: `/admin/exams` (role:super-admin,school-admin) + `/teacher/exams` (role:...,teacher)
  expose the full `Route::resource('exams')` (view/create/edit/delete) + publish/activate/complete/results.
- Libraries: WRITE routes (public + private create/edit/delete) live in an **admin-only**
  group (`role:super-admin,school-admin`, ~L498); the teacher-inclusive group (~L806) is
  **read-only** (index/show/items/labs browse). So teacher's effective library access = **view only**.
- Pre-seed `permission_role` (verified): teacher held `exams.view/create/edit` (NO delete),
  **no** `libraries.*`; school-admin held `exams.*` but **no** `libraries.*`. → seed required.
- Role → must-keep map:
  - `school-admin` → full catalog (does everything today).
  - `teacher` → `exams.{view,create,edit,delete}`, `weekly-plans.{view,create,edit,delete}`,
    `grades.{view,create,edit}`, `attendance.{view,create,edit}`, `libraries.view`,
    + reads `classes/subjects/schedules/reports/students/calendar .view`.

**Outcome:** ✅ A written map: role → permissions-it-must-keep.
**Dependencies:** None.

### Task 1.2: Extract the MODULES catalog (DRY)
- [x] Create `app/Modules/Users/Support/PermissionCatalog.php` with `MODULES`, `SCOPE_LABELS`,
      `allSlugs()` moved verbatim from `JobTitlePermissionsController`.
- [x] Point `JobTitlePermissionsController` at `PermissionCatalog::MODULES` / `::SCOPE_LABELS`.
- [x] Verify the job-title matrix renders identically (behaviour-preserving refactor).

**Outcome:** ✅ One catalog, two consumers.
**Dependencies:** None.

---

## Phase 2: Anti-lockout baseline seed

### Task 2.1: Baseline `permission_role` seed migration
- [x] Add `database/migrations/2026_07_13_100000_seed_baseline_role_permissions.php`.
- [x] Grant `school-admin` the full catalog; grant `teacher` the Task 1.1 list;
      leave `student`/`parent` untouched.
- [x] Idempotent: resolve `permission_id` by slug, `updateOrInsert` (additive, no detach);
      skip missing slugs (fail closed — never fabricate a write grant).

**Outcome:** ✅ Roles hold permissions equal to today's effective access.
**Dependencies:** 1.1.

### Task 2.2: Verify no regression (pre-enforcement)
- [x] Ran the seed on local. Post-seed `permission_role` counts:
      `school-admin` 86→294 (now holds full catalog incl `libraries.*`),
      `teacher` 32→37 (added `exams.delete`, `libraries.view`, `weekly-plans.delete`,
      `students.view`, `calendar.view`), `student` 10 & `parent` 9 untouched.
- [x] Middleware is still `role:` (Phase 4 not applied here); teacher/school-admin now hold
      `exams.*` + `libraries.view`/`libraries.*` in `permission_role`, so `canDo()` returns
      true for those (hasPermission short-circuit) — parity preserved, additive only.

**Outcome:** ✅ Parity checklist green (seed additive; nothing removed).
**Dependencies:** 2.1.

---

## Phase 3: Roles management UI (no enforcement yet)

### Task 3.1: RoleController
- [x] Add `app/Modules/Users/Controllers/RoleController.php` (index / editPermissions /
      updatePermissions), mirroring `JobTitlePermissionsController`.
- [x] Super-admin role guard: `updatePermissions` skips sync for `slug === 'super-admin'`.
- [x] Sync selected slugs → `permission_id`s → `$role->permissions()->sync()`.
- [x] Extra: `abort_unless(isSuperAdmin())` on every action — needed because
      `roles.view` is a ".view" slug that `canDo()` default-allows, so the
      `permission:roles.view` middleware alone would let a school-admin reach the index.
- [x] Extra: `updatePermissions` preserves non-catalog `permission_role` rows (e.g. legacy
      `classes.*`) so the grid never drops permissions it doesn't render (sync would otherwise).

**Outcome:** ✅ Backend for role permission editing.
**Dependencies:** 1.2.

### Task 3.2: Views
- [x] `resources/views/admin/roles/index.blade.php` — roles list + user counts + edit button.
- [x] `resources/views/admin/roles/permissions.blade.php` — clone job-title grid, drop scope
      column, post to `admin.roles.permissions.update`; super-admin grid rendered read-only.

**Outcome:** ✅ Roles dashboard renders.
**Dependencies:** 3.1.

### Task 3.3: Routes + navbar + lang
- [x] Add `roles.index` (`permission:roles.view`) and `roles.permissions.edit/update`
      (`permission:roles.edit`) to the `admin` group.
- [x] Add gated "الأدوار والصلاحيات" **sidebar** link (`canDo('roles.edit')` — super-admin only;
      `roles.view` would leak to school-admins via default-allow). Link sits beside job-titles.
- [x] Add ar/en strings (`lang/ar/roles.php`, `lang/en/roles.php`).

**Outcome:** ✅ Super-admin can open `/admin/roles`, edit a role's grid, save to `permission_role`.
**Dependencies:** 3.1, 3.2.

### Task 3.4: Verify UI end-to-end (local)
- [x] As super-admin (developer@midade.com, Playwright): opened roles list (200, all 5 roles),
      edited `teacher`, toggled `books.print`, saved ("تم حفظ صلاحيات الدور بنجاح"), reloaded —
      change persisted in `permission_role` (PASS); reverted to keep DB clean.
- [x] Non-catalog `classes.view` survives the save cycle (teacher stays 37) — preserve fix works.
- [x] School-admin → `/admin/roles` 403: verified deterministically — `canDo('roles.edit')`=false
      and `isSuperAdmin()`=false for a school-admin, so `abort_unless(isSuperAdmin())` fires 403.
      (Note: `canDo('roles.view')` returns true via default-allow, so the controller guard — not
      the middleware — is what enforces US-007.)
- [x] `super-admin` role grid cannot revoke its own bypass: 0 save buttons, 257 disabled
      checkboxes, and `updatePermissions` early-returns for `slug === 'super-admin'`.
- [x] No regression: `/admin/users/teachers`, `/teacher/exams`, `/admin/libraries/public` all 200.

**Outcome:** ✅ Dashboard verified; still no route enforcement.
**Dependencies:** 3.3.

---

## Phase 4: Enforcement (LAST — behind Phase 2 verification)

### Task 4.1: Gate exams routes
- [ ] Inside the existing `role:` groups, add `permission:exams.view` to read routes and
      `permission:exams.create` (and edit/delete where split) to write routes, for both
      `/admin/exams` and `/teacher/exams`.
- [ ] Verify: teacher without `exams.*` → `403`; teacher with baseline grant → unchanged
      access; super-admin → always passes. Resolves the QA card.

**Outcome:** `/teacher/exams` reflects granted permissions.
**Dependencies:** 2.2 (green checklist), 3.4.

### Task 4.2: Gate libraries routes
- [ ] Add `permission:libraries.view` to read routes; `libraries.create/edit/delete` to the
      matching write routes for public + private libraries (`/admin/libraries`).
- [ ] Verify parity for teacher + school-admin against Task 2.2 checklist.

**Outcome:** `/admin/libraries/public` reflects granted permissions.
**Dependencies:** 2.2, 3.4.

### Task 4.3: Live verification
- [ ] Deploy per project flow (commit → push → SSH → `git pull` → `migrate --force` →
      `view:cache`). Migration runs the baseline seed before middleware takes effect.
- [ ] On live: re-run the teacher + school-admin exam/library checklist; confirm identical to
      local. Toggle a permission in the UI and confirm the route reacts.

**Outcome:** Enforcement live with zero access regressions.
**Dependencies:** 4.1, 4.2.

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Audit & shared catalog | 2 | 2 | ✅ Done |
| 2. Anti-lockout baseline seed | 2 | 2 | ✅ Done |
| 3. Roles management UI | 4 | 4 | ✅ Done |
| 4. Enforcement (last) | 3 | 0 | Not Started (handled separately) |
| **Total** | **11** | **8** | **73%** |

---

## Rollback

Enforcement is reversible: remove the `permission:` middleware added in Phase 4 to fall back
to `role:`-only gating. The baseline seed and the roles UI are additive and safe to leave in
place. No schema was changed.
