<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Modules\Localization\Controllers\LocaleController;
use App\Modules\Profile\Controllers\ProfileWebController;
use App\Modules\Scope\Controllers\ScopeController;
use Illuminate\Support\Facades\Route;

// Root: show login page directly (per Sprint 1 deliverable — /login and / both reach login)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Locale switcher — available to guests and authenticated users
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->whereIn('locale', ['ar', 'en'])
    ->name('locale.switch');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Scope (header company/school/semester selectors)
    Route::get('/scope/options', [ScopeController::class, 'options'])->name('scope.options');
    Route::post('/scope', [ScopeController::class, 'set'])->name('scope.set');

    // Profile (card 6)
    Route::get('/profile/edit', [ProfileWebController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileWebController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileWebController::class, 'changePassword'])->name('profile.change-password');
    Route::post('/profile/avatar', [ProfileWebController::class, 'updateAvatar'])->name('profile.avatar');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::get('/notifications/latest', [\App\Http\Controllers\NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/clear-read', [\App\Http\Controllers\NotificationController::class, 'clearRead'])->name('notifications.clear-read');

    // Messages
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [\App\Http\Controllers\MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [\App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('messages.count');
    Route::get('/messages/{conversation}', [\App\Http\Controllers\MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/reply', [\App\Http\Controllers\MessageController::class, 'reply'])->name('messages.reply');
    Route::post('/messages/{conversation}/mute', [\App\Http\Controllers\MessageController::class, 'toggleMute'])->name('messages.mute');
    Route::delete('/messages/message/{message}', [\App\Http\Controllers\MessageController::class, 'destroyMessage'])->name('messages.destroy-message');
    Route::post('/messages/group', [\App\Http\Controllers\MessageController::class, 'createGroup'])->name('messages.create-group');
});

// Admin Routes (Super Admin Only)
Route::middleware(['auth', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    // School Branches (Trello card 51) — global lookup, schools point at one branch.
    Route::get('school-branches', [\App\Modules\SchoolBranches\Controllers\SchoolBranchController::class, 'index'])->name('school-branches.index');
    Route::get('school-branches/create', [\App\Modules\SchoolBranches\Controllers\SchoolBranchController::class, 'create'])->name('school-branches.create');
    Route::post('school-branches', [\App\Modules\SchoolBranches\Controllers\SchoolBranchController::class, 'store'])->name('school-branches.store');
    Route::get('school-branches/{id}/edit', [\App\Modules\SchoolBranches\Controllers\SchoolBranchController::class, 'edit'])->name('school-branches.edit');
    Route::put('school-branches/{id}', [\App\Modules\SchoolBranches\Controllers\SchoolBranchController::class, 'update'])->name('school-branches.update');
    Route::delete('school-branches/{id}', [\App\Modules\SchoolBranches\Controllers\SchoolBranchController::class, 'destroy'])->name('school-branches.destroy');

    // SMS Services (Trello card 51 — Additional Services button)
    Route::prefix('sms-services')->name('sms-services.')->group(function () {
        Route::get('/', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'index'])->name('index');
        Route::get('{school}/connection', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'editConnection'])->name('connection.edit');
        Route::put('{school}/connection', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'updateConnection'])->name('connection.update');
        Route::get('{school}/default-sender', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'editDefaultSender'])->name('default-sender.edit');
        Route::put('{school}/default-sender', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'updateDefaultSender'])->name('default-sender.update');
        Route::post('{school}/toggle', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'toggleActive'])->name('toggle');
        Route::get('{school}/senders', [\App\Modules\SmsServices\Controllers\SmsSenderRequestController::class, 'index'])->name('senders.index');
        Route::get('{school}/senders/create', [\App\Modules\SmsServices\Controllers\SmsSenderRequestController::class, 'create'])->name('senders.create');
        Route::post('{school}/senders', [\App\Modules\SmsServices\Controllers\SmsSenderRequestController::class, 'store'])->name('senders.store');
        Route::delete('{school}/senders/{sender}', [\App\Modules\SmsServices\Controllers\SmsSenderRequestController::class, 'destroy'])->name('senders.destroy');
    });

    // Schools Management
    Route::resource('schools', \App\Http\Controllers\Admin\SchoolController::class);

    // Per-school control menu — Sprint 2
    Route::prefix('schools/{school}')->name('schools.')->group(function () {
        Route::get('settings', [\App\Http\Controllers\Admin\School\SchoolSettingsController::class, 'show'])->name('settings.show');
        Route::put('settings', [\App\Http\Controllers\Admin\School\SchoolSettingsController::class, 'update'])->name('settings.update');

        Route::get('academic-years', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'index'])->name('academic-years.index');
        Route::post('academic-years', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'storeYear'])->name('academic-years.store');
        Route::post('academic-years/{year}/promote', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'promote'])->name('academic-years.promote');
        Route::post('academic-years/{year}/terms', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'storeTerm'])->name('academic-years.terms.store');
        Route::put('academic-years/{year}/terms/{term}/set-current', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'setCurrentTerm'])->name('academic-years.terms.set-current');
        Route::delete('academic-years/{year}/terms/{term}', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'destroyTerm'])->name('academic-years.terms.destroy');
        Route::post('academic-years/{year}/terms/{term}/weeks', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'storeWeek'])->name('academic-years.terms.weeks.store');
        Route::delete('academic-years/{year}/terms/{term}/weeks/{week}', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'destroyWeek'])->name('academic-years.terms.weeks.destroy');
        Route::get('grade-levels', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'index'])->name('grade-levels.index');
        Route::post('grade-levels', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'storeSection'])->name('grade-levels.store');
        Route::get('grade-levels/{section}/classes', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'showClasses'])->name('grade-levels.classes');
        Route::post('grade-levels/{section}/classes', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'storeClass'])->name('grade-levels.classes.store');
        Route::delete('grade-levels/{section}/classes/{class}', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'destroyClass'])->name('grade-levels.classes.destroy');
        Route::get('grade-levels/{section}/classes/{class}/students', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'showStudents'])->name('grade-levels.classes.students');
        Route::post('grade-levels/{section}/classes/{class}/students', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'addStudent'])->name('grade-levels.classes.students.add');
        Route::post('grade-levels/{section}/classes/{class}/students/transfer', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'transferStudents'])->name('grade-levels.classes.students.transfer');
        Route::get('permissions', [\App\Http\Controllers\Admin\School\SchoolPermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions/toggle', [\App\Http\Controllers\Admin\School\SchoolPermissionController::class, 'toggle'])->name('permissions.toggle');
        Route::post('permissions/copy', [\App\Http\Controllers\Admin\School\SchoolPermissionController::class, 'copyFrom'])->name('permissions.copy');
    });
});

