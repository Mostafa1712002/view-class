# Design: Sprint 4 — Educational Operations

## Architecture Overview

```
┌────────────────────────────────────────────────────────────────────┐
│  Sprint 4 modules (all under app/Modules/)                         │
│                                                                    │
│  Subjects/         QuestionBanks/      ClassPeriods/   SchoolSchedule/
│  ├ Controllers     ├ Controllers       ├ Controllers   ├ Controllers
│  ├ Actions         ├ Actions           ├ Actions       (read-only,
│  ├ Repositories    ├ Repositories      ├ Repositories   no Actions)
│  ├ Models          ├ Models            ├ Models        ├ Repositories
│  └ Routes          └ Routes            └ Routes        └ Routes
│                                                                    │
│         ↓                ↓                  ↓               ↓      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  shared: User, School, Grade, Section (legacy models)       │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                              ↓                                     │
│                       MySQL (viewclass schema)                     │
└────────────────────────────────────────────────────────────────────┘
```

## Database Schema

```sql
-- 1. SUBJECTS
CREATE TABLE subjects (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    school_id BIGINT UNSIGNED NULL,           -- NULL = ViewClass-platform template
    grade_id BIGINT UNSIGNED NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    section VARCHAR(120) NULL,                -- e.g. "علمي", "أدبي"
    credit_hours TINYINT UNSIGNED NULL,
    certificate_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    source ENUM('manual','excel','viewclass') NOT NULL DEFAULT 'manual',
    template_subject_id BIGINT UNSIGNED NULL, -- if user converted manual → ViewClass
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX (school_id, grade_id),
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (template_subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);

-- 2. SUBJECT LESSON TREE
CREATE TABLE subject_units (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    subject_id BIGINT UNSIGNED NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    sort_order SMALLINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE subject_lessons (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    unit_id BIGINT UNSIGNED NOT NULL,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    sort_order SMALLINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (unit_id) REFERENCES subject_units(id) ON DELETE CASCADE
);

-- 3. QUESTION BANKS
CREATE TABLE question_banks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    school_id BIGINT UNSIGNED NULL,
    name_ar VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NULL,
    is_library TINYINT(1) NOT NULL DEFAULT 0, -- platform-provided template
    created_by BIGINT UNSIGNED NULL,          -- author teacher
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE question_bank_subjects (
    question_bank_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (question_bank_id, subject_id),
    FOREIGN KEY (question_bank_id) REFERENCES question_banks(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE question_bank_users (
    question_bank_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('viewer','editor') NOT NULL DEFAULT 'viewer',
    PRIMARY KEY (question_bank_id, user_id),
    FOREIGN KEY (question_bank_id) REFERENCES question_banks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE questions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    question_bank_id BIGINT UNSIGNED NOT NULL,
    type ENUM('mcq','true_false','short','essay') NOT NULL DEFAULT 'mcq',
    body_ar TEXT NOT NULL,
    body_en TEXT NULL,
    answer_data JSON NULL,                     -- options + correct, depends on type
    difficulty TINYINT UNSIGNED NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (question_bank_id) REFERENCES question_banks(id) ON DELETE CASCADE,
    INDEX (question_bank_id, type)
);

-- 4. CLASS PERIODS (the binding of teacher × class × subject)
CREATE TABLE class_periods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    school_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    substitute_teacher_id BIGINT UNSIGNED NULL,
    grade_id BIGINT UNSIGNED NOT NULL,
    class_room_id BIGINT UNSIGNED NOT NULL,    -- existing classes table from Sprint 2
    subject_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
    FOREIGN KEY (class_room_id) REFERENCES class_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY (school_id, teacher_id, grade_id, class_room_id, subject_id)
);

-- 5. TIME SLOTS (grid of period × day-of-week × start/end)
CREATE TABLE time_slots (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    school_id BIGINT UNSIGNED NOT NULL,
    period_no TINYINT UNSIGNED NOT NULL,        -- 1, 2, 3, …
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    is_break TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
    UNIQUE KEY (school_id, period_no),
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

-- 6. SCHEDULE ENTRIES (placement of a class_period at a specific time_slot × day)
CREATE TABLE schedule_entries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    school_id BIGINT UNSIGNED NOT NULL,
    class_period_id BIGINT UNSIGNED NOT NULL,
    time_slot_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,     -- 0=Sun … 4=Thu
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (class_period_id) REFERENCES class_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE,
    UNIQUE KEY (school_id, time_slot_id, day_of_week, class_period_id)
);
-- conflict prevention: a teacher cannot have two entries at same time_slot+day
-- enforced in application layer via repository check (DB cannot express it without trigger)
```

## Routes

