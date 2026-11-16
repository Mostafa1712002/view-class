# Design / gap map (code at origin/main c17b28f)

| Card | Exists | Gap | Size |
|---|---|---|---|
| 374 RBAC | CheckRole/CheckPermission/RolePermission on all admin+manage routes (scan: 2 unguarded, both have in-controller checks) | regression test per role; check school-admin on admin/roles (global roles) | S |
| 373 Seeders | dashboard UI + repo | EloquentDashboardStatsRepository returns hardcoded zeros for rates/content/various/weekly/mostActive → real queries + `vc:demo-data` command | M |
| 365 Companies | full CRUD, super-admin | E2E verify, fix what fails | S |
| 366 Promotion | Promotion module (batches, items, rollback, executed_by/at) at admin/schools/{s}/promotion; batch-level dup guard | per-student guard on every path; landing route for sidebar | S-M |
| 367 Terms/weeks | AcademicTerm/StudyWeek + CRUD under schools/{s}/academic-years; qb weeks page | current-week accessor; landing page; sidebar | S-M |
| 370 Exams | admin/exams + teacher/student exams, results, qb exams | E2E check, link sidebar, grade-report hook check | S-M |
| 369 Distance | VirtualClasses + attendance + Discussion | hub page + sidebar | S |
| 372 Coaching | none | new module | L |
| 371 Points | none | new module, rules from نقاط التحفيز.docx | L |
| 368 Programs | none | new module | L |
