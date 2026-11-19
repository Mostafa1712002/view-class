# Al-Awwal («الأول») → ViewClass: source map of the three special programs

Source: `/home/mostafa/www/alawal` (git `live-2026-11-19`, Laravel 9, docroot = project root), DB `alawal_local` (local import). Read-only analysis, 2026-11-19.
Programs: **قدرات / qiyas** (لفظي QL + كمي QQ), **تحصيلي / tahsili**, **نافس / nafis**.
Row counts are exact `COUNT(*)` on the local import unless marked "≈".

Sections 0–2 and 6–9 are shared. Section 3 is qiyas, 4 is tahsili and 5 is nafis: routes, controllers, flows, views, scoring and gotchas, with sub-sections lettered as in the brief (A routes … I gotchas).

---

## 0. TL;DR for the port

- **All program questions are in one table, `questions`.** They are split by `exam_type` (`qiyas`/`tahsili`/`nafis`) and by `subject_id`. Each program has its own subjects: 21/22 = qiyas, 59/60/61 = nafis, 62–65 = tahsili. The grade level is set **only** by the `classroom_question` pivot. `questions.classroom_id` is NULL on every program row.
- **There is no `passages` table.** The `Passage` model and the 2024-10 passages migrations are dead. A passage is one `questions` row with `is_independent='passage'` and the passage body in `passage_text`. `question_text` holds a JSON **array** of sub-question stems, and `options` holds an array of option-arrays (one per sub-question).
- **The correct answer is stored only in `options` JSON** (`[{"text":..,"is_correct":true|false},..]`). `questions.correct_answer` is NULL on all 7,306 program rows. Each question, and each passage sub-question, has exactly one correct option (verified).
- **58 of the 68 nafis passage rows are double-encoded.** In those rows, `question_text`, `options` and `skills` hold a JSON *string* that itself contains JSON. Decode in a loop until you get an array.
- **`fingerprint` is not a unique key.** For tahsili, 2,701 of 2,704 rows share 11 fingerprints. Most tahsili questions are an image plus «اختر الإجابة الصحيحة» plus options أ/ب/ج/د, so the hash collides. Real content duplicates (same text + options + image + passage) are: qiyas 327 rows in 149 groups, nafis 53 rows in 26 groups, tahsili 0.
- **Tahsili has no skills.** `skill_id` is NULL on every tahsili row and subjects 62–65 have 0 skills. Tahsili exams pick questions by **difficulty % + a `question_order` range**. Qiyas and nafis pick questions by **skill-distribution %**.
- **Images:** 4,993 program questions have an image, 199.5 MB in total, and every file exists locally. They are stored as relative paths `uploads/questions/...` under the web root (no storage disk).
- **Performance levels** are 5 fixed Arabic names. Each school (`grades` row) can override the % bands per `exam_type`, but only for `general|nafis|tahsili`; **qiyas has none**. `levels_config` is double-encoded JSON.
- **Timezone is UTC** (`config/app.php:72`). `expires_at` and timers are computed in UTC, while users are in KSA (UTC+3).
- **Do not copy the runtime as-is. The behaviour is buggy:**
  - Timers run only in the browser.
  - Tahsili exams never expire.
  - Qiyas snapshots break passages (176 rows saved with only the first sub-question, no passage text and answer index 0).
  - Nafis and tahsili staff skill/level reports have known formula bugs.
  - Most admin exam and report routes have **no auth middleware**.
  
  Port the *intent*: the screens, fields, labels and formulas listed in §3–§6.

---

## 1. Shared data model

### 1.1 `questions` (34,779 rows total; program rows = 7,306)

| column | type | use in programs |
|---|---|---|
| id | bigint PK | |
| fingerprint | varchar(255) NOT NULL | md5 hash (see per-program §C). Not unique; see §8.4 |
| exam_type | enum('regular','nafis','tahsili','qiyas') default 'regular' | program discriminator |
| question_text | longtext | JSON array of stems. Independent → `["stem"]`. Passage → `["q1","q2",…]` |
| question_image | varchar(255) NULL | relative path `uploads/questions/...` |
| difficulty | enum('easy','medium','hard') NOT NULL | qiyas: real spread; tahsili: almost all `medium` |
| term | enum('first','second','third','both') NULL | |
| week | tinyint NULL | qiyas: 1–19 (all set); nafis: mostly NULL; tahsili: NULL |
| skill_id | bigint NULL → skills.id (no FK constraint) | qiyas: all set; nafis independent: set; nafis passage: NULL; tahsili: NULL |
| subject_id | bigint NOT NULL → subjects.id (no FK) | |
| type | enum('multiple_choice','true_false') | always `multiple_choice` in programs |
| question_order | int NULL | **tahsili only** (1..N per subject, used for range picking). NULL elsewhere |
| correct_answer | varchar(255) NULL | **always NULL in programs** |
| classroom_id | bigint NULL | **always NULL in programs**. Use `classroom_question` |
| is_independent | enum('independent','passage') default 'independent' | |
| passage_text | longtext NULL | passage body (passage rows only) |
| options | longtext | JSON (see §8.1) |
| skills | longtext NULL | **nafis passage rows only**: JSON array of skill ids (strings), one per sub-question |
| created_at/updated_at | timestamps | |

Indexes: `skill_id`, `subject_id`, `classroom_id`, `idx_tahsili_order(subject_id, exam_type, question_order)`. There are no FK constraints.

Model `app/Models/Question.php`:
- `$casts`: `options`, `question_text`, `skills` → array (`:12-18`).
- `setOptionsAttribute` json_encodes the value (`:90-93`). A string that is already JSON gets encoded a second time, which is the double-encoding source.
- `getOptionsAttribute` json_decodes once (`:78-81`). For double-encoded rows it returns a **string**.
- `setQuestionTextAttribute` wraps a scalar in an array, collapses all whitespace and newlines to single spaces, then json_encodes (`:96-110`).
- `getQuestionTextAttribute` applies `nl2br` to every stem on read (`:113-125`). **Export from raw SQL, not through the model.**
- `classrooms()` is `belongsToMany(Classroom, 'classroom_question')` (`:36-39`).
- `relatedQuestions()` uses `passage_id` / `is_from_passage` (`:61-64`). Both are **non-existent columns**, so this is dead code.

### 1.2 Reference tables

| table | rows | columns (key ones) | notes |
|---|---|---|---|
| `subjects` | 63 | id, name, **type** varchar(50) | program subjects listed below. `type` is the program switch |
| `skills` | 4,900 | id, name, subject_id, term enum(first,second,third) | no FK. Program skills are listed below |
| `skill_classroom` | 35,623 | skill_id, classroom_id | links skills to per-school classrooms. Program skills: 21→270, 22→270, 59→144, 60→264, 61→144 links |
| `skill_weeks` | 6,184 | skill_id, week int | program skills: 21→95, 22→95, 59→38, 60→133, 61→57 |
| `classroom_question` | 218,686 | question_id, classroom_id | **the only grade-level link**. No duplicate pairs. 60 rows (program questions) point to 2 deleted classrooms (orphans) |
| `classroom_subject` | 1,213 | subject_id, classroom_id (unique pair, FK cascade) | which classes get which subject |
| `classrooms` | 72 | id, Grade_id (= **school**), Name_Class («الصف الأول»…), school_type_id (NULL everywhere) | **classrooms are per school.** «الصف الثالث» exists separately in each school |
| `grades` | 20 | id, Name (school name), school_type_id (1 ابتدائية, 2 متوسطة, 3 ثانوية), complex_id, is_active, supervisor_id (وكيل مدرسة), deputy_supervisor_id (وكيل مدرسة ثان), head_id (مدير القسم) | **a "grade" is a school** (boys/girls variants are separate rows) |
| `school_types` | 3 | 1 ابتدائية, 2 متوسطة, 3 ثانوية | |
| `sections` | 243 | Name_Section, Grade_id, Class_id | exams target sections via `*_exam_sections` |
| `weeks` | 38 | term enum(first,second), week, start_date, end_date | calendar 2025-26, 19 weeks per term. CRUD `WeekController` (`routes/web.php:366-369`), sidebar «إعدادات الأسابيع والفصول الدراسية» (مدير النظام only) |
| `teacher_section_subject` | 5,644 | teacher_id, section_id, subject_id, classroom_id | **ties a teacher to a program**: `Teacher::subjects()` goes through this pivot (`app/Models/Teacher.php:67-72`) |
| `settings` | 17 | key/value | `report_percentage_type=full_class` (used by reports), school/company names, logos |

Program subjects (`subjects.type` is the switch used everywhere):

| id | name | type | questions | skills (ids) |
|---|---|---|---|---|
| 21 | القدرات اللفظية | QL | 1,611 | 6216 التناظر اللفظي, 6217 الخطأ السياقي, 6218 إكمال الجمل, 6219 المفردة المخالفة, 6220 استيعاب المقروء |
| 22 | القدرات الكمية | QQ | 1,960 | 6221 الجبر والمعادلات, 6222 الحساب والتقدير, 6223 الهندسة والمساحة, 6224 الإحصاء والرسوم البيانية, 6225 المقارنات |
| 59 | نافس "قراءة" | nafis | 68 (all passages) | 4489 استيعاب المقروء (first), 4491 دلالات الألفاظ (second) |
| 60 | نافس "رياضيات" | nafis | 492 | 4499 الهندسة والقياس, 4500 الجبر, 4501 البيانات والاحتمالات, 4502 الأعداد والعمليات, 4507 الأعداد والعمليات عليها, 4509 الجبر والدوال الحقيقيةوالتحليل, 4511 الهندسة والقياس (second) |
| 61 | نافس "علوم" | nafis | 471 | 4493 علوم الحياة, 4494 علم الأرض والفلك, 4495 العلوم الفيزيائية والكيميائية |
| 62 | تحصيلي "فيزياء" | tahsili | 502 | none |
| 63 | تحصيلي "كيمياء" | tahsili | 608 | none |
| 64 | تحصيلي "أحياء" | tahsili | 859 | none |
| 65 | تحصيلي "رياضيات" | tahsili | 735 | none |

(Subjects 66–76 have types `islamic A/B/C`. That is a fourth program, «إسلاميات», which is out of scope.)

### 1.3 Program-specific tables (columns abbreviated; exact DDL comes from `SHOW CREATE TABLE`)

**Qiyas** (InnoDB, FK constraints present, cascade):

| table | rows | columns |
|---|---|---|
| `qiyas_exams` | 43 | title, type enum(single,multi), grade_id→grades, classroom_id→classrooms, complex_id→complexes (set null), expiry_hours int def 24, expires_at timestamp, created_by, teacher_id, created_by_type varchar def 'admin' |
| `qiyas_exam_sections` | 85 | qiyas_exam_id→ (cascade), section_id→sections |
| `qiyas_exam_subjects` | 45 | qiyas_exam_id, subject_id→subjects, duration_minutes def 60, total_questions def 20, order_index, **term enum(first,second)**, **weeks longtext JSON (CHECK json_valid)**, teacher_id→teachers (set null) |
| `qiyas_exam_skill_distribution` | 163 | qiyas_exam_subject_id→ (cascade), skill_id→skills, percentage int |
| `qiyas_exam_student_questions` | 3,001 | student_id→students, qiyas_exam_subject_id, question_id→questions (cascade), sub_index def 0, question_text text, options_json (json_valid), correct_answer, passage_text, order_index, question_type enum(independent,passage), skill_id→skills (set null) |
| `qiyas_exam_student_answers` | 2,700 | student_id, qiyas_exam_subject_id, qiyas_student_question_id→ (cascade), answer_text text NOT NULL, is_correct, score **int**. UNIQUE(qiyas_student_question_id, student_id) |
| `qiyas_exam_student_results` | 209 | student_id, qiyas_exam_subject_id, status enum(pending,completed,auto_finished), total_score int, obtained_score int, started_at, ended_at, time_spent int, last_saved_at. UNIQUE(student_id, qiyas_exam_subject_id) |

**Tahsili** (no FK constraints, bigint signed ids):

| table | rows | columns |
|---|---|---|
| `tahsili_exams` | 198 | title, type enum(single,multi), classroom_id, expiry_hours def **48**, expires_at datetime, **weeks_count**, grade_id, complex_id, created_by, created_by_type enum(admin,teacher,supervisor) |
| `tahsili_exam_sections` | 539 | tahsili_exam_id, section_id |
| `tahsili_exam_subjects` | 218 | tahsili_exam_id, subject_id, duration_minutes, total_questions, order_index, teacher_id, **easy_percentage, medium_percentage, hard_percentage, start_question_id, end_question_id, total_available_questions** |
| `tahsili_exam_student_questions` | 37,838 | student_id, tahsili_exam_subject_id, question_id, **question_original_order**, sub_index, question_text, options_json, correct_answer, order_index (no passage columns) |
| `tahsili_exam_student_answers` | 34,212 | student_id, tahsili_exam_subject_id, tahsili_student_question_id, answer_text varchar, is_correct, score **float** |
| `tahsili_exam_student_results` | 1,308 | student_id, tahsili_exam_subject_id, total_score float, obtained_score float, status enum(pending,completed,timeout), started_at, ended_at |
| `tahsili_settings` | 1 | weeks_count def 10, allow_editing_by_default, created_by/updated_by→users. Row: weeks_count=10, allow=0 |
| `tahsili_school_settings` | 6 | grade_id UNIQUE, weeks_count, allow_editing. Rows: schools 5, 6, 11, 12, 23, 24 (all secondary), weeks_count=10, allow=0 |
| *(no `tahsili_exam_skill_distribution`)* | | |

**Nafis** (only the distribution table has FKs):

| table | rows | columns |
|---|---|---|
| `nafis_exams` | 104 | title, type enum(single,multi), classroom_id, grade_id, complex_id, created_by, created_by_type enum(admin,teacher), teacher_id→teachers (cascade), expiry_hours def 24, expires_at |
| `nafis_exam_sections` | 202 | nafis_exam_id, section_id |
| `nafis_exam_subjects` | 131 | nafis_exam_id, subject_id, teacher_id, **reading_mode enum(detailed,passages) def detailed**, **passages_data longtext JSON (CHECK json_valid)**, duration_minutes, total_questions, order_index |
| `nafis_exam_skill_distribution` | 298 | nafis_exam_subject_id→ (cascade), skill_id→skills (cascade), percentage |
| `nafis_exam_student_questions` | 35,907 | student_id, nafis_exam_subject_id, question_id, sub_index, question_text, options_json, passage_text text, question_type enum(independent,passage), correct_answer, order_index, last_saved_at |
| `nafis_exam_student_answers` | 25,069 | student_id, nafis_exam_subject_id, nafis_student_question_id, answer_text, is_correct, score float |
| `nafis_exam_student_results` | 636 | student_id, nafis_exam_subject_id, total_score, obtained_score float, status enum(pending,completed,timeout,in_progress), started_at, ended_at, last_saved_at |

Pattern shared by all three: **exam → exam_subjects (one per subject, with its own timer) → per-student frozen question snapshot (`*_student_questions`, which copies text/options/correct answer) → answers → one result row per student per exam-subject.** Because of this snapshot, reports never re-read `questions`.

Ignore the `*_bak_20260929` tables (nafis backups).

### 1.4 Performance levels (shared by nafis/tahsili reports and `general`)

| table | rows | notes |
|---|---|---|
| `performance_levels` | 5 | global defaults: ممتاز 90–100 success fa-star · جيد جداً 80–89 info · جيد 70–79 primary · مقبول 60–69 warning · ضعيف 0–59 danger |
| `performance_level_settings` | 1 | allow_editing_by_default=0 |
| `performance_level_grade_settings` | 20 | per school (`grade_id`) × `exam_type` (`general`/`nafis`/`tahsili`). UNIQUE(grade_id, exam_type). allow_editing. **levels_config is double-encoded JSON** (a JSON string inside JSON). 18 schools have `general` = ضعيف 0–49 / مقبول 50–74 / جيد 75–79 / جيد جداً 80–90 / ممتاز 91–100. Rows 19 (school 7, nafis) and 20 (school 6, tahsili) have NULL config, so defaults apply |

Logic (`app/Models/PerformanceLevelGradeSetting.php`):
- `getFixedLevels()` (`:63-72`) returns the 5 names, colors and icons.
- `getDefaultPercentages($type)` (`:77-105`):
  - general: 90/80/70/60/0
  - **nafis: ممتاز 85–100, جيد جداً 75–84, جيد 65–74, مقبول 50–64, ضعيف 0–49**
  - **tahsili: ممتاز 95–100, جيد جداً 85–94, جيد 75–84, مقبول 65–74, ضعيف 0–64**
- `getLevels(gradeId,type)` (`:111-149`) returns the saved config if present (decoding a string), otherwise the defaults.
- `getPerformanceLevel(%,gradeId,type)` (`:152-174`) rounds to 2 decimals, sorts levels by min descending, and returns the first level where `% >= min`. If none matches it returns the lowest level.
- `canEdit($userId)` (`:36-47`) is true when allow_editing is set **and** the user is the school's supervisor_id, deputy_supervisor_id or head_id.
- `getSettings()` uses `firstOrCreate` (`:53-58`), **so reading the settings inserts rows**.

Older duplicate logic: `app/Models/PerformanceLevel.php:27-55` `getLevelByPercentage` reads the config without an exam_type filter.

Controller `app/Http/Controllers/Admin/PerformanceLevelSettingsController.php`:
- `adminSettings` (`:18`). `?type=` filters schools by stage via `getSchoolTypesByExamType`: nafis → school types [1,2], tahsili → [3], general → all.
- `getSchoolSetting` (`:78`), `getSchoolLevels` (`:99`), `saveGlobal` (`:115`).
- `saveSchool` (`:147`) validates `exam_type in:general,nafis,tahsili` and `levels.*.min/max` integer 0–100, and rejects overlapping ranges (`:160-175`).
- `resetSchool` (`:213`).
- `supervisorSettings` (`:224`): the first active school where the user is head, supervisor or deputy, otherwise 403 «لا توجد مدرسة تابعة لك مناسبة لهذا النوع من الاختبارات».
- `updateSchoolSettings` (`:258`), and the static `getPerformanceLevel` (`:341`).

Routes (`routes/web.php`; outside the `auth:web` group (`:107-387`), but each group adds its own `auth` middleware):

| line | method | URI | name | action |
|---|---|---|---|---|
| 629 | GET | admin/performance-levels/{type?} (general\|nafis\|tahsili) | performance-levels.admin | adminSettings |
| 633 | POST | admin/performance-levels/global | performance-levels.global | saveGlobal |
| 636 | POST | admin/performance-levels/school | performance-levels.school | saveSchool |
| 639 | DELETE | admin/performance-levels/reset/{gradeId} | performance-levels.reset | resetSchool |
| 642 | GET | admin/performance-levels/get/{gradeId} | performance-levels.get | getSchoolLevels |
| 645 | GET | admin/performance-levels/setting/{gradeId} | performance-levels.setting | getSchoolSetting |
| 651 | GET | supervisor/performance-levels/{type?} | performance-levels.supervisor | supervisorSettings |
| 655 | POST | supervisor/performance-levels/update | performance-levels.update | updateSchoolSettings |

Views: `resources/views/admin/performance-levels/admin-settings.blade.php` and `supervisor-settings.blade.php`.

---

## 2. Guards, roles, permissions, program assignment

### 2.1 Guards (`config/auth.php`)

All guards use the session driver. `web` → `users` (staff and admins), `student` → `students`, `teacher` → `teachers`, `parent` → `my__parents`.

The sidebar is chosen by guard in `resources/views/layouts/main-sidebar.blade.php`: `auth('web')` → admin, `student`, `teacher`, `parent` (the parent sidebar has **no** program entries).

Laratrust 7.1 roles and permissions apply to `web` users only (`role_user.user_type = App\Models\User`). Teachers, students and parents have no Laratrust roles.

### 2.2 Roles (`roles`, 10) and users assigned (`role_user`)

