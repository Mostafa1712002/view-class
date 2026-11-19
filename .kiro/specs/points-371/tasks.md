# Tasks: Points & Rewards (Gamification) — Trello #371

Worktree `/home/mostafa/www/viewclass-wt/points-371`, branch `feature/points-371`. Read `requirements.md` + `design.md` first.
Model split (repo CLAUDE.md): Opus planned this; Sonnet/Codex implement; Opus reviews before deploy. Implementers stop and ask on any ambiguity; no product decisions.
Commits: conventional subject, no AI/session trailer. Data work (rule values, periods, demo points, backfill) = **rawaniedu.com only**.

**File ownership:** each task lists the files it may touch. Shared files (`routes/web.php`, `resources/views/components/sidebar.blade.php`, `PermissionCatalog.php`, `RepositoryServiceProvider.php`, `AppServiceProvider.php`, `lang/*/shell.php`) are touched ONLY by the task that names them (0.3 for RepositoryServiceProvider, 1.1 for the rest).

```
Phase 0 (core, 1 agent) ──merge to main──► #368 can call the contract
        │
Phase 1 (wiring, 1 agent)
        │
        ├── Phase 2: 2A staff screens ‖ 2B my points          (parallel)
        └── Phase 3: 3.1 ‖ 3.2 ‖ 3.3 ‖ 3.4 ‖ 3.5 ‖ 3.6 hooks    (parallel, need Phase 0 only)
                                   │
                        Phase 4: review → local E2E → deploy → card
```

## Phase 0 — Core ledger + public contract (one agent; land on main first)

### Task 0.1: Schema + models
- [x] `database/migrations/2026_11_19_371000_create_points_tables.php` — `point_rule_settings`, `point_transactions`, `point_reward_periods`, `point_rewards` exactly as design §3 (sqlite-safe: strings not enums, no raw ALTER; unique keys + indexes as listed)
- [x] `app/Modules/Gamification/Models/{PointTransaction,PointRuleSetting,PointRewardPeriod,PointReward}.php` (`PointTransaction`: `UPDATED_AT = null`, `meta` cast array, no update/delete helpers; `PointRewardPeriod`: SoftDeletes)
**Outcome:** tables migrate on MySQL and sqlite. **Dependencies:** none

### Task 0.2: Catalog, settings resolver, DTO
- [x] `Support/RuleCatalog.php` — all 20 rules of design §4 (beneficiary, calc_mode, wired flag, options schema), `get()`, `forBeneficiary()`
- [x] `Support/RuleSettings.php` — `effective(ruleKey, schoolId)` per design §5 (platform master switch, school only narrows, override only when allowed), per-request memo
- [x] `DTOs/ActivityPoints.php` — readonly (ruleKey, userId, sourceType, sourceId, units float, actorId?, reason?, occurredAt?)
- [x] `lang/ar/points.php` + `lang/en/points.php` — `rules.<key>` (Arabic = file wording, design §4), `sources.<type>`, `kinds.<kind>`, `calc_modes.*`, `unwired_badge` («غير مربوط — الميزة غير موجودة»)
**Dependencies:** 0.1

### Task 0.3: Repositories (+ the only edit to RepositoryServiceProvider)
- [x] `Repositories/Contracts/PointLedgerRepository.php` + `EloquentPointLedgerRepository.php` — `keyRowsForUpdate`, `insert`, `balance(userId)`, `balances(schoolId, filters)`, `history(userId)`, `sourceKeys(type,id)`
- [x] `Repositories/Contracts/PointSettingsRepository.php` + Eloquent — platform row (`school_id=0`) and school rows
- [x] `Repositories/Contracts/RewardRepository.php` + Eloquent — periods CRUD, `activePeriods(schoolId, sectionId, date)`, `periodSum(user, period)` (end date inclusive), rewards insert/list/deliver, `honourList(schoolId, month, type, sectionId)`
- [x] `app/Providers/RepositoryServiceProvider.php` — bind the 3 contracts (**shared file — only this task**)
**Dependencies:** 0.1

### Task 0.4: Actions (public contract)
- [x] `Actions/SyncActivityPoints.php` — algorithm in design §5 (afterCommit + rescue/report, beneficiary check vice-slug first, lock key rows, snapshot unit points, revision, `DB::transaction($fn, 3)` for deadlocks + one retry on unique violation; returns void)
- [x] `Actions/RevokeSourcePoints.php`
- [x] `Actions/CheckRewardThreshold.php`
**Dependencies:** 0.2, 0.3

### Task 0.5: Core tests
- [x] `tests/Feature/PointsLedgerTest.php` and `tests/Feature/PointsRewardsTest.php` — cases in design §12; per-class migration loading (copy `CounselingAccessTest::refreshDatabase()`, add glob `2026_11_19_371*`)
**Dependencies:** 0.4 · **Outcome:** green; branch merged to main so #368 can rebase and call `SyncActivityPoints`

