# Design: إدارة رسائل غياب الطالب

## Module
New feature under `app/Modules/Attendance/` (reuse the module's existing Actions/Services). New:
- Controller `AbsenceMessageController` (index/settings-save/preview-absent/send/save-template).
- Routes under `admin.student-attendance.absence-messages.*` in `app/Modules/Attendance/Routes/web.php`
  (same `['auth','role:super-admin,school-admin']` group; add `permission:attendance.view/…` to match siblings).
- View `resources/views/admin/attendance/absence-messages/index.blade.php` + a pick-students modal partial.
- Console command `SendDailyAbsenceMessages` registered in `routes/console.php` hourly.

## Data
- **Reuse `SmsTemplate`** for the saved body (school-scoped, is_active). Store the "which template is the
  absence template" + mode + day-end time in **`SmsAutoMessageSetting`** by adding an `absent_daily` entry to
  `EVENT_TYPES` (fields already there: `is_enabled`, `template_body`, `threshold`). Add a nullable
  `day_end_time` (TIME) column via a small migration IF `threshold` can't carry it cleanly — prefer a new
  column `send_time` for clarity.
- No new attendance columns. `attendances.notified_parent` already exists → set it on send to avoid dupes.

## Absent query
```php
Attendance::query()->where('date',$date)->whereNull('period')->where('status','absent')
  ->whereHas('classRoom.section', fn($q)=>$q->where('school_id',$schoolId)) // scope via classes→sections
  ->when($classIds, fn($q)=>$q->whereIn('class_id',$classIds));
```
Cascade values: compound(مجموعة)→school→grade_level(int on `classes`)→class(`classes.id`). Grade is the
`grade_level` integer column on `ClassRoom`, NOT a table. Reuse QBCore `ScopeSelectorController` AJAX
endpoints; if their response shape doesn't fit, add thin sibling endpoints returning `{id,name}` lists.

## Send path
`SendAttendanceMessageAction::execute(students, channel, templateId|bodyTemplate)` — already resolves
guardians + renders. Channel default per school settings (whatsapp if enabled else sms else in_app).
Manual send-all = all absent rows for current filter/day; pick = the checked subset.

## Auto (end-of-day)
`SendDailyAbsenceMessages` (hourly): for each school with `absent_daily` enabled where now(tz) ≥ send_time and
not already run today → gather today's absent daily rows not yet `notified_parent` → dispatch same action.
Guard against double-run with a per-school per-date marker (a cache key or the `notified_parent` flag).

## UI
- Bootstrap 4 tabs/cards. Settings card (mode radio + send_time + template textarea + variable buttons +
  حفظ). Manual card (cascade filter row + إرسال الكل / تحديد الطلاب). Modal = absent table (checkbox col,
  select-all header, filter-affected columns + الحالة=غائب only) + إرسال.
- Variable buttons insert `{key}` at cursor into the textarea (reuse renderer VARIABLES list).

## Verify
Local: seed a couple absent daily rows; open page; filter cascade narrows; modal shows only absent; save
template persists + reloads; manual send writes to sms_messages/whatsapp_logs (log driver) + sets
notified_parent. Auto: run `php artisan schedule:test`/invoke command with a past send_time → same result,
no dupes on second run.