```
GET    /admin/subjects                       SubjectController@index
GET    /admin/subjects/create                SubjectController@create
POST   /admin/subjects                       SubjectController@store
GET    /admin/subjects/{id}/edit             SubjectController@edit
PATCH  /admin/subjects/{id}                  SubjectController@update
DELETE /admin/subjects/{id}                  SubjectController@destroy
GET    /admin/subjects/{id}/lesson-tree      SubjectController@lessonTree
POST   /admin/subjects/import-excel          SubjectController@importExcel
POST   /admin/subjects/import-template       SubjectController@importTemplate
GET    /admin/subjects/credit-hours          SubjectController@creditHours
PATCH  /admin/subjects/credit-hours          SubjectController@saveCreditHours

GET    /admin/question-banks                 QuestionBankController@index
GET    /admin/question-banks/create          QuestionBankController@create
POST   /admin/question-banks                 QuestionBankController@store
GET    /admin/question-banks/{id}/edit       QuestionBankController@edit
PATCH  /admin/question-banks/{id}            QuestionBankController@update
DELETE /admin/question-banks/{id}            QuestionBankController@destroy
GET    /admin/question-banks/library         QuestionBankController@library
POST   /admin/question-banks/{id}/clone      QuestionBankController@clone
GET    /admin/question-banks/{id}/questions  QuestionController@index
… (questions CRUD nested under bank)

GET    /admin/class-periods                  ClassPeriodController@index
POST   /admin/class-periods                  ClassPeriodController@store
DELETE /admin/class-periods/{id}             ClassPeriodController@destroy
GET    /admin/class-periods/time-slots       TimeSlotController@index
POST   /admin/class-periods/time-slots       TimeSlotController@store
GET    /admin/class-periods/advanced         ClassPeriodController@advanced
POST   /admin/class-periods/schedule-entries ScheduleEntryController@store
DELETE /admin/class-periods/schedule-entries/{id} ScheduleEntryController@destroy

GET    /admin/school-schedule                SchoolScheduleController@index
GET    /admin/school-schedule/pdf            SchoolScheduleController@pdf
```

## Folder Structure

```
app/Modules/
├── Subjects/
│   ├── Controllers/
│   │   └── SubjectController.php
│   ├── Actions/
│   │   ├── CreateSubjectAction.php
│   │   ├── UpdateSubjectAction.php
│   │   ├── ImportSubjectsFromExcelAction.php
│   │   └── ImportSubjectsFromTemplateAction.php
│   ├── Repositories/
│   │   ├── Contracts/SubjectRepository.php
│   │   └── EloquentSubjectRepository.php
│   ├── Models/
│   │   ├── Subject.php
│   │   ├── SubjectUnit.php
│   │   └── SubjectLesson.php
│   └── DTOs/
│       └── SubjectDto.php
├── QuestionBanks/
│   ├── Controllers/{QuestionBank,Question}Controller.php
│   ├── Actions/{Create,Update,Clone}QuestionBankAction.php
│   ├── Repositories/Contracts/{QuestionBank,Question}Repository.php
│   ├── Repositories/Eloquent{QuestionBank,Question}Repository.php
│   └── Models/{QuestionBank,Question}.php
├── ClassPeriods/
│   ├── Controllers/{ClassPeriod,TimeSlot,ScheduleEntry}Controller.php
│   ├── Actions/{CreateClassPeriod,SetSubstituteTeacher,PlaceScheduleEntry}Action.php
│   ├── Services/ScheduleConflictDetector.php
│   ├── Repositories/Contracts/{ClassPeriod,TimeSlot,ScheduleEntry}Repository.php
│   └── Models/{ClassPeriod,TimeSlot,ScheduleEntry}.php
└── SchoolSchedule/
    ├── Controllers/SchoolScheduleController.php
    ├── Repositories/Contracts/ScheduleViewRepository.php
    └── Repositories/EloquentScheduleViewRepository.php

resources/views/admin/
├── subjects/{index,_form,lesson_tree,credit_hours}.blade.php
├── question-banks/{index,_form,library,questions/index}.blade.php
├── class-periods/{index,advanced,time_slots}.blade.php
└── school-schedule/{index,pdf}.blade.php

lang/{ar,en}/sprint4.php
```

## Sequence: Manual Class Period Creation

```
Admin                Controller            Action               Repo                 DB
  |  POST /class-periods  |                  |                    |                    |
  |---- (data) ---------->|                  |                    |                    |
  |                       | execute(dto)     |                    |                    |
  |                       |----------------->| validateNoConflict |                    |
  |                       |                  |------------------->| count by teacher   |
  |                       |                  |                    |------------------->|
  |                       |                  |                    |<-- 0 (no conflict) |
  |                       |                  | create($period)    |                    |
  |                       |                  |------------------->|                    |
  |                       |                  |                    |---- INSERT ------->|
  |                       |                  |<--- ClassPeriod ---|                    |
  |                       |<----- DTO -------|                    |                    |
  |<---- 200 + envelope --|                  |                    |                    |
```

## Conflict detection

For US-403:
- Before persisting a `schedule_entries` row, the repository checks: any other entry with the same `(school_id, time_slot_id, day_of_week)` for any `class_period_id` whose `teacher_id` matches the new entry's teacher? If yes → reject with `SCHEDULE_CONFLICT`.
- Same check for `class_room_id` (one classroom can't have two classes at once).

## Technology Stack

- **Backend**: Laravel 12 / PHP 8.4
- **Frontend**: Blade + Bootstrap 4 + jQuery for the dashboard, plus a small JS helper for the drag-drop advanced schedule
- **Database**: MySQL 8 (viewclass schema)
- **PDF**: `barryvdh/laravel-dompdf` (already installed for User Cards)
- **Excel**: `maatwebsite/excel` (install if not present)
- **i18n**: Laravel built-in localization, `lang/{ar,en}/sprint4.php`
