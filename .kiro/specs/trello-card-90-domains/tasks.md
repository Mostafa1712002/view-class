# Tasks: Card #90 — Subject Domains

## Phase 1: Domains module (the dead المجالات button)
- [x] Migration: domains table
- [x] Model: Domain + Subject::domains()
- [x] Routes: domains index/store/update/destroy
- [x] Controller: domains, storeDomain, updateDomain, destroyDomain
- [x] View: admin/subjects/domains.blade.php (table, add/edit/tree modals, search)
- [x] Lang keys (ar/en)
- [x] Wire index.blade dropdown link (remove disabled href="#")
- [x] Verify live: link opens page; add/edit/delete/tree work; scoped to subject

**Outcome:** ✅ Deployed (7f62de1) + migrated live; verified end-to-end live
(link → page → add/edit/delete via BS4 modals) and locally (CRUD + validation).

## Phase 2: Subjects page audit (mostly already built)
- [x] Confirm add-subject form, Excel import, ready-made templates exist + work — present (subjects.create / subjects.import.store / subjects.templates.index); subjects index renders with the 3-option add menu + credit-values
- [~] Detailed field-by-field parity of the add-subject form — note as follow-up if QA wants it

## Progress
| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Domains | 8 | 8 | ✅ Done + verified live |
| 2. Audit | 2 | 1 | ✅ Reviewed (parity = follow-up) |
| **Total** | **10** | **9** | **90%** |
