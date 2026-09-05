# Tasks: إدارة رسائل غياب الطالب

## Phase 1: Data + settings
### Task 1.1
- [x] Add `absent_daily` to `SmsAutoMessageSetting::EVENT_TYPES`; add `send_time` (TIME) column via migration (nullable).
### Task 1.2
- [x] Controller `AbsenceMessageController` + routes (`admin.student-attendance.absence-messages.*`) with attendance permission middleware.

## Phase 2: Manual flow
### Task 2.1
- [x] Cascade filter (compound→school→grade_level→class, multi-select, no "all"); resolved client-side from one embedded scope payload (QbScopeService data), matching the controller's 5-action list in the brief — no separate scope AJAX endpoints needed.
### Task 2.2
- [x] Absent-students query + pick modal (checkbox col, select-all, absent-only الحالة). Preview endpoint (`absence-messages/preview`).
### Task 2.3
- [x] إرسال الكل / تحديد الطلاب → `SendAttendanceMessageAction`; set `notified_parent`.

## Phase 3: Template
### Task 3.1
- [x] Body textarea + variable insert buttons; save/load via `SmsTemplate` (school-scoped, is_active, fixed title key); prefilled + inline editable. Mirrored into `SmsAutoMessageSetting.template_body` so the generic auto-messages screen stays consistent.

## Phase 4: Auto
### Task 4.1
- [x] `SendDailyAbsenceMessages` command; registered hourly in `routes/console.php`; per-school tz + send_time gate; dedupe via `notified_parent`.

## Phase 5: Verify + ship
### Task 5.1
- [x] Local end-to-end (manual + auto) verified via Playwright + tinker — see session report. Test rows cleaned up.
### Task 5.2
- [ ] Deploy (git pull + migrate --force + view:cache). Live smoke. — NOT done (explicitly out of scope for this session; do not deploy).
### Task 5.3
- [ ] Arabic handback; move; reassign Mohamed Farouk. — pending deploy.

## Progress
| Phase | Tasks | Done | Status |
|-------|-------|------|--------|
| 1 data | 2 | 2 | Done |
| 2 manual | 3 | 3 | Done |
| 3 template | 1 | 1 | Done |
| 4 auto | 1 | 1 | Done |
| 5 ship | 3 | 1 | Local verified; deploy pending |
| **Total** | **10** | **8** | **80%** |
