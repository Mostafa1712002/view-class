# Design: Sprint 10 — Operational-Cycle Foundation (Trello #260, card ZTYLMhJU)

This card builds the **shared scaffolding** every Sprint-10 operational module plugs into:
student attendance, teacher attendance, QR services, certificates, support/tickets,
admissions/registration, and educational websites. The individual pages/screens are
SEPARATE later cards (**#261–#274**). This card delivers ONLY: the permission set, the
scope + activity-log conventions documented here, and (where a route already exists) the
sidebar gate. It builds **no feature pages**.

It follows the exact 3-part pattern proven in Sprint 9 — see
`.kiro/specs/trello-sprint9-comms-foundation/design.md` for the canonical write-up of how
the permission system works (`permissions` table + `MODULES` const + `$actionLabels` blade +
`job_title_permissions` pivot + `User::canDo()` + `CheckPermission` middleware). That
mechanism is unchanged here; only new groups are added.

---

## 1. Feature → existing-module/table map (THE AUDIT — critical for #261–#274)

| Sprint-10 area (later card) | Existing module | Existing table(s) | Verdict |
|---|---|---|---|
| Student attendance (#261) | `app/Modules/Attendance` (Controllers, Repositories, Jobs) | `attendances` (student_id, class_id, subject_id, teacher_id, academic_year_id, date, period, status enum present/absent/late/excused, arrival_time, notes, notified_parent, **excuse_* columns** added 2026-06-14) | **EXISTING** — extend. Daily + per-period both supported (`period` col). Excuse workflow already present. |
| Late / absence mgmt + reports (#262, #263) | `app/Modules/Attendance` + `app/Modules/Behavior` | `attendances`, `behavior_records` (scope enum student/teacher, points, note, needs_followup, notified_parent) | **EXISTING** — behavior already supports both student & teacher scope. Reports are net-new pages. |
| Teacher attendance (#264) | — (none) | — | **NET-NEW**. `attendances` is student-only (`student_id` NOT NULL). Needs its own table/feature. |
| QR attendance (#265) | — (none) | — | **NET-NEW**. No qr/scan/attendance-group tables exist. |
| Certificates (#266) | `app/Modules/Certificates` (Controllers, Http/Requests, Repositories) | `certificates` (school_id, type, title, recipient_user_id, issued_by, issue_date, status, note, file_path, soft-deletes) | **EXISTING (basic)** — extend. **No certificate templates table yet** — template_* actions are net-new. Routes today use `role:` middleware, NOT `permission:` (see §5). |
| Support / tickets (#267) | `app/Modules/Support` (Controllers, Http/Requests, Repositories) | `support_tickets` (school_id, created_by, related_student_id, creator_role, category, subject, body, priority, status, assigned_to, attachment_path, last_reply_at, soft-deletes) + `support_ticket_replies` | **EXISTING** — extend. Assignment + replies + attachments already modelled. |
| Admissions / registration (#268) | — (none) | — | **NET-NEW**. Confirmed: no enroll/regist/program/admission/lead tables; `schools` has no registration/slug/form columns. Build from scratch. |
| Registration info + form settings (#268) | — | — | **NET-NEW** (part of admissions). |
| Parent CRM (#269) | `app/Modules/Users` (parents) + Sprint-9 `parents_contact` | `users` (parent rows + `pa_*` profile fields), `parent_student` pivot | **EXISTING** — reuse `parents` + `parents_contact` permission groups. **No new perms.** |
| Educational websites (#270) | — (none) | — | **NET-NEW**. No website/site tables. |
| Messaging link (#271) | `app/Modules/{Communications,SmsServices,Whatsapp,Mail}` | sms_*, whatsapp_*, internal_mails | **EXISTING** — reuse `sms`/`whatsapp`/`mail`/`messages` groups. **No new perms.** |
| UX polish (#272) | cross-cutting | — | No perms. |
| Export / PDF (#273) | mPDF stack | — | **EXISTING** — reuse `pdf_export` + `reports` groups. **No new perms.** |
| Integration tests (#274) | — | — | No perms. |
| Appointments (cross-ref) | `app/Modules/Appointments` | `appointment_schedules`, `appointment_bookable_roles`, `appointments` | **EXISTING** — admissions "تحديد موعد" reuses this (see §2). |

**Bottom line for the next cards:** EXTEND = Attendance, Certificates, Support. NET-NEW =
Teacher-Attendance, QR, Admissions/Registration, Educational-Sites. REUSE (no new module) =
Parent-CRM, Messaging, Export/PDF.

---

## 2. Permission keys added by this card

Registered in BOTH the seed migration
`database/migrations/2026_06_16_600000_seed_sprint10_permissions.php` AND
`JobTitlePermissionsController::MODULES`, with Arabic verb labels in
`resources/views/admin/users/job_titles/permissions.blade.php` (`$actionLabels`). Slugs map
one-for-one to card #260's "الصلاحيات المطلوبة" section (7 sub-headers → 7 groups).

| group | actions | Arabic label | status |
|---|---|---|---|
| `attendance` | view, record_present, record_absent, record_late, record_excuse, edit, delete, add_excuse, add_note, bulk_present, bulk_absent, bulk_late, notify_parent, view_reports, export, print | حضور الطلاب | EXTEND |
| `teacher_attendance` | view, record_present, record_absent, record_late, record_excuse, record_period, edit, export, send_message | حضور المعلمين | NEW |
| `qr` | view, create_card, print_card, export_cards, scan, view_log, close_day, group_create, group_edit, group_delete, link_students, link_devices | خدمات QR للحضور | NEW |
| `certificates` | view, template_create, template_edit, template_delete, create, edit, delete, issue, upload_file, preview, send, copy_link | الشهادات | NEW |
| `support` | view, create, reply, assign, change_status, close, delete, view_attachments | الدعم الفني | EXTEND |
| `admissions` | view, edit, delete, change_status, schedule, export, copy_link, copy_company_link, edit_school_settings, edit_settings, edit_info, convert_to_student | القبول والتسجيل | NEW |
| `educational_sites` | view, create, edit, delete, reorder, toggle_active | المواقع التعليمية | NEW |

### Reconciliation note (overlaps with existing groups — DO NOT regress)

- **`attendance` is EXTENDED, not replaced.** The pre-Sprint-10 group had
  view/create/edit/delete/export. The matrix `MODULES` entry now lists the granular set
  (record_present/absent/late/excuse, bulk_*, add_excuse, add_note, notify_parent,
  view_reports, print) **plus the kept view/create/edit/delete/export**. The legacy
  `attendance.create` slug is **kept in the matrix** because it has live grants
  (verified: 3 rows in `permission_role`) — dropping it would hide an active grant from
  school-admins. **New attendance code (#261/#262) should gate on the granular slugs**
  (`attendance.record_present`, etc.); `attendance.create` remains only for backward-compat.
- **`support` is EXTENDED.** Legacy view/create/edit/delete. The new `MODULES` set keeps
  view/create/edit/delete and adds granular reply/assign/change_status/close/view_attachments.
  `support.edit` is **kept** (no code references it and it has 0 grants today — verified —
  but it is retained for symmetry/backward-compat). New ticket code gates on the granular
  slugs.
- **Behavior stays in `behavior`.** Card #260 lists "إضافة سلوك / إضافة سلوك لمجموعة طلاب"
  under the attendance sub-header, but the behavior feature already owns the `behavior`
  group (and a per-group `behavior_groups` table). Those actions are **not** duplicated into
  `attendance`; #262/#263 gate behavior writes on `behavior.create` (+ a future
  `behavior.bulk` if a card needs group-behavior as a distinct grant).
- **"تحديد موعد" (admissions) reuses `appointments`.** The admissions group exposes a
  `schedule` slug for the *admissions-interview* concept, but the actual appointment record
  is created through the existing `appointments` feature; admissions scheduling code also
  gates on `appointments.create`. Both grants apply.
- **Parent-CRM / Messaging / Export-PDF add NO new groups.** #269 reuses `parents` +
  `parents_contact`; #271 reuses `sms`/`whatsapp`/`mail`/`messages`; #273 reuses
  `pdf_export`/`reports`. This is intentional — #260's permission section does not enumerate
  separate sub-headers for them.

### Assignable-scope restriction (kept from Sprint 8/9 — DO NOT regress)
`JobTitlePermissionsController::update()` still clamps non-super-admins to narrow scopes
(`school` and below); the wide scopes `all`/`company`/`group` remain super-admin only. The
new Sprint-10 groups inherit this automatically (the rule is per-actor, not per-group).

---

## 3. Data-scope convention (MANDATORY for every #261–#274 card)

Every Sprint-10 query that touches school-owned data MUST filter by scope, enforced in the
**repository/action layer** (CLAUDE.md rule), reusing the fail-closed helper
`App\Modules\Users\Controllers\Concerns\HasSchoolScope::scopedSchoolId()` — a `null` scope
(see-all) is permitted **only** for super-admins; any non-super-admin resolving to a null
school is `abort(403)`.

Resolution order (mirrors Sprint-9 §3, retargeted to the #260 role matrix):

1. **school_id — always.** No attendance/certificate/ticket/admission/site row crosses a
   school boundary. Bind to the active school (multi-school admins use the navbar scope
   selector via `activeSchoolId()`).
2. **academic_year_id** — attendance, QR, admissions and reports are also bound to the
   active academic year (every operational record belongs to one year).
3. **Role narrowing on top of school_id** (from #260 "نطاق ظهور البيانات"):
   - **super-admin / general admin** → all schools per granted scope (company/group/all).
   - **school-admin (مدير المدرسة)** → own school only.
   - **system-admin assistant (مساعد مدير النظام)** → per delegated job-title scope.
   - **attendance officer (مسؤول الغياب)** → only the classes/sections assigned to them
     (pivot scope `class`/`section`).
   - **teacher** → own classes & own students only (own periods / class assignments). Teacher
     attendance & student attendance the teacher records.
   - **student** → own records only.
   - **parent** → own children only (via `parent_student`).
   - **admissions officer (مسؤول القبول)** → admission requests of the school(s) allowed.
   - **support agent (الدعم الفني)** → tickets by department/category + granted scope.
4. The granted **pivot `scope`** further narrows within school
   (stage/class/section/subject/own_students/own_subjects/own); read it from
   `$jobTitle->permissions`, default to `school` when unset.

A `HasSchoolScope`-style trait (or a Sprint-10 `OpsScope` concern, modelled on Sprint-9's
`Communications/Concerns/CommsScope`) should centralise the school_id + year + role narrowing
so each new repository reuses one implementation. Keep it minimal; later cards extend it.

---

## 4. Activity-log convention (MANDATORY for every #261–#274 card)

Use the existing logger — do NOT invent one. `App\Models\ActivityLog`:

```php
use App\Models\ActivityLog;
ActivityLog::log(string $action, string $description, ?Model $model = null,
                 ?array $oldValues = null, ?array $newValues = null);
ActivityLog::logCreate($model, 'وصف عربي');
ActivityLog::logUpdate($model, 'وصف عربي', $oldValues);
ActivityLog::logDelete($model, 'وصف عربي');
```

`log()` auto-fills `user_id`, `school_id`, `ip_address`, `user_agent`. Per #260, every
**sensitive op** is logged with a concise Arabic description:
تسجيل حضور · تعديل حضور · إرسال رسالة غياب لولي الأمر · إصدار شهادة · إرسال شهادة ·
إنشاء تذكرة دعم · الرد على تذكرة · تغيير حالة طلب قبول · تعديل إعدادات التسجيل ·
تشغيل ماسح QR · إغلاق يوم QR · إنشاء مجموعة حضور. Reads are not logged (except the
QR scan log, which is a feature store of the QR module, not the ActivityLog table).

---

## 5. Sidebar

Kept minimal and route-gated, exactly like Sprint-9:
- An item renders only when **both** its route exists (`Route::has(...)`) **and** the staff
  user `canViewModule('<group>')`; non-staff (student/parent) are never hidden by the gate.
- Today only **certificates** has live routes (`admin.certificates.*`, `my.certificates.*`)
  and is already linked. The other six areas (teacher-attendance, QR, admissions,
  educational-sites, and the new attendance/support sub-pages) have **no routes yet** — they
  are deferred to #264/#265/#268/#270 etc. **No `href="#"` placeholders are added** (building
  dead links is forbidden). Each later card adds its route + a `Route::has`-gated item and it
  lights up automatically. No sidebar change is made by this foundation card beyond what
  already exists.

> Note: certificate routes currently use `role:super-admin,school-admin` middleware. When
> #266 builds the full certificates UI it should migrate those routes to the
> `permission:certificates.*` aliases so the new granular grants are enforced backend-side.

---

## 6. What this card does NOT do (deferred to #261–#274)

- No feature pages/screens, controllers, or repositories for the net-new areas.
- No new tables (teacher_attendance, qr_*, admissions/registration, educational_sites,
  certificate_templates) — those ship with their owning card.
- No migration of certificate routes from `role:` to `permission:` (left for #266).
- No sidebar section for unbuilt areas (no dead links).
