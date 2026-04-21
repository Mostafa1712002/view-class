# ViewClass - Implementation Workflow
## Structured Development Execution Plan

---

## Workflow Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    VIEWCLASS IMPLEMENTATION WORKFLOW                        │
├─────────────────────────────────────────────────────────────────────────────┤
│  Sprint 1 ──► Sprint 2 ──► Sprint 3 ──► Sprint 4 ──► Sprint 5 ──► Sprint 6 │
│  Foundation   Education   Assessment   Operations   Analytics   Integration │
│  (2 weeks)    (3 weeks)   (3 weeks)    (2 weeks)    (2 weeks)   (2 weeks)  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Sprint 1: Foundation & Authentication (Week 1-2)

### Epic 1.1: Database Architecture
**Priority:** CRITICAL | **Dependency:** None

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 1.1.1 | Create roles migration | 2h | - |
| 1.1.2 | Create permissions migration | 2h | 1.1.1 |
| 1.1.3 | Create schools migration | 2h | - |
| 1.1.4 | Create sections migration | 2h | 1.1.3 |
| 1.1.5 | Create academic_years migration | 2h | 1.1.3 |
| 1.1.6 | Create classes migration | 3h | 1.1.4, 1.1.5 |
| 1.1.7 | Extend users table migration | 3h | 1.1.1, 1.1.3 |
| 1.1.8 | Create role_user pivot migration | 1h | 1.1.1 |
| 1.1.9 | Create permission_role pivot migration | 1h | 1.1.2 |
| 1.1.10 | Create seeders for roles/permissions | 4h | 1.1.1-1.1.9 |

#### Models to Create:
```php
// app/Models/
├── Role.php
├── Permission.php
├── School.php
├── Section.php
├── AcademicYear.php
└── ClassRoom.php  // Renamed to avoid PHP reserved word
```

#### Database Schema:
```sql
-- roles
id, name, slug, guard_name, created_at, updated_at

-- permissions
id, name, slug, guard_name, created_at, updated_at

-- schools
id, name, name_ar, code, logo, address, phone, email,
is_active, created_at, updated_at

-- sections
id, school_id, name, name_ar, type (boys/girls),
level (primary/intermediate/secondary), is_active,
created_at, updated_at

-- academic_years
id, school_id, name, start_date, end_date, is_current,
created_at, updated_at

-- classes
id, section_id, academic_year_id, name, name_ar,
grade_level, capacity, is_active, created_at, updated_at

-- users (extended)
+ school_id, section_id, employee_id, national_id,
+ phone, phone_secondary, address, gender,
+ date_of_birth, hire_date, specialization,
+ is_active, last_login_at
```

---

### Epic 1.2: Authentication System
**Priority:** CRITICAL | **Dependency:** Epic 1.1

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 1.2.1 | Install Laravel Breeze/Fortify | 1h | 1.1.10 |
| 1.2.2 | Configure multi-guard authentication | 3h | 1.2.1 |
| 1.2.3 | Create login controller with role detection | 4h | 1.2.2 |
| 1.2.4 | Create role-based middleware | 3h | 1.2.3 |
| 1.2.5 | Create permission middleware | 2h | 1.2.4 |
| 1.2.6 | Implement dashboard routing by role | 3h | 1.2.4 |
| 1.2.7 | Create password reset functionality | 2h | 1.2.1 |
| 1.2.8 | Create login views (AR/EN) | 4h | 1.2.3 |
| 1.2.9 | Create role selection page | 2h | 1.2.8 |

#### Middleware Stack:
```php
// app/Http/Middleware/
├── RoleMiddleware.php
├── PermissionMiddleware.php
├── CheckSchoolAccess.php
├── CheckSectionAccess.php
└── SetLocale.php
```

---

### Epic 1.3: Admin Dashboard Base
**Priority:** HIGH | **Dependency:** Epic 1.2

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 1.3.1 | Copy dashboard HTML template | 2h | - |
| 1.3.2 | Convert to Laravel Blade layout | 4h | 1.3.1 |
| 1.3.3 | Implement RTL support | 3h | 1.3.2 |
| 1.3.4 | Create navigation component | 3h | 1.3.2 |
| 1.3.5 | Create sidebar with role-based menu | 4h | 1.2.6 |
| 1.3.6 | Create breadcrumb component | 1h | 1.3.4 |
| 1.3.7 | Create alert/notification component | 2h | 1.3.2 |
| 1.3.8 | Create table component (reusable) | 3h | 1.3.2 |
| 1.3.9 | Create form components | 3h | 1.3.2 |
| 1.3.10 | Create modal component | 2h | 1.3.2 |