| id | name | users | program relevance |
|---|---|---|---|
| 1 | مدير النظام | 1 | everything, plus «إعدادات التحصيلي» (admin) and «إعدادات مستويات الأداء» (admin) |
| 2 | مشرف تعليمي | 6 | exams + questions CRUD (no delete) |
| 3 | وكيل مدرسة | 20 | school-scoped via `grades.supervisor_id`. No question permissions |
| 4 | مدخل أسئلة | 1 | question entry |
| 5 | مدير القسم | 18 | school-scoped via `grades.head_id` |
| 6 | وكيل مدرسة ثان | 1 | school-scoped via `grades.deputy_supervisor_id` |
| 7 | إسلاميات | 3 | islamic only |
| 8 | مدخل مهارات | 1 | skills entry, no reports |
| **9** | **قدرات لفظية** | 1 | qiyas verbal only (questions CRUD; sees only QL) |
| **10** | **قدرات كمية** | 1 | qiyas quant only (questions CRUD; sees only QQ) |

Permissions (68, `{create,read,update,delete}_{x}`). The ones that gate programs:
- `read_questions`: 1, 2, 4, 5, 6, 7, 9, 10. `create_questions`: 1, 2, 4, 6, 7, 9, 10. `update_questions`: 1, 2, 4, 6, 9, 10. `delete_questions`: 1, 4, 6, 9, 10.
- `read_exams` / `create_exams` / `update_exams` / `delete_exams`: 1, 2, 3, 5, 6.
- `read_results`: **no role has it**, so the «النتائج» menu entry is dead.
- There are **no program-specific permissions.** Program visibility is computed in Blade from role **names** plus the stage (`school_type_id`) of the schools the user supervises (§2.4). Controllers repeat similar checks; see the per-program sections.

### 2.3 How a teacher is tied to a program

There is no program column on `teachers`. A teacher "has" a program when `teacher_section_subject` contains a row with a subject of the program's `type`. `teachers.subject_id` exists but the sidebar does not use it.

Teacher sidebar `resources/views/layouts/main-sidebar/teacher-main-sidebar.blade.php:40-97`:
- `hasNafis` = `subjects()->where('type','nafis')`. Fallback: classroom name contains «الثالث ابتدائي» / «السادس ابتدائي» / «الثالث متوسط».
- `hasTahsili` = `type='tahsili'`. Fallback: «الثالث ثانوي».
- `hasQiyas` = `type in ('QL','QQ')`. No fallback.
- `hasIslamic` = types `islamic A/B/C`, or a subject name LIKE إسلام / اسلام / فقه / توحيد / تفسير / حديث / قرآن / قران / تجويد.
- **Gotcha:** the classroom-name fallbacks never match, because classrooms are named «الصف الثالث» without a stage.

Per-exam ownership is stored as `teacher_id` on `*_exam_subjects` (all three programs) and `*_exams.teacher_id` (qiyas, nafis), together with `created_by` / `created_by_type`.

### 2.4 Sidebar entries per role

**Admin / staff (`web`)**, from `resources/views/layouts/main-sidebar/admin-main-sidebar.blade.php`.

Settings group under «المدارس» (exact label `المدارس `, `:48`; `@if hasPermission('read_grades')`, `:44-157`):
- `:56-58` «إعدادات التحصيلي» → `tahsili.settings.admin`. Visible only when `hasRole('مدير النظام')`.
- `:60-107` «إعدادات التحصيلي» → `tahsili.settings.my`. Visible when the user has role مدير القسم or وكيل مدرسة **and every** active school they head or supervise is `school_type_id=3` (all secondary).
- `:110-112` «إعدادات مستويات الأداء» → `performance-levels.admin`. Visible only to مدير النظام.
- `:114-152` «إعدادات مستويات الأداء» → `performance-levels.supervisor`. Visible to مدير القسم or وكيل مدرسة with at least one active school.

Visibility flags computed at `:263-369`:
- `isQiyasVerbalRole` / `isQiyasQuantRole` / `isIslamicRole` come from the role names.
- For وكيل مدرسة / مدير القسم (only when the user is not islamic or qiyas): `showNafisExams` and `showQiyasExams` = the user's schools intersect stages {1,2}; `showTahsiliExams` = the schools include stage 3. A qiyas role forces nafis/tahsili off and qiyas on. The islamic role forces all three off.
- Every other role (مدير النظام, مشرف تعليمي, مدخل أسئلة, وكيل مدرسة ثان) keeps the defaults, so they see everything.

«الأسئلة والاختبارات» group (`:263-469`, needs `read_exams` or `read_questions`):

| line | label (exact) | route | condition |
|---|---|---|---|
| 382 | قائمة الاسئلة | questions.index | read_questions && !qiyasRole |
| 386 | قائمة أسئلة نافس | nafis-questions.index | read_questions && showNafisExams && !islamic && !qiyasRole |
| 394/396/398 | قائمة أسئلة القدرات اللفظية / قائمة أسئلة القدرات الكمية / قائمة أسئلة القدرات | qiyas-questions.index | read_questions && showQiyasExams && !islamic. Label depends on the qiyas role |
| 404 | قائمة أسئلة تحصيلي | tahsili-questions.index | read_questions && showTahsiliExams && !islamic && !qiyasRole |
| 411 | قائمة الاختبارات | exams.index | !islamic && !qiyasRole && read_exams |
| 414 | قائمة اختبارات نافس | nafis_exams.index | same as 411, plus showNafisExams |
| 418 | قائمة اختبارات تحصيلي | tahsili_exams.index | same as 411, plus showTahsiliExams |
| 424 | قائمة اختبارات إسلاميات | islamic_exams.index | !islamic && !qiyasRole (no permission check) |
| **437** | **قائمة اختبارات القدرات** | qiyas_exams.index | !islamic && !qiyasRole. **No `read_exams` check and no `showQiyasExams` check**: a secondary-only وكيل still sees it |
| 452 | عرض اختبارات المعلمين | admin.teacher-exams.index | !islamic && !qiyasRole |
| 460/462 | قائمة اختبارات القدرات اللفظية / قائمة اختبارات القدرات الكمية | qiyas_exams.index | qiyasRole && read_exams. **Roles 9/10 do not have read_exams**, so these never render |

«التقارير» group (`:586-716`). A commented-out duplicate of this block sits at `:545-586`.
- Hidden for إسلاميات, مدخل مهارات and both qiyas roles.
- `showNafisReports` = schools in stage {1,2}; `showTahsiliReports` = stage 3. وكيل مدرسة uses supervisor_id, وكيل مدرسة ثان uses deputy_supervisor_id, مدير القسم uses head_id.
- `:684` «تقارير الاختبارات» → reports.exam. `:685` «تقارير اختبارات المعلمين» → teacher-exams-reports.index.
- `:691/696` «تقارير نافس» › «تقارير اختبارات نافس» → reports.nafis.index.
- `:705/710` «تقارير تحصيلي» › «تقارير اختبارات تحصيلي» → tahsili-reports.index.
- **There is no qiyas report menu entry.**
- `:736` «إعدادات الأسابيع والفصول الدراسية» → weeks.index (مدير النظام).

**Teacher sidebar**, under «الاختبارات» (`teacher-main-sidebar.blade.php:28-133`):

| line | label | route | condition |
|---|---|---|---|
| 38 | قائمة الاختبارات | teacher_exams.index | always |
| 102 | اختبارات نافس | teacher.nafis_exams.index | hasNafis |
| 110 | اختبارات تحصيلي | teacher.tahsili_exams.index | hasTahsili |
| 119 | اختبارات القدرات | teacher.qiyas_exams.index | hasQiyas |
| 128 | اختبارات الإسلاميات | teacher.islamic_exams.index | hasIslamic |
| 151 | التقارير | teacher_exams.reports | always |

**Student sidebar** (`student-main-sidebar.blade.php:32-94`), driven by the student's `grade.school_type_id` and the classroom name:
- `:80` «اختبارات نافس» → student.nafis.exams.index. Shown when stage 1 and the class name contains «الثالث» or «3» or «السادس» or «6», or when stage 2 and the class name contains «الثالث» or «3».
- `:86` «اختبارات القدرات» → student.qiyas.exams.index. Shown for **every** class in stage 1 or 2.
- `:92` «اختبارات تحصيلي» → student.tahsili.exams.index. Shown when stage 3 and the class name contains «الثالث» or «3».

**Parent sidebar:** no program entries. Parent level tests (`ParentLevelTestController`) use **regular** subjects only (`:97-111` filters `s.type` NULL / '' / normal / regular), so they are **not part of this port**.

---

## 3. Qiyas («القدرات»: لفظي QL / كمي QQ)

### 3.0 Key facts
- **Subjects:** `subjects.id=21` «القدرات اللفظية» (`type='QL'`) and `subjects.id=22` «القدرات الكمية» (`type='QQ'`). The code filters by `type IN ('QL','QQ')`, never by id.
- **Skills:** 10 skills, all `term='first'`; the list is in §1.2. The 58 passage rows all sit on 6220 «استيعاب المقروء».
- **Bank:** 3,571 questions: 3,513 independent and 58 passages.
  - `term` is only `first` or `second`. `week` runs 1–19.
  - Difficulty for QL: easy 518 / medium 670 / hard 423. For QQ: easy 674 / medium 781 / hard 505.
  - 1,951 rows have an image. No passage has an image.
- **No qiyas import exists.** The import modal is commented out (and pointed at the generic `questions.import`), so every question was typed in one by one, between 2026-06-10 and 2026-10-22.
- **Runtime data:**

  | table | rows | notes |
  |---|---|---|
  | `qiyas_exams` | 43 | 40 created by teachers (all `single`), 3 by admin |
  | `qiyas_exam_subjects` | 45 | |
  | `qiyas_exam_sections` | 85 | |
  | `qiyas_exam_skill_distribution` | 163 | over 41 exam subjects; 2 subjects do not sum to 100 |
  | `qiyas_exam_student_questions` | 3,001 | |
  | `qiyas_exam_student_answers` | 2,700 | |
  | `qiyas_exam_student_results` | 209 | 176 completed (average 76.3%), 33 pending; `time_spent` is always 0 |

- **There are no qiyas reports**, no Excel export, and no performance levels. Results are only visible to the student.

### 3.A Routes

`RouteServiceProvider` loads `routes/*.php` under the `web` middleware only. In the table, "auth:web" means inside the group at `web.php:107-387`.

| file:line | method | URI | name | controller@method | guard |
|---|---|---|---|---|---|
| web.php:307 | GET | /qiyas-questions | qiyas-questions.index | QiyasQuestionController@index | auth:web |
| web.php:308 | GET | /qiyas-questions/create | qiyas-questions.create | @create | auth:web |
| web.php:309 | POST | /qiyas-questions | qiyas-questions.store | @store | auth:web |
| web.php:312-319 | GET/GET/PUT/DELETE/GET/POST | /qiyas-questions/{id}, /{id}/edit, /{id}, /{id}, /{id}/copy, /bulk-delete | .show .edit .update .destroy .copy .bulk-delete | **all 6 methods are missing** from the controller | auth:web |
| web.php:323 | POST | /qiyas/get-subjects | — | QiyasQuestionController@getSubjects, **shadowed by :577** | auth:web |
| web.php:324 | POST | /qiyas/get-skills | — | QiyasQuestionController@getSkills | auth:web |
| web.php:325 | GET | /qiyas/classrooms/{classroomId}/subjects | qiyas.get_subjects | @getQiyasSubjectsByClassroom | auth:web |
| web.php:339 | GET | /qiyas-passages | qiyas_passages.index | QiyasPassageQuestionController@index | auth:web |
| web.php:340 | GET | /qiyas-passages/create | qiyas_passages.create | @create | auth:web |
| web.php:341 | POST | /qiyas-passages | qiyas_passages.store | @store (JSON) | auth:web |
| web.php:342 | GET | /qiyas-passages/{id}/edit | qiyas_passages.edit | @edit | auth:web |
| web.php:343 | PUT | /qiyas-passages/{id} | qiyas_passages.update | @update (JSON) | auth:web |
| web.php:344 | DELETE | /qiyas-passages/{id} | qiyas_passages.destroy | @destroy | auth:web |
| web.php:349 | GET | /qiyas-passages/get-subjects/{classroomId} | qiyas_passages.get.subjects | @getQiyasSubjectsByClassroom (QL only) | auth:web |
| web.php:353 | GET | /qiyas-passages/get-skills | qiyas_passages.get.skills | @getSkillsForPassage | auth:web |
| web.php:357 | POST | /qiyas-passages/get-subjects-for-classrooms | qiyas_passages.get.subjects.for.classrooms | @getSubjectsForClassrooms | auth:web |
| web.php:560 | GET | /qiyas_exams | qiyas_exams.index | QiyasExamController@index | **none** |
| web.php:561 | GET | /qiyas_exams/create | qiyas_exams.create | @create | **none** |
| web.php:562 | POST | /qiyas_exams/store | qiyas_exams.store | @store | **none** |
| web.php:563 | DELETE | /qiyas_exams/{id} | qiyas_exams.destroy | @destroy | **none** |
| web.php:571 | GET | /qiyas/get-classrooms/{grade_id} | — | @getClassrooms | **none** |
| web.php:574 | GET | /qiyas/get-sections/{classroom_id} | — | @getSections | **none** |
| web.php:577 | POST | /qiyas/get-subjects | — | @getSubjects (**wins over :323**) | **none** |
| web.php:580 | GET | /qiyas/get-teachers | qiyas.get_teachers | @getTeachers | **none** |
| web.php:583 | GET | /qiyas/get-subject-skills/{subject_id} | qiyas.get_subject_skills | @getSubjectSkills | **none** |
| teacher.php:325 | GET | /teacherQiyas-exams | teacher.qiyas_exams.index | Teacher\dashboard\QiyasExamTeacherController@index | auth:teacher (group teacher.php:321-336) |
| teacher.php:326 | GET | /teacherQiyas-exams/create | teacher.qiyas_exams.create | @create | auth:teacher |
| teacher.php:327 | POST | /teacherQiyas-exams | teacher.qiyas_exams.store | @store | auth:teacher |
| teacher.php:328 | GET | /teacherQiyas-exams/{id} | teacher.qiyas_exams.show | @show | auth:teacher |
| teacher.php:329 | DELETE | /teacherQiyas-exams/{id} | teacher.qiyas_exams.destroy | @destroy | auth:teacher |
| teacher.php:332 | GET | /teacherQiyas-exams/get-sections/{classroom_id} | teacher.qiyas_exams.get_sections | @getSections | auth:teacher |
| teacher.php:333 | GET | /teacherQiyas-exams/get-subject-skills/{subject_id} | teacher.qiyas_exams.get_subject_skills | @getSubjectSkills | auth:teacher |
| student.php:279 | GET | /student/student-qiyas-exams | student.qiyas.exams.index | Students\dashboard\StudentQiyasExamController@index | auth:student (group student.php:276) |
| student.php:283 | GET | /student/qiyas-exams/{exam_id} | student.qiyas.exams.show | @show | auth:student |
| student.php:287 | GET | /student/qiyas-exams/{exam}/continue | student.qiyas.exams.continue | @continueExam | auth:student |
| student.php:291 | GET | /student/qiyas-exams/subject/start/{subject_id} | student.qiyas.exams.subject.start | @startSubject | auth:student |
| student.php:295 | POST | /student/qiyas-exams/answer | student.qiyas.exams.answer | @submitAnswer (AJAX) | auth:student |
| student.php:299 | POST | /student/qiyas-exams/finish/{subject_id} | student.qiyas.exams.subject.finish | @finishSubject | auth:student |
| student.php:303 | GET | /student/qiyas-exams/detailed-result/{subject_id} | student.qiyas.exams.detailed-result | @detailedResult | auth:student |
| student.php:307 | GET | /student/qiyas-exams/get-next-subject/{currentSubjectId} | student.qiyas.exams.get-next-subject | @getNextSubject | auth:student |
| student.php:312 | GET | /student/qiyas-exams/check-next-subject/{currentSubjectId} | student.qiyas.exams.check-next-subject | @checkNextSubject | auth:student |
| student.php:317 | GET | /student/qiyas-exams/{exam}/final-result | student.qiyas.exams.final-result | @showFinalResult | auth:student |

`parent.php` has no qiyas routes. No qiyas route uses permission or role middleware. All gating happens in controllers and the sidebar.

### 3.B Controllers

**`app/Http/Controllers/QiyasQuestionController.php`** (380 lines)
- `getAllowedSubjectTypes()` (:36, private) maps role to subject types: «مدير النظام» and «مدخل مهارات» → QL+QQ; «قدرات لفظية» → QL; «قدرات كمية» → QQ; any other role → both.
- `index` (:59) lists non-passage qiyas questions of the allowed types.
  - Filters: `search` (strips diacritics; also matches the skill name), `classroom_id` (via pivot), `subject_id`, `term` (question term OR skill term).
  - Paginated 10 per page.
  - The classroom filter keeps stages 1/2 whose class name matches الثالث/3/السادس/6/الأول/1… (:117-139).
- `create` (:157) loads the allowed subjects, all classrooms, active complexes with their grades and classrooms, and the skills of the allowed subjects.
- `store` (:176) creates one independent question. Details in 3.C.
- `getSubjects` (:289) is dead: its route is shadowed.
- `getSkills` (:335) returns skills by subject + classrooms through `skill_classroom`. It requires `term` but never filters on it.
- `getQiyasSubjectsByClassroom` (:362) returns subjects of a classroom, limited to the allowed types.
- **There is no show/edit/update/destroy/copy/bulkDelete.** The index view links to the generic `questions.show`, `questions.edit` and `questions.deleteMultiple` instead (`Pages/qiyas_questions/index.blade.php:356-361, 459`).

**`QiyasPassageQuestionController.php`** (746 lines)
- `index` (:17) lists passages only.
  - Filters: search on passage text or skill name, classroom, subject, term, difficulty, week.
  - Paginated 10 per page.
