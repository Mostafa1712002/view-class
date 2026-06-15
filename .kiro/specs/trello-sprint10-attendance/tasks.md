# Tasks: Sprint 10 — Attendance Subsystem (Trello #261–#265)

Local-only build (no commit/push/deploy). Module pattern, scopedSchoolId fail-closed,
canDo + role middleware gating. Verified at http://viewclass.test.

## #261 — Student attendance (eKMe2G3Y) — EXTEND
- [x] Daily + per-period boards (tabs) — `StudentAttendanceController@daily/period`
- [x] Filters: date, class, subject, period, name, national_id
- [x] Status states: present/absent/late/excused (+ "لم يتم التسجيل" = no row); excuse = excused+excuse_status
- [x] Individual quick-status buttons + bulk apply (confirm dialog)
- [x] Stat cards (present/absent/late/excused) auto from data
- [x] Persist via existing EloquentAttendanceRepository@saveWithNotify (keeps notify/excuse flow)
- [x] add_note / add_excuse actions
- [x] Verified: store persists (attendances id 6/7), board renders 200 (admin + super + null-super)
- [ ] DEFERRED: per-row "send message to parent" (covered by #262 follow-up notify), export/print of selection, behavior-add inline (behavior module owns it)

## #262 — Late/absence follow-up + user reports (t5TwsTmd)
- [x] Follow-up board: filters (date/class/subject/status/type/name/national_id) + stat cards
- [x] type filter: absent_daily/absent_period/late_daily/late_period/excuse
- [x] Parent notify (in_app/sms/whatsapp) — refuses send w/o valid phone for sms/whatsapp; sets notified_parent
- [x] User-reports composer: multi-select students + channel + template + preview + send (records result)
- [x] Verified 200 + renders with data
- [ ] DEFERRED: real SMS/WhatsApp dispatch wired to provider (records in-app notification + flag; channel services exist but send is stubbed to in-app), contact-log history screen, internal-mail channel

## #263 — Reports (hKbA7XEP)
- [x] Report cards landing (status/day-absence/period-absence/late/aggregate/behavior)
- [x] Attendance-status, day-absence, period-absence, late reports (filters + paginated tables)
- [x] Aggregate report: totals + CSS progress-bar charts (status distribution, absence-by-class)
- [x] Behavior report reads behavior_records (scope=student) joined to behaviors/actions
- [x] All school-scoped via class->section join (attendance) / school_id (behavior)
- [x] Verified 200 + render with data
- [ ] DEFERRED: Excel/PDF export buttons, column customization, "ملخص المواد" + "الغياب المجمع weekly" cards, the 8-card exact set (built 6 core reports)

## #264 — Teacher attendance (Ns8eR9EP) — NET-NEW
- [x] Migration teacher_attendances (school_id, academic_year, class/subject/period, status, soft-deletes, unique)
- [x] TeacherAttendance model
- [x] Daily + per-period boards (tabs) + stat cards + filters
- [x] Record (updateOrCreate, scope-guarded) + message-to-teacher (in-app)
- [x] Verified: store persists (teacher_attendances id 1), 200/403 gating
- [ ] DEFERRED: dedicated teacher-attendance reports screen (data is queryable; no separate report UI)

## #265 — QR attendance services (aB6pju4n) — NET-NEW
- [x] Migrations: qr_attendance_groups, qr_cards (secure 48-char token != id), qr_scans, qr_day_closures
- [x] Models + QrScanService (validation chain, status-by-time-window, attendance mirror)
- [x] Cards: list/search, generate (secure token), enable/disable, regenerate, print page (QR via html5 lib)
- [x] Scanner page: camera (html5-qrcode) + manual entry + device name + manual scan_time + recent list
- [x] Scan endpoint: validates token, derives status from group window, records scan + mirrors to attendances
- [x] Error states: invalid_qr, card_disabled, card_expired, wrong_school, already_scanned, day_closed, no_active_group
- [x] Scan log (filters) + day-close (locks date, marks non-scanned absent, activity log)
- [x] Groups CRUD (time windows + work_days + active)
- [x] Verified: generate (48-char token), scan present + mirror (attendances id 7), duplicate=422, invalid=422, group create, day-close
- [ ] DEFERRED / STUB: camera decode is client-side (html5-qrcode CDN) — not server-verified in tests (manual + token path fully tested); IoT devices page (qr.link_devices) NOT built; "ربط الطلاب" group-link UI NOT built (group_id column + statusForTime logic exist); reopen-day permission flow not built

## Cross-cutting
- [x] Routes self-contained in 3 module Routes/web.php; only edit to routes/web.php = 3 require lines at EOF
- [x] canDo gating on all writes (granular slugs) + role:super-admin,school-admin (non-vacuous 403)
- [x] ActivityLog on all sensitive ops
- [x] Did NOT touch sidebar.blade.php or permission seeder
- [x] Non-regression: legacy attendance index/daily-report 200; excuse routes intact