// School Admin Routes
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('manage')->name('manage.')->group(function () {
    // Sections Management
    Route::resource('sections', \App\Http\Controllers\Admin\SectionController::class);

    // Classes Management
    Route::resource('classes', \App\Http\Controllers\Admin\ClassController::class);

    // Subjects Management — legacy resource kept for backward compat;
    // Sprint 4 group below shadows the index/CRUD routes with the module controller.
    Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class)
        ->only(['show']);

    // Users Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);

    // Academic Years
    Route::resource('academic-years', \App\Http\Controllers\Admin\AcademicYearController::class);

    // Schedules Management
    Route::resource('schedules', \App\Http\Controllers\Admin\ScheduleController::class);
    Route::post('schedules/{schedule}/periods', [\App\Http\Controllers\Admin\ScheduleController::class, 'storePeriod'])->name('schedules.store-period');
    Route::delete('schedules/{schedule}/periods/{period}', [\App\Http\Controllers\Admin\ScheduleController::class, 'destroyPeriod'])->name('schedules.destroy-period');

    // Weekly Plans Management
    Route::get('weekly-plans/pdf', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'pdf'])->name('weekly-plans.pdf');
    Route::resource('weekly-plans', \App\Http\Controllers\Admin\WeeklyPlanController::class);
    Route::post('weekly-plans/{weekly_plan}/lock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'lock'])->name('weekly-plans.lock');
    Route::post('weekly-plans/{weekly_plan}/unlock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'unlock'])->name('weekly-plans.unlock');
    Route::post('weekly-plans/{weekly_plan}/mark-prepared', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'markPrepared'])->name('weekly-plans.mark-prepared');
    Route::post('weekly-plans/bulk-lock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'bulkLock'])->name('weekly-plans.bulk-lock');
    Route::get('weekly-plans/{weekly_plan}/duplicate', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'duplicate'])->name('weekly-plans.duplicate');
});

