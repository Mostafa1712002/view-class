# Design: Sprint 3 — Users Module

## Architecture Overview
```
+-------------------------------------------------------------+
| Sidebar mega-menu: Users                                    |
|   ├─ Students  → /admin/users/students                      |
|   ├─ Parents   → /admin/users/parents                       |
|   ├─ Teachers  → /admin/users/teachers                      |
|   ├─ Admins    → /admin/users/admins                        |
|   ├─ Cards     → /admin/users/cards                         |
|   └─ Search    → /admin/users/search (cross-school)         |
+-------------------------------------------------------------+
        ↓
+-------------------------------------------------------------+
| Controllers (Admin/Users namespace)                         |
|   StudentController · ParentController · TeacherController  |
|   AdminController   · UserCardController · ImpersonateCtrl  |
|   JobTitleController                                        |
+-------------------------------------------------------------+
        ↓
+-------------------------------------------------------------+
| Repositories (App\Repositories\Users)                       |
|   StudentRepository · ParentRepository · TeacherRepository  |
|   AdminRepository — all share BaseUserRepository            |
+-------------------------------------------------------------+
        ↓
+-------------------------------------------------------------+
| Models — single users table, role-filtered                  |
|   User (existing) + JobTitle (new) + UserSettingsCast       |
+-------------------------------------------------------------+
```

## Database Schema

### New: job_titles
```sql
CREATE TABLE job_titles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    school_id BIGINT UNSIGNED NULL,
    slug VARCHAR(64) NOT NULL,
    name_ar VARCHAR(120) NOT NULL,
    name_en VARCHAR(120) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order SMALLINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_slug_school (school_id, slug)
);
```
NULL `school_id` means a global system title (seeded defaults). Per-school custom titles use the school's id.

### New: users.job_title_id (nullable FK)
```sql
ALTER TABLE users ADD COLUMN job_title_id BIGINT UNSIGNED NULL AFTER specialization;
ALTER TABLE users ADD CONSTRAINT users_job_title_fk FOREIGN KEY (job_title_id) REFERENCES job_titles(id) ON DELETE SET NULL;
```

### New: users.plain_password_for_card (nullable, encrypted)
```sql
ALTER TABLE users ADD COLUMN plain_password_for_card TEXT NULL AFTER password;
```
Filled at creation (encrypted via Laravel `encrypted` cast); nulled when user changes their own password. Used only for User Cards PDF.

### Existing pivots reused
- `class_student` (student → classes)
- `parent_student` (parent → student)
- `subject_teacher` (teacher → subjects)
- `role_user` (user → roles)
- `school_role_permissions` (Sprint 2)

### New: admin_supervisees (polymorphic, optional)
```sql
CREATE TABLE admin_supervisees (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    supervisee_type VARCHAR(32) NOT NULL, -- 'student' | 'teacher'
    supervisee_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    UNIQUE KEY uniq (admin_id, supervisee_type, supervisee_id)
);
```
Supervisor → list of teachers. Counselor → list of students.

## API / Web Endpoints

| Method | Endpoint                                            | Description                             |
|--------|-----------------------------------------------------|-----------------------------------------|
| GET    | /admin/users/students                               | List + search                           |
| GET    | /admin/users/students/create                        | Form                                    |
| POST   | /admin/users/students                               | Create                                  |
| GET    | /admin/users/students/{id}/edit                     | Edit form                               |
| PUT    | /admin/users/students/{id}                          | Update                                  |
| DELETE | /admin/users/students/{id}                          | Soft delete                             |
| GET    | /admin/users/students/{id}/parents                  | Linked parents                          |
| GET    | /admin/users/students/{id}/schedule                 | Schedule view                           |
| POST   | /admin/users/students/bulk                          | Bulk operation (license/hide/etc)       |
| POST   | /admin/users/students/import                        | Excel import                            |
|        |                                                     |                                         |
| GET    | /admin/users/parents                                | List                                    |
| POST   | /admin/users/parents                                | Create                                  |
| GET    | /admin/users/parents/{id}/students                  | Linked-students manager                 |
| POST   | /admin/users/parents/{id}/students/sync             | Sync linked students                    |
|        |                                                     |                                         |
| GET    | /admin/users/teachers                               | List                                    |
| POST   | /admin/users/teachers                               | Create                                  |
| GET    | /admin/users/teachers/workloads                     | Workload report                         |
|        |                                                     |                                         |
| GET    | /admin/users/admins                                 | List                                    |
| POST   | /admin/users/admins                                 | Create (job_title_id required)          |
| GET    | /admin/users/admins/{id}/supervisees                | Manage assignments                      |
| POST   | /admin/users/admins/{id}/supervisees                | Sync                                    |
| GET    | /admin/users/job-titles                             | CRUD index                              |
| POST   | /admin/users/job-titles                             | Create                                  |
| PUT    | /admin/users/job-titles/{id}                        | Update                                  |
| DELETE | /admin/users/job-titles/{id}                        | Delete                                  |
|        |                                                     |                                         |
| GET    | /admin/users/cards                                  | UI                                      |
| POST   | /admin/users/cards/generate                         | Stream PDF                              |
|        |                                                     |                                         |
| POST   | /admin/users/{id}/impersonate                       | Login as                                |
| POST   | /admin/users/impersonate/stop                       | Stop                                    |

## Folder Structure
```
app/Http/Controllers/Admin/Users/
├── StudentController.php
├── ParentController.php
├── TeacherController.php
├── AdminController.php
├── JobTitleController.php
├── UserCardController.php
└── ImpersonateController.php

app/Repositories/Users/
├── BaseUserRepository.php
├── StudentRepository.php
├── ParentRepository.php
├── TeacherRepository.php
└── AdminRepository.php

app/Models/JobTitle.php

resources/views/admin/users/
├── _shared/_form.blade.php   # role-aware
├── students/{index,create,edit,parents,schedule}.blade.php
├── parents/{index,create,edit,students}.blade.php
├── teachers/{index,create,edit,workloads}.blade.php
├── admins/{index,create,edit,supervisees}.blade.php
├── job_titles/index.blade.php
└── cards/index.blade.php

resources/views/pdfs/user-cards.blade.php

lang/{ar,en}/users.php
```

## Sequence: Login-as
```
admin → click "Login as" on row(123)
        ↓ POST /admin/users/123/impersonate
ImpersonateController::start(123)
  ├─ Gate: hasRole('super-admin')
  ├─ Activity::log('impersonate', target=123)
  ├─ session()->put('impersonator_id', auth()->id())
  ├─ Auth::loginUsingId(123)
  └─ redirect '/dashboard'
        ↓ banner shown via middleware
        ↓ click "Stop"
        ↓ POST /admin/users/impersonate/stop
ImpersonateController::stop()
  ├─ Auth::loginUsingId(session()->pull('impersonator_id'))
  └─ redirect back to admin/users/students
```

## Technology Stack
- **Backend**: Laravel 12 / PHP 8.4
- **Frontend**: Bootstrap 4, Blade, vanilla JS (no Vue/React)
- **PDF**: `barryvdh/laravel-dompdf` if not already pulled (fallback: `mpdf`)
- **Excel**: `maatwebsite/excel` (or basic CSV reader if not available)
- **DB**: MySQL (single `users` table, role-filtered)
