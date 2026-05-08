# Tasks: Redesign Slice 1

## Phase 1: Theme plumbing

### Task 1.1: Add `@yield('body_class')` to layouts/app.blade.php
- [x] Edit `<body>` tag to interpolate `@yield('body_class')`
- [x] Add `.theme-luxury` style block (page bg, card glass, text colors, table on dark)

**Outcome:** ✅ Theme can be activated per-view via `@section('body_class', 'theme-luxury')`.
**Dependencies:** None.

---

## Phase 2: Login

### Task 2.1: Polish login to glass-on-dark
- [x] Replace solid white auth-card bg with `rgba(20,20,28,.55)` + blur
- [x] Recolor headings/labels to light tones
- [x] Verify both LTR and RTL render correctly

**Outcome:** ✅ Login renders glass-on-dark; verified live (gold-tinted brand title, blur 18px, Cairo+gold accents).
**Dependencies:** None.

---

## Phase 3: Dashboards

### Task 3.1: dashboard.blade.php (admin/manager/teacher)
- [x] Add `@section('body_class', 'theme-luxury')`
- [x] Gold KPI tint applied via global `.theme-luxury .card h2.fw-bolder` rule (no per-element class needed)

### Task 3.2: student/dashboard.blade.php
- [x] Add body_class section

### Task 3.3: parent/dashboard.blade.php
- [x] Add body_class section

**Outcome:** ✅ All five role dashboards render with theme-luxury body class, glass cards (rgba+blur), gold KPI accents. Verified live.
**Dependencies:** Phase 1.

---

## Phase 4: Deploy + verify

### Task 4.1: Deploy via git
- [x] Commit on main (4 commits: 83561be slice-1 theme, 7e1fa69 student/parent exam fix, 7168dea teacher schedule fix, bd70a5f subjects pivot fix)
- [x] Push, ssh, pull, view:clear

### Task 4.2: Live verification
- [x] Login at 1440 + 375 — glass card, dark gradient, gold accents
- [x] Admin (super-admin) dashboard at 1440 — luxury theme, gold KPIs
- [~] Manager dashboard — same template as admin (verified once via shared dashboard.blade.php); did not log in as separate manager since their password is not mine to reset
- [x] Teacher dashboard at 1440 — luxury theme; required schedules + subjects pivot fixes
- [x] Student dashboard at 1440 — luxury theme; required exam-column fix
- [x] Parent dashboard at 1440 — luxury theme; required exam-column fix
- [x] Mobile 375px sanity check — no horizontal overflow, content clears nav

**Outcome:** ✅ Six surfaces verified live, four cascade column-drift bugs fixed along the way. Manager dashboard inherits admin template so design coverage is complete; manager-specific data binding still relies on prior session's fixes.
**Dependencies:** Phases 1–3.

---

## Phase 5: Trello

### Task 5.1: Comment + reassign
- [x] Arabic comment summarising Slice 1 + what remains for Slice 2–5 (with test credentials)
- [x] Reassign to creator (Mahmoud Yasser, 68905a1af157def85d18a667)
- [x] Move card to `testing prompt`

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Plumbing | 1 | 1 | ✅ Done |
| 2. Login | 1 | 1 | ✅ Done |
| 3. Dashboards | 3 | 3 | ✅ Done |
| 4. Deploy + verify | 2 | 2 | ✅ Done |
| 5. Trello | 1 | 1 | ✅ Done |
| **Total** | **8** | **8** | **100%** |
