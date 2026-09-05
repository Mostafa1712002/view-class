# Design: إدارة ملف أنجاز

## Data
- Migration `portfolio_entries`: `id, user_id (FK users), section (string), title, body (text null),
  attachment_path (string null), entry_date (date null), created_by, timestamps, softDeletes`. Index (user_id, section).
- Model `App\Models\PortfolioEntry` with `SECTIONS_TEACHER` / `SECTIONS_STUDENT` const arrays (key→Arabic label).
  Scope by the owner user's `school_id` (multi-tenant) at repository/query level.
- No change to existing tables. Auto sections are computed, not stored.

## Controllers / routes
- Admin: `PortfolioController` (index with `?tab=teacher|student`, showUser, entry store/update/destroy).
  Routes `admin.portfolio.*` in `routes/web.php` (super-admin/school-admin, scoped).
- Teacher portal: add `teacher.portfolio` (mirror of `student.portfolio`) → `TeacherController::portfolio`.
- Student: reuse existing `student.portfolio`.

## Views
- `resources/views/admin/portfolio/index.blade.php` — two tabs (reuse nav-tabs idiom); each a user list
  (filter by school scope) → link to show.
- `resources/views/admin/portfolio/show.blade.php` — renders auto sections (from models) + entry sections
  (from `portfolio_entries`) with add/edit/delete per section (admin edit).
- `resources/views/teacher/portfolio.blade.php` — teacher's own view (read + edit own entries).
- Student: extend `student/portfolio.blade.php` if any of the 5 sections missing.

## Auto vs entry mapping
- **Teacher auto**: السيرة الذاتية ← User (name, qualification, specialization, hire_date, contact) + TeacherProfile;
  الإنجازات المهنية (certificates) ← Certificate(recipient_user_id); خطابات وآراء ← Evaluation/ClassVisit summaries.
- **Teacher entry**: الرؤية والرسالة, ميثاق الأخلاقيات, الأنشطة والوسائل, المشاريع, التقويم الذاتي, تحفيز الطلاب,
  نماذج وأعمال مميزة (with attachments).
- **Student auto**: السيرة الذاتية ← StudentProfile; الإنجازات ← Certificate; نتائج الطالب ← Grade(is_published)/StudentExam;
  (reuse `StudentController::portfolio` assembly).
- **Student entry**: أقسام المواد (worksheets/quizzes/homework samples), المشاريع التعليمية.

## Verify
Local: create teacher + student entries with attachment; confirm admin show renders auto + entry sections;
teacher sees own; student sees own; school scope isolates. Screenshot each tab.
