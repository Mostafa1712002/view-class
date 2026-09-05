# Requirements: الشركات والمجمعات التعليمية (card #1 backend)

## Overview
Card "#1 [الإضافات والإسنادات]" (creator: Mohamed Farouk). Beyond the sidebar section (see
`sidebar-restructure`), card #1 introduces the company/complex hierarchy and its management pages,
plus header-selector and schools-page changes. This is the **reconciliation card** the existing
`compounds` migration comment anticipated.

## Hierarchy (card)
شركة تعليمية (company) → مجمعات تعليمية (complexes) → مدارس (schools). School→complex is OPTIONAL
(ليست الزامية). Complex→company link exists.

## Grounding (most exists)
- `EducationalCompany` model + `educational_companies` table (no CRUD UI). `School::educationalCompany()`.
- `Compound` (=المجمع/complex) model + `compounds` table (`educational_company_id` nullable) +
  `compound_school` M2M pivot + full CRUD at `admin.qb.compounds.*` (QuestionBank module, global super-admin).
- Header scope selectors: `navbar.blade.php:352-378` (company + school + academic_year) via
  `EloquentScopeRepository` (`companiesFor/schoolsFor/yearsFor/termsFor`), persisted through `scope.set`.
- Schools: `Admin\SchoolController` (resource, super-admin), `_form.blade.php`, index table.
  `schools` has `educational_company_id`, `branch`, `branch_id` — NO `complex_id`.

## Acceptance criteria
- **Companies page** (`الشركات التعليمية`): table [عداد | الشعار | الاسم | المجموعات(count) | المدارس(count) |
  الإجراءات: تعديل/عرض/حذف]. Add-company modal: name + logo. Simple name search filter. زر عرض → navigate to
  complexes page filtered to that company.
- **Complexes page** (`المجمعات التعليمية`): table [عداد | الشعار | الاسم | الشركة التعليمية | المدارس(count) |
  الإجراءات: تعديل/حذف]. Add modal: name + logo + company select (link). Filters: name search + company select.
- **Schools create/edit**: add two selects — الشركة التعليمية and المجمعة التعليمية — cascading (complex
  options depend on chosen company); neither required. Persist the school→complex link.
- **Schools index table**: columns عداد | شعار | اسم المدرسة | الشركة التعليمية | المجمع التعليمي | الفرع |
  الصفوف | الفصول | الطلاب | الطلاب المرخصين | الحالة | الأجراءات. Empty company/complex render "بدون شركة
  تعليمية/مجموعة تعليمية". Filters: name, company, complex, + "بدون شركة" option in company filter → shows
  schools with no company.
- **Header selectors**: add a المجموعة التعليمية (complex) selector alongside company/school/term. All four
  cascade (company→complex→school→term). **NO "all" option anywhere** — exactly one value per box. Company box
  gets a "بدون شركة" option → shows schools not linked to any company AND disables/empties the complex box.
- **صفوف وفصول page**: show classes across all schools with columns الشركة التعليمية | المجمع | المدرسة.

## Key decision (per advisor — do NOT create a second source of truth)
Do NOT add `schools.complex_id`. Reuse the existing `compound_school` M2M pivot but enforce
**one complex per school** at the action layer (`sync([$complexId])`, or detach when "بدون"). Add the missing
`Compound::company()` relation. Surface the existing `Compound` CRUD in the new admin area (either new
controllers under `admin.educational-companies.*` / `admin.educational-complexes.*` that reuse the models, or
re-point to `admin.qb.compounds.*`). Verify the current header empty/unselected state BEFORE changing it —
if super-admin currently relies on an implicit "all", removing it changes every scoped page.
