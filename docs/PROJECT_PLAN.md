# ViewClass - Smart Educational Ecosystem
## Full Project Plan & Technical Specification

---

## Project Overview

**Project Name:** ViewClass (فيو كلاس)
**Type:** Smart Educational Management System
**Framework:** Laravel 12.40.2
**PHP Version:** 8.3.28
**Database:** MySQL
**Status:** Development Phase

---

## What Has Been Done

| Task | Status | Details |
|------|--------|---------|
| Laravel Installation | Done | v12.40.2 (Latest) |
| PHP Upgrade | Done | 7.2 -> 8.3.28 |
| MySQL Configuration | Done | Database: viewclass, User: root |
| Initial Migrations | Done | users, cache, jobs tables |
| Project Structure | Done | Standard Laravel structure |

---

## System Architecture

### User Roles & Hierarchy

```
System Administrator (مدير النظام)
├── School Manager (مدير المدرسه)
│   ├── Deputy/Agent (الوكيل)
│   │   ├── Resident Supervisor (مشرف تعليمي مقيم)
│   │   ├── Specialist Supervisor (مشرف تخصص)
│   │   └── Attendance Recorder (مسجل غياب)
│   └── Teachers (المعلمين)
├── Students (الطلاب)
└── Parents (أولياء الأمور)
```

### Section Accounts
- Primary Boys Section (قسم ابتدائي بنين)
- Primary Girls Section (قسم ابتدائي بنات)
- Intermediate Boys Section (قسم متوسط بنين)
- Intermediate Girls Section (قسم متوسط بنات)
- Aggregate Account (المجمع كله) - Full access to all sections

---

## Phase 1: Foundation & User Management

### 1.1 Interface & Navigation
- [ ] Hero Section with main branding
- [ ] Main Menu Implementation
- [ ] About Us page (من نحن؟)
- [ ] Educational System page (النظام التعليمي)
- [ ] Contact Us page (اتصل بنا)
- [ ] Login Portal (تسجيل دخول)
- [ ] E-Payment page (السداد الإلكتروني)

### 1.2 Language Support
- [ ] Arabic (العربية) - RTL support
- [ ] English - LTR support
- [ ] Language switcher component
- [ ] Translation files structure

### 1.3 Authentication System
- [ ] Multi-role login system
- [ ] System Manager login (مدير النظام)
- [ ] Teacher login (المعلم)
- [ ] Student login (الطالب)
- [ ] Parent login (ولي الأمر)
- [ ] Role-based dashboard routing
- [ ] Password reset functionality

### 1.4 System Administration
- [ ] School management (CRUD)
- [ ] Academic year management
- [ ] Class management (الفصول)
- [ ] User management with permissions
- [ ] Section-based accounts (Boys/Girls, Primary/Intermediate)

### 1.5 Admissions & Registration (القبول والتسجيل)
- [ ] External application form
- [ ] Application tracking for principals
- [ ] Application status management
- [ ] Student enrollment workflow

### 1.6 Technical Support System (دعم فني متخصص)
- [ ] Support ticket system
- [ ] Ticket categories
- [ ] Assignment to support staff
- [ ] Status tracking

---

## Phase 2: Educational Management & Scheduling

### 2.1 Subject Management (إدارة المواد الدراسية)
- [ ] Default/Core subjects from platform (سورس أساسي)
- [ ] Custom subject addition by school
- [ ] Subject image upload capability
- [ ] Subject-to-grade level mapping
- [ ] Subject categorization (Arabic, English, Math, Science, etc.)

### 2.2 Digital Libraries (المكتبات الرقمية)
- [ ] Visual materials library
- [ ] Books library (linked to subjects)
- [ ] Content classification system
- [ ] Three library types:
  - Private (خاصة بالمدرسة)
  - Public/General (عامة) - shared with other schools
  - Al-Awwal Bank (بنك الأول) - premium content

### 2.3 Dynamic School Schedule (الجدول المدرسي)

#### Core Features
- [ ] Teacher-to-class period linking
- [ ] Data import functionality
- [ ] Schedule generation

#### Teacher Quota System (نصاب المعلمين)
- [ ] **CRITICAL CONSTRAINT**: Maximum 35 periods/week per teacher
- [ ] Quota calculation and reporting
- [ ] Teacher quota comparison report
- [ ] Subject-wise period distribution
- [ ] Validation to reject schedules exceeding 35 periods

#### Schedule Views
- [ ] Teacher's personal schedule view
- [ ] Student's combined schedule (all teachers merged)
- [ ] Class schedule view
- [ ] Section-wise schedule overview

### 2.4 Weekly Plan (الخطة الأسبوعية)

#### Teacher Features
- [ ] Lesson objectives input per session
- [ ] Homework (الواجب) assignment under each period
- [ ] Plan export/share functionality (typically Thursday)

#### Workflow Controls
- [ ] **LOCK MECHANISM**: Once exported, teacher cannot modify
- [ ] Modification requests routed to Deputy/Agent (الوكيل)
- [ ] Approval workflow for changes
- [ ] Parent/Student notification on export