## Phase 1 — Wiring (one agent; owns every shared file)

### Task 1.1: Routes, permissions, policy, sidebar
- [x] `app/Modules/Gamification/Routes/web.php` — every route of design §8 (names exact) pointing at the Phase 2 controllers
- [x] `routes/web.php` — `require` the module routes (next to the Counseling require, `routes/web.php:1325`)
- [x] `app/Modules/Users/Support/PermissionCatalog.php` — add `'points'` group (design §10)
- [x] `database/migrations/2026_11_19_371001_seed_points_permissions.php` — 4 permissions + `permission_role` for `school-admin`; reversible
- [x] `app/Modules/Gamification/Policies/PointsPolicy.php` + register in `app/Providers/AppServiceProvider.php` (abilities: viewStaff, adjust (not self), manageRules, editPlatformRules, editRuleOverride(ruleKey) (no `vice.*` for a وكيل), manageRewards, viewPerson(user), viewChild(child))
- [x] `resources/views/components/sidebar.blade.php` — 4 edits of design §9, all behind `Route::has`
- [x] `lang/ar/shell.php` + `lang/en/shell.php` — `nav_my_points` «نقاطي», `nav_children_points` «نقاط أبنائي»
- [x] Empty controller classes with the method names used in routes (bodies filled by 2A/2B) so the route file loads
**Dependencies:** Phase 0

## Phase 2 — Screens (2 agents in parallel; RTL, `<x-svg-icon>`, existing layout/components)

### Task 2A: Staff screens
Files: `Controllers/{ManagePointsController,PointRulesController,RewardsController}.php`, `Actions/{AdjustPoints,SaveRuleSettings,DeliverReward}.php`, `Http/Requests/*`, `resources/views/points/manage/*.blade.php`, `lang/{ar,en}/points_manage.php`, `tests/Feature/PointsManageAccessTest.php`
- [x] Balances (tabs طلاب/معلمون/وكلاء, filters grade/class/name, behaviour balance column read-only from `behavior_records`, scoped `scopedSchoolId()`)
- [x] Person ledger + manual adjust (reason required; not self)
- [x] Rules matrix: platform columns (super-admin), override columns (school admin, only allowed rules), unwired rules greyed with badge; options inputs per rule (basis / visits_per_term / window / deadline)
- [x] Reward periods CRUD (overlap warning), rewards list + deliver, honour list (month, type, grade)
- [x] Tests: staff cases of design §12 `PointsAccessTest`
**Dependencies:** 1.1

### Task 2B: «نقاطي» / «نقاط أبنائي»
Files: `Controllers/MyPointsController.php`, `resources/views/points/my/*.blade.php`, `lang/{ar,en}/points_my.php`, `tests/Feature/MyPointsAccessTest.php`
- [x] Balance, history (rule label, source label, ±, reason, date), rewards with status, behaviour balance (read-only, separate card)
- [x] Parent: child switcher limited to `children()`; foreign `?child=` → 404
- [x] وكيل: list his `vice.*` rules with effective deadline / target / window («جميع ما يتم تحديده يظهر للشخص المحدد له على حسابه»)
**Dependencies:** 1.1

## Phase 3 — Hooks (6 agents in parallel; each owns its hook class, host files and test file)
Every host edit = one line after the save (deletes: after the delete succeeds; pass the id). Never wrap host logic; never change host behaviour. Each test file loads the legacy migration globs it needs (or creates minimal tables if a chain has MySQL-only ALTERs).

### Task 3.1: Exams
Files: `Hooks/ExamPointHooks.php`, `app/Http/Controllers/StudentExamController.php` (after txn ~:171, `autoEndAttempt` ~:300), `app/Http/Controllers/Admin/ExamController.php` (`reopenAttempt` :411, `destroy` after delete :268), `tests/Feature/PointsHooksExamTest.php`
- [x] `student.correct_answers.subjects` (best submitted attempt, `is_correct` count) + `teacher.exams` (basis interaction/correct); delete → revoke
**Dependencies:** Phase 0

### Task 3.2: Assignments
Files: `Hooks/AssignmentPointHooks.php`, `app/Http/Controllers/StudentAssignmentController.php` (:81), `app/Http/Controllers/Admin/AssignmentController.php` (:215 grade, after delete :190), `tests/Feature/PointsHooksAssignmentTest.php`
- [x] `student.assignments` (score ratio on grade/re-grade) + `teacher.assignments`; delete → revoke
**Dependencies:** Phase 0