// Sprint 3 — Users Module (admin-prefixed)
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('students', [\App\Modules\Users\Controllers\StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [\App\Modules\Users\Controllers\StudentController::class, 'create'])->name('students.create');
        Route::post('students', [\App\Modules\Users\Controllers\StudentController::class, 'store'])->name('students.store');
        Route::post('students/bulk', [\App\Modules\Users\Controllers\StudentController::class, 'bulk'])->name('students.bulk');
        Route::get('students/{id}/edit', [\App\Modules\Users\Controllers\StudentController::class, 'edit'])->name('students.edit');
        Route::get('students/{id}/parents', [\App\Modules\Users\Controllers\StudentController::class, 'parents'])->name('students.parents');
        Route::get('students/{id}/schedule', [\App\Modules\Users\Controllers\StudentController::class, 'schedule'])->name('students.schedule');
        Route::get('students/{id}/lessons', [\App\Modules\Users\Controllers\StudentController::class, 'lessons'])->name('students.lessons');
        Route::get('students/{id}/attendance', [\App\Modules\Users\Controllers\StudentController::class, 'attendance'])->name('students.attendance');
        Route::get('students/{id}/behavior', [\App\Modules\Users\Controllers\StudentController::class, 'behavior'])->name('students.behavior');
        Route::get('students/{id}/medical', [\App\Modules\Users\Controllers\StudentController::class, 'medical'])->name('students.medical');
        Route::get('students/{id}', [\App\Modules\Users\Controllers\StudentController::class, 'show'])->whereNumber('id')->name('students.show');
        Route::put('students/{id}', [\App\Modules\Users\Controllers\StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{id}', [\App\Modules\Users\Controllers\StudentController::class, 'destroy'])->name('students.destroy');

        Route::get('parents', [\App\Modules\Users\Controllers\ParentController::class, 'index'])->name('parents.index');
        Route::get('parents/create', [\App\Modules\Users\Controllers\ParentController::class, 'create'])->name('parents.create');
        Route::post('parents', [\App\Modules\Users\Controllers\ParentController::class, 'store'])->name('parents.store');
        Route::get('parents/{id}', [\App\Modules\Users\Controllers\ParentController::class, 'show'])->name('parents.show');
        Route::get('parents/{id}/edit', [\App\Modules\Users\Controllers\ParentController::class, 'edit'])->name('parents.edit');
        Route::put('parents/{id}', [\App\Modules\Users\Controllers\ParentController::class, 'update'])->name('parents.update');
        Route::delete('parents/{id}', [\App\Modules\Users\Controllers\ParentController::class, 'destroy'])->name('parents.destroy');
        Route::get('parents/{id}/students', [\App\Modules\Users\Controllers\ParentController::class, 'students'])->name('parents.students');
        Route::post('parents/{id}/students', [\App\Modules\Users\Controllers\ParentController::class, 'syncStudents'])->name('parents.students.sync');

        Route::get('teachers', [\App\Modules\Users\Controllers\TeacherController::class, 'index'])->name('teachers.index');
        Route::get('teachers/workloads', [\App\Modules\Users\Controllers\TeacherController::class, 'workloads'])->name('teachers.workloads');
        Route::get('teachers/import', [\App\Modules\Users\Controllers\TeacherController::class, 'importForm'])->name('teachers.import');
        Route::get('teachers/create', [\App\Modules\Users\Controllers\TeacherController::class, 'create'])->name('teachers.create');
        Route::post('teachers', [\App\Modules\Users\Controllers\TeacherController::class, 'store'])->name('teachers.store');
        Route::get('teachers/{id}', [\App\Modules\Users\Controllers\TeacherController::class, 'show'])->name('teachers.show');
        Route::get('teachers/{id}/edit', [\App\Modules\Users\Controllers\TeacherController::class, 'edit'])->name('teachers.edit');
        Route::put('teachers/{id}', [\App\Modules\Users\Controllers\TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('teachers/{id}', [\App\Modules\Users\Controllers\TeacherController::class, 'destroy'])->name('teachers.destroy');

        Route::get('admins', [\App\Modules\Users\Controllers\AdminController::class, 'index'])->name('admins.index');
        Route::get('admins/create', [\App\Modules\Users\Controllers\AdminController::class, 'create'])->name('admins.create');
        Route::post('admins', [\App\Modules\Users\Controllers\AdminController::class, 'store'])->name('admins.store');
        Route::get('admins/{id}/edit', [\App\Modules\Users\Controllers\AdminController::class, 'edit'])->name('admins.edit');
        Route::put('admins/{id}', [\App\Modules\Users\Controllers\AdminController::class, 'update'])->name('admins.update');
        Route::delete('admins/{id}', [\App\Modules\Users\Controllers\AdminController::class, 'destroy'])->name('admins.destroy');
        Route::get('admins/{id}/supervisees', [\App\Modules\Users\Controllers\AdminController::class, 'supervisees'])->name('admins.supervisees');
        Route::post('admins/{id}/supervisees', [\App\Modules\Users\Controllers\AdminController::class, 'syncSupervisees'])->name('admins.supervisees.sync');

        Route::get('job-titles', [\App\Modules\Users\Controllers\JobTitleController::class, 'index'])->name('job-titles.index');
        Route::post('job-titles', [\App\Modules\Users\Controllers\JobTitleController::class, 'store'])->name('job-titles.store');
        Route::put('job-titles/{jobTitle}', [\App\Modules\Users\Controllers\JobTitleController::class, 'update'])->name('job-titles.update');
        Route::delete('job-titles/{jobTitle}', [\App\Modules\Users\Controllers\JobTitleController::class, 'destroy'])->name('job-titles.destroy');

        Route::get('cards', [\App\Modules\Users\Controllers\UserCardController::class, 'index'])->name('cards.index');
        Route::post('cards/generate', [\App\Modules\Users\Controllers\UserCardController::class, 'generate'])->name('cards.generate');
        Route::post('cards/{id}/regenerate-password', [\App\Modules\Users\Controllers\UserCardController::class, 'regenerate'])
            ->whereNumber('id')
            ->name('cards.regenerate');

        Route::post('{id}/impersonate', [\App\Modules\Users\Controllers\ImpersonateController::class, 'start'])->name('impersonate.start');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::post('admin/users/impersonate/stop', [\App\Modules\Users\Controllers\ImpersonateController::class, 'stop'])->name('admin.users.impersonate.stop');
});

// Sprint 4 — Subjects Module
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('subjects/credit-hours', [\App\Modules\Subjects\Controllers\SubjectController::class, 'creditHours'])->name('subjects.credit-hours');
    Route::patch('subjects/credit-hours', [\App\Modules\Subjects\Controllers\SubjectController::class, 'saveCreditHours'])->name('subjects.credit-hours.save');

    Route::get('subjects/templates', [\App\Modules\Subjects\Controllers\SubjectController::class, 'templatesIndex'])->name('subjects.templates.index');
    Route::post('subjects/templates', [\App\Modules\Subjects\Controllers\SubjectController::class, 'templatesAttach'])->name('subjects.templates.attach');

    Route::get('subjects', [\App\Modules\Subjects\Controllers\SubjectController::class, 'index'])->name('subjects.index');
    Route::get('subjects/create', [\App\Modules\Subjects\Controllers\SubjectController::class, 'create'])->name('subjects.create');
    Route::post('subjects', [\App\Modules\Subjects\Controllers\SubjectController::class, 'store'])->name('subjects.store');
    Route::get('subjects/{id}/edit', [\App\Modules\Subjects\Controllers\SubjectController::class, 'edit'])->name('subjects.edit');
    Route::put('subjects/{id}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('subjects/{id}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'destroy'])->name('subjects.destroy');

    Route::get('subjects/{id}/lesson-tree', [\App\Modules\Subjects\Controllers\SubjectController::class, 'lessonTree'])->name('subjects.lesson-tree');
    Route::post('subjects/{id}/units', [\App\Modules\Subjects\Controllers\SubjectController::class, 'storeUnit'])->name('subjects.units.store');
    Route::delete('subjects/{id}/units/{unitId}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'destroyUnit'])->name('subjects.units.destroy');
    Route::post('subjects/{id}/units/{unitId}/lessons', [\App\Modules\Subjects\Controllers\SubjectController::class, 'storeLesson'])->name('subjects.lessons.store');
    Route::delete('subjects/{id}/units/{unitId}/lessons/{lessonId}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'destroyLesson'])->name('subjects.lessons.destroy');

    // Question Banks (Sprint 4 phase 2)
    Route::get('question-banks/library', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'library'])->name('question-banks.library');
    Route::post('question-banks/library/{id}/clone', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'clone'])->name('question-banks.library.clone');

    Route::get('question-banks', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'index'])->name('question-banks.index');
    Route::get('question-banks/create', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'create'])->name('question-banks.create');
    Route::post('question-banks', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'store'])->name('question-banks.store');
    Route::get('question-banks/{id}/edit', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'edit'])->name('question-banks.edit');
    Route::put('question-banks/{id}', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'update'])->name('question-banks.update');
    Route::delete('question-banks/{id}', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'destroy'])->name('question-banks.destroy');

    Route::get('question-banks/{bankId}/questions', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'index'])->name('question-banks.questions.index');
    Route::get('question-banks/{bankId}/questions/create', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'create'])->name('question-banks.questions.create');
    Route::post('question-banks/{bankId}/questions', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'store'])->name('question-banks.questions.store');
    Route::delete('question-banks/{bankId}/questions/{questionId}', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'destroy'])->name('question-banks.questions.destroy');

    // Class Periods + Time Slots + Schedule Entries (Sprint 4 phase 3)
    Route::get('class-periods/time-slots', [\App\Modules\ClassPeriods\Controllers\TimeSlotController::class, 'index'])->name('class-periods.time-slots.index');
    Route::post('class-periods/time-slots', [\App\Modules\ClassPeriods\Controllers\TimeSlotController::class, 'store'])->name('class-periods.time-slots.store');
    Route::delete('class-periods/time-slots/{id}', [\App\Modules\ClassPeriods\Controllers\TimeSlotController::class, 'destroy'])->name('class-periods.time-slots.destroy');

    Route::get('class-periods/advanced', [\App\Modules\ClassPeriods\Controllers\ClassPeriodController::class, 'advanced'])->name('class-periods.advanced');

    Route::get('class-periods', [\App\Modules\ClassPeriods\Controllers\ClassPeriodController::class, 'index'])->name('class-periods.index');
    Route::get('class-periods/create', [\App\Modules\ClassPeriods\Controllers\ClassPeriodController::class, 'create'])->name('class-periods.create');
    Route::post('class-periods', [\App\Modules\ClassPeriods\Controllers\ClassPeriodController::class, 'store'])->name('class-periods.store');
    Route::delete('class-periods/{id}', [\App\Modules\ClassPeriods\Controllers\ClassPeriodController::class, 'destroy'])->name('class-periods.destroy');

    Route::post('class-periods/schedule-entries', [\App\Modules\ClassPeriods\Controllers\ScheduleEntryController::class, 'store'])->name('class-periods.schedule-entries.store');
    Route::delete('class-periods/schedule-entries/{id}', [\App\Modules\ClassPeriods\Controllers\ScheduleEntryController::class, 'destroy'])->name('class-periods.schedule-entries.destroy');

    // School Schedule (Sprint 4 phase 4 — read-only view + PDF)
    Route::get('school-schedule', [\App\Modules\SchoolSchedule\Controllers\SchoolScheduleController::class, 'index'])->name('school-schedule.index');
    Route::get('school-schedule/pdf', [\App\Modules\SchoolSchedule\Controllers\SchoolScheduleController::class, 'pdf'])->name('school-schedule.pdf');

    // Libraries module (public + private + virtual labs)
    Route::prefix('libraries')->name('libraries.')->group(function () {
        // Public library
        Route::get('public', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'index'])->name('public.index');
        Route::get('public/create', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'create'])->name('public.create');
        Route::post('public', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'store'])->name('public.store');
        Route::get('public/{id}/edit', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'edit'])->name('public.edit');
        Route::put('public/{id}', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'update'])->name('public.update');
        Route::delete('public/{id}', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'destroy'])->name('public.destroy');

        // Private libraries
        Route::get('private', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'index'])->name('private.index');
        Route::get('private/create', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'create'])->name('private.create');
        Route::post('private', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'store'])->name('private.store');
        Route::get('private/{id}/edit', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'edit'])->name('private.edit');
        Route::put('private/{id}', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'update'])->name('private.update');
        Route::delete('private/{id}', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'destroy'])->name('private.destroy');
        Route::get('private/{id}/items', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'items'])->name('private.items');
        Route::post('private/{id}/items', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'storeItem'])->name('private.items.store');
        Route::delete('private/{id}/items/{itemId}', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'destroyItem'])->name('private.items.destroy');

        // Virtual labs
        Route::get('labs', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'index'])->name('labs.index');
        Route::get('labs/manage', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'manage'])->name('labs.manage');
        Route::get('labs/create', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'create'])->name('labs.create');
        Route::post('labs', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'store'])->name('labs.store');
        Route::get('labs/{id}', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'show'])->name('labs.show');
        Route::get('labs/{id}/edit', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'edit'])->name('labs.edit');
        Route::put('labs/{id}', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'update'])->name('labs.update');
        Route::delete('labs/{id}', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'destroy'])->name('labs.destroy');
    });
});

