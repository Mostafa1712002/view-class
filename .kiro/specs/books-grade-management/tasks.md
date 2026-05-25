# Tasks: Books — Grade Management

## Phase 1: Schema
### Task 1.1: Pivot table
- [x] Migration `bk_create_school_grade_books_table` (school_id, class_id, book_id + unique triple)

**Outcome:** school_grade_books table.

## Phase 2: Domain
### Task 2.1: Repository
- [x] Contract methods: availableBooksForSchool, linkedBookIdsByClass, classIdsForSchool, syncSchoolGradeBooks
- [x] Eloquent implementation (ministry ∪ school pool; transactional, scope-guarded replace)
### Task 2.2: Action
- [x] SyncSchoolGradeBooksAction wrapping DB::transaction with class/book scope validation
### Task 2.3: Controller + routes
- [x] BookGradeController index/save (HasSchoolScope, empty-state when no school)
- [x] manage.books.grades (GET) + manage.books.grades.save (POST)

## Phase 3: UI
### Task 3.1: View + lang
- [x] grades.blade.php — BS4 accordion per stage, grades, per-grade book checkboxes, select-all/clear-all, save
- [x] lang/ar/books_admin.php `grades` block + link button on books index

## Phase 4: Verification
- [x] Local: render + save round-trip + persisted checked state (php built-in server)
- [ ] Live: deploy + Playwright verify + screenshots

---

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1 Schema | 1 | 1 | Done |
| 2 Domain | 3 | 3 | Done |
| 3 UI | 1 | 1 | Done |
| 4 Verify | 2 | 1 | In progress |
| **Total** | **7** | **6** | **86%** |
