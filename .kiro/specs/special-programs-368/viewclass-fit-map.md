# ViewClass fit map — card #368 «البرامج التعليمية الخاصة» (قدرات · تحصيلي · نافس)

Read-only analysis of worktree `feature/special-programs-368` @ `8e0d28af` (off origin/main), 2026-11-19.
Local DB `viewclass` matches this worktree's schema: every migration in `database/migrations` is applied
except `2026_11_17_120000_sync_class_gender_from_school` (a data-only sync, no schema change). Column
lists below come from `SHOW COLUMNS`; the migration file is named so a reader can find the source.

The Al-Awwal source codebase is **not** analysed here. Section 8 lists what to confirm against it.

---

## 0. TL;DR

| Question | Answer |
|---|---|
| Where do the ~7,300 questions go? | Into the **QuestionBankCore** model, which shares its physical tables with the legacy module (`question_banks`, `bank_questions`, `question_answers`, `passages`, `passage_questions`, `skills`). Use **owner-tier banks** (`is_owner_bank=1`, `school_id=NULL`, `source='al_awwal'`, `external_platform='al_awwal'`) with **one bank per program section**, and set `bank_questions.external_id` to the Al-Awwal question id as the idempotency key. |
| Can the Excel importer handle them? | **No.** The importer reads only the `Questions` sheet. It does not handle passages or ZIP images, and it drops option images (an image-only option becomes its URL as text). Image URLs are stored verbatim. All lookups are by name in school scope. Use an **artisan migration command** that calls `CreateQuestion` / `MapAnswerData` and the passage-attach path directly. |
| Which exam engine do students use today? | Only the **legacy** one (`exams` / `student_exams` / `student_answers`). It is built for one class, one teacher, one subject and one year. The take page shows no images, and only text is compared when auto-grading. `qb_exams` has targets and snapshots but **no attempt tables**, so it is not wired to students. |
| Exam engine for programs | A new **attempt layer**, built on the `qb_exams` shape or on program-owned tables, that renders images and passages. Reuse the anti-cheat/heartbeat and the `pushToGradeReport` patterns from the legacy engine. |
| Who is in a program? | Nothing exists yet. Student ↔ class links come from `users.class_room_id` plus the `class_student` pivot (`User::enrolledClassIds()`). There is **no "performance group" concept** anywhere in the codebase. |
| Grade targeting (نافس 3/6 ابتدائي + 3 متوسط; قدرات/تحصيلي ثانوي) | `classes.grade_level` (integer 1..12) is the reliable key. `sections.grade_number` is NULL locally, and the 11-17 sprint note says live schools are not numbered yet. |
| Sidebar | `sidebar.blade.php:609`, the «البرامج التعليمية الخاصة» item (`shell.nav_special_programs`) inside «الشؤون التعليمية والخطط». Swap its href only. The order is frozen (sprint spec cross-cutting rule). |
| Permissions | Add a group to `PermissionCatalog::MODULES` (`app/Modules/Users/Support/PermissionCatalog.php:19`; it **moved out of** `JobTitlePermissionsController`), plus a seed migration modelled on `2026_11_16_372001_seed_counseling_permissions.php`. |

---

## 1. Question bank today

### 1.1 Two modules, one set of tables