// Admin Exams & Grades Routes
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin')->name('admin.')->group(function () {
    // Exams Management
    Route::resource('exams', \App\Http\Controllers\Admin\ExamController::class);
    Route::post('exams/{exam}/publish', [\App\Http\Controllers\Admin\ExamController::class, 'publish'])->name('exams.publish');
    Route::post('exams/{exam}/unpublish', [\App\Http\Controllers\Admin\ExamController::class, 'unpublish'])->name('exams.unpublish');
    Route::post('exams/{exam}/activate', [\App\Http\Controllers\Admin\ExamController::class, 'activate'])->name('exams.activate');
    Route::post('exams/{exam}/complete', [\App\Http\Controllers\Admin\ExamController::class, 'complete'])->name('exams.complete');
    Route::get('exams/{exam}/results', [\App\Http\Controllers\Admin\ExamController::class, 'results'])->name('exams.results');

    // Exam Questions Management
    Route::get('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'index'])->name('exams.questions.index');
    Route::get('exams/{exam}/questions/create', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'create'])->name('exams.questions.create');
    Route::post('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'store'])->name('exams.questions.store');
    Route::get('exams/{exam}/questions/{question}/edit', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'edit'])->name('exams.questions.edit');
    Route::put('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'update'])->name('exams.questions.update');
    Route::delete('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');
    Route::post('exams/{exam}/questions/reorder', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'reorder'])->name('exams.questions.reorder');
    Route::post('exams/{exam}/questions/{question}/duplicate', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'duplicate'])->name('exams.questions.duplicate');

    // Grades Management
    Route::get('grades', [\App\Http\Controllers\Admin\GradeController::class, 'index'])->name('grades.index');
    Route::post('grades', [\App\Http\Controllers\Admin\GradeController::class, 'store'])->name('grades.store');
    Route::post('grades/publish', [\App\Http\Controllers\Admin\GradeController::class, 'publish'])->name('grades.publish');
    Route::post('grades/unpublish', [\App\Http\Controllers\Admin\GradeController::class, 'unpublish'])->name('grades.unpublish');

    // Grade Reports
    Route::get('grades/class-report', [\App\Http\Controllers\Admin\GradeController::class, 'classReport'])->name('grades.class-report');
    Route::get('grades/student-report', [\App\Http\Controllers\Admin\GradeController::class, 'studentReport'])->name('grades.student-report');
    Route::get('grades/subject-report', [\App\Http\Controllers\Admin\GradeController::class, 'subjectReport'])->name('grades.subject-report');

    // Attendance Management
    Route::get('attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('attendance/mark-all-present', [\App\Http\Controllers\Admin\AttendanceController::class, 'markAllPresent'])->name('attendance.mark-all-present');
    Route::get('attendance/daily-report', [\App\Http\Controllers\Admin\AttendanceController::class, 'dailyReport'])->name('attendance.daily-report');
    Route::get('attendance/student-report', [\App\Http\Controllers\Admin\AttendanceController::class, 'studentReport'])->name('attendance.student-report');
    Route::get('attendance/class-report', [\App\Http\Controllers\Admin\AttendanceController::class, 'classReport'])->name('attendance.class-report');
    Route::get('attendance/calendar', [\App\Http\Controllers\Admin\AttendanceController::class, 'calendar'])->name('attendance.calendar');
});