- `create` (:227) offers subjects with `type='QL'` only, and skills whose name is LIKE «استيعاب المقروء».
- `store` (:241) returns JSON.
  - Validates `term` first/second/**third**, `week` 1–19, `skill_id`, `difficulty`, `passage_text`, `questions[*][question_text]` (max 255), `questions[*][options][]`, `questions[*][correct_option]`, `Class_id[]`.
  - The skill must contain «استيعاب المقروء» (:274) and the subject must be QL (:283).
- `edit` (:363) rebuilds the form from the parallel `question_text[]` / `options[][]` arrays.
- `update` (:433) returns JSON. It accepts an image via `Storage::disk('public')->store('questions')`, which is a **different path scheme** from independent questions.
- `destroy` (:554) deletes the image from the public disk, detaches classrooms, then deletes the row.
- `getSubjectsForClassrooms` (:594), `getQiyasSubjectsByClassroom` (:684, QL only) and `getSkillsForPassage` (:703: the subject's «استيعاب المقروء» skills linked to the classrooms) feed the form dropdowns.

**`QiyasExamController.php`** (admin/staff, 623 lines)
- `index` (:25) scopes exams through `buildQiyasExamQuery` (:79). The table below shows which exams each role sees.

  | role | filter |
  |---|---|
  | مشرف تعليمي | `complexes.supervisor_id` = user |
  | وكيل مدرسة | `grades.supervisor_id` = user |
  | وكيل مدرسة ثان | `grades.deputy_supervisor_id` = user |
  | مدير القسم | `grades.head_id` = user |
  | معلم | `created_by` = user |
  | anyone else | all exams |

  - Results are always limited to active grades with `school_type_id IN (1,2)` and active complexes.
  - Filters: `complex_id`, `grade_id`, `search` (title), `sort` (newest/oldest). Paginated 10.
- `create` (:189) loads grades and classrooms with the same scoping, plus QL/QQ subjects linked to stage-1/2 classrooms.
- `store` (:338) is described in 3.D.
- `getSubjectSkills` (:455) returns skills via `skill_classroom` for the classroom, falling back to all the subject's skills. Each skill carries a `questions_count` of qiyas questions.
- `getClassrooms` (:505) and `getSections` (:556) are simple lookups.
- `getSubjects` (:517) returns QL/QQ subjects for the chosen classrooms (stages 1/2), minus `exclude_subject_ids`.
- `getTeachers` (:565) returns teachers from `teacher_section_subject` matching subject, classroom and sections.
- `destroy` (:590) runs `FOREIGN_KEY_CHECKS=0`, then deletes distribution, subjects, sections and the exam. **Student questions, answers and results are left behind as orphans.**

**`Teacher/dashboard/QiyasExamTeacherController.php`** (411 lines)
- `index` (:24) lists exams where `teacher_id` = me, or `created_by` = me with `created_by_type='teacher'`.
- `create` (:45) loads the teacher's sections from `teacher_section_subject`, their grades and classrooms, and the teacher's QL/QQ subjects. If either list is empty it redirects with a warning.
- `store` (:96) is described in 3.D.
- `show` (:231) shows the exam config: sections, subjects, skill distribution. **No student results.**
- `destroy` (:258) deletes manually in order: answers → student questions → results → distribution → subjects → sections → exam.
- `getSections` (:325) and `getSubjectSkills` (:341) feed the form. The skills endpoint only returns skills that have questions, counting both `skill_id` matches and `skills LIKE '%id%'`.

**`Students/dashboard/StudentQiyasExamController.php`** (878 lines) is described in 3.D and 3.E.

**Elsewhere:**
- `SkillController` has a «القدرات فقط» filter (`filter_type=qiyas`, at :144, :336, :794, :875).
- `SkillController:256` and `:666` check a role named «قدرات» that **does not exist**.

### 3.C Question CRUD and encoding

**Independent question** (`QiyasQuestionController@store`, :176-270)
- Validation (:184): `question_text[]` (max 255), `term` (first/second/both), `week` 1–19, `skill_id`, `difficulty`, `type`, `Class_id[]`, optional image. For MCQ it also needs `options[i][option_text]` and the `correct_option` index.
- The subject type is checked against the allowed types (:199).
- What gets written:
  - `exam_type='qiyas'`.
  - `question_text`: the model mutator wraps the text in a JSON array (e.g. `["كتاب : ورقة"]`).
  - `options`: `[{text, is_correct: index==correct_option}]` (:247-254).
  - For true/false questions: `correct_answer=bool` and `options=null`. No qiyas row actually uses this.
- **Image** (:231-243): saved to `base_path('uploads/questions')` as `question_<time()>_<uniqid()>.<ext>`. The DB stores `uploads/questions/<name>`, and the page renders it with `asset()`.
- **Fingerprint** (:261): `md5(json_encode(['question_text'=>accessor output, 'term','subject_id','week','skill_id']))`.
  - The accessor output is already `nl2br`'d.
  - The **image, options and exam_type are not part of the hash.**
- **Classrooms:** saved with `$question->classrooms()->sync(Class_id[])`.

**Passage** (`QiyasPassageQuestionController@store`, :300-341)
- One `questions` row per passage, with `is_independent='passage'` and `type='multiple_choice'`.
- `passage_text` holds plain text. `question_text` is a JSON array of sub-question stems. `options` is a JSON array of arrays, one per sub-question.
- One `skill_id` covers the whole passage. The `skills` column stays NULL.
- Fingerprint: `md5(json_encode(['question_text'=>array,'exam_type'=>'qiyas','term','subject_id','week','skill_id']))` (:331). The key order differs from the independent-question formula.

**Editing an independent question** goes through the generic `QuestionController@edit` / `@update` (:300, :327).
- Its skill list comes from `skill_weeks`, so a qiyas skill is only offered if it has `skill_weeks` rows.
- It recomputes the fingerprint with the same formula.
- After saving it **redirects to `questions.index`**, the regular bank, not the qiyas list.

### 3.D Exam creation and question picking

**Admin form** (`Pages/qiyas_exams/create.blade.php`, uses select2 and SweetAlert2)

| field | label | notes |
|---|---|---|
| title | «عنوان الاختبار» | |
| type | «نوع الاختبار» | `single` «📘 مادة واحدة» / `multi` «📚 عدة مواد» |
| grade | «المدرسة» | |
| classroom | «الصف الدراسي» | |
| sections | «الفصول المستهدفة» | checkboxes |
| expiry_hours | «مدة صلاحية الاختبار» | default 24, 1–720, unit «ساعة» |

Each repeatable subject row has: «المادة», «المعلم», «المدة», «عدد الأسئلة», «الترتيب», «الفصل الدراسي» (first «الأول» / second «الثاني»), and «الأسابيع الدراسية» (checkboxes «الأسبوع 1..19»).

Under each subject, «توزيع الأسئلة حسب المهارات» asks for a percentage per skill and shows «المجموع» and «المتبقي» badges.

**Admin store** (`QiyasExamController@store`, :338-450)
- Rejects a subject with no weeks, or with skill percentages that sum to more than 100 (:361-378).
- Sets `expires_at = now()->addHours(expiry_hours)` (:383, UTC).
- Writes:
  - the exam row with `created_by=Auth::id()`; `created_by_type` stays at the DB default `'admin'`;
  - one row per section;
  - one `qiyas_exam_subjects` row per subject (with `term`, `weeks` as a JSON int array, `teacher_id`, `order_index`);
  - distribution rows for every non-empty skill/percentage pair.

**Teacher store** (`QiyasExamTeacherController@store`, :96-226)
- Single subject only. Duration 15–180 minutes, 1–100 questions, expiry 1–168 hours.
- The subject must belong to the teacher in `teacher_section_subject` and be QL/QQ (:123). Every section must be the teacher's (:135).
- Writes `created_by=NULL`, `teacher_id=<teacher>`, `created_by_type='teacher'`, `type='single'`.
- The over-100% check runs **after** the inserts, followed by a rollback (:204).

**Question picking is lazy and per student.** Nothing is generated when the exam is created. The first time a student opens a subject, `startSubject` (:147) calls `generateMixedQuestions` (:564), which calls `generateIndependentQuestions` (:601):
1. Load the distribution map `skill_id → %`. If it is empty, use every subject skill linked to the student's classroom, split equally (:626-641).
2. For each skill, `count = max(1, round(pct / Σpct × total_questions))`. The rounding difference goes to the first skill (:647-658).
3. For each skill, pick `count` questions at random (`inRandomOrder()->take(count)`, :679-692). Conditions: `exam_type='qiyas'`, same `subject_id` and `skill_id`, `term` = the subject's term, `week IN weeks`, linked through the pivot to the student's `Classroom_id`, and not already picked. If that pool is short, fallbacks apply in order:
   1. Any classroom of the same grade (:704). This is **buggy**: it uses `$classroom->grade_id`, but the column is `Grade_id`, so the value is null and the fallback finds nothing.
   2. Drop the week filter (:739).
   3. Top up from any skill of the subject with the same term and classroom (:781).
4. **Difficulty is never used.**
5. Each picked question is snapshotted by `storeIndependentQuestion` (:816):
   - `question_text` = the **first stem only**;
   - `options_json` = `json_encode(options)`, which is then double-encoded by the model's `array` cast;
   - `correct_answer` = the **0-based index** of the correct option (`extractCorrectAnswer`, :865);
   - `sub_index=0`, `question_type='independent'`, `skill_id`, and a sequential `order_index`.
6. Question order is random. **Options are not shuffled.**

**Passages are not handled.** Passage rows (skill 6220) are snapshotted as if they were independent: first sub-question only, nested options, `correct_answer` forced to 0, no passage text. There are **176 such broken snapshots**.

**Visibility:** a student sees an exam when their `section_id` is in `qiyas_exam_sections` (`index`, :29; `validateExamAccess`, :390).

### 3.E Student taking the exam

**List** (`index`, :25)
- Exam cards (10 per page) showing `has_taken`, `is_expired` (`expires_at < now`) and completed/total subjects.
- For multi-subject exams, an overall % (`calculateExamOverallResult`, :397) = Σ obtained ÷ Σ total × 100.

**Show** (`show`, :74)
- Subject cards in `order_index` order.
- A subject stays locked until the previous one is completed, but only while the exam has not expired (:553-563).
- Expiry only hides the start button in the view (:571).

**Start** (`startSubject`, :119)
- Checks only that the student is in one of the exam's sections. **Expiry is not checked on the server.**
- `firstOrCreate`s a result row (`pending`, `started_at=now`). If the row is already completed, it shows «لقد أنهيت هذه المادة بالفعل».
- Generates the questions if there are none yet.

**Taking** (`Students/QiyasExams/start.blade.php`)
- One question per screen, with previous/next buttons and group dots.
- Images go through `asset()`. Each question also triggers its own `Question::find` (N+1 queries).
- Answers are radio buttons whose value is the option index.
- **The timer is client-side only** (:945-990): a `setInterval` counting down from `duration_minutes`. It resets on page reload, warns at 5 and 1 minutes, and calls `autoFinishExam()` at 0.
- A `beforeunload` warning stops the student leaving by accident.
- The so-called "auto-save" (:1306) only writes to `console.log` every 30 s.

**Saving answers**
- Every radio change POSTs to `submitAnswer` (:166).
- `is_correct = (answer == correct_answer)`, `score` = 1 or 0.
- The row is saved with `updateOrCreate` keyed on (question, student).

**Finish** (`finishSubject`, :204)
- Triggered by the SweetAlert «تأكيد إنهاء الاختبار» dialog, or automatically when the timer runs out.
- Score:
  - `total_score = 100`
  - `obtained_score = round(correct / generated_count × 100, 2)` (`calculatePercentageScore`, :422)
- Sets `status=completed` and `ended_at`.
- The posted `time_spent` and the `auto_finished` status are **ignored**.
- Then `get-next-subject` moves on to the next subject, or back to the list.

**Results**
- `detailedResult` (:325, `subject_result.blade.php`): score, %, and a per-question review that highlights the correct option. A JS message appears at ≥80 and ≥60.
- `showFinalResult` (:270, `final-result.blade.php`): overall % = Σ obtained ÷ Σ total × 100. A `single` exam uses only its first result.

### 3.F Teacher side
- A teacher sees «اختبارات القدرات» when `teacher_section_subject` links them to a QL or QQ subject.
- Teachers can only create, list, view the config of, and delete their own exams.
- **There is no monitoring or results screen** for teachers.
- There is no verbal/quant split for teachers. The subject dropdown simply lists whichever QL/QQ subjects the teacher teaches.

### 3.G Reports
**None.** No qiyas report controller, Excel export, chart, or performance-level use exists. The reports sidebar is hidden from both qiyas roles.

### 3.H Views

All extend `layouts.master`. Libraries come from the master layout (jQuery 3.3.1, Bootstrap 4, DataTables, select2 4.1, SweetAlert2 11, toastr). Qiyas uses **no chart library**.

| view | UI |
|---|---|
| Pages/qiyas_questions/index | Filters (search, classroom, subject, term). Table columns: #، المادة، نص السؤال، مهارة السؤال، الفصل الدراسي، الأسبوع، مستوى الصعوبة، نوع السؤال، الصفوف، الإجابة الصحيحة، إجراءات. Options modal; bulk delete through generic `questions.deleteMultiple` (toastr); commented-out Excel import modal; button to the passages list; `links()` pagination |
| Pages/qiyas_questions/create | Classroom checkboxes grouped by complex → subject (AJAX) → term → week 1–19 → skill (AJAX) → difficulty → type → image → text → 4 options with a correct radio. AJAX submit with SweetAlert2 |
| Pages/qiyas_questions/edit | **Empty file (0 bytes)** |
| Pages/QiyasPassages/index | Filters (search, classroom, subject, term, difficulty, week). Table columns: #، القطعة، المادة، الأسئلة والخيارات، المهارات، الفصل الدراسي، الأسبوع، مستوى الصعوبة، عرض الصفوف، الاجراءات |
| Pages/QiyasPassages/create, edit | «إضافة سؤال قطعة - القدرات» / «تعديل سؤال قطعة - القدرات». Passage textarea plus repeatable sub-questions with options; AJAX JSON submit |
| Pages/qiyas_exams/index | Filters (complex, grade, search, sort). Table columns: #، عنوان الاختبار، المدرسة، نوع المدرسة، الصف، نوع الاختبار، المنشئ، تاريخ الإنشاء، الإجراءات. Delete only; no show or edit |
| Pages/qiyas_exams/create | «إنشاء اختبار قدرات». Repeatable subject blocks with a skill % table |
| Teachers/dashboard/qiyas_exams/index | Table columns: عنوان الاختبار، المادة، الصف / الفصول، الأسئلة، تاريخ الإنشاء، الصلاحية، الحالة، الإجراءات. Delete modal |
| Teachers/dashboard/qiyas_exams/create | Panels: «معلومات الاختبار»، «المدرسة والصف»، «الفصول المستهدفة»، «المادة الدراسية»، «تفاصيل الاختبار»، «الفصل الدراسي والأسابيع»، «توزيع المهارات» |
| Teachers/dashboard/qiyas_exams/show | Config cards (duration, count, skills) plus «منطقة الإجراءات» with delete |
| Students/QiyasExams/index | Exam cards: expired / taken / new, expiry date, overall % |
| Students/QiyasExams/show | Ordered subject cards with locked, expired and completed states |
| Students/QiyasExams/start | One question per screen, timer, group dots, zoomable image, radio options |
| Students/QiyasExams/subject_result | Score card plus per-question review |
| Students/QiyasExams/final-result | Overall score; per-subject tiles for multi exams |
| Students/QiyasExams/exam_result, detailed_result | **Unused.** `detailed_result` is a standalone printable page |

### 3.I Gotchas (qiyas)
1. **Exam routes have no authentication.** The `qiyas_exams.*` routes and the `/qiyas/*` lookups (`web.php:559-583`) are outside every auth group.
   - A guest who opens `index` or `create` gets a 500, from `auth()->user()->hasRole`.
   - `store`, `destroy` and the lookups (including `getTeachers`) work without logging in.
2. **Duplicate `POST /qiyas/get-subjects`** (`web.php:323` and `:577`). The last one wins, so the question-create form ignores the verbal/quant restriction in its subject dropdown. `store` still re-checks it.
3. **Six `qiyas-questions.*` routes point to methods that do not exist**, and `edit.blade.php` is an empty file. Editing goes through the generic question bank and returns there afterwards.
4. **Student snapshots break passage questions**: only the first sub-question is kept, there is no passage text, and `correct_answer` is 0. There are 176 such rows.
5. **The correct answer is stored as an option index**, and options are never shuffled.
6. **Time and expiry are not enforced on the server.** The timer is client-only, and `time_spent` / `auto_finished` are never written.
7. **Admin `destroy` disables FK checks** and leaves student data orphaned.
8. The role «معلم» branch uses `Teacher::where('user_id', …)` (`QiyasExamController:266`). `teachers` has no `user_id` column, so this would throw.
9. **The `Grade_id` vs `grade_id` mix-up** breaks the same-grade fallback (`StudentQiyasExamController:713`).
10. **Questions with `term='both'` can never be picked**, because the filter is `term = subject.term`. Passage validation also accepts `third`.
11. **Fingerprint is not unique.** It ignores the image and options; for example «اختر» appears 506 times with different images.
12. **`options_json` in the snapshot table is double-encoded**, and the views decode it twice.
13. **Hard-coded values:** school types `[1,2]` (`QiyasExamController:38`, …); the classroom-name LIKE lists (`QiyasQuestionController:125-136`); the «استيعاب المقروء» skill name; passage subject restricted to `QL`; and the non-existent «قدرات» role in `SkillController`.
14. **Two image path schemes:** independent questions use `base_path('uploads/questions')`, while passage update uses `Storage::disk('public')`.
15. **UTC `expires_at`** is shown without conversion to KSA time.
16. **Wrong id in a link:** `final-result.blade.php:460` passes the exam id to `detailed-result`, which expects an exam-subject id.
17. **Distribution may total less than 100%** (`≤100` is accepted). The proportional formula absorbs the gap.

---

## 4. Tahsili («تحصيلي»)

### 4.0 Key facts
- **Subjects:** 62 «تحصيلي "فيزياء"», 63 «كيمياء», 64 «أحياء», 65 «رياضيات» (all `type='tahsili'`).
- **Skills:** none. The question bank has no skill data at all.
- **Questions:** 2,704 in total, all independent multiple-choice. 2,702 of them are **an image plus a generic stem** («اختر الإجابة الصحيحة» or a variant) with options «أ/ب/ج/د». The question content lives in the image.
- **Grade level:** classrooms 21, 24, 45, 48, 93 and 96, all «الصف الثالث» in the 6 secondary schools.
- **Question selection:** an exam picks questions by **easy/medium/hard %** plus a **`question_order` range** (`start_question_id..end_question_id`, which are question_order numbers despite the column names). There is no classroom filter at generation time.
- **Expiry never works.** `TahsiliExam::$fillable` omits `expiry_hours` and `expires_at`, so all 198 exams have `expires_at=NULL` and never expire.
- **No tahsili import exists.** The «استيراد أسئلة من Excel» button is commented out (`Pages/tahsili_questions/index.blade.php:57`). The leftover modal points at the generic `questions.import`.

### 4.A Routes
The `auth:web` group covers only `web.php:107-387`. Every tahsili admin route in `web.php` sits after it.

**Admin / supervisor** (`routes/web.php`, `web` guard):

| line | method | URI | name | controller@method | middleware |
|---|---|---|---|---|---|
| 505 | GET | /tahsili-questions | tahsili-questions.index | TahsiliQuestionController@index | **none** |
| 506 | GET | /tahsili-questions/create | tahsili-questions.create | @create | none |
| 507 | POST | /tahsili-questions/store | tahsili-questions.store | @store | none |
| 509 | GET | /tahsili-questions/{question}/edit | tahsili-questions.edit | @edit | none |
| 510 | PUT | /tahsili-questions/{question}/update | tahsili-questions.update | @update | none |
| 511 | DELETE | /tahsili-questions/{question}/destroy | tahsili-questions.destroy | @destroy | none |
| 513 | POST | /tahsili-questions/get-subjects | tahsili-questions.getSubjects | @getSubjects | none |
| 514 | POST | /tahsili-questions/get-skills | tahsili-questions.getSkills | @getSkills (unused) | none |
| 515 | GET | /tahsili-questions/count | tahsili-questions.count | @getQuestionCount | none |
| 521 | GET | /tahsili_exams | tahsili_exams.index | TahsiliExamController@index | none |
| 522 | GET | /tahsili_exams/create | tahsili_exams.create | @create | none |
| 523 | POST | /tahsili_exams/store | tahsili_exams.store | @store | none |
| 524 | GET | /tahsili_exams/{id} | tahsili_exams.show | @show (view is 0 bytes) | none |
| 526 | DELETE | /tahsili_exams/tahsili-exams/{id} | tahsili_exams.destroy | @destroy | none |
| 528 | GET | /tahsili/get-classrooms/{grade_id} | – | @getClassrooms | none |
| 529 | GET | /tahsili/get-sections/{classroom_id} | – | @getSections | none |
| 531 | GET | /tahsili/get-subjects/{grade_id}/{classroom_id} | – | @getSubjects | none |
| 532 | GET | /tahsili/get-teachers | tahsili.get_teachers | @getTeachers | none |
| 535 | GET | /get-question-range | tahsili.get_question_range | @getQuestionRange | none |
| 538 | GET | /count-questions-in-range | tahsili.count_questions_in_range | @countQuestionsInRange | none |
| 541 | GET | /find-question | tahsili.find_question | @findQuestion | none |
| 544 | GET | /preview-questions | tahsili.preview_questions | @previewQuestions | none |
| 547 | GET | /suggest-range | tahsili.suggest_range | @suggestRange | none |
| 614 | GET | /tahsili-settings/admin | tahsili.settings.admin | TahsiliSettingsController@adminSettings | auth |
| 615 | POST | /tahsili-settings/global | tahsili.settings.global | @saveGlobal | auth |
| 616 | POST | /tahsili-settings/school | tahsili.settings.school | @saveSchool (AJAX) | auth |
| 619 | GET | /tahsili-settings/my-school | tahsili.settings.my | @supervisorSettings | auth |
| 620 | POST | /tahsili-settings/school/update | tahsili.settings.school.update | @updateSchoolSettings | auth |
| 741 | GET | /tahsili-reports | tahsili-reports.index | Reports\TahsiliReportController@index | auth |
| 742 | POST | /tahsili-reports/generate | tahsili-reports.generate | @generateReport (returns HTML partial) | auth |
| 746 | GET | /reports/tahsili/export-excel | reports.tahsili.export-excel | @exportExcel | **none** |
| 815 | GET | /tahsili/get-exams-by-filters | tahsili.get-exams-by-filters | inline closure (`web.php:815-852`) | none |

Performance-level routes (`type=tahsili`) are listed in §1.4.

**Teacher** (`routes/teacher.php`, group `auth:teacher` `:35-256`; URIs have **no** `/teacher` prefix). All rows use `Teacher\dashboard\TahsiliExamController`.

| line | method | URI | name | method |
|---|---|---|---|---|
| 202 | GET | /tahsili-exams | teacher.tahsili_exams.index | index |
| 206 | GET | /tahsili-exams/create | teacher.tahsili_exams.create | create |
| 208 | POST | /tahsili-exams/store | teacher.tahsili_exams.store | store |
| 212 | GET | /tahsili-exams/{id} | teacher.tahsili_exams.show | show |
| 216 | DELETE | /tahsili-exams/{id} | teacher.tahsili_exams.destroy | destroy |
| 220 | GET | /tahsili-exams/{id}/results | teacher.tahsili_exams.results | results |
| 222 | GET | /tahsili-exams/{id}/statistics | teacher.tahsili_exams.statistics | statistics (**view missing**) |
| 224 | GET | /tahsili-exams/{id}/download-results | teacher.tahsili_exams.download.results | downloadResults (CSV) |
| 232 | GET | /tahsili-exams/get-classrooms/{grade_id} | teacher.tahsili_exams.get_classrooms | getMyClassrooms |
| 234 | GET | /tahsili-exams/get-sections/{classroom_id} | teacher.tahsili_exams.get_sections | getMySections |
| 236 | GET | /tahsili-exams/get-subjects/{classroom_id} | teacher.tahsili_exams.get_subjects | getMySubjects |
| 242 | GET | /tahsili-exams/find-question | teacher.tahsili_exams.find_question | findQuestion (**unreachable**: `/{id}` at :212 catches it) |
| 244 | GET | /tahsili-exams/preview-questions | …preview_questions | previewQuestions (**unreachable**, same reason) |
| 246 | GET | /tahsili-exams/suggest-range | …suggest_range | suggestRange (**unreachable**, same reason) |
| 251 | GET | /tahsili-exams/get-school-weeks/{gradeId} | …get_school_weeks | getSchoolWeeks |
| 257 | GET | /teacher_tahsili_exams/count-bank-questions | teacher.tahsili_exams.count_bank_questions | countBankQuestions (outside the auth group, `withoutMiddleware(VerifyCsrfToken)`; checks the teacher guard itself) |

**Student** (`routes/student.php:342-375`, prefix `student`, `auth:student`). All rows use `StudentTahsiliExamController`.

| line | method | URI | name | method |
|---|---|---|---|---|
| 344 | GET | /student/student-tahsili-exams | student.tahsili.exams.index | index |
| 347 | GET | /student/tahsili-exams/{exam_id} | student.tahsili.exams.show | show |
| 350 | GET | /student/tahsili-exams/{exam}/continue | student.tahsili.exams.continue | continueExam |
| 353 | GET | /student/tahsili-exams/subject/start/{subject_id} | student.tahsili.exams.subject.start | startSubject |
| 356 | POST | /student/tahsili-exams/answer | student.tahsili.exams.answer | submitAnswer |
| 359 | POST | /student/tahsili-exams/finish/{subject_id} | student.tahsili.exams.subject.finish | finishExam |
| 362 | GET | /student/tahsili-exams/subject/result/{subject_id} | student.tahsili.exams.subject.result | subjectResult |
| 365 | GET | /student/tahsili-exams/{exam}/final-result | student.tahsili.exams.final-result | showFinalResult |
| 367 | GET | **/student/student/tahsili-exams/check-next-subject/{id}** (prefix doubled) | student.tahsili.exams.check-next-subject | checkNextSubject |
| 370 | GET | /student/tahsili-exams/detailed-result/{subject_id} | student.tahsili.exams.detailed-result | detailedResult |
| 372 | GET | **/student/student/tahsili-exams/get-next-subject/{id}** (prefix doubled) | student.tahsili.exams.get-next-subject | getNextSubject |

