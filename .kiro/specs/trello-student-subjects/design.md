# Design: Student subjects + subject content hub (#171)

Status captured 2026-06-13.

## What exists
- Student portal shell (#170) + dashboard, attendance, grades, schedule, weekly-plans,
  books, exams pages — all under `student/*`, dynamic per logged-in student.
- A student's subjects resolve from `subjects` where `school_id = student.school_id`,
  `is_active = 1`, and `grade_levels` (json array) contains the student's class grade level
  (`classes.grade_level` via `users.class_room_id`), optionally narrowed to the student's
  `section`. Subject has `name`, `icon`, `image`, `code`, `grade_levels`, `certificate_order`.
- Aggregatable content modules already exist: **Assignments** (`assignments`), **Exams**
  (`exams`), **VirtualClasses**, **Discussion** (rooms), **Books**, **Certificates**.

## The gap (#171 needs building)
1. **`student/subjects` cards page** — NOT present. New route + `StudentController@subjects`
   (or a SubjectController) + a cards view: name, icon, grade/stage, status, open link. Bounded.
2. **Subject content page** (`student/subjects/{subject}`) — NOT present. A hub with sections:
   video, attachments, assignments, exams, virtual classes, interactive activities, discussion
   rooms, + a "request certificate" button when the subject supports one (`certificate_order`).
   - Sections with a data source today: **assignments, exams, virtual classes, discussion rooms,
     books, certificates** — aggregate by subject_id + publish/availability gating.
   - ⚠️ **Sections with NO data source (prerequisite build):** **videos** and **attachments/
     materials** have no tables (`subject_lessons` carries only name/unit; no content/video/file
     columns), and there is no "interactive activities" model. These require new models
     (e.g. `subject_contents` with type=video|attachment|link, file/url, title, description,
     teacher_id, published flag, availability window, views) + a teacher upload UI before the
     student content page can show them.

## Recommended build order (own focused session)
1. `subject_contents` table + model + teacher upload UI (video/attachment/link) with publish +
   availability window (this unblocks the content page's video/attachment sections).
2. `student/subjects` cards page (resolve subjects as above).
3. `student/subjects/{subject}` content hub — aggregate the existing modules per subject +
   surface `subject_contents`, all gated by publish/availability and the student's enrolment.
4. Certificate request button wired to the Certificates module when `certificate_order` is set.

## Note
Do not ship the subjects-cards page alone — its cards must open a working content page, so #171
is a single coherent feature, not two independently-shippable halves.
