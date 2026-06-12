# Design: Appointments Module (#175 student, #184 parent, #197 teacher)

New module `app/Modules/Appointments`. Lets staff (teachers/admins) publish bookable
availability ("schedules"), and students/parents request appointments / school visits
by choosing a role-function → person, with a معلم-مادة cascade. NOT class_visits
(that's evaluation supervisor visits — different concept).

## Schema (all additive, school-scoped, softDeletes)
appointment_schedules:
  id, school_id(idx), owner_id(user — the staff member offering slots), title,
  date_from DATE, date_to DATE, days JSON([sun..sat]), time_from TIME, time_to TIME,
  slot_minutes INT default 30, max_appointments INT null, location VARCHAR null,
  mode VARCHAR(20) default 'in_person' (in_person|call|virtual), notes TEXT null,
  status VARCHAR(20) default 'active' (active|inactive|expired), booking_open TINYINT default 1,
  created_by, timestamps, deleted_at.

appointment_bookable_roles (admin config — which functions accept bookings per school):
  id, school_id(idx), label VARCHAR, target_type VARCHAR(20) (role|job_title|user|subject_teacher),
  target_id BIGINT null (role id / job_title_id / user id), is_active TINYINT default 1,
  sort INT default 0, created_by, timestamps.

appointments (bookings):
  id, school_id(idx), schedule_id null(idx), student_id(idx — the student the visit concerns),
  booked_by(user — student or parent), bookable_role_id null, target_user_id null(the person),
  subject_id null (for معلم مادة), reason TEXT, appointment_date DATE, appointment_time TIME,
  contact_method VARCHAR(20) (in_person|call|virtual), notes TEXT null, attachment_path null,
  status VARCHAR(20) default 'requested' (requested|confirmed|rejected|cancelled|completed),
  decision_by null, decision_at TIMESTAMP null, decision_note TEXT null,
  created_by, timestamps, deleted_at.

## Roles & routes
- Staff (teacher/admin) — manage own schedules + see/decide bookings to them:
  manage/appointment-schedules (index/create/store/edit/update/destroy/toggle/copy/show),
  manage/appointments (index of bookings to me; confirm/reject/complete/cancel).
- Admin — appointment settings (bookable roles config): admin/appointment-settings.
- Student (#175) & Parent (#184) — book visit + my appointments:
  my/appointments (index), my/appointments/create (the dynamic role→person form),
  store, cancel. Parent books per selected child; student books for self.
- Role→person cascade: pick bookable role → if target_type=subject_teacher, show the
  student's class subjects → subject → subject_teacher → teacher; else show users matching
  role/job_title in the school; else the configured user.

## Phasing (Opus plan; Sonnet codes each)
- Phase 1 (#197 core): schema + models + repositories; staff schedule management
  (AppointmentScheduleController + views) + admin bookable-roles settings + sidebar links. DEPLOYABLE.
- Phase 2 (#175 + #184): booking flow (student + parent forms w/ cascade) + my-appointments
  + staff booking management (confirm/reject/complete) + notifications.

## Conventions
Laravel 12/PHP8.4, module pattern (Actions/Controllers/Repositories), HasSchoolScope on every
query, Bootstrap-4 + Line Awesome views matching the platform, SweetAlert confirms + toasts,
@lang ar+en. No new libs.
