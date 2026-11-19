# Design: Points & Rewards (Gamification) — Trello #371

Read `requirements.md` first (coverage table with file:line hooks, open questions).

## 1. Shape in one picture

```
 host module event (exam submit, grade, plan delete, evaluation submit …)
        │  one line, after its own save
        ▼
 Gamification\Hooks\<Source>PointHooks::<event>(model) ← all mapping logic lives here (one class per source)
        │  computes target UNITS per (rule, source, beneficiary)
        ▼
 Gamification\Actions\SyncActivityPoints::execute(ActivityPoints dto)   ← PUBLIC CONTRACT (#368 calls this directly)
        │  DB::afterCommit → rescue(report) → never throws into host
        │  settings = RuleSettings::effective(rule, school)
        │  lock key rows → current net → target = round(units × unitPoints) → delta
        ▼
 point_transactions (insert-only ledger)  ──► CheckRewardThreshold (students, delta > 0)
        │                                          ▼
        ▼                                     point_rewards (one per period+student)
 balances / history / honour list = SUM queries on the ledger
```

## 2. Module layout (`app/Modules/Gamification`)

```
Actions/
  SyncActivityPoints.php      # public contract: sync one key to a target (units)
  RevokeSourcePoints.php      # bring every key of a source to 0 (deletes)
  AdjustPoints.php            # manual correction row (admin, reason required)
  CheckRewardThreshold.php    # create reward when period sum ≥ min_points
  SaveRuleSettings.php        # platform row / school override (with permission rules)
  DeliverReward.php
DTOs/ActivityPoints.php       # readonly: ruleKey, userId, sourceType, sourceId, units, actorId?, reason?, occurredAt?
Support/RuleCatalog.php       # const RULES — the catalog (code, not DB)
Support/RuleSettings.php      # effective settings resolver (catalog ← platform ← school)
Hooks/ExamPointHooks.php  AssignmentPointHooks.php  DiscussionPointHooks.php  VirtualClassPointHooks.php
      PreparationPointHooks.php  EvaluationPointHooks.php  SchedulePointHooks.php
                              # one class per source (parallel-safe tasks); methods = host events; compute units; call the actions
Repositories/Contracts/PointLedgerRepository.php    + EloquentPointLedgerRepository.php
Repositories/Contracts/PointSettingsRepository.php  + EloquentPointSettingsRepository.php
Repositories/Contracts/RewardRepository.php         + EloquentRewardRepository.php   (periods + rewards + honour list)
Models/PointTransaction.php  PointRuleSetting.php  PointRewardPeriod.php  PointReward.php
Policies/PointsPolicy.php
Controllers/ManagePointsController.php   # balances, person ledger, adjust
Controllers/PointRulesController.php     # rules matrix + overrides
Controllers/RewardsController.php        # periods CRUD, rewards list, deliver, honour list
Controllers/MyPointsController.php       # student / teacher / وكيل / parent (child switch)
Routes/web.php
resources/views/points/*.blade.php       # (project keeps views under resources/views/<feature>, like counseling)
lang/{ar,en}/points.php
```
ponytail: three repositories, not one per table — periods/rewards/honour share one reader.

## 3. Schema (one migration `2026_11_19_371000_create_points_tables.php`, sqlite-safe: no enum, no raw ALTER)

### `point_rule_settings` — only overrides of the code catalog
| column | type | note |
|---|---|---|
| id | bigint pk | |
| school_id | unsignedBigInteger, default 0 | **0 = platform row** (NOT NULL so the unique key really is unique; MySQL lets NULLs duplicate) |
| rule_key | string(64) | must exist in `RuleCatalog::RULES` (validated) |
| points | int nullable | null = inherit |
| period_points | int nullable | points used inside an active reward period; null = same as points |
| is_enabled | boolean nullable | platform: on/off; school: null inherit / false = switched off |
| allow_school_override | boolean default false | meaningful on platform rows only |
| options | json nullable | rule-specific (basis, visits_per_term, window_start/end, deadline) |
| updated_by | unsignedBigInteger nullable | |
| timestamps | | |
Unique `(school_id, rule_key)`. No soft delete (config; `updated_by` + timestamps are enough; ledger rows carry their own snapshot).