Controller methods that have no route:
- Student controller: `restartSubject`, `getExamStatistics`, `saveProgress`, `getQuestionInfo`.
- Admin exam controller: `studentResults`, `statistics`, `toggleStatus`, `studentQuestions`.

### 4.B Controllers

**`app/Http/Controllers/TahsiliQuestionController.php`** (question bank)
- `index` (84) lists non-passage tahsili questions.
  - Search ignores tashkeel (`prepareSearchText`, :16).
  - Filters: classroom (pivot), subject, term.
  - Sorted by subject_id, then question_order. 50 per page, with a computed `serial_number`.
- `create` (173) loads tahsili subjects and active complexes. Schools are filtered by name LIKE ثانوية/ثانوي and classrooms by name LIKE الثالث.
- `store` (334) and `update` (697) are described in 4.C.
- `edit` (488): for a true/false question with no options, it builds صح/خطأ options from `correct_answer`.
- `destroy` (905) deletes the image file, detaches classrooms, deletes the row, then closes the order gap by decrementing `question_order` on later rows.
- `getSubjects` (952) returns tahsili subjects for the chosen classrooms (via `classroom_subject`).
- `getSkills` (965) exists but is unused.
- `getQuestionCount` (982) returns `MAX(question_order)` for the subject.

**`app/Http/Controllers/TahsiliExamController.php`** (admin, supervisor and school roles)
- `index` (64): 10 per page. Filters: complex, school (`school_type_id` 3/4), search, sort (latest|oldest). Role scoping is at :101-125 (see 4.G).
- `create` (261) loads schools and third-grade classrooms by role, plus tahsili subjects.
- `store` (508) is described in 4.D.
- Lookups:
  - `getClassrooms` (655): classrooms named LIKE الثالث.
  - `getSubjects` (670): via `classroom_subject`, falling back to all tahsili subjects.
  - `getSections` (707).
  - `getTeachers` (714): from `teacher_section_subject`.
- `destroy` (737) sets `FOREIGN_KEY_CHECKS=0`, then deletes answers → questions → results → subjects → sections → exam.
- `show` (793) renders an empty view.
- Range helpers `getQuestionRange` / `countQuestionsInRange` / `findQuestion` / `previewQuestions` / `suggestRange` (935-1092) use **`questions.id` and `questions.classroom_id`**. This is broken; see 4.J item 1.

**`Teacher/dashboard/TahsiliExamController.php`** (teacher guard)
- `index` (49) lists exams where any `tahsili_exam_subjects.teacher_id` is this teacher.
- `create` (140) loads the teacher's tahsili subjects and الثالث classrooms from `teacher_section_subject`, plus `weeksCount` and a per-school weeks map.
- `store` (219) is described in 4.D.
- `show` (335) is allowed for the creator or a subject teacher. It includes `getExamStatistics` (978).
- `destroy` (365) is creator-only and cascades like the admin version.
- `results` (427) returns completed results for this teacher's subjects, grouped by student.
- `statistics` (470) computes per-subject stats, a grade distribution and the top 5. Its view does not exist.
- `downloadResults` (550) exports CSV (see 4.H).
- Lookups: `getMyClassrooms` (645), `getMySections` (703), `getMySubjects` (732).
- Range helpers (758-1022) are the same as admin's, with a `teacher_section_subject` permission check added.
- `getSchoolWeeks` (1024) returns the school setting, else the global setting, else 10.
- `countBankQuestions` (1046) counts subject + tahsili questions linked to the classroom through the pivot.

**`app/Http/Controllers/TahsiliSettingsController.php`**: `adminSettings` (14), `saveGlobal` (35), `saveSchool` (75), `supervisorSettings` (95), `updateSchoolSettings` (129), and a static `getSchoolWeeks` (150) that is unused. See 4.F.

**`Students/dashboard/StudentTahsiliExamController.php`**: `index` (23), `show` (121), `showFinalResult` (135), `startSubject` (194), `finishExam` (384), `submitAnswer` (433), `subjectResult` (513), `detailedResult` (644), `getNextSubject` (753), `checkNextSubject` (779), `continueExam` (885). See 4.E.

**`Reports/TahsiliReportController.php`**: `index` (22), `generateReport` (70), `exportExcel` (565). See 4.H.

### 4.C Question CRUD and encoding

**Store validation** (`TahsiliQuestionController.php:344-361`)

| field | rule |
|---|---|
| `question_text` | required, max 5000 |
| `term` | first / second / both |
| `subject_id` | required |
| `difficulty` | required |
| `type` | multiple_choice / true_false |
| `Class_id[]` | required, min 1 |
| `question_image` | image |
| `options[i][option_text]` | multiple choice: min 2 options |
| `correct_option` | multiple choice: index of the correct option |
| `correct_answer` | true/false: true / false |

`update` names the option field `options[i][text]` and limits the image to jpeg/png/jpg/gif up to 2 MB (`:716-722`).

**Form labels** (`Pages/tahsili_questions/create.blade.php`): «اختر الصفوف:», «المادة:», «الفصل الدراسي:», «مستوى الصعوبة:», «نوع السؤال:», «رفع صورة (اختياري):», «أضف السؤال بعد رقم:», «نص السؤال:», «الخيارات:», «الإجابة الصحيحة:». The form submits via AJAX.

**Encoding**
- Text, options and classroom pivot follow the same rules as qiyas (§1.1 and §3.C).
- `skill_id`, `skills`, `week`, `classroom_id` and `correct_answer` are all NULL.

**Image**
- Saved to `base_path('uploads/questions/tahsili')` as `tahsili_<time>_<uniqid>.<ext>` (`:367-376`).
- Shown with `asset()`.

**Fingerprint** (`:389-393`, same in update `:788-792`)
- `md5(json_encode(['question_text'=>[...], 'term', 'subject_id']))`.
- Because the stem is almost always the same generic text, the whole bank collapses into **14 fingerprints**, one per (subject, stem variant, term).
- Do not use it for dedupe. Hash the image plus the options instead.

**`question_order`** is the 1..N number per subject that admins see. Teachers type it into the exam range fields.
- **Store:** with `insert_after=X`, every row whose order is greater than X shifts up by one and the new row gets X+1. Without it, the new row gets MAX+1 (`:396-416`).
- **Update:** changing the subject or setting `insert_after` closes the old gap first, then opens the new slot (`:794-833`).
- **Destroy:** closes the gap.
- None of this is atomic. Concurrent edits produced the collisions listed in §8.5.

### 4.D Exam creation and question picking

**Admin form** (`Pages/tahsili_exams/create.blade.php`)
- Exam fields: «عنوان الاختبار», «نوع الاختبار» (مادة واحدة / عدة مواد), «المدرسة», «الصف الدراسي», «الفصول المستهدفة», «مدة صلاحية الاختبار (ساعة)».
- Repeatable subject row: «المادة», «المعلم», «المدة (دقيقة)», «عدد الأسئلة», «الترتيب», «سهل %», «متوسط %», «صعب %», plus «نطاق الأسئلة» («بداية النطاق», «نهاية النطاق») with find / preview / suggest helpers.

**Admin store** (`TahsiliExamController.php:508-621`)
- Validation is at :510-528. The three percentages must total 100 (:531-541).
- The school must be type 3 or 4.
- `created_by` = `Auth::id()` (a users.id). `created_by_type` stays at its default, `admin`.
- The "range has enough questions" check (:588-602) references undefined variables, so it never fires.

**Teacher store** (`Teacher/.../TahsiliExamController.php:219-333`)
- Checks `teacher_section_subject` for every subject, classroom and section.
- Percentages must total 100.
- Writes `type='single'`, `teacher_id`=self, `created_by`=teachers.id, `created_by_type='teacher'`.

**Weeks are informational only.** The teacher create screen shows:
- «إجمالي الأسئلة في بنك الأسئلة» (from `countBankQuestions`)
- «عدد الأسابيع» (school setting → global setting → 10)
- «عدد الأسئلة الأسبوعي» = `ceil(bank/weeks)` (`create.blade.php:270-300, 696-770`)

None of these values is stored. `tahsili_exams.weeks_count` is NULL on all 198 exams.

**Question generation** happens lazily on the student's first `startSubject`, in `generateTahsiliQuestions` (`StudentTahsiliExamController.php:244-383`).
1. Split the question count N by difficulty:
   - `easy = round(easy% × N)`
   - `medium = round(medium% × N)`
   - `hard = N − easy − medium`
2. Base query: subject + `exam_type='tahsili'`. **No classroom filter.** If a range is set: `whereBetween('question_order', [start ?? 1, end ?? PHP_INT_MAX])` (:269-276).
3. Draw each difficulty bucket with `inRandomOrder()->take()`. Fill any shortfall with random questions of any difficulty from the range, and use the whole range if still short (:305-328).
   - The bank is 99.9% `medium`, so in practice this draws random questions from the range.
   - Observed presets: `0/100/0` and `30/40/30`.
4. Shuffle the selection (:331). **Options are not shuffled.**
5. Insert one `tahsili_exam_student_questions` row per question (:359-372), with:
   - `question_original_order`;
   - snapshots of `question_text` and `options_json`;
   - `correct_answer` = index of the first `is_correct` option, stored as a string;
   - `order_index` 1..n.
6. `options_json` is **double-encoded** in all 37,838 rows: the controller json-encodes it, then the model's array cast encodes it again.
7. The **image is not snapshotted**; it is read live from `question->question_image`.

Observed `tahsili_exam_subjects` data: `total_questions` 5–400, `duration_minutes` 10–120. 161 of 218 rows have a range start. `total_available_questions` is always 0.

### 4.E Student taking the exam

**Menu:** «اختبارات تحصيلي» shows when the school type is 3 and the class name contains الثالث or 3.

**Index** (:23-88)
- Shows exams where the student's `section_id` is in the exam's sections and `expires_at` is NULL or in the future. Since expiry is never saved, that is effectively all of them.
- 5 per page, with `has_taken` and the completed-subject count.
- Multi-subject exams also show an overall % (`calculateExamOverallResult`, :93-116).

**startSubject** (:194-242)
- Runs a section check (`validateExamAccess`, :611) and an expiry check that does nothing.
- `firstOrCreate`s the result row: `pending`, `total_score = total_questions`, `started_at = now()`.
- Refuses if the subject is already completed.
- Generates questions if none exist, then renders the player.

**Player** (`Pages/Students/TahsiliExams/start.blade.php`)
- Everything is on one page, with one `.question-group` visible at a time.
- Navigation: «السؤال السابق» / «السؤال التالي» and numbered dots. Skipping an unanswered question shows a SweetAlert prompt (:857-880).
- Each question shows the image (:676) and radio options whose value is the option index (:690-705).
- **The timer runs in the browser only.** It starts at `duration_minutes×60` (:760) and resets on every reload. It warns at 300 s and 60 s, and calls `autoFinishExam` at 0 (:1097).
- `$answers` is never passed to the view, so a reload loses the visible selections.

**Answer saving:** every radio change POSTs `submitAnswer` (:433-486).
- The server derives the correct option from `options_json` (with `findCorrectAnswerFromOldFormat`, :491, as a fallback).
- It runs `updateOrCreate` on (student, subject, student_question), saving `answer_text` (the option index), `is_correct` and `score` (1 or 0).
- **It returns `is_correct` to the browser during the exam**, so correctness leaks.

**Finish** (`finishExam`, :384-428)
- `obtained_score` = count of correct answers (`calculateScore`, :587). `total_score` = `total_questions`.
- Sets `status=completed` and `ended_at`, then redirects to the next subject by `order_index`, or to the final result.
- `time_spent` is posted but dropped. The `timeout` status is never written.

**Scoring**
- Subject % = obtained / total_questions × 100. Unanswered questions count as wrong.
- Exam % for a multi-subject exam = Σ obtained / Σ total over completed subjects (:156-158). A single-subject exam uses its first subject (:161-167).

**Result screens**
- `final-result` (:135) shows subject cards and the overall %.
- `detailedResult` (:644-733) shows correct / wrong counts, %, `timeSpent = diffInMinutes(started_at, ended_at)`, seconds per question, and questions grouped by difficulty.
- `analyzeByDifficulty` (:546) reads a difficulty column that the snapshot table does not have, so it **always shows 0/0**.

### 4.F Settings (weeks)

**Data**
- `tahsili_settings`: a single row with `weeks_count` 10.
- `tahsili_school_settings`: one row per secondary school (§1.3).

**`adminSettings`** (:14)
- Creates the global row if it is missing.
- Lists schools with `school_type_id = 3`.
- View: `Pages/tahsili_settings/index.blade.php`.
  - Global form: `global_weeks` (1–52), `global_allow_editing`, `apply_to_all`.
  - Per-school table with columns «اسم المدرسة», «عدد الأسابيع», «السماح بالتعديل للمشرفين», «الإجراءات»; saves via AJAX.

**`saveGlobal`** (:35)
- With `apply_to_all`, overwrites the existing school rows.
- **`TahsiliSetting::$fillable` lacks `allow_editing_by_default`, so that flag never saves.**

**`supervisorSettings`** (:95)
- Finds the user's school with type 3 where they are `head_id` or `supervisor_id`. Otherwise it returns 403 «لا توجد مدرسة ثانوية تابعة لك».
- `firstOrCreate`s the school's settings row from the global defaults.
- The page is read-only unless `allow_editing=1`.
- View: `Pages/tahsili_settings/settings_supervisor.blade.php`.

**`updateSchoolSettings`** (:129) returns 403 «غير مسموح لك بالتعديل» unless editing is allowed.

**Effect:** weeks only feed the informational weekly count on the teacher create screen. Nothing enforces them.

### 4.G Role scoping and teacher restrictions

Exams and reports are scoped by role (`TahsiliExamController.php:101-125`, `TahsiliReportController.php:489-502`):

| role | scope |
|---|---|
| مشرف تعليمي | `complexes.supervisor_id` |
| وكيل مدرسة | `grades.supervisor_id` |
| وكيل مدرسة ثان | `grades.deputy_supervisor_id` |
| مدير القسم | `grades.head_id` |
| «معلم» | dead branch: the role does not exist, and the code uses the non-existent `teachers.user_id` |
| anyone else | everything |

Teachers:
- A teacher sees tahsili exams where they are a subject teacher, or where they are the creator or a subject teacher.
- Results are filtered to `tes.teacher_id` = self.
- Only the creator can delete.

`created_by` mixes ID spaces:
- It holds users.id for admin-created exams and teachers.id for teacher-created ones.
- `TahsiliExam::creator()` is always `belongsTo(User)`, so teacher-created exams show the wrong creator.
- `created_by_type='supervisor'` is never written.

### 4.H Reports (`Reports/TahsiliReportController.php`)

**Filters** (`index`, :22-65)
- المجمع, المدرسة (types 3/4 by role, `getAvailableGrades` :538), الصف (الثالث), المادة, «نوع الاختبار» (created_by_type), «من تاريخ», «إلى تاريخ», «الاختبار».
- The exam dropdown label is `title (Y-m-d) - 📋 إدارة|👨‍🏫 معلم`.
- Dropdowns cascade via AJAX: `get-grades-by-complex`, `get-classrooms-by-grade`, `tahsili.get-exams-by-filters`.

**Output:** `generateReport` (:70) requires at least one filter («يرجى تحديد فلاتر البحث أولاً») and returns the partial `Pages.Reports.partials.tahsili-reports-content`.

**Base joins:** result → student → the student's **current** section → classroom → grade → complex (not the exam's section).
- Conditions: active complex and school, `grades.school_type_id=3`, `status='completed'`, class name LIKE الثالث / 3.
- `applyPermissions` (:489) applies the role scope; `applyFilters` (:507) filters by date on `result.created_at`.

**Report types**

| report | method:line | output |
|---|---|---|
| student_results | getStudentResults:169 | One row per result. % = `ROUND(obtained/NULLIF(total,0)*100,2)`; time = `TIMESTAMPDIFF(MINUTE, started, ended)`; performance level per row |
| subject_performance | getSubjectPerformance:245 | Per (subject, school): distinct students, attempts, AVG/MAX/MIN %, average time |
| exam_statistics | getExamStatistics:298 | Per exam: subjects, students who took it, students in the exam's classroom (all its sections), participation = took ÷ in class × 100, average %, first/last attempt, average time. The date filter uses `tahsili_exams.created_at` |
| grade_distribution | getGradeDistribution:376 | Per (school, classroom, subject): counts in bands ≥95, 90–94.99, 85–89.99, 80–84.99, 70–79.99, 60–69.99, <60 |
| section_performance | getSectionPerformance:431 | Per (school, classroom, section, subject): students, average %, «المتفوقين» ≥80, «الضعاف» <60, average time |

