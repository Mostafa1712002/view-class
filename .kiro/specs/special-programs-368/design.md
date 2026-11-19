# Design: البرامج التعليمية الخاصة (قدرات · تحصيلي · نافس) — Trello #368

Inputs: `alawwal-source-map.md` (source = «الأول», cited as **SM §x**) and `viewclass-fit-map.md` (cited as **FM §x**).
Rule of the port: **same screens, flows, labels and formulas as «الأول»; none of its bugs.** Every deliberate deviation is listed in §13.

---

## 0. Decisions at a glance

| # | Decision |
|---|---|
| D1 | One module `app/Modules/SpecialPrograms`. One set of tables for all three programs (`sp_*`), not 3 × 7 copies like «الأول». The program is a column. |
| D2 | Questions live in the **QuestionBankCore tables** (`question_banks`, `bank_questions`, `question_answers`, `passages`, `passage_questions`) in **owner banks** (`is_owner_bank=1`, `school_id=NULL`, `source='al_awwal'`, `external_platform='al_awwal'`, `category_type='special_program'`), one bank per program subject. Program-only attributes (skill, term, week 1–19, `question_order`, grade levels, difficulty copy) live in a **module side table** `sp_questions` keyed by `bank_question_id`. No new columns or enum values on QB tables. |
| D3 | Program definitions (3 programs) are a PHP const (`Support/Programs`), not a table. Program **subjects** (9) are a seeded table because questions, exams and banks point at them. |
| D4 | Students take program exams through a new **attempt layer** (`sp_attempts` + `sp_attempt_items`), never the legacy `exams` engine. Questions are picked **lazily, per student, per subject, on first open**, exactly like «الأول», and frozen in a snapshot. Answers live on the snapshot row. |
| D5 | **Server-side timer**: `deadline_at = started_at + duration`. Reloading never resets it. Answers after the deadline are rejected; overdue attempts are finalized on next read and by a scheduled sweep. |
| D6 | **One score convention** for all programs: item = one answerable question (a passage sub-question is one item); `score_pct = correct / items × 100` (unanswered = wrong); multi-subject exam % = Σcorrect / Σitems. |
| D7 | **Options are never shuffled** (image questions use «أ/ب/ج/د» labels that point into the image). Question order is shuffled; passage sub-questions stay contiguous. |
| D8 | Enrolment is explicit («إضافة طالب لبرنامج»); an exam is visible to an enrolled student who is in one of its target classes **or** target performance groups. |
| D9 | Program banks are **hidden from the legacy exam picker** (`visibleBankIds`): the legacy copy is text-only, so image questions would arrive as a bare «اختر». |
| D10 | Permissions: new group `special_programs` in `PermissionCatalog`; routes use `permission:` (honours job titles), never `role_permission:`. Super-admin-only pages carry route-level `role:super-admin`. |
| D11 | Domain event `ProgramAttemptCompleted` fired once per attempt, after commit, on manual and automatic finish (contract §10). #371 listens; this card ships no listener. |
| D12 | Data import (bank) runs on **rawaniedu.com only**. Migrations create no bank rows, so viewclass.newaves-systems.com shows empty states (§12). |

---

## 1. Module layout

```
app/Modules/SpecialPrograms/
├── Support/
│   ├── Programs.php              # const PROGRAMS: qiyas|tahsili|nafis → label_ar, label_en, default_grade_levels, default_bands, picker
│   ├── PerformanceLevels.php     # 5 fixed levels (name, color, icon) + level for a % given bands
│   └── ProgramAudience.php       # eligible classes per school/program + readiness alerts (§6)
├── Models/                       # SpProgramSubject, SpSkill, SpQuestion, SpSetting, SpEnrollment, SpGroup,
│                                 # SpTeacher, SpExam, SpExamTarget, SpExamSubject, SpSkillShare, SpAttempt, SpAttemptItem
├── Repositories/
│   ├── Contracts/                # QuestionPoolRepository, EnrollmentRepository, GroupRepository, ExamRepository,
│   │                             # AttemptRepository, ReportRepository, BankRepository, SettingsRepository
│   └── Eloquent*.php             # one per contract; ALL school scoping lives here
├── Actions/
│   ├── Import/                   # AlAwwalRowNormalizer, AlAwwalLevelMap, ImportAlAwwalBank
│   ├── Picking/                  # QuestionPicker (interface), QiyasPicker, NafisPicker, TahsiliPicker, Apportion
│   ├── Enrollment/               # EnrollStudents, RemoveEnrollment, SaveGroup, AssignToGroup, AssignTeacher
│   ├── Exams/                    # CreateProgramExam, DeleteProgramExam
│   ├── Attempts/                 # StartSubject, SaveAnswer, LogExit, FinishAttempt, FinalizeOverdue
│   ├── Bank/                     # SaveProgramQuestion, SaveProgramPassage, DeleteProgramQuestion
│   └── Settings/                 # SaveLevelBands, SaveProgramSettings
├── Events/ProgramAttemptCompleted.php
├── Policies/SpecialProgramPolicy.php
├── DTOs/ReportFilters.php
├── Http/Requests/                # StoreExamRequest, EnrollRequest, GroupRequest, QuestionRequest, PassageRequest, LevelsRequest
├── Controllers/
│   ├── Staff/                    # HubController, EnrollmentController, GroupController, TeacherController,
│   │                             # ExamController, BankController, SettingsController, ReportController, TeacherReportController
│   ├── Student/                  # ProgramController, AttemptController, ReportController
│   └── Parent/ProgramController.php
└── Routes/                       # one file per task: staff-setup, staff-groups, staff-bank, staff-settings, staff-exams,
                                  # staff-reports, staff-teacher, student, student-report, parent (globbed from routes/web.php)

app/Console/Commands/SpecialProgramsImportAlAwwal.php   # thin; calls ImportAlAwwalBank (auto-discovered dir)
app/Console/Commands/SpecialProgramsFinalizeOverdue.php # thin; calls FinalizeOverdue
resources/views/special-programs/{staff,student,parent,reports,partials}/…
lang/{ar,en}/special_programs.php          # shared labels (programs, levels, statuses)
lang/{ar,en}/special_programs/{staff,groups,bank,settings,exams,student,reports,student_report,teacher,parent}.php
                                           # per-area keys → __('special_programs/staff.key')
database/migrations/2026_11_19_368000_… → 368009_…
tests/Feature/SpecialPrograms/*Test.php, tests/Unit/SpecialPrograms/*Test.php
```

Conventions (FM §7): views in `resources/views`, migrations in `database/migrations`, no FK to `users.id`, soft deletes on tenant-owned tables, `->whereNumber()` on ids, no implicit model binding on super-admin routes, `<x-svg-icon>`, `@extends('layouts.app')`, design tokens from `design-system.css`, no `ad-` CSS prefix, Chart.js via the same CDN tag as `dashboard.blade.php`.

