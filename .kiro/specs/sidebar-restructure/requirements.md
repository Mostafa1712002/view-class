# Requirements: Sidebar Restructure (6 new sections)

## Overview
Trello cards #1,#2,#3,#4,#5,#6 ("اضافة قسم جديد في شريط الجانبي"). Add six NEW top-level
sections to the admin sidebar that re-group the EXISTING admin pages under a new information
architecture. Assigned to me: #1, #2, #4, #5. #3 and #6 are unassigned but are pure re-org of
existing routes and belong to the same file/creator — recommended to include for a coherent nav.

Creator (handback): Mohamed Farouk. Board list flow: coding → prompt done/testing.

## Hard constraints (from card text, verbatim intent)
- **ADDITIVE ONLY.** «لا تحذف او تخفي اي صفحة او قسم موجود حاليا في شريط الجانبي. بل ضيف فقد.»
  Do NOT delete/hide any current section or item. New sections are appended; existing pages will
  therefore appear twice and `request()->routeIs()` will light both — this is expected and must be
  called out in the Arabic handback.
- Only mutation permitted (card #1): swap order of «أنا والقدرات» and «الأول بلس» so الأول بلس comes first.
- All target routes already exist (verified). Four labels have no route → shared "coming soon" page.

## New section order (top → bottom) and their items
1. **الإضافات والإسنادات** (above العمليات التعليمية): الشركات التعليمية (NEW, card #1 backend),
   المجمعات التعليمية (NEW/reuse Compound), المدرسة (`admin.schools.index`), صفوف وفصول
   (`admin.schools`-scoped classes), فريق العمل (placeholder), الطلاب (`admin.users.students.index`),
   اولياء الامور (`admin.users.parents.index`), الترحيل (placeholder), الفصول الدراسية والاسابيع (placeholder).
2. **الشؤون التعليمية والخطط**: الجدول المدرسي (`admin.school-schedule.index`), الخطة الاسبوعية
   (`manage.weekly-plans.index`), إدارة المواد [اضافة مادة `admin.subjects.index`, الحصص `admin.lessons.index`,
   جدول الاختبارات `admin.exams.index`], البرامج التعليمية الخاصة (placeholder), التعليم عن بعد (placeholder),
   الفصول الافتراضية (`manage.virtual-classes.index`), غرف النقاش (`manage.discussion-rooms.index`),
   بنوك الأسئلة (`admin.question-banks.index`), مهارات المواد (`admin.qb.skills.index`).
3. **القياس والتقويم** (unassigned #3): نماذج التقييم (`admin.evaluations…`), التقييمات (`admin.my-evaluations…`),
   اعتماد التقييم (`admin.evaluations.approvals`), إدارة الدرجات [grade-reports, grades.entry, grades],
   الاختبارات (placeholder), الزيارات الصفية (`admin.class-visits…`).
4. **الدعم والتقارير**: الكتب (`manage.books.index`), المكتبات [public/private/labs], المواقع التعليمية
   (`admin.educational-sites.index`), الاستبيانات (`admin.surveys.index`), التقارير
   [administrative/statistical/user-reports], النقاط والمكافأت (placeholder), الشهادات
   (`admin.certificates.index`), الإعلانات (`admin.announcements.index`), التقويم الدراسي
   (`manage.school-calendar.index`), الإرشاد الطلابي (placeholder).
5. **القبول والتسجيل**: القبول والتسجيل (`admissions.index`).
6. **التواصل والدعم الفني** (unassigned #6): صندوق البريد (`my.mailbox…`), رسائل الجوال [SMS section],
   التواصل مع أولياء الأمور (`admin.parents-contact…`), الدعم الفني (`admin.support…`).

## Acceptance criteria
- WHEN a super-admin/school-admin opens any page THE SIDEBAR SHALL show the six new sections in the
  order above, in addition to (not replacing) the current sections.
- WHEN a new-section link points to an existing route THE SYSTEM SHALL navigate to that same page.
- WHEN a link has no route (placeholder labels) THE SYSTEM SHALL show the shared "coming soon" page
  with the exact text «قريبًا هذا القسم قيد التطوير وسيكون متاحًا قريبًا بإذن الله.».
- WHEN rendered THE SYSTEM SHALL keep every existing section/item present and reachable.
- Permission gating on new items mirrors the existing item's gating (reuse the same `$canView*` flags).

## Out of scope (tracked in sibling specs)
- الشركات/المجمعات التعليمية CRUD, header complex selector, school-form fields → `educational-companies-complexes`.
- المواد والمسارات rename+tabs → `subjects-tracks-tabs`.