### 2.5 Lesson Preparation (تحضير الدروس)
- [ ] Digital preparation form/template
- [ ] Word/PDF-like output format
- [ ] Save and edit capabilities
- [ ] Template customization
- [ ] Default template with school branding

---

## Phase 3: Assessments & Virtual Learning

### 3.1 Question Bank System (بنك الأسئلة)

#### Question Types
- [ ] Multiple choice
- [ ] True/False
- [ ] Fill in the blank
- [ ] Essay/Open-ended
- [ ] Matching

#### Classification System
- [ ] Standard Bank (created by school teachers)
- [ ] Al-Awwal Bank (الأول) - Premium/specialized
- [ ] Classification tags for organization

#### Sharing Controls
- [ ] Private (خاصة بشركتك) - school only
- [ ] Public (عامة) - shared with other schools
- [ ] Import from public banks capability

### 3.2 Assignments & Tests (الواجبات والاختبارات)
- [ ] Assignment creation
- [ ] Test creation with multiple question types
- [ ] Automatic correction system
- [ ] Publication/submission time controls
- [ ] Statistical analysis of results
- [ ] Print-ready test generation

### 3.3 Grade Management (رصد وإدارة الدرجات)

#### Core Features
- [ ] Dynamic grading based on subject and class
- [ ] Grade entry interface
- [ ] Grade reports generation

#### Anti-Manipulation Logic
- [ ] **CONSTRAINT**: Prevent excessive assignment creation to inflate scores
- [ ] Assignment count monitoring
- [ ] Alert system for suspicious patterns

#### Targeted Assessment Logic
- [ ] "For All" (للكل) - Standard percentage calculation
- [ ] "For Some" (للبعض) - **Calculated as Bonus Points only**
- [ ] Intent Classification required:
  - Support Plan (خطة دعم) - Remedial
  - Enrichment Plan (خطة إثرائية) - Advanced

### 3.4 Virtual Classroom (الفصول الافتراضية)
- [ ] Zoom Premium integration (single account)
- [ ] Schedule-linked virtual sessions
- [ ] Session scheduling interface
- [ ] **SECURITY**: Students cannot enter until teacher starts session
- [ ] Reminder notifications before sessions
- [ ] Recording capabilities (optional)

### 3.5 Discussion Rooms (غرف النقاش)
- [ ] Student-teacher communication platform
- [ ] Topic-based discussions
- [ ] File sharing in discussions
- [ ] Moderation controls

---

## Phase 4: Staff & Student Lifecycle

### 4.1 Attendance Management (إدارة الحضور والغياب)

#### Student Attendance
- [ ] Dedicated Attendance Recorder role (مسجل غياب)
- [ ] Daily attendance tracking
- [ ] Tardiness tracking (التأخير)
- [ ] SMS/Email notifications to parents
- [ ] Attendance reports

#### Teacher Attendance
- [ ] Teacher absence tracking
- [ ] **Impact on performance evaluation points**
- [ ] Absence reports

### 4.2 Teacher Evaluation - Class Visits (الزيارات الصفية)

#### Visitor Types
- [ ] Agent/Director visits
- [ ] Resident Supervisor visits
- [ ] **Specialist Supervisor** - Can ONLY visit teachers in their specialization

#### Visit Types
- [ ] Support Visit (دعم) - No grade assigned
- [ ] Evaluation Visit (تقييم) - Grade affects teacher points

#### Features
- [ ] Schedule-linked visits
- [ ] Visit form/criteria (dynamic)
- [ ] Visit reports
- [ ] Notification system

### 4.3 Performance Grading

#### Educational Outcome Test (اختبار ناتج التعليم)
- [ ] Test creation by administration
- [ ] **WEIGHT**: Contributes exactly 30% to Functional Performance Review
- [ ] Automatic calculation integration

#### Performance Review (تقييم الأداء الوظيفي)
- [ ] Multiple criteria evaluation
- [ ] Attendance impact
- [ ] Visit grades impact
- [ ] Educational outcome test (30%)
- [ ] Final score calculation

### 4.4 Transfer/Promotion Service (ترحيل)

#### Student Promotion
- [ ] End-of-year promotion workflow
- [ ] **DATA RESET**: Reset all academic statistics for new year
- [ ] **EXCEPTION**: First Primary Grade students - complete reset
- [ ] Grade advancement logic

#### Graduate Archiving
- [ ] Archive graduating secondary students (الخريجين)
- [ ] Historical data preservation
- [ ] Certificate generation for graduates

#### Teacher Transfer
- [ ] Teacher reassignment
- [ ] Statistics preservation

### 4.5 Electronic Certificates (الشهادات الإلكترونية)
- [ ] Achievement certificates
- [ ] Appreciation certificates
- [ ] **BRANDING**: School logo as watermark
- [ ] Customizable design templates (قوالب تصميم)
- [ ] Sharing options (WhatsApp/Email)
- [ ] Manager/Deputy signatures
- [ ] Student certificates
- [ ] Teacher certificates

