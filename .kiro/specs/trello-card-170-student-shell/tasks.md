# Tasks: Student Account Shell (#170)

## Done
- [x] T1: Setting flag `allow_previous_periods` (boolean, school-scoped, default false)
      via Setting::get/set — no migration needed (default handles absence).
- [x] T2: Scope term support — `academic_term_id` in session + SetScopeAction;
      ScopeRepository::termsFor()/termExistsFor() + Eloquent impl.
- [x] T3: Server-side previous-period gate in SetScopeAction (students forced to
      current year/term when flag off) — verified a raw POST is rejected.
- [x] T4: ResolvesStudentScope trait (effective year + term, current as default,
      gated). Resolver ALSO enforces the gate (ignores stale session when off).
- [x] T5: StudentController routes dashboard/grades/attendance/exams/schedule/
      weekly-plans through the resolver.
- [x] T6: Navbar student branch — account-type "الطالب" chip, static company/school
      context, gated year + term selectors (disabled when flag off), desktop + mobile.
- [x] T7: Sidebar — dynamic "مواد الطالب" group from grade+class (deduped by name+code),
      + named groups عمليات تعليمية / تقارير / مكتبات / تواصل / الدعم. Items gated by
      Route::has() + role. Libraries(#173)/discussion(Sprint9) show "قريباً" when no route.
- [x] T8: lang keys (ar + en) for new strings.
- [x] T9: Fixed pre-existing weekly-plans 500 (queried non-existent academic_year_id);
      now scoped by term/year DATE WINDOW; subjects filter resolved by grade (ClassRoom
      has no subjects() relation).
- [x] T10: Playwright verification (see design.md / report).

## Verification evidence
- Header: account type "الطالب", notifications bell+counter (real, user-scoped),
  mail/search/language/avatar, school+company+grade-class context — all present.
- Subjects sidebar: [اللغة العربية, الرياضيات, النشاط] from grade 1; 3 links all 200.
- Gate off (default): year/term selects disabled; raw POST to year 4 forced back to 1447.
- Gate on: switching navbar year 1447→1446 changed grades page from 3 subjects to 1
  (the year-4 grade) — no year mixing. Setting back off snaps resolver to current.
- All 9 student pages return 200.

## Deferred (later cards)
- مكتبات → #173 (links my.libraries.index; shows "قريباً" placeholder if route absent).
- تواصل (discussion rooms) → Sprint 9 (links discussion.index; "قريباً" if absent).
  Mailbox/virtual-classes/appointments wired to existing routes.
- Term-level data scoping limited to what is term-stamped (books, grade_reports,
  weekly-plans by date window). Exams/grades/attendance/schedule are YEAR-scoped only —
  their tables carry no academic_term_id (documented, not faked).