| | Legacy `app/Modules/QuestionBanks` | Core `app/Modules/QuestionBankCore` (QB rebuild #248–#258) |
|---|---|---|
| Routes | `routes/web.php:514-545` (writes, role super-admin/school-admin) and `:815-821` (reads, + teacher). URL `admin/question-banks/*`, names `admin.question-banks.*` | `app/Modules/QuestionBankCore/Routes/web.php` (required at `routes/web.php:1309`). URL `admin/qb/*`, names `admin.qb.*` |
| Scope | Bank CRUD, three-tier index (عام / خاص / منصة الأول), copy / promote / access requests, per-bank question CRUD, legacy Excel import (`option_a..d` columns) | Scope selector, question CRUD with normalised answers, passages, review workflow, new Excel import, skills / standards / compounds / weeks, `qb_exams` |
| Models | `App\Models\QuestionBank`, `App\Models\BankQuestion` (root models, shared) | Same two models, plus `Modules/QuestionBankCore/Models/{QuestionAnswer, Passage, Skill, Standard, Compound, QbExam*, ...}` |
| Nav | Sidebar `:618` «بنوك الأسئلة» → `admin.question-banks.index`; teacher block → same | Sidebar links only skills (`:621`), weeks, standards and compounds (`:603-606`). **No nav entry was found for `admin.qb.questions.*`, `admin.qb.import.*`, `admin.qb.passages.*` or `admin.qb.exams.*`.** A grep of `resources/`, `app/Http` and `app/Modules` outside `views/admin/qb` and the module itself found no link. |

The core writes **both** representations of an answer: `bank_questions.answer_data` JSON (legacy readers) and normalised `question_answers` rows (`MapAnswerData.php:1-30`). Either module can read a question the other wrote.

### 1.2 Tables and columns

**`question_banks`** (create `2026_04_28_110001`; extended by `2026_05_18_120062`, `2026_05_25_120002_qb_extend_question_banks_ana_qudurat_fields`, `2026_06_15_500004_qbcore_extend_question_banks`, `2026_08_05_120000_add_is_owner_bank_to_question_banks`)

`id, school_id NULL, subject_id, semester_id, bank_type enum(public,private), is_owner_bank, requires_approval, allow_excel_import, allow_images, allow_passage_questions, allow_tahsili_questions, name_ar, name_en, description, visibility(public|private), status(active|inactive|under_review|archived), source, grade_level tinyint, category_type varchar(32), is_ana_qudurat_linkable, exportable, metadata json, external_id, external_platform, link_status, sync_status, last_sync_at, is_library, created_by, imported_by, timestamps, deleted_at`

- `App\Models\QuestionBank` (`app/Models/QuestionBank.php`):
  - `$fillable` (lines 15-37) **omits** `bank_type`, `requires_approval`, `allow_*`, `subject_id` and `semester_id`. These columns still exist and are read in places: `requires_approval` is read in `PassageController.php:100,252`.
  - Tier logic: `getTierAttribute()` (`:57-64`) says owner wins over visibility.
  - Constants (`:66-81`): `SOURCE_AL_AWWAL='al_awwal'`, `SOURCE_ANA_QUDURAT`, and `CATEGORY_{SCHOOL, QUDURAT, VERBAL, QUANTITATIVE, SPEED_READING}`. There is **no tahsili and no nafis category**.
- Owner tier «منصة الأول» / «الأول بلس»:
  - Create (super-admin only): `QuestionBankController.php:120-127` and `:163-167`. When `source=al_awwal`, `external_platform` is forced to `'al_awwal'`.
  - Validation allows `source in manual,al_awwal,library,import,ana_qudurat` and `category_type in school,qudurat,verbal,quantitative,speed_reading` (`:570-575`).
  - The index tabs filter owner banks (`EloquentQuestionBankRepository.php:32-45`, counts `:78-81`).
  - Schools request access and the owner decides (`QuestionBankController.php:454,507`; table `question_bank_access_requests`).
- **Visibility gotcha:** `visibleBankIds()` (`EloquentQuestionBankRepository.php:272-317`) includes **every owner bank** (`where is_owner_bank = true`, `:294`) whatever the access-request state. The legacy exam picker uses it (`ExamQuestionController::addFromBank`, `:252`), so a school can already pull *approved* owner-bank questions into an exam without an approved access request. For program content served platform-wide this behaviour is convenient, but it does not match `qb-unified-banks` US-002 («NOT list their questions to a school until access is granted»). A decision is needed.
- Existing local data: banks 16 and 17, «بنك القدرات — القسم اللفظي» / «بنك القدرات — القسم الكمي» (`school_id NULL`, public, `category_type` verbal/quantitative). They are created by `database/seeders/QuestionBankCard303Seeder.php` as demo data, not real content.

**`bank_questions`** (create `2026_04_28_110004`; extended by `2026_05_18_120063_card_63`, `2026_05_25_120003` (status enum), `2026_06_12_210001_card215`, `2026_06_15_500006_qbcore_extend_bank_questions`)

`id, question_bank_id FK, subject_id, grade_id, class_id, semester_id, passage_id, question_code(60), question_category enum(normal,tahsili,passage), question_content_type(10: text|image|mixed), is_full_image_question, lesson_id, unit_id, week_id, skill_id, standard_id, domain_id, type enum(mcq,true_false,short,essay,matching,fill_blank), body_ar TEXT NOT NULL, body_en, answer_data json, difficulty tinyint(1..3), points decimal(6,2), attachment_path, source(30), explanation TEXT, status enum(draft,pending_review,approved,rejected,archived), created_by, reviewed_by, reviewed_at, rejected_reason, imported_by, import_batch_id, external_platform(30), external_id(100), sync_status, last_synced_at, metadata json, timestamps, deleted_at, archived_at`

- `grade_id` holds the **grade_level integer** (1..12), not a FK (`ParseQuestionsExcel.php:420-438`, comment "grade_id here is the grade_level integer").
- The question image is `attachment_path` (one per question, public disk, relative path). Explanations are text only; there is no explanation image.
- There is no tags column. Tags and notes in the import template are not persisted.
- `external_id` / `external_platform` exist and can serve as the idempotency key for re-runs.

**`question_answers`** (`2026_06_15_500007_qbcore_create_question_answers`; no FK)

`id, question_id, answer_text, answer_image, answer_content_type enum(text,image,text_and_image), is_correct, sort_order, blank_number, column_a_text, column_a_image, column_b_text, column_b_image`

The MCQ JSON mirror is `{"options":[text...], "correct": denseIndex}` (`MapAnswerData.php:72-75`). Option **images live only in `question_answers.answer_image`**; the JSON holds text only.

**`passages`** and **`passage_questions`** (`2026_06_15_500008_qbcore_create_passages_tables`)

- `passages`: `id, question_bank_id, passage_code, passage_text, passage_image, subject_id, semester_id, week_id, skill_id, difficulty_level, status, created_by, ...`
- `passage_questions`: `passage_id, question_id, sort_order`. This pivot is the authoritative link. `bank_questions.passage_id` and `question_category='passage'` are also set for fast reads (`PassageController.php:244-264`).

**`skills`** (`2026_06_15_500003_qbcore_create_skills_tables`, `2026_08_12_140000_add_lesson_id_to_skills`)

`id, school_id NULL, name, subject_id, semester_id, week_id, lesson_id, skill_type enum(normal,ability,tahsili,verbal,quantitative), is_tahsili, is_ability, status, created_by, ...`

The table is flat: there is **no parent_id**, so it has no skill → sub-skill hierarchy, and there is **no nafis type**. `skill_assignments` maps a skill to compound/school/grade/class.

**`standards`** has `subject_id, domain_id, code, name`. **`domains`** has `subject_id, name` (0 rows). **`compounds`** + **`compound_school`** (global, super-admin). **`question_bank_assignments`** maps a bank to compound/school/grade/class/teacher (0 rows; not read by any exam).

**Import tables**:
- `question_import_batches` holds `preview_data json` for the **whole file** and `images_zip_path`, which is unused by the core.
- `question_import_errors`.
- `skill_import_batches`.

### 1.3 Import pipeline (core, `admin/qb/import`)

- `GenerateImportTemplate.php`:
  - Columns at `:25-54`: `question_code, question_category, subject, grade, class, semester, week, skill, difficulty_level, question_type, question_text, question_image, is_full_image_question, score, answer_1..5_text/_image, correct_answer, explanation, tags, notes`.
  - The header comment (`:14-19`) states that **only the Questions sheet** is generated and consumed. There is no TahsiliQuestions, Passages or PassageQuestions sheet.
- `ParseQuestionsExcel.php`:
  - Validation matrix.
  - Name lookups for subject, skill, term, week and class in the bank's school scope **or** global (`:345-388`).
  - `grade` must be numeric and one of the existing `classes.grade_level` values (`:420-438`).
  - MCQ allows at most 5 options. An image-only option becomes its image **URL as option text** (`:235-262`).
  - Duplicate checks use `question_code` within the file and within the bank (`:170-181`).
  - ZIP images (Method 2) are not consumed (`:32-34`).
- `ImportFromPreview.php`:
  - Each valid row runs through `CreateQuestion` in its own transaction.
  - `attachment_path` takes the raw URL verbatim, so display is "UNVERIFIED" (`:125-128`).
  - `option_image_paths` is never passed, so answer images are lost.
  - Provenance is set as `source='imported'`, `import_batch_id`.
- The legacy importer (`QuestionBanks/Actions/ParseQuestionsExcel.php:20-33`) is a smaller column set (`option_a..d`). Not suitable.

### 1.4 Verdict: home for ~7,300 Al-Awwal questions

**Home: the core data model (shared tables), owner tier, loaded by a one-off artisan command, not the Excel UI.**

Why:
- The core is the only path that writes `question_answers` with images, passages + pivot, skills, and review status.
- `bank_questions.external_id` and `external_platform` give a re-runnable upsert key.
- Owner tier banks already mean "platform content, schools consume". Both the legacy picker (via `visibleBankIds`) and the bank UI see them.
- The UI importer cannot carry passages, answer images or tags, and it resolves subject and skill names in school scope. It also stores the whole file in a single JSON preview row, which is heavy at 7k rows.

Recommended bank layout (decide):
- قدرات لفظي and قدرات كمي: `category_type` `verbal` / `quantitative` (values exist).
- تحصيلي: new `category_type='tahsili'`, possibly one bank per subject.
- نافس: new `category_type='nafis'`, one bank per grade × subject.

**Fields / values missing for an exact port:**

| Need | ViewClass today | Gap |
|---|---|---|
| Program on bank | `category_type` validated to `school,qudurat,verbal,quantitative,speed_reading` (`QuestionBankController.php:572`) | Add `tahsili`, `nafis` (column is varchar(32), so this is a validation and constant change only) |
| Program on question | `question_category enum(normal,tahsili,passage)` | `passage` competes with program (a passage question in تحصيلي loses the tahsili tag). Prefer deriving the program from the bank or a new column. Do not overload the enum. |
| Skill types | `skill_type enum(normal,ability,tahsili,verbal,quantitative)` | No `nafis`. Flat table with no sub-skill/parent. Skills are school-scoped or global (`school_id NULL` OK) |
| Subjects (تحصيلي: رياضيات/فيزياء/كيمياء/أحياء; نافس: رياضيات/علوم/لغتي) | `subjects.school_id` nullable, but subjects are school-owned in practice; `subject_id` on question is optional | Decide between global subjects (`school_id NULL`) and storing the program subject on the skill or bank. Student-subject screens ignore NULL-school subjects (`sidebar.blade.php:12`). |
| Question image | `attachment_path` (public disk path) | Download the Al-Awwal images to `storage/app/public/bank-questions/{bank}/…` during migration. Never store remote URLs. |
| Answer images | `question_answers.answer_image` | Must pass `option_image_paths` to `MapAnswerData`. The importer does not. |
| Passage image | `passages.passage_image` | OK |
| Explanation image / video | none | Confirm whether Al-Awwal has them; if so, add to `metadata` json or a new column |
| Tags | none | `metadata` json |
| > 5 options | importer max 5; `MapAnswerData` unlimited | Fine via the command |
| Difficulty | 1..3 | Map Al-Awwal's scale |
| Source ids | `external_id`, `external_platform` (bank and question) | OK, use as upsert key. Passages have no `external_id`, so use `passage_code` |

---

## 2. Exams students actually take

### 2.1 Legacy online exams (the only student-facing engine)

**Tables:**

| Table | Migration | Columns |
|---|---|---|
| `exams` | `2025_11_30_174400` | `teacher_id NOT NULL, subject_id NOT NULL, class_id NOT NULL, academic_year_id NOT NULL, title, type enum(quiz,midterm,final,assignment,homework), total_marks, pass_marks, duration_minutes, start_time, end_time, is_published, show_results, shuffle_*, attempts_allowed, max_exit_attempts, status enum(draft,scheduled,active,completed,cancelled)` |
| `exam_questions` | `2025_11_30_174401` + `2026_06_13_140001_add_source_bank_question_to_exam_questions` | `question TEXT, type enum(multiple_choice,true_false,short_answer,essay), options json, correct_answer TEXT, marks, explanation, order, source_bank_question_id` |
| `student_exams` | — | `exam_id, student_id, started_at, submitted_at, score, percentage, status enum(not_started,in_progress,submitted,graded), session_token, exit_attempts_count, auto_ended, attempt_number, teacher_feedback` |
| `student_answers` | — | `student_exam_id, question_id, answer TEXT, marks_obtained, is_correct, feedback` |
| `exam_exit_attempts` | — | anti-cheat |

`exam_questions` has **no image/attachment column and no passage link**.

**Routes:**

| Area | Location | What |
|---|---|---|
| Admin | `routes/web.php:627-652` | `admin/online-exams` (resource named `admin.exams.*`): publish, activate, complete, results, `push-to-grade-report`, reopen attempt, question CRUD, `from-bank` picker. `admin/exams` = read-only schedule. |
| Teacher | `routes/web.php:905-925` | `teacher/exams` (same controllers) |
| Student | `routes/web.php:961-969` | list, show, start, submit, exit-attempt, heartbeat, result |
| Parent | `routes/web.php:1007` | `parent/child/{child}/exams` |

**Lifecycle:**
- **Build:** the teacher or admin creates an exam for **one class + one subject + one year**. Questions are typed in or copied from a bank via `ExamQuestionController::addFromBank` (`:240-297`) and `mapBankToExamQuestion` (`:299+`). The copy:
  - takes `body_ar` text only;
  - writes `explanation => null`;
  - copies no `attachment_path`, no option images and no passage;
  - turns matching into essay and fill-blank into short answer.
- **Assign:** implicit. Any student whose `enrolledClassIds()` contains `exams.class_id` sees it (`StudentController::exams` `:203-230`; guard `StudentExamController.php:357-365`; `Exam::canTake` `:139-147` checks the attempts limit).
- **Take:** `resources/views/student/exam-take.blade.php` has **zero `<img>` / attachment references**.
- **Score:**
  - `StudentExamController::submit` (`:115-175`) upserts `student_answers` and auto-grades MCQ and TF.
  - `ExamQuestion::checkAnswer` (`app/Models/ExamQuestion.php:66-73`) compares the **answer string** to `correct_answer`.
  - Essays are left for the teacher; `StudentExam::calculateScore` / `markAsGraded`.
- **Results → grades:** `ExamController::pushToGradeReport` (`app/Http/Controllers/Admin/ExamController.php:363-398`) takes the latest graded attempt and rescales it into `student_grade_values` for one numeric `grade_report_columns` row (idempotent).

### 2.2 `qb_exams` (QuestionBankCore #255): not wired to students

- **Tables** (`2026_06_15_800001_qbcore_create_qb_exams_tables`):
  - `qb_exams`: `school_id, title, delivery_type(electronic|paper), subject_id, semester_id, starts_at, ends_at, duration_minutes, selection_strategy(manual|...), questions_target, difficulty_distribution json, allow_direct_access, show_result_immediately, allow_retake, shuffle_*, pass_score, status, is_published`
  - `qb_exam_targets`: `school_id, grade_level, class_id`, so one exam can target many schools, grades or classes.
  - `qb_exam_questions`: `bank_question_id, question_type, body, attachment_path, answer_snapshot json, question_snapshot json, marks, sort_order`
- **Snapshot** (`QuestionBankCore/Controllers/ExamController.php:300-314`):
  - copies `attachment_path` and `answer_data`;
  - does not copy option images (JSON has text only) or the passage.
- **Results:** a placeholder. The comment at `:194-196` reads «Electronic exam attempts run through the legacy StudentExam engine, which is NOT wired to qb_exams». No attempts or answers table exists, and no student or teacher route exists.

### 2.3 Reuse potential for program exams

| Piece | Reuse? |
|---|---|
| Legacy `exams` row as the program exam | **Poor fit.** `class_id`, `teacher_id`, `subject_id` and `academic_year_id` are NOT NULL and single-valued. A Qudurat mock spans classes or schools and has no teacher or subject. |
| Legacy take/submit/anti-cheat (`session_token`, heartbeat, `exam_exit_attempts`) | Copy the pattern (controller `StudentExamController.php:190-306`) |
| Legacy auto-grade | No. Grading is by text compare, and there are no image options |
| `qb_exams` + targets + snapshot | Good base for definition and targeting (multi-school/grade, `selection_strategy`, `difficulty_distribution`, shuffle, duration). Needs an attempt and answer layer, an option-image snapshot, a passage snapshot and a take UI. |
| `pushToGradeReport` | Pattern for writing a program score into a school's grade report, if wanted |

---

## 3. Academic structure

| Concept (UI label) | Table / model | Key columns | Notes |
|---|---|---|---|
| School | `schools` / `School` | `educational_company_id, stage, educational_track enum(national,international,general,k12), student_gender enum(boys,girls,mixed), timezone` | Soft deletes. Company = «الشركات التعليمية»; complex = `compounds` + `compound_school` |
| «الصف الدراسي» (grade) | `sections` / `Section` | `school_id, name, level enum(primary,intermediate,secondary), grade_number tinyint NULL, gender` | `grade_number` added by `2026_07_25_100000_add_grade_number_to_sections_table`. **NULL on all local rows.** The sprint 11-17 note says no live school is numbered yet («توحيد الترقيم» pending). Labels come from `App\Modules\Promotion\Support\StandardGrades` (1..12 → «الصف الأول الابتدائي» … «الصف الثالث الثانوي», `StandardGrades.php:20-33`) |
| «الفصل» (class) | `classes` / `ClassRoom` (`protected $table='classes'`, `ClassRoom.php:14`) | `section_id, academic_year_id, name, grade_level int, division, number, gender, lead_teacher_id, capacity, is_active` | `grade_level` is the global 1..12 ordinal in seed data (e.g. «الصف السابع» = 7 under المرحلة المتوسطة). `getGradeLevelLabelAttribute` only maps 1-6 (`:97-109`). Gender is forced from the school (`:17-27`) |
| Student ↔ class | `users.class_room_id`, `users.section_id`, `class_student(class_id, student_id, result)` | — | Union via `User::enrolledClassIds()` (`User.php:172-181`); pivot relation `enrolledClasses()` (`:154-158`). Section/class student filter pattern: `CounselingController.php:88-98` |
| Parent ↔ student | `parent_student(parent_id, student_id, relationship, is_primary, ...)` | — | `User::children()` / `parents()` (`User.php:216,223`); parent guard pattern `ParentController::childExams` (`:183-189`) |
| Subjects | `subjects` / `Subject` | `school_id NULL-able, name, grade_levels json, section, is_core, credit_*, source enum(manual,excel,viewclass), template_subject_id` | The student sidebar resolves subjects by `school_id` + `grade_levels` containing the class grade_level (`sidebar.blade.php:9-28`) |
| Subject tracks «المسارات» | `subject_tracks` / `SubjectTrack` | `school_id, name, name_en, sort_order, is_active` | **Standalone list.** Not linked to subjects or students (only CRUD in `SubjectTrackController`) |
| Academic year | `academic_years` | `school_id, start_date, end_date, is_current` | Per school; `classes.academic_year_id` |
| Term | `academic_terms` | `academic_year_id, start/end, is_current` | `AcademicTerm::currentWeek()` (`:40`) |
| Study week | `study_weeks` | `academic_term_id, name, start/end, sort_order` | `StudyWeek::isCurrent()` / `scopeCurrent` (`:23,32`), computed from the date (card #367) |

**Targeting programs by grade (use `classes.grade_level`; fall back to `StandardGrades` labels):**

| Program | Grades | `grade_level` | `sections.level` |
|---|---|---|---|
| نافس | الثالث الابتدائي، السادس الابتدائي، الثالث المتوسط | 3, 6, 9 | primary, primary, intermediate |
| قدرات (لفظي/كمي) | ثانوي; the product decides which years | 10–12 | secondary |
| تحصيلي | ثانوي | 10–12 | secondary |

Do not rely on `sections.grade_number` until numbering is unified.

---

## 4. Roles & permissions

- **Roles** (`roles`, 5 rows):
  - `super-admin` «مدير النظام», `school-admin` «مدير المدرسة», `teacher` «معلم», `student` «طالب», `parent` «ولي أمر».
  - Linked through `role_user`. Role permissions live in `permission_role`.
  - Checks: `User::hasRole`, `isSuperAdmin`, `isSchoolAdmin`, `isTeacher`, `isStudent`, `isParent` (`User.php:240,339-360`).
- **Job titles:**
  - `job_titles(school_id NULL, role_id, slug, name_ar, ...)`, e.g. `counselor` «مرشد طلابي», `supervisor` «مشرف تعليمي», `vice` «وكيل».
  - `job_title_permissions(job_title_id, permission_id, scope enum(all,company,group,school,stage,class,section,subject,own_students,own_subjects,own))`.
  - Scope labels: `PermissionCatalog::SCOPE_LABELS` (`:89-101`).
- **`User::canDo($perm)`** (`User.php:284-322`):
  1. super-admin passes;
  2. the role has the permission in `permission_role`;
  3. otherwise the job-title matrix decides.

  With no job title or an empty matrix, **only `*.view` default-allows**; writes fail closed. `canViewModule($m)` = `canDo("$m.view")` (`:324-327`).
- **Middleware aliases** (`bootstrap/app.php:15-19`):
  - `role` → `CheckRole`;
  - `permission` → `CheckPermission` (calls `canDo`);
  - `role_permission` → `RolePermission`, which checks **only** `isSuperAdmin() || hasPermission()`. No job-title fallback and no view default-allow.
- **Permission catalog:** `app/Modules/Users/Support/PermissionCatalog.php:19-87`, `MODULES` = `'group' => ['label' => '…', 'actions' => [...]]`. The newest entry is `'counseling' => ['label' => 'الإرشاد الطلابي', 'actions' => ['view','manage']]` (`:20`). Both the job-title matrix (`JobTitlePermissionsController.php:50`) and the global roles editor read it.
- **Adding a permission group (pattern from #372):**
  1. Add `'special_programs' => ['label' => 'البرامج التعليمية الخاصة', 'actions' => [...]]` to `MODULES`.
  2. Write a migration modelled on `database/migrations/2026_11_16_372001_seed_counseling_permissions.php`:
     - `updateOrInsert` into `permissions(slug, name, group)`;
     - grant to job titles via `job_title_permissions`, and/or to roles via `permission_role` (the pattern is in `2026_07_13_100000_seed_baseline_role_permissions.php`);
     - give `down()` deletes from both pivots.

  If routes use `role_permission:`, the school-admin and teacher roles **must** get `permission_role` rows, or they will 403.
- **Sidebar:**
  - **Card target:** «الشؤون التعليمية والخطط» (`shell.section_academic`, `lang/ar/shell.php:206`), header at `sidebar.blade.php:583`, content `#gp-sec-academic` `:590-623`. The item is **`:609`** `<li … data-section="academic"><a href="{{ route('programs.coming-soon') }}"> … @lang('shell.nav_special_programs')` → «البرامج التعليمية الخاصة» (`lang/ar/shell.php:220`, icon `heart-pulse`).
  - **Also coming-soon:** the standalone «الأول بلس» (`:543`, `shell.nav_alawwal`, `lang/ar/shell.php:81`), «أنا والقدرات» (`:544`) and «تطبيقي القراءة السريعة والقدرات اللفظية» (`:545`), plus «النقاط والمكافآت» (`:691`, card #371).
  - **Card scope:** #368 names «الأول بلس» as its example, and the sprint spec says "only swap coming-soon hrefs, sidebar order untouched". Decide whether `:543` should also point to the new module.
  - **Placeholder route:** `routes/web.php:36` `Route::view('/programs/coming-soon', 'programs.coming-soon')`.
  - **Staff-only scope:** all of the above sits inside `@if($isStaff)` (`:538` → `@endif {{-- /isStaff --}}` `:1007`). Teacher, student and parent menus are separate blocks (section 6).
  - **Gating:** `$canView*` flags (`:31-60`) use `!$sidebarUser || !$isStaff || canViewModule('…')`. Add `$canViewSpecialPrograms` the same way, and guard the href with `Route::has()`.
- **Guard test:** `tests/Feature/RbacSuperAdminRoutesTest.php` enumerates every GET route whose middleware contains exactly `role:super-admin`. It hits each one with `{param}` replaced by `1` as school-admin, teacher, student and parent, and expects **403**. Super-admin-only program routes (for example the migration or import screens) must 403 before any 404 or 500.

---

## 5. Reports, grades, charts, exports

| Area | Where | Fit for programs |
|---|---|---|
| Grade reports «تقارير الدرجات» | `app/Modules/GradeReports` (`GradeReportController`, `GradeEntryController`, `GradeMonitorController`, `GradeReportPrintController`; repo bound in `RepositoryServiceProvider`). Routes `routes/web.php:1015-1031` (`admin.grade-reports.*`, super/school-admin). Tables (see `.kiro/specs/trello-grade-reports/spec.md`): `grade_reports` (school, year, term, class, subject, type enum(dynamic,static,gradesheet,transcript,notification), visible_to_student/parent/teacher, is_locked), `grade_report_columns` (numeric/calculated, weight, max_score, pass_threshold), `grade_report_ratings` (label + min/max), `student_grade_values` (column, student, score) | Optional sink for program scores via the `pushToGradeReport` pattern. The model is class × subject × term, which does not fit a program report |
| Grade monitor | `GradeMonitorController` (`admin.grade-reports.monitor`, `/export`) | Analytical table pattern |
| Legacy grades | `grades` (quiz_avg…total, letter_grade); student `student.grades`, parent `parent.child.grades` | — |
| Evaluation outcomes | `evaluation_outcomes` (`test_name, test_type, source, registered/present/absent counts, scores_sum, final_average, students json`). `OutcomeSource` enum already has `external_alawwal` «منصة الأول» and `external_qudrat` «أنا والقدرات» marked "TODO Phase D" (`app/Modules/Evaluation/Enums/OutcomeSource.php:11-20`) | A ready hook. A program exam result could post an outcome row with source `internal` or `external_alawwal` |
| Performance levels | `evaluation_levels(form_id, label, value, percentage)` (evaluation forms); `grade_report_ratings` (score bands) | Program levels (e.g. ممتاز/متقدم…) need their own bands. Reuse the `grade_report_ratings` shape |
| «مجموعات الأداء» | **Nothing.** A grep for مجموعات الأداء / performance_group / student_groups returned 0. `behavior_groups` (behaviour categories) and `qr_attendance_groups` are unrelated | New table needed |
| Charts | Chart.js 4.4.1 via CDN, **only** in `resources/views/dashboard.blade.php:636`. No npm chart dependency (`package.json` = vite + tailwind only) | Load the same CDN tag in program report views |
| PDF | `mpdf/mpdf ^8.2` (also `barryvdh/laravel-dompdf`). Shared helper: trait `App\Modules\Attendance\Controllers\Concerns\ExportsReports::exportPdf()` (`:26-63`, mPDF utf-8, RTL, `xbriyaz` font, page footer «صفحة {PAGENO} من {nb}»). Also `Admin\ReportController.php:538` and `GradeReportPrintController`. Memory note: Arabic in mPDF → xbriyaz, not dejavusans | Use `ExportsReports` |
| Excel / CSV | Same trait: `exportExcel()` (`:72`, RTL sheet, PhpSpreadsheet 5.x) and `exportCsv()` (`:109`, UTF-8 BOM) | Use the trait |

---

## 6. Student, teacher and parent shells

| Role | Routes | Controller / views | Sidebar block | Add program screens as |
|---|---|---|---|---|
| Student | `routes/web.php:956-1000`, `role:student`, prefix `student`, name `student.` | `App\Http\Controllers\StudentController` (dashboard, grades, exams, reports…), `StudentExamController`; views `resources/views/student/*` (`dashboard`, `exams`, `exam-take`, `exam-result`, `grades`, `reports/…`) | `sidebar.blade.php:1125-1260`: groups `shell.subjects_group`, `shell.group_educational`, `shell.group_reports`. Items such as «الاختبارات» → `student.exams`, «درجاتي» → `student.grades` | `student/programs` (my programs → take a program exam → my progress) plus a sidebar `<li data-section="student">` |
| Parent | `routes/web.php:1003-1011`, `role:parent`, prefix `parent` | `ParentController` (`child/{child}/…`, guard `children()->where('users.id', …)` `:187`); views `resources/views/parent/child-*.blade.php` | `:1261+` | `parent/child/{child}/programs` (read-only progress report) |
| Teacher | `routes/web.php:890-953`, `role:super-admin,school-admin,teacher`, prefix `teacher` (reuses Admin controllers for exams/weekly-plans) | Teacher dashboard is `DashboardController::index` → `getTeacherStats` (`DashboardController.php:52`), view `dashboard.blade.php`; `resources/views/teacher/*` holds only calendar/materials/students/subjects/portfolio | `:1044-1124` (`shell.portal_teacher`): «اختباراتي» → `teacher.exams.index`, «بنك الأسئلة» → `admin.question-banks.index`, «طلابي» → `teacher.students.index` | `manage/special-programs` under `role:super-admin,school-admin,teacher` (like Counseling at `manage/counseling`), filtered to the teacher's classes |
| Staff dashboard | `DashboardController::index` (`:44-71`); students and parents are redirected to their own dashboards | `dashboard.blade.php` | Dashboard `<li>` `:514-534` is role-aware | Optional KPI tile |

---

## 7. Conventions checklist for the new module

- **Location:**
  - code in `app/Modules/SpecialPrograms/{Actions,Controllers,DTOs,Http/Requests,Models,Repositories(/Contracts),Routes,Services}`;
  - views in **`resources/views/<feature>/…`**, not inside the module (no module calls `loadViewsFrom`; Counseling uses `resources/views/counseling/*`);
  - migrations in **`database/migrations/`** (no module-local migration dirs). Name them `2026_11_xx_368NNN_create_…` (the #372 precedent is `2026_11_16_372000_…`).
- **Routes:**
  - `app/Modules/SpecialPrograms/Routes/web.php`;
  - add `require __DIR__.'/../app/Modules/SpecialPrograms/Routes/web.php';` at the end of `routes/web.php` (the existing list is `:1187-1325`; Counseling is last at `:1325`);
  - use `->whereNumber('id')` on ids, as Counseling does (`app/Modules/Counseling/Routes/web.php`).
- **Repository:**
  - interface in `Repositories/Contracts/`, Eloquent implementation beside it, binding added to `RepositoryServiceProvider::$bindings` (`app/Providers/RepositoryServiceProvider.php:110-160`);
  - note: the newest module (Counseling) skipped the interface and uses a concrete `CounselingCaseRepository::visibleTo(User)` as its single scoping entry point. CLAUDE.md still asks for interface + binding.
- **Actions:** one `execute()` each; return models or DTOs, never a Response.
- **Tenant scope:**
  - `use App\Modules\Users\Controllers\Concerns\HasSchoolScope;` → `scopedSchoolId()` (`HasSchoolScope.php:69`), which 403s a non-super with a null school;
  - `activeSchoolId()` (`:43`) honours the header scope switcher;
  - filter by `school_id` **inside repositories**.
- **Schema rules:**
  - soft deletes on tenant-owned tables;
  - **no FK to `users.id`** (type differs between installs; sprint spec cross-cutting). Plain `unsignedBigInteger(...)->index()` as in `2026_11_16_372000_create_counseling_tables.php`.
- **API envelope:** only for `/api/*`: `App\Support\ApiResponse::ok/fail`. Web pages return Blade.
- **Permissions:** add to `PermissionCatalog::MODULES` and write a seed migration (section 4). Gate pages with `canDo()` in controllers and/or the `permission:` middleware (it honours job titles). Prefer it over `role_permission:` (role pivot only).
- **Sidebar:** replace the href at `sidebar.blade.php:609` with `Route::has('…') ? route('…') : route('programs.coming-soon')`. Add role entries in the teacher, student and parent blocks. Keep the order.
- **Translations:**
  - add `lang/ar/special_programs.php` and `lang/en/special_programs.php` (62 files in each locale);
  - `fallback_locale` is **`en`** (`config/app.php:83`);
  - `lang/en/shell.php` lacks several keys that `ar` has (`section_academic`, `nav_special_programs`, `nav_points_rewards`…). An English UI renders the raw key, so add the English keys when you touch these.
- **Icons and design:**
  - icons: `<x-svg-icon name="bootstrap-icon-name" class="vc-ico" />` (`resources/views/components/svg-icon.blade.php`, sprite `public/vendor/bootstrap-icons/bootstrap-icons.svg`);
  - layout: `@extends('layouts.app')`;
  - tokens in `public/css/design-system.css` («المنصة الذهبية — Gold & Navy»): `--gold-50..700`, `--navy`, `--navy-dark|light|muted`, `--gray-*`, `--space-*`, `--radius-*`, `--shadow-*`, `--font-size-*`;
  - avoid an `ad-` CSS class prefix (ad-blockers hide it).
- **Tests:**
  - in `tests/Feature/<Name>Test.php`; base class `tests/TestCase.php` (RefreshDatabase + `RoleSeeder`, plus `createSuperAdmin/SchoolAdmin/Teacher/Student/Parent` helpers);
  - the full suite is broken on sqlite (MySQL-only ALTERs), so each class overrides `refreshDatabase()` and `require`s **only** the migrations it needs, by glob (see `tests/Feature/CounselingAccessTest.php:16-30`);
  - run with `php artisan test --filter=SpecialPrograms` in the main checkout (the worktree has no vendor/);
  - keep `RbacSuperAdminRoutesTest` green;
  - never run the full suite against the dev MySQL DB.
- **Deploy (per CLAUDE.md):** commit → push → SSH → `git pull` → `migrate --force` → `view:cache` on **both** viewclass.newaves-systems.com and rawaniedu.com.

---

## 8. Gaps & risks for an exact port

### 8.1 Gaps already visible on the ViewClass side

1. **No program / enrolment model.** Every one of these must be built:
   - program definitions (قدرات لفظي، قدرات كمي، تحصيلي، نافس);
   - program ↔ school activation;
   - student enrolment (manual pick, or a section/class bulk add like Counseling's «اختيار كل الطلاب»);
   - «مجموعات الأداء» (performance groups: named student groups, probably with a level band and a teacher);
   - per-student progress.
2. **No student-facing engine for bank-based exams.**
   - The legacy engine cannot show images or passages and grades by text compare.
   - `qb_exams` has no attempts.
   - Qiyas content is image-heavy (geometry, charts, comparisons), so a new take/grade path is mandatory, not optional.
3. **Snapshot loses data.**
   - Option images are not in `answer_data`.
   - Passages are not snapshotted.
   - Any exam snapshot must read `question_answers` + `passages`.
4. **Importer limits.** No passage sheets, no ZIP images, option images dropped, verbatim URLs, school-scoped name lookups, max 5 options, and a whole-file JSON preview. Use a command for the 7,300 questions. The UI importer stays for school-made questions.
5. **Taxonomy enums.**
   - No `nafis` in `skill_type` or `category_type` (validation); `tahsili` is missing from `category_type`.
   - `question_category` mixes program (`tahsili`) with structure (`passage`).
   - Skills are flat (no sub-skill).
   - `domains` / `standards` exist but are empty and unused by any importer.
6. **Subjects for programs.** Subjects are school-scoped in practice. Program subjects (فيزياء/كيمياء/أحياء for تحصيلي, لغتي/علوم/رياضيات for نافس) need a global representation that does not leak into school subject lists.
7. **Grade numbering.** `sections.grade_number` is unset, so targeting must use `classes.grade_level`. Verify the live data has sane `grade_level` values per school before auto-enrolling by grade.
8. **Owner-bank access semantics.** Owner banks are already visible to every school's exam picker (`visibleBankIds` `:294`), but the policy spec says access needs approval. Decide whether program banks follow the access-request gate or are served through programs only, never through the generic picker.
9. **Performance and scale.**
   - At 7,300 questions + answers (~30k rows) + images, an image download/copy step and storage quota matter on **both** live hosts (rawaniedu runs on a separate VPS).
   - `bank_questions.question_code` has no unique index; uniqueness is per bank in code only.
10. **Two live instances.** The data migration must run on viewclass **and** rawaniedu, and be idempotent on `external_id`.
11. **Branding collision.** ViewClass's own default brand is «منصة الأول» (`Setting.php:99`, `AppServiceProvider.php:38`), and the owner tier is labelled «منصة الأول» / «الأول بلس». The *external* Al-Awwal platform being ported shares the name. Keep `external_platform='al_awwal'` for provenance, and pick UI labels deliberately.
12. **i18n debt.** English shell keys are missing for the academic section items.

### 8.2 Confirm against the Al-Awwal source

The source codebase was not analysed here. Confirm each item before the schema is fixed:

- the program → section → subject → skill (→ sub-skill?) hierarchy, and how نافس splits by grade and subject;
- question types used: MCQ only? Number of options; image-only options; full-image questions;
- passage structure: one passage, many questions; passage images; ordering;
- the explanation / solution format: text, image, video;
- difficulty scale and any per-question time;
- exam forms («اختبار محاكي», skill drills, adaptive?), timing, sections, scoring scale and level bands, and whether score is percent or a scaled 100 like Qiyas;
- the student report: what «تقرير الأداء» shows (per skill mastery, trend, comparison with the group) and the parent/teacher views;
- the meaning of a "performance group" (مجموعة أداء): teacher-made or auto by level;
- media hosting: where images live, and whether they can be copied.
