# Requirements: Points & Rewards (Gamification) — Trello #371

«الدعم والتقارير - ربط نظام النقاط والمكافآت (Gamification)»

## Overview
Turn the sidebar «النقاط والمكافآت» (today `programs.coming-soon`) into a working points system for **students, teachers and وكلاء** (job title slug `vice`), driven by the client file `source-نقاط-التحفيز.md` (source of truth, same folder). Points are used for **evaluation and encouragement**, so every point is an immutable ledger row; a balance is the sum of the ledger.

Acceptance (card): a student's balance updates automatically when the activities in the file are completed.

### Fixed decisions (from the coordinator — not reopened here)
1. Immutable ledger: who, school, rule, source model+id, ±points, reason, created_by, time. Corrections and deletions write reversing rows. Never double-awards (DB-enforced).
2. New module `app/Modules/Gamification`. The existing Behavior points (`behavior_records.points`) stay separate (discipline ≠ motivation); shown side by side, read-only.
3. Platform defaults by super-admin («نحن»); per rule: points, enabled, «allow schools to override». School admin overrides only where allowed. Defaults ship **0 points + disabled**.
4. Borrow from «الأول» (Al-Awwal): reward periods with in-period points, min-points threshold → reward record (earned → delivered), monthly honour list «المتميزين». Note: Al-Awwal's `grade_reward_settings.grade_id` points at its `grades` table, which is a **school** (it has `complex_id`, `supervisor_id`, `email`), so periods are per school; we add an optional grade (section) filter.
5. Public contract so card #368 (special programs) and any module can award points.

## Coverage of the client file (research 2026-11-19)

Status: **EXISTS** = activity exists and a hook point is clear; **PARTIAL** = activity exists but part of the rule's data is missing (wired with the stated interpretation); **MISSING** = feature does not exist → rule is in the catalog, shown as «غير مربوط — الميزة غير موجودة», not wired. Missing features are NOT built in this card.

