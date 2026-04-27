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
- [ ] create_job_titles_table
- [ ] add_job_title_id_to_users
- [ ] add_plain_password_for_card_to_users
- [ ] create_admin_supervisees_table

### Task 1.3: Seed default job titles
- [ ] school_manager / supervisor / counselor / clinic / canteen / activity_lead / vice / assistant_admin

### Task 1.4: JobTitle model
- [ ] App\Models\JobTitle with school scope

### Task 1.5: Sidebar Users mega-menu
- [ ] resources/views/layouts/partials/sidebar.blade.php — add Users group with 5+ children
- [ ] active-link highlight on /admin/users/*
- [ ] role gate (school-admin, super-admin)

**Outcome:** Schema + sidebar ready
**Dependencies:** None

---

## Phase 2: Card 26 — Students

### Task 2.1: Repository + controller scaffold
- [ ] App\Repositories\Users\StudentRepository (paginate, search, scope by school)
- [ ] Admin\Users\StudentController (index/create/store/edit/update/destroy/bulk/import)

### Task 2.2: Routes
- [ ] `/admin/users/students` group with all 9 endpoints

### Task 2.3: Index view
- [ ] columns: ☐ id name grade class gender last-activity ⋯
- [ ] search bar (debounced GET)
- [ ] Add ▼ / Other Options ▼ / Operations ▼ button-menus
- [ ] row dropdown (login-as, parents, schedule, classes, absences, behavior, medical)

### Task 2.4: Create / edit form
- [ ] role=student auto-applied, school_id from session
- [ ] grade level + class selector
- [ ] gender + DOB + national-id

### Task 2.5: Bulk operations
- [ ] hide/show grades (User flag)
- [ ] hide/show report
- [ ] license / unlicense / waiting (User status)

### Task 2.6: Excel import (basic CSV)
- [ ] accept .csv with template headers
- [ ] default password = national_id (forced reset on first login)

**Outcome:** Students CRUD shipped
**Dependencies:** 1.2, 1.5

---

## Phase 3: Card 27 — Parents

### Task 3.1: Repository + controller
- [ ] ParentRepository, ParentController

### Task 3.2: Routes + index view (search, table, ⋯ menu)

### Task 3.3: Linked-students manager (parent_student sync)

### Task 3.4: Add ▼ + Excel import/edit

**Outcome:** Parents CRUD + linking
**Dependencies:** Phase 2

---

## Phase 4: Card 28 — Teachers

### Task 4.1: Repository + controller

### Task 4.2: Routes + index + role assignment

### Task 4.3: Workloads page (read-only)

**Outcome:** Teachers CRUD + workload
**Dependencies:** Phase 2

---

## Phase 5: Card 29 — Admins + Job titles

### Task 5.1: JobTitleController CRUD + view

### Task 5.2: AdminController CRUD with job_title selector

### Task 5.3: Supervisees manager (admin_supervisees pivot)

**Outcome:** Admins + job titles
**Dependencies:** 1.2, 1.4

---

## Phase 6: Card 30 — User Cards

### Task 6.1: Install/confirm dompdf

### Task 6.2: UserCardController (UI + generate)

### Task 6.3: PDF blade template (cards grid with logo + creds)

### Task 6.4: Search + filter + tab logic

**Outcome:** Printable cards PDF
**Dependencies:** Phases 2-5

---

## Phase 7: Login-as (cross-cutting)

### Task 7.1: ImpersonateController + routes

### Task 7.2: Banner middleware

### Task 7.3: Activity log entry

**Outcome:** Login-as works for super-admin
**Dependencies:** None (can be parallel)

---

## Phase 8: Ship

### Task 8.1: i18n keys
- [ ] lang/ar/users.php + lang/en/users.php (all labels)

### Task 8.2: Push to git + deploy

### Task 8.3: MCP smoke tests on live (AR + EN)

### Task 8.4: Move 6 Trello cards to testing + comment

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 0. Re-test S1+S2 | 1 | 1 | ✅ Done |
| 1. Foundation | 5 | 1 | 🔄 In progress |
| 2. Students | 6 | 0 | ⏳ Pending |
| 3. Parents | 4 | 0 | ⏳ Pending |
| 4. Teachers | 3 | 0 | ⏳ Pending |
| 5. Admins | 3 | 0 | ⏳ Pending |
| 6. Cards | 4 | 0 | ⏳ Pending |
| 7. Login-as | 3 | 0 | ⏳ Pending |
| 8. Ship | 4 | 0 | ⏳ Pending |
| **Total** | **33** | **2** | **6%** |