#### Blade Structure:
```
resources/views/
├── layouts/
│   ├── app.blade.php          (Main layout)
│   ├── admin.blade.php        (Admin layout)
│   ├── teacher.blade.php      (Teacher layout)
│   ├── student.blade.php      (Student layout)
│   └── parent.blade.php       (Parent layout)
├── components/
│   ├── navigation.blade.php
│   ├── sidebar.blade.php
│   ├── breadcrumb.blade.php
│   ├── alert.blade.php
│   ├── table.blade.php
│   ├── form/
│   │   ├── input.blade.php
│   │   ├── select.blade.php
│   │   └── textarea.blade.php
│   └── modal.blade.php
└── partials/
    ├── header.blade.php
    └── footer.blade.php
```

---

### Epic 1.4: Localization (AR/EN)
**Priority:** HIGH | **Dependency:** Epic 1.3

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 1.4.1 | Configure Laravel localization | 1h | - |
| 1.4.2 | Create Arabic translation files | 4h | 1.4.1 |
| 1.4.3 | Create English translation files | 2h | 1.4.1 |
| 1.4.4 | Create language switcher | 2h | 1.4.2 |
| 1.4.5 | Store language preference in session/DB | 1h | 1.4.4 |
| 1.4.6 | Test RTL layout switching | 2h | 1.4.4 |

#### Translation Structure:
```
resources/lang/
├── ar/
│   ├── auth.php
│   ├── validation.php
│   ├── pagination.php
│   ├── passwords.php
│   ├── general.php
│   ├── menu.php
│   ├── roles.php
│   └── messages.php
└── en/
    └── (same structure)
```

---

### Epic 1.5: School & User Management
**Priority:** HIGH | **Dependency:** Epic 1.3

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 1.5.1 | Create School CRUD controller | 4h | 1.3.8 |
| 1.5.2 | Create School views (index, create, edit, show) | 6h | 1.5.1 |
| 1.5.3 | Create Section CRUD controller | 3h | 1.5.1 |
| 1.5.4 | Create Section views | 4h | 1.5.3 |
| 1.5.5 | Create AcademicYear CRUD controller | 3h | 1.5.1 |
| 1.5.6 | Create AcademicYear views | 3h | 1.5.5 |
| 1.5.7 | Create Class CRUD controller | 4h | 1.5.3 |
| 1.5.8 | Create Class views | 4h | 1.5.7 |
| 1.5.9 | Create User management controller | 6h | 1.5.3 |
| 1.5.10 | Create User views with role assignment | 6h | 1.5.9 |
| 1.5.11 | Create bulk user import (Excel/CSV) | 4h | 1.5.9 |

---

## Sprint 2: Educational Management (Week 3-5)

### Epic 2.1: Subject Management
**Priority:** CRITICAL | **Dependency:** Sprint 1

#### Database:
```sql
-- subjects
id, school_id, name, name_ar, code, description, image,
is_core (boolean), grade_levels (json), is_active,
created_at, updated_at

-- subject_teacher (pivot)
id, subject_id, user_id (teacher), section_id,
academic_year_id, created_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 2.1.1 | Create subjects migration | 2h | Sprint 1 |
| 2.1.2 | Create Subject model with relationships | 2h | 2.1.1 |
| 2.1.3 | Create SubjectController | 4h | 2.1.2 |
| 2.1.4 | Create subject views | 5h | 2.1.3 |
| 2.1.5 | Implement subject image upload | 2h | 2.1.4 |
| 2.1.6 | Create core subjects seeder | 2h | 2.1.2 |
| 2.1.7 | Create teacher-subject assignment | 3h | 2.1.2 |

---

### Epic 2.2: Schedule System (الجدول المدرسي)
**Priority:** CRITICAL | **Dependency:** Epic 2.1

#### Database:
```sql
-- schedules
id, school_id, academic_year_id, section_id,
name, is_active, created_at, updated_at

-- schedule_periods
id, schedule_id, class_id, subject_id, teacher_id,
day_of_week (1-5), period_number (1-7),
start_time, end_time, room, created_at, updated_at

