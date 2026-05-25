# Tasks: Teachers Page (Trello "المعلمين")

## Gaps confirmed live (admin)
- Import page is "coming soon" placeholder — no real Excel import / update / photo-ZIP.
- Workloads page has 6 cols; spec wants 4 (الاسم | رقم الهوية | النصاب | التحكم).
- Permissions/roles page (صلاحيات وأدوار المعلم) missing; dropdown link disabled.
- GET /admin/users/{id}/impersonate → 405 (card says 419). Dropdown POST works.

## Phase 1: Excel import + photo ZIP
- [x] ParseTeacherSheet action (clone ParseParentSheet + teacher headers)
- [x] TeacherController::importTemplate / export / import / importUpdate / importPhotos
- [x] Real import view (3 cards: add, update, photos) + routes

## Phase 2: Workloads trim
- [x] Reduce workloads view to 4 columns per spec

## Phase 3: Teacher permissions/assignments
- [x] migration te_..._create_teacher_assignments_table
- [x] TeacherAssignment model + User relation
- [x] TeacherPermissionController show/store/destroy + routes
- [x] permissions view (3 selects + add + table) + enable dropdown link

## Phase 4: Impersonate GET confirm
- [x] GET impersonate confirm route + auto-submit view

## Phase 5: lang + deploy + live verify
- [x] lang/ar/users.php keys
- [ ] deploy + Playwright live verify all URLs
