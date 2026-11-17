# Tasks — order: 374 → 373 → 365 → 366 → 367 → 370 → 369 → 372 → 371 → 368
Why: 374 gives a guard test re-run after each card; 373 gives data the others are tested against; 365–369 are small (existing modules, wiring); 372/371/368 are new modules.

- [x] 374 RBAC test + fixes
- [x] 373 dashboard real queries + demo data (viewclass only)
- [x] 365 companies/complexes E2E
- [x] 366 promotion per-student guard + sidebar
- [x] 367 current week + sidebar
- [x] 370 exams lifecycle + sidebar
- [x] 369 distance-learning hub + sidebar
- [x] 372 coaching module
  - [x] 372 round 2 (11-17, Farouk): grade + class filters, multi-student pick, «اختيار كل الطلاب» on create (57cdea1)
- [ ] 371 points & rewards
- [ ] 368 special programs

## Open questions (not posted yet)
- 371: default point values per activity? (file gives the mechanism, not numbers) — default: 0 until admin sets; teacher/agent points (preparation, visits, schedule) phase 2.
- 371: rewards catalogue / redemption — not in the file; skipped.
- 368: confirm native build (no الأول بلس API) and score fields (exam score / level per skill?).
- 372: reference https://my.viewclass.com/admin/Coaching needs a login — can someone share screenshots or an account?
- 369: no approved scope beyond virtual classes + discussion rooms + attendance.
- 373: demo data on rawaniedu? (default: no, client data)

## Status 2026-11-16
Live on viewclass + rawaniedu at 7c304f2 (backups /root/backups/*-pre-sprint-20261116.sql.gz). Demo data: viewclass school 1 only (`vc:demo-data --school=1 --remove` undoes it).
8 cards → testing prompt (Mohamed Farouk). 371 + 368 blocked: questions posted on the cards.

## Status 2026-11-17
372 round 2 live on viewclass + rawaniedu at 57cdea1: create form filters students by «الصف الدراسي» (section) and «الفصل» (class column or class_student pivot), multi-select, select-all resolved on the server; one case per student. «فصل دراسي وصف» read as grade + class (students are not linked to academic terms) — noted on the card. Back in testing prompt.