-- schedule_settings
id, school_id, periods_per_day, days_per_week,
max_periods_per_teacher (default: 35),
period_duration_minutes, break_durations (json)
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 2.2.1 | Create schedule migrations | 3h | 2.1.7 |
| 2.2.2 | Create Schedule/Period models | 3h | 2.2.1 |
| 2.2.3 | Create ScheduleController | 6h | 2.2.2 |
| 2.2.4 | **CRITICAL: Implement 35-period validation** | 4h | 2.2.3 |
| 2.2.5 | Create schedule builder UI | 8h | 2.2.4 |
| 2.2.6 | Create teacher schedule view | 4h | 2.2.5 |
| 2.2.7 | Create student combined schedule view | 4h | 2.2.5 |
| 2.2.8 | Create class schedule view | 3h | 2.2.5 |
| 2.2.9 | Create teacher quota report | 4h | 2.2.4 |
| 2.2.10 | Implement schedule import (Excel) | 4h | 2.2.5 |
| 2.2.11 | Create schedule PDF export | 3h | 2.2.5 |

#### Quota Validation Service:
```php
// app/Services/TeacherQuotaService.php
class TeacherQuotaService
{
    public function validateQuota(int $teacherId, int $scheduleId): bool
    {
        $currentPeriods = $this->getTeacherPeriodCount($teacherId, $scheduleId);
        $maxPeriods = $this->getMaxPeriodsAllowed($teacherId);

        return $currentPeriods < $maxPeriods; // Default: 35
    }

    public function getQuotaReport(int $scheduleId): Collection
    {
        // Returns all teachers with their period counts
    }
}
```

---

### Epic 2.3: Weekly Plan (الخطة الأسبوعية)
**Priority:** HIGH | **Dependency:** Epic 2.2

#### Database:
```sql
-- weekly_plans
id, teacher_id, class_id, subject_id, academic_year_id,
week_start_date, week_end_date, status (draft/published/locked),
published_at, published_by, created_at, updated_at

-- weekly_plan_items
id, weekly_plan_id, schedule_period_id, day_of_week,
lesson_objectives, homework, notes, created_at, updated_at

-- weekly_plan_modifications
id, weekly_plan_id, requested_by, approved_by,
old_content, new_content, status (pending/approved/rejected),
request_reason, response_note, created_at, updated_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 2.3.1 | Create weekly_plans migrations | 3h | 2.2.11 |
| 2.3.2 | Create WeeklyPlan model with relationships | 2h | 2.3.1 |
| 2.3.3 | Create WeeklyPlanController | 5h | 2.3.2 |
| 2.3.4 | Create weekly plan builder UI | 6h | 2.3.3 |
| 2.3.5 | **Implement lock mechanism on publish** | 3h | 2.3.4 |
| 2.3.6 | Create modification request workflow | 4h | 2.3.5 |
| 2.3.7 | Create Deputy approval interface | 3h | 2.3.6 |
| 2.3.8 | Implement parent/student notifications | 3h | 2.3.5 |
| 2.3.9 | Create weekly plan PDF export | 3h | 2.3.4 |
| 2.3.10 | Create student weekly view | 3h | 2.3.4 |

---

### Epic 2.4: Lesson Preparation (تحضير الدروس)
**Priority:** MEDIUM | **Dependency:** Epic 2.2

#### Database:
```sql
-- lesson_preparations
id, teacher_id, subject_id, class_id, schedule_period_id,
title, objectives, introduction, main_content,
activities, assessment, homework, resources,
teaching_aids, notes, template_id, status,
created_at, updated_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 2.4.1 | Create lesson_preparations migration | 2h | 2.2.11 |
| 2.4.2 | Create LessonPreparation model | 2h | 2.4.1 |
| 2.4.3 | Create LessonPreparationController | 4h | 2.4.2 |
| 2.4.4 | Create WYSIWYG form interface | 5h | 2.4.3 |
| 2.4.5 | Create default template | 2h | 2.4.4 |
| 2.4.6 | Create PDF/Word export | 4h | 2.4.5 |
| 2.4.7 | Create preparation history | 2h | 2.4.3 |

---

## Sprint 3: Assessments & Grades (Week 6-8)

### Epic 3.1: Question Bank (بنك الأسئلة)
**Priority:** CRITICAL | **Dependency:** Epic 2.1

