# Design: Card #92 — Schools fixes

## Bug 1 — permission group labels
- `permissions.group` stores English keys (academic-years, attendance, classes,
  exams, grades, reports, roles, schedules, schools, sections, settings,
  subjects, users, weekly-plans). The sub-permissions (`permissions.name`) are
  already Arabic; only the group column is raw.
- Add a `permission_groups` map to `lang/{ar,en}/schools.php`.
- In `permissions/index.blade.php` line 64 render
  `{{ __('schools.permission_groups.'.$g) }}` for the **button text**, but keep
  `data-group="{{ $g }}"` as the raw key (the JS indexes `permissionsByGroup`
  by the raw key). `__()` returns the key itself when missing → safe fallback.

## Bug 2 — books button on grade-levels
- `manage.books.grades` (BookGradeController@index) resolves the school via
  `activeSchoolId()` (navbar session scope). A super-admin viewing school N's
  grade-levels may have a different/empty scope → would look broken.
- Make `BookGradeController` honor an optional `?school=<id>` query param:
  - `resolveBookSchoolId(Request)`: if `?school` present AND user is super-admin
    or owns that school → use it; else `activeSchoolId()`.
  - `index()` uses it for display; `save()` uses it too (form carries a hidden
    `school` input echoing the resolved id when present) so saving stays on the
    same school.
- grade-levels/index.blade.php: add
  `<a href="{{ route('manage.books.grades', ['school' => $school->id]) }}">الكتب</a>`
  button to the actions cell.

## Bug 3 — class edit + view
New routes (inside `schools/{school}` group, `schools.` name prefix):
| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET  | grade-levels/{section}/classes/{class}        | grade-levels.classes.show   | SchoolGradeLevelController@showClass   |
| GET  | grade-levels/{section}/classes/{class}/edit   | grade-levels.classes.edit   | SchoolGradeLevelController@editClass   |
| PUT  | grade-levels/{section}/classes/{class}        | grade-levels.classes.update | SchoolGradeLevelController@updateClass |

- `showClass`: read-only details (name, grade number, capacity, vacancies,
  students count, lead teacher, academic year) + link to students.
- `editClass`: form pre-filled (reuses add-class fields).
- `updateClass`: validates same rules as `storeClass`, capacity ≥ current
  students count, saves, redirects to classes list.
- All three `abort_unless(section->school_id===school->id && class->section_id===section->id, 404)`.
- New views: `grade_levels/class_show.blade.php`, `grade_levels/class_edit.blade.php`.
- classes.blade.php actions: add عرض (la-eye → show) and تعديل (la-edit → edit)
  before the existing students/delete buttons.

## Technology
- Laravel 12 / PHP 8.4, Blade + Bootstrap 4 admin theme.