// Teacher Routes
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    // Teacher Schedule
    Route::get('schedule', [\App\Http\Controllers\Admin\ScheduleController::class, 'teacherSchedule'])->name('schedule');

    // Weekly Plans for Teachers
    Route::resource('weekly-plans', \App\Http\Controllers\Admin\WeeklyPlanController::class);
    Route::get('weekly-plans/{weekly_plan}/duplicate', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'duplicate'])->name('weekly-plans.duplicate');

    // Exams for Teachers
    Route::resource('exams', \App\Http\Controllers\Admin\ExamController::class);
    Route::post('exams/{exam}/publish', [\App\Http\Controllers\Admin\ExamController::class, 'publish'])->name('exams.publish');
    Route::post('exams/{exam}/unpublish', [\App\Http\Controllers\Admin\ExamController::class, 'unpublish'])->name('exams.unpublish');
    Route::post('exams/{exam}/activate', [\App\Http\Controllers\Admin\ExamController::class, 'activate'])->name('exams.activate');
    Route::post('exams/{exam}/complete', [\App\Http\Controllers\Admin\ExamController::class, 'complete'])->name('exams.complete');
    Route::get('exams/{exam}/results', [\App\Http\Controllers\Admin\ExamController::class, 'results'])->name('exams.results');

    // Exam Questions for Teachers
    Route::get('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'index'])->name('exams.questions.index');
    Route::get('exams/{exam}/questions/create', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'create'])->name('exams.questions.create');
    Route::post('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'store'])->name('exams.questions.store');
    Route::get('exams/{exam}/questions/{question}/edit', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'edit'])->name('exams.questions.edit');
    Route::put('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'update'])->name('exams.questions.update');
    Route::delete('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');
    Route::post('exams/{exam}/questions/{question}/duplicate', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'duplicate'])->name('exams.questions.duplicate');

    // Grades for Teachers
    Route::get('grades', [\App\Http\Controllers\Admin\GradeController::class, 'index'])->name('grades.index');
    Route::post('grades', [\App\Http\Controllers\Admin\GradeController::class, 'store'])->name('grades.store');
    Route::post('grades/publish', [\App\Http\Controllers\Admin\GradeController::class, 'publish'])->name('grades.publish');
    Route::post('grades/unpublish', [\App\Http\Controllers\Admin\GradeController::class, 'unpublish'])->name('grades.unpublish');
    Route::get('grades/class-report', [\App\Http\Controllers\Admin\GradeController::class, 'classReport'])->name('grades.class-report');
    Route::get('grades/student-report', [\App\Http\Controllers\Admin\GradeController::class, 'studentReport'])->name('grades.student-report');
    Route::get('grades/subject-report', [\App\Http\Controllers\Admin\GradeController::class, 'subjectReport'])->name('grades.subject-report');

    // Attendance for Teachers
    Route::get('attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [\App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('attendance/mark-all-present', [\App\Http\Controllers\Admin\AttendanceController::class, 'markAllPresent'])->name('attendance.mark-all-present');
    Route::get('attendance/daily-report', [\App\Http\Controllers\Admin\AttendanceController::class, 'dailyReport'])->name('attendance.daily-report');
    Route::get('attendance/student-report', [\App\Http\Controllers\Admin\AttendanceController::class, 'studentReport'])->name('attendance.student-report');
    Route::get('attendance/class-report', [\App\Http\Controllers\Admin\AttendanceController::class, 'classReport'])->name('attendance.class-report');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('grades', [\App\Http\Controllers\StudentController::class, 'grades'])->name('grades');
    Route::get('attendance', [\App\Http\Controllers\StudentController::class, 'attendance'])->name('attendance');
    Route::get('exams', [\App\Http\Controllers\StudentController::class, 'exams'])->name('exams');
    Route::get('schedule', [\App\Http\Controllers\StudentController::class, 'schedule'])->name('schedule');
    Route::get('weekly-plans', [\App\Http\Controllers\StudentController::class, 'weeklyPlans'])->name('weekly-plans');
});

