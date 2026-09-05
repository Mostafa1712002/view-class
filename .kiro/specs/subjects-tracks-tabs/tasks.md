# Tasks: المواد والمسارات

## Task 1: Shared tabs partial
- [ ] Create `resources/views/admin/subjects/_tabs.blade.php` (nav-tabs: المواد → `admin.subjects.index`, المسارات → `admin.subject-tracks.index`) modeled on `admin/reports/_tabs.blade.php`.
- [ ] Include `@include('admin.subjects._tabs', ['current'=>'subjects'])` in subjects index; `['current'=>'tracks']` in tracks index.

## Task 2: Rename
- [ ] Page title/heading → "المواد والمسارات" on both pages (via lang key). Update `lang/ar/subject_tracks.php` / add `lang/ar/subjects.php` key as needed.
- [ ] Sidebar label at `sidebar.blade.php:562` group + new-section item (coordinate with sidebar-restructure).

## Task 3: Verify + ship
- [ ] Local: open both tabs, switch, confirm both lists + CRUD work, scope intact. Screenshot.
- [ ] Deploy (git pull + view:cache). Live smoke.
- [ ] Arabic handback comment; move; reassign Mohamed Farouk.

## Progress
| Task | Done |
|------|------|
| 1 tabs | ☐ |
| 2 rename | ☐ |
| 3 ship | ☐ |
