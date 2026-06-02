# Tasks: استيراد الطلاب من ملف إكسل (Trello #108)

## Phase 1: Backend
### Task 1.1: Migration + module
- [x] `student_imports` migration
- [x] StudentImportRowDto + StudentImportResult
- [x] ParseStudentExcel (English headers)
- [x] ClassifyStudentRows (required + dup + grade/class + username)
- [x] ImportStudentsAction (users + profile + class link + parents; password-safe)
- [x] StudentImportRequest
- [x] StudentImportController (form/template/preview/execute/errors)
- [x] Routes

## Phase 2: UI + wiring
- [x] form.blade (header, archive, steps, template link, grade buttons, columns table)
- [x] preview.blade + result.blade
- [x] lang ar/en student_import.php
- [x] Fix dropdown link in students/index.blade.php
- [x] Commit template xlsx into repo

## Phase 3: Verify
- [x] Local: import new row → created (user+profile+class+parent)
- [x] Local: re-import same id → updated, password unchanged
- [x] Local: bad grade → invalid row, not imported
- [x] Deploy + migrate
- [x] Live: button opens Excel page; full flow with throwaway data + cleanup
- [x] Arabic QA comment + reassign + move to testing prompt

---
## Progress Tracking
| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Backend | 8 | 8 | Done |
| 2. UI | 5 | 5 | Done |
| 3. Verify | 7 | 4 | In Progress |
| **Total** | **20** | **17** | **85%** |
