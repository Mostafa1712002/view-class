# Requirements: البرامج التعليمية الخاصة (قدرات · تحصيلي · نافس) — Trello #368

Card: «الشؤون التعليمية والخطط - تنفيذ البرامج التعليمية الخاصة (قدرات - تحصيلي - نافس)».
**Acceptance (card):** add a student to a قدرات program and follow his performance report.

Scope: port the three programs of «الأول» — same screens, flows, role experiences, reports and Arabic labels — into ViewClass, fixing the defects listed in `alawwal-source-map.md` (SM) and `design.md` §13. Out of scope: «إسلاميات» (subjects 66–76), «أنا والقدرات», «القراءة السريعة», migrating «الأول» exam history, the points listener (#371).

Roles: **SA** super-admin «مدير النظام» · **ADM** school-admin and supervisor/vice job titles · **TCH** teacher assigned to a program · **QE** question editor (holder of `special_programs.questions`) · **STU** student · **PAR** parent.

---

## 1. Program hub and setup

### US-ADM-1: Program hub
**As** ADM **I want** one entry «البرامج التعليمية الخاصة» showing قدرات / تحصيلي / نافس **so that** I manage all three from one place.
- WHEN ADM opens the sidebar item «البرامج التعليمية الخاصة» (or «الأول بلس») THE SYSTEM SHALL show three program cards with enrolled students, groups, exams and mean score for the active school.
- WHEN a program's bank has no questions THE SYSTEM SHALL show «لم يتم استيراد بنك الأسئلة لهذا البرنامج بعد» and disable exam creation for it, without errors.
- WHEN SA uses the header school switcher THE SYSTEM SHALL scope every program page to that school.

### US-ADM-2: Enrol students («إضافة طالب لبرنامج»)
- WHEN ADM opens «إضافة طالب لبرنامج» THE SYSTEM SHALL list the classes of the program's target grades (nafis 3, 6, 9; qiyas and tahsili 10–12, by `classes.grade_level`) and their students, searchable by name or national id.
- WHEN ADM selects students (or «إضافة كل طلاب الفصل») and saves THE SYSTEM SHALL enrol each once per (school, program); an already-enrolled student SHALL be shown as enrolled, and re-enrolling a removed student SHALL restore the same enrolment.
- WHEN the school has classes without a grade number THE SYSTEM SHALL show a warning naming them («توجد فصول بدون رقم صف…»); WHEN no class matches the target grades THE SYSTEM SHALL say so and offer «عرض كل الفصول», marking such classes «خارج الصفوف المستهدفة». The list SHALL never be silently empty.
- WHEN ADM removes a student THE SYSTEM SHALL keep his attempts and results.

### US-ADM-3: Performance groups («مجموعات الأداء»)
- WHEN ADM creates a group THE SYSTEM SHALL store its name, program, optional «المعلم المسؤول» and optional «مستوى الأداء المستهدف».
- WHEN ADM adds students to a group THE SYSTEM SHALL only offer students enrolled in that program, filterable by their current performance level; a student SHALL belong to at most one group per program.
- WHEN a group is used as an exam target THE SYSTEM SHALL make the exam visible to its members.

### US-ADM-4: Program teachers («معلمو البرنامج»)
- WHEN ADM assigns a teacher to a program, optionally to one subject (e.g. «القدرات اللفظية» only), THE SYSTEM SHALL let that teacher create exams only for the assigned subject(s) and see only their results.

### US-ADM-5: Program exams (admin)
- WHEN ADM creates «اختبار قدرات / تحصيلي / نافس» THE SYSTEM SHALL capture the «الأول» fields: title, single/multi, target classes and/or groups, validity hours, and per subject: duration, question count, order, teacher; plus qiyas term + weeks 1–19 + skill %, nafis skill % (reading: number of passages), tahsili easy/medium/hard % + question-order range.
- WHEN skill percentages sum to 0 or more than 100, or tahsili difficulty percentages do not sum to 100, or a range start exceeds its end THE SYSTEM SHALL reject the form before saving anything.
- WHEN ADM opens the builder THE SYSTEM SHALL show available question counts per skill, difficulty, level and range, and warn when the range holds fewer questions than requested.
- WHEN the target classes' grades hold fewer questions than requested in a subject THE SYSTEM SHALL default «مستوى الأسئلة» to the whole subject bank and show a warning, and the picker SHALL drop the level filter before returning fewer questions than requested.
- WHEN an exam is created THE SYSTEM SHALL save `expires_at` = creation time + validity hours (app timezone).
- WHEN ADM deletes an exam THE SYSTEM SHALL soft-delete it, keep its attempts, and hide it from lists and reports.
- WHEN ADM opens an exam THE SYSTEM SHALL show per subject which audience students have not started, are in progress, completed, or timed out.

### US-ADM-6: Settings
- WHEN SA edits global performance levels for a program THE SYSTEM SHALL store 5 bands (ممتاز، جيد جداً، جيد، مقبول، ضعيف) that cover 0–100 without overlap; defaults: tahsili 95/85/75/65, nafis 85/75/65/50, qiyas 90/80/70/60.
- WHEN SA allows a school to edit THE SYSTEM SHALL let that school's ADM (permission `special_programs.settings`) override the bands for his school; otherwise the page SHALL be read-only.
- WHEN SA edits «الصفوف المستهدفة» of a program THE SYSTEM SHALL use them for eligibility everywhere.
- WHEN SA or an allowed school edits «إعدادات التحصيلي» THE SYSTEM SHALL store the weeks count used by the builder's informational weekly count.
- WHEN any user only views settings THE SYSTEM SHALL NOT write any row.

---

## 2. Question bank

### US-QE-1: Program question lists
- WHEN SA or QE opens «بنك أسئلة {subject}» THE SYSTEM SHALL list the subject's questions with the «الأول» columns (المادة، نص السؤال، المهارة، الفصل الدراسي، الأسبوع، مستوى الصعوبة، الصفوف، الإجابة الصحيحة) and filters (search ignoring tashkeel, skill, term, week, difficulty, grade, question_order for tahsili), plus «قطع القراءة» for passages.
- WHEN QE is limited to a subject (e.g. «قدرات لفظية») THE SYSTEM SHALL show and accept edits for that subject only.

### US-QE-2: Add / edit questions
- WHEN QE saves an independent question THE SYSTEM SHALL require stem, ≥2 options with exactly one correct, skill (qiyas/nafis), term, week (qiyas 1–19), difficulty, grade levels and accept one image (png/jpg/jpeg, size-limited); tahsili SHALL accept «أضف السؤال بعد رقم» and renumber `question_order` atomically.
- WHEN QE saves a passage THE SYSTEM SHALL store the passage text and N sub-questions, each with its own options, correct answer and (nafis) skill.
- WHEN QE deletes a question used by any attempt THE SYSTEM SHALL deactivate it instead of deleting, so past results stay intact.

---

## 3. Teacher

### US-TCH-1: Teacher exams
- WHEN TCH opens «اختبارات القدرات / التحصيلي / نافس» THE SYSTEM SHALL list only exams he created or teaches.
- WHEN TCH creates an exam THE SYSTEM SHALL allow one subject from his assignment, targets only among his classes and the groups he leads, duration 15–180 min, 1–100 questions, validity 1–168 h.
- WHEN TCH opens his exam THE SYSTEM SHALL show results per student and export them to Excel.

### US-TCH-2: Teacher reports
- WHEN TCH opens «تقاريري» THE SYSTEM SHALL show per exam total / completed / pending students and mean %, and per exam: average, highest, lowest, top 10, weakest 10, level distribution, and per-student skill analysis computed from each answer's own skill.

---

## 4. Student

### US-STU-1: My program exams
- WHEN STU opens «البرامج التعليمية» THE SYSTEM SHALL list exams of programs he is enrolled in where he is in a target class or target group, with status «متاح / قيد التنفيذ / مكتمل / انتهت الصلاحية».
- WHEN STU opens a multi-subject exam THE SYSTEM SHALL show subjects in order and refuse, on the server, to start a subject before the previous ones are completed («يجب إكمال اختبار {subject} أولاً»).
- WHEN the exam has expired and STU has not started the subject THE SYSTEM SHALL refuse to start it.

### US-STU-2: Taking a subject
- WHEN STU starts a subject for the first time THE SYSTEM SHALL generate his own fixed question set by the program's rules (qiyas: skill % × count by term/weeks; nafis: skill % × count, reading = N whole passages; tahsili: difficulty % × count within the question-order range) and never generate a second set, even from two tabs.
- WHEN a passage is picked THE SYSTEM SHALL include the passage text and all of its sub-questions together.
- WHILE the attempt is open THE SYSTEM SHALL show the time left computed from the server deadline; WHEN STU reloads the page THE SYSTEM SHALL keep the same deadline and restore his saved answers.
- WHEN STU chooses an option THE SYSTEM SHALL save it immediately and SHALL NOT reveal whether it is correct.
- WHEN an answer arrives after the deadline (5 s grace) THE SYSTEM SHALL reject it.
- WHEN the deadline passes without finishing THE SYSTEM SHALL close the attempt as «انتهى الوقت» and score the saved answers (unanswered = wrong), at the next request or within 5 minutes.
- WHERE the exam sets a maximum number of exits THE SYSTEM SHALL count window exits and auto-finish at the limit.
- THE SYSTEM SHALL keep option order as stored (no option shuffle).

### US-STU-3: Results and my report
- WHEN a subject is closed THE SYSTEM SHALL show score (correct / total), %, performance level, time spent, per-skill (qiyas, nafis) or per-difficulty (tahsili) results and a review with the correct options.
- WHEN all subjects are closed THE SYSTEM SHALL show the exam % = total correct / total questions.
- WHEN STU opens «تقرير أدائي» THE SYSTEM SHALL show his program level, score trend, per-subject and per-skill mastery and attempt history.

---

## 5. Parent

### US-PAR-1: Child's program report
- WHEN PAR opens «البرامج التعليمية» for one of his children THE SYSTEM SHALL show that child's performance report read-only; for any other child id THE SYSTEM SHALL return 403/404.

---

## 6. Reports (ADM, SA, TCH scoped)

### US-REP-1: Program reports
- WHEN ADM opens «تقارير اختبارات {القدرات|التحصيلي|نافس}» with at least one filter (school for SA, grade, class, group, subject, exam origin admin/teacher, date range, exam) THE SYSTEM SHALL show: student results, performance by subject, performance by skill (qiyas, nafis) or by difficulty (tahsili), exam statistics with participation = takers / audience, grade distribution in the program's bands, and class performance.
- THE SYSTEM SHALL credit each answer only to its own question's skill.
- THE SYSTEM SHALL compute every level with the program's bands for the student's school.
- WHEN the user clicks «تصدير Excel» on a section THE SYSTEM SHALL download a real .xlsx (RTL) of that section; «طباعة» SHALL produce a PDF.

### US-REP-2: Student performance report (acceptance)
- WHEN ADM, the student's program teacher, the student or his parent opens a student's program report THE SYSTEM SHALL show current level, trend chart, per-subject and per-skill/difficulty tables and attempt history, and export it as PDF.

---

## 7. Import of the «الأول» bank

### US-IMP-1: Import command
- WHEN SA runs `special-programs:import-alawwal` against an Al-Awwal DB connection and an images directory THE SYSTEM SHALL import all qiyas (≈3,630 items incl. passage sub-questions), nafis (≈1,714) and tahsili (2,704) questions, passages, skills and referenced images into one owner bank per program subject.
- THE SYSTEM SHALL derive the correct answer from the options JSON, decode multiply-encoded JSON, split passages into sub-questions with their own skills, map grade levels from (stage, class name) to ViewClass grades, copy images under `storage/app/public/special-programs/<program>/`, and store the Al-Awwal id in `bank_questions.external_id`.
- WHEN two source rows have the same normalised content and image bytes THE SYSTEM SHALL keep the lowest id and merge the others' grade levels into it, listing every merge in the report.
- WHEN run with `--dry-run` THE SYSTEM SHALL write nothing and print the same summary.
- WHEN run again THE SYSTEM SHALL change nothing that is already imported (idempotent) and report rows as unchanged.
- WHEN a row cannot be decoded or an image is missing THE SYSTEM SHALL continue, and list the row in the report.
- THE SYSTEM SHALL run the import on rawaniedu.com only; migrations SHALL NOT create bank rows.

---

## 8. Integration

### US-INT-1: Points hook (#371)
- WHEN a program attempt closes (finish, timeout or auto-end) THE SYSTEM SHALL dispatch `ProgramAttemptCompleted` exactly once, after commit, with the payload in `design.md` §10.

---

## Non-functional requirements

### NFR-001 Tenant scope
- Every staff query filters by the active school inside repositories; another school's id SHALL return 404, never data. Teachers are further limited to their own exams/subjects/students; parents to their children; students to themselves.

### NFR-002 Security
- Every route requires `auth` + a role; staff routes also `permission:special_programs.<action>`. Super-admin-only pages SHALL 403 for others before any lookup (`RbacSuperAdminRoutesTest` green). Correct answers SHALL NOT be sent to the browser before an attempt closes. Uploaded images are validated by mime and size.

### NFR-003 Performance
- The bank holds ~8k items, ~30k answer rows and ~5k images; picking a subject's questions SHALL use indexed filters (subject, skill, term, order, level) and finish in under 1 s for 100 questions.
- Reports SHALL aggregate in SQL (no per-row queries) and stay under 3 s for 50k attempt items in a school.
- The take page SHALL load all items of an attempt in O(1) queries (no per-question lookup).

### NFR-004 Integrity and audit
- Each attempt freezes its questions, options, correct index, skill and difficulty at generation; later bank edits SHALL NOT change past scores or reports.
- Attempts record start, deadline, end, time spent, exit count and final status; deleted exams and removed enrolments keep their attempts.
- Times are stored and shown in the app timezone (Asia/Riyadh).

### NFR-005 Idempotent, additive rollout
- Migrations are additive and run on both servers; the import is re-runnable with a dry-run and a JSON report.

### NFR-006 i18n and UI
- All labels in `lang/ar` and `lang/en`; Arabic wording follows «الأول» labels; RTL; `<x-svg-icon>` icons; design-system tokens.

---

## Open questions for the client

1. **Qiyas grades.** In «الأول» the qiyas bank is written for grades 2–9 (primary 2–6, intermediate 1–3), but the decision targets 10–12. Until told otherwise, 10–12 students draw from the whole qiyas bank with a warning. Should qiyas also open to grades 4–9?
2. **Performance groups.** «الأول» has no groups. Default: groups are made by hand (name + teacher + optional target level). Should they instead be filled automatically by level?
3. **Duplicates.** 327 qiyas and 53 nafis rows are exact content duplicates. Default: merged (report lists them). Keep them separate instead?
4. **Near-duplicate nafis skills** («الهندسة والقياس» ×2, «الأعداد والعمليات» vs «…عليها»): kept separate by default. Merge?