**Performance level** (:115-136) = `PerformanceLevelGradeSetting::getPerformanceLevel($pct, $gradeId, 'tahsili')`. The default bands (§1.4) are ممتاز ≥95, جيد جداً 85–94, جيد 75–84, مقبول 65–74, ضعيف 0–64.
- The fallback names in the controller (متميز / متقدم / مستوفي / مقبول / غير مستوفي, :131-135) are unreachable.
- The teacher statistics screens (teacher `statistics` :470 and admin `statistics` :841) use a **different hard-coded scale**: «ممتاز (90-100%)», «جيد جداً (80-89%)», «جيد (70-79%)», «مقبول (60-69%)», «ضعيف (أقل من 60%)».

**Charts:** none. The reports are tables only (Bootstrap cards, select2, SweetAlert2).

**Excel export** (`exportExcel`, :565-905)
- No library: it builds an HTML table, sends it as `application/vnd.ms-excel` with the filename `tahsili_reports_Y-m-d_H-i-s.xls`, then does `echo …; exit`.
- Sheets and columns:

  | section title | columns |
  |---|---|
  | «📊 نتائج الطلاب في اختبارات التحصيلي» | #، اسم الطالب، رقم الهوية، المدرسة، الصف، الشعبة، المادة، الاختبار، الدرجة، النسبة، المستوى، الوقت، التاريخ، نوع الاختبار |
  | «📚 أداء الطلاب حسب المادة» | المدرسة، المادة، عدد الطلاب، عدد المحاولات، متوسط النسبة، أعلى نسبة، أدنى نسبة، متوسط الوقت |
  | «📈 إحصائيات اختبارات التحصيلي» | الاختبار، نوع الاختبار، نوع المحتوى، المدرسة، الصف، المواد، الطلاب المتقدمين، نسبة المشاركة، متوسط الدرجة، تاريخ الإنشاء |
  | «📊 توزيع الدرجات حسب المادة» | المدرسة، الصف، المادة، عدد الطلاب، 95-100، 90-94، 85-89، 80-84، 70-79، 60-69، أقل من 60 |
  | «👥 أداء الفصول (الشعب)» | المدرسة، الصف، الفصل، المادة، عدد الطلاب، متوسط النسبة، المتفوقين، الضعاف، متوسط الوقت |

**Teacher CSV** (`downloadResults`, :550-622)
- `;`-separated, filename `نتائج_{title}_{date}.csv`.
- Columns: اسم الطالب، رقم الهوية، المادة، الدرجة الكلية، الدرجة المتحصل عليها، النسبة المئوية، وقت البدء، وقت الانتهاء.

### 4.I Views

All extend `layouts.master`.

| view | UI |
|---|---|
| Pages/tahsili_questions/index | Per-subject «المواد التحصيلية» cards; GET filters (search, classroom, subject, term). Table columns: #، المادة، نص السؤال، الفصل الدراسي، مستوى الصعوبة، نوع السؤال، الصفوف، الإجابة الصحيحة، إجراءات. Bulk delete through generic `questions.deleteMultiple`; toastr |
| Pages/tahsili_questions/create | Classroom checkboxes grouped by complex/school; subject, term, difficulty and type selects; image; «أضف السؤال بعد رقم»; dynamic options with a correct-answer radio. AJAX save with SweetAlert2 |
| Pages/tahsili_questions/edit | Same as create, prefilled, plus a remove-image option. Keeps the list filters via hidden `current_*` fields |
| Pages/tahsili_exams/index | Filters (complex, school via AJAX, search, sort). Table columns: عنوان الاختبار، نوع الاختبار، المدرسة، نوع المدرسة، الصف، تم الإنشاء بواسطة، تاريخ الإنشاء، تاريخ الانتهاء، الإجراءات. Delete modal «هل أنت متأكد من حذف الاختبار؟» |
| Pages/tahsili_exams/create | Exam header plus repeatable subject rows; difficulty % with a live sum; «نطاق الأسئلة» with helpers; select2 |
| Pages/tahsili_exams/show | **0 bytes** |
| Pages/tahsili_settings/index · settings_supervisor | Global and per-school weeks settings (AJAX); read-only supervisor view |
| Pages/Teachers/dashboard/tahsili_exams/index | Table columns: عنوان الاختبار، المدرسة، الصف، المواد، الصلاحية، الحالة، الإجراءات. Delete modal posts to `url('teacher/tahsili-exams')` and **404s** |
| Pages/Teachers/dashboard/tahsili_exams/create | Same as the admin form, plus a bank-count / weeks / weekly-count card |
| Pages/Teachers/dashboard/tahsili_exams/show | Stats cards (students, completed, average, pending). Subjects table: المادة، المعلم، المدة، عدد الأسئلة، توزيع الصعوبة، نطاق الأسئلة. Links to results, statistics and CSV |
| Pages/Teachers/dashboard/tahsili_exams/results | Results grouped by student (DataTables) |
| Pages/Teachers/dashboard/tahsili_exams/statistics | **Missing** |
| Pages/Students/TahsiliExams/index | «اختبارات تحصيلي المتاحة»: اسم الاختبار، النوع، المواد، المدة، الحالة، الإجراءات |
| Pages/Students/TahsiliExams/show | «تفاصيل اختبار»: subject cards in sequence |
| Pages/Students/TahsiliExams/start | Player: countdown, one question at a time, dots, zoomable image |
| Pages/Students/TahsiliExams/final-result · detailed_result · subject_result | Result screens. `exam_result` is orphaned |
| Pages/Reports/tahsili-reports + partials/tahsili-reports-content | «تقارير اختبارات التحصيلي»: filter bar (select2), AJAX generate, Excel button, five table cards with level badges coloured by `performance_color` |

### 4.J Gotchas (tahsili)
1. **The range helpers do not match the generator.**
   - The helpers filter on `questions.id`, `questions.classroom_id` and `created_at` order.
   - The generator filters on `question_order` and ignores the classroom.
   - `classroom_id` is NULL for every tahsili question, so the helpers always return 0 or nothing.
   - **When porting, keep the generator's meaning: the range is in `question_order` units.**
2. **No auth** on question CRUD, exam create/store/destroy, the range helpers, `reports.tahsili.export-excel` and `tahsili.get-exams-by-filters`. `store`, `update` and `destroy` need nothing but a CSRF token.
3. **Expiry is never saved**, because the fields are not fillable.
4. **Teacher routes:**
   - `/{id}` (`teacher.php:212`) shadows find-question, preview-questions and suggest-range.
   - The delete forms post to `url('teacher/tahsili-exams')`, which 404s because the routes have no `/teacher` prefix.
   - Teacher `destroy` redirects to the admin route.
5. **Doubled prefix** in two student URIs: `/student/student/...`.
6. **Empty or missing views:** `tahsili_exams.show` and the teacher `statistics` view.
7. **Broken links:** `show.blade.php:595` uses the undefined route `student.tahsili.exams.subject.continue`. `final-result.blade.php:356` passes the exam id where a subject id is expected.
8. **An unscoped `orWhere('Name_Class','like','%3%')`** in admin `create` (`TahsiliExamController.php:281-406`) escapes the role scope.
9. **Hard-coded values:**
   - School types 3 and 4 (reports use 3 only).
   - Class names «الثالث», «3» and «الثالث ثانوي».
   - School names ثانوية / ثانوي.
   - A default of 10 weeks.
10. **Fingerprints are useless for tahsili**, and `question_order` has collisions. The snapshot does not copy the image.
11. **Exam integrity:** `options_json` is double-encoded, `is_correct` is returned to the browser while the exam is running, and the timer is client-only.
12. **Dropped attributes:** `time_spent`, `question_type`, `score` and `allow_editing_by_default` never save. The difficulty analysis reads a column that does not exist.
13. **ID spaces are mixed:** teachers.id vs users.id in `created_by` and in the report's teacher scope. `getPerformanceLevels` (:150) calls an undefined method; it is private and never called.
14. **Dead code:** `use App\Http\Controllers\TahsiliSettingController;` (`web.php:43`) imports a class that does not exist, and `app/Http_old` holds a stale copy.
15. **Times are UTC:** `started_at` and `ended_at` are DATETIME values written in UTC and displayed as-is.
16. **Two percentage scales:** the statistics screens use 90/80/70/60, while the reports use 95/85/75/65.

---

## 5. Nafis («نافس»: ابتدائي ثالث وسادس، متوسط ثالث)

### 5.0 Key facts
- **Subjects:** 59 «نافس "قراءة"», 60 «نافس "رياضيات"», 61 «نافس "علوم"» (`type='nafis'`).
- **Levels:** the program covers stages 1 and 2 only, and classes are detected by a name regex `(الثالث|3|السادس|6)`. No column stores the level.
  - Subject 59 (reading) is 68 passages with 751 sub-questions in total.
  - Subjects 60 and 61 are independent questions only.
- **Question selection:**
  - Exams pick questions by **skill-distribution %**.
  - For reading, `total_questions` counts **passages**, not questions. Each picked passage expands into all of its sub-questions.
- **Unused columns:** `reading_mode` (always `detailed`) and `passages_data` (always NULL) on `nafis_exam_subjects` are never read or written by any code.
- **Runtime data:**

  | table | rows | detail |
  |---|---|---|
  | nafis_exams | 104 | 99 by teachers (all single-subject); admins: 3 multi + 2 single |
  | nafis_exam_student_results | 636 | 575 completed, 61 pending |

### 5.A Routes

All `routes/web.php` groups after line 387 have no auth middleware. Rows marked **(missing)** point to controller methods that do not exist.

| file:line | method | URI | name | controller@method | guard |
|---|---|---|---|---|---|
| web.php:267 | GET | /nafis-questions | nafis-questions.index | NafisQuestionController@index | auth:web |
| web.php:268 | GET | /nafis-questions/create | nafis-questions.create | @create | auth:web |
| web.php:269 | POST | /nafis-questions | nafis-questions.store | @store | auth:web |
| web.php:272-279 | GET/GET/PUT/DELETE/GET/POST | /nafis-questions/{id}, /{id}/edit, /{id}, /{id}, /{id}/copy, /bulk-delete | .show .edit .update .destroy .copy .bulk-delete | **all 6 methods missing** | auth:web |
| web.php:282 | POST | /nafis/get-subjects | – | NafisQuestionController@getSubjects | auth:web |
| web.php:283 | POST | /nafis/get-skills | – | @getSkills | auth:web |
| web.php:284 | GET | /nafis/classrooms/{classroomId}/subjects | nafis.get_subjects | @getNafisSubjectsByClassroom | auth:web |
| web.php:290-297 | GET/GET/POST/GET/GET/PUT/DELETE | /nafis-passages, /create, /, /{id}, /{id}/edit, /{id}, /{id} | nafis_passages.index/create/store/show/edit/update/destroy | NafisPassageQuestionController (**`show` missing**) | auth:web |
| web.php:483 | GET | /nafis_exams | nafis_exams.index | NafisExamController@index | **none** |
| web.php:484 | GET | /nafis_exams/create | nafis_exams.create | @create | none |
| web.php:485 | POST | /nafis_exams/store | nafis_exams.store | @store | none |
| web.php:486 | GET | /nafis_exams/{id} | nafis_exams.show | @show **(missing)** | none |
| web.php:488 | DELETE | /nafis_exams/nafis-exams/{id} | nafis_exams.destroy | @destroy | none |
| web.php:490 | GET | /nafis/get-classrooms/{grade_id} | – | @getClassrooms | none |
| web.php:491 | GET | /nafis/get-sections/{classroom_id} | – | @getSections | none |
| web.php:493 | GET | /nafis/get-subjects/{grade_id}/{classroom_id} | – | @getSubjects (ignores the URI params, reads `classrooms[]`) | none |
| web.php:494 | GET | /nafis/get-teachers | nafis.get_teachers | @getTeachers | none |
| web.php:495 | GET | /nafis/get-passages | nafis.get_passages | @getPassages **(missing)** | none |
| web.php:499 | GET | /nafis/get-subject-skills/{subject_id}?classroom_id= | nafis.get_subject_skills (an older copy at :497-498 is commented out) | @getSubjectSkills | none |
| web.php:669 | GET | /reports/nafis | reports.nafis.index | Reports\NafisReportController@index | none (guest → 500 on `hasRole`) |
| web.php:670 | POST | /reports/nafis/generate | reports.nafis.generate | @generateReport (**this is the `nafis/generate` endpoint; it returns the report HTML partial**) | none |
| web.php:672 | GET | /reports/nafis/export-excel | reports.nafis.export-excel | @exportExcel | none |
| web.php:711-737 | GET | /nafis/get-exams-by-date | nafis.get-exams-by-date | inline closure (filters by date, complex and grade; returns `{id:"title (Y-m-d)"}`) | none |
| teacher.php:269-277 | GET/GET/POST/GET/DELETE/GET/GET | /teacherNafis-exams, /create, /, /{id}, /{id}, /get-sections/{classroom_id}, /get-subject-skills/{subject_id} | teacher.nafis_exams.index/create/store/show/destroy/get_sections/get_subject_skills | Teacher\dashboard\NafisExamController | auth:teacher (teacher.php:265) |
| teacher.php:288-317 | GET | /teacher/nafis-reports, /exam/{exam_id}, /subject/{subject_id}, /section/{section_id}, /student/{student_id}, /skills/{exam_id}, /download/{type}/{id}, /stats | teacher.nafis.reports, .exam, .subject, .section, .student, .skills, .download, .stats | Teacher\dashboard\ReportNafisExamController@reports/examReport/subjectReport/sectionReport/studentReport/skillsAnalysis/downloadReport/dashboardStats | auth:teacher, prefix `teacher` (teacher.php:285) |
| student.php:211 | GET | /student/student-nafis-exams | student.nafis.exams.index | Students\dashboard\StudentNafisExamController@index | auth:student (student.php:208) |
| student.php:215 | GET | /student/nafis-exams/{exam_id} | student.nafis.exams.show | @show | auth:student |
| student.php:218 | GET | /student/nafis-exams/{exam}/continue | student.nafis.exams.continue | @continueExam | auth:student |
| student.php:221 | GET | /student/nafis-exams/subject/start/{subject_id} | student.nafis.exams.subject.start | @startSubject (`subject_id` = nafis_exam_subjects.id) | auth:student |
| student.php:225 | POST | /student/nafis-exams/answer | student.nafis.exams.answer | @submitAnswer (AJAX) | auth:student |
| student.php:229 | POST | /student/nafis-exams/finish/{subject_id} | student.nafis.exams.subject.finish | @finishSubject | auth:student |
| student.php:236 | GET | /student/debug/skills/{exam_subject_id} | – | @checkSkillsDebug **(missing)** | auth:student |
| student.php:237 | GET | /student/nafis-exams/reset/{subject_id} | student.nafis.exams.reset | @resetSubject **(missing)** | auth:student |
| student.php:240 | GET | /student/nafis-exams/detailed-result/{subject_id} | student.nafis.exams.detailed-result (**duplicate; :263 wins**) | @detailedResult | auth:student |
| student.php:242 | GET | /student/student/nafis-exams/get-next-subject/{currentSubjectId} | student.nafis.exams.get-next-subject | @getNextSubject (doubled prefix) | auth:student |
| student.php:247 | GET | /student/student/nafis-exams/check-next-subject/{id} | student.nafis.exams.check-next-subject | @checkNextSubject | auth:student |
| student.php:254 | GET | /student/student/nafis-exams/{exam}/final-result | student.nafis.exams.final-result (**duplicate; :259 wins**) | @showFinalResult | auth:student |
| student.php:259 | GET | /student/nafis-exams/{exam}/final-result | student.nafis.exams.final-result | @showFinalResult | auth:student |
| student.php:263 | GET | /student/nafis-exams/{exam}/detailed-result | student.nafis.exams.detailed-result | @detailedResult($examId) | auth:student |

Notes:
- Commented-out legacy student routes remain at student.php:185-202.
- The performance-level routes (`type=nafis`) are listed in §1.4.

### 5.B Controllers

**`NafisExamController.php`** (admin, 785 lines; lines 26-110 are a commented-out old index)
- `index` (112): 10 per page, filters `search`, `sort=oldest`, `complex_id`, `grade_id`.
  - Rows are scoped by role in `buildNafisExamQuery` (:170):

    | role | scoped to |
    |---|---|
    | مشرف تعليمي | `complexes.supervisor_id` |
    | وكيل مدرسة | `grades.supervisor_id` |
    | وكيل مدرسة ثان | `deputy_supervisor_id` |
    | مدير القسم | `head_id` |
    | معلم | `created_by` |
    | anyone else | all rows |

- `create` (271): same scoping; stages 1/2; classrooms matched by the name regex; subjects with `type='nafis'`.
- `store` (449): see 5.D.
- `getSubjectSkills` (561): skills linked to the classroom through `skill_classroom` that have at least one nafis question, counting both `skill_id=` and `skills LIKE '%id%'`. If anything throws, it falls back to hard-coded skills 4489/4491/1001 (:632).
- `getClassrooms` (644): returns 403 if the school is not stage 1/2.
- `getSubjects` (666): nafis subjects via `classroom_subject` for the posted `classrooms[]`.
- `getSections` (699).
- `getTeachers` (706): from `teacher_section_subject`.
- `destroy` (729): `FOREIGN_KEY_CHECKS=0`, then hard-deletes answers → student questions → results → subjects → sections → exam. Distribution rows go through the FK cascade.

**`NafisQuestionController.php`**
- `index` (37): independent questions only. Search strips tashkeel (`prepareSearchText`, :20). Filters: `classroom_id`, `subject_id`, `term` (matches the question term OR the skill term). 10 per page.
- `create` (122), `store` (135).
- `getSubjects` (237).
- `getSkills` (274): requires `term` but does not apply it.
- `getNafisSubjectsByClassroom` (294).

**`NafisPassageQuestionController.php`**
- `index` (16), `create` (112).
- `store` (127) and `update` (368) respond with JSON.
- `edit` (231).
- `destroy` (467): detaches classrooms, then deletes the row.
- `getNafisSubjectsByClassroom` (483).

**`Teacher/dashboard/NafisExamController.php`** (teacher guard)
- `index` (24): rows where `teacher_id` = me, or (`created_by` = me AND `created_by_type='teacher'`).
- `create` (40): only the teacher's own sections and nafis subjects; redirects with a warning if there are none.
- `store` (96).
- `show` (220): renders `Pages.Teachers.nafis_exams.show`, **which does not exist**. The real file is at `Pages/Teachers/dashboard/nafis_exams/show.blade.php`.
- `destroy` (244), `getSections` (312), `getSubjectSkills` (328; same as admin, without the fallback).

**`Students/dashboard/StudentNafisExamController.php`** (2,666 lines, about half commented-out code)
- Routed: `index` (82), `show` (165), `showFinalResult` (179), `startSubject` (236), `submitAnswer` (437), `finishSubject` (476), `detailedResult` (2017), `getNextSubject` (2528), `checkNextSubject` (2555), `continueExam` (2637).
- Public but unrouted (dead): `finishExam` (348), `checkQuestionsStatus` (547, an echo/die debug page), `subjectResult` (579), `getExamStatistics` (1800), `restartSubject` (1840), `saveProgress` (1867; the only writer of `last_saved_at`), `loadProgress` (1889), `exportResult` (1913), `getQuestionInfo` (1943).

**`Reports/NafisReportController.php`**: `index` (19), `generateReport` (44), `exportExcel` (513).

**`Teacher/dashboard/ReportNafisExamController.php`**: `reports` (24), `examReport` (211), `subjectReport` (258), `sectionReport` (299), `studentReport` (342), `skillsAnalysis` (425), `downloadReport` (457), `dashboardStats` (496).

**Models**
- `NafisExam`: `isExpired()`, `getStatusBadge()` («انتهت الصلاحية» / «نشط»), `getTimeRemaining()`. `teacher()` belongsTo `User`, but `teacher_id` holds a teachers.id.
- `NafisExamStudentQuestion::$fillable` has no `skill_id` (and the table has no such column), so the skill passed on create is silently dropped.
- `Teacher::teachesNafisSubjects()` is at `Teacher.php:106`.

### 5.C Question bank: CRUD, passages, import

