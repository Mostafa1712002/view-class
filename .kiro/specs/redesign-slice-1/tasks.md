# Tasks: Redesign Slice 1

## Phase 1: Theme plumbing

### Task 1.1: Add `@yield('body_class')` to layouts/app.blade.php
- [ ] Edit `<body>` tag to interpolate `@yield('body_class')`
- [ ] Add `.theme-luxury` style block (page bg, card glass, text colors, table on dark)

**Outcome:** Theme can be activated per-view via `@section('body_class', 'theme-luxury')`.
**Dependencies:** None.

---

## Phase 2: Login

### Task 2.1: Polish login to glass-on-dark
- [ ] Replace solid white auth-card bg with `rgba(20,20,28,.55)` + blur
- [ ] Recolor headings/labels to light tones
- [ ] Verify both LTR and RTL render correctly

**Outcome:** Login matches the new luxury feel.
**Dependencies:** None.

---

## Phase 3: Dashboards

### Task 3.1: dashboard.blade.php (admin/manager/teacher)
- [ ] Add `@section('body_class', 'theme-luxury')`
- [ ] Confirm KPI numbers carry gold tint via `.luxury-stat` class on the wrapping element

### Task 3.2: student/dashboard.blade.php
- [ ] Add body_class section
- [ ] Confirm KPI/stat blocks use `.luxury-stat` where numbers display

### Task 3.3: parent/dashboard.blade.php
- [ ] Add body_class section
- [ ] Confirm child cards keep readability on dark surface

**Outcome:** All five role dashboards render in the new luxury theme.
**Dependencies:** Phase 1.

---

## Phase 4: Deploy + verify

### Task 4.1: Deploy via git
- [ ] Commit on main
- [ ] Push, ssh, pull, `php artisan view:cache`

### Task 4.2: Live verification
- [ ] Login at 1440 + 375
- [ ] Admin dashboard at 1440 + 375
- [ ] Manager dashboard at 1440 + 375
- [ ] Teacher dashboard at 1440
- [ ] Student dashboard at 1440
- [ ] Parent dashboard at 1440

**Outcome:** Six surfaces verified live with no regressions.
**Dependencies:** Phases 1–3.

---

## Phase 5: Trello

### Task 5.1: Comment + reassign
- [ ] Arabic comment summarising Slice 1 + what remains for Slice 2–5
- [ ] Reassign to creator (Mahmoud Yasser)
- [ ] Move card to `testing prompt`

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Plumbing | 1 | 0 | Not Started |
| 2. Login | 1 | 0 | Not Started |
| 3. Dashboards | 3 | 0 | Not Started |
| 4. Deploy + verify | 2 | 0 | Not Started |
| 5. Trello | 1 | 0 | Not Started |
| **Total** | **8** | **0** | **0%** |