---

## 2. Schema (all additive; migrations `2026_11_19_368000…368009`)

Naming: `sp_` prefix. Timestamps stored in the app timezone (`Asia/Riyadh`, `config/app.php:68`); no UTC math anywhere (fixes SM §9.2).

### 2.1 Content (platform-owned, no `school_id`)

**`sp_subjects`** — seeded by migration `368001` (9 rows, both servers)

| col | type | notes |
|---|---|---|
| id | bigint PK | |
| program | varchar(10) | qiyas \| tahsili \| nafis |
| code | varchar(30) unique | qiyas_verbal, qiyas_quant, tahsili_physics, tahsili_chemistry, tahsili_biology, tahsili_math, nafis_reading, nafis_math, nafis_science |
| name_ar / name_en | | «القدرات اللفظية», «القدرات الكمية», «تحصيلي فيزياء», «تحصيلي كيمياء», «تحصيلي أحياء», «تحصيلي رياضيات», «نافس قراءة», «نافس رياضيات», «نافس علوم» |
| picks_passages | bool | true only for nafis_reading (replaces «الأول»'s subject-name match «قراءة/عربي», SM §5.I-7) |
| question_bank_id | bigint NULL index | set by the import command when it creates the owner bank |
| source_subject_id | int NULL | «الأول» subjects.id (21,22,62–65,59–61) — import mapping |
| sort_order | tinyint | |

**`sp_skills`** — filled by the import (and bank screens)

`id, sp_subject_id index, name, term enum(first,second,third) NULL, source_skill_id int NULL unique, sort_order, timestamps`.
Own table, not `skills`: `skills` is flat and has no `nafis` type; two nafis skills share a name («الهندسة والقياس» 4499/4511, SM §5.I-20) and need `source_skill_id` to stay distinct. Labels show the term when names collide.

**`sp_questions`** — one row per answerable item, 1:1 with `bank_questions`

| col | type | notes |
|---|---|---|
| id | bigint PK | |
| bank_question_id | bigint unique | → bank_questions.id |
| sp_subject_id | bigint | |
| sp_skill_id | bigint NULL | nafis passage sub-question: from `skills[i]` |
| passage_id | bigint NULL index | → passages.id (sub-questions only) |
| sub_index | smallint default 0 | order inside the passage |
| difficulty | enum(easy,medium,hard) | copied so pickers/tests need no QB join; `bank_questions.difficulty` gets 1/2/3 too |
| term | enum(first,second,both) NULL | qiyas/nafis; `third` from source → `both` (never picked otherwise) |
| week | tinyint NULL | qiyas 1–19 |
| question_order | int NULL | tahsili 1..N per subject |
| has_image | bool | |
| content_hash | char(32) index | dedupe key (§4.4) |
| merged_source_ids | json NULL | «الأول» ids folded into this row by dedupe (audit) |
| is_active | bool default 1 | soft-hide without deleting snapshots' sources |
| timestamps | | |

Indexes: `(sp_subject_id, sp_skill_id, is_active)`, `(sp_subject_id, question_order)`, `(sp_subject_id, difficulty)`.

**`sp_question_levels`** — `(sp_question_id, grade_level tinyint)` composite PK, index `grade_level`. A question can serve several grades (3 multi-level source rows, plus dedupe unions). Grade = ViewClass `classes.grade_level` ordinal 1..12 (`StandardGrades`).

Passages: `passages` row per source passage (`passage_code = 'AW-<source id>'`, `question_bank_id`, `passage_text`); `passage_questions(passage_id, question_id, sort_order)` for every sub-question; `bank_questions.passage_id` + `question_category='passage'` set (FM §1.2).

Existing-table touch: **index only** — `bank_questions (external_platform, external_id)` (migration `368002`, plain `Schema::table()->index()`, sqlite-safe). `bank_questions.external_id` = `"<source id>"` for an independent question, `"<source id>:<sub_index>"` for a passage sub-question.

### 2.2 School configuration

**`sp_settings`** — performance-level bands and program options

`id, school_id NULL (NULL = global row), program, level_bands json NULL, allow_school_edit bool default 0, grade_levels json NULL (global row only), weeks_count tinyint NULL (tahsili), updated_by, timestamps`; unique `(school_id, program)`; global rows written with `updateOrInsert(['school_id'=>null,...])` (MySQL unique allows several NULLs, so code guards it). **Reading never inserts** (fixes SM §9.6).

Resolution: school row bands ?? global row bands ?? `Programs::DEFAULT_BANDS`. Defaults (SM §1.4):

| program | ممتاز | جيد جداً | جيد | مقبول | ضعيف |
|---|---|---|---|---|---|
| tahsili | 95–100 | 85–94 | 75–84 | 65–74 | 0–64 |
| nafis | 85–100 | 75–84 | 65–74 | 50–64 | 0–49 |
| **qiyas (new)** | 90–100 | 80–89 | 70–79 | 60–69 | 0–59 |

Qiyas defaults = «الأول»'s `general` scale (the only scale it applies outside nafis/tahsili). Validation as `PerformanceLevelSettingsController::saveSchool`: 5 bands, min/max integers 0–100, no overlap, cover 0–100.

**`sp_teachers`** — «معلمو البرنامج» (replaces `teacher_section_subject` typing, SM §2.3)
`id, school_id, program, sp_subject_id NULL (NULL = every subject of the program), teacher_id, created_by, timestamps, deleted_at`; unique `(school_id, sp_subject_id, teacher_id)`.

**`sp_groups`** — «مجموعات الأداء» (not in «الأول»; defined here)
`id, school_id, program, name, teacher_id NULL, target_level NULL (one of the 5 level keys), notes NULL, created_by, timestamps, deleted_at`; index `(school_id, program)`.

**`sp_enrollments`** — «الطلاب المسجلون»
`id, school_id, program, student_id, group_id NULL, enrolled_by, enrolled_at, timestamps, deleted_at`; unique `(school_id, program, student_id)` (re-enrol = restore). A student is in at most one group per program.

### 2.3 Exams

**`sp_exams`**: `id, school_id, program, title, type enum(single,multi), expiry_hours int, expires_at datetime, max_exit_attempts tinyint NULL (NULL = anti-cheat off, as «الأول»), created_by, creator_role varchar(20) (admin|teacher — the «نوع الاختبار» filter), teacher_id NULL, timestamps, deleted_at`; index `(school_id, program, deleted_at)`. `expires_at` **is saved** (fixes tahsili SM §4.J-3).

**`sp_exam_targets`**: `id, exam_id, class_id NULL, group_id NULL` (exactly one set); index `exam_id`, `class_id`, `group_id`.

**`sp_exam_subjects`**: `id, exam_id, sp_subject_id, teacher_id NULL, duration_minutes, total_questions, order_index, term enum(first,second) NULL, weeks json NULL, question_grade_levels json NULL, easy_pct, medium_pct, hard_pct (tinyint NULL), range_start int NULL, range_end int NULL, timestamps`. Meaning of `total_questions` for nafis_reading = **number of passages** (as «الأول»; label says so, §8).

**`sp_exam_skill_shares`** (= skill distribution): `id, exam_subject_id, sp_skill_id, percentage tinyint`; unique `(exam_subject_id, sp_skill_id)`.

### 2.4 Attempts

**`sp_attempts`** (= results row)

| col | notes |
|---|---|
| id, school_id, program, exam_id, exam_subject_id, student_id | denormalised for report scoping |
| status | enum(in_progress, completed, timed_out, auto_ended) |
| started_at, deadline_at, ended_at, last_seen_at | server timer |
| items_count, answered_count, correct_count | |
| score_pct | decimal(5,2) NULL until finished |
| time_spent_seconds | `min(ended_at, deadline_at) − started_at` (fixes always-0 SM §3.I-6) |
| exit_count | anti-cheat counter |
| completed_event_at | set in the same UPDATE that closes the attempt; guards "event once" |
| timestamps | |

Unique `(exam_subject_id, student_id)`; indexes `(school_id, program, status)`, `(student_id, program)`.

**`sp_attempt_items`** (= snapshot + answer)

`id, attempt_id index, bank_question_id, sp_skill_id NULL index, difficulty, passage_id NULL, sub_index, order_index, stem text, image_path NULL, options json ([{text,image}] in source order), correct_index tinyint, answer_index tinyint NULL, is_correct bool NULL, answered_at NULL`.
Passage text is **not** copied per item; the take/review page reads `passages.passage_text` by `passage_id` (passages are long; edits after an attempt are rare and do not change scoring). Everything that affects a score or a report (skill, difficulty, correct index, answer) is snapshotted, so scoring and reports never read `bank_questions` (SM §1.3 pattern, and makes the attempt/report tests sqlite-only).

---

## 3. Programs, subjects, targeting (§ also requirements US-ADM-6)

`Support/Programs::PROGRAMS`:

| program | label | default target grades (`classes.grade_level`) | picker | bank grade filter |
|---|---|---|---|---|
| qiyas | «القدرات» (لفظي + كمي) | 10, 11, 12 (decision) | QiyasPicker | soft (§5.1) |
| tahsili | «التحصيلي» | 10, 11, 12 | TahsiliPicker | none (as «الأول») |
| nafis | «نافس» | 3, 6, 9 | NafisPicker | soft |

Target grades are overridable by super-admin in the global `sp_settings.grade_levels` (screen «الصفوف المستهدفة»).

**Known data mismatch (biggest risk, client question Q1):** the «الأول» qiyas bank is linked to grades 2–9 only (SM §7: primary 2–6, intermediate 1–3; 2 questions on first-secondary). With the decided 10–12 targeting, a hard grade filter would give every qiyas student an empty exam. Hence the soft filter: the exam builder picks the **question levels** per subject (`question_grade_levels`, label «مستوى الأسئلة»), shows the question count per level, defaults to the target classes' grades, and when those grades hold **fewer questions than requested** (not only zero — first-secondary has 2 كمي questions) defaults to "all levels" with the warning «أسئلة صفوف الفصول المستهدفة أقل من العدد المطلوب؛ سيتم السحب من كامل بنك المادة». The picker also drops the level filter as its last fallback (§5.1 step 4), so an exam never comes out short because of levels. Same for nafis علوم (no grade-3 content). Tahsili content is all grade 12 and is picked without a grade filter.

---

## 4. Import command (rawaniedu only)

### 4.1 Interface

```
php artisan special-programs:import-alawwal
    {--host=} {--port=3306} {--database=} {--username=} {--password=}   # default env ALAWWAL_DB_*
    {--images=}            # dir containing uploads/questions/… (default env ALAWWAL_IMAGES_PATH)
    {--program=*}          # qiyas|tahsili|nafis (default all)
    {--dry-run}            # read + normalise + dedupe + check images; write nothing; print the summary
    {--report=}            # JSON report path (default storage/logs/sp-import-<timestamp>.json)
```

The command builds a runtime connection `alawwal` with `config()->set(...)` — no edit to `config/database.php`. It reads with `DB::connection('alawwal')`, raw SQL (never through a model mutator, SM §8.2), using SM §8.6 query, ordered by `id`, chunked by 500.

### 4.2 Pipeline (per program, one transaction per source row)

1. **Banks + subjects.** For each `sp_subjects` row of the program: `firstOrCreate` owner bank (`name_ar` «بنك {subject} — منصة الأول», `school_id NULL`, `is_owner_bank 1`, `visibility public`, `status active`, `source/external_platform al_awwal`, `category_type special_program`, `allow_images 1`, `allow_passage_questions 1`), store its id on `sp_subjects.question_bank_id`.
2. **Skills.** Upsert `sp_skills` by `source_skill_id` from source `skills` where `subject_id` ∈ program subjects (qiyas 10, nafis 12, tahsili 0).
3. **Normalise each row** (`AlAwwalRowNormalizer`, pure, unit-tested):
   - JSON columns (`question_text`, `options`, `skills`): `while (is_string($v)) $v = json_decode($v, true)` with a 5-loop cap → error on failure (handles the 58 double-encoded nafis passages).
   - Stems: strip a leading `["` and trailing `"]` (row 18469), convert literal `\n` to newline, trim; keep HTML-like text as text (escaped on render).
   - Independent: stem = `question_text[0]`; options = `[{text}]`; `correct_index` = position of the single `is_correct:true` (error if ≠ 1 — verified 0 such rows).
   - Passage: `question_text[i]` ↔ `options[i]` ↔ (`skills[i]` nafis, else source `skill_id`) → N sub-items with `sub_index=i`; passage body from `passage_text`.
   - Difficulty easy/medium/hard → 1/2/3; term `third` → `both`.
4. **Levels** (`AlAwwalLevelMap`): source `classroom_question → classrooms(Name_Class, Grade_id) → grades.school_type_id`; stage 1 + «الأول..السادس» → 1..6, stage 2 + «الأول..الثالث» → 7..9, stage 3 + «الأول..الثالث» → 10..12 (ordinal word parse, not digit LIKE). Orphan classroom links (60 rows, SM §8.5) are skipped and counted. Union of all links → `sp_question_levels`.
5. **Image.** `--images/<question_image>` must exist → copy to `storage/app/public/special-programs/<program>/<basename with lower-case ext>`; `.jfif` → `.jpg`; skip copy when the target exists with the same size; `bank_questions.attachment_path` = that relative path, `question_content_type = 'mixed'` (or `image` when the stem is a generic «اختر…»), `is_full_image_question = 1` for image + generic stem (tahsili). Missing file → row imported without image + listed in the report (expected 0, SM §8.3).
6. **Dedupe** (§4.4) → canonical row or survivor update.
7. **Upsert.** Find `bank_questions` by (`question_bank_id`, `external_platform='al_awwal'`, `external_id`). Create or update the row; answers via `MapAnswerData` + `CreateQuestion::syncAnswerRows` (keeps `answer_data` JSON and `question_answers` in sync, FM §1.1); `status='approved'`, `source='al_awwal'`, `type='mcq'`, `points=1`. Upsert `sp_questions` + levels. Passages: upsert `passages` by (`question_bank_id`, `passage_code`) and `passage_questions`. **One** `activity_logs` row per run (not 8k).
8. **Report**: per program — rows read, items written (created/updated/unchanged), passages, dedupe groups/rows merged, images copied/skipped/missing, orphan links, decode failures, level histogram. Printed as a table; JSON to `--report`. Expected totals: qiyas 3,630 items before dedupe, nafis 1,714, tahsili 2,704 (SM §8.1).

### 4.3 Idempotency

Keys: bank by (category_type, name), skill by `source_skill_id`, question by `external_id`, passage by `passage_code`, image by target path. Re-run = all "unchanged". `--dry-run` runs steps 3–6 against an in-memory map and touches neither DB nor disk. The dedupe groups are deterministic (sorted by source id), so a re-run picks the same survivors.

### 4.4 Dedupe

Key = md5 of `sp_subject + skill + normalised stem(s) + options (text + correct flag, in order) + md5(image bytes) + normalised passage body`. Normalisation: trim, collapse whitespace, strip tashkeel and tatweel. Not the source `fingerprint` (it collides: tahsili 14 fingerprints for 2,704 rows, SM §8.4).
Survivor = **lowest source id** in the group. Merged rows add their grade levels to the survivor, widen `term` to `both` when terms differ, keep the lowest `week`, and append their ids to `merged_source_ids`. Expected **approximately** qiyas 327 rows / 149 groups, nafis 53 / 26, tahsili 0 (SM §8.4 counted without skill and image bytes in the key, so the real numbers can differ; the report is the truth). The report lists every group so the client can veto.

### 4.5 Getting the data onto rawaniedu (no student data shipped)

The full live dump holds every «الأول» table incl. students/parents. Ship a **slim extract** instead:

1. Locally: import `~/www/alawol/قاعدة البيانات/alawal-live-2026-11-19.sql.gz` into a scratch DB (or use `alawal_local`, same snapshot).
2. `mysqldump` only `subjects skills classrooms grades school_types` plus `questions --where="exam_type IN ('qiyas','tahsili','nafis')"` and `classroom_question --where="question_id IN (SELECT id FROM questions WHERE exam_type IN (…))"` (done via a temp table) → `sp-alawwal-extract.sql.gz` (a few MB).
3. Image list: `SELECT question_image FROM questions WHERE exam_type IN (…) AND question_image IS NOT NULL` → `rsync -a --files-from=list ~/www/alawal/ rawaniedu:~/sp-import/` (≈190 MB of 333 MB; only referenced files).
4. On rawaniedu: create DB `sp_alawwal_import`, load the extract, run `--dry-run`, compare with the expected totals, run for real, open the hub + one question of each program in the browser, then **drop `sp_alawwal_import` and delete `~/sp-import`**.

Runbook goes into `claudedocs/special-programs-import-runbook.md` in the deploy task.

---

## 5. Question picking (per student, at first open of a subject)

Common: runs inside `StartSubject` in a transaction after `INSERT … sp_attempts` (the unique `(exam_subject_id, student_id)` key makes a second tab/double-click reuse the same attempt instead of generating twice). Pool = active `sp_questions` of the subject. Random = `inRandomOrder()` on a pool already narrowed by indexes (≤ ~2k rows). Passage members are always picked as a **whole passage** (all sub-questions, contiguous order). The resulting item list is shuffled by group (passage = one group), then `order_index` 1..n assigned.

`Apportion::bySkill(shares, N)` (pure, unit-tested): `count_k = round(p_k / Σp × N)`, each ≥ 1 while Σcount ≤ N; the difference `N − Σcount` is added to / taken from the largest buckets one by one (never below 1, never negative) — fixes «الأول»'s "add the rest to the first skill" going negative when skills outnumber questions.

### 5.1 Qiyas (`QiyasPicker`, SM §3.D)

1. Shares = `sp_exam_skill_shares`; empty → every skill of the subject with ≥1 pool question, equal split.
2. Base filter: subject, `term IN (subject.term, 'both')` (fixes «both never picked», SM §3.I-10), `week IN weeks` when weeks set, level filter `EXISTS sp_question_levels.grade_level IN question_grade_levels` when that list is set.
3. Per skill: take `count_k` random items; a passage (verbal «استيعاب المقروء») contributes all its sub-questions and counts them toward `count_k` (may overshoot by ≤2; fixes the 176 broken passage snapshots, SM §3.I-4).
4. Shortfall per skill, in order: drop the week filter → top up from any skill of the subject with the same term + level filter → drop the level filter (same term) → drop the term filter. (The source's same-grade fallback is meaningless here — levels are global — and is replaced by the level drop.)
5. Difficulty is not used (as «الأول»).

### 5.2 Nafis (`NafisPicker`, SM §5.D)

- `picks_passages` subject (reading): `total_questions` = number of passages. Pick N random passages (level filter) and expand every sub-question; each sub-item carries its own skill from `skills[i]`. Skill shares are ignored for selection (as «الأول»). None found → the start page says «لا توجد قطع قراءة متاحة لصفك الدراسي» instead of a 500.
- Other subjects: qiyas steps 1, 3 and 4 without term/week filters; skill match is exact (`sp_skill_id =`, fixes the `LIKE '%449%'` substring bug, SM §5.I-10); top-up from passages takes whole passages, never sub_index 0 only.
- No hard-coded skill ids (SM §5.I-8): the empty-shares default comes from the pool.

### 5.3 Tahsili (`TahsiliPicker`, SM §4.D)

1. `easy = round(e% × N)`, `medium = round(m% × N)`, `hard = N − easy − medium` (clamped ≥ 0).
2. Pool: subject, `question_order BETWEEN range_start AND range_end` when set (range is in **question_order units**, SM §4.J-1). No grade filter.
3. Draw each bucket at random; fill the shortfall from any difficulty in the range; still short → the attempt simply has fewer items (`items_count` = generated, so the % is correct). The builder warns up front: «عدد الأسئلة في النطاق ({n}) أقل من العدد المطلوب ({N})».
4. Shuffle.

Builder helpers (real, not the broken ones of SM §4.J-1): `GET …/bank-stats` returns per subject the max `question_order`, the count in a range, counts per difficulty and per skill, and per level.

---

## 6. Eligibility, enrolment, groups

- **Eligible classes** (`ProgramAudience::classes(school, program)`): `classes` of the school's current academic year with `grade_level ∈ program grades`.
- **Readiness alerts** (pattern of `PromotionPlanner::readiness`, `admin/schools/promotion/preview.blade.php:59-75`), shown on the program page and the enrol screen:
  - «توجد فصول بدون رقم صف ({classes})، لن تظهر ضمن الفصول المستهدفة حتى يُحدَّد الصف» — classes with NULL/0 `grade_level`, linked to the classes edit screen.
  - «لا توجد فصول للصفوف المستهدفة في هذه المدرسة» — when none match.
  - Fallback: «عرض كل الفصول» toggle lets the admin enrol from / target any class; such rows carry the badge «خارج الصفوف المستهدفة». Never a silently empty list.
- **Enrol** («إضافة طالب لبرنامج»): pick class(es) → students (search by name / national id) → optional group → save. «إضافة كل طلاب الفصل» bulk. Students already enrolled are shown checked/disabled. Remove = soft delete (attempt history stays).
- **Groups** («مجموعات الأداء»): manual named groups per program (default answer to client question Q2) with «المعلم المسؤول» and optional «مستوى الأداء المستهدف». The group student picker can filter enrolled students by their **current level** (latest program %) so the admin can build level-based groups by hand. Move/remove students between groups.
- **Exam audience** = enrolled (not soft-deleted) students of the exam's school and program whose class (`User::enrolledClassIds()`) ∩ target classes ≠ ∅ **or** whose `group_id` ∈ target groups.

---

## 7. Attempt lifecycle (student)

| step | server rule |
|---|---|
| List | exams of the student's enrolled programs where the student is in the audience; badges «مكتمل» / «قيد التنفيذ» / «انتهت الصلاحية» / «متاح» (SM §5.E-1). |
| Show exam | subject cards by `order_index`; a subject is startable only when every lower `order_index` subject is completed — **checked on the server**, not only in the view (msg «يجب إكمال اختبار {subject} أولاً»). |
| Start subject | refuse when the student is not enrolled or not in the exam audience (`ExamRepository::isInAudience`), when the exam is soft-deleted, or `expires_at < now()` **and** no attempt exists yet (expiry checked server-side, fixes SM §3.I-6/§5.I-12); refuse when the attempt is closed («لقد أنهيت هذه المادة بالفعل»). Otherwise `firstOrCreate` attempt (`deadline_at = now() + duration`), generate items if none. |
| Take | render remaining = `deadline_at − now()`; reload keeps the clock. One question (or one passage group) per screen, numbered dots, «السؤال السابق» / «السؤال التالي», warnings at 5 and 1 min, zoomable image. Options rendered without any correctness flag. |
| Autosave | `POST answer {item_id, option}`: owner + `in_progress` + `now() ≤ deadline_at + 5 s grace` + item ∈ attempt → set `answer_index`, `is_correct`, `answered_at`; response `{saved, remaining_seconds}` only (never `is_correct`, fixes SM §4.E leak). Reload restores selections from the items. |
| Heartbeat | `GET heartbeat` → remaining seconds; updates `last_seen_at`; finalizes if overdue. |
| Exit / blur | `POST exit` increments `exit_count`; when `max_exit_attempts` is set and reached → `FinishAttempt(auto_ended)` (pattern of `StudentExamController::logExit`). |
| Finish | `FinishAttempt`: one conditional `UPDATE … WHERE id=? AND status='in_progress'` sets status (`completed`, or `timed_out` when now > deadline, or `auto_ended`), `ended_at`, counts, `score_pct`, `time_spent_seconds`, `completed_event_at`; only if that UPDATE affected 1 row → dispatch `ProgramAttemptCompleted` **after commit**. |
| Overdue | any read of an `in_progress` attempt with `now() > deadline_at + 5 s` calls `FinishAttempt(timed_out)`; plus `special-programs:finalize-overdue` scheduled every 5 minutes (`routes/console.php`). |
| Results | subject result: score, %, level badge, correct/wrong, time, per-skill (qiyas/nafis) or per-difficulty (tahsili) table, per-question review with the correct option — shown only after the attempt is closed. Final result: exam % (D6) + subject tiles. |

---

## 8. Exam builder (staff)

Form «إنشاء اختبار {قدرات|تحصيلي|نافس}» — fields and labels from SM §3.D / §4.D / §5.D:

- «عنوان الاختبار», «نوع الاختبار» (📘 مادة واحدة / 📚 عدة مواد), «الفصول المستهدفة» (eligible classes as checkboxes, grouped by grade) and/or «مجموعات الأداء المستهدفة», «مدة صلاحية الاختبار (ساعة)» (admin 1–720, teacher 1–168; default qiyas/nafis 24, tahsili 48), «الحد الأقصى لمحاولات الخروج» (optional).
- Repeatable subject row: «المادة», «المعلم», «المدة (دقيقة)», «عدد الأسئلة» (nafis reading: «عدد القطع»), «الترتيب», then per program:
  - qiyas: «الفصل الدراسي» (الأول/الثاني), «الأسابيع الدراسية» (checkboxes «الأسبوع 1..19», at least one), «مستوى الأسئلة», «توزيع الأسئلة حسب المهارات» with «المجموع» / «المتبقي» badges and live counts per skill;
  - nafis: «مستوى الأسئلة», skill distribution (non-reading);
  - tahsili: «سهل %», «متوسط %», «صعب %» (sum = 100), «نطاق الأسئلة» («بداية النطاق», «نهاية النطاق») with available count, and the informational card «إجمالي الأسئلة في بنك الأسئلة» / «عدد الأسابيع» / «عدد الأسئلة الأسبوعي» = ceil(bank / weeks) (SM §4.D).
- Validation: skill % sum 1–100 (as «الأول», proportional); tahsili % sum = 100; range start ≤ end; duration admin 1–300 / teacher 15–180; questions admin 1–400 / teacher 1–100; subject has a bank with ≥1 question. All checks run **before** any insert (fixes teacher over-100 insert-then-rollback, SM §3.D).
- Teacher: `type=single`, one subject from his `sp_teachers` assignment; targets ⊆ his classes (`ResolvesTeacherStudents::teachingClassIds`) ∪ groups where he is «المعلم المسؤول».
- Delete: soft delete; attempts kept; excluded from lists and reports (fixes FK-off orphan deletes, SM §3.I-7). Creator or school-admin.

---

## 9. Reports

Common filters (SM §4.H / §5.G): «المدرسة» (super-admin), «الصف», «الفصل», «مجموعة الأداء», «المادة», «نوع الاختبار» (إدارة / معلم), «من تاريخ», «إلى تاريخ» (on `ended_at`, app timezone), «الاختبار». Only closed attempts (`completed|timed_out|auto_ended`) of non-deleted exams. School scope in `ReportRepository`; teacher scope = exams he created or exam subjects where he is the teacher.

Staff report page «تقارير اختبارات {القدرات|التحصيلي|نافس}» — sections (same titles as «الأول», qiyas gets the nafis set):

| section | rows | columns |
|---|---|---|
| «نتائج الطلاب في اختبارات …» | attempt | # · اسم الطالب · رقم الهوية · الفصل · المجموعة · المادة · الاختبار · الدرجة (correct/items) · النسبة · المستوى · الوقت · التاريخ · نوع الاختبار |
| «أداء الطلاب حسب المادة» | subject | المادة · عدد الطلاب · عدد المحاولات · متوسط النسبة · أعلى نسبة · أدنى نسبة · متوسط الوقت |
| «أداء الطلاب حسب المهارات» (qiyas, nafis) | skill | # · المهارة · المادة · عدد الطلاب · عدد الإجابات · الإجابات الصحيحة · نسبة النجاح · التقييم — grouped by `sp_attempt_items.sp_skill_id`, so each answer credits **only its own skill** (fixes SM §5.I-11). التقييم: ممتاز ≥80, جيد جداً ≥60, جيد ≥50, else يحتاج تحسين (nafis rule). |
| «الأداء حسب مستوى الصعوبة» (tahsili) | difficulty | سهل / متوسط / صعب: answers, correct, % (fixes always-0/0, SM §4.E) |
| «إحصائيات اختبارات …» | exam | الاختبار · نوع الاختبار · المواد · الطلاب المتقدمين · الطلاب المستهدفين (audience size) · نسبة المشاركة · متوسط الدرجة · تاريخ الإنشاء |
| «توزيع الدرجات» | class × subject | bands per program — tahsili 95-100/90-94/85-89/80-84/70-79/60-69/<60; nafis & qiyas 90-100/80-89/70-79/60-69/50-59/<50 |
| «أداء الفصول» | class × subject | الفصل · المادة · عدد الطلاب · متوسط النسبة · المتفوقين (≥80) · الضعاف (<60) · متوسط الوقت |

- Level per row = `PerformanceLevels::for(pct, bands(school, program))` — one scale per program everywhere (fixes the two-scale mix, SM §4.J-16). A strip «مستويات الأداء المعتمدة» shows the bands used.
- **Excel**: `ExportsReports::exportExcel` (real .xlsx, RTL) — one button per section (`?section=students|subjects|skills|difficulty|exams|distribution|classes`). Print = `exportPdf` (mPDF, xbriyaz).
- Charts: Chart.js (same CDN tag as `dashboard.blade.php:636`): bar of level distribution on the report page; line of score trend on the student report.

**«تقرير أداء الطالب»** (the card's acceptance; staff, teacher of the student, the student, the parent): header (name, class, group, program, current level badge = level of the latest-30-days mean %), trend chart of every closed attempt, per-subject table (attempts, last %, best %, mean %), per-skill mastery (qiyas/nafis) or per-difficulty (tahsili) across all attempts, attempts history with links to each subject result, PDF export.

Teacher screens (port of `ReportNafisExamController`, working versions): «تقاريري» = his exams with total / completed / pending / mean %; exam report = avg/max/min, top 10 / weakest 10, level distribution, per-subject; skills analysis per student (correct per skill). Excel via the same trait.

Performance-level settings («إعدادات مستويات الأداء», per program tab): super-admin edits the global bands and «السماح للمدرسة بالتعديل» per school; a school admin (or job title holding `special_programs.settings`) edits his school bands when allowed, otherwise read-only (SM §1.4). «إعدادات التحصيلي»: weeks count global/per school (informational only, as «الأول»).

---

## 10. Event contract for #371 (points)

```php
namespace App\Modules\SpecialPrograms\Events;

final class ProgramAttemptCompleted   // use Dispatchable; implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly int $attemptId,
        public readonly int $studentId,
        public readonly int $schoolId,
        public readonly string $program,        // 'qiyas' | 'tahsili' | 'nafis'
        public readonly int $examId,
        public readonly int $examSubjectId,
        public readonly string $subjectCode,    // sp_subjects.code, e.g. 'qiyas_verbal'
        public readonly int $correctCount,
        public readonly int $totalCount,        // items in the attempt (passage sub-questions count 1 each)
        public readonly float $scorePct,        // 0..100, 2 decimals
        public readonly string $status,         // 'completed' | 'timed_out' | 'auto_ended'
        public readonly bool $examCompleted,    // true when this closes the student's last subject of the exam
        public readonly \DateTimeImmutable $completedAt,
    ) {}
}
```

Guarantees: fired **exactly once per attempt** (guarded by the conditional status UPDATE), **after the DB commit**, for manual finish, timer expiry (on read or by the sweep) and anti-cheat auto-end. Not fired for a re-enrolment or an exam deletion. Payload is scalars only (safe for queued listeners). #371 registers its own listener; this module ships none and must not reference #371 code.

---

## 11. Roles, permissions, sidebar

### 11.1 Role mapping («الأول» → ViewClass)

| «الأول» role (SM §2.2) | ViewClass | Gets |
|---|---|---|
| مدير النظام | `super-admin` | everything, all schools via the header scope switcher; global bands, target grades, program banks |
| مشرف تعليمي / وكيل مدرسة / وكيل مدرسة ثان / مدير القسم | `school-admin` (+ job titles `supervisor`, `vice`, `school_manager`) | enrol, groups, teachers, exams, reports, school bands (when allowed) — own school only |
| معلم (with a program subject) | `teacher` + `sp_teachers` row | his program exams (single subject, his classes/groups), their results, reports and his students' performance reports |
| «قدرات لفظية» / «قدرات كمية» / مدخل أسئلة | any staff user granted `special_programs.questions` (role or job title) + an `sp_teachers` row limiting the subject | question bank CRUD for that subject only (verbal-only / quant-only) |
| طالب | `student` | his programs, exams, results, «تقرير أدائي» |
| ولي أمر | `parent` | read-only «البرامج التعليمية» report per child (new; «الأول» had none) |

### 11.2 Permission group

`PermissionCatalog::MODULES['special_programs'] = ['label' => 'البرامج التعليمية الخاصة', 'actions' => ['view','manage','exams','questions','reports','settings']]`.

| action | meaning | seeded to `permission_role` |
|---|---|---|
| view | open the hub / program pages | school-admin, teacher |
| manage | enrol, groups, assign teachers | school-admin |
| exams | create / delete program exams, view results | school-admin, teacher |
| questions | program bank CRUD (platform-wide content) | — (super-admin implicit; grant per user via job title) |
| reports | reports + exports | school-admin, teacher |
| settings | school performance bands (when allowed) | school-admin |

Migration `368009` (pattern `2026_11_16_372001_seed_counseling_permissions.php` + `2026_07_13_100000_seed_baseline_role_permissions.php`): `updateOrInsert` permissions; `permission_role` rows above (non-view actions fail closed in `canDo`, FM §4); `job_title_permissions` for `supervisor`, `vice`, `school_manager` → view/manage/exams/reports (scope `school`). `down()` deletes from both pivots and `permissions`.

### 11.3 Routes and middleware

| file | prefix / name | middleware |
|---|---|---|
| `Routes/staff-*.php` | `manage/special-programs` · `manage.special-programs.` | `auth`, `role:super-admin,school-admin,teacher`, then per route `permission:special_programs.<action>`; global settings + target grades additionally `role:super-admin` |
| `Routes/student*.php` | `student/programs` · `student.programs.` | `auth`, `role:student` |
| `Routes/parent.php` | `parent/programs` · `parent.programs.` | `auth`, `role:parent` + `children()` guard (`ParentController::childExams` pattern) |

`{program}` is always constrained `->whereIn('program', ['qiyas','tahsili','nafis'])` so it never shadows literal segments (`settings`, `teacher-reports`) whatever order the globbed files load in. **Super-admin-only GET routes take no path parameters** (program comes as `?program=`): `RbacSuperAdminRoutesTest` substitutes `1` for every param, and a failed `whereIn` would give 404 instead of the expected 403.

Pinned routes (prefix `manage/special-programs` = `MSP`, name prefix `manage.special-programs.` = `msp.`):

| file (task) | method · URI | name |
|---|---|---|
| staff-setup (T3.1) | GET `MSP` | `msp.index` |
| | GET `MSP/{program}` | `msp.program` |
| | GET `MSP/{program}/enrollments` · GET `…/create` · POST `…` · DELETE `…/{id}` | `msp.enrollments.index/create/store/destroy` |
| staff-groups (T3.2) | GET `MSP/{program}/groups` · GET `…/create` · POST · GET `…/{id}` · GET `…/{id}/edit` · PUT `…/{id}` · DELETE `…/{id}` | `msp.groups.index/create/store/show/edit/update/destroy` |
| | POST `…/groups/{id}/members` · DELETE `…/groups/{id}/members/{studentId}` | `msp.groups.members.store/destroy` |
| | GET `MSP/{program}/teachers` · POST · DELETE `…/{id}` | `msp.teachers.index/store/destroy` |
| staff-bank (T3.3) | GET `MSP/{program}/bank` · GET `…/bank/passages` · GET `…/bank/create` · GET `…/bank/passages/create` · POST `…/bank` · POST `…/bank/passages` · GET `…/bank/{id}` · GET `…/bank/{id}/edit` · PUT `…/bank/{id}` · DELETE `…/bank/{id}` · GET `…/bank/passages/{id}/edit` · PUT `…/bank/passages/{id}` · DELETE `…/bank/passages/{id}` | `msp.bank.index/passages/create/passages.create/store/passages.store/show/edit/update/destroy/passages.edit/passages.update/passages.destroy` |
| staff-settings (T3.4) | GET `MSP/{program}/settings` · PUT `…/settings` | `msp.settings.index/update` |
| | GET `MSP/settings/global` · PUT `MSP/settings/global` (`role:super-admin`, `?program=`) | `msp.settings.global/global.update` |
| staff-exams (T4.2) | GET `MSP/{program}/exams` · GET `…/create` · POST · GET `…/{id}` · DELETE `…/{id}` · GET `…/{id}/export` · GET `MSP/{program}/bank-stats` | `msp.exams.index/create/store/show/destroy/export`, `msp.bank-stats` |
| staff-reports (T6.2) | GET `MSP/{program}/reports` · GET `…/reports/export` (`?section=`) · GET `…/reports/print` · GET `MSP/{program}/students/{studentId}/report` · GET `…/report/pdf` | `msp.reports.index/export/print`, `msp.student-report`, `msp.student-report.pdf` |
| staff-teacher (T7.1) | GET `MSP/teacher-reports` · GET `MSP/teacher-reports/exams/{id}` · GET `…/exams/{id}/export` | `msp.teacher-reports.index/exam/export` |
| student (T5.2) | GET `student/programs` · GET `student/programs/exams/{id}` · POST `…/exams/{examId}/subjects/{subjectId}/start` · GET `student/programs/attempts/{id}` · POST `…/attempts/{id}/answer` · GET `…/{id}/heartbeat` · POST `…/{id}/exit` · POST `…/{id}/finish` · GET `…/{id}/result` · GET `student/programs/exams/{id}/final` | `student.programs.index/exam/start/take/answer/heartbeat/exit/finish/result/final` |
| student-report (T7.2) | GET `student/programs/report/{program}` · GET `…/pdf` | `student.programs.report/report.pdf` |
| parent (T7.2) | GET `parent/programs` · GET `parent/programs/{childId}/{program}` · GET `…/pdf` | `parent.programs.index/report/report.pdf` |

The `role:` on the staff group is required: `*.view` default-allows users without a job-title matrix, so students/parents would otherwise pass `permission:special_programs.view`. Ids use `{id}` + `whereNumber`, resolved in repositories scoped by school (404 for another school's id, never a leak). CheckRole already runs before `SubstituteBindings` (`bootstrap/app.php`), so super-admin-only GET routes 403 for others and `RbacSuperAdminRoutesTest` stays green.

### 11.4 Sidebar (`resources/views/components/sidebar.blade.php`, order unchanged)

- `:609` «البرامج التعليمية الخاصة» → `Route::has('manage.special-programs.index') ? route(...) : route('programs.coming-soon')`, gated by `$canViewSpecialPrograms = !$sidebarUser || !$isStaff || canViewModule('special_programs')`.
- `:543` «الأول بلس» → **recommendation: same hub** (`manage.special-programs.index`). The ported programs are the «الأول» content, the owner tier is already labelled «منصة الأول» / «الأول بلس», and the card names «الأول بلس» as its example. «أنا والقدرات» (`:544`) and «القراءة السريعة» (`:545`) stay coming-soon (other platforms).
- Teacher block (`:1049+`): «البرامج الخاصة» → hub.
- Student block (`:1160` group): «البرامج التعليمية» → `student.programs.index`.
- Parent block (`:1270+`): «البرامج التعليمية» → `parent.programs.index`.
- These three links are **query-free** (`Route::has()` only; the page itself shows «لست مسجلاً في أي برنامج» / «لم يتم تعيينك لأي برنامج»). A sidebar `exists()` on `sp_*` tables would break every existing test that renders a layout page without the `368*` migrations, and adds a query to every page.
- `lang/en/shell.php`: add the missing `section_academic`, `nav_special_programs`, `nav_alawwal` keys (FM §7 i18n debt) plus the new nav keys.

---

## 12. Rollout

1. Migrations `368000–368009` are additive (new tables, one index, permission seeds, 9 `sp_subjects` rows). Run on **both** servers via the normal deploy (commit → push → SSH → `git pull` → `migrate --force` → `view:cache`), plus `storage:link` check.
2. The bank import runs on **rawaniedu.com only** (§4.5). viewclass.newaves-systems.com keeps empty banks: the hub shows «لم يتم استيراد بنك الأسئلة لهذا البرنامج بعد», the exam builder disables a subject with 0 questions («لا توجد أسئلة في بنك هذه المادة»), and nothing errors.
3. Demo data (sample enrolment + one qiyas exam for the QA walkthrough) is created **by hand on rawaniedu** through the UI, never seeded by migration.
4. `routes/console.php` gains `Schedule::command('special-programs:finalize-overdue')->everyFiveMinutes()`; confirm the cron runs `schedule:run` on both boxes (lazy finalization covers a missing cron).
5. Local-first: run the import locally from `alawal_local` into the local `viewclass` DB, walk the acceptance flow in a browser, then deploy.

---

## 13. Deliberate deviations from «الأول»

| «الأول» behaviour (SM ref) | Port |
|---|---|
| Admin exam/report routes without auth (§9.9) | every route behind `auth` + role + permission |
| Client-only timer, resets on reload (§3.I-6) | server `deadline_at`, rejected late answers, overdue finalize |
| Tahsili `expires_at` never saved (§4.J-3) | saved; start blocked after expiry |
| Qiyas passage snapshot = first sub-question, answer 0 (§3.I-4) | whole passage, per-sub-question key |
| Nafis/qiyas staff skill report credits every skill (§5.I-11) | per-item skill |
| Tahsili difficulty analysis always 0/0 (§4.E) | difficulty snapshotted per item |
| Correctness returned during tahsili exam (§4.E) | never returned before finish |
| `term='both'` never picked (§3.I-10) | `term IN (x,'both')` |
| Skill match `LIKE '%id%'` (§5.I-10) | exact id |
| Subject-name / class-name / hard-coded id detection (§9.3-4) | `picks_passages` flag, `grade_level`, pool-derived defaults |
| Two score conventions; multi-exam = mean of % for qiyas/nafis (§6) | one convention: Σcorrect / Σitems |
| Two level scales in tahsili screens (§4.J-16) | one per program, configurable |
| Reading settings inserts rows (§9.6) | read-only resolution |
| UTC timestamps (§9.2) | app timezone Asia/Riyadh |
| Exam delete with FK checks off, orphans (§3.I-7) | soft delete, attempts kept |
| HTML-as-.xls export (§4.H) | real .xlsx via `ExportsReports` |
| Qiyas: no reports (§3.G) | full report set + student performance report |
| No performance groups, no parent view | «مجموعات الأداء» + parent report (card scope) |
| (brief) legacy `pushToGradeReport` | **skipped**: grade reports are class × subject × term (FM §5) while program exams span classes/groups with no school subject; there is no column to push into. The `ProgramAttemptCompleted` event is the integration point if the client later wants it. |
| First-skill rounding remainder can go negative | `Apportion` clamps |

---

## 14. Tests

sqlite :memory: with per-class migration lists (`CounselingAccessTest::refreshDatabase` pattern). `tests/TestCase::setUp` runs `RoleSeeder` **after** the migrations and it `sync()`s `permission_role`, wiping the `368009` grants; the `LoadsSpMigrations` trait therefore re-grants `special_programs.*` to school-admin/teacher in `setUp`, and T1.3 adds the group to `RoleSeeder` so fresh installs (rawaniedu was fresh-seeded) keep the grants. Tests that need a same-wave implementation bind a fake of its contract. The QB migrations `2026_05_18_120063` and `2026_05_25_120003` run unguarded MySQL `ALTER … MODIFY` and cannot load in sqlite, so **no test needs `bank_questions`**: pickers read `sp_questions` (+levels), attempts and reports read only `sp_*` tables, and the importer's logic is tested as pure classes.

| test | covers |
|---|---|
| `Unit/SpecialPrograms/AlAwwalRowNormalizerTest` | double-encoded nafis passage, stray `["`, parallel arrays → sub-items with per-index skill, correct index, `third`→`both`, error on 0/2 correct |
| `Unit/SpecialPrograms/AlAwwalLevelMapTest` | (stage, «الصف الثالث») → 3/9/12; unknown → null |
| `Unit/SpecialPrograms/DedupeKeyTest` | tashkeel/whitespace-insensitive; image hash part of key; survivor = lowest id; level union |
| `Unit/SpecialPrograms/ApportionTest` | sums to N; ≥1 each; more skills than N; Σp < 100 |
| `Unit/SpecialPrograms/PerformanceLevelsTest` | defaults per program, school override, boundaries 94.99/95 |
| `Feature/SpecialPrograms/PickersTest` | qiyas term/both/weeks fallback/whole passage/level filter + level drop when target grades hold fewer than N (e.g. grade 10 with 2 questions → full N); nafis reading N passages; tahsili buckets + range + shortfall → items_count |
| `Feature/SpecialPrograms/AttemptLifecycleTest` | single generation on double start; reload keeps deadline; late answer rejected; answer response has no `is_correct`; finish scores; overdue → timed_out; sequential subject lock; expiry blocks start; event fired once (`Event::fake`) with payload |
| `Feature/SpecialPrograms/AccessTest` | student/parent 403 on staff routes; school A cannot see school B exam/report (404); teacher sees only his exams; parent only his child; teacher cannot target foreign class |
| `Feature/SpecialPrograms/ReportsTest` | skill rows credit only their own skill; level per program bands; date filter; Excel headers |
| `Feature/SpecialPrograms/EnrollmentTest` | enrol/bulk/re-enrol restore; audience = enrolled ∩ (class ∪ group); readiness alerts for unnumbered classes |
| existing `RbacSuperAdminRoutesTest` | must stay green |

Manual (local MySQL, not phpunit): full import from `alawal_local` → totals match §4.2 step 8; re-run all unchanged; browser walkthrough of the acceptance flow.

## Decision update 2026-11-19 (overrides §targeting where it says قدرات/تحصيلي = 10–12)
The 10–12 rule was our assumption, not the client's. The client asked for an exact port of «الأول». Grade targeting therefore follows the imported Al-Awwal question↔class links (qiyas: الصف الأول…السادس as linked in Al-Awwal, nafis: الثالث + السادس, tahsili: الثالث of its stage), mapped through the (stage + class name) → ViewClass grade mapping. No hard 10–12 filter and no "fall back to whole subject bank" warning path for qiyas; the fallback stays only for schools whose grades are unnumbered.