**Independent question** (`NafisQuestionController@store`, :135-225)
- Validation:
  - `question_text[]`: each ≤255 characters.
  - `term`: first / second / both.
  - `subject_id`, `skill_id`: required.
  - `difficulty`: easy / medium / hard.
  - `type`: multiple_choice or true_false.
  - `Class_id[]`.
  - Optional image.
  - MCQ: `options[*][option_text]` plus `correct_option` (an index).
- Saved with `exam_type='nafis'` (:170).
- Encoding is the same as qiyas.
- `week` is never set: 983 of 1,031 rows have `week = NULL`.
- **Fingerprint** (:209): `md5(json_encode([question_text getter output (nl2br'd array), term, subject_id, skill_id]))`.
- **Image** (:182): `base_path('uploads/questions')`, file name `question_<time>_<uniqid>.<ext>`.

**Passage**
- Store (127-228) and update (368-466) both return JSON.
- One row per passage holds `passage_text`, a `question_text` array, an `options` array of arrays and a `skills` array of skill-id strings; `skill_id` stays NULL.
- No image upload for passages.
- **Fingerprint** (:198, :435): `md5(json_encode([exam_type:'nafis', passage_text, term, subject_id]))`.

**Where the double encoding comes from**
- `store` (:192-194) and `update` (:430-432) assign `json_encode(...)` **strings** to the model, which then encodes them again:
  - `options`: `setOptionsAttribute` encodes the string, giving a JSON string literal.
  - `skills`: the `'skills' => 'array'` cast encodes the string again.
  - `question_text`: the current setter would wrap a JSON string as `["<json>"]` (a one-element array). No row in the DB has that shape. Row 18469 decodes to a normal 10-element array, but its **first stem starts with a stray `["`** (a split artefact). Strip leading `["` and trailing `"]` from stems on import.
- The 58 rows whose `question_text` starts with `"[` were all created 2025-10-15…26, apparently under an older cast/setter.
- **No code path in the current tree writes the clean form.** The 10 clean passages were all last updated 2026-04-07/08, which points to a manual data fix.
- Readers tolerate both forms by decoding again whenever they get a string: `StudentNafisExamController::extractQuestionTexts` (1401) and `extractOptions` (1416), the `generateReadingPassages` loop, and `NafisPassageQuestionController@edit` (231-305).

**Editing.** The index links to the generic `questions.show` / `questions.edit` (`index.blade.php:337-338`) and to `questions.deleteMultiple` (:440). `nafis_questions/edit.blade.php` and `nafis_exams/show.blade.php` are **0 bytes**.

**Import.** The modal (`nafis_questions/index.blade.php:55-75`) posts to the generic `questions.import`, which is `QuestionController@import` (QuestionController.php:684). That uses `Maatwebsite\Excel` with `App\Imports\QuestionsImport`.
- Heading-row columns: `question_text, term (الأول/الثاني/الثالث), subject_name, week, skill_name, difficulty, classroom_name, school_name, school_type (بنين→1 else 2), complex_name, option_1..option_4, correct_option (1-based)`. If `correct_answer` is `صح`, the row becomes true/false.
- It `firstOrCreate`s complex, grade, classroom, subject and skill **by name**.
- **It never sets `exam_type`**, so anything imported from the nafis page becomes `regular`.
- There is no passage import for nafis.

### 5.D Exam creation and question picking

**Admin `store`** (:449-559)
- Exam fields: `title`, `type` (single «مادة واحدة» / multi «عدة مواد»), `grade_id`, `classroom_id`, `expiry_hours` (1-720), `section_ids[]`.
- Per subject: `subjects[i][subject_id, duration_minutes, total_questions, order_index, teacher_id?, skill_distribution[j][skill_id, percentage]]`.
- Checks:
  - Skill % sums to ≤100, otherwise «مجموع النسب المئوية للمهارات يجب ألا يتجاوز 100%».
  - School stage is 1 or 2.
  - The class name matches `preg_match('/(الثالث|3|السادس|6)/')` (:489).
- Values set:
  - `expires_at = now()->addHours()` (:496, UTC).
  - `complex_id = grade.complex_id ?? grade_id` (:504).
  - `created_by_type` = default `admin`; `teacher_id` = NULL.
- Inserts sections, subjects and distribution rows. Distribution rows are only written when both skill and percentage are set.

**Teacher `store`** (:96-218)
- Always `single`, one subject from the teacher's own nafis subjects.
- Limits: duration 15-180 min, 1-100 questions, expiry 1-168 h.
- Every section must be the teacher's own.
- Sets `created_by` = teacher, `created_by_type='teacher'`, `teacher_id` = teacher.
- If the % sum is over 100, the transaction rolls back.

**Question generation** happens per student at first open.
1. `startSubject` → `generateMixedQuestions` (606-649) deletes any existing questions and loads the distribution.
2. It then picks a branch **by subject name** (:633). Names containing «قراءة» or «عربي» go to `generateReadingPassages`; everything else goes to `generateIndependentQuestions`.

**`generateReadingPassages`** (731-875)
- `total_questions` = **number of passages**. Teachers used about 7.8 for subject 59, against 18.3 / 21.3 for subjects 60 / 61.
- Picks random passages of the subject linked to the student's `Classroom_id`.
  - The same-school fallback is broken: it reads `grade_id` instead of `Grade_id`.
  - If nothing is found it throws «لا توجد قطع قراءة متاحة لصفك الدراسي ...».
- Stores **every** sub-question as a `nafis_exam_student_questions` row:
  - `sub_index = i`
  - `question_text = texts[i]`
  - `options_json = options[i]`
  - `correct_answer` = **index** of the option with `is_correct === true`
  - `passage_text` only on `sub_index` 0 (:1335)
  - `question_type='passage'`
  - sequential `order_index`
- The skill distribution is **ignored** for selection.

**`generateIndependentQuestions`** (1033-1291)
- With no distribution, it falls back to `getDefaultSkillsForSubject` (2567): the top 10 skills by question count, or hard-coded ids by subject name (4489/4491/1001, 4507/4509/4511, 4493/4494/4495), split equally.
- Questions per skill: `round(pct/Σ × N)`, at least 1 (:1075). The rounding difference goes to the first skill (:1083).
- Per skill it takes random rows matching `skill_id=` OR `JSON_CONTAINS(skills)`, independent, nafis, in the student's classroom, and not already picked. It fetches `take(count*2)`, then keeps `take(count)`.
  - If short, it tops up from that skill's passage rows (only `sub_index` 0 is stored).
  - It then does an "emergency" fill from any nafis question of the subject in the classroom.
- **Difficulty is never used.**
- If no option is marked correct, `extractCorrectAnswer` defaults to index 0 (:1797).

**Sections:** students see an exam when their `section_id` is in `nafis_exam_sections`.

### 5.E Student taking the exam

1. **`index`** (82): exams for the student's section, 10 per page. Each row computes `has_taken`, completed/total subjects, an overall result for multi exams (Σobtained/Σtotal), and `is_expired`. Badges: «مكتمل», «قيد التنفيذ», «انتهت الصلاحية», «متاح».
2. **`show`** (165): subject cards with sequential locking (`show.blade.php:453-466`). A locked card says «يجب إكمال اختبار {subject} أولاً».
3. **`startSubject`** (236)
   - `validateExamAccess` (1630) checks the section only. **Expiry is not checked**: it only hides the start button in the list.
   - `firstOrCreate` the result row (`pending`, `started_at`). A completed subject redirects with «لقد أنهيت هذه المادة بالفعل».
   - Generates questions if missing.
   - `getExamData` (1684) groups the questions: one group per passage (`passage_<question_id>`) or per independent question. `displayType` is `passages_only`, `independent_only` or `mixed`.
4. **Player** (`Students/NafisExams/start.blade.php`): pages through **one group at a time** with prev/next, dots, «submit-group» and «finish-exam».
   - Timer: a client-only countdown of `duration_minutes*60` (:921). It resets on reload. Toasts fire at 5 and 1 minutes (:949-960). At 0, `autoFinishExam` runs.
   - A `beforeunload` guard is installed.
   - The "autosave" is fake: it only `console.log`s every 30 s (:1494).
   - Every radio change POSTs `{nafis_exam_subject_id, question_instance_id, answer: optionIndex}` (:1165-1200).
   - Libraries: jQuery and SweetAlert2.
5. **`submitAnswer`** (437): `is_correct = (answer == correct_answer)`, compared as indexes. Saves with `updateOrCreate`, `score` 1 or 0.
6. **`finishSubject`** (476): **`percentage = correct / count(student question rows) × 100`**. Each passage sub-question counts as one question (`calculatePercentageScore`, :1974).
   - Stores `total_score=100`, `obtained_score=round(%,2)`, `status='completed'`, `ended_at` (:2004).
   - The posted `answered_count`, `time_spent` and `auto_finished` values are ignored.
   - Message: «تم إنهاء المادة بنجاح - النتيجة: X% (c/n)».
7. **Next subject:** `get-next-subject` returns the next `order_index`. The JS then goes to `/student/nafis-exams/subject/start/{id}`, or back to `show` when everything is done.
8. **`showFinalResult`** (179): Σobtained/Σtotal over completed subjects. A single exam uses its first result only.
9. **`detailedResult`** (2017) runs `analyzeSkillsForExam` (2088), the **only real per-skill mastery** in the program.
   - Skill per question: independent → `questions.skill_id`; passage → `skills[sub_index]`, else `skills[0]`, else `skill_id`, else the bucket `question_type`.
   - Per-skill % = correct / total × 100. Weak < 50, strong ≥ 80.
   - Uses `prepareQuestionsForExamReview` for the review list.
   - Rendered by `detailed_result.blade.php`, a standalone Bootstrap 5 + Font Awesome CDN page with print.

**Statuses:** only `pending` and `completed` are ever written. `timeout` and `in_progress` are never set, and `last_saved_at` is never written on the routed path. The stored `correct_answer` values are 0–3, plus one NULL.

### 5.F Teacher side
- A teacher is scoped to their own sections and subjects through `teacher_section_subject`. Subjects must have `type='nafis'`, and classrooms are matched by the name regex.
- Screens:
  - index (with a link to reports);
  - create: single subject, with skills fetched via AJAX alongside their question counts;
  - show: the view path is broken.
- There is no separate monitoring screen; the teacher reports below cover it.

### 5.G Reports

**Admin — `Reports\NafisReportController`**

Filters: `complex_id, grade_id, classroom_id, subject_id, exam_id, created_by_type (admin/teacher), date_from, date_to`.

Role scope (`applyPermissions`, :424):

| role | scoped by |
|---|---|
| مشرف تعليمي | `complexes.supervisor_id` |
| وكيل مدرسة | `grades.supervisor_id` |
| وكيل مدرسة ثان | `deputy_supervisor_id` |
| مدير القسم | `head_id` |
| معلم | `nafis_exam_subjects.teacher_id` = user id (wrong id space) |

Page: `Pages/Reports/nafis-reports.blade.php`. It POSTs via AJAX to `reports.nafis.generate` and receives `partials/nafis-reports-content`. Dependent dropdowns use `/get-grades-by-complex-ajax` and `/nafis/get-exams-by-date`.

Sections, in order:

1. **«نتائج الطلاب في اختبارات نافس»** (`getStudentResults`, :120): one row per result, any status.
   - `percentage = ROUND(obtained/NULLIF(total,0)*100,2)`. School comes from student → section → classroom → grade.
   - Level = `PerformanceLevelGradeSetting::getPerformanceLevel($pct, $gradeId, 'nafis')`. Nafis defaults: ممتاز 85–100 · جيد جداً 75–84 · جيد 65–74 · مقبول 50–64 · ضعيف 0–49 (§1.4).
   - The controller's own fallback bands (متقدم ≥85 / متقن ≥70 / متمكن ≥50 / ينمو ≥30 / يبدأ, :81-104) are dead code.
   - `getPerformanceLevels` (:106) calls an undefined method.
2. **«أداء الطلاب حسب المادة»** (`getSubjectPerformance`, :189): per subject × school — distinct students, attempts, AVG/MAX/MIN %.
3. **«أداء الطلاب حسب المهارات»** (`getSkillsPerformance`, :235): joins skill_distribution ⨝ student_questions ⨝ answers per exam subject, `success_rate = AVG(is_correct*100)`.
   - **Wrong by construction:** every answer in the subject is credited to every skill in the distribution.
   - Subjects with no distribution produce no rows.
4. **«إحصائيات اختبارات نافس»** (`getExamStatistics`, :283): per exam — subjects, students who took it vs students in the class (all sections), `participation_rate = took/in_class×100`, average, first/last attempt.
5. **«توزيع الدرجات»** (`getGradeDistribution`, :357): per school × classroom × subject, in bands 90–100 / 80–89.99 / 70–79.99 / 60–69.99 / 50–59.99 / <50.

**Excel export** (`exportExcel`, :513) is **not Maatwebsite**. It writes an HTML table with `application/vnd.ms-excel`, names the file `nafis_reports_<date>.xls`, then calls `exit`.
- Header: title «تقرير اختبارات نافس», the filter summary, and the strip «مستويات الأداء المعتمدة (اختبارات نافس):».
- Tables:

  | table | columns |
  |---|---|
  | Students | `# \| اسم الطالب \| رقم الهوية \| المدرسة \| الصف \| الفصل \| المادة \| الاختبار \| الدرجة \| النسبة \| المستوى \| التاريخ \| نوع الاختبار` |
  | Subjects | `المدرسة \| المادة \| عدد الطلاب \| عدد المحاولات \| متوسط النسبة \| أعلى نسبة \| أدنى نسبة` |
  | Skills | `# \| المهارة \| المادة \| المدرسة \| عدد الطلاب \| عدد الإجابات \| الإجابات الصحيحة \| نسبة النجاح \| التقييم` |
  | Stats | `الاختبار \| نوع الاختبار \| المدرسة \| الصف \| المواد \| الطلاب المتقدمين \| نسبة المشاركة \| متوسط الدرجة \| تاريخ الإنشاء` |
  | Distribution | `المدرسة \| الصف \| المادة \| عدد الطلاب \| 90-100 \| 80-89 \| 70-79 \| 60-69 \| 50-59 \| أقل من 50` |

- In the Skills table, التقييم is «ممتاز» ≥80, «جيد جداً» ≥60, «جيد» ≥50, else «يحتاج تحسين».
- "PDF" is just `window.print()` (`nafis-reports.blade.php:400`).
- **No chart library**: everything is tables plus Bootstrap progress bars.

**Teacher — `ReportNafisExamController`**
- `reports` (24): the teacher's exams with total / completed / pending students and the average %. Header cards: total exams, students, completed, pending, average, active (not expired).
- `examReport` (211):
  - `getExamStatistics` (511): avg / max / min % over completed results.
  - Per-student Σobtained/Σtotal, with the top 10 and weakest 10.
  - `getGradeDistribution` (617) uses **different bands**: «ممتاز (90-100%)», «جيد جداً (80-89%)», «جيد (70-79%)», «مقبول (60-69%)», «ضعيف (أقل من 60%)».
  - `getSubjectsAnalysis` (677).
- `skillsAnalysis` (425 → `getSkillsAnalysis` 725 / `getStudentsSkillsPerformance` 810): every distributed skill gets the **whole subject's** average, so this is not real mastery.
- `studentReport` (342): `analyzeStudentBySkills` (1114) has the same flaw.
- `subjectReport` / `sectionReport` (258 / 299): **SQL error**. They filter on the non-existent columns `nafis_exams.subject_id` / `section_id`, and their views are missing too.
- `downloadReport` (457): **broken**. It uses `Barryvdh\DomPDF`, which is not installed, and the `pdf.*` views are missing.
- `dashboardStats` (496): returns JSON.

### 5.H Views

| view | UI |
|---|---|
| Pages/nafis_questions/index | Filters: search, classroom, subject, term. Columns: # / المادة / نص السؤال / مهارة السؤال / الفصل الدراسي / الأسبوع / مستوى الصعوبة / نوع السؤال / الصفوف / الإجابة الصحيحة / إجراءات. Import modal «استيراد الأسئلة من Excel», bulk delete «حذف المحدد», link to passages. Libraries: DataTables + toastr |
| Pages/nafis_questions/create | Complex → school → classroom checkboxes; subjects via POST `/nafis/get-subjects`; skills via `/nafis/get-skills`; options with a correct radio; image. SweetAlert |
| Pages/nafis_questions/edit | **0 bytes** |
| Pages/NafisPassages/index | Columns: # / القطعة / المادة / الأسئلة والخيارات / المهارات / الفصل الدراسي / الأسبوع / مستوى الصعوبة / عرض الصفوف / الاجراءات |
| Pages/NafisPassages/create, edit | Passage textarea plus a repeatable sub-question block (text, options, correct answer, skill), saved as AJAX JSON |
| Pages/nafis_exams/index | Complex / school filters, search, sort. Columns: # / عنوان الاختبار / المدرسة / نوع المدرسة / الصف / نوع الاختبار / تم الإنشاء بواسطة / تاريخ الإنشاء / الإجراءات |
| Pages/nafis_exams/create (109 KB) | Single/multi; cascading school → classroom → sections → subjects; repeatable subject rows (duration, count, order, teacher, skill-distribution rows with live question counts). Select2 + SweetAlert |
| Pages/nafis_exams/show | **0 bytes** |
| Pages/Teachers/dashboard/nafis_exams/{index,create,show} | Teacher list; single-subject form with «عنوان الاختبار *», «المدرسة *», «الصف الدراسي *», «اختر الفصول *», «مدة الاختبار (دقائق) *», «عدد الأسئلة *», «صلاحية الدخول (ساعات) *»; details |
| .../nafis_exams/reports/{index,exam,skills,student} | Stat cards, tables, progress bars, `window.print`. No charts |
| Pages/Reports/nafis-reports + partials/nafis-reports-content | Filter form → AJAX partial with the five sections; «تصدير Excel» and print buttons |
| Pages/Students/NafisExams/index | Exam table with status badges and «النتيجة / متابعة / منتهي / ابدأ» |
| .../show | Subject cards with locking; «بدء الاختبار» and «عرض تفاصيل الاختبار» |
| .../start (52 KB) | The player (5.E) |
| .../final-result | Summary. SweetAlert |
| .../detailed_result | Standalone page: skill analysis plus per-question review, print |
| .../exam_result, subject_result | Legacy. `exam_result` uses the missing route `student.nafis.exams.subject.result` |

### 5.I Gotchas (nafis)
1. **No auth on admin pages.** All admin exam CRUD, its AJAX helpers and the admin reports (`web.php:482-499, 668-737`) have no auth middleware.
2. **Routes pointing at missing methods:** `NafisExamController@show` and `@getPassages`; `NafisQuestionController@show/edit/update/destroy/copy/bulkDelete`; `NafisPassageQuestionController@show`; `StudentNafisExamController@checkSkillsDebug` and `@resetSubject`.
3. **Missing views:** `Pages.Teachers.nafis_exams.show`, the teacher `reports.subject` / `reports.section` / `pdf.*` views, and the route `student.nafis.exams.subject.continue`.
4. **Duplicate route names:** `detailed-result` (:240 / :263) and `final-result` (:254 / :259); the later one wins in each case.
   - `finishSubject` and `show.blade:588` pass an **exam-subject id** to `detailedResult($examId)`, which treats it as an exam id. The student gets a wrong or empty result plus «لم يتم العثور على نتائج...».
   - Routes :242, :247 and :254 resolve to `/student/student/...`.
5. **Fatal error in admin `create` for the «معلم» branch** (:371): `Teacher` is not imported, and the query uses `teachers.user_id`, which does not exist.
6. **`grade_id` vs `Grade_id`:** the same-school fallback never matches.
7. **Name-based detection:**
   - The reading branch is picked by subject name (`قراءة` / `عربي`).
   - For reading, «عدد الأسئلة» means the number of passages.