// Parent Routes
Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\ParentController::class, 'dashboard'])->name('dashboard');
    Route::get('child/{child}', [\App\Http\Controllers\ParentController::class, 'childDetails'])->name('child');
    Route::get('child/{child}/grades', [\App\Http\Controllers\ParentController::class, 'childGrades'])->name('child.grades');
    Route::get('child/{child}/attendance', [\App\Http\Controllers\ParentController::class, 'childAttendance'])->name('child.attendance');
    Route::get('child/{child}/schedule', [\App\Http\Controllers\ParentController::class, 'childSchedule'])->name('child.schedule');
    Route::get('contact-teacher', [\App\Http\Controllers\ParentController::class, 'contactTeacher'])->name('contact-teacher');
});

// Sprint 5 — Grade Reports (report-builder layer above the legacy grades data-entry)
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/grade-reports')->name('admin.grade-reports.')->group(function () {
    Route::get('/', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'index'])->name('index');
    Route::get('create', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'create'])->name('create');
    Route::post('/', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'store'])->name('store');
    Route::get('{id}', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'show'])->name('show');
    Route::delete('{id}', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'destroy'])->name('destroy');
});

// Reports Routes
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/reports')->name('admin.reports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
    // Sprint 5 — 3 categorised report views
    Route::get('administrative', [\App\Http\Controllers\Admin\ReportController::class, 'administrative'])->name('administrative');
    Route::get('statistical', [\App\Http\Controllers\Admin\ReportController::class, 'statistical'])->name('statistical');
    Route::get('user-reports', [\App\Http\Controllers\Admin\ReportController::class, 'userReports'])->name('user-reports');
    Route::get('schools-general', [\App\Http\Controllers\Admin\ReportController::class, 'schoolsGeneral'])->name('schools-general');
    Route::get('student-card', [\App\Http\Controllers\Admin\ReportController::class, 'studentCard'])->name('student-card');
    Route::get('student-card/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'studentCardPdf'])->name('student-card-pdf');
    Route::get('class-report', [\App\Http\Controllers\Admin\ReportController::class, 'classReport'])->name('class-report');
    Route::get('class-report/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'classReportPdf'])->name('class-report-pdf');
    Route::get('attendance-report', [\App\Http\Controllers\Admin\ReportController::class, 'attendanceReport'])->name('attendance-report');
    Route::get('attendance-report/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'attendanceReportPdf'])->name('attendance-report-pdf');
    Route::get('analytics', [\App\Http\Controllers\Admin\ReportController::class, 'analytics'])->name('analytics');
});

// Files Management
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('admin/files')->name('admin.files.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\FileController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\Admin\FileController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\FileController::class, 'store'])->name('store');
    Route::get('{file}', [\App\Http\Controllers\Admin\FileController::class, 'show'])->name('show');
    Route::get('{file}/edit', [\App\Http\Controllers\Admin\FileController::class, 'edit'])->name('edit');
    Route::put('{file}', [\App\Http\Controllers\Admin\FileController::class, 'update'])->name('update');
    Route::delete('{file}', [\App\Http\Controllers\Admin\FileController::class, 'destroy'])->name('destroy');
    Route::get('{file}/download', [\App\Http\Controllers\Admin\FileController::class, 'download'])->name('download');
});

