# Tasks: Sprint 1 QA Round 2

## Phase 1 — Scope (company/school/semester) selectors
- [ ] 1.1 Add `Scope` module: Repository contract + Eloquent impl (list companies / schools-of-company / years-of-school filtered by user)
- [ ] 1.2 Action: `SetScopeAction` — validates and persists to session
- [ ] 1.3 Controller + routes: `GET /scope/options`, `POST /scope`
- [ ] 1.4 Navbar: render three cascading `<select>` elements bound via small JS

## Phase 2 — Font-size picker
- [ ] 2.1 Blade: add submenu to profile dropdown with 3 options
- [ ] 2.2 JS: persist to localStorage, apply `font-size` on `<html>` at boot
- [ ] 2.3 Translation keys `shell.font_size_*`

## Phase 3 — Sidebar reorganisation
- [ ] 3.1 Rewrite `components/sidebar.blade.php` with 4 QA-specified groups
- [ ] 3.2 Keep working routes, use `#` for not-yet-built items
- [ ] 3.3 Verify role-gating (super/school admin vs teacher/student/parent) still respected

## Phase 4 — Dashboard data plumbing
- [ ] 4.1 Repo additions: `mostActive(schoolId, companyId)`, `weeklyActivity(schoolId)`
- [ ] 4.2 Actions: `GetMostActiveAction`, `GetWeeklyActivityAction`
- [ ] 4.3 Controller methods + routes `/api/dashboard/most-active`, `/api/dashboard/weekly-activity`

## Phase 5 — Dashboard Blade sections
- [ ] 5.1 Section 2 — progress bars (server-render from action output)
- [ ] 5.2 Section 3 — content stats tiles
- [ ] 5.3 Section 4 — various stats tiles
- [ ] 5.4 Section 5 — weekly absence chart (Chart.js CDN)
- [ ] 5.5 Section 6 — most-active tables
- [ ] 5.6 Section 7 — weekly activity chart

## Phase 6 — Deploy + verify + QA comments
- [ ] 6.1 Commit (two commits, one per card)
- [ ] 6.2 Deploy (git pull on live, view:cache)
- [ ] 6.3 Playwright verification
- [ ] 6.4 Arabic comment on card A + move to testing done + reassign to Mahmoud
- [ ] 6.5 Arabic comment on card B + move to testing done + reassign to Mahmoud

## Progress
| Phase | Total | Done | Status |
|-------|-------|------|--------|
| 1 | 4 | 0 | Not Started |
| 2 | 3 | 0 | Not Started |
| 3 | 3 | 0 | Not Started |
| 4 | 3 | 0 | Not Started |
| 5 | 6 | 0 | Not Started |
| 6 | 5 | 0 | Not Started |
| **Total** | **24** | **0** | **0%** |
