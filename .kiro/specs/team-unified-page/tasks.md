# Tasks: فريق العمل — unified team page

- [x] 1. TeamRepository contract + EloquentTeamListRepository + binding
- [x] 2. TeamController@index (tabs, KPIs, job titles + roles data) + route
- [x] 3. Extract job_titles index body into `_panel` partial; include it from job_titles/index and team page
- [x] 4. team/index view (tabs, filters, table, add menu, delete modal)
- [x] 5. Redirects back() in teacher/admin destroy + job-title writes
- [x] 6. Sidebar «فريق العمل» → new page
- [x] 7. ar + en lang keys
- [x] 8. Local test: super-admin all-schools, super-admin scoped, school-admin; old 4 pages still OK; screenshots in claudedocs/
- [x] 9. Deploy viewclass + rawaniedu, verify live, Trello comment + move card

## Shipped 2026-10-22
Commit 640a48d, live on viewclass.newaves-systems.com and rawaniedu.com (no migration). Card moved to `testing prompt`.
Open: create/edit forms for teachers/admins still return to their own list pages after saving (only delete + job-title writes return to the team page).