8. **Hard-coded skill ids:** 4489, 4491, 1001 (1001 is actually a math skill of subject 5), 4507, 4509, 4511, 4493, 4494, 4495 (StudentNafisExamController:2525, :2567-2600; NafisExamController:632).
9. **Hard-coded school stages and name matching:** stages `[1,2]` are hard-coded. Name patterns like `'%3%'` / `'%6%'` match any class name containing those digits.
10. **Substring skill matching:** `skills LIKE '%id%'` also matches substrings (449 matches 4493).
11. **Skill reports are wrong:** both the admin and teacher skill reports credit subject-wide results to every distributed skill. Only the student `detailedResult` is correct.
12. **Timing is not enforced on the server:** the timer is client-only and resets on refresh, and expiry is not checked when a subject starts.
13. **UTC timezone** (users are on UTC+3).
14. **`obtained_score` is already a percentage** and `total_score` is 100, so cross-subject Σobtained/Σtotal is an average of percentages.
15. **Answers are option indexes.** A question with no correct option silently defaults to option 0.
16. **The import creates `regular` questions** even from the nafis page, and it `firstOrCreate`s schools and classrooms by name.
17. **`NafisExam::teacher()`** belongs to `User`, so the «معلم» scope compares users.id with teachers.id.
18. **Dead code:** about half of `StudentNafisExamController` is commented-out duplicates, plus unrouted public methods including an echo/die debug page.
19. **Unused columns:** `reading_mode` and `passages_data`.
20. **Near-duplicate skills in subject 60:** «الهندسة والقياس» appears twice (4499 first term, 4511 second term), and «الأعداد والعمليات» (4502) overlaps «الأعداد والعمليات عليها» (4507). Decide whether to merge them before migrating.

---

## 6. Cross-program comparison (what the port must reproduce)

| aspect | qiyas | tahsili | nafis |
|---|---|---|---|
| Subjects (`subjects.type`) | 21 QL, 22 QQ | 62–65 `tahsili` | 59–61 `nafis` |
| Target students | stages 1+2, all classes | stage 3, «الثالث» | stage 1 «الثالث»/«السادس», stage 2 «الثالث» |
| Question picking | skill % × `total_questions`, filtered by `term` + `weeks[]` + student classroom; random | difficulty % + `question_order` range; no classroom filter; random, then shuffled | skill % × `total_questions` + student classroom; reading picks **N passages** and expands all sub-questions |
| When picked | lazily, at the student's first open of the subject (snapshot table) | same | same (regenerated if missing) |
| Option shuffle | no | no | no |
| Answer key | option index | option index (string) | option index |
| Timer | client only, resets on reload | client only | client only |
| Expiry | `expires_at` (UTC), checked in the list only | **never saved**, so never expires | `expires_at`, checked in the list only |
| Score stored | total 100, obtained = % | total = N questions, obtained = correct count | total 100, obtained = % |
| Subject % | correct / generated × 100 | obtained / total_questions × 100 | correct / rows (sub-questions count) × 100 |
| Multi-subject % | Σobtained / Σtotal | Σobtained / Σtotal | Σobtained / Σtotal |
| Correctness leaked during exam | no | **yes** (AJAX returns `is_correct`) | no |
| Performance levels | none | `tahsili` bands 95/85/75/65 (overridable per school) | `nafis` bands 85/75/65/50 (overridable) |
| Staff reports | **none** | `TahsiliReportController`: 5 tables + HTML-as-.xls | `NafisReportController`: 5 tables + HTML-as-.xls |
| Teacher reports | none | results, CSV (statistics view missing) | `ReportNafisExamController` (several sub-pages broken) |
| Skill mastery | none | n/a (no skills) | correct only in the student `detailedResult` (per-question skill); the staff and teacher skill reports are wrong |
| Charts library | none | none | none (tables + Bootstrap progress bars) |
| Settings | – | `tahsili_settings` / `tahsili_school_settings` weeks (informational) | – |

---

## 7. Grade-level coverage of the bank (via `classroom_question` → classrooms → grades.school_type_id)

Each program question is linked to the same class level in **all 6 schools** of that stage. Example: a nafis question for third grade (primary) links to classrooms 3, 9, 27, 33, 75, 81, which are «الصف الثالث» in 6 primary schools. The port therefore needs a **(stage, class name) → ViewClass grade-level** mapping, not a classroom-id mapping.

| program | subject | stage | class | questions |
|---|---|---|---|---|
| nafis | 59 قراءة | ابتدائية | الصف الثالث | 33 |
| nafis | 59 | ابتدائية | الصف السادس | 20 |
| nafis | 59 | متوسطة | الصف الثالث | 16 |
| nafis | 60 رياضيات | ابتدائية | الصف الثالث | 146 |
| nafis | 60 | ابتدائية | الصف السادس | 157 |
| nafis | 60 | متوسطة | الصف الثالث | 189 |
| nafis | 61 علوم | ابتدائية | الصف السادس | 274 |
| nafis | 61 | متوسطة | الصف الثالث | 197 |
| tahsili | 62 فيزياء | ثانوية | الصف الثالث | 502 |
| tahsili | 63 كيمياء | ثانوية | الصف الثالث | 608 |
| tahsili | 64 أحياء | ثانوية | الصف الثالث | 859 |
| tahsili | 65 رياضيات | ثانوية | الصف الثالث | 735 |
| qiyas | 21 لفظي | ابتدائية | الرابع / الخامس / السادس | 244 / 270 / 270 |
| qiyas | 21 | متوسطة | الثاني / الثالث | 356 / 471 |
| qiyas | 22 كمي | ابتدائية | الثاني / الثالث / الرابع / الخامس / السادس | 109 / 181 / 273 / 261 / 257 |
| qiyas | 22 | متوسطة | الأول / الثاني / الثالث | 229 / 248 / 402 |
| qiyas | 22 | ثانوية | الأول | 2 (only in 1 school; same 2 questions as متوسطة الأول) |

Note: qiyas questions target **primary and intermediate** students, not secondary. This matches the student sidebar.

The subject total equals the sum of its levels, except for 3 multi-level questions:
- nafis 18804 is linked to ابتدائية الثالث **and** متوسطة الثالث.
- qiyas 38602 and 38603 are linked to متوسطة الأول **and** ثانوية الأول.

Every program question has at least one classroom link.

Other distributions:
- Qiyas weeks 1–19: week 1 has 257 questions; weeks 16–19 have 105 / 91 / 37 / 20.
- Qiyas difficulty × term: first easy 580 / medium 697 / hard 438; second 612 / 754 / 490.
- Nafis is mostly `term=both` and `medium`.
- Tahsili: 2,686 of 2,704 are `medium/both`. **Difficulty is effectively unset for tahsili**, so difficulty-percentage picking degenerates to "all medium".

Per-skill question counts:

| skill | questions |
|---|---|
| 6216 التناظر اللفظي | 421 |
| 6217 الخطأ السياقي | 419 |
| 6218 إكمال الجمل | 418 |
| 6219 المفردة المخالفة | 295 |
| 6220 استيعاب المقروء | 58 (all 58 qiyas passages) |
| 6221 الجبر والمعادلات | 356 |
| 6222 الحساب والتقدير | 816 |
| 6223 الهندسة والمساحة | 355 |
| 6224 الإحصاء والرسوم البيانية | 221 |
| 6225 المقارنات | 212 |
| 4499 الهندسة والقياس | 91 |
| 4500 الجبر | 126 |
| 4501 البيانات والاحتمالات | 51 |
| 4502 الأعداد والعمليات | 78 |
| 4507 الأعداد والعمليات عليها | 99 |
| 4509 الجبر والدوال الحقيقيةوالتحليل | 7 |
| 4511 الهندسة والقياس (second) | 40 |
| 4493 علوم الحياة | 163 |
| 4494 علم الأرض والفلك | 115 |
| 4495 العلوم الفيزيائية والكيميائية | 193 |

Nafis reading (subject 59) has `skill_id` NULL. Its per-sub-question skills come from the `skills` JSON: 4489 ×454 sub-questions and 4491 ×297.

---

## 8. Question-bank migration notes

### 8.1 Encodings (verified by parsing every program row)

**Independent question** (qiyas 3,513 / tahsili 2,704 / nafis 963):
```
question_text = ["stem text"]                      -- JSON array, exactly 1 element
options       = [{"text":"…","is_correct":true|false}, …]   -- keys exactly {text,is_correct}
```
- Option counts: 4 options for almost all; qiyas has 5 rows with 2 options and 1 with 3; tahsili has 1 row with 5; nafis has 1 row with 2.
- **Exactly one `is_correct:true`** in every row.
- `correct_answer` is NULL, so derive the correct answer from `options`.

**Passage**: qiyas 58 rows / 117 sub-questions; nafis 68 rows / 751 sub-questions.
```
is_independent = 'passage'
passage_text   = "<passage body>"                  -- plain text; nafis bodies contain newlines
question_text  = ["sub q1", "sub q2", …]           -- len = N
options        = [[{text,is_correct}×4], …]        -- len = N, one group per sub-question, 1 correct each
skills         = ["4489","4491",…]                 -- nafis only, len = N, skill id strings; qiyas = NULL (use skill_id = 6220)
```
- Qiyas passages have 2–3 sub-questions. Nafis passages have 6–16 sub-questions (17 passages have 10).
- **Double encoding:** for 58 of the 68 nafis passages, all three JSON columns are a JSON string literal (they start with `"[`). Decode in a loop (`while is_string: json_decode`). The other 10 nafis passages and all qiyas passages are clean. The writer of the double-encoded form is identified in §5 (nafis).
- Student snapshot tables flatten passages into one row per sub-question (`sub_index`, `passage_text` copied, `question_type='passage'`).

**Real sample rows** (raw DB values, trimmed with `…`):

Qiyas, independent (id 30929, subject 21, skill 6216, easy, first term, week 1):
```
question_text = ["كتاب : ورقة"]
options       = [{"text":"فصل : مدرسة","is_correct":false},{"text":"رأس : عين","is_correct":true},{"text":"باب : خشب","is_correct":false},{"text":"نهر : شاطئ","is_correct":false}]
question_image = NULL ; correct_answer = NULL ; classroom_id = NULL ; skills = NULL ; question_order = NULL
```

Qiyas, image question (id 30953, subject 22, skill 6222):
```
question_text = ["اختر"]
question_image = uploads/questions/question_1781092344_6a294ff8ab74f.png
options       = [{"text":"أ","is_correct":true},{"text":"ب","is_correct":false},{"text":"ج","is_correct":false},{"text":"د","is_correct":false}]
```

Qiyas, passage (id 33155, subject 21, skill 6220):
```
passage_text  = عني أديسون عناية بالغة بأمور الكهرباء فكان أول اختراع له في هذا المجال جهاز (تلغرافيا) … لفصل السلك المغناطيسي.
question_text = ["عدد مكونات جهاز التليغراف:","علاقة جملة (لفصل السلك المغناطيسي) بما قبلها:"]
options       = [[{"text":"2","is_correct":false},{"text":"3","is_correct":true},{"text":"4",…},{"text":"6",…}],[{"text":"تعليل","is_correct":true},{"text":"تمثيل",…},{"text":"شرط",…},{"text":"توضيح",…}]]
skills = NULL
```

Tahsili (id 21988, subject 65, medium, term both, question_order 387):
```
question_text = ["اختر الاجابة الصحيحة"]
question_image = uploads/questions/tahsili/tahsili_1765862688_6940ed2045565.JPG
options       = [{"text":"أ","is_correct":false},{"text":"ب","is_correct":false},{"text":"ج","is_correct":true},{"text":"د","is_correct":false}]
skill_id = NULL ; week = NULL
```
Tahsili id 22006 (`question_order` 388) is the same shape, with options `a/b/c/d`.

Nafis, independent (id 17968, subject 61, skill 4493, medium, term both, week NULL):
```
question_text = ["أي الأعضاء التالية يعد من الأعضاء الملحقة بالجهاز الهضمي:"]
options       = [{"text":"الكبد","is_correct":true},{"text":"- المعدة","is_correct":false},{"text":"-المريء","is_correct":false},{"text":"الأمعاء الغليظة","is_correct":false}]
```
Nafis id 17967 is an image question: `["اختر"]` plus `uploads/questions/question_1760590608_68f07b10c3961.png`.

Nafis, passage, **double-encoded** (id 17959, subject 59, skill_id NULL). Each column is a JSON *string* containing JSON:
```
passage_text  = خلق الاعتذار\nأوصى حكيم ابنه قائلا: أي بني، اعلم أن الإنسان معرض للخطأ صغيرا كان أو كبيرًا، …
question_text = "[\"قد يصدر الخطأ من:\",\"وقوع الخطأ قد يؤدي إلى:\",\"تطلق كلمة ( خطاء ) على من يخطئ:\",\"معنى كلمة الثرى\",…]"
options       = "[[{\"text\":\"الصغير والكبير\",\"is_correct\":true},{\"text\":\"الصغير فقط\",…},…],[…],…]"
skills        = "[\"4489\",\"4489\",\"4491\",\"4491\",\"4489\",\"4489\",\"4489\",\"4491\",\"4491\",\"4489\"]"
```

**Effective item counts** (independent questions + passage sub-questions):

| program | items |
|---|---|
| qiyas | 3,513 + 117 = **3,630** |
| nafis | 963 + 751 = **1,714** |
| tahsili | **2,704** |

### 8.2 Text quirks

- `question_text` is passed through the model mutator. Newlines are collapsed to spaces at write time, and `nl2br` is applied at read time. **Read the raw column.**
- 82 nafis rows still contain literal `\n` in `question_text` (written via a non-model path).
- 2 nafis rows contain HTML-like `<…>` in the text.
- No `<img>` or LaTeX in any program question. All formulas and figures are **images**.
- Image-only stems are generic («اختر», «اختر الاجابة الصحيحة», «اختر الإجابة الصحيحة»). The real question is in the image, and the options are just «أ/ب/ج/د» or «a/b/c/d».

### 8.3 Images

| program | rows with image | extensions | path pattern |
|---|---|---|---|
| qiyas | 1,951 | png 1684 · jpeg 267 | `uploads/questions/question_<unix>_<uniqid>.<ext>` |
| tahsili | 2,702 | png 2153 · jpg 549 | 2,696 under `uploads/questions/tahsili/tahsili_<unix>_<uniqid>.<ext>`; 6 under `uploads/questions/question_…` |
| nafis | 340 | png 245 · jpeg 64 · jpg 30 · jfif 1 | `uploads/questions/question_…` |

- Paths are **relative to the web root** (the project root, because the docroot is the root). They are rendered via `asset($question_image)` / `url()`. There is no Storage disk, so the files are directly under `/home/mostafa/www/alawal/uploads/questions/` (6,749 files, plus 2,696 in `tahsili/`).
- **All 4,993 referenced files exist locally. Total 199,458,228 bytes (≈190 MiB).** No image is shared by two questions.
- Only the question stem has images. Options and passages have none.
- Some extensions are upper-case (`.JPG`), and there is 1 `.jfif` that ViewClass may need to whitelist or convert.

### 8.4 Duplicates

- `fingerprint` is an md5 (formulas in §3.C, §4.C, §5.C). Because image-only rows share text and options, it is **not** an identity:

  | program | distinct fingerprints / rows | rows in groups sharing a fingerprint |
  |---|---|---|
  | qiyas | 1,780 / 3,571 | 2,112 rows in 321 groups |
  | nafis | 883 / 1,031 | 193 rows in 45 groups |
  | tahsili | 14 / 2,704 | 2,701 rows in 11 groups; the largest group has 718 rows |

- True duplicates (same subject, question_text, options, image and passage):

  | program | rows | groups |
  |---|---|---|
  | qiyas | 327 | 149 |
  | nafis | 53 | 26 |
  | tahsili | 0 | 0 |

  Matching by **image bytes** (md5 of the file), not by path, finds more identical-image groups. None of them span two subjects:

  | program | identical-image groups | rows |
  |---|---|---|
  | qiyas | 42 | 95 |
  | tahsili | 8 | 16 |
  | nafis | 5 | 10 |

  Image-only questions with identical bytes **and** identical options are likely true duplicates. Re-check them per row before dropping any.

  Before deduping, decide whether same content with different `week`/`term`/difficulty/level counts as a duplicate. The level link differs per row.
- For idempotent import, use `source_question_id` (the Al-Awwal `questions.id`) as the key, not the fingerprint.

### 8.5 Integrity (program rows)

- 0 orphan `skill_id`. 0 `skill.subject_id ≠ question.subject_id` mismatches. 0 empty stems. 0 invalid JSON. 0 rows without a classroom link.
- 60 `classroom_question` rows point to 2 deleted classrooms. Ignore them.
- Tahsili `question_order` has gaps and repeats:

  | subject | distinct / rows | max |
  |---|---|---|
  | 62 | 495 / 502 | 504 |
  | 63 | 606 / 608 | 608 |
  | 64 | 854 / 859 | 861 |
  | 65 | 734 / 735 | 740 |

  Exams store **question ids** (`start_question_id` / `end_question_id`), so keep a `source_question_id` → new id map if old exams are migrated.
- `skills.term` for nafis 4491 and 4511 is `second`, while the question `term` is mostly `both`.

### 8.6 Suggested export query (raw, per program)

```sql
SELECT q.id AS source_id, q.exam_type, q.subject_id, s.name AS subject_name, s.type AS subject_type,
       q.skill_id, sk.name AS skill_name, q.skills AS passage_skill_ids_json,
       q.is_independent, q.passage_text, q.question_text, q.options, q.question_image,
       q.difficulty, q.term, q.week, q.question_order, q.fingerprint, q.created_at,
       GROUP_CONCAT(DISTINCT CONCAT(st.id,':',c.Name_Class)) AS levels   -- e.g. "1:الصف الثالث,2:الصف الثالث"
FROM questions q
JOIN subjects s ON s.id = q.subject_id
LEFT JOIN skills sk ON sk.id = q.skill_id
LEFT JOIN classroom_question cq ON cq.question_id = q.id
LEFT JOIN classrooms c ON c.id = cq.classroom_id
LEFT JOIN grades g ON g.id = c.Grade_id
LEFT JOIN school_types st ON st.id = g.school_type_id
WHERE q.exam_type IN ('qiyas','tahsili','nafis')
GROUP BY q.id;
```
Export with `JSON_OBJECT(...)` (or PDO), not tab-separated `mysql -B`. The JSON columns and passages contain tabs and newlines. Then normalise in code: decode JSON until it is an array; derive the correct index from `is_correct`; map `levels` (stage, class) to a ViewClass grade level; copy `question_image` files preserving the relative path.

What this import does **not** need: exam history (`*_exams`, `*_student_*`). That is student data, and nothing in the ask says to migrate it.

---

## 9. Cross-cutting gotchas

1. **`php artisan route:list` crashes** with `Class "ParentController" does not exist` (`routes/web.php:131` uses an un-imported short name). The route tables in this document come from reading the route files.
2. **Timezone UTC** (`config/app.php:72`) for a KSA client. `expires_at = now()->addHours(expiry_hours)` is stored in UTC. Display, and any "today" filters in reports, are off by 3 hours unless converted.
3. **Program gating is name-based.** Roles are matched by Arabic name (`in_array('قدرات لفظية', …)`) and stages by hard-coded `school_type_id` 1/2/3. Classroom matching uses `strpos(Name_Class,'الثالث')` / `'3'` / `'6'`, so «الصف الثالث عشر» or any class containing the digit 3 would match.
4. **Hard-coded subject ids/types:** qiyas = `type in ('QL','QQ')`, nafis = `'nafis'`, tahsili = `'tahsili'`. Several controllers also hard-code ids 21/22, 59–61, 62–65 (see per-program gotchas).
5. **Two DB-level JSON double-encodings:** nafis passages and `performance_level_grade_settings.levels_config`. Both come from a `json_encode` before an `'array'` cast or mutator.
6. **Reading the performance-level settings writes rows.** `getSettings` uses `firstOrCreate`, so opening the admin page inserts a row per school.
7. **Dead artefacts:**
   - The `Passage` model and its migrations (no table).
   - `Question::relatedQuestions` (missing columns).
   - `questions.correct_answer` and `questions.classroom_id` for programs.
   - `performance_levels` (5 rows) are only fallback data. The `getDefaultPercentages()` constants are what is actually used.
   - The `read_results` permission and the «النتائج» menu.
   - The commented-out reports block in the admin sidebar (`:545-586`).
   - The teacher-sidebar classroom-name fallbacks (they never match).
