# Tasks: Sprint 1 QA Round 2

## Phase 1 — Scope (company/school/semester) selectors
- [x] 1.1 Add `Scope` module: Repository contract + Eloquent impl (list companies / schools-of-company / years-of-school filtered by user)
- [x] 1.2 Action: `SetScopeAction` — validates and persists to session
- [x] 1.3 Controller + routes: `GET /scope/options`, `POST /scope`
- [x] 1.4 Navbar: render three cascading `<select>` elements bound via small JS

## Phase 2 — Font-size picker
- [x] 2.1 Blade: add submenu to profile dropdown with 3 options
- [x] 2.2 JS: persist to localStorage, apply `font-size` on `<html>` at boot
- [x] 2.3 Translation keys `shell.font_size_*`

## Phase 3 — Sidebar reorganisation
- [x] 3.1 Rewrite `components/sidebar.blade.php` with 4 QA-specified groups
- [x] 3.2 Keep working routes, use `#` for not-yet-built items
- [x] 3.3 Verify role-gating (super/school admin vs teacher/student/parent) still respected

## Phase 4 — Dashboard data plumbing
- [x] 4.1 Repo additions: `mostActive(schoolId, companyId)`, `weeklyActivity(schoolId)`
- [x] 4.2 Actions: `GetMostActiveAction`, `GetWeeklyActivityAction`
- [x] 4.3 Controller methods + routes `/api/dashboard/most-active`, `/api/dashboard/weekly-activity`

## Phase 5 — Dashboard Blade sections
- [x] 5.1 Section 2 — progress bars (server-render from action output)
- [x] 5.2 Section 3 — content stats tiles
- [x] 5.3 Section 4 — various stats tiles
- [x] 5.4 Section 5 — weekly absence chart (Chart.js CDN)
- [x] 5.5 Section 6 — most-active tables
- [x] 5.6 Section 7 — weekly activity chart

## Phase 6 — Deploy + verify + QA comments
- [x] 6.1 Commit (two commits, one per card)
- [x] 6.2 Deploy (git pull on live, view:cache)
- [x] 6.3 Playwright verification
- [x] 6.4 Arabic comment on card A + move to testing done + reassign to Mahmoud
- [x] 6.5 Arabic comment on card B + move to testing done + reassign to Mahmoud

## Progress
| Phase | Total | Done | Status |
|-------|-------|------|--------|
| 1 | 4 | 4 | Done |
| 2 | 3 | 3 | Done |
| 3 | 3 | 3 | Done |
| 4 | 3 | 3 | Done |
| 5 | 6 | 6 | Done |
| 6 | 5 | 5 | Done |
| **Total** | **24** | **24** | **100%** |