---

## Phase 5: Reports, Analytics & Tools

### 5.1 Advanced Reporting (التقارير)

#### Teacher Performance Reports
- [ ] Compare teachers within same phase/department
- [ ] Cross-specialization comparison (Science vs Physics vs Chemistry)
- [ ] Teacher ranking by student results

#### Student Reports
- [ ] Weak students identification (الطلاب الضعاف)
- [ ] Progress tracking (معدل تقدم)
- [ ] Improvement reports

#### Administrative Reports
- [ ] Section-wise reports
- [ ] Aggregate reports
- [ ] Dynamic report builder

### 5.2 Evaluation Models (نماذج التقييم)
- [ ] Dynamic evaluation forms
- [ ] Role-specific evaluations
- [ ] Customizable criteria
- [ ] Point-based system

### 5.3 Behavior Management (إدارة السلوك)
- [ ] Positive behavior tracking
- [ ] Negative behavior tracking
- [ ] Parent notifications
- [ ] Behavior reports

### 5.4 Incentive Points System (نقاط تحفيز)
- [ ] Student incentive points
- [ ] Teacher incentive points
- [ ] Point redemption system
- [ ] Leaderboards

### 5.5 Targeted Announcements (الإعلانات)
- [ ] Section-specific announcements
- [ ] All-school announcements
- [ ] Role-based targeting
- [ ] Notification delivery

---

## Phase 6: Deferred Features (Future Development)

| Feature | Status | Notes |
|---------|--------|-------|
| Virtual Laboratories (المعامل الافتراضية) | Deferred | High complexity, requires extensive data |
| E-Canteen (المقصف الإلكتروني) | Deferred | Future enhancement |
| School Clinic (العيادة المدرسية) | Deferred | Student medical records |
| Student Counseling (الإرشاد الطلابي) | Deferred | Complex, tied to behavior management |

---

## Database Schema Overview

### Core Tables (To Be Created)

```
schools
academic_years
sections
classes
subjects
users (extended)
roles
permissions

schedules
schedule_periods
teacher_quotas

weekly_plans
lesson_preparations
homework

question_banks
questions
question_types
tests
assignments
grades

virtual_classes
discussion_rooms
messages

attendance_students
attendance_teachers

visits
visit_types
evaluations

certificates
certificate_templates

announcements
notifications

behavior_records
incentive_points
```

---

## Technical Requirements

### Frontend
- Blade templates with RTL support
- Responsive design (mobile-first)
- Dashboard from `/home/mostafa/www/ecommerce-dashboard/html`
- JavaScript for dynamic interactions
- AJAX for real-time updates

### Backend
- Laravel 12 with latest best practices
- RESTful API design
- Role-based access control (RBAC)
- Event-driven architecture for notifications
- Queue system for heavy operations

### Integrations
- Zoom API for virtual classrooms
- SMS gateway for notifications
- Email service (SMTP)
- WhatsApp Business API (optional)

### Security
- Multi-factor authentication (optional)
- Session management
- CSRF protection
- Input validation
- SQL injection prevention
- XSS protection

---

## Development Priority Order

### Sprint 1: Foundation
1. User authentication & roles
2. School/Academic year setup
3. Basic dashboard

### Sprint 2: Core Education
1. Subject management
2. Schedule system with quota validation
3. Weekly plans

### Sprint 3: Assessments
1. Question bank
2. Tests & assignments
3. Grade management

### Sprint 4: Operations
1. Attendance system
2. Teacher evaluation
3. Certificates

### Sprint 5: Analytics
1. Reports system
2. Performance tracking
3. Announcements

### Sprint 6: Integration
1. Virtual classroom (Zoom)
2. Notifications (SMS/Email)
3. Final testing

---

## File Structure (Recommended)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Teacher/
│   │   ├── Student/
│   │   └── Parent/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Services/
├── Repositories/
└── Events/

resources/
├── views/
│   ├── admin/
│   ├── teacher/
│   ├── student/
│   ├── parent/
│   ├── components/
│   └── layouts/
├── lang/
│   ├── ar/
│   └── en/
└── js/

database/
├── migrations/
├── seeders/
└── factories/
```

---

## Notes from Client Meeting

1. **Teacher Quota**: Must not exceed 35 periods/week - CRITICAL
2. **Weekly Plan Lock**: Once exported, only Deputy can modify
3. **Targeted Assessments**: Must be classified as Support or Enrichment
4. **Grade Manipulation Prevention**: System must detect excessive assignments
5. **Specialist Supervisors**: Can only visit their specialization teachers
6. **Educational Outcome Test**: Exactly 30% of performance review
7. **First Grade Exception**: Complete data reset on promotion
8. **Certificate Watermark**: School logo across entire background

---

## Contact & Support

**Project Lead:** [To be defined]
**Technical Lead:** [To be defined]
**Client Contact:** [To be defined]

---

*Document Created: November 30, 2025*
*Last Updated: November 30, 2025*
*Version: 1.0*