#### Database:
```sql
-- question_banks
id, school_id, name, name_ar, subject_id, grade_level,
classification (standard/alawwal), visibility (private/public),
created_by, is_active, created_at, updated_at

-- questions
id, question_bank_id, question_type_id, content, content_ar,
options (json), correct_answer, explanation, difficulty,
points, time_seconds, image, audio, created_by,
created_at, updated_at

-- question_types
id, name, name_ar, slug, description
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 3.1.1 | Create question bank migrations | 3h | Epic 2.1 |
| 3.1.2 | Create QuestionBank/Question models | 3h | 3.1.1 |
| 3.1.3 | Create question type seeder | 2h | 3.1.2 |
| 3.1.4 | Create QuestionBankController | 5h | 3.1.2 |
| 3.1.5 | Create bank management views | 5h | 3.1.4 |
| 3.1.6 | Create question builder UI | 8h | 3.1.5 |
| 3.1.7 | Implement MCQ question type | 3h | 3.1.6 |
| 3.1.8 | Implement True/False type | 2h | 3.1.6 |
| 3.1.9 | Implement Fill-blank type | 3h | 3.1.6 |
| 3.1.10 | Implement Essay type | 2h | 3.1.6 |
| 3.1.11 | Implement Matching type | 4h | 3.1.6 |
| 3.1.12 | Create public bank import feature | 4h | 3.1.5 |
| 3.1.13 | Create bank sharing controls | 3h | 3.1.5 |

---

### Epic 3.2: Tests & Assignments (الاختبارات والواجبات)
**Priority:** CRITICAL | **Dependency:** Epic 3.1

#### Database:
```sql
-- tests
id, school_id, class_id, subject_id, teacher_id,
title, title_ar, description, type (test/assignment),
target_type (all/some), target_intent (support/enrichment),
total_points, passing_score, duration_minutes,
start_datetime, end_datetime, is_published,
auto_correct, show_results, allow_review,
created_at, updated_at

-- test_questions
id, test_id, question_id, order, points_override

-- test_submissions
id, test_id, student_id, started_at, submitted_at,
score, percentage, status, is_late, created_at

-- test_answers
id, test_submission_id, question_id, answer,
is_correct, points_earned, feedback, created_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 3.2.1 | Create tests migrations | 4h | 3.1.13 |
| 3.2.2 | Create Test/Submission models | 3h | 3.2.1 |
| 3.2.3 | Create TestController | 6h | 3.2.2 |
| 3.2.4 | Create test builder UI | 8h | 3.2.3 |
| 3.2.5 | Create question selector from banks | 4h | 3.2.4 |
| 3.2.6 | Implement target selection (all/some) | 3h | 3.2.4 |
| 3.2.7 | **Implement intent classification (support/enrichment)** | 2h | 3.2.6 |
| 3.2.8 | Create student test-taking interface | 8h | 3.2.4 |
| 3.2.9 | Implement auto-correction engine | 6h | 3.2.8 |
| 3.2.10 | Create results statistics | 4h | 3.2.9 |
| 3.2.11 | Create printable test PDF | 4h | 3.2.4 |

---

### Epic 3.3: Grade Management (رصد الدرجات)
**Priority:** CRITICAL | **Dependency:** Epic 3.2

#### Database:
```sql
-- grades
id, student_id, class_id, subject_id, academic_year_id,
semester, grade_type (test/assignment/participation/final),
reference_id, reference_type, score, max_score,
percentage, is_bonus, notes, recorded_by,
created_at, updated_at

-- grade_manipulation_logs
id, teacher_id, subject_id, class_id, action_type,
details (json), flagged_at, reviewed_by,
review_status, created_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 3.3.1 | Create grades migration | 3h | 3.2.11 |
| 3.3.2 | Create Grade model | 2h | 3.3.1 |
| 3.3.3 | Create GradeService | 4h | 3.3.2 |
| 3.3.4 | **Implement bonus-only for targeted tests** | 3h | 3.3.3 |
| 3.3.5 | Create grade entry interface | 5h | 3.3.3 |
| 3.3.6 | **Implement manipulation detection** | 6h | 3.3.3 |
| 3.3.7 | Create grade reports | 4h | 3.3.5 |
| 3.3.8 | Create student grade view | 3h | 3.3.5 |
| 3.3.9 | Create parent grade view | 2h | 3.3.8 |

#### Manipulation Detection Service:
```php
// app/Services/GradeManipulationService.php
class GradeManipulationService
{
    public function detectExcessiveAssignments(int $teacherId, int $classId): bool
    {
        $count = $this->getAssignmentCount($teacherId, $classId);
        $threshold = $this->getThreshold($classId);

        if ($count > $threshold) {
            $this->logSuspiciousActivity($teacherId, $classId, $count);
            return true;
        }
        return false;
    }
}
```

---

## Sprint 4: Operations (Week 9-10)

### Epic 4.1: Attendance System (الحضور والغياب)
**Priority:** HIGH | **Dependency:** Sprint 1

#### Database:
```sql
-- student_attendance
id, student_id, class_id, date, status (present/absent/late/excused),
late_minutes, reason, recorded_by, parent_notified,
created_at, updated_at

