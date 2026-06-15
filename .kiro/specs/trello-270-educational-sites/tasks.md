# Tasks: Educational Websites (Trello #270, card LWi6PeWf)

NET-NEW module `app/Modules/EducationalSites/`. Directory of external educational
site links shown as modern cards to end-users, with admin CRUD/toggle/reorder.
Permission group `educational_sites.*` was pre-seeded by the Sprint-10 foundation
card (#260).

## Phase 1: Data + module scaffolding
### Task 1.1: Persistence
- [x] Migration `2026_06_16_720000_create_educational_sites_table.php` (nullable
      `school_id` for globals, name_ar/en, description_ar/en, url, logo_path,
      category, sort_order, opens_new_tab, is_active, softDeletes). Ran locally.
- [x] `EducationalSite` model (casts, school relation, display_name/description/logo_url accessors).
- [x] Repository contract + Eloquent impl (scope enforced inside), bound in `RepositoryServiceProvider`.

## Phase 2: HTTP + UI
- [x] `EducationalSiteRequest` (url validated as real URL → no broken links saved).
- [x] `EducationalSiteController` (display, index, create/store, edit/update, destroy, toggle, reorder).
- [x] Routes file + ONE require line appended to routes/web.php.
- [x] Views: management index (table + filters + toggle/edit/delete + reorder + empty state),
      form (all card fields), public display card grid. design-system.css + x-svg-icon, RTL.
- [x] ActivityLog on create/update/delete/toggle/reorder (Arabic).

## Phase 3: Verification (Playwright + DB)
- [x] Management index 200 as super-admin.
- [x] Create → persists (DB row + activity log).
- [x] Edit → category updated + logged.
- [x] Toggle → is_active flipped + logged.
- [x] Delete → soft-deleted + logged.
- [x] Display grid renders active cards (global + own-school); hides disabled + deleted.
- [x] Scope isolation: school-2 viewer sees global only, not school-1 site.
- [x] super-admin null branch: listForManagement(null) returns all without error.
- [x] 403 on write route as school-admin lacking the grant (user 67, configured job-title w/o educational_sites).

## Deferred (with reason)
- [ ] "تصدير إن لزم" (export) — card says "إن لزم" (if needed); not built. Reuse pdf_export/reports later.
- [ ] Live broken-link health monitoring — out of scope; minimal guard = url validation at save.
- [ ] Sidebar item — task explicitly forbids touching sidebar. (A `href="#"` placeholder
      already exists, seeded elsewhere; not wired by this work. Routes are URL-reachable.)

## Progress
| Phase | Status |
|-------|--------|
| 1. Data + scaffolding | Done |
| 2. HTTP + UI | Done |
| 3. Verification | Done |
