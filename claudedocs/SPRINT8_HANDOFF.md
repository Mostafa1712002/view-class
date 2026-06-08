# Sprint 8 — Evaluation Engine — Handoff (resume tomorrow)

**Last worked:** 2026-06-08 · **Branch:** `main` · **HEAD:** `cde877c`
**Spec:** `.kiro/specs/trello-sprint8-evaluation-engine/{requirements,design,tasks}.md`

## TL;DR
Building the Sprint 8 evaluation engine (Trello cards: `sprint 8` + Task 1–20 + permissions/notifications/audit/acceptance). Foundation + authoring + targeting/publish + execution/scoring are **done and committed to `main`**, verified locally. **NOTHING is deployed to live** — there is a deliberate review gate; the user must sign off before the engine ships. Remaining: approval, job-perf linkage, class visits, reports, and cross-cutting (permissions/notifications/audit/acceptance).

## ⚠️ Hard rules in force
- **Do NOT deploy the evaluation engine to live** until the user explicitly approves. Everything is local + committed only.
- All engine work is committed to `main` and pushed. Local `viewclass.test` (Valet) is the test env; DB MySQL `root`/`123` db `viewclass`; admin login `admin` / `Admin@12345`.
- Conventions: `app/Modules/Evaluation/` module; BS4 + Line Awesome (`la la-*`) + select2 + gold theme (`var(--gold-*)`); `layouts.app`; `content-header row`; `@lang()`; multi-tenant scope via `App\Modules\Users\Controllers\Concerns\HasSchoolScope` → `activeSchoolId()`; routes in `routes/web.php` admin group (`role:super-admin,school-admin`, prefix `admin`, name `admin.`).
- Decision (recorded design §11): **keep PHP enums** for types/statuses/domains this sprint (revisit a DB-managed registry later).
- Never `git add -A`; commit explicit paths; conventional messages; no AI attribution.

## ✅ Done (committed to main)
- **P0 Foundation** — 14 migrations (13 tables + `score_breakdown` add), 13 models, 5 enums, school-scoped repositories (bound in `RepositoryServiceProvider`), services `AuditTrail`/`EvaluationNotifier`/`FormCompletenessChecker`. ar/en lang `lang/*/evaluation.php`.
- **P1 Authoring (Tasks 1–5)** — forms list (KPIs/filters/table, sidebar "نماذج التقييم"), create/edit form (16 settings toggles, validation), type logic (checklist hides levels; rubric auto level %s), items (weights=100 gate, flags, ordering, disable-not-delete), indicators (rubric level-binding).
- **P2 Targets/Evaluators/Publish (Tasks 6–8)** — targets w/ org filters + summary + dedup; evaluators linked to target subsets + self-eval block; **publish freezes an `EvaluationFormSnapshot`**, locks structure, notifies evaluators; close/archive.
- **P3 Execution + Scoring (Tasks 9–13)** — "التقييمات" my-evaluations (required / my-results tabs), subject picker, type-aware execution screen, evidence (files/links, required-evidence gate), and the **scoring engine** (`app/Modules/Evaluation/Scoring/`: ScoringStrategy + Rubric/RatingScale/Checklist scorers + factory + ScoreResult). Scores bind to the snapshot so historical scores never change.

### Key commits
`482eb0c` schema · `d2264b6` models+enums · `f12c7ae` repos+services · `c327a6e` Task1 · `ab2e619` Tasks2-3 · `d5632c4` Tasks4-5 · `62394bd` fix missing evaluations migration · `07bec0e` Phase2 · Task13 scoring · `cde877c` Phase3 execution.

### Local test data seeded (for resuming verification)
Forms #5 (rubric), #6 (rating_scale), #7 (checklist) — all **published**, school_id=1, admin assigned as evaluator on teacher subjects 47/48. Evaluations: #1 completed (86.67%), #2 draft, #3 admin-as-subject completed. Forms #1/#2 older; **#3 is archived** (consumed by a publish test — create a NEW draft if you need a clean one).