### `point_transactions` — the ledger (insert-only)
| column | type | note |
|---|---|---|
| id | bigint pk | |
| school_id | unsignedBigInteger index | beneficiary's `users.school_id` at write time |
| user_id | unsignedBigInteger | beneficiary |
| rule_key | string(64) | `manual` for manual corrections (kind `manual`) |
| source_type | string(40) | short stable name: `exam`, `assignment`, `weekly_plan`, `evaluation`, `class_visit`, `schedule`, `discussion_room`, `virtual_class`, `manual`, `program_test` (#368)… |
| source_id | unsignedBigInteger | parent object id (`0` for manual) |
| revision | unsignedSmallInteger | 0,1,2… per key |
| points | integer | signed, never 0 |
| kind | string(10) | `award` (rev 0) · `adjust` (re-price delta) · `reverse` (to 0) · `manual` |
| reason | string(255) nullable | required for `manual`; auto text for reverse ("حذف التحضير") |
| meta | json nullable | `{units, unit_points, calc_mode, basis, period_id, correct, total}` |
| created_by | unsignedBigInteger nullable | actor (null = system) |
| created_at | timestamp | no `updated_at`, no `deleted_at` (immutable) |
Indexes: **unique `(user_id, rule_key, source_type, source_id, revision)`** (idempotency guard) · `(school_id, user_id, points)` covering for balances · `(user_id, created_at)` history · `(school_id, created_at)` honour list / period sums · `(source_type, source_id)` for revoke-by-source.

### `point_reward_periods`
id · school_id index · section_id nullable (grade = `sections.id`; null = all grades) · name string · starts_on date · ends_on date (**inclusive**: queries use `created_at < ends_on + 1 day`, fixing Al-Awwal's lost last day) · min_points unsigned int · created_by · timestamps · softDeletes. Index `(school_id, starts_on, ends_on)`.

### `point_rewards`
id · school_id index · user_id · period_id · points_at_award int · status string(10) `earned|delivered` · delivered_at nullable · delivered_by nullable · timestamps. **Unique `(period_id, user_id)`** — one reward per student per period (Al-Awwal allowed one per source type from the same pile of points).

Honour list: **no table** — computed from the ledger per month (see §7).

## 4. Rule catalog (`Support/RuleCatalog::RULES`, code)

Each entry: `key => [beneficiary, calc_mode, unit, wired(bool), label key, options schema]`. Labels (ar) are the file's words; `lang/ar/points.php` → `rules.<key>`.

| key | label (ar) | beneficiary | calc mode → units | source_type / beneficiary resolution | wired |
|---|---|---|---|---|---|
| `student.correct_answers.alawwal` | الإجابات الصحيحة — الأول | student | per_correct_answer | external | no |
| `student.correct_answers.alawwal_plus` | الإجابات الصحيحة — الأول بلس | student | per_correct_answer | external | no |
| `student.correct_answers.ana_wal_qudurat` | الإجابات الصحيحة — أنا والقدرات | student | per_correct_answer | `program_test` via #368 contract | by #368 |
| `student.correct_answers.nafes` | الإجابات الصحيحة — نافس | student | per_correct_answer | via #368 | by #368 |
| `student.correct_answers.tahsili` | الإجابات الصحيحة — تحصيلي | student | per_correct_answer | via #368 | by #368 |
| `student.correct_answers.subjects` | الإجابات الصحيحة — المواد العلمية | student | per_correct_answer: max `is_correct` count over the student's submitted attempts of the exam | `exam` / attempt student | yes |
| `student.assignments` | الواجبات (نسبة الدرجة) | student | ratio: `score / max_score` of the graded submission | `assignment` / submission student | yes |
| `student.portfolio_approved` | نقاط ملف الإنجاز (بعد اعتماد المعلم) | student | per_event | — | no (no approval flow) |
| `teacher.portfolio_approved` | نقاط ملف الإنجاز (بعد اعتماد المشرف التعليمي للمجمع) | teacher | per_event | — | no |
| `teacher.assignments` | الواجبات | teacher | ratio, `options.basis` = `interaction` (submitted ÷ targeted students) or `correct` (avg score ratio of graded) | `assignment` / `assignments.teacher_id` | yes |
| `teacher.exams` | الاختبارات | teacher | ratio, basis interaction (students with a submitted attempt ÷ targeted) or correct (avg best correct ÷ questions) | `exam` / `exams.teacher_id` | yes |
| `teacher.discussion_participation` | غرف النقاش — نسبة المشاركة | teacher | ratio: distinct students with a live comment ÷ targeted students | `discussion_room` / `discussion_rooms.created_by` (if teacher) | yes (partial) |
| `teacher.live_rooms_attendance` | غرف النقاش — نسبة الحضور | teacher | ratio: present+late ÷ targeted | `virtual_class` / `virtual_classes.teacher_id` | yes (interpretation) |
| `teacher.motivation` | التحفيز | teacher | per_event | — | no (undefined) |
| `teacher.media_video` | وسائط متعددة — فيديو (مشاهدة – لايك – تعليق) | teacher | ratio | — | no |
| `teacher.media_worksheet` | وسائط متعددة — ورق عمل (تحميل – رفع – تصحيح المعلم) | teacher | ratio | — | no |
| `teacher.preparation` | التحضير | teacher | per_event: 1 per weekly plan | `weekly_plan` / `weekly_plans.teacher_id` | yes (partial) |
| `vice.job_performance_on_time` | تقييم الأداء الوظيفي (التسليم في الموعد) | vice | per_event: 1 per evaluation submitted with `submitted_at ≤ form.close_date` (form in job-performance domain) (no close_date = on time, round 2); else 0 | `evaluation` / `evaluations.evaluator_id` | yes (partial) |
| `vice.class_visits` | زيارات صفية | vice | per_event: 1 per visit whose evaluation is submitted, `visit_date` inside `options.window_start..window_end` (default: current term), while awarded visits this term < `options.visits_per_term` (null = no cap) | `class_visit` / `class_visits.supervisor_id` | yes (partial) |
| `vice.school_schedule_on_time` | إضافة الجدول المدرسي (في الوقت المحدد) | vice | per_event: 1 if created on/before `options.deadline` | `schedule` / acting user | yes (partial) |

Ratio rules: `points = round(units × unit_points)`, units ∈ [0,1], one key per source item, recomputed each time a student acts → a few small delta rows per item (ceiling: one row per rounding step; upgrade path = recompute at due date via a scheduled command if the history gets noisy).

Beneficiary check: the action verifies the user matches the rule's beneficiary — `vice` = `jobTitle?->slug === 'vice'` **checked first** (وكلاء and supervisors carry the `school-admin` role, `Users/Controllers/AdminController.php:154`), then `isTeacher()`, then `isStudent()`. Mismatch → no write.

## 5. Award algorithm (`SyncActivityPoints`)

```
execute(dto):
  DB::afterCommit(fn) → rescue(fn, report: true)          # never breaks the host; runs immediately when no txn
  fn:
    rule = RuleCatalog::get(dto.ruleKey) or return null
    user = users.find(dto.userId) (withTrashed: no)   ; beneficiary check
    DB::transaction:
      rows = ledger.keyRowsForUpdate(user, rule, srcType, srcId)    # SELECT … FOR UPDATE
      net  = sum(rows.points)
      if rows empty:
          s = RuleSettings::effective(rule, user.school_id)
          if !s.enabled or s.points == 0 or dto.units == 0: return null      # no award, no later backfill
          period = activePeriod(user.school_id, user.section_id, occurredAt)
          unitPoints = (period && s.period_points !== null) ? s.period_points : s.points
      else:
          unitPoints = rows.first.meta.unit_points                           # snapshot: re-fires re-price at the first row's value
      target = round(dto.units × unitPoints)
      delta  = target − net ;  if delta == 0: return null
      insert {revision: rows.count, points: delta, kind: rows.empty ? award : (target==0 ? reverse : adjust), meta…}
      DB::transaction($fn, 3) — retries deadlocks (two writers FOR UPDATE an empty key take gap locks → MySQL deadlock, not a unique violation);
      a unique violation is caught and retried once (re-read); a further failure → report()
    if rule.beneficiary == student and delta > 0: CheckRewardThreshold(user)
```
- Enabled flag is checked only on the first award; a later disable stops **new** keys, existing keys still follow their source (re-grade, delete).
- `RevokeSourcePoints(sourceType, sourceId, reason)`: for each `(user, rule)` key of that source with net ≠ 0 → `SyncActivityPoints` with units 0. Works from the ledger alone, so it is called **after** the delete succeeds (a failed delete must not leave points reversed); hard cascades (exams/assignments) don't matter.
- Class-visit cap: a visit over the per-term cap gets units 0. If a counted visit is later reversed, over-cap visits are **not** promoted automatically; they are re-evaluated only when they fire again. ponytail: simplest correct rule; promote on reversal if the client asks.

### Settings resolution (`RuleSettings::effective`)
`catalog default {points 0, period_points null, enabled false, allow false, options {}}` ← platform row (`school_id=0`) ← school row **only if** platform `allow_school_override`. `enabled = platform.enabled && (school.is_enabled ?? true)` (school can only switch off). Memoised per request.

## 6. Hook points (host code calls `app(<Source>PointHooks::class)->…` — one line each)

| host file:line (main @ 8e0d28af) | call | effect |
|---|---|---|
| `app/Http/Controllers/StudentExamController.php` after txn closes (~:171) and `autoEndAttempt()` ~:300 | `examAttemptChanged($exam, $studentId)` | `student.correct_answers.subjects` (best attempt) + `teacher.exams` |
| `app/Http/Controllers/Admin/ExamController.php:411` `reopenAttempt()` after save | `examAttemptChanged($exam, $studentId)` | recompute (reopened attempt excluded until resubmitted) |
| `app/Http/Controllers/Admin/ExamController.php:266` `destroy()` after `$exam->delete()` (:268) | `examDeleted($examId)` | revoke `exam` source |
| `app/Http/Controllers/StudentAssignmentController.php:81` after save | `assignmentChanged($assignment, null)` | `teacher.assignments` |
| `app/Http/Controllers/Admin/AssignmentController.php:215` after update | `assignmentChanged($assignment, $student->id)` | `student.assignments` + `teacher.assignments` |
| `app/Http/Controllers/Admin/AssignmentController.php:186` after `$assignment->delete()` (:190) | `assignmentDeleted($assignmentId)` | revoke |
| `app/Modules/Discussion/Controllers/DiscussionController.php:149` after addComment, `:164` after delete; `ManageDiscussionController.php:236,251` after delete; `ManageDiscussionController.php:103` room `destroy()` | `discussionRoomChanged($roomId)` / `discussionRoomDeleted($roomId)` | `teacher.discussion_participation` |
| `app/Modules/VirtualClasses/Actions/RecalcAttendanceAction.php` end of `execute()` | `virtualClassRecalculated($vc)` | `teacher.live_rooms_attendance` |
| `app/Http/Controllers/Admin/WeeklyPlanController.php:410` store, `:604` duplicate; `:508` after delete | `weeklyPlanSaved($plan)` / `weeklyPlanDeleted($planId)` | `teacher.preparation` |
| `app/Modules/Evaluation/Actions/SubmitEvaluation.php:104` and `:298` (inside txn — fine, afterCommit) ; `ReopenEvaluation.php:49`, `RejectEvaluation.php:53` | `evaluationChanged($evaluation)` | `vice.job_performance_on_time` and, when `class_visit_id`, `vice.class_visits`; reopen/reject → units 0 |
| `app/Modules/Evaluation/Controllers/ClassVisitController.php:159` destroy | `classVisitDeleted($visitId)` | revoke |
| `app/Http/Controllers/Admin/ScheduleController.php:270` store; `:343` after delete | `scheduleCreated($schedule, auth()->user())` / `scheduleDeleted($scheduleId)` | `vice.school_schedule_on_time` |

"Targeted students" per source: reuse each module's existing audience resolver where one exists (virtual classes recalc already resolves its targets; discussion rooms have `discussion_room_targets` / `class_ids` / `grade_levels`); assignments and exams use `class_id` → students of that class (`users.class_room_id` + `class_student`). Implementer: put one private resolver per source in its hook class; zero targeted → units 0.

## 7. Periods, rewards, honour list (from «الأول», fixed)
- Period active for a student = same school, `section_id` null or = student's `section_id`, award date in `[starts_on, ends_on]`. Overlapping periods: the latest `starts_on` wins (validation warns on overlap).
- `CheckRewardThreshold(user)`: for each active period of the student: `sum(points where school, user, created_at in period)` ≥ `min_points` and no reward → insert (unique key guards races). Rewards are not revoked if the sum later drops (a delivered gift stays delivered; admin sees current sum next to `points_at_award`).
- Deliver: `status=delivered, delivered_at, delivered_by`; scoped to school; policy `points.manage_rewards`.
- Honour list «المتميزين»: `SELECT user_id, SUM(points) … WHERE school_id=? AND created_at in month GROUP BY user_id HAVING SUM>0 ORDER BY 2 DESC LIMIT 10`, joined to users filtered by beneficiary type (and `section_id` for students). Live, not frozen (ponytail: add a snapshot table only if they want the list frozen at month end).

## 8. Screens & routes (`app/Modules/Gamification/Routes/web.php`, required from `routes/web.php`)

| route name | method / path | who | screen |
|---|---|---|---|
| `manage.points.index` | GET `/manage/points` | super-admin, school admin | balances table: type tabs (طلاب / معلمون / وكلاء), filters grade/class/name, balance + this-month + behaviour balance (read-only column) |
| `manage.points.show` | GET `/manage/points/users/{user}` | same, same school | person ledger + manual adjust form |
| `manage.points.adjust` | POST `/manage/points/users/{user}/adjust` | `points.adjust`, not self | |
| `manage.points.rules` | GET `/manage/points/rules` | super-admin (platform), school admin (overrides) | matrix grouped by beneficiary: label, calc mode, points, in-period points, enabled, allow override, options; unwired rules greyed with badge |
| `manage.points.rules.platform` | PUT `/manage/points/rules/platform` | super-admin | |
| `manage.points.rules.school` | PUT `/manage/points/rules/school` | `points.manage_rules`, only overridable rules, never `vice.*` for a وكيل | |
| `manage.points.periods.{index,store,update,destroy}` | `/manage/points/periods` | `points.manage_rewards` | |
| `manage.points.rewards.index` / `.deliver` | GET `/manage/points/rewards`, PATCH `/{reward}/deliver` | `points.manage_rewards` | earned vs delivered filter |
| `manage.points.honour` | GET `/manage/points/honour?month=&type=&section=` | staff | honour list |
| `my.points.index` | GET `/my/points` (`?child=` for parents) | any logged-in user, own data / own children | balance, history, rewards, behaviour balance, (وكيل) his rule deadlines/targets |

All staff queries via repositories with `scopedSchoolId()`; `{user}` and `{reward}` resolved through the scoped repository → 404 outside the school.

## 9. Sidebar (`resources/views/components/sidebar.blade.php` — shared file, one wiring task)
- `:691` (inside `@if($isStaff)`) → `Route::has('manage.points.index') ? route('manage.points.index') : route('programs.coming-soon')`, active on `manage.points.*`.
- Student block (`@if isStudent`, from `:1125`): add «نقاطي» → `my.points.index`.
- Parent block (from `:1261`): add «نقاط أبنائي» → `my.points.index`.
- Teacher: next to `my.certificates` (`:1013` block is teacher‖parent) add an `@if($sidebarUser->isTeacher())` «نقاطي».
- وكيل (staff role): «نقاطي» button on `manage.points.index` header (no extra sidebar item).
All wrapped in `Route::has(...)`.

## 10. Permissions
- `PermissionCatalog::MODULES['points'] = ['label' => 'النقاط والمكافآت', 'actions' => ['view','manage_rules','adjust','manage_rewards']]`.
- Migration `2026_11_19_371001_seed_points_permissions.php` (pattern of `2026_11_16_372001_seed_counseling_permissions.php`): `updateOrInsert` the 4 permissions (group `points`); grant all 4 to role `school-admin` in `permission_role` (matches baseline seed: school-admin holds the whole catalog). `down()` removes them.
- `PointsPolicy`: staff pages require `isSuperAdmin()` or (`isSchoolAdmin()` && `canDo('points.view')`) — never `canDo('points.view')` alone (it default-allows every user). Writes require the specific permission. Self-adjust denied; `vice.*` override edits denied to job title `vice`. Platform rules: `isSuperAdmin()` only.
- Register policy in `AppServiceProvider` (like Counseling, `AppServiceProvider.php:21`); bind the 3 repositories in `RepositoryServiceProvider::$bindings`.

## 11. Integration contract for #368 (and any module)

```php
use App\Modules\Gamification\Actions\SyncActivityPoints;
use App\Modules\Gamification\DTOs\ActivityPoints;

app(SyncActivityPoints::class)->execute(new ActivityPoints(
    ruleKey:    'student.correct_answers.nafes',   // reserved: ana_wal_qudurat | nafes | tahsili
    userId:     $student->id,
    sourceType: 'program_test',                    // stable short name of the PARENT object (test), not the attempt
    sourceId:   $test->id,
    units:      $bestCorrectCount,                 // target units NOW; 0 = take the points back
    actorId:    auth()->id(),                      // optional
));
```
- Idempotent: call it on every submit/regrade/reopen/delete with the current target; only the difference is written.
- Safe inside a transaction (runs after commit) and never throws.
- Returns `void`: the write is deferred with `DB::afterCommit` (runs immediately when no transaction is open). Read the result back with `PointLedgerRepository::balance()` if needed.
- Delete of a whole program test: `app(RevokeSourcePoints::class)->execute('program_test', $test->id, 'حذف الاختبار')`.
- Merge order: Phase 0 of this spec (migration + DTO + action + catalog) lands on `main` first, so #368's branch can call it. Until values are set, calls are no-ops (rules 0/disabled).

## 12. Tests (sqlite in-memory, per-class migration loading like `tests/Feature/CounselingAccessTest.php:16-31`)
Glob `2026_11_19_371*` plus the base globs Counseling uses; each hook test names the legacy globs it needs (exams, assignments, weekly_plans, evaluation, discussion). If a legacy chain contains MySQL-only ALTERs, create the minimal legacy tables in the test's `refreshDatabase()` instead.
- `PointsLedgerTest`: first award; double call = one row; units change → one adjust row; units 0 → reverse row, net 0; disabled / 0 → nothing, then enabling → still nothing for that key until it fires again; snapshot unit points after a rule change; school override ignored when not allowed; platform disabled beats school; pre-inserted same revision → retry succeeds; exception inside → host call returns normally.
- `PointsRewardsTest`: in-period points used; threshold creates one reward; second award no duplicate; last day of period counts; deliver stamps who/when.
- `PointsAccessTest`: student/teacher see only own `/my/points`; parent only linked children (`?child=` of another student → 404); school admin of school A gets 404 for a user of school B; teacher 403 on `/manage/points/rules`; school admin 403 on platform PUT and on a non-overridable rule; وكيل 403 editing `vice.*` overrides and adjusting himself.
- `PointsHooksTest`: exam submit (best of 2 attempts) → student + teacher rows; exam delete → reversed; assignment grade then re-grade → delta; weekly plan create/delete → +/−; evaluation submitted before vs after `close_date`; reopen → reversed.

## 13. Rollout
- Migrations are additive only (4 new tables + permission rows). No change to existing tables.
- **Code** → BOTH `viewclass.newaves-systems.com` and `rawaniedu.com`: commit → push → ssh → `git pull` → `php artisan migrate --force` → `view:cache` (+ route/config cache as each box does today).
- **Data** → `rawaniedu.com` ONLY (user 2026-11-19): setting rule values, reward periods, any demo points, any backfill/import. Never on viewclass.newaves-systems.com. The catalog is in code and the defaults are 0/disabled, so the migration itself seeds no rule data; only the permission rows (schema-level RBAC, same as Counseling) run on both.
- No backfill of past activity by default (rules start disabled; first-award-only). ponytail: a `points:reconcile` command is skipped — add it if the client asks for backfill or a swallowed hook failure is reported; it would replay `PointHooks` over existing sources (rawaniedu only).
- Live check after deploy (both): `/manage/points`, `/manage/points/rules` open; `/my/points` opens for a student; on rawaniedu only, with a test rule enabled: submit an exam as a test student → row appears, balance updates.

## 14. Round 2 (2026-11-19)
- Missing deadline = on time for every deadline rule (job-performance evaluation now like the school schedule).
- Recalc on edits (one host line each, after the save): `AssignmentController::update` → `assignmentEdited` (all students with a submission + teacher); `Admin\ExamController::update` and `ExamQuestionController::updateExamTotalMarks` (every question add/edit/delete/duplicate/bank path) → `examChanged` (teacher key); `ManageDiscussionController::update` → `discussionRoomChanged`; `VirtualClassController::update` → `virtualClassRecalculated`.
- Not applicable: no manual exam grading path exists (answers are auto-graded only; essays stay null); a graded/returned assignment submission is locked for the student, so "resubmit after grading" cannot happen.
- Kept: duplicating a weekly plan awards preparation points per copy.
- Sidebar: «نقاطي» also shown to the وكيل (job title `vice`), same `Route::has` gate.
- Known edge: reassigning a live session/room to another teacher leaves the old teacher's row until his next sync.
