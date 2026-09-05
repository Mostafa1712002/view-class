# Requirements: إدارة رسائل غياب الطالب

## Overview
Card "ارسال رسائل عن غياب الطالب بعد انتهاء اليوم" (creator: Mohamed Farouk). New admin page to
message parents of ABSENT students, either automatically after the school day ends, or manually
(send-all / pick students), with a cascading filter and a saved reusable message template.

## User stories / acceptance criteria
- **US-1 Settings — mode.** WHEN admin opens إدارة رسائل غياب الطالب THE SYSTEM SHALL offer two modes:
  إرسال تلقائي (auto after school-day end) and إرسال يدوي (manual).
- **US-2 Auto.** WHILE auto is enabled THE SYSTEM SHALL, after the configured school-day-end time, send
  the saved template to the parent phone of every student marked absent that day (daily rows, `period IS NULL`).
- **US-3 Manual.** WHEN manual is selected THE SYSTEM SHALL show two buttons: إرسال الكل and تحديد الطلاب.
- **US-4 Pick modal.** WHEN تحديد الطلاب clicked THE SYSTEM SHALL open a table of ONLY absent students for
  the chosen day, with a per-row checkbox + header select-all, showing only filter-affected columns plus a
  الحالة column that must never show حاضر (absent-only; a present row = bug).
- **US-5 Cascade filter.** THE SYSTEM SHALL provide filters: المجموعة → المدارس → الصف → الفصول, where each
  selection narrows the next (choosing a school shows only its grades; a grade shows only its classes), and
  multi-select is allowed per level. NO "all" option — a real value must be picked. Filter drives the table.
- **US-6 Send.** WHEN إرسال (all or selected) THE SYSTEM SHALL send the saved message to each absent
  student's guardian phone (`can_receive_notifications = true`).
- **US-7 Template.** THE SYSTEM SHALL provide a message-body textarea with insertable variable buttons; on
  حفظ the template persists, is shown pre-filled thereafter, is editable inline, and is the body used for sends.

## Grounding (≈80% infra exists — reuse)
- Send bridge: `app/Modules/Attendance/Actions/SendAttendanceMessageAction.php` (in_app/sms/whatsapp,
  resolves parents, renders per-recipient).
- Manual multi-select composer to clone: `FollowUpController::userReports/sendUserReports`
  (`app/Modules/Attendance/Controllers/FollowUpController.php`), view
  `resources/views/admin/attendance/follow-up/user-reports.blade.php`.
- Absent query: `Attendance::scopeAbsent()`; daily = `status='absent' AND period IS NULL AND date=?`.
- Template engine: `SmsTemplate` + `SmsTemplateRenderer` (VARIABLES catalog: {student_name},{parent_name},
  {date},{attendance_state},{school_name},{class},{grade}…).
- Saved auto-message settings: `SmsAutoMessageSetting` (`EVENT_TYPES`) — no `absent_daily` type yet.
- Cascade UI to borrow: `resources/views/admin/qb/partials/scope-selector.blade.php` + QBCore
  `ScopeSelectorController` endpoints (compound→school→grade→class).
- Parent phone: `users.phone/phone_secondary/whatsapp`; link via `parent_student` pivot.
- Scheduler: `routes/console.php` (Laravel 12 style).

## Open decision (must resolve, do NOT silently default)
"بعد انتهاء اليوم الدراسي" has no defined time. **Add a school-day-end time field** on the settings page
(evaluated in `schools.timezone`); the scheduled command runs hourly and fires per-school when local time
passes that value. Do not hardcode 16:00.