## 🔜 Remaining work (build next, same agent-driven pattern)
- **P4 — Approval + Job-perf (Tasks 14–15):** approval cycle (approve / reject+reason / request-review, lock, reopen-by-permission) on submitted evaluations; job-performance **linkage settings + a linked-results view** (the full aggregating job-performance module is a later sprint, per the user's decision). Submit currently defaults to `completed`; wire `pending_approval` path here.
- **P5 — Class visits (Tasks 16–18):** list (filters/statuses/export), schedule (timetable validation via `ClassVisitRepository::existsForSlot` — already null-period-safe; only offer published class-visit-only forms; notify teacher unless secret), execute (opens the linked form → creates an Evaluation → completes + links + notifies). Models/repo/notifier already exist.
- **P6 — Reports (Tasks 19–20):** supervisor summary report (KPIs + per-supervisor table), detailed supervisor report (one row per evaluation), general-manager screen (cross-org filters, KPIs, per-teacher rows). Aggregate from `evaluations` + `class_visits`. Apply **multi-evaluator averaging** (`settings.average_on_multiple`) here at read time.
- **P7 — Cross-cutting + acceptance:**
  - **Permissions/role access** (BLOCKER for real use): evaluator/subject pages live in the `super-admin,school-admin` admin group; teachers/supervisors can't reach them yet. Decide route grouping/permissions (seed slugs into `school_role_permissions` + policies). This is the biggest open gap.
  - All 13 notifications firing + close-date-approaching scheduled command.
  - Audit-log read screen (filter `activity_logs` where action starts `evaluation.`) — logging already happens.
  - Export everywhere; verify old evaluations unaffected by later form edits (snapshot already guarantees scoring).
  - Walk the 27 acceptance checks (شروط قبول) + map the 5 QA test cards.

## 🐞 Open flags / gotchas (from build agents)
- **Response-row contract (load-bearing for scoring):** Rubric = 1 row/item (`indicator_id` NULL, `level_id`); Rating = 1 row/indicator (`indicator_id`+`level_id`); Checklist = 1 row/indicator (`indicator_id`+`checklist_value`). Any new write path must follow this or scores silently become 0. Centralized in `Actions/Concerns/WritesResponses.php`.
- **Scoring reads the snapshot payload** (`PublishEvaluationForm::buildPayload()` shape), not the live form. Rubric rank = level `sort_order` ascending (weakest→strongest) — keep levels stored weakest-first.
- `score_breakdown` is a JSON column on `evaluations` (added in P3) storing reproducibility inputs.
- Visibility flags `visible_to_evaluator_only` / `visible_to_subject_after_result` are NOT yet filtered in the read-only result view — do in P4/P6.
- Item toggle/reorder don't re-check the 100% weight rule (publish gate backstops it) — tighten if desired.
- `EvaluationEvidence` needed an explicit `$table='evaluation_evidences'` (Laravel mis-pluralized) — already fixed.

## ▶️ How to resume tomorrow
1. `cd /home/mostafa/www/viewclass && git pull origin main` (should be at/after `cde877c`); `php artisan migrate` (applies `score_breakdown` if a fresh DB); `php artisan view:clear`.
2. Re-read this file + `.kiro/specs/trello-sprint8-evaluation-engine/tasks.md` (phase checklist).
3. Continue with **P4 (approval + job-perf)** next (it unblocks the full lifecycle), then P5, P6, P7. The user is driving via `/loop untill finish all` + "use agents".
4. **Agent pattern that works here:** delegate one cohesive phase per agent, SEQUENTIALLY on the main tree (parallel/worktree breaks — worktrees lack `vendor`/`.env`, and Valet only serves the main tree). Each agent: read spec + reference slices, build, verify locally (Valet + DB + Playwright with concrete evidence), commit explicit paths + push, report. Then I health-check `main` (routes + boot + migrations tracked) before the next agent.
5. **Watch for:** agents finding pre-existing uncommitted partial work in the tree (happened in P2) — verify-and-commit is fine; and glob mistakes when committing migrations (the `evaluations` table was missed once — always `git ls-files database/migrations | grep evaluation` to confirm).
6. When the engine is feature-complete and locally verified, STOP and get user sign-off, THEN deploy to live (`git pull` + `migrate --force` + `view:cache` + chown; never artisan as root) and verify on `viewclass.newaves-systems.com`.

## Separate track — 3 bug cards (already shipped to live, awaiting QA)
In `testing prompt`, assigned to QA (mahmoud yasser): **#128** private-library audience selects (redesign + jQuery `.data()` cache fix), **#162** user-cards PDF (dompdf→mPDF Arabic/RTL) + header, **#163** exams (Bootstrap-Icons loaded globally + app timezone → Asia/Riyadh fixed "exam not started" + exam-schedule tab already existed). If QA bounces any, handle via `/trello` (reproduce live first).