### Task 3.3: Discussion rooms + live rooms
Files: `Hooks/DiscussionPointHooks.php`, `Hooks/VirtualClassPointHooks.php`, `app/Modules/Discussion/Controllers/DiscussionController.php` (:149, :164), `app/Modules/Discussion/Controllers/ManageDiscussionController.php` (:103, :236, :251), `app/Modules/VirtualClasses/Actions/RecalcAttendanceAction.php` (end of `execute`), `tests/Feature/PointsHooksRoomsTest.php`
- [x] `teacher.discussion_participation` (distinct student commenters ÷ targeted) and `teacher.live_rooms_attendance` (present+late ÷ targeted); room delete → revoke
**Dependencies:** Phase 0

### Task 3.4: Preparation (weekly plans)
Files: `Hooks/PreparationPointHooks.php`, `app/Http/Controllers/Admin/WeeklyPlanController.php` (:410, :604, after delete :508), `tests/Feature/PointsHooksPreparationTest.php`
- [x] `teacher.preparation` 1 per plan; delete → reverse («في حال حذف تحضير تحذف الدرجة»)
**Dependencies:** Phase 0

### Task 3.5: Job performance + class visits (وكيل)
Files: `Hooks/EvaluationPointHooks.php`, `app/Modules/Evaluation/Actions/{SubmitEvaluation,ReopenEvaluation,RejectEvaluation}.php` (:104/:298, :49, :53), `app/Modules/Evaluation/Controllers/ClassVisitController.php` (:159), `tests/Feature/PointsHooksEvaluationTest.php`
- [x] `vice.job_performance_on_time` (submitted ≤ `close_date`, job-performance forms only, evaluator is وكيل)
- [x] `vice.class_visits` (window + per-term target from rule options; term from `academic_terms` via the school's current `academic_years`)
- [x] reopen/reject/visit delete → 0
**Dependencies:** Phase 0

### Task 3.6: School schedule (وكيل)
Files: `Hooks/SchedulePointHooks.php`, `app/Http/Controllers/Admin/ScheduleController.php` (:270, after delete :343), `tests/Feature/PointsHooksScheduleTest.php`
- [x] `vice.school_schedule_on_time` (actor is وكيل, created ≤ `options.deadline`); delete → revoke
**Dependencies:** Phase 0

## Phase 4 — Review, verify, ship

### Task 4.1: Review
- [ ] Opus review of the full diff (or `/code-review` / `codex:rescue`): idempotency, scoping, hooks never throw, no host behaviour change, no MySQL-only migration code
- [ ] `php artisan test --filter=Points` green (on a disposable DB — never the shared dev DB)

### Task 4.2: Local end-to-end
- [ ] Local app: super-admin enables `student.correct_answers.subjects` (e.g. 2/answer) + `teacher.exams`; student submits an exam → `/my/points` shows the row and balance; teacher re-opens attempt → delta; delete exam → reverse; school admin sees balances, adjusts, creates a period, reward appears at threshold, marks delivered; parent sees only his child (Playwright screenshots in `claudedocs/`)
- [ ] Revert local test data; `git diff --stat` = feature only

### Task 4.3: Deploy
- [ ] Code → **both** viewclass.newaves-systems.com and rawaniedu.com (commit → push → ssh → `git pull` → `migrate --force` → `view:cache`; one batched ssh per box)
- [ ] Data → **rawaniedu.com only**: any rule values / periods / demo points the client or tester needs; nothing on viewclass
- [ ] Live smoke on both: `/manage/points`, `/manage/points/rules`, `/my/points` open (200); on rawaniedu only, the exam → balance check

### Task 4.4: Card
- [ ] Trello #371 → `testing prompt`, Arabic QA comment (what to test + where), assign Mohamed Farouk; post the client questions from requirements.md «Open questions»; tracking card per the team rules
- [ ] Update this file's progress table + `.kiro/specs/viewclass-sprint-2026-11-16/tasks.md` line for 371

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 0. Core ledger + contract | 5 | 5 | Done (not yet merged to main) |
| 1. Wiring | 1 | 1 | Done |
| 2. Screens | 2 | 2 | Done |
| 3. Hooks | 6 | 6 | Done |
| 4. Review / verify / ship | 4 | 0 | Not Started |
| **Total** | **18** | **14** | **78%** |

## Deferred (not in this card)
- Portfolio approval flows (student by teacher, teacher by complex supervisor), التحفيز, video tracking, worksheets, separate preparation upload, complex-supervisor ownership of visit targets — wait for client answers; rules already in the catalog as unwired.
- `points:reconcile` / backfill command — only if the client asks (rawaniedu only).
- Frozen monthly honour list snapshot, cached balance table — only when measured need.

## Round 2 (2026-11-19)
- [x] Missing deadline = on time (EvaluationPointHooks + test)
- [x] Recalc hooks: assignment edit, exam edit/questions, discussion room update, live room update (+ tests)
- [x] «نقاطي» sidebar link for the وكيل
- [x] Kept: weekly-plan duplicate awards per copy
- [ ] Manual exam grading / assignment resubmit after grading: no such host path exists — nothing to hook
