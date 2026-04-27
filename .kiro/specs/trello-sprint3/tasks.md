# Tasks: Sprint 3 — Users Module

## Phase 0: Re-test Sprint 1 + 2

### Task 0.1: Smoke-test Sprint 1+2 admin routes
- [x] login as developer / curl every admin route
- [x] confirm 200 on schools / settings / academic-years / grade-levels / permissions

**Outcome:** ✅ All Sprint 1+2 routes healthy
**Dependencies:** None

---

## Phase 1: Foundation

### Task 1.1: Sprint 3 specs
- [x] requirements.md
- [x] design.md
- [x] tasks.md

### Task 1.2: Database migrations
- [x] create_job_titles_table
- [x] add_job_title_id_to_users + plain_password_for_card
- [x] create_admin_supervisees_table
- [x] seed_default_job_titles (8 defaults)

### Task 1.3: JobTitle model
- [x] App\Models\JobTitle with school scope + active scope + localized_name accessor

### Task 1.4: Sidebar Users mega-menu
- [x] resources/views/components/sidebar.blade.php — Users group wired to 6 routes
- [x] active-link highlight on /admin/users/*
- [x] role gate (school-admin, super-admin)

**Outcome:** ✅ Schema + sidebar ready
**Dependencies:** None

---

## Phase 2: Card 26 — Students

- [x] BaseUserListRepository + EloquentStudentListRepository
- [x] StudentController (index/create/store/edit/update/destroy/bulk)
- [x] /admin/users/students routes (7 endpoints)
- [x] Index view: 8 columns, search, 3 button-menus (Add ▼ / Other Options ▼ / Operations ▼), row ⋯ menu
- [x] _form / create / edit views
- [x] Bulk operations (license/unlicense/waiting/hide-grades/show-grades/hide-report/show-report)
- [x] live verified: created طالب اختبار, redirected, listed, success message, cleaned up

**Outcome:** ✅ Students CRUD shipped
**Dependencies:** 1.x

---

## Phase 3: Card 27 — Parents

- [x] EloquentParentListRepository
- [x] ParentController (index/create/store/edit/update/destroy/students/syncStudents)
- [x] Index + create/edit + students-link manager
- [x] live verified: created ولي اختبار

**Outcome:** ✅ Parents CRUD + linking
**Dependencies:** Phase 2

---

## Phase 4: Card 28 — Teachers

- [x] EloquentTeacherListRepository
- [x] TeacherController (index/create/store/edit/update/destroy/workloads)
- [x] Index + workloads page (read-only, lead-teacher count from Sprint 2)
- [x] live verified: created معلم اختبار

**Outcome:** ✅ Teachers CRUD + workload
**Dependencies:** Phase 2

---

## Phase 5: Card 29 — Admins + Job titles

- [x] EloquentAdminListRepository (multi-role support)
- [x] AdminController (index/create/store/edit/update/destroy/supervisees/syncSupervisees)
- [x] JobTitleController (index/store/update/destroy)
- [x] Add ▼ menu lists every active job title
- [x] supervisees pivot manager (counselor → students, supervisor → teachers)
- [x] live verified: created مدير اختبار with job_title_id=1 (school_manager)

**Outcome:** ✅ Admins + job titles
**Dependencies:** 1.2, 1.3

---

## Phase 6: Card 30 — User Cards

- [x] UserCardController (index + generate)
- [x] PDF blade with 2-column grid, RTL, platform header
- [x] dompdf streaming
- [x] tabs: Students+Parents / Teachers+Admins
- [x] live verified: PDF returned with content-type application/pdf

**Outcome:** ✅ Printable cards PDF
**Dependencies:** Phases 2-5

---

## Phase 7: Login-as

- [x] ImpersonateController (start/stop)
- [x] Banner middleware via session('impersonator_id') in layouts/app.blade.php
- [x] Activity log entries (impersonate.start / impersonate.stop)

**Outcome:** ✅ Login-as for super-admin
**Dependencies:** None

---

## Phase 8: Ship

- [x] lang/ar/users.php + lang/en/users.php (full coverage)
- [x] git commits + push to main
- [x] ssh git pull + migrate + optimize on live
- [x] MCP smoke test on AR + EN (all 6 routes 200, English titles render)
- [ ] Move 6 Trello cards to testing + comment

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 0. Re-test S1+S2 | 1 | 1 | ✅ Done |
| 1. Foundation | 4 | 4 | ✅ Done |
| 2. Students | 7 | 7 | ✅ Done |
| 3. Parents | 4 | 4 | ✅ Done |
| 4. Teachers | 4 | 4 | ✅ Done |
| 5. Admins | 6 | 6 | ✅ Done |
| 6. Cards | 5 | 5 | ✅ Done |
| 7. Login-as | 3 | 3 | ✅ Done |
| 8. Ship | 5 | 4 | 🔄 In progress |
| **Total** | **39** | **38** | **97%** |
