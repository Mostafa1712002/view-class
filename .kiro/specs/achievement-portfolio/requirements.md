# Requirements: إدارة ملف أنجاز (Achievement Portfolio)

## Overview
Card "أنشاء صفحة جديدة اسمها إدارة ملف أنجاز" (creator: Mohamed Farouk). New page under العمليات
التعليمية → tabbed into ملف أنجاز معلم (teacher) and ملف أنجاز طالب (student). Placed in the sidebar
under المكتبات. The portfolio also shows in the teacher's and student's own account.

## Content sections (from card)
**Teacher (10):** السيرة الذاتية، الرؤية والرسالة، ميثاق أخلاقيات المهنة، الإنجازات المهنية (دورات/شهادات/جوائز)،
الأنشطة والوسائل التعليمية، المشاريع التعليمية، التقويم الذاتي، تحفيز الطلاب، نماذج وأعمال مميزة، خطابات وآراء الآخرين.
**Student (5):** السيرة الذاتية، الإنجازات (شهادات/تكريمات)، أقسام المواد (أوراق عمل/اختبارات قصيرة/واجبات)،
المشاريع التعليمية، نتائج الطالب (اشعارات الدرجات).

## Grounding (partial reuse)
- **Student portfolio already exists**: `StudentController::portfolio` (`app/Http/Controllers/StudentController.php:426`),
  view `resources/views/student/portfolio.blade.php`, sidebar `student.portfolio` (:1205). Pulls certificates,
  published grades, exam results, attendance. Reuse this for auto-populated student sections.
- Teacher data sources: `users.qualification/specialization/hire_date`, `TeacherProfile`, `Certificate`
  (`recipient_user_id`), `ClassVisit`/`Evaluation*`. No teacher portfolio page exists.
- Libraries sidebar section: `sidebar.blade.php:606-615` (placement target).

## Acceptance criteria
- WHEN admin opens إدارة ملف أنجاز THE SYSTEM SHALL show two tabs (teacher / student), each listing users
  with a view into their portfolio.
- WHEN a teacher/student views their own account THE SYSTEM SHALL show their portfolio per the sections above.
- Auto-derivable sections (student results/certificates/attendance; teacher CV from user+profile+certificates)
  pull from existing data. Narrative/upload sections (vision, ethics charter, self-eval, projects, samples,
  letters) are editable entries with optional file attachment.

## Decision (per advisor — avoid 15 bespoke forms)
Single table **`portfolio_entries`** (`user_id`, `section` enum/string const, `title`, `body` text nullable,
`attachment_path` nullable, `entry_date` nullable, timestamps, softDeletes; school scope via user). Section
constants: 10 teacher + 5 student keys. One generic add/edit-entry flow keyed by section. Auto sections render
from existing models (not stored as entries). Largest greenfield card → build LAST.
