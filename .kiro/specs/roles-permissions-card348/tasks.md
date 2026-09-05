# Tasks: Roles & Permissions — card #348

## Task 1: Explanations (no code) — post to Trello
- [ ] Arabic comment answering: (a) roles = global coarse buckets (super-admin/school-admin/teacher…),
      permissions = `{group}.{action}` fine-grained abilities, job titles = per-school layer whose OWN
      permission matrix is what actually drives access (`User::canDo` reads job-title perms, then role perms);
      (b) "الدور الرئيسي" is currently a display/label link only — it does NOT copy permissions; to copy use
      "نسخ الصلاحيات من مسمى آخر". Offer to make primary-role auto-copy if wanted.

## Task 2: Catalog audit (real fix for ask 4)
- [x] Diff `PermissionCatalog::MODULES` groups vs sidebar routes; list pages with no `{group}.view` option.
- [x] Add missing groups to `MODULES` with correct action lists + Arabic labels.
      Added 6 groups (19 + 4 = 23 new slugs) for real, reachable admin pages that had NO permission
      group at all: `policies` (سياسات التعليم), `lessons` (الحصص الدراسية), `class_visits` (الزيارات
      الصفية), `user_cards` (بطاقات المستخدمين), `surveys` (الاستبيانات — view/create/edit/delete/publish),
      `special_education` (التربية الخاصة). Verified each has a real controller + route before adding
      (skipped `admin.classified-ads.*` and `admin.educational-companies/complexes.*` — sidebar links
      exist but no route/controller is registered, so `Route::has()` renders them as dead `#` links,
      not reachable pages).
      Separately found (NOT fixed — sidebar-gating bug, out of scope for this card): `admin.school-schedule.index`
      is ungated despite the `schedules` group + `$canViewSchedules` existing; the whole evaluations /
      eval-reports / job-performance block (sidebar ~L624-626, ~L896-913) is ungated despite `evaluations`
      and `job_performance` already being catalog groups. Both are "sidebar never calls canViewModule()"
      bugs, not missing-catalog-group gaps — flagging for the lead, not fixing here.

## Task 3: Seeder gap fix (silent-save bug)
- [x] Make `PermissionSeeder` (or a new idempotent seeder) create a `permissions` row for EVERY
      `PermissionCatalog::allSlugs()` entry, so every rendered checkbox persists.
      Rewrote `PermissionSeeder` to iterate `PermissionCatalog::MODULES` (firstOrCreate by slug — never
      touches an existing row) + added migration `2026_09_05_090000_seed_full_permission_catalog.php`
      (insert-only-if-missing, chunked `insertOrIgnore`) so live's `migrate --force` path backfills the
      gap without touching any row it doesn't need to. Verified `canDo()` / `CheckPermission` middleware
      never branch on "does a Permission row exist" — they only read `permission_role` /
      `job_title_permissions` pivots — so inserting rows with no grant is behaviorally inert; no access
      changes on deploy.
      Actual pre-card-348 gap on local (excluding the 6 new groups above): exactly 5 slugs —
      `users.export/import/print/send_notifications/manage_permissions` — the extended `users` actions
      had a catalog entry but no seed migration ever ran for them, unlike attendance/qr/certificates/etc.
      which each got a dedicated `seed_*_permissions` migration.
- [x] Confirm `RoleController::updatePermissions` now syncs previously-ignored slugs.
      Verified locally on BOTH matrices: ticked `policies.view` on the school-admin role via
      `/admin/roles/2/permissions` (257→258 checked, confirmed in `permission_role`), and ticked
      `special_education.view` on job title #1 via `/admin/users/job-titles/1/permissions` (confirmed in
      `job_title_permissions`). Both stayed ticked after reload; both reverted after the test.

## Task 4: Verify + ship
- [ ] Local: tick a previously-non-seeded permission on a role, save, reload → stays ticked. New catalog pages appear.
- [ ] Deploy: git pull + `migrate --force` (runs seeder) + view:cache. Live: repeat check.
- [ ] Arabic handback; move; reassign Mohamed Farouk.

## Progress
| Task | Done |
|------|------|
| 1 explain (Trello) | ☐ |
| 2 catalog audit | ☑ |
| 3 seeder fix | ☑ |
| 4 ship | ☐ |