8. **The qiyas roles (9/10) cannot see «قائمة اختبارات القدرات»**, because they lack `read_exams`. They only get the question list. Meanwhile, every non-qiyas, non-islamic staff user sees «قائمة اختبارات القدرات», even secondary-only وكلاء.
9. **Missing auth on routes.** The `Route::group(['middleware'=>['auth:web']])` in `routes/web.php` covers **only `:107-387`**. Groups declared after it have **no auth middleware** unless they add their own:
   - **Unauthenticated:** `nafis_exams` (`:482`), `tahsili-questions` (`:504`), `tahsili_exams` (`:520`), `qiyas_exams` plus the `/qiyas/*` exam lookups (`:559-583`), `islamic_exams` (`:591`), `admin/teacher-exams` (`:660`), and `reports.*` (`:668`, which includes the nafis reports).
   - **With their own `auth`** (`auth` with no guard argument = the default guard `web`): `tahsili-settings` (`:612`), `admin/performance-levels` (`:627`), `supervisor/performance-levels` (`:650`), `tahsili-reports` (`:740`).
   - The question/passage groups for qiyas and nafis (`:266-357`) sit inside the auth group.
   - Note for whoever re-checks this: counting braces to find the group end is unreliable, because URIs like `{id}` contain braces. Read the file.
10. **Repo-wide sweep** (`grep -rli "qiyas|tahsili|nafis|'QL'|'QQ'"` over app, views, routes, config, lang; excluding `*_old`). Every hit is covered above except:
    - `ExamController.php:570, 604, 643, 682, 719, 747, 1104`: the **regular** exam builder excludes only `type='nafis'` subjects. Qiyas (QL/QQ) and tahsili subjects can therefore be chosen in regular exams.
    - `app/Models/Grade.php:148`: the `tahsiliSetting()` hasOne relation.
    - `StudentSelfLearningController.php:100-103`: self-learning lists subjects `type != 'nafis'`.
    - `Pages/skills/skills.blade.php` + `SkillController`: the skills CRUD that program picking depends on, through `skill_classroom` / `skill_weeks`. Routes: `web.php:114` `get-skills-by-term/{term}`; `:115` `skills/export-excel` (skills.export-excel); `:117` `Route::resource('skills')`; `:118` `skills/import` (skills.import). It has a «القدرات فقط» filter (`filter_type=qiyas`).
    - **Not found by the sweep:** dashboards (`HomeController`, `dashboard.blade.php`), `ParentStatisticsController`, `QuestionBankInfoController` / `question-bank.blade.php`, `ReportController`, exports, console commands and `run_cron.php`. None of them mention the programs.
11. **`programs` / `student_program_progress` / `student_program_questions` are unrelated.** They belong to a student "training program" feature (`Students\ProgramController`, `student.php:148-156`; 5 stages «البرنامج الأول…الخامس») that runs on regular questions. Out of scope.
12. **Columns the flows rely on:**
    - `students.Grade_id` = school, `students.Classroom_id`, `students.section_id` (exam visibility is decided by `section_id`).
    - `students.name` is text.
    - `complexes.supervisor_id` (مشرف تعليمي scope). Note there is also a stray `supervisor_id11` column.

---

## Appendix A: exact DDL of the program tables (from `SHOW CREATE TABLE`, `*_bak_*` excluded)

```sql
CREATE TABLE `questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fingerprint` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exam_type` enum('regular','nafis','tahsili','qiyas') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `question_text` longtext COLLATE utf8mb4_unicode_ci,
  `question_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` enum('first','second','third','both') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `week` tinyint unsigned DEFAULT NULL,
  `skill_id` bigint unsigned DEFAULT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `type` enum('multiple_choice','true_false') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `question_order` int DEFAULT NULL,
  `correct_answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `classroom_id` bigint unsigned DEFAULT NULL,
  `is_independent` enum('independent','passage') COLLATE utf8mb4_unicode_ci DEFAULT 'independent',
  `passage_text` longtext COLLATE utf8mb4_unicode_ci,
  `options` longtext COLLATE utf8mb4_unicode_ci,
  `skills` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `questions_skill_id_foreign` (`skill_id`),
  KEY `questions_subject_id_foreign` (`subject_id`),
  KEY `questions_classroom_id_foreign` (`classroom_id`),
  KEY `idx_tahsili_order` (`subject_id`,`exam_type`,`question_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `term` enum('first','second','third') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `skills_subject_id_foreign` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `skill_classroom` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `skill_id` bigint unsigned NOT NULL,
  `classroom_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `skill_classroom_skill_id_foreign` (`skill_id`),
  KEY `skill_classroom_classroom_id_foreign` (`classroom_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `skill_weeks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `skill_id` bigint unsigned NOT NULL,
  `week` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `skill_weeks_skill_id_foreign` (`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `classroom_question` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint unsigned NOT NULL,
  `classroom_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classroom_question_question_id_foreign` (`question_id`),
  KEY `classroom_question_classroom_id_foreign` (`classroom_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `classroom_subject` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` bigint unsigned NOT NULL,
  `classroom_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `classroom_subject_subject_id_classroom_id_unique` (`subject_id`,`classroom_id`),
  KEY `classroom_subject_classroom_id_foreign` (`classroom_id`),
  CONSTRAINT `classroom_subject_classroom_id_foreign` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classroom_subject_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `classrooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `Grade_id` bigint unsigned NOT NULL,
  `Name_Class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_type_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classrooms_school_type_id_foreign` (`school_type_id`),
  KEY `classrooms_grade_id_foreign` (`Grade_id`),
  CONSTRAINT `classrooms_grade_id_foreign` FOREIGN KEY (`Grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classrooms_school_type_id_foreign` FOREIGN KEY (`school_type_id`) REFERENCES `school_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `grades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `Name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complex_id` bigint unsigned NOT NULL,
  `school_type_id` bigint unsigned DEFAULT NULL,
  `grade_type_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `supervisor_id` bigint unsigned DEFAULT NULL,
  `deputy_supervisor_id` bigint unsigned DEFAULT NULL,
  `head_id` int DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stamp_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `center_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grades_complex_id_foreign` (`complex_id`),
  KEY `grades_grade_type_id_foreign` (`grade_type_id`),
  KEY `grades_school_type_id_foreign` (`school_type_id`),
  KEY `grades_deputy_supervisor_id_foreign` (`deputy_supervisor_id`),
  CONSTRAINT `grades_deputy_supervisor_id_foreign` FOREIGN KEY (`deputy_supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `school_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `Name_Section` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Grade_id` bigint unsigned NOT NULL,
  `Class_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sections_grade_id_foreign` (`Grade_id`),
  KEY `sections_class_id_foreign` (`Class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `teacher_section_subject` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `classroom_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_section_subject_teacher_id_foreign` (`teacher_id`),
  KEY `teacher_section_subject_section_id_foreign` (`section_id`),
  KEY `teacher_section_subject_subject_id_foreign` (`subject_id`),
  KEY `teacher_section_subject_classroom_id_foreign` (`classroom_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `weeks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `term` enum('first','second') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'first',
  `week` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `qiyas_exams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('single','multi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `grade_id` bigint unsigned NOT NULL,
  `classroom_id` bigint unsigned NOT NULL,
  `complex_id` bigint unsigned DEFAULT NULL,
  `expiry_hours` int NOT NULL DEFAULT '24',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `created_by_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qiyas_exams_grade_id_foreign` (`grade_id`),
  KEY `qiyas_exams_classroom_id_foreign` (`classroom_id`),
  KEY `qiyas_exams_complex_id_foreign` (`complex_id`),
  KEY `qiyas_exams_created_by_foreign` (`created_by`),
  KEY `qiyas_exams_teacher_id_index` (`teacher_id`),
  CONSTRAINT `qiyas_exams_classroom_id_foreign` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exams_complex_id_foreign` FOREIGN KEY (`complex_id`) REFERENCES `complexes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qiyas_exams_grade_id_foreign` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `qiyas_exam_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `qiyas_exam_id` bigint unsigned NOT NULL,
  `section_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qiyas_exam_sections_qiyas_exam_id_foreign` (`qiyas_exam_id`),
  KEY `qiyas_exam_sections_section_id_foreign` (`section_id`),
  CONSTRAINT `qiyas_exam_sections_qiyas_exam_id_foreign` FOREIGN KEY (`qiyas_exam_id`) REFERENCES `qiyas_exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_sections_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `qiyas_exam_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `qiyas_exam_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `duration_minutes` int NOT NULL DEFAULT '60',
  `total_questions` int NOT NULL DEFAULT '20',
  `order_index` int NOT NULL DEFAULT '1',
  `term` enum('first','second') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'first',
  `weeks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qiyas_exam_subjects_qiyas_exam_id_foreign` (`qiyas_exam_id`),
  KEY `qiyas_exam_subjects_subject_id_foreign` (`subject_id`),
  KEY `qiyas_exam_subjects_teacher_id_foreign` (`teacher_id`),
  CONSTRAINT `qiyas_exam_subjects_qiyas_exam_id_foreign` FOREIGN KEY (`qiyas_exam_id`) REFERENCES `qiyas_exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_subjects_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qiyas_exam_subjects_chk_1` CHECK (json_valid(`weeks`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `qiyas_exam_skill_distribution` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `qiyas_exam_subject_id` bigint unsigned NOT NULL,
  `skill_id` bigint unsigned NOT NULL,
  `percentage` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qiyas_exam_skill_distribution_qiyas_exam_subject_id_foreign` (`qiyas_exam_subject_id`),
  KEY `qiyas_exam_skill_distribution_skill_id_foreign` (`skill_id`),
  CONSTRAINT `qiyas_exam_skill_distribution_qiyas_exam_subject_id_foreign` FOREIGN KEY (`qiyas_exam_subject_id`) REFERENCES `qiyas_exam_subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_skill_distribution_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `qiyas_exam_student_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `qiyas_exam_subject_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `sub_index` int NOT NULL DEFAULT '0',
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `correct_answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passage_text` text COLLATE utf8mb4_unicode_ci,
  `order_index` int NOT NULL DEFAULT '1',
  `question_type` enum('independent','passage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'independent',
  `skill_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qiyas_exam_student_questions_student_id_index` (`student_id`),
  KEY `qiyas_exam_student_questions_qiyas_exam_subject_id_index` (`qiyas_exam_subject_id`),
  KEY `qiyas_exam_student_questions_question_id_index` (`question_id`),
  KEY `qiyas_exam_student_questions_skill_id_index` (`skill_id`),
  KEY `qiyas_exam_student_questions_order_index_index` (`order_index`),
  CONSTRAINT `qiyas_exam_student_questions_qiyas_exam_subject_id_foreign` FOREIGN KEY (`qiyas_exam_subject_id`) REFERENCES `qiyas_exam_subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_student_questions_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_student_questions_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qiyas_exam_student_questions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_student_questions_chk_1` CHECK (json_valid(`options_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `qiyas_exam_student_answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `qiyas_exam_subject_id` bigint unsigned NOT NULL,
  `qiyas_student_question_id` bigint unsigned NOT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `score` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_qiyas_student_answer` (`qiyas_student_question_id`,`student_id`),
  KEY `qiyas_exam_student_answers_student_id_index` (`student_id`),
  KEY `qiyas_exam_student_answers_qiyas_exam_subject_id_index` (`qiyas_exam_subject_id`),
  KEY `qiyas_exam_student_answers_qiyas_student_question_id_index` (`qiyas_student_question_id`),
  CONSTRAINT `qiyas_exam_student_answers_qiyas_exam_subject_id_foreign` FOREIGN KEY (`qiyas_exam_subject_id`) REFERENCES `qiyas_exam_subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_student_answers_qiyas_student_question_id_foreign` FOREIGN KEY (`qiyas_student_question_id`) REFERENCES `qiyas_exam_student_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_student_answers_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `qiyas_exam_student_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `qiyas_exam_subject_id` bigint unsigned NOT NULL,
  `status` enum('pending','completed','auto_finished') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_score` int NOT NULL DEFAULT '0',
  `obtained_score` int NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `time_spent` int NOT NULL DEFAULT '0',
  `last_saved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_qiyas_subject` (`student_id`,`qiyas_exam_subject_id`),
  KEY `qiyas_exam_student_results_student_id_index` (`student_id`),
  KEY `qiyas_exam_student_results_qiyas_exam_subject_id_index` (`qiyas_exam_subject_id`),
  CONSTRAINT `qiyas_exam_student_results_qiyas_exam_subject_id_foreign` FOREIGN KEY (`qiyas_exam_subject_id`) REFERENCES `qiyas_exam_subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qiyas_exam_student_results_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_exams` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('single','multi') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'single',
  `classroom_id` bigint NOT NULL,
  `expiry_hours` int DEFAULT '48',
  `expires_at` datetime DEFAULT NULL,
  `weeks_count` int DEFAULT NULL,
  `grade_id` bigint NOT NULL,
  `complex_id` bigint NOT NULL,
  `created_by` bigint NOT NULL,
  `created_by_type` enum('admin','teacher','supervisor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin' COMMENT 'نوع منشئ الاختبار: admin, teacher, supervisor',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tahsili_exams_created_by_type_index` (`created_by_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_exam_sections` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tahsili_exam_id` bigint NOT NULL,
  `section_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tahsili_exam_sections_exam` (`tahsili_exam_id`),
  KEY `idx_tahsili_exam_sections_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_exam_subjects` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tahsili_exam_id` bigint NOT NULL,
  `subject_id` bigint NOT NULL,
  `duration_minutes` int NOT NULL,
  `total_questions` int DEFAULT '0',
  `order_index` int DEFAULT '1',
  `teacher_id` bigint unsigned DEFAULT NULL,
  `easy_percentage` int DEFAULT '0',
  `medium_percentage` int DEFAULT '0',
  `hard_percentage` int DEFAULT '0',
  `start_question_id` bigint DEFAULT NULL,
  `end_question_id` bigint DEFAULT NULL,
  `total_available_questions` bigint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tahsili_exam_subjects_exam` (`tahsili_exam_id`),
  KEY `idx_tahsili_exam_subjects_subject` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_exam_student_questions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `tahsili_exam_subject_id` bigint NOT NULL,
  `question_id` bigint NOT NULL,
  `question_original_order` int DEFAULT NULL,
  `sub_index` int DEFAULT NULL,
  `question_text` longtext COLLATE utf8mb4_unicode_ci,
  `options_json` longtext COLLATE utf8mb4_unicode_ci,
  `correct_answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_index` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tahsili_questions_student` (`student_id`),
  KEY `idx_tahsili_questions_subject` (`tahsili_exam_subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_exam_student_answers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `tahsili_exam_subject_id` bigint NOT NULL,
  `tahsili_student_question_id` bigint NOT NULL,
  `answer_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT '0',
  `score` float DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tahsili_answers_student` (`student_id`),
  KEY `idx_tahsili_answers_subject` (`tahsili_exam_subject_id`),
  KEY `idx_tahsili_answers_question` (`tahsili_student_question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_exam_student_results` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `tahsili_exam_subject_id` bigint NOT NULL,
  `total_score` float DEFAULT '0',
  `obtained_score` float DEFAULT '0',
  `status` enum('pending','completed','timeout') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tahsili_results_student` (`student_id`),
  KEY `idx_tahsili_results_subject` (`tahsili_exam_subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `weeks_count` int NOT NULL DEFAULT '10' COMMENT 'عدد الأسابيع الافتراضي لجميع المدارس',
  `allow_editing_by_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل يسمح للمشرفين بالتعديل افتراضياً',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tahsili_settings_created_by_foreign` (`created_by`),
  KEY `tahsili_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `tahsili_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tahsili_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `tahsili_school_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grade_id` int NOT NULL COMMENT 'معرف المدرسة',
  `weeks_count` int NOT NULL DEFAULT '10' COMMENT 'عدد الأسابيع',
  `allow_editing` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=مسموح للمشرفين بالتعديل، 0=غير مسموح',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade` (`grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `nafis_exams` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `type` enum('single','multi') NOT NULL DEFAULT 'single',
  `classroom_id` bigint NOT NULL,
  `grade_id` bigint NOT NULL,
  `complex_id` bigint NOT NULL,
  `created_by` bigint NOT NULL,
  `created_by_type` enum('admin','teacher') DEFAULT 'admin',
  `teacher_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expiry_hours` int NOT NULL DEFAULT '24' COMMENT 'عدد ساعات صلاحية دخول الاختبار',
  `expires_at` datetime DEFAULT NULL COMMENT 'تاريخ انتهاء صلاحية دخول الاختبار',
  PRIMARY KEY (`id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_grade_class` (`grade_id`,`classroom_id`),
  KEY `fk_nafis_exams_teacher` (`teacher_id`),
  CONSTRAINT `fk_nafis_exams_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;

CREATE TABLE `nafis_exam_sections` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `nafis_exam_id` bigint NOT NULL,
  `section_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;

CREATE TABLE `nafis_exam_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nafis_exam_id` bigint NOT NULL,
  `subject_id` bigint NOT NULL,
  `teacher_id` bigint DEFAULT NULL,
  `reading_mode` enum('detailed','passages') NOT NULL DEFAULT 'detailed',
  `passages_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `duration_minutes` int NOT NULL,
  `total_questions` int DEFAULT '0',
  `order_index` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `nafis_exam_subjects_chk_1` CHECK (json_valid(`passages_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;

CREATE TABLE `nafis_exam_skill_distribution` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nafis_exam_subject_id` bigint unsigned NOT NULL,
  `skill_id` bigint unsigned NOT NULL,
  `percentage` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_subject` (`nafis_exam_subject_id`),
  KEY `idx_skill` (`skill_id`),
  CONSTRAINT `fk_nafis_exam_subject` FOREIGN KEY (`nafis_exam_subject_id`) REFERENCES `nafis_exam_subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

CREATE TABLE `nafis_exam_student_questions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `nafis_exam_subject_id` bigint NOT NULL,
  `question_id` bigint NOT NULL,
  `sub_index` int DEFAULT NULL,
  `question_text` longtext,
  `options_json` longtext,
  `passage_text` text,
  `question_type` enum('independent','passage') DEFAULT 'independent',
  `correct_answer` varchar(255) DEFAULT NULL,
  `order_index` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_saved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;

CREATE TABLE `nafis_exam_student_answers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `nafis_exam_subject_id` bigint NOT NULL,
  `nafis_student_question_id` bigint NOT NULL,
  `answer_text` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT '0',
  `score` float DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;

CREATE TABLE `nafis_exam_student_results` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint NOT NULL,
  `nafis_exam_subject_id` bigint NOT NULL,
  `total_score` float DEFAULT '0',
  `obtained_score` float DEFAULT '0',
  `status` enum('pending','completed','timeout','in_progress') DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `last_saved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
;

CREATE TABLE `performance_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'اسم المستوى (ممتاز، جيد جداً، إلخ)',
  `name_en` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'الاسم بالإنجليزية',
  `min_percentage` int NOT NULL DEFAULT '0' COMMENT 'الحد الأدنى للنسبة المئوية',
  `max_percentage` int NOT NULL DEFAULT '100' COMMENT 'الحد الأقصى للنسبة المئوية',
  `color` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'secondary' COMMENT 'لون العرض (success, info, warning, danger)',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'أيقونة FontAwesome',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'ترتيب العرض',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل هو إعداد افتراضي؟',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'هل المستوى نشط؟',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_performance_levels_active` (`is_active`),
  KEY `idx_performance_levels_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول المستويات الأساسية للأداء'
;

CREATE TABLE `performance_level_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `allow_editing_by_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'السماح للمشرفين بالتعديل افتراضياً (0=لا, 1=نعم)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'من قام بالإنشاء',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT 'من قام بالتحديث',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='الإعدادات العامة لمستويات الأداء'
;

CREATE TABLE `performance_level_grade_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grade_id` bigint unsigned NOT NULL COMMENT 'معرف المدرسة',
  `exam_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `allow_editing` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'السماح لمشرف المدرسة بالتعديل (0=لا, 1=نعم)',
  `levels_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'إعدادات المستويات الخاصة بالمدرسة (JSON)',
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'من قام بالإنشاء',
  `updated_by` bigint unsigned DEFAULT NULL COMMENT 'من قام بالتحديث',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade_exam_type` (`grade_id`,`exam_type`),
  KEY `idx_performance_level_grade_settings_grade` (`grade_id`),
  KEY `idx_performance_level_grade_settings_editing` (`allow_editing`),
  CONSTRAINT `fk_performance_level_grade_settings_grade` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `performance_level_grade_settings_chk_1` CHECK (json_valid(`levels_config`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='إعدادات مستويات الأداء لكل مدرسة'
;

```
