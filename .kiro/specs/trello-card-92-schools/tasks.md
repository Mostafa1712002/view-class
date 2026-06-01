# Tasks: Card #92 — Schools

## Phase 1: Concrete bugs (explicit card asks)

### Task 1.1: Bug 1 — Arabic permission group labels
- [ ] Add `permission_groups` map to lang/ar/schools.php
- [ ] Add `permission_groups` map to lang/en/schools.php
- [ ] Render label in permissions/index.blade.php (keep raw key in data-group)
- [ ] Verify live: all main-function buttons show Arabic

### Task 1.2: Bug 2 — Books button on grade-levels
- [ ] BookGradeController: honor optional `?school=` (index + save)
- [ ] grade_levels/index.blade.php: add الكتب button
- [ ] Verify live: button present, opens books for THIS school

### Task 1.3: Bug 3 — Class edit + view
- [ ] Routes: classes.show, classes.edit, classes.update
- [ ] Controller: showClass, editClass, updateClass (with ownership guards)
- [ ] Views: class_show.blade.php, class_edit.blade.php
- [ ] classes.blade.php: add عرض + تعديل buttons
- [ ] Verify live: edit pre-fills + saves; view shows details

---

## Phase 2: Module audit (remaining, broad spec) — NOT in first deploy
Most of the 32-page spec already exists. Genuine gaps to confirm with QA/user
before building (do NOT silently mark card done):
- [ ] Year rollover multi-type migration (ترحيل الفصول/الطلاب/الفترات/الحصص) — only `promote` exists
- [ ] Lesson-distribution table (جدول توزيع الدروس) coverage
- [ ] Search/filter/column-customize/export across school tables

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Concrete bugs | 3 | 0 | In Progress |
| 2. Audit | 3 | 0 | Deferred (needs user/QA) |
| **Total** | **6** | **0** | **0%** |