| # | File item (beneficiary) | Rule key | Status | Where / why |
|---|---|---|---|---|
| S1 | الإجابات الصحيحة — «الأول» (طالب) | `student.correct_answers.alawwal` | MISSING | sidebar placeholder only (`sidebar.blade.php:543`) |
| S1 | «الأول بلس» | `student.correct_answers.alawwal_plus` | MISSING | no integration |
| S1 | «أنا والقدرات» | `student.correct_answers.ana_wal_qudurat` | MISSING here → #368 | special programs built on #368; it calls the contract |
| S1 | «نافس» | `student.correct_answers.nafes` | MISSING here → #368 | same |
| S1 | «تحصيلي» | `student.correct_answers.tahsili` | MISSING here → #368 | same |
| S1 | «المواد العلمية» (online exams) | `student.correct_answers.subjects` | **EXISTS (interpretation)** | `app/Http/Controllers/StudentExamController.php:115` `submit()` (txn ends :171), `:288` `autoEndAttempt()`; correct = `student_answers.is_correct` (`app/Models/StudentAnswer.php:34`); reopen `Admin/ExamController.php:411`; delete `Admin/ExamController.php:266` (hard cascade) |
| S1+ | «المواد العلمية» — الواجبات (score ratio) | `student.assignments` | **EXISTS** (extra, disabled) | grade `Admin/AssignmentController.php:196` (update :215); delete `:186`. Not named in the file; offered under «كافة الأنشطة ....» |
| S2 | نقاط ملف الإنجاز بعد اعتماد المعلم | `student.portfolio_approved` | MISSING | `portfolio_entries` has no status/approval; students cannot add entries (`StudentController.php:429` read-only) |
| T1 | نقاط ملف الإنجاز بعد اعتماد المشرف التعليمي للمجمع | `teacher.portfolio_approved` | MISSING | no approval flow; no supervisor↔complex link |
| T2a | الواجبات (نسبة تفاعل الطلاب / الإجابات الصحيحة) | `teacher.assignments` | **EXISTS** | submit `StudentAssignmentController.php:36` (save :81); grade `Admin/AssignmentController.php:196`; delete `:186` |
| T2b | الاختبارات | `teacher.exams` | **EXISTS** | same hooks as S1 subjects |
| T2c | غرف النقاش — نسبة المشاركة | `teacher.discussion_participation` | PARTIAL | comments `Discussion/Controllers/DiscussionController.php:133` (add :149), deletes `:164`, `ManageDiscussionController.php:251`; no join/attendance record for rooms |
| T2c | غرف النقاش — نسبة الحضور | `teacher.live_rooms_attendance` | PARTIAL (interpretation) | read as live virtual classes: `VirtualClasses/Actions/RecalcAttendanceAction.php:34` (per-student status :87); `left_at` never set so everyone who joins is present/late |
| T2d | التحفيز | `teacher.motivation` | MISSING | meaning not defined in the file → client question |
| T2e | وسائط متعددة — فيديو «مشاهدة – لايك – تعليق» | `teacher.media_video` | MISSING | no per-user view tracking (`subject_contents.views_count` never incremented); library items have like/comment only (`Libraries/Controllers/PublicLibraryController.php:132,189`) |
| T2e | وسائط متعددة — ورق عمل «تحميل – رفع – تصحيح المعلم» | `teacher.media_worksheet` | MISSING | no worksheet concept |
| T3 | التحضير — كل حصتين = تحضير، حذف التحضير يحذف الدرجة | `teacher.preparation` | PARTIAL | read as `weekly_plans` (`Admin/WeeklyPlanController.php` store :410, duplicate :604, destroy :508); one plan per teacher+subject+class+week, so "one per two periods" is not representable → client question |
| V1 | تقييم الأداء الوظيفي في الموعد (يزيد بعدد المعلمين المقيَّمين) | `vice.job_performance_on_time` | PARTIAL | submit `Evaluation/Actions/SubmitEvaluation.php:68` txn (save :104), shared path :218/:298; due date = `evaluation_forms.close_date` (never enforced today); reverse on `ReopenEvaluation.php:49`, `RejectEvaluation.php:53` |
| V2 | زيارات صفية (عدد لكل فصل دراسي + مدة يحددها المشرف التعليمي للمجمع) | `vice.class_visits` | PARTIAL | completed visit = its evaluation submitted with `class_visit_id` (same SubmitEvaluation hook); term window from `academic_terms`; target/window not stored anywhere → stored as rule options; complex-supervisor ownership missing |
| V3 | إضافة الجدول المدرسي في الوقت المحدد | `vice.school_schedule_on_time` | PARTIAL | `Admin/ScheduleController.php:270` store, `:343` destroy (hard); no uploader column and no deadline → actor = `auth()->user()`, deadline = rule option |