-- teacher_attendance
id, teacher_id, date, status, reason, recorded_by,
impacts_evaluation (boolean), created_at, updated_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 4.1.1 | Create attendance migrations | 2h | Sprint 1 |
| 4.1.2 | Create Attendance models | 2h | 4.1.1 |
| 4.1.3 | Create AttendanceController | 4h | 4.1.2 |
| 4.1.4 | Create daily attendance interface | 5h | 4.1.3 |
| 4.1.5 | Create attendance recorder role | 2h | 4.1.3 |
| 4.1.6 | Implement parent SMS notification | 4h | 4.1.4 |
| 4.1.7 | Create attendance reports | 4h | 4.1.4 |
| 4.1.8 | Implement teacher attendance | 3h | 4.1.2 |
| 4.1.9 | Link to performance evaluation | 2h | 4.1.8 |

---

### Epic 4.2: Teacher Evaluation (الزيارات الصفية)
**Priority:** HIGH | **Dependency:** Epic 2.2

#### Database:
```sql
-- visits
id, teacher_id, visitor_id, schedule_period_id,
visit_type (support/evaluation), visit_date,
grade, notes, criteria_scores (json),
created_at, updated_at

-- visit_criteria
id, school_id, name, name_ar, max_points,
category, order, is_active
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 4.2.1 | Create visits migrations | 2h | Epic 2.2 |
| 4.2.2 | Create Visit model | 2h | 4.2.1 |
| 4.2.3 | Create VisitController | 4h | 4.2.2 |
| 4.2.4 | Create visit scheduling interface | 4h | 4.2.3 |
| 4.2.5 | **Implement specialist subject restriction** | 3h | 4.2.3 |
| 4.2.6 | Create evaluation form UI | 4h | 4.2.4 |
| 4.2.7 | Implement support vs evaluation logic | 2h | 4.2.6 |
| 4.2.8 | Create visit reports | 3h | 4.2.6 |
| 4.2.9 | Link grades to teacher points | 2h | 4.2.7 |

---

### Epic 4.3: Certificates (الشهادات)
**Priority:** MEDIUM | **Dependency:** Sprint 1

#### Database:
```sql
-- certificate_templates
id, school_id, name, type (achievement/appreciation),
background_image, watermark, layout (json),
is_active, created_at, updated_at

-- certificates
id, template_id, recipient_id, recipient_type,
title, content, issued_by, issued_date,
qr_code, pdf_path, shared_at, created_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 4.3.1 | Create certificates migrations | 2h | Sprint 1 |
| 4.3.2 | Create Certificate models | 2h | 4.3.1 |
| 4.3.3 | Create template designer | 6h | 4.3.2 |
| 4.3.4 | **Implement watermark feature** | 3h | 4.3.3 |
| 4.3.5 | Create certificate generator | 4h | 4.3.4 |
| 4.3.6 | Create PDF export with branding | 4h | 4.3.5 |
| 4.3.7 | Implement WhatsApp/Email sharing | 3h | 4.3.6 |

---

## Sprint 5: Analytics & Reports (Week 11-12)

### Epic 5.1: Advanced Reports
**Priority:** HIGH | **Dependency:** Sprints 1-4

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 5.1.1 | Create ReportService | 4h | Sprint 4 |
| 5.1.2 | Create teacher comparison report | 5h | 5.1.1 |
| 5.1.3 | Create cross-subject comparison | 4h | 5.1.1 |
| 5.1.4 | Create weak students report | 4h | 5.1.1 |
| 5.1.5 | Create progress tracking report | 4h | 5.1.1 |
| 5.1.6 | Create section-wise reports | 3h | 5.1.1 |
| 5.1.7 | Create dynamic report builder | 8h | 5.1.1 |
| 5.1.8 | Create Excel/PDF exports | 4h | 5.1.7 |

---

### Epic 5.2: Performance Review (تقييم الأداء)
**Priority:** HIGH | **Dependency:** Epic 4.2

