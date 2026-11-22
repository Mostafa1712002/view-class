# Requirements: Subject → grade filtering + online-exam school scope

Trello: WONqSJOX «مشكل في صفحة إنشاء اختبار جديد», avTqQ1zT «علاقات المواد الدراسية والصف الدراسي والفصول والطلاب» (QA Mohamed Farouk).

- WHEN an admin opens /admin/online-exams/create THE SYSTEM SHALL list only the subjects, classes and academic years of the school selected in the header (super-admin with no school = all schools).
- WHEN the form is submitted THE SYSTEM SHALL reject subject/class/year ids of another school (422).
- WHEN a subject is chosen on a page that pairs المادة with a grade (الصف / الصف الدراسي) THE SYSTEM SHALL list only grades linked to that subject (subjects.grade_levels).
- IF the subject has no linked grades, or no grade option would remain, THEN THE SYSTEM SHALL show every option (legacy data).
- WHEN a saved/filtered value is outside the subject's grades on page load THE SYSTEM SHALL keep it selected.
- A student SHALL see only subjects (and their books/content) whose grades include the student's class grade (already true — verified).