// Assignments Management
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('admin/assignments')->name('admin.assignments.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AssignmentController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\Admin\AssignmentController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\AssignmentController::class, 'store'])->name('store');
    Route::get('{assignment}', [\App\Http\Controllers\Admin\AssignmentController::class, 'show'])->name('show');
    Route::get('{assignment}/edit', [\App\Http\Controllers\Admin\AssignmentController::class, 'edit'])->name('edit');
    Route::put('{assignment}', [\App\Http\Controllers\Admin\AssignmentController::class, 'update'])->name('update');
    Route::delete('{assignment}', [\App\Http\Controllers\Admin\AssignmentController::class, 'destroy'])->name('destroy');
    Route::post('{assignment}/grade/{student}', [\App\Http\Controllers\Admin\AssignmentController::class, 'grade'])->name('grade');
});

// Global Search
Route::middleware(['auth'])->prefix('search')->name('search.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SearchController::class, 'index'])->name('index');
    Route::get('/quick', [\App\Http\Controllers\SearchController::class, 'quick'])->name('quick');
});

// Activity Logs
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/activity-logs')->name('admin.activity-logs.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('index');
    Route::get('{activityLog}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->name('show');
    Route::delete('{activityLog}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'destroy'])->name('destroy');
    Route::post('clear', [\App\Http\Controllers\Admin\ActivityLogController::class, 'clear'])->name('clear');
});