#### Database:
```sql
-- performance_reviews
id, teacher_id, academic_year_id, semester,
attendance_score, visit_score, outcome_test_score,
other_scores (json), total_score, percentage,
reviewed_by, notes, created_at, updated_at
```

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 5.2.1 | Create performance_reviews migration | 2h | Epic 4.2 |
| 5.2.2 | Create PerformanceReview model | 2h | 5.2.1 |
| 5.2.3 | **Implement 30% outcome test weight** | 3h | 5.2.2 |
| 5.2.4 | Create review calculation service | 4h | 5.2.3 |
| 5.2.5 | Create review dashboard | 4h | 5.2.4 |
| 5.2.6 | Create review reports | 3h | 5.2.5 |

---

### Epic 5.3: Announcements
**Priority:** MEDIUM | **Dependency:** Sprint 1

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 5.3.1 | Create announcements migration | 2h | Sprint 1 |
| 5.3.2 | Create Announcement model | 1h | 5.3.1 |
| 5.3.3 | Create AnnouncementController | 3h | 5.3.2 |
| 5.3.4 | Create targeting system | 4h | 5.3.3 |
| 5.3.5 | Create announcement views | 3h | 5.3.4 |
| 5.3.6 | Implement notifications | 3h | 5.3.5 |

---

## Sprint 6: Integration & Testing (Week 13-14)

### Epic 6.1: Virtual Classroom (Zoom)
**Priority:** HIGH | **Dependency:** Epic 2.2

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 6.1.1 | Install Zoom SDK/API package | 2h | Epic 2.2 |
| 6.1.2 | Create ZoomService | 4h | 6.1.1 |
| 6.1.3 | Create meeting scheduling | 4h | 6.1.2 |
| 6.1.4 | **Implement student entry restriction** | 3h | 6.1.3 |
| 6.1.5 | Create virtual class interface | 5h | 6.1.3 |
| 6.1.6 | Implement notifications | 2h | 6.1.5 |
| 6.1.7 | Create recording management | 3h | 6.1.5 |

---

### Epic 6.2: Notifications System
**Priority:** HIGH | **Dependency:** Sprint 1

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 6.2.1 | Configure mail settings | 2h | Sprint 1 |
| 6.2.2 | Integrate SMS gateway | 4h | 6.2.1 |
| 6.2.3 | Create notification templates | 3h | 6.2.2 |
| 6.2.4 | Create NotificationService | 4h | 6.2.3 |
| 6.2.5 | Implement queue for notifications | 2h | 6.2.4 |
| 6.2.6 | Create notification preferences | 3h | 6.2.4 |

---

### Epic 6.3: Transfer/Promotion (الترحيل)
**Priority:** CRITICAL | **Dependency:** All Epics

#### Tasks:
| ID | Task | Est. Hours | Dependencies |
|----|------|------------|--------------|
| 6.3.1 | Create TransferService | 6h | All Sprints |
| 6.3.2 | **Implement data reset logic** | 4h | 6.3.1 |
| 6.3.3 | **Implement first-grade exception** | 3h | 6.3.2 |
| 6.3.4 | Create promotion interface | 4h | 6.3.3 |
| 6.3.5 | Create graduate archiving | 3h | 6.3.4 |
| 6.3.6 | Create transfer reports | 3h | 6.3.4 |

---

## Testing Strategy

### Unit Tests
```
tests/Unit/
├── Services/
│   ├── TeacherQuotaServiceTest.php
│   ├── GradeManipulationServiceTest.php
│   ├── WeeklyPlanLockServiceTest.php
│   └── TransferServiceTest.php
├── Models/
└── Rules/
```

### Feature Tests
```
tests/Feature/
├── Auth/
├── Admin/
├── Teacher/
├── Student/
└── API/
```

### Critical Test Cases:
1. Teacher quota cannot exceed 35 periods
2. Weekly plan locks after export
3. Only Deputy can approve modifications
4. Targeted assessments calculated as bonus only
5. Specialist can only visit their subject teachers
6. Educational outcome test = 30% weight
7. First grade data completely resets

---

## Deployment Checklist

- [ ] Environment configuration
- [ ] Database migrations
- [ ] Seed default data (roles, permissions, subjects)
- [ ] Configure mail/SMS services
- [ ] Configure Zoom integration
- [ ] Set up queue workers
- [ ] Configure cron jobs
- [ ] SSL certificate
- [ ] Backup strategy
- [ ] Monitoring setup

---

*Generated: November 30, 2025*
*Strategy: Systematic*
*Total Estimated Hours: ~400 hours*
