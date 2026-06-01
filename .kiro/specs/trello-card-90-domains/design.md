# Design: Card #90 — Subject Domains

Mirrors the existing `lesson-tree` feature (subject-scoped CRUD on SubjectController).

## DB
`domains` table: id, subject_id (FK→subjects, cascade), name (string), sort_order (int), timestamps, softDeletes. Index (subject_id).

## Model
`App\Models\Domain` (SoftDeletes): fillable subject_id,name,sort_order; belongsTo Subject.
`Subject::domains()` hasMany(Domain)->orderBy('sort_order').

## Routes (admin prefix, inside subjects group)
| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET    | subjects/{id}/domains            | subjects.domains         | SubjectController@domains |
| POST   | subjects/{id}/domains            | subjects.domains.store   | SubjectController@storeDomain |
| PUT    | subjects/{id}/domains/{domainId} | subjects.domains.update  | SubjectController@updateDomain |
| DELETE | subjects/{id}/domains/{domainId} | subjects.domains.destroy | SubjectController@destroyDomain |

All resolve `$subject = $this->subjects->findScoped($id, $this->activeSchoolId())` + `abort_if(!$subject,404)`; domain fetched via `$subject->domains()->whereKey($domainId)->firstOrFail()`.

## View `admin/subjects/domains.blade.php`
- content-header "إدارة المجالات — {subject}".
- toolbar: "إضافة مجال" (opens add modal), "شجرة المجالات" (opens tree modal).
- search input (client-side filter of table rows by name).
- table: المجالات (name) | القالب (— placeholder; templates not wired yet) | التحكم (edit→modal, delete→form+confirm). Empty-state when none.
- add modal: name (required) → POST store.
- edit modal: pre-filled name → PUT update (JS sets action + value from clicked row).
- tree modal: root "المجالات" with each domain as a child li (or empty note).
- BS4 modals (`data-toggle="modal"`, `data-target`), include both bs4+bs5 attrs like existing code (line 177 pattern).

## Wire-up
index.blade line 263: replace the disabled `href="#"` standards item with
`href="{{ route('admin.subjects.domains', $subject->id) }}"` (remove `disabled`),
label `sprint4.subjects.standards` ("المجالات والمعايير").

## Lang (lang/{ar,en}/sprint4.php or domains.* keys)
domains page title, add/edit/delete, name label, tree, empty, template, confirm.