// Data Exports
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/exports')->name('admin.exports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ExportController::class, 'index'])->name('index');
    Route::get('students', [\App\Http\Controllers\Admin\ExportController::class, 'students'])->name('students');
    Route::get('teachers', [\App\Http\Controllers\Admin\ExportController::class, 'teachers'])->name('teachers');
    Route::get('grades', [\App\Http\Controllers\Admin\ExportController::class, 'grades'])->name('grades');
    Route::get('attendance', [\App\Http\Controllers\Admin\ExportController::class, 'attendance'])->name('attendance');
});

// System Maintenance (Super Admin Only)
Route::middleware(['auth', 'role:super-admin'])->prefix('admin/maintenance')->name('admin.maintenance.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\MaintenanceController::class, 'index'])->name('index');
    Route::post('clear-cache', [\App\Http\Controllers\Admin\MaintenanceController::class, 'clearCache'])->name('clear-cache');
    Route::post('clear-logs', [\App\Http\Controllers\Admin\MaintenanceController::class, 'clearLogs'])->name('clear-logs');
    Route::post('optimize', [\App\Http\Controllers\Admin\MaintenanceController::class, 'optimizeSystem'])->name('optimize');
    Route::get('system-info', [\App\Http\Controllers\Admin\MaintenanceController::class, 'systemInfo'])->name('system-info');
});

// Settings Management
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/settings')->name('admin.settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
    Route::put('/', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('update');
    Route::get('profile', [\App\Http\Controllers\Admin\SettingsController::class, 'profile'])->name('profile');
    Route::put('profile', [\App\Http\Controllers\Admin\SettingsController::class, 'updateProfile'])->name('profile.update');
    Route::get('password', [\App\Http\Controllers\Admin\SettingsController::class, 'password'])->name('password');
    Route::put('password', [\App\Http\Controllers\Admin\SettingsController::class, 'updatePassword'])->name('password.update');
    Route::get('notifications', [\App\Http\Controllers\Admin\SettingsController::class, 'notifications'])->name('notifications');
    Route::put('notifications', [\App\Http\Controllers\Admin\SettingsController::class, 'updateNotifications'])->name('notifications.update');
    Route::post('logo', [\App\Http\Controllers\Admin\SettingsController::class, 'uploadLogo'])->name('logo.upload');
});

// === Noor card 58 ===
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('noor', [\App\Modules\NoorImport\Controllers\NoorImportController::class, 'form'])->name('noor.form');
    Route::post('noor', [\App\Modules\NoorImport\Controllers\NoorImportController::class, 'submit'])->name('noor.submit');
});
