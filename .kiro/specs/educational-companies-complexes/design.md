# Design: الشركات والمجمعات التعليمية

## Decisions
1. **Single source of truth = `compound_school` pivot.** No `schools.complex_id`. School's complex is the
   (enforced-single) pivot row. `SchoolController::store/update` calls `$school->compounds()->sync(array_filter([$complexId]))`.
2. **Add `Compound::company()`** belongsTo `EducationalCompany` (col `educational_company_id` already there).
   Add `EducationalCompany::compounds()` hasMany.
3. **Reuse existing models + Compound CRUD.** New admin controllers `EducationalCompanyController` and
   `EducationalComplexController` (or reuse `admin.qb.compounds.*`) operating on the same tables. Prefer new
   thin controllers in a small module/namespace so the admin area is clean; they must not fork the data model.
4. **Header empty-state audit FIRST.** Read `navbar.blade.php:205-271` + `EloquentScopeRepository`. Determine
   what "no selection" means today (does super-admin see all-schools implicitly?). Only then add the complex
   box + remove any "all" per card. Document what changed for the handback.

## Data / migrations
- Migration: add `Compound::company()` needs no column (exists). Ensure `educational_companies` has `logo`,
  `status` (per model fillable). If schools table lacks nothing new (we use pivot), no schools migration.
- `compound_school` unique (compound_id, school_id) already exists → single-complex enforced by sync + a
  guard (a school appears in at most one pivot row after sync).

## Pages
- **Companies** (`admin.educational-companies.*`): index (table + counts via `withCount('compounds','schools')`
  — add `schools()` hasMany already on EducationalCompany), store/update (name+logo), destroy, `show` → redirect
  to complexes index with `?company=`.
- **Complexes** (`admin.educational-complexes.*`): index (filter company + name), store/update (name+logo+company),
  destroy. Counts: `withCount('schools')` via pivot.
- **Schools form** (`admin/schools/_form.blade.php`): two selects; complex options AJAX by company (add endpoint
  `admin.educational-complexes.byCompany` returning `{id,name}`). Preselect current pivot complex on edit.
- **Schools index** (`admin/schools/index.blade.php` + `SchoolController::index`): add company/complex columns
  (`$school->educationalCompany?->name ?? 'بدون شركة تعليمية'`, `$school->compounds->first()?->name ?? 'بدون
  مجموعة تعليمية'`), add filters (name, company, complex, "بدون شركة").
- **Header** (`navbar.blade.php` + `EloquentScopeRepository` + `SetScopeAction`): add `complex_id` to the scope
  session + a `complexesFor(companyId)` repo method; make `schoolsFor` respect `complex_id` and the "بدون شركة"
  pseudo-value (`company_id = '__none__'` → schools with null company, disable complex box). Server-side validate
  in `SetScopeAction`. Remove any "all"/empty default; require a concrete pick.
- **صفوف وفصول**: a page listing `ClassRoom` across schools with company/complex/school columns (new controller
  or extend an existing classes listing).

## Cascade rules (header + filters)
company → complex (complexes of company) → school (schools of complex, or of company if no complex) → term.
Multi-select only where the card says (schools-page filters allow multi; header is single-select per box).
"بدون شركة" in company box: complex box disabled/empty; school list = companyless schools.

## Phase 0 audit findings (DONE — read before touching header)
- "all" sentinel exists on company/school/year selects (`navbar.blade.php:355,363,371`, `value="all"`);
  `SetScopeAction::nullableInt` (:94-98) maps `'all'`/`''`/`null` → null. Selecting "all" stores null.
- **null `scope.school_id` is overloaded for super-admin**: it means "global / all schools" for the
  see-all family (`HasSchoolScope::scopedSchoolId/listSchoolId` :69-99, `DashboardController:80-90`,
  `->when($schoolId,…)` consumers) BUT means "empty result" for unconditional-`where('school_id',$id)`
  consumers (`ClassPeriodController:23-27`, TimeSlot/ScheduleEntry/QR). No single enforced meaning.
- Scope module has NO compound wiring today; `compound_id` net-new to scope. `ScopeController::set`
  forwards only company/school/year (drops academic_term_id too).
- `Compound` model DOES exist at `app/Modules/QuestionBankCore/Models/Compound.php` (audit grep only
  looked in app/Models). CRUD at `admin.qb.compounds.*`. `compounds` + `compound_school` tables present.

## ✅ RESOLVED (user, 2026-09-05): Option A — Keep implicit global
Header change is SAFE-mode: remove the visible «الكل» option LABEL, add the complex selector +
«بدون شركة» option, but when super-admin picks nothing the app still behaves as global/all-schools
(do NOT change the null-school = global semantics; do NOT force a concrete school). Never touch the
unconditional-`where('school_id',$id)` consumers. So: add `compound_id` to scope, add complex select
that cascades from company, add «بدون شركة» (companyless) filter — WITHOUT removing the implicit
global fallback. Below is the original analysis kept for reference.

## ⚠️ (superseded) OPEN PRODUCT DECISION
Card #1 says "no «all» option anywhere; pick exactly one". But removing "all" removes super-admin's
GLOBAL view (which is implemented AS null-school). Options:
  (A) Keep an implicit global when nothing chosen, only forbid a literal "all" label — minimal risk.
  (B) Force a concrete school always — breaks super-admin global dashboards/user directory until every
      such consumer is reworked. High blast radius.
Do NOT choose silently. When reaching Phase 4, ask Mohamed Farouk (creator) / the user which behavior
they want for super-admin with no selection. CRUD (Phases 1-3, 5) + school-form + schools-table are
safe to build first without touching the header; defer Phase 4 until this is answered.

## Verify
Local: create company → complex (linked) → assign a school its complex via school edit; schools table shows
both + "بدون" for unlinked; company filter + "بدون شركة" work; header complex box cascades and scoping still
returns correct data on a scoped page (e.g. students). Confirm QB scope-selector (which also uses compounds)
still works — no regression. Screenshots.
