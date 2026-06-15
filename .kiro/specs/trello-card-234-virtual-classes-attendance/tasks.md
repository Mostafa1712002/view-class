# Tasks: Card #234 — Virtual Classes + Attendance linkage (AUDIT + GAP-FILL)

Scope fence: edit ONLY `app/Modules/VirtualClasses/**`, its views (`resources/views/virtual-classes/**`),
lang `lang/{ar,en}/virtual_classes.php`, and migrations. Do NOT touch sidebar, permission seeder,
or main `routes/web.php` (report a require line if needed).

## Audit result (existed BEFORE)
- CRUD (index/create/store/show/edit/update/destroy) + cancel — staff routes, school-scoped repo. [exists]
- Zoom meeting creation on store. [exists]
- Student read-only upcoming list. [exists]
- VirtualClass model + migration + lang ar/en + manage/student views. [exists]
- `isJoinable()` window = 10 min (card says 5). [bug]
- NO permission gating (uses role: middleware only, no canDo / permission: alias). [gap]
- NO class_id/subject_id capture in form → roster + columns empty. [gap]
- NO start (live + started_at "بدأ المعلم" column). [gap]
- NO join entry logging / attendance source. [gap]
- NO attendance view / recalc / attendance write to shared `attendances`. [gap — CORE]
- NO platform field (Zoom/Teams/external). [gap]
- NO tabs (today/recorded/old/all). [gap]
- NO ActivityLog calls. [gap]
- NO clear_cache. [gap]

## Phase 1: schema + model
- [ ] Migration: add `platform`, `started_at`, `class_id` already exists, `subject_id` exists; add recalc summary cache support (none-needed, use Cache). Add enum `partial` to attendances.status.
- [ ] Migration: create `virtual_class_attendees` (entry/exit log per student per VC).
- [ ] Model: VirtualClass fillable += platform, started_at; cast started_at; isJoinable 5 min; attendees() relation; platformLabel().
- [ ] Model: VirtualClassAttendee.

## Phase 2: gating + scope + routes
- [ ] Routes: add permission: middleware per action (view/create/start/join/view_attendance/recalc_attendance/clear_cache). Keep role: for student/parent join via enrollment (no canDo on student join).
- [ ] Controller: gate staff actions; class_id/subject_id capture + validation; platform.

## Phase 3: attendance (CORE)
- [ ] RecalcAttendanceAction: read attendees entry/exit → compute duration → status → upsert into shared `attendances` (class_id, academic_year_id current, subject_id, teacher_id, date, period=null-key, notes) → ActivityLog.
- [ ] JoinAction: record entry (and student attendance source row) → log.
- [ ] StartAction: status live + started_at → log.
- [ ] Attendance view (modal/page) with colored statuses + search + recalc button + CSV export.

## Phase 4: tabs + views + clear_cache
- [ ] index tabs: today / recorded / old / all (query filters in repo).
- [ ] platform + started_at columns; join/start buttons gated + time-windowed.
- [ ] clear_cache: clear recalc summary cache key + flash.
- [ ] design-system.css + x-svg-icon + empty/loading states.

## Phase 5: verify — DONE
- [x] Playwright admin: list (tabs+columns), create (class+subject+platform), attendance view, recalc, clear_cache.
- [x] Recalc writes shared attendances row + visible via student->attendances() (report linkage).
- [x] 403 for non-permitted job-title user (curl: index 403, create 403).
- [x] All phases 1-4 implemented + lint clean + test artifacts removed.

## Key design decision (attendance linkage)
- Shared `attendances` table is the integration point: parent/student reports read
  `$student->attendances()` generically, so a recalc row appears there automatically.
- `partial` ("حضر جزئيًا") is kept ONLY in `virtual_class_attendees.attendance_status`
  (the in-module attendance view renders all 4 colored statuses). When mirroring to the
  shared table, `partial`→`late` so it stays inside the legacy 4-status enum that the
  un-editable report code understands (stats keep summing correctly). No shared enum
  change; report code untouched.
- recalc does NOT dispatch NotifyAbsenceJob (that is the daily-attendance alert path).
  Sets `notified_parent=false`; parent sees the row in their existing report.

## Deferred (report, not built)
- customize columns, multi-format export (ship CSV only), Teams/external full plumbing beyond field,
  guest access, recording upload + per-student view-logging, exit time via Zoom webhooks (entry-only logged).
</content>
</invoke>
