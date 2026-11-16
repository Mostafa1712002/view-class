# Requirements: ViewClass sprint 2026-11-16 (Trello «sprint prompt», 10 cards by Gad Reda)

Source: 10 cards #365–#374 + «Review Report» (#364, ViewClass_Project_Dashboard.html) + Drive folder
(ما تم انتهاء منه.docx, قائمة المنصة.docx, نقاط التحفيز.docx, الميت الرابع.mp4 not watched).
Tester: Mohamed Farouk. Done = live on viewclass.newaves-systems.com AND rawaniedu.com → «testing prompt».

## Card → acceptance (EARS)
- #374 RBAC: WHEN a teacher/student/parent/school-admin requests any super-admin-only page THE SYSTEM SHALL return 403; an automated test enumerates those routes.
- #373 Seeders: WHEN the demo school has seeded activity THE dashboard SHALL show real non-zero rates/counts (repo currently returns hardcoded 0 → wire real queries first). Demo data is idempotent, tagged, removable; seeded on viewclass demo school only, never on rawaniedu.
- #365 Companies/complexes: CRUD for both works without errors, super-admin only, complex↔company link holds.
- #366 Promotion: WHEN a student was already promoted into year Y by an executed (not rolled back) batch THE SYSTEM SHALL refuse to promote them into Y again (per student, all promote paths); history keeps actor + timestamp; sidebar «الترحيل» opens the page.
- #367 Terms & weeks: weeks have start/end dates; WHEN today falls inside a week THE SYSTEM SHALL mark it «الأسبوع الحالي» (computed from date, Asia/Riyadh, no stored flag); sidebar opens the page.
- #369 Distance learning: sidebar «التعلم عن بعد» opens a hub of virtual classes + discussion rooms; scheduling a virtual class and recording attendance works end-to-end.
- #370 Exams: full lifecycle create → questions → publish → student attempt → auto-grade → result reaches grade reports; sidebar «الاختبارات» opens it.
- #371 Points: admin-defined point rules (global defaults, school override), ledger per student, balance + history; auto-award on configured activities (correct answers, approved portfolio entry).
- #372 Coaching: case files with category, assigned counsellor, notes, status; only the assigned counsellor + super/school-admin with explicit permission can read a case (fail-closed, per record).
- #368 Special programs (قدرات/تحصيلي/نافس): native (inspired by الأول بلس, no API); add students to a program, record scores, per-student progress report.

## Cross-cutting
- No FK to users.id in new tables (type differs between installs).
- New permissions via migration + JobTitlePermissionsController::MODULES.
- School scoping via scopedSchoolId(); test as super-admin (null school) and school-admin.
- Sidebar order untouched (meeting 4 unapproved); only swap coming-soon hrefs.
