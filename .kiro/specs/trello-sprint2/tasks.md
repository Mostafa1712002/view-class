# Tasks: Trello Sprint 2

Pipeline: `sprint 2 prompt` → coding → `testing` → `sprint 1 done`. Each card moves to `testing` after deploy + live verify + Arabic comment.

Order: Card 20 (schools) → 21 (settings) → 22 (years) → 23 (grades/sections) → 24 (permissions).

## Phase 1 — Card 20: Schools enhancement
- [ ] 1.1 Update SchoolController validation to accept all sprint-2 columns
- [ ] 1.2 Update create.blade.php form: name_ar/en, branch, sort_order, educational_track, stage, city, default_language, logo
- [ ] 1.3 Update edit.blade.php form: same fields
- [ ] 1.4 Update index.blade.php table: sort_order, sections count, classes count, students count, licensed students, control menu (settings/years/grades/permissions/show/edit/delete)
- [ ] 1.5 Add school-scoped routes: `/admin/schools/{school}/settings`, `/academic-years`, `/grade-levels`, `/permissions`
- [ ] 1.6 i18n keys for all new labels (ar + en)
- [ ] 1.7 Deploy + live verify
- [ ] 1.8 Arabic comment + move card 20 to testing

## Phase 2 — Card 21: General settings
- [ ] 2.1 Migration: ensure schools.settings JSON exists ✅ (already present)
- [ ] 2.2 Action: SaveSchoolSettingsAction
- [ ] 2.3 Controller: SchoolSettingsController (show, update)
- [ ] 2.4 View: settings.blade.php with grouped sections
- [ ] 2.5 i18n keys
- [ ] 2.6 Deploy + verify
- [ ] 2.7 Arabic comment + move card 21

## Phase 3 — Card 22: Academic years
- [ ] 3.1 Migrations: terms (name, start, end, is_current, academic_year_id), weeks (term_id, start, end, sort_order)
- [ ] 3.2 Models: Term, Week + relationships on AcademicYear
- [ ] 3.3 Repository + Actions: CreateYear, AddTerm, SetCurrentTerm, AddWeeks, PromoteYear (stub)
- [ ] 3.4 Update AcademicYearController + views
- [ ] 3.5 i18n keys
- [ ] 3.6 Deploy + verify
- [ ] 3.7 Arabic comment + move card 22

## Phase 4 — Card 23: Grade levels + sections
- [ ] 4.1 Migrations: grade_levels (per-school enabled flag), update sections (capacity, lead_teacher_id), pivot students to sections
- [ ] 4.2 Models + relations
- [ ] 4.3 Controllers: GradeLevelController, SectionController (enhance)
- [ ] 4.4 Views: grade-levels index + assign, sections inside grade, students inside section + transfer
- [ ] 4.5 i18n keys
- [ ] 4.6 Deploy + verify
- [ ] 4.7 Arabic comment + move card 23

## Phase 5 — Card 24: Permissions matrix
- [ ] 5.1 Migrations: roles (if missing), permissions (function + sub_function), school_role_permissions (school_id, role_id, permission_id)
- [ ] 5.2 Seed roles (19 roles from spec) + functions/sub-functions
- [ ] 5.3 PermissionsController with auto-save AJAX endpoint
- [ ] 5.4 Permissions matrix view (3-column UI) + copy-from-school modal
- [ ] 5.5 i18n keys
- [ ] 5.6 Deploy + verify
- [ ] 5.7 Arabic comment + move card 24

## Progress

| Phase | Tasks | Done | Status |
|-------|-------|------|--------|
| 1 Schools | 8 | 8 | ✅ shipped + tested |
| 2 Settings | 7 | 7 | ✅ shipped + tested |
| 3 Years | 7 | 7 | ✅ shipped + tested |
| 4 Grades/Sections | 7 | 7 | ✅ shipped + tested |
| 5 Permissions | 7 | 7 | ✅ shipped + tested |
| **Total** | **36** | **36** | **100%** |

All 5 cards moved to `testing` list. 5 migrations ran on prod (academic_terms, study_weeks, lead_teacher_id on classes, school_role_permissions). 4 new commits on main: 147e021 → 55755d9 → da427cc → c747cd1 → 492185b → 61ef51a.
