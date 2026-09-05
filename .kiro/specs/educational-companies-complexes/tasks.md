# Tasks: الشركات والمجمعات التعليمية

## Phase 0: Audit (do first)
### Task 0.1
- [x] Read `navbar.blade.php` + `EloquentScopeRepository` + `SetScopeAction`: documented in design.md.
      FINDING: "all"=null=super-admin global view (overloaded). ⚠️ Phase 4 (header) BLOCKED on a product
      decision (keep implicit global vs force one school) — ask creator/user before building it.
      Phases 1-3,5 (model, CRUD, school form/table, classes list) are safe to build first.

## Phase 1: Model reconciliation
### Task 1.1
- [x] Add `Compound::company()` + `EducationalCompany::compounds()`/`schools()` relations. Ensure fillable/logo/status.
      Added `School::compounds()` belongsToMany too (needed by index/form/header). Added a migration for
      `compounds.logo` (missing — design.md's complexes CRUD needs it, `educational_companies.logo/status`
      already existed so no migration needed there).

## Phase 2: Companies + Complexes CRUD
### Task 2.1
- [x] `EducationalCompanyController` + routes + index/modal/store/update/destroy/show(→complexes filtered). Counts.
### Task 2.2
- [x] `EducationalComplexController` + routes + index(filter company/name)/modal(store link company)/update/destroy. `byCompany` AJAX endpoint.

## Phase 3: Schools integration
### Task 3.1
- [x] Schools `_form`: company + complex cascading selects (not required); `store/update` sync single pivot complex.
### Task 3.2
- [x] Schools index: company + complex columns ("بدون…" fallback) + filters (name/company/complex/"بدون شركة").

## Phase 4: Header scope
### Task 4.1
- [x] Add complex selector + `complex_id` to scope session; `complexesFor()`; `schoolsFor` honors complex + "بدون شركة"; no "all"; validate in SetScopeAction.
      Option A confirmed: null company/school still = super-admin global (untouched). Found and fixed the
      SAME selectors duplicated in a mobile scope strip further down navbar.blade.php — both updated.

## Phase 5: صفوف وفصول
### Task 5.1
- [x] Classes-across-schools listing with company/complex/school columns. New `ClassesOverviewController` +
      `admin.classes-overview.index`, wired into the sidebar `nav_classes` placeholder.

## Phase 6: Verify + ship
### Task 6.1
- [x] Local end-to-end + QB scope-selector regression check. Screenshots taken during verification (not
      committed to repo). See handback report for full results.
### Task 6.2
- [ ] Deploy (git pull + migrate --force + view:cache). Live smoke incl. a scoped page. — NOT done (card
      says do not deploy; ship on user's go-ahead).
### Task 6.3
- [ ] Arabic handback; move; reassign Mohamed Farouk. — pending deploy.

## Progress

| Phase | Tasks | Done | Status |
|-------|-------|------|--------|
| 0 audit | 1 | 1 | Done |
| 1 model | 1 | 1 | Done |
| 2 CRUD | 2 | 2 | Done |
| 3 schools | 2 | 2 | Done |
| 4 header | 1 | 1 | Done |
| 5 classes | 1 | 1 | Done |
| 6 ship | 3 | 1 | Verify done, deploy pending |
| **Total** | **11** | **9** | **~82%** |
