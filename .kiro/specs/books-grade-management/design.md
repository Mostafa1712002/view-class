# Design: Books — Grade Management

## Data model
```
sections (مرحلة)  id, name, school_id, level, is_active
classes  (صف)     id, name, section_id, grade_level, division, is_active
books             id, school_id (NULL=ministry), is_ministry, is_active, title, subject_id, ...
school_grade_books (NEW pivot)  id, school_id, class_id, book_id, timestamps
                   UNIQUE(school_id, class_id, book_id)
```

## Migration  `..._bk_create_school_grade_books_table`
- bigIncrements id
- unsignedBigInteger school_id, class_id, book_id (all indexed)
- unique (school_id, class_id, book_id)
- timestamps
(No FK constraints to stay consistent with the legacy int-PK / nullable-school pattern; integrity enforced in the Action.)

## Routes (manage. prefix, role super-admin/school-admin)
| Method | URI                  | Name                  | Action                       |
|--------|----------------------|-----------------------|------------------------------|
| GET    | books/grades         | manage.books.grades   | BookGradeController@index    |
| POST   | books/grades         | manage.books.grades.save | BookGradeController@save   |

Placed right after the existing book CRUD routes.

## Controller  `App\Modules\Books\Controllers\BookGradeController`
- uses `HasSchoolScope`
- `index()`:
  - $schoolId = activeSchoolId(); if null → view with `school=null` (empty state)
  - stages = sections of school (with classes, active)
  - books  = repo->availableBooksForSchool($schoolId) (ministry ∪ school, active, with subject)
  - linked = repo->linkedMap($schoolId) → [class_id => [book_id,...]]
- `save(Request)`:
  - validate `grades` array: grades.*.* are book ids
  - delegate to SyncSchoolGradeBooksAction
  - redirect back with success / error flash

## Action  `App\Modules\Books\Actions\SyncSchoolGradeBooksAction`
- `execute(int $schoolId, array<int classId, int[] bookIds> $selection): void`
- validates classIds belong to the school's sections and bookIds belong to the available pool
- DB::transaction:
  - delete all school_grade_books rows for $schoolId whose class_id ∈ school's classes
  - bulk insert the new (school_id, class_id, book_id) rows (deduped)

## Repository additions (`BookRepository` contract + Eloquent impl)
- `availableBooksForSchool(?int $schoolId): Collection` — ministry ∪ school, active, ordered.
- `linkedBookIdsByClass(int $schoolId): array` — map class_id => [book_id...] from pivot.
- `classIdsForSchool(int $schoolId): array` — valid class ids of the school (for scoping deletes/inserts).
- `syncSchoolGradeBooks(int $schoolId, array $rows): void` — transactional replace (used by Action).

## View  `resources/views/admin/books/grades.blade.php`
- extends layouts.app, theme-light
- header + breadcrumb + "إدارة كتب الصفوف"
- current school name banner
- if no school → empty state "اختر مدرسة من الأعلى"
- if no stages/grades → empty state with hint to create stages/classes
- else: one BS4 `card` per stage; inside, per grade a sub-block with the book checkboxes
  (name = `grades[<class_id>][]`, value = book_id), "تحديد الكل"/"إلغاء" buttons.
- single Save button (full form POST), CSRF.
- small inline JS for the per-grade select-all/clear-all.

## Lang
Add a `grades` block to `lang/ar/books_admin.php`.

## Sidebar
Add a sub-item "إدارة كتب الصفوف" under the existing "إدارة المواد" parent ONLY if the
sidebar is a data-driven menu (NOT editing sidebar.blade.php per rule). Otherwise expose the
link from the Books index page header (a button) — safe and discoverable.
