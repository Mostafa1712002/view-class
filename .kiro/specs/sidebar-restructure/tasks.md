# Tasks: Sidebar Restructure

## Phase 1: Prep
### Task 1.1: Placeholder decision
- [ ] Read `resources/views/programs/coming-soon.blade.php`; confirm text vs card string.
- [ ] Pick placeholder mechanism (reuse `programs.coming-soon` vs parametric `/soon/{slug?}`).
- [ ] Add placeholder route(s) if needed in `routes/web.php`.

**Outcome:** placeholder target ready. **Dependencies:** None.

## Phase 2: Labels
### Task 2.1: shell.php strings
- [ ] Add `section_extras/academic/assessment/support/admissions/comm2` + any new `nav_*` labels to `lang/ar/shell.php`.

**Outcome:** all new labels resolve. **Dependencies:** None.

## Phase 3: Sidebar markup (serialized — one editor only)
### Task 3.1: Swap programs order
- [ ] Sidebar :543–545 — put الأول بلس before أنا والقدرات.
### Task 3.2: Section #1 الإضافات والإسنادات (sec-extras, above sec-educational)
- [ ] Header + content. Items: companies (`admin.educational-companies.index` — pending sibling spec; `Route::has` guard), complexes, schools, classes, team(placeholder), students, parents, promotion(placeholder), terms-weeks(placeholder).
### Task 3.3: Section #2 الشؤون التعليمية والخطط (sec-academic)
- [ ] school-schedule, weekly-plans, إدارة المواد[subjects/lessons/exams], special-ed(placeholder), distance(placeholder), virtual-classes, discussion-rooms, question-banks, qb.skills.
### Task 3.4: Section #3 القياس والتقويم (sec-assessment)
- [ ] evaluations, my-evaluations, evaluations.approvals, إدارة الدرجات[grade-reports/grades.entry/grades], exams(placeholder), class-visits.
### Task 3.5: Section #4 الدعم والتقارير (sec-support)
- [ ] books, libraries[public/private/labs], educational-sites, surveys, reports[3], points(placeholder), certificates, announcements, school-calendar, counseling(placeholder).
### Task 3.6: Section #5 القبول والتسجيل (sec-admissions)
- [ ] admissions.
### Task 3.7: Section #6 التواصل والدعم الفني (sec-comm2)
- [ ] mailbox, sms-messages[sub], parents-contact, support.

**Outcome:** six sections render additively. **Dependencies:** 1.1, 2.1.

## Phase 4: Verify + ship
### Task 4.1: Local verify
- [ ] `view:clear`; login super-admin; expand each new section; click every link → no 500; existing nav intact; الأول بلس first. Before/after screenshots.
### Task 4.2: Deploy
- [ ] commit → push → SSH git pull → `view:clear && view:cache`. Live smoke same checklist.
### Task 4.3: Handback
- [ ] Arabic QA comment on #1,#2,#4,#5 (note duplicate-highlight is expected); move to prompt done/testing; reassign to Mohamed Farouk. Note #3,#6 status.

**Outcome:** cards handed back. **Dependencies:** 4.1.

## Progress
| Phase | Tasks | Done | Status |
|-------|-------|------|--------|
| 1 Prep | 1 | 0 | Not started |
| 2 Labels | 1 | 0 | Not started |
| 3 Markup | 7 | 0 | Not started |
| 4 Ship | 3 | 0 | Not started |
| **Total** | **12** | **0** | **0%** |