**Tally (file items):** EXISTS 3 (subject exams — our reading of «المواد العلمية», teacher assignments, teacher exams) · PARTIAL 5 (discussion rooms, preparation, job performance, class visits, school schedule) · MISSING 6 (the 5 programs as one item, waiting on #368/external; student portfolio approval; teacher portfolio approval; التحفيز; video; worksheet).

## User stories

### US-001 Platform rules (super-admin «نحن»)
**As** super-admin **I want** to set each rule's points, in-period points, enabled flag, «allow schools to override» flag and rule options **so that** every school starts from our defaults.
- THE SYSTEM SHALL list every catalog rule grouped by beneficiary (طالب / معلم / وكيل) with its Arabic label from the file and its calc mode.
- THE SYSTEM SHALL ship every rule with 0 points, disabled, override not allowed (no DB rows needed; defaults live in code).
- WHEN a rule is not wired (MISSING) THE SYSTEM SHALL show it read-only with the badge «غير مربوط — الميزة غير موجودة».
- WHEN super-admin saves THE SYSTEM SHALL store the platform row (`school_id = 0`) and record `updated_by`.
- WHEN a rule is disabled at platform level THE SYSTEM SHALL award nothing for it in any school (platform switch is the master switch).

### US-002 School overrides (school admin)
**As** a school admin **I want** to change points / switch off a rule for my school **only where the platform allows it**.
- WHEN the platform row has `allow_school_override = false` THE SYSTEM SHALL show the rule read-only to the school and reject any override write (403).
- WHEN an override exists THE SYSTEM SHALL use the school's points / in-period points / enabled (enabled only narrows: a school can switch off, never switch on a platform-disabled rule) and options.
- WHEN a user whose job title is `vice` edits overrides THE SYSTEM SHALL refuse edits to `vice.*` rules (no one sets their own pay).
- All override reads/writes SHALL be scoped to `scopedSchoolId()`.

### US-003 Automatic awarding (all beneficiaries)
- WHEN a wired source event happens THE SYSTEM SHALL compute the target points for (rule, source, beneficiary) and write only the difference from the current ledger sum for that key (sync-to-target).
- WHEN the same event fires twice (double submit, retry, recalc) THE SYSTEM SHALL write nothing the second time.
- WHEN a value changes (re-grade, reopened exam, better attempt, more students submit) THE SYSTEM SHALL write one delta row (+ or −).
- WHEN a source is deleted / reopened / rejected THE SYSTEM SHALL write a reversing row bringing that key to 0 («في حال حذف تحضير تحذف الدرجة»).
- WHEN the rule is disabled or worth 0 at the first award THE SYSTEM SHALL write nothing, and SHALL NOT backfill when the rule is enabled later.
- WHEN a key already has an award THE SYSTEM SHALL re-price it with the unit points recorded on its first row (rule changes do not silently re-price old work); reversals always apply.
- IF the award code fails THEN the source action (exam submit, grading, …) SHALL still succeed; the failure is reported to the log.
- Awards SHALL run after the source transaction commits.

### US-004 Student
- WHEN a student opens «نقاطي» THE SYSTEM SHALL show the balance, the transaction history (date, rule label, source, ±points, reason), earned rewards with status, and — separately, read-only — the Behavior points balance.
- Students' exam points SHALL use correct answers of the best submitted attempt (`student_answers.is_correct`), at submit time (essay exams never reach `graded`).

### US-005 Teacher
- WHEN a teacher opens «نقاطي» THE SYSTEM SHALL show balance + history like US-004.
- Teacher aggregate rules (`teacher.assignments`, `teacher.exams`, `teacher.discussion_participation`, `teacher.live_rooms_attendance`) SHALL use the calc mode chosen by the admin per rule: «نسبة تفاعل الطلاب» (students who acted ÷ targeted students) or «الإجابات الصحيحة» (average correct ratio of those who acted), × rule points, per source item.

### US-006 وكيل
- WHEN a وكيل submits a job-performance evaluation on or before the form's `close_date` THE SYSTEM SHALL award the rule points per evaluated person (so the total grows with the number of teachers evaluated); late or no-deadline submissions earn nothing.
- WHEN a وكيل completes a class visit within the configured window THE SYSTEM SHALL award points per visit, up to the configured visits-per-term target.
- WHEN a وكيل adds a school schedule on or before the configured deadline THE SYSTEM SHALL award the rule points once per schedule; deleting it reverses.
- «جميع ما يتم تحديده يظهر للشخص المحدد له على حسابه»: the وكيل's «نقاطي» page SHALL show his rules' deadlines/targets next to his balance.

### US-007 Parent (read-only)
- WHEN a parent opens «نقاط أبنائي» THE SYSTEM SHALL show each linked child's (`parent_student`) balance, history and rewards; never other students.

### US-008 Ledger, balances and corrections (school admin)
- THE SYSTEM SHALL list balances per school, filterable by beneficiary type, grade (section), class and name, and open a person's ledger.
- WHEN an admin with `points.adjust` adds a manual correction THE SYSTEM SHALL require a reason and write a `manual` row; editing or deleting rows SHALL NOT be possible.
- An admin SHALL NOT adjust his own balance.

### US-009 Reward periods & rewards (from «الأول»)
- WHEN a school admin creates a reward period (name, start, end inclusive, optional grade, min points) THE SYSTEM SHALL use the rules' in-period points (if set) for awards dated inside it.
- WHEN a student's points earned inside an active period reach `min_points` THE SYSTEM SHALL create exactly one reward (status `earned`) for that student and period.
- WHEN an admin marks a reward delivered THE SYSTEM SHALL store `delivered_at` and `delivered_by`.
- Reward periods SHALL be soft-deleted; deleting a period does not touch the ledger.

### US-011 Educational supervisor (job title `supervisor`)
**As** an educational supervisor **I want** to see the points of the schools I manage.
- Today a supervisor account carries the `school-admin` role (`app/Modules/Users/Controllers/AdminController.php:154`), so WHILE scoped to one of his `managedSchoolIds()` THE SYSTEM SHALL give him the school-admin screens (balances, ledger, rewards, honour list, overrides where allowed).
- Approving teacher portfolio entries and setting the class-visit target/window «للمجمع» wait on two missing pieces (portfolio approval flow, supervisor↔complex link) — open questions 4 and 6. Until then the school admin sets the visit target/window as rule options.

### US-010 Honour list «المتميزين»
- THE SYSTEM SHALL compute, per school and month, the top 10 by points earned that month, separately for students (optionally per grade), teachers and وكلاء — from the ledger, not hand-picked.

## Non-functional requirements

### NFR-001 Auditability
Ledger rows are insert-only (no `updated_at`, no soft delete, no update/delete routes). Each row stores rule key, source type+id, kind (`award` / `adjust` / `reverse` / `manual`), reason, `created_by`, and `meta` (units, unit points, calc mode, period id) so a row can be explained without the current settings. Rule-setting rows store `updated_by`.

### NFR-002 Idempotency
DB unique key `(user_id, rule_key, source_type, source_id, revision)`. A writer reads the key's rows under lock, computes `revision = count`, inserts once. Concurrent first awards end in a gap-lock deadlock or a unique violation; both are retried (`DB::transaction($fn, 3)` + one retry on unique violation), and the re-read sees the winner's row. This is the guard Al-Awwal lacked (1,381 duplicate groups, up to 507 rows for one exam).

### NFR-003 School scoping
Every query goes through the repository with `school_id` from `scopedSchoolId()` (fail-closed; super-admin with no scope = all schools). The ledger's `school_id` is the **beneficiary's** `users.school_id` at award time (exams, portfolio, weekly plans and schedules have no `school_id`).

### NFR-003b Which balance
A person's balance = sum of **all** his ledger rows (follows a student who changes school). Staff lists show rows earned in the selected school (`school_id` filter), so a transferred student starts at 0 in the new school's lists while «نقاطي» keeps the total.

### NFR-004 Performance — balance strategy
Balance = `SUM(points)` over the covering index `(school_id, user_id, points)`; history uses `(user_id, created_at)`. No cached balance table: a cache must be kept in sync by every writer and can drift, while the ledger stays small (Al-Awwal: ~70k rows in years; ViewClass has far fewer users). Upgrade path if a school's balances page passes ~200 ms: a `point_balances` table updated in the same transaction as the ledger insert.

### NFR-005 Safety of host flows
Hooks never throw into the host action (`SyncActivityPoints` catches and `report()`s). No queue: prod-like configs may run `QUEUE_CONNECTION=sync` or without a worker; awards run synchronously after commit via `DB::afterCommit`.

### NFR-006 i18n / UI
All strings in `lang/ar/points.php` + `lang/en/points.php`; icons via `<x-svg-icon>`; RTL.

## Open questions (client only)
1. Point values per rule — admin will fill them (already told the client); confirm nobody expects defaults from us.
2. «التحفيز» under teachers — what activity is it?
3. «التحضير»: is the weekly plan the preparation? With one plan per class+subject+week, "كل حصتين = تحضير" can't be counted — do they want a separate preparation upload per lesson?
4. «ملف الإنجاز» approval (student by teacher, teacher by complex supervisor) and «وفق النسبة المحددة» — percentage of what? Approval flow does not exist yet; build it as a separate card?
5. «غرف النقاش — نسبة الحضور»: do they mean live virtual classes (our reading) or a presence record inside discussion rooms?
6. Class visits: who is «المشرف التعليمي للمجمع» in the system (no supervisor↔complex link today)? Until answered, the school admin sets the target and window.
7. Exams with several attempts: best attempt (our default) or first?
7b. «المواد العلمية»: is it the online exams of the school subjects in ViewClass (our reading — the only student source wired now) or something else?
8. «الأول / الأول بلس»: are these external platforms we must integrate, or the #368 programs?
9. Videos and worksheets: build view/like/comment tracking and worksheets (separate cards)?
10. Should the honour list appear outside the points page (dashboard / landing page)?
