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
        Route::post('{school}/connection/test', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'testConnection'])->name('connection.test');
        Route::get('{school}/messages', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'messages'])->name('messages.index');
        Route::get('{school}/default-sender', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'editDefaultSender'])->name('default-sender.edit');
        Route::put('{school}/default-sender', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'updateDefaultSender'])->name('default-sender.update');
        Route::post('{school}/toggle', [\App\Modules\SmsServices\Controllers\SmsServicesController::class, 'toggleActive'])->name('toggle');
        Route::get('senders/template/{provider}', [\App\Modules\SmsServices\Controllers\SmsSenderRequestController::class, 'downloadTemplate'])
            ->whereIn('provider', ['stc', 'mobily', 'zain'])
            ->name('senders.template');
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
        Route::get('academic-years/migrate', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'migrate'])->name('academic-years.migrate');
        Route::post('academic-years/migrate/classes', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'migrateClasses'])->name('academic-years.migrate.classes');
        Route::post('academic-years/migrate/students', [\App\Http\Controllers\Admin\School\SchoolAcademicYearController::class, 'migrateStudents'])->name('academic-years.migrate.students');
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
        Route::get('grade-levels/{section}/classes/{class}/edit', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'editClass'])->name('grade-levels.classes.edit');
        Route::put('grade-levels/{section}/classes/{class}', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'updateClass'])->name('grade-levels.classes.update');
        Route::delete('grade-levels/{section}/classes/{class}', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'destroyClass'])->name('grade-levels.classes.destroy');
        Route::get('grade-levels/{section}/classes/{class}/students', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'showStudents'])->name('grade-levels.classes.students');
        Route::get('grade-levels/{section}/classes/{class}', [\App\Http\Controllers\Admin\School\SchoolGradeLevelController::class, 'showClass'])->name('grade-levels.classes.show');
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
    Route::get('schedules/list', [\App\Http\Controllers\Admin\ScheduleController::class, 'manageList'])->name('schedules.list');
    Route::resource('schedules', \App\Http\Controllers\Admin\ScheduleController::class);
    Route::post('schedules/{schedule}/periods', [\App\Http\Controllers\Admin\ScheduleController::class, 'storePeriod'])->name('schedules.store-period');
    Route::delete('schedules/{schedule}/periods/{period}', [\App\Http\Controllers\Admin\ScheduleController::class, 'destroyPeriod'])->name('schedules.destroy-period');

    // Weekly Plans Management
    Route::get('weekly-plans/pdf', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'pdf'])->name('weekly-plans.pdf');
    Route::get('weekly-plans/excel', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'excel'])->name('weekly-plans.excel');
    Route::resource('weekly-plans', \App\Http\Controllers\Admin\WeeklyPlanController::class);
    Route::post('weekly-plans/{weekly_plan}/lock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'lock'])->name('weekly-plans.lock');
    Route::post('weekly-plans/{weekly_plan}/unlock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'unlock'])->name('weekly-plans.unlock');
    Route::post('weekly-plans/{weekly_plan}/mark-prepared', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'markPrepared'])->name('weekly-plans.mark-prepared');
    Route::post('weekly-plans/bulk-lock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'bulkLock'])->name('weekly-plans.bulk-lock');
    Route::get('weekly-plans/{weekly_plan}/duplicate', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'duplicate'])->name('weekly-plans.duplicate');

    // Card 66 — الملاحظات الجاهزة (Weekly Plan Note Templates)
    Route::get('weekly-plan-notes', [\App\Http\Controllers\Admin\WeeklyPlanNoteTemplateController::class, 'index'])->name('weekly-plan-notes.index');
    Route::post('weekly-plan-notes', [\App\Http\Controllers\Admin\WeeklyPlanNoteTemplateController::class, 'store'])->name('weekly-plan-notes.store');
    Route::put('weekly-plan-notes/{id}', [\App\Http\Controllers\Admin\WeeklyPlanNoteTemplateController::class, 'update'])->name('weekly-plan-notes.update');
    Route::delete('weekly-plan-notes/{id}', [\App\Http\Controllers\Admin\WeeklyPlanNoteTemplateController::class, 'destroy'])->name('weekly-plan-notes.destroy');

    // === Books card 65 — write routes (create/edit/delete) — admin-only ===
    Route::get('books/create', [\App\Modules\Books\Controllers\BookController::class, 'create'])->name('books.create');
    Route::post('books', [\App\Modules\Books\Controllers\BookController::class, 'store'])->name('books.store');
    // Books — bulk grade↔book management (POST/save is write-only — admin-only)
    Route::post('books/grades', [\App\Modules\Books\Controllers\BookGradeController::class, 'save'])->name('books.grades.save');
    Route::get('books/{id}/edit', [\App\Modules\Books\Controllers\BookController::class, 'edit'])->name('books.edit');
    Route::put('books/{id}', [\App\Modules\Books\Controllers\BookController::class, 'update'])->name('books.update');
    Route::delete('books/{id}', [\App\Modules\Books\Controllers\BookController::class, 'destroy'])->name('books.destroy');
});

// Sprint 3 — Users Module (admin-prefixed)
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('users')->name('users.')->group(function () {
        // === School search card 59 ===
        Route::get('students/global-search', [\App\Modules\Users\Controllers\StudentGlobalSearchController::class, 'index'])->name('students.global-search');
        Route::get('students', [\App\Modules\Users\Controllers\StudentController::class, 'index'])->name('students.index');
        Route::get('students/create', [\App\Modules\Users\Controllers\StudentController::class, 'create'])->name('students.create');
        Route::post('students', [\App\Modules\Users\Controllers\StudentController::class, 'store'])->name('students.store');
        Route::post('students/bulk', [\App\Modules\Users\Controllers\StudentController::class, 'bulk'])->name('students.bulk');
        // === Bulk student photos + status (card #125) ===
        Route::get('students/photos', [\App\Modules\Users\Controllers\StudentController::class, 'photosForm'])->name('students.photos');
        Route::post('students/photos', [\App\Modules\Users\Controllers\StudentController::class, 'importPhotos'])->name('students.photos.import');
        Route::get('students/status', [\App\Modules\Users\Controllers\StudentController::class, 'statusForm'])->name('students.status');
        Route::post('students/status', [\App\Modules\Users\Controllers\StudentController::class, 'updateFromExcel'])->name('students.status.update');
        Route::post('students/graduates/delete', [\App\Modules\Users\Controllers\StudentController::class, 'deleteGraduates'])->name('students.graduates.delete');
        // === Excel student import card #108 (declared before students/{id} wildcard) ===
        Route::get('students/import', [\App\Modules\StudentImport\Controllers\StudentImportController::class, 'form'])->name('students.import.form');
        Route::get('students/import/template', [\App\Modules\StudentImport\Controllers\StudentImportController::class, 'template'])->name('students.import.template');
        Route::post('students/import/preview', [\App\Modules\StudentImport\Controllers\StudentImportController::class, 'preview'])->name('students.import.preview');
        Route::post('students/import/{log}/run', [\App\Modules\StudentImport\Controllers\StudentImportController::class, 'execute'])->whereNumber('log')->name('students.import.execute');
        Route::get('students/import/{log}/errors', [\App\Modules\StudentImport\Controllers\StudentImportController::class, 'errorsReport'])->whereNumber('log')->name('students.import.errors');
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
        // Excel tools — declared before parents/{id} so the wildcard does not swallow them
        Route::get('parents/import', [\App\Modules\Users\Controllers\ParentController::class, 'importForm'])->name('parents.import');
        Route::get('parents/import/template', [\App\Modules\Users\Controllers\ParentController::class, 'importTemplate'])->name('parents.import.template');
        Route::post('parents/import', [\App\Modules\Users\Controllers\ParentController::class, 'import'])->name('parents.import.run');
        Route::get('parents/export', [\App\Modules\Users\Controllers\ParentController::class, 'export'])->name('parents.export');
        Route::post('parents/import-update', [\App\Modules\Users\Controllers\ParentController::class, 'importUpdate'])->name('parents.import.update');
        Route::post('parents/link-by-numbers', [\App\Modules\Users\Controllers\ParentController::class, 'linkByNumbers'])->name('parents.link.numbers');
        Route::get('parents/create', [\App\Modules\Users\Controllers\ParentController::class, 'create'])->name('parents.create');
        Route::post('parents', [\App\Modules\Users\Controllers\ParentController::class, 'store'])->name('parents.store');
        Route::get('parents/{id}', [\App\Modules\Users\Controllers\ParentController::class, 'show'])->whereNumber('id')->name('parents.show');
        Route::get('parents/{id}/edit', [\App\Modules\Users\Controllers\ParentController::class, 'edit'])->whereNumber('id')->name('parents.edit');
        Route::put('parents/{id}', [\App\Modules\Users\Controllers\ParentController::class, 'update'])->whereNumber('id')->name('parents.update');
        Route::delete('parents/{id}', [\App\Modules\Users\Controllers\ParentController::class, 'destroy'])->whereNumber('id')->name('parents.destroy');
        Route::get('parents/{id}/students', [\App\Modules\Users\Controllers\ParentController::class, 'students'])->whereNumber('id')->name('parents.students');
        Route::post('parents/{id}/students', [\App\Modules\Users\Controllers\ParentController::class, 'syncStudents'])->whereNumber('id')->name('parents.students.sync');

        Route::get('teachers', [\App\Modules\Users\Controllers\TeacherController::class, 'index'])->name('teachers.index');
        Route::get('teachers/workloads', [\App\Modules\Users\Controllers\TeacherController::class, 'workloads'])->name('teachers.workloads');
        Route::get('teachers/import', [\App\Modules\Users\Controllers\TeacherController::class, 'importForm'])->name('teachers.import');
        Route::get('teachers/import/template', [\App\Modules\Users\Controllers\TeacherController::class, 'importTemplate'])->name('teachers.import.template');
        Route::get('teachers/export', [\App\Modules\Users\Controllers\TeacherController::class, 'export'])->name('teachers.export');
        Route::post('teachers/import', [\App\Modules\Users\Controllers\TeacherController::class, 'import'])->name('teachers.import.store');
        Route::post('teachers/import/update', [\App\Modules\Users\Controllers\TeacherController::class, 'importUpdate'])->name('teachers.import.update');
        Route::post('teachers/import/photos', [\App\Modules\Users\Controllers\TeacherController::class, 'importPhotos'])->name('teachers.import.photos');
        Route::get('teachers/create', [\App\Modules\Users\Controllers\TeacherController::class, 'create'])->name('teachers.create');
        Route::post('teachers', [\App\Modules\Users\Controllers\TeacherController::class, 'store'])->name('teachers.store');
        Route::get('teachers/{id}/permissions', [\App\Modules\Users\Controllers\TeacherController::class, 'permissions'])->whereNumber('id')->name('teachers.permissions');
        Route::post('teachers/{id}/permissions', [\App\Modules\Users\Controllers\TeacherController::class, 'storePermission'])->whereNumber('id')->name('teachers.permissions.store');
        Route::delete('teachers/{id}/permissions/{assignmentId}', [\App\Modules\Users\Controllers\TeacherController::class, 'destroyPermission'])->whereNumber('id')->whereNumber('assignmentId')->name('teachers.permissions.destroy');
        Route::get('teachers/{id}', [\App\Modules\Users\Controllers\TeacherController::class, 'show'])->whereNumber('id')->name('teachers.show');
        Route::get('teachers/{id}/edit', [\App\Modules\Users\Controllers\TeacherController::class, 'edit'])->whereNumber('id')->name('teachers.edit');
        Route::put('teachers/{id}', [\App\Modules\Users\Controllers\TeacherController::class, 'update'])->whereNumber('id')->name('teachers.update');
        Route::delete('teachers/{id}', [\App\Modules\Users\Controllers\TeacherController::class, 'destroy'])->whereNumber('id')->name('teachers.destroy');

        Route::get('admins', [\App\Modules\Users\Controllers\AdminController::class, 'index'])->name('admins.index');
        Route::get('admins/create', [\App\Modules\Users\Controllers\AdminController::class, 'create'])->name('admins.create');
        Route::post('admins', [\App\Modules\Users\Controllers\AdminController::class, 'store'])->name('admins.store');
        Route::get('admins/{id}/edit', [\App\Modules\Users\Controllers\AdminController::class, 'edit'])->name('admins.edit');
        Route::get('admins/{id}', [\App\Modules\Users\Controllers\AdminController::class, 'show'])->name('admins.show')->whereNumber('id');
        Route::put('admins/{id}', [\App\Modules\Users\Controllers\AdminController::class, 'update'])->name('admins.update');
        Route::delete('admins/{id}', [\App\Modules\Users\Controllers\AdminController::class, 'destroy'])->name('admins.destroy');
        Route::get('admins/{id}/supervisees', [\App\Modules\Users\Controllers\AdminController::class, 'supervisees'])->name('admins.supervisees');
        Route::post('admins/{id}/supervisees', [\App\Modules\Users\Controllers\AdminController::class, 'syncSupervisees'])->name('admins.supervisees.sync');

        // Job Titles management — protected by job_titles.* permissions.
        // canDo() default-allow rule means users with unconfigured job titles still have access.
        Route::middleware(['permission:job_titles.view'])->group(function () {
            Route::get('job-titles', [\App\Modules\Users\Controllers\JobTitleController::class, 'index'])->name('job-titles.index');
        });
        Route::middleware(['permission:job_titles.create'])->group(function () {
            Route::post('job-titles', [\App\Modules\Users\Controllers\JobTitleController::class, 'store'])->name('job-titles.store');
        });
        Route::middleware(['permission:job_titles.edit'])->group(function () {
            Route::put('job-titles/{jobTitle}', [\App\Modules\Users\Controllers\JobTitleController::class, 'update'])->name('job-titles.update');
        });
        Route::middleware(['permission:job_titles.delete'])->group(function () {
            Route::delete('job-titles/{jobTitle}', [\App\Modules\Users\Controllers\JobTitleController::class, 'destroy'])->name('job-titles.destroy');
        });
        // Permission matrix routes — backend-protected
        Route::middleware(['permission:job_titles.manage_permissions'])->group(function () {
            Route::get('job-titles/{jobTitle}/permissions',
                [\App\Modules\Users\Controllers\JobTitlePermissionsController::class, 'index'])
                ->name('job-titles.permissions.index');
            Route::post('job-titles/{jobTitle}/permissions',
                [\App\Modules\Users\Controllers\JobTitlePermissionsController::class, 'update'])
                ->name('job-titles.permissions.update');
            Route::post('job-titles/{jobTitle}/permissions/copy',
                [\App\Modules\Users\Controllers\JobTitlePermissionsController::class, 'copy'])
                ->name('job-titles.permissions.copy');
        });

        Route::get('cards', [\App\Modules\Users\Controllers\UserCardController::class, 'index'])->name('cards.index');
        Route::post('cards/generate', [\App\Modules\Users\Controllers\UserCardController::class, 'generate'])->name('cards.generate');
        Route::post('cards/{id}/regenerate-password', [\App\Modules\Users\Controllers\UserCardController::class, 'regenerate'])
            ->whereNumber('id')
            ->name('cards.regenerate');

        Route::get('{id}/impersonate', [\App\Modules\Users\Controllers\ImpersonateController::class, 'confirm'])->whereNumber('id')->name('impersonate.confirm');
        Route::post('{id}/impersonate', [\App\Modules\Users\Controllers\ImpersonateController::class, 'start'])->name('impersonate.start');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::post('admin/users/impersonate/stop', [\App\Modules\Users\Controllers\ImpersonateController::class, 'stop'])->name('admin.users.impersonate.stop');

    // === My education policies (any signed-in user) — card #105 ===
    Route::get('my/policies', [\App\Modules\Policies\Controllers\MyPolicyController::class, 'index'])->name('policies.my.index');
    Route::get('my/policies/{id}', [\App\Modules\Policies\Controllers\MyPolicyController::class, 'show'])->whereNumber('id')->name('policies.my.show');

    // === Parent Libraries page (ولي الأمر) — card #182 ===
    Route::get('my/libraries', [\App\Modules\Libraries\Controllers\ParentLibraryController::class, 'index'])->name('my.libraries.index');

    // === Parent canteen controls (ولي الأمر) — card #116 / Task 20 part 4b ===
    Route::get('my/canteen', [\App\Modules\Canteen\Controllers\MyCanteenController::class, 'index'])->name('my.canteen.index');
    Route::put('my/canteen/{student}/limit', [\App\Modules\Canteen\Controllers\MyCanteenController::class, 'updateLimit'])->whereNumber('student')->name('my.canteen.limit');
    Route::get('my/canteen/{student}/products', [\App\Modules\Canteen\Controllers\MyCanteenController::class, 'products'])->whereNumber('student')->name('my.canteen.products');
    Route::post('my/canteen/{student}/products/{product}/toggle', [\App\Modules\Canteen\Controllers\MyCanteenController::class, 'toggleBlock'])->whereNumber('student')->whereNumber('product')->name('my.canteen.products.toggle');
    Route::get('my/canteen/{student}/orders', [\App\Modules\Canteen\Controllers\MyCanteenController::class, 'orders'])->whereNumber('student')->name('my.canteen.orders');
});

// Sprint 4 — Subjects Module
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('subjects/credit-hours', [\App\Modules\Subjects\Controllers\SubjectController::class, 'creditHours'])->name('subjects.credit-hours');
    Route::patch('subjects/credit-hours', [\App\Modules\Subjects\Controllers\SubjectController::class, 'saveCreditHours'])->name('subjects.credit-hours.save');

    // === Education policies (سياسات التعليم) — cards #104 + #105 ===
    Route::get('policies', [\App\Modules\Policies\Controllers\PolicyController::class, 'index'])->name('policies.index');
    Route::get('policies/create', [\App\Modules\Policies\Controllers\PolicyController::class, 'create'])->name('policies.create');
    Route::post('policies', [\App\Modules\Policies\Controllers\PolicyController::class, 'store'])->name('policies.store');
    Route::get('policies/{id}/edit', [\App\Modules\Policies\Controllers\PolicyController::class, 'edit'])->whereNumber('id')->name('policies.edit');
    Route::put('policies/{id}', [\App\Modules\Policies\Controllers\PolicyController::class, 'update'])->whereNumber('id')->name('policies.update');
    Route::delete('policies/{id}', [\App\Modules\Policies\Controllers\PolicyController::class, 'destroy'])->whereNumber('id')->name('policies.destroy');
    Route::get('policies/{id}/acknowledgements', [\App\Modules\Policies\Controllers\PolicyController::class, 'acknowledgements'])->whereNumber('id')->name('policies.acknowledgements');

    // === Behaviour: behaviour groups (السلوك) — card #114 / Task 18 ===
    Route::get('behavior/groups', [\App\Modules\Behavior\Controllers\BehaviorGroupController::class, 'index'])->name('behavior.groups.index');
    Route::get('behavior/groups/create', [\App\Modules\Behavior\Controllers\BehaviorGroupController::class, 'create'])->name('behavior.groups.create');
    Route::post('behavior/groups', [\App\Modules\Behavior\Controllers\BehaviorGroupController::class, 'store'])->name('behavior.groups.store');
    Route::get('behavior/groups/{id}/edit', [\App\Modules\Behavior\Controllers\BehaviorGroupController::class, 'edit'])->whereNumber('id')->name('behavior.groups.edit');
    Route::put('behavior/groups/{id}', [\App\Modules\Behavior\Controllers\BehaviorGroupController::class, 'update'])->whereNumber('id')->name('behavior.groups.update');
    Route::post('behavior/groups/{id}/toggle', [\App\Modules\Behavior\Controllers\BehaviorGroupController::class, 'toggle'])->whereNumber('id')->name('behavior.groups.toggle');
    Route::delete('behavior/groups/{id}', [\App\Modules\Behavior\Controllers\BehaviorGroupController::class, 'destroy'])->whereNumber('id')->name('behavior.groups.destroy');

    // === Behaviour: behaviours (السلوكيات) — card #115 / Task 19 ===
    Route::get('behavior/behaviors', [\App\Modules\Behavior\Controllers\BehaviorController::class, 'index'])->name('behavior.behaviors.index');
    Route::get('behavior/behaviors/create', [\App\Modules\Behavior\Controllers\BehaviorController::class, 'create'])->name('behavior.behaviors.create');
    Route::post('behavior/behaviors', [\App\Modules\Behavior\Controllers\BehaviorController::class, 'store'])->name('behavior.behaviors.store');
    Route::get('behavior/behaviors/{id}/edit', [\App\Modules\Behavior\Controllers\BehaviorController::class, 'edit'])->whereNumber('id')->name('behavior.behaviors.edit');
    Route::put('behavior/behaviors/{id}', [\App\Modules\Behavior\Controllers\BehaviorController::class, 'update'])->whereNumber('id')->name('behavior.behaviors.update');
    Route::post('behavior/behaviors/{id}/toggle', [\App\Modules\Behavior\Controllers\BehaviorController::class, 'toggle'])->whereNumber('id')->name('behavior.behaviors.toggle');
    Route::delete('behavior/behaviors/{id}', [\App\Modules\Behavior\Controllers\BehaviorController::class, 'destroy'])->whereNumber('id')->name('behavior.behaviors.destroy');

    // === Behaviour: actions (الإجراءات) — card #115 / Task 19 ===
    Route::get('behavior/actions', [\App\Modules\Behavior\Controllers\BehaviorActionController::class, 'index'])->name('behavior.actions.index');
    Route::get('behavior/actions/create', [\App\Modules\Behavior\Controllers\BehaviorActionController::class, 'create'])->name('behavior.actions.create');
    Route::post('behavior/actions', [\App\Modules\Behavior\Controllers\BehaviorActionController::class, 'store'])->name('behavior.actions.store');
    Route::get('behavior/actions/{id}/edit', [\App\Modules\Behavior\Controllers\BehaviorActionController::class, 'edit'])->whereNumber('id')->name('behavior.actions.edit');
    Route::put('behavior/actions/{id}', [\App\Modules\Behavior\Controllers\BehaviorActionController::class, 'update'])->whereNumber('id')->name('behavior.actions.update');
    Route::post('behavior/actions/{id}/toggle', [\App\Modules\Behavior\Controllers\BehaviorActionController::class, 'toggle'])->whereNumber('id')->name('behavior.actions.toggle');
    Route::delete('behavior/actions/{id}', [\App\Modules\Behavior\Controllers\BehaviorActionController::class, 'destroy'])->whereNumber('id')->name('behavior.actions.destroy');

    // === Behaviour: records / apply behaviour (تسجيل السلوك) — card #115 / Task 19 ===
    // NOTE (#192): the view/log records routes (index/create/actions/store) are
    // registered in the teacher-inclusive admin group below so teachers can record
    // behaviour for their school. Deleting a record stays admin-only here.
    Route::delete('behavior/records/{id}', [\App\Modules\Behavior\Controllers\BehaviorRecordController::class, 'destroy'])->whereNumber('id')->name('behavior.records.destroy');

    // === E-canteen: canteens management (المقصف الإلكتروني) — card #116 / Task 20 ===
    Route::get('canteens', [\App\Modules\Canteen\Controllers\CanteenController::class, 'index'])->name('canteens.index');
    Route::get('canteens/create', [\App\Modules\Canteen\Controllers\CanteenController::class, 'create'])->name('canteens.create');
    Route::post('canteens', [\App\Modules\Canteen\Controllers\CanteenController::class, 'store'])->name('canteens.store');
    Route::get('canteens/{id}/edit', [\App\Modules\Canteen\Controllers\CanteenController::class, 'edit'])->whereNumber('id')->name('canteens.edit');
    Route::put('canteens/{id}', [\App\Modules\Canteen\Controllers\CanteenController::class, 'update'])->whereNumber('id')->name('canteens.update');
    Route::delete('canteens/{id}', [\App\Modules\Canteen\Controllers\CanteenController::class, 'destroy'])->whereNumber('id')->name('canteens.destroy');
    Route::get('canteens/{id}/manager', [\App\Modules\Canteen\Controllers\CanteenController::class, 'managerForm'])->whereNumber('id')->name('canteens.manager');
    Route::put('canteens/{id}/manager', [\App\Modules\Canteen\Controllers\CanteenController::class, 'assignManager'])->whereNumber('id')->name('canteens.manager.assign');
    Route::post('canteens/{id}/activate', [\App\Modules\Canteen\Controllers\CanteenController::class, 'activate'])->whereNumber('id')->name('canteens.activate');
    Route::post('canteens/{id}/deactivate', [\App\Modules\Canteen\Controllers\CanteenController::class, 'deactivate'])->whereNumber('id')->name('canteens.deactivate');

    // === E-canteen: categories — card #116 / Task 20 part 2 ===
    Route::get('canteens/{canteen}/categories', [\App\Modules\Canteen\Controllers\CanteenCategoryController::class, 'index'])->whereNumber('canteen')->name('canteens.categories.index');
    Route::post('canteens/{canteen}/categories', [\App\Modules\Canteen\Controllers\CanteenCategoryController::class, 'store'])->whereNumber('canteen')->name('canteens.categories.store');
    Route::put('canteens/{canteen}/categories/{id}', [\App\Modules\Canteen\Controllers\CanteenCategoryController::class, 'update'])->whereNumber('canteen')->whereNumber('id')->name('canteens.categories.update');
    Route::post('canteens/{canteen}/categories/{id}/toggle', [\App\Modules\Canteen\Controllers\CanteenCategoryController::class, 'toggle'])->whereNumber('canteen')->whereNumber('id')->name('canteens.categories.toggle');
    Route::delete('canteens/{canteen}/categories/{id}', [\App\Modules\Canteen\Controllers\CanteenCategoryController::class, 'destroy'])->whereNumber('canteen')->whereNumber('id')->name('canteens.categories.destroy');

    // === E-canteen: products — card #116 / Task 20 part 2 ===
    Route::get('canteens/{canteen}/products', [\App\Modules\Canteen\Controllers\CanteenProductController::class, 'index'])->whereNumber('canteen')->name('canteens.products.index');
    Route::get('canteens/{canteen}/products/create', [\App\Modules\Canteen\Controllers\CanteenProductController::class, 'create'])->whereNumber('canteen')->name('canteens.products.create');
    Route::post('canteens/{canteen}/products', [\App\Modules\Canteen\Controllers\CanteenProductController::class, 'store'])->whereNumber('canteen')->name('canteens.products.store');
    Route::get('canteens/{canteen}/products/{id}/edit', [\App\Modules\Canteen\Controllers\CanteenProductController::class, 'edit'])->whereNumber('canteen')->whereNumber('id')->name('canteens.products.edit');
    Route::put('canteens/{canteen}/products/{id}', [\App\Modules\Canteen\Controllers\CanteenProductController::class, 'update'])->whereNumber('canteen')->whereNumber('id')->name('canteens.products.update');
    Route::post('canteens/{canteen}/products/{id}/toggle', [\App\Modules\Canteen\Controllers\CanteenProductController::class, 'toggle'])->whereNumber('canteen')->whereNumber('id')->name('canteens.products.toggle');
    Route::delete('canteens/{canteen}/products/{id}', [\App\Modules\Canteen\Controllers\CanteenProductController::class, 'destroy'])->whereNumber('canteen')->whereNumber('id')->name('canteens.products.destroy');

    // === E-canteen: student balances + ledger — card #116 / Task 20 part 3 ===
    Route::get('canteen-balances', [\App\Modules\Canteen\Controllers\CanteenBalanceController::class, 'index'])->name('canteen-balances.index');
    Route::get('canteen-balances/{student}/edit', [\App\Modules\Canteen\Controllers\CanteenBalanceController::class, 'edit'])->whereNumber('student')->name('canteen-balances.edit');
    Route::put('canteen-balances/{student}', [\App\Modules\Canteen\Controllers\CanteenBalanceController::class, 'update'])->whereNumber('student')->name('canteen-balances.update');
    Route::get('canteen-balances/{student}/history', [\App\Modules\Canteen\Controllers\CanteenBalanceController::class, 'history'])->whereNumber('student')->name('canteen-balances.history');

    // === E-canteen: orders lifecycle — card #116 / Task 20 part 4 ===
    Route::get('canteen-orders', [\App\Modules\Canteen\Controllers\CanteenOrderController::class, 'index'])->name('canteen-orders.index');
    Route::get('canteen-orders/create', [\App\Modules\Canteen\Controllers\CanteenOrderController::class, 'create'])->name('canteen-orders.create');
    Route::post('canteen-orders', [\App\Modules\Canteen\Controllers\CanteenOrderController::class, 'store'])->name('canteen-orders.store');
    Route::get('canteen-orders/{id}', [\App\Modules\Canteen\Controllers\CanteenOrderController::class, 'show'])->whereNumber('id')->name('canteen-orders.show');
    Route::put('canteen-orders/{id}/status', [\App\Modules\Canteen\Controllers\CanteenOrderController::class, 'updateStatus'])->whereNumber('id')->name('canteen-orders.status');

    // === Subject tracks (شعب المواد) — card 61 ===
    Route::get('subjects/tracks',              [\App\Modules\Subjects\Controllers\SubjectTrackController::class, 'index'])->name('subject-tracks.index');
    Route::get('subjects/tracks/create',       [\App\Modules\Subjects\Controllers\SubjectTrackController::class, 'create'])->name('subject-tracks.create');
    Route::post('subjects/tracks',             [\App\Modules\Subjects\Controllers\SubjectTrackController::class, 'store'])->name('subject-tracks.store');
    Route::get('subjects/tracks/{id}/edit',    [\App\Modules\Subjects\Controllers\SubjectTrackController::class, 'edit'])->name('subject-tracks.edit');
    Route::put('subjects/tracks/{id}',         [\App\Modules\Subjects\Controllers\SubjectTrackController::class, 'update'])->name('subject-tracks.update');
    Route::delete('subjects/tracks/{id}',      [\App\Modules\Subjects\Controllers\SubjectTrackController::class, 'destroy'])->name('subject-tracks.destroy');

    Route::get('subjects/templates', [\App\Modules\Subjects\Controllers\SubjectController::class, 'templatesIndex'])->name('subjects.templates.index');
    Route::post('subjects/templates', [\App\Modules\Subjects\Controllers\SubjectController::class, 'templatesAttach'])->name('subjects.templates.attach');

    // === Bulk import from Excel/CSV (platform template) ===
    Route::get('subjects/import/template', [\App\Modules\Subjects\Controllers\SubjectController::class, 'importTemplate'])->name('subjects.import.template');
    Route::post('subjects/import', [\App\Modules\Subjects\Controllers\SubjectController::class, 'importStore'])->name('subjects.import.store');

    // Subjects — write routes (create/edit/delete) — admin-only
    Route::get('subjects/create', [\App\Modules\Subjects\Controllers\SubjectController::class, 'create'])->name('subjects.create');
    Route::post('subjects', [\App\Modules\Subjects\Controllers\SubjectController::class, 'store'])->name('subjects.store');
    Route::get('subjects/{id}/edit', [\App\Modules\Subjects\Controllers\SubjectController::class, 'edit'])->name('subjects.edit');
    Route::put('subjects/{id}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('subjects/{id}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'destroy'])->name('subjects.destroy');

    // Subject lesson-tree write routes (unit/lesson create/delete) — admin-only
    Route::post('subjects/{id}/units', [\App\Modules\Subjects\Controllers\SubjectController::class, 'storeUnit'])->name('subjects.units.store');
    Route::delete('subjects/{id}/units/{unitId}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'destroyUnit'])->name('subjects.units.destroy');
    Route::post('subjects/{id}/units/{unitId}/lessons', [\App\Modules\Subjects\Controllers\SubjectController::class, 'storeLesson'])->name('subjects.lessons.store');
    Route::delete('subjects/{id}/units/{unitId}/lessons/{lessonId}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'destroyLesson'])->name('subjects.lessons.destroy');

    // Subject domains (المجالات) — write routes — admin-only
    Route::post('subjects/{id}/domains', [\App\Modules\Subjects\Controllers\SubjectController::class, 'storeDomain'])->name('subjects.domains.store');
    Route::put('subjects/{id}/domains/{domainId}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'updateDomain'])->name('subjects.domains.update');
    Route::delete('subjects/{id}/domains/{domainId}', [\App\Modules\Subjects\Controllers\SubjectController::class, 'destroyDomain'])->name('subjects.domains.destroy');

    // Question Banks — write routes (create/edit/delete/approve/promote/import) — admin-only
    Route::get('question-banks/batch/create', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'createBatch'])->name('question-banks.batch.create');
    Route::post('question-banks/batch/store', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'storeBatch'])->name('question-banks.batch.store');
    Route::post('question-banks/library/{id}/clone', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'clone'])->name('question-banks.library.clone');
    Route::get('question-banks/create', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'create'])->name('question-banks.create');
    Route::post('question-banks', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'store'])->name('question-banks.store');
    Route::get('question-banks/{id}/edit', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'edit'])->name('question-banks.edit');
    Route::put('question-banks/{id}', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'update'])->name('question-banks.update');
    Route::delete('question-banks/{id}', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'destroy'])->name('question-banks.destroy');
    Route::get('question-banks/{bankId}/questions/create', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'create'])->name('question-banks.questions.create');
    Route::post('question-banks/{bankId}/questions', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'store'])->name('question-banks.questions.store');
    Route::get('question-banks/{bankId}/questions/{questionId}/edit', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'edit'])->name('question-banks.questions.edit');
    Route::put('question-banks/{bankId}/questions/{questionId}', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'update'])->name('question-banks.questions.update');
    Route::post('question-banks/{bankId}/questions/{questionId}/duplicate', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'duplicate'])->name('question-banks.questions.duplicate');
    Route::delete('question-banks/{bankId}/questions/{questionId}', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'destroy'])->name('question-banks.questions.destroy');
    // Question Bank — Excel import (card #214) — must be before any {questionId} wildcard
    Route::get('question-banks/{bankId}/questions/import', [\App\Modules\QuestionBanks\Controllers\QuestionImportController::class, 'form'])->name('question-banks.questions.import.form');
    Route::get('question-banks/{bankId}/questions/import/template', [\App\Modules\QuestionBanks\Controllers\QuestionImportController::class, 'template'])->name('question-banks.questions.import.template');
    Route::post('question-banks/{bankId}/questions/import/preview', [\App\Modules\QuestionBanks\Controllers\QuestionImportController::class, 'preview'])->name('question-banks.questions.import.preview');
    Route::post('question-banks/{bankId}/questions/import/{batchId}/execute', [\App\Modules\QuestionBanks\Controllers\QuestionImportController::class, 'execute'])->name('question-banks.questions.import.execute');
    Route::get('question-banks/{bankId}/questions/import/{batchId}/errors.csv', [\App\Modules\QuestionBanks\Controllers\QuestionImportController::class, 'errorsReport'])->name('question-banks.questions.import.errors');
    // Question Bank — curation actions (T3/T5)
    Route::post('question-banks/{id}/approve', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'approve'])->name('question-banks.approve');
    Route::post('question-banks/{id}/promote', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'promote'])->name('question-banks.promote');
    Route::post('question-banks/{id}/copy-to-my-school', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'copyToMySchool'])->name('question-banks.copy-to-my-school');

    // Retired duplicate "إدارة المواد / class-periods" tab — consolidated into الحصص (/admin/lessons).
    // Keep the old URLs alive as redirects so bookmarks/links don't 404.
    Route::get('class-periods/time-slots', fn () => redirect()->route('admin.lessons.time-slots.index'))->name('class-periods.time-slots.index');
    Route::get('class-periods/advanced', fn () => redirect()->route('admin.lessons.advanced'))->name('class-periods.advanced');
    Route::get('class-periods/create', fn () => redirect()->route('admin.lessons.create'));
    Route::get('class-periods', fn () => redirect()->route('admin.lessons.index'))->name('class-periods.index');

    // School Schedule (Sprint 4 phase 4 — read-only view + PDF)
    Route::get('school-schedule', [\App\Modules\SchoolSchedule\Controllers\SchoolScheduleController::class, 'index'])->name('school-schedule.index');
    Route::get('school-schedule/pdf', [\App\Modules\SchoolSchedule\Controllers\SchoolScheduleController::class, 'pdf'])->name('school-schedule.pdf');

    // Libraries module (public + private + virtual labs)
    Route::prefix('libraries')->name('libraries.')->group(function () {
        // Public library — write routes (create/edit/delete/rate/comment) — admin-only
        Route::get('public/create', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'create'])->name('public.create');
        Route::post('public', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'store'])->name('public.store');
        Route::get('public/{id}/edit', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'edit'])->whereNumber('id')->name('public.edit');
        Route::put('public/{id}', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'update'])->whereNumber('id')->name('public.update');
        Route::delete('public/{id}', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'destroy'])->whereNumber('id')->name('public.destroy');
        // Ratings + comments (card #97) — write routes — admin-only
        Route::post('public/{id}/rate', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'rate'])->whereNumber('id')->name('public.rate');
        Route::post('public/{id}/react', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'react'])->whereNumber('id')->name('public.react');
        Route::post('public/{id}/comments', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'storeComment'])->whereNumber('id')->name('public.comments.store');
        Route::delete('public/{id}/comments/{commentId}', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'destroyComment'])->whereNumber('id')->whereNumber('commentId')->name('public.comments.destroy');

        // Private libraries — write routes (create/edit/delete/items) — admin-only
        Route::get('private/create', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'create'])->name('private.create');
        Route::get('private/class-members', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'classMembers'])->name('private.class-members');
        Route::post('private', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'store'])->name('private.store');
        Route::get('private/{id}/edit', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'edit'])->name('private.edit');
        Route::put('private/{id}', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'update'])->name('private.update');
        Route::delete('private/{id}', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'destroy'])->name('private.destroy');
        Route::post('private/{id}/items', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'storeItem'])->name('private.items.store');
        Route::delete('private/{id}/items/{itemId}', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'destroyItem'])->name('private.items.destroy');

        // Virtual labs — WRITE/manage stay admin-only; browse (index/show) is
        // teacher-inclusive in the read group below (card #290).
        Route::get('labs/manage', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'manage'])->name('labs.manage');
        Route::get('labs/create', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'create'])->name('labs.create');
        Route::post('labs', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'store'])->name('labs.store');
        Route::get('labs/{id}/edit', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'edit'])->name('labs.edit');
        Route::put('labs/{id}', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'update'])->name('labs.update');
        Route::delete('labs/{id}', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'destroy'])->name('labs.destroy');
    });
});

// Admin Exams & Grades Routes
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin')->name('admin.')->group(function () {
    // === Lessons (الحصص) — card 64 + "تعديلات الحصص" ===
    Route::get('lessons', [\App\Modules\Lessons\Controllers\LessonController::class, 'index'])->name('lessons.index');
    Route::get('lessons/create', [\App\Modules\Lessons\Controllers\LessonController::class, 'create'])->name('lessons.create');
    Route::post('lessons', [\App\Modules\Lessons\Controllers\LessonController::class, 'store'])->name('lessons.store');

    // Time slots (إدارة الفترات الزمنية) — migrated from the retired ClassPeriods module
    Route::get('lessons/time-slots', [\App\Modules\Lessons\Controllers\LessonTimeSlotController::class, 'index'])->name('lessons.time-slots.index');
    Route::post('lessons/time-slots', [\App\Modules\Lessons\Controllers\LessonTimeSlotController::class, 'store'])->name('lessons.time-slots.store');
    Route::delete('lessons/time-slots/{id}', [\App\Modules\Lessons\Controllers\LessonTimeSlotController::class, 'destroy'])->name('lessons.time-slots.destroy');

    // Advanced board (الجدول المتقدم)
    Route::get('lessons/advanced', [\App\Modules\Lessons\Controllers\LessonScheduleBoardController::class, 'index'])->name('lessons.advanced');

    // === خدمات أخرى — card #91 ===
    Route::get('lessons/conflicts', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'conflicts'])->name('lessons.conflicts');
    Route::post('lessons/reassign-students', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'reassignStudents'])->name('lessons.reassign-students');
    Route::delete('lessons/schedule', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'destroySchedule'])->name('lessons.schedule.destroy');
    Route::delete('lessons/time-slots-all', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'destroyTimeSlots'])->name('lessons.time-slots.destroy-all');
    Route::get('lessons/export/course-students', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'exportCourseStudents'])->name('lessons.export.course-students');
    Route::get('lessons/import', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'importForm'])->name('lessons.import.form');
    Route::get('lessons/import/template', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'importTemplate'])->name('lessons.import.template');
    Route::post('lessons/import', [\App\Modules\Lessons\Controllers\LessonServicesController::class, 'import'])->name('lessons.import.run');

    // Students inside a lesson (إدارة الطلاب داخل الحصة)
    Route::get('lessons/{id}/students', [\App\Modules\Lessons\Controllers\LessonStudentController::class, 'index'])->name('lessons.students.index');
    Route::put('lessons/{id}/students', [\App\Modules\Lessons\Controllers\LessonStudentController::class, 'update'])->name('lessons.students.update');

    Route::get('lessons/{id}/edit', [\App\Modules\Lessons\Controllers\LessonController::class, 'edit'])->name('lessons.edit');
    Route::put('lessons/{id}', [\App\Modules\Lessons\Controllers\LessonController::class, 'update'])->name('lessons.update');
    Route::delete('lessons/{id}', [\App\Modules\Lessons\Controllers\LessonController::class, 'destroy'])->name('lessons.destroy');

    // Exams Management
    Route::resource('exams', \App\Http\Controllers\Admin\ExamController::class);
    Route::post('exams/{exam}/publish', [\App\Http\Controllers\Admin\ExamController::class, 'publish'])->name('exams.publish');
    Route::post('exams/{exam}/unpublish', [\App\Http\Controllers\Admin\ExamController::class, 'unpublish'])->name('exams.unpublish');
    Route::post('exams/{exam}/activate', [\App\Http\Controllers\Admin\ExamController::class, 'activate'])->name('exams.activate');
    Route::post('exams/{exam}/complete', [\App\Http\Controllers\Admin\ExamController::class, 'complete'])->name('exams.complete');
    Route::get('exams/{exam}/results', [\App\Http\Controllers\Admin\ExamController::class, 'results'])->name('exams.results');
    // === Anti-cheat (ac) — Trello #229: re-open a student's auto-locked attempt ===
    Route::post('exams/{exam}/attempts/{studentExam}/reopen', [\App\Http\Controllers\Admin\ExamController::class, 'reopenAttempt'])->name('exams.attempts.reopen');

    // Exam Questions Management
    Route::get('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'index'])->name('exams.questions.index');
    Route::get('exams/{exam}/questions/create', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'create'])->name('exams.questions.create');
    Route::post('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'store'])->name('exams.questions.store');
    Route::get('exams/{exam}/questions/{question}/edit', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'edit'])->name('exams.questions.edit');
    Route::put('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'update'])->name('exams.questions.update');
    Route::delete('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');
    Route::post('exams/{exam}/questions/reorder', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'reorder'])->name('exams.questions.reorder');
    Route::post('exams/{exam}/questions/{question}/duplicate', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'duplicate'])->name('exams.questions.duplicate');
    // #217 — Add questions from bank
    Route::get('exams/{exam}/questions/from-bank', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'bankPicker'])->name('exams.questions.bank-picker');
    Route::post('exams/{exam}/questions/from-bank', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'addFromBank'])->name('exams.questions.add-from-bank');

    // Evaluation Forms (Sprint 8 — نماذج التقييم)
    Route::get('evaluations', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'index'])->name('evaluations.index');
    Route::get('evaluations/create', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'create'])->name('evaluations.create');
    Route::post('evaluations', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'store'])->name('evaluations.store');
    Route::get('evaluations/{id}/edit', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'edit'])->name('evaluations.edit');
    Route::put('evaluations/{id}', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'update'])->name('evaluations.update');
    Route::delete('evaluations/{id}', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'destroy'])->name('evaluations.destroy');

    // Evaluation Items (Sprint 8 Task 4 — عناصر النموذج)
    Route::get('evaluations/{form}/items', [\App\Modules\Evaluation\Controllers\EvaluationItemController::class, 'index'])->name('evaluations.items.index');
    Route::post('evaluations/{form}/items', [\App\Modules\Evaluation\Controllers\EvaluationItemController::class, 'store'])->name('evaluations.items.store');
    Route::put('evaluations/{form}/items/{item}', [\App\Modules\Evaluation\Controllers\EvaluationItemController::class, 'update'])->name('evaluations.items.update');
    Route::post('evaluations/{form}/items/{item}/toggle', [\App\Modules\Evaluation\Controllers\EvaluationItemController::class, 'toggle'])->name('evaluations.items.toggle');
    Route::delete('evaluations/{form}/items/{item}', [\App\Modules\Evaluation\Controllers\EvaluationItemController::class, 'destroy'])->name('evaluations.items.destroy');
    Route::post('evaluations/{form}/items/reorder', [\App\Modules\Evaluation\Controllers\EvaluationItemController::class, 'reorder'])->name('evaluations.items.reorder');

    // Evaluation Indicators (Sprint 8 Task 5 — مؤشرات العنصر)
    Route::get('evaluations/{form}/items/{item}/indicators', [\App\Modules\Evaluation\Controllers\EvaluationIndicatorController::class, 'index'])->name('evaluations.indicators.index');
    Route::post('evaluations/{form}/items/{item}/indicators', [\App\Modules\Evaluation\Controllers\EvaluationIndicatorController::class, 'store'])->name('evaluations.indicators.store');
    Route::put('evaluations/{form}/items/{item}/indicators/{indicator}', [\App\Modules\Evaluation\Controllers\EvaluationIndicatorController::class, 'update'])->name('evaluations.indicators.update');
    Route::post('evaluations/{form}/items/{item}/indicators/{indicator}/toggle', [\App\Modules\Evaluation\Controllers\EvaluationIndicatorController::class, 'toggle'])->name('evaluations.indicators.toggle');
    Route::delete('evaluations/{form}/items/{item}/indicators/{indicator}', [\App\Modules\Evaluation\Controllers\EvaluationIndicatorController::class, 'destroy'])->name('evaluations.indicators.destroy');
    Route::post('evaluations/{form}/items/{item}/indicators/reorder', [\App\Modules\Evaluation\Controllers\EvaluationIndicatorController::class, 'reorder'])->name('evaluations.indicators.reorder');

    // Evaluation Targets (Sprint 8 Task 6 — تحديد المستهدفين)
    Route::get('evaluations/{form}/targets', [\App\Modules\Evaluation\Controllers\EvaluationTargetController::class, 'index'])->name('evaluations.targets.index');
    Route::post('evaluations/{form}/targets', [\App\Modules\Evaluation\Controllers\EvaluationTargetController::class, 'store'])->name('evaluations.targets.store');
    Route::post('evaluations/{form}/targets/summary', [\App\Modules\Evaluation\Controllers\EvaluationTargetController::class, 'summary'])->name('evaluations.targets.summary');
    Route::delete('evaluations/{form}/targets/{target}', [\App\Modules\Evaluation\Controllers\EvaluationTargetController::class, 'destroy'])->name('evaluations.targets.destroy');

    // Evaluation Evaluators (Sprint 8 Task 7 — تحديد المقيّمين)
    Route::get('evaluations/{form}/evaluators', [\App\Modules\Evaluation\Controllers\EvaluationAssignmentController::class, 'index'])->name('evaluations.evaluators.index');
    Route::post('evaluations/{form}/evaluators', [\App\Modules\Evaluation\Controllers\EvaluationAssignmentController::class, 'store'])->name('evaluations.evaluators.store');
    Route::put('evaluations/{form}/evaluators/{assignment}', [\App\Modules\Evaluation\Controllers\EvaluationAssignmentController::class, 'update'])->name('evaluations.evaluators.update');
    Route::delete('evaluations/{form}/evaluators/{assignment}', [\App\Modules\Evaluation\Controllers\EvaluationAssignmentController::class, 'destroy'])->name('evaluations.evaluators.destroy');

    // Evaluation Publish / Close / Archive (Sprint 8 Task 8 — النشر)
    Route::get('evaluations/{id}/publish', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'publishConfirm'])->name('evaluations.publish.confirm');
    Route::post('evaluations/{id}/publish', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'publish'])->name('evaluations.publish');
    Route::post('evaluations/{id}/close', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'close'])->name('evaluations.close');
    Route::post('evaluations/{id}/archive', [\App\Modules\Evaluation\Controllers\EvaluationFormController::class, 'archive'])->name('evaluations.archive');

    // NOTE: the evaluator/subject-facing routes (Tasks 9-12: my-evaluations, subject picker,
    // execution, evidence) live in a SEPARATE teacher-inclusive admin group below, so teachers
    // can act as evaluators/subjects. Those controllers enforce per-user ownership
    // (evaluator_id / subject_id == auth id), so broadening the role is safe.

    // Approval cycle (Sprint 8 Task 14 — اعتماد ومراجعة التقييم)
    Route::get('evaluations/approvals', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'index'])->name('evaluations.approvals.index');
    Route::get('evaluations/approvals/{evaluation}', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'show'])->name('evaluations.approvals.show');
    Route::post('evaluations/approvals/{evaluation}/approve', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'approve'])->name('evaluations.approvals.approve');
    Route::post('evaluations/approvals/{evaluation}/reject', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'reject'])->name('evaluations.approvals.reject');
    Route::post('evaluations/approvals/{evaluation}/review', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'requestReview'])->name('evaluations.approvals.review');
    Route::post('evaluations/approvals/{evaluation}/reopen', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'reopen'])->name('evaluations.approvals.reopen');
    // Phase E (#203) — Per-item approve/reject/return (shared_mode evaluations only)
    Route::post('evaluations/approvals/{evaluation}/items/{response}/approve', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'approveItem'])->name('evaluations.approvals.item.approve');
    Route::post('evaluations/approvals/{evaluation}/items/{response}/reject', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'rejectItem'])->name('evaluations.approvals.item.reject');
    Route::post('evaluations/approvals/{evaluation}/items/{response}/return', [\App\Modules\Evaluation\Controllers\EvaluationApprovalController::class, 'returnItem'])->name('evaluations.approvals.item.return');

    // Job-performance linkage results (Sprint 8 Task 15 — الربط بتقييم الأداء الوظيفي)
    Route::get('job-performance', [\App\Modules\Evaluation\Controllers\JobPerformanceController::class, 'index'])->name('job-performance.index');
    Route::get('job-performance/{teacher}', [\App\Modules\Evaluation\Controllers\JobPerformanceController::class, 'show'])->name('job-performance.show');

    // Class visits (Sprint 8 Tasks 16-18 — الزيارات الصفية)
    Route::get('class-visits', [\App\Modules\Evaluation\Controllers\ClassVisitController::class, 'index'])->name('class-visits.index');
    Route::get('class-visits/create', [\App\Modules\Evaluation\Controllers\ClassVisitController::class, 'create'])->name('class-visits.create');
    Route::post('class-visits', [\App\Modules\Evaluation\Controllers\ClassVisitController::class, 'store'])->name('class-visits.store');
    Route::get('class-visits/{id}/edit', [\App\Modules\Evaluation\Controllers\ClassVisitController::class, 'edit'])->name('class-visits.edit');
    Route::put('class-visits/{id}', [\App\Modules\Evaluation\Controllers\ClassVisitController::class, 'update'])->name('class-visits.update');
    Route::delete('class-visits/{id}', [\App\Modules\Evaluation\Controllers\ClassVisitController::class, 'destroy'])->name('class-visits.destroy');
    Route::post('class-visits/{id}/execute', [\App\Modules\Evaluation\Controllers\ClassVisitController::class, 'execute'])->name('class-visits.execute');

    // Evaluation reports (Sprint 8 Tasks 19-20 — تقارير المشرفين + شاشة المدير العام)
    Route::get('eval-reports/supervisors', [\App\Modules\Evaluation\Controllers\SupervisorReportController::class, 'index'])->name('eval-reports.supervisors');
    Route::get('eval-reports/supervisors/detailed', [\App\Modules\Evaluation\Controllers\SupervisorReportController::class, 'detailed'])->name('eval-reports.supervisors-detailed');
    Route::get('eval-reports/general-manager', [\App\Modules\Evaluation\Controllers\GeneralManagerController::class, 'index'])->name('eval-reports.general-manager');

    // Evaluation audit log (Sprint 8 P7 — سجل العمليات)
    Route::get('evaluations/audit', [\App\Modules\Evaluation\Controllers\EvaluationAuditController::class, 'index'])->name('eval-audit.index');

    // Educational Outcomes — Phase C (#205 — بند الناتج التعليمي وطريقة احتسابه)
    Route::prefix('evaluations/outcomes')->name('evaluations.outcomes.')->group(function () {
        Route::get('/',          [\App\Modules\Evaluation\Controllers\EducationalOutcomeController::class, 'index'])->name('index');
        Route::get('/settings',  [\App\Modules\Evaluation\Controllers\EducationalOutcomeController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Modules\Evaluation\Controllers\EducationalOutcomeController::class, 'updateSettings'])->name('settings.update');
        Route::get('/create',    [\App\Modules\Evaluation\Controllers\EducationalOutcomeController::class, 'create'])->name('create');
        Route::post('/',         [\App\Modules\Evaluation\Controllers\EducationalOutcomeController::class, 'store'])->name('store');
        Route::get('/{outcome}', [\App\Modules\Evaluation\Controllers\EducationalOutcomeController::class, 'show'])->name('show');
        Route::post('/{outcome}/recompute', [\App\Modules\Evaluation\Controllers\EducationalOutcomeController::class, 'recompute'])->name('recompute');
    });

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

// Evaluation — evaluator/subject-facing routes (Sprint 8 Tasks 9-12). Teacher-inclusive so a
// teacher can execute evaluations assigned to them and view their own results. Same prefix/name
// (admin.) as the authoring group — only the role is broadened. Controllers enforce per-user
// ownership (evaluator_id / subject_id == auth id), so this exposes only the user's own data.
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('admin')->name('admin.')->group(function () {
    // My Evaluations landing (Task 9 — التقييمات: required of me / my results)
    Route::get('my-evaluations', [\App\Modules\Evaluation\Controllers\MyEvaluationsController::class, 'index'])->name('my-evaluations.index');

    // Behaviour records — view + log (#192). Teacher-inclusive; the controller is
    // school-scoped (activeSchoolId). Behaviour CONFIG (groups/behaviours/actions) and
    // record DELETE stay admin-only in the group above.
    Route::get('behavior/records', [\App\Modules\Behavior\Controllers\BehaviorRecordController::class, 'index'])->name('behavior.records.index');
    Route::get('behavior/records/create', [\App\Modules\Behavior\Controllers\BehaviorRecordController::class, 'create'])->name('behavior.records.create');
    Route::get('behavior/records/actions', [\App\Modules\Behavior\Controllers\BehaviorRecordController::class, 'actions'])->name('behavior.records.actions');
    Route::post('behavior/records', [\App\Modules\Behavior\Controllers\BehaviorRecordController::class, 'store'])->name('behavior.records.store');
    // Subject picker (Task 10)
    Route::get('evaluations/{form}/subjects', [\App\Modules\Evaluation\Controllers\EvaluationExecutionController::class, 'subjects'])->name('evaluations.subjects');
    // Execution screen (Task 11)
    Route::get('evaluations/{form}/subjects/{subject}/start', [\App\Modules\Evaluation\Controllers\EvaluationExecutionController::class, 'start'])->name('evaluations.execute.start');
    Route::get('evaluations/execute/{evaluation}', [\App\Modules\Evaluation\Controllers\EvaluationExecutionController::class, 'show'])->name('evaluations.execute.show');
    Route::post('evaluations/execute/{evaluation}/draft', [\App\Modules\Evaluation\Controllers\EvaluationExecutionController::class, 'draft'])->name('evaluations.execute.draft');
    Route::post('evaluations/execute/{evaluation}/submit', [\App\Modules\Evaluation\Controllers\EvaluationExecutionController::class, 'submit'])->name('evaluations.execute.submit');
    // Evidence (Task 12)
    Route::post('evaluations/execute/{evaluation}/evidence', [\App\Modules\Evaluation\Controllers\EvaluationEvidenceController::class, 'store'])->name('evaluations.execute.evidence.store');
    Route::delete('evaluations/execute/{evaluation}/evidence/{evidence}', [\App\Modules\Evaluation\Controllers\EvaluationEvidenceController::class, 'destroy'])->name('evaluations.execute.evidence.destroy');
    // Phase B (#204) — evidence review (approve / reject / request-edit)
    // TODO Phase D: replace role-gate below with granular evidence permissions
    Route::middleware('role:super-admin,school-admin')->group(function () {
        Route::post('evidence/{evidence}/approve', [\App\Modules\Evaluation\Controllers\EvaluationEvidenceController::class, 'approve'])->name('evidence.approve');
        Route::post('evidence/{evidence}/reject', [\App\Modules\Evaluation\Controllers\EvaluationEvidenceController::class, 'reject'])->name('evidence.reject');
        Route::post('evidence/{evidence}/request-edit', [\App\Modules\Evaluation\Controllers\EvaluationEvidenceController::class, 'requestEdit'])->name('evidence.requestEdit');
    });
    // Subject comment on result (Sprint 8 Item 1)
    Route::post('evaluations/execute/{evaluation}/comment', [\App\Modules\Evaluation\Controllers\EvaluationExecutionController::class, 'comment'])->name('evaluations.comment');
});

// =========================================================================
// Read-only access for teachers — cards #189 / #190
// Write routes (create/edit/delete/approve/promote/import) remain in the
// admin-only groups above.  Controllers verified school-scoped via
// HasSchoolScope::activeSchoolId() / findScoped() before this section.
// =========================================================================

// Question Banks — read (index, library, questions index, question preview)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('admin')->name('admin.')->group(function () {
    // QuestionBankController::index — school-scoped: activeSchoolId() line 23
    Route::get('question-banks/library', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'library'])->name('question-banks.library');
    Route::get('question-banks', [\App\Modules\QuestionBanks\Controllers\QuestionBankController::class, 'index'])->name('question-banks.index');
    // BankQuestionController::index — school-scoped: resolveBank() → findScoped() line 252-253
    Route::get('question-banks/{bankId}/questions', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'index'])->name('question-banks.questions.index');
    // BankQuestionController::preview — school-scoped: resolveBank() → findScoped() line 252-253
    Route::get('question-banks/{bankId}/questions/{questionId}/preview', [\App\Modules\QuestionBanks\Controllers\BankQuestionController::class, 'preview'])->name('question-banks.questions.preview');
});

// Books — read (index, grades view)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('manage')->name('manage.')->group(function () {
    // BookController::index — school-scoped: activeSchoolId() line 28
    Route::get('books', [\App\Modules\Books\Controllers\BookController::class, 'index'])->name('books.index');
    // BookGradeController::index — school-scoped: resolveBookSchoolId() → activeSchoolId() line 31
    Route::get('books/grades', [\App\Modules\Books\Controllers\BookGradeController::class, 'index'])->name('books.grades');

    // === Subject content management — cards #171 ===
    // Security: SubjectContentController::resolveSubject() checks school scope +
    // teaching assignment for teachers; admins bypass the teaching check.
    Route::get(
        'subjects/{subject}/contents',
        [\App\Modules\Subjects\Controllers\SubjectContentController::class, 'index']
    )->name('subject-contents.index');
    Route::post(
        'subjects/{subject}/contents',
        [\App\Modules\Subjects\Controllers\SubjectContentController::class, 'store']
    )->name('subject-contents.store');
    Route::post(
        'subjects/{subject}/contents/{content}/toggle-publish',
        [\App\Modules\Subjects\Controllers\SubjectContentController::class, 'togglePublish']
    )->whereNumber('subject')->whereNumber('content')->name('subject-contents.toggle-publish');
    Route::delete(
        'subjects/{subject}/contents/{content}',
        [\App\Modules\Subjects\Controllers\SubjectContentController::class, 'destroy']
    )->whereNumber('subject')->whereNumber('content')->name('subject-contents.destroy');
});

// Subject content download — accessible to teachers, admins AND students;
// the controller method re-checks access before streaming the private file.
// Uses a dedicated prefix to avoid role-guard collision with the manage group above.
Route::middleware(['auth'])
    ->get(
        'manage/subjects/{subject}/contents/{content}/download',
        [\App\Modules\Subjects\Controllers\SubjectContentController::class, 'download']
    )->whereNumber('subject')->whereNumber('content')->name('manage.subject-contents.download');

// Subjects — read (index, lesson-tree, domains)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('admin')->name('admin.')->group(function () {
    // SubjectController::index — school-scoped: activeSchoolId() line 25
    Route::get('subjects', [\App\Modules\Subjects\Controllers\SubjectController::class, 'index'])->name('subjects.index');
    // SubjectController::lessonTree — school-scoped: findScoped() line 113-114
    Route::get('subjects/{id}/lesson-tree', [\App\Modules\Subjects\Controllers\SubjectController::class, 'lessonTree'])->name('subjects.lesson-tree');
    // SubjectController::domains — school-scoped: findScoped() line 132-133
    Route::get('subjects/{id}/domains', [\App\Modules\Subjects\Controllers\SubjectController::class, 'domains'])->name('subjects.domains');
});

// Libraries (public + private + virtual labs) — read (index, show, items)
// Virtual labs are platform-wide (no school_id), so browse/view is safe for
// teachers; only lab CRUD stays admin-only in the group above (card #290).
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('admin/libraries')->name('admin.libraries.')->group(function () {
    // PublicLibraryController::index — school-scoped: activeSchoolId() line 24
    Route::get('public', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'index'])->name('public.index');
    // PublicLibraryController::show — school-scoped: findScoped() line 75
    Route::get('public/{id}', [\App\Modules\Libraries\Controllers\PublicLibraryController::class, 'show'])->whereNumber('id')->name('public.show');
    // PrivateLibraryController::index — school-scoped: activeSchoolId() line 32
    Route::get('private', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'index'])->name('private.index');
    // PrivateLibraryController::items — school-scoped: findScoped() line 92
    Route::get('private/{id}/items', [\App\Modules\Libraries\Controllers\PrivateLibraryController::class, 'items'])->name('private.items');
    // Virtual labs — browse + view (read-only). whereNumber keeps {id} from
    // shadowing labs/manage|create above.
    Route::get('labs', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'index'])->name('labs.index');
    Route::get('labs/{id}', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'show'])->whereNumber('id')->name('labs.show');
});

// Teacher Routes
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    // Teacher Schedule
    Route::get('schedule', [\App\Http\Controllers\Admin\ScheduleController::class, 'teacherSchedule'])->name('schedule');
    Route::get('schedule/pdf', [\App\Http\Controllers\Admin\ScheduleController::class, 'teacherSchedulePdf'])->name('schedule.pdf');

    // Interactive teacher calendar (aggregates lessons/exams/assignments/
    // virtual-classes/appointments/school-events for the authenticated teacher)
    Route::get('calendar', [\App\Modules\SchoolCalendar\Controllers\TeacherCalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/events', [\App\Modules\SchoolCalendar\Controllers\TeacherCalendarController::class, 'events'])->name('calendar.events');

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
    // === Anti-cheat (ac) — Trello #229: re-open a student's auto-locked attempt ===
    Route::post('exams/{exam}/attempts/{studentExam}/reopen', [\App\Http\Controllers\Admin\ExamController::class, 'reopenAttempt'])->name('exams.attempts.reopen');

    // Exam Questions for Teachers
    Route::get('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'index'])->name('exams.questions.index');
    Route::get('exams/{exam}/questions/create', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'create'])->name('exams.questions.create');
    Route::post('exams/{exam}/questions', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'store'])->name('exams.questions.store');
    Route::get('exams/{exam}/questions/{question}/edit', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'edit'])->name('exams.questions.edit');
    Route::put('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'update'])->name('exams.questions.update');
    Route::delete('exams/{exam}/questions/{question}', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'destroy'])->name('exams.questions.destroy');
    Route::post('exams/{exam}/questions/{question}/duplicate', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'duplicate'])->name('exams.questions.duplicate');
    // #217 — Add questions from bank (teachers manage their own exams' questions)
    Route::get('exams/{exam}/questions/from-bank', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'bankPicker'])->name('exams.questions.bank-picker');
    Route::post('exams/{exam}/questions/from-bank', [\App\Http\Controllers\Admin\ExamQuestionController::class, 'addFromBank'])->name('exams.questions.add-from-bank');

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

    // My Students — cards #191 / #198
    // Security: TeacherStudentController resolves teachingStudentIds() before
    // returning any data; show() aborts 403 if the student is not in that set.
    Route::get('students', [\App\Http\Controllers\TeacherStudentController::class, 'index'])->name('students.index');
    Route::get('students/{student}', [\App\Http\Controllers\TeacherStudentController::class, 'show'])->whereNumber('student')->name('students.show');

    // مواد المعلم — card #284: subject cards derived from the teacher's
    // real teaching assignments (never a hardcoded list).
    Route::get('subjects', [\App\Modules\Subjects\Controllers\TeacherSubjectController::class, 'index'])->name('subjects.index');
});

// Student Routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('special-education', [\App\Http\Controllers\StudentController::class, 'specialEducation'])->name('special-education');
    Route::get('grades', [\App\Http\Controllers\StudentController::class, 'grades'])->name('grades');
    Route::get('attendance', [\App\Http\Controllers\StudentController::class, 'attendance'])->name('attendance');
    Route::get('exams', [\App\Http\Controllers\StudentController::class, 'exams'])->name('exams');
    // === Exams card (ex) — student-facing take-exam flow ===
    Route::get('exams/{exam}', [\App\Http\Controllers\StudentExamController::class, 'show'])->name('exams.show');
    Route::post('exams/{exam}/start', [\App\Http\Controllers\StudentExamController::class, 'start'])->name('exams.start');
    Route::post('exams/{exam}/submit', [\App\Http\Controllers\StudentExamController::class, 'submit'])->name('exams.submit');
    // === Anti-cheat (ac) — exit-attempt beacon + single-session heartbeat ===
    Route::post('exams/{exam}/exit-attempt', [\App\Http\Controllers\StudentExamController::class, 'logExit'])->name('exams.exit-attempt');
    Route::post('exams/{exam}/heartbeat', [\App\Http\Controllers\StudentExamController::class, 'heartbeat'])->name('exams.heartbeat');
    Route::get('exams/{exam}/result', [\App\Http\Controllers\StudentExamController::class, 'result'])->name('exams.result');
    Route::get('schedule', [\App\Http\Controllers\StudentController::class, 'schedule'])->name('schedule');
    Route::get('weekly-plans', [\App\Http\Controllers\StudentController::class, 'weeklyPlans'])->name('weekly-plans');
    // === Books card 65 + digital reader card 103 ===
    Route::get('books', [\App\Modules\Books\Controllers\StudentBookController::class, 'index'])->name('books.index');
    Route::get('books/{id}/read', [\App\Modules\Books\Controllers\StudentBookController::class, 'read'])->whereNumber('id')->name('books.read');
    // === Absence reports + portfolio + exam schedule — card #172 ===
    Route::get('reports', [\App\Http\Controllers\StudentController::class, 'reportsIndex'])->name('reports.index');
    Route::get('reports/absence-days', [\App\Http\Controllers\StudentController::class, 'absenceDays'])->name('reports.absence-days');
    Route::get('reports/absence-summary', [\App\Http\Controllers\StudentController::class, 'absenceSummary'])->name('reports.absence-summary');
    Route::get('reports/absence-by-subject', [\App\Http\Controllers\StudentController::class, 'absenceBySubject'])->name('reports.absence-by-subject');
    Route::get('reports/exam-schedule', [\App\Http\Controllers\StudentController::class, 'examSchedule'])->name('reports.exam-schedule');
    Route::get('portfolio', [\App\Http\Controllers\StudentController::class, 'portfolio'])->name('portfolio');
    // === Student subjects cards + content hub — card #171 ===
    // Security: StudentSubjectController verifies the subject is in the
    // student's grade-level subject list before showing content.
    Route::get('subjects', [\App\Http\Controllers\StudentSubjectController::class, 'index'])->name('subjects.index');
    Route::get('subjects/{subject}', [\App\Http\Controllers\StudentSubjectController::class, 'show'])->whereNumber('subject')->name('subjects.show');

    // === Student library hub (general/private/my-files tabs) — card #173 ===
    Route::get('libraries', [\App\Modules\Libraries\Controllers\StudentLibraryController::class, 'index'])->name('libraries.index');
    Route::get('libraries/files/{source}/{id}/download', [\App\Modules\Libraries\Controllers\StudentLibraryController::class, 'downloadFile'])
        ->whereNumber('id')->whereIn('source', ['submission', 'file', 'mail', 'evidence', 'ticket'])->name('libraries.files.download');
    Route::delete('libraries/files/{source}/{id}', [\App\Modules\Libraries\Controllers\StudentLibraryController::class, 'destroyFile'])
        ->whereNumber('id')->whereIn('source', ['submission', 'file', 'mail', 'evidence', 'ticket'])->name('libraries.files.destroy');

    // === Student virtual labs (المعامل الافتراضية) — card #173 ===
    Route::get('labs', [\App\Modules\Libraries\Controllers\VirtualLabController::class, 'studentIndex'])->name('labs.index');
});

// Parent Routes
Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\ParentController::class, 'dashboard'])->name('dashboard');
    Route::get('child/{child}', [\App\Http\Controllers\ParentController::class, 'childDetails'])->name('child');
    Route::get('child/{child}/grades', [\App\Http\Controllers\ParentController::class, 'childGrades'])->name('child.grades');
    Route::get('child/{child}/exams', [\App\Http\Controllers\ParentController::class, 'childExams'])->name('child.exams');
    Route::get('child/{child}/attendance', [\App\Http\Controllers\ParentController::class, 'childAttendance'])->name('child.attendance');
    Route::get('child/{child}/schedule', [\App\Http\Controllers\ParentController::class, 'childSchedule'])->name('child.schedule');
    Route::get('contact-teacher', [\App\Http\Controllers\ParentController::class, 'contactTeacher'])->name('contact-teacher');
});

// Sprint 5 — Grade Reports (report-builder layer above the legacy grades data-entry)
// === Grades card 67 ===
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/grade-reports')->name('admin.grade-reports.')->group(function () {
    Route::get('/', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'index'])->name('index');
    Route::get('create', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'create'])->name('create');
    Route::post('/', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'store'])->name('store');
    // Grade-monitoring report (standalone analytical page)
    Route::get('monitor', [\App\Modules\GradeReports\Controllers\GradeMonitorController::class, 'index'])->name('monitor');
    Route::get('monitor/export', [\App\Modules\GradeReports\Controllers\GradeMonitorController::class, 'export'])->name('monitor.export');
    Route::get('{id}/edit', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'edit'])->whereNumber('id')->name('edit');
    Route::put('{id}', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'update'])->whereNumber('id')->name('update');
    Route::post('{id}/columns', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'updateColumns'])->whereNumber('id')->name('columns.update');
    // Per-report lock toggle, publish/close, transcript, notification
    Route::post('{id}/toggle-lock', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'toggleLock'])->whereNumber('id')->name('toggle-lock');
    Route::get('{id}/transcript', [\App\Modules\GradeReports\Controllers\GradeReportPrintController::class, 'transcript'])->whereNumber('id')->name('transcript');
    Route::get('{id}/notification', [\App\Modules\GradeReports\Controllers\GradeReportPrintController::class, 'notification'])->whereNumber('id')->name('notification');
    Route::get('{id}', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'show'])->whereNumber('id')->name('show');
    Route::delete('{id}', [\App\Modules\GradeReports\Controllers\GradeReportController::class, 'destroy'])->whereNumber('id')->name('destroy');
});

// === Grades card 67 === — Dynamic, report-driven grade entry (lives at /admin/grades/entry)
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/grades')->name('admin.grades.entry.')->group(function () {
    Route::get('entry', [\App\Modules\GradeReports\Controllers\GradeEntryController::class, 'index'])->name('index');
    Route::post('entry', [\App\Modules\GradeReports\Controllers\GradeEntryController::class, 'store'])->name('store');
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
    Route::get('noor/template', [\App\Modules\NoorImport\Controllers\NoorImportController::class, 'downloadTemplate'])->name('noor.template');
    Route::post('noor/preview', [\App\Modules\NoorImport\Controllers\NoorImportController::class, 'preview'])->name('noor.preview');
    Route::post('noor/{log}/execute', [\App\Modules\NoorImport\Controllers\NoorImportController::class, 'execute'])->name('noor.execute');
    Route::get('noor/{log}/errors', [\App\Modules\NoorImport\Controllers\NoorImportController::class, 'errorsReport'])->name('noor.errors');
    // Method 2 & 3 — Settings (scaffolded; no live API calls)
    Route::get('noor/settings', [\App\Modules\NoorImport\Controllers\NoorSettingsController::class, 'index'])->name('noor.settings');
    Route::post('noor/settings', [\App\Modules\NoorImport\Controllers\NoorSettingsController::class, 'save'])->name('noor.settings.save');
    Route::post('noor/settings/test-connection', [\App\Modules\NoorImport\Controllers\NoorSettingsController::class, 'testConnection'])->name('noor.settings.test');
});

// === Appointments card #197 (Phase 1) ===
// Staff schedule management (super-admin, school-admin, teacher)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/appointment-schedules')
    ->name('manage.appointment-schedules.')
    ->group(function () {
        Route::get('/', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'index'])->name('index');
        Route::get('create', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'create'])->name('create');
        Route::post('/', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'store'])->name('store');
        Route::get('{id}', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'show'])->name('show');
        Route::get('{id}/edit', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'edit'])->name('edit');
        Route::put('{id}', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'update'])->name('update');
        Route::delete('{id}', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'destroy'])->name('destroy');
        Route::post('{id}/toggle', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'toggle'])->name('toggle');
        Route::post('{id}/copy', [\App\Modules\Appointments\Controllers\AppointmentScheduleController::class, 'copy'])->name('copy');
    });

// Admin appointment settings – bookable roles (super-admin, school-admin only)
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/appointment-settings')
    ->name('admin.appointment-settings.')
    ->group(function () {
        Route::get('/', [\App\Modules\Appointments\Controllers\AppointmentBookableRoleController::class, 'index'])->name('index');
        Route::post('/', [\App\Modules\Appointments\Controllers\AppointmentBookableRoleController::class, 'store'])->name('store');
        Route::put('{id}', [\App\Modules\Appointments\Controllers\AppointmentBookableRoleController::class, 'update'])->name('update');
        Route::delete('{id}', [\App\Modules\Appointments\Controllers\AppointmentBookableRoleController::class, 'destroy'])->name('destroy');
        Route::post('{id}/toggle', [\App\Modules\Appointments\Controllers\AppointmentBookableRoleController::class, 'toggle'])->name('toggle');
    });

// === Appointments card #175 / #184 (Phase 2) ===
// Student / parent booking flow
Route::middleware(['auth'])->prefix('my/appointments')->name('my.appointments.')->group(function () {
    Route::get('/people',       [\App\Modules\Appointments\Controllers\AppointmentBookingController::class, 'people'])->name('people');
    Route::get('/',             [\App\Modules\Appointments\Controllers\AppointmentBookingController::class, 'index'])->name('index');
    Route::get('/create',       [\App\Modules\Appointments\Controllers\AppointmentBookingController::class, 'create'])->name('create');
    Route::post('/',            [\App\Modules\Appointments\Controllers\AppointmentBookingController::class, 'store'])->name('store');
    Route::post('{id}/cancel',  [\App\Modules\Appointments\Controllers\AppointmentBookingController::class, 'cancel'])->name('cancel');
});

// Staff booking management (super-admin, school-admin, teacher)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])->prefix('manage/appointments')->name('manage.appointments.')->group(function () {
    Route::get('/',              [\App\Modules\Appointments\Controllers\AppointmentBookingManagementController::class, 'index'])->name('index');
    Route::get('{id}',           [\App\Modules\Appointments\Controllers\AppointmentBookingManagementController::class, 'show'])->name('show');
    Route::post('{id}/decide',   [\App\Modules\Appointments\Controllers\AppointmentBookingManagementController::class, 'decide'])->name('decide');
});

// ==========================================
// Support Tickets (Trello #267) — routes moved to the module file
// ==========================================
require __DIR__.'/../app/Modules/Support/Routes/web.php';

// ==========================================
// Surveys module (Trello #185)
// ==========================================
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/surveys')->name('admin.surveys.')->group(function () {
    Route::get('/', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'index'])->name('index');
    Route::get('/create', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'create'])->name('create');
    Route::post('/', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'store'])->name('store');
    Route::get('/{survey}/edit', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'edit'])->whereNumber('survey')->name('edit');
    Route::put('/{survey}', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'update'])->whereNumber('survey')->name('update');
    Route::post('/{survey}/publish', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'publish'])->whereNumber('survey')->name('publish');
    Route::post('/{survey}/close', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'close'])->whereNumber('survey')->name('close');
    Route::get('/{survey}/results', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'results'])->whereNumber('survey')->name('results');
    Route::delete('/{survey}', [\App\Modules\Surveys\Controllers\AdminSurveyController::class, 'destroy'])->whereNumber('survey')->name('destroy');
});

Route::middleware(['auth'])->prefix('my/surveys')->name('my.surveys.')->group(function () {
    Route::get('/', [\App\Modules\Surveys\Controllers\MySurveyController::class, 'index'])->name('index');
    Route::get('/{survey}', [\App\Modules\Surveys\Controllers\MySurveyController::class, 'show'])->whereNumber('survey')->name('show');
    Route::post('/{survey}', [\App\Modules\Surveys\Controllers\MySurveyController::class, 'submit'])->whereNumber('survey')->name('submit');
});

// === Certificates module (#192 §9 / #172 / #266) ===
// Migrated to permission:-based gating; full route set lives in the module file.
require base_path('app/Modules/Certificates/Routes/web.php');

// ==========================================
// Internal Mailbox
// ==========================================
Route::middleware(['auth'])->prefix('my/mailbox')->name('my.mailbox.')->group(function () {
    Route::get('/', [\App\Modules\Mail\Controllers\MailboxController::class, 'index'])->name('index');
    Route::get('/compose', [\App\Modules\Mail\Controllers\MailboxController::class, 'create'])->name('create');
    Route::post('/', [\App\Modules\Mail\Controllers\MailboxController::class, 'store'])->name('store');
    Route::get('/folder/{folder}', [\App\Modules\Mail\Controllers\MailboxController::class, 'index'])
        ->whereIn('folder', ['inbox', 'sent', 'drafts', 'starred', 'important', 'task', 'archive', 'trash'])
        ->name('folder');
    Route::get('/{mail}', [\App\Modules\Mail\Controllers\MailboxController::class, 'show'])->whereNumber('mail')->name('show');
    Route::get('/{mail}/attachment', [\App\Modules\Mail\Controllers\MailboxController::class, 'download'])->whereNumber('mail')->name('attachment');
    Route::post('/{mail}/star', [\App\Modules\Mail\Controllers\MailboxController::class, 'star'])->name('star');
    Route::post('/{mail}/unstar', [\App\Modules\Mail\Controllers\MailboxController::class, 'unstar'])->name('unstar');
    Route::post('/{mail}/archive', [\App\Modules\Mail\Controllers\MailboxController::class, 'archive'])->name('archive');
    Route::post('/{mail}/unarchive', [\App\Modules\Mail\Controllers\MailboxController::class, 'unarchive'])->name('unarchive');
    Route::post('/{mail}/trash', [\App\Modules\Mail\Controllers\MailboxController::class, 'trash'])->name('trash');
    Route::post('/{mail}/restore', [\App\Modules\Mail\Controllers\MailboxController::class, 'restore'])->name('restore');
    Route::post('/{mail}/task', [\App\Modules\Mail\Controllers\MailboxController::class, 'toggleTask'])->name('task');
    Route::delete('/{mail}', [\App\Modules\Mail\Controllers\MailboxController::class, 'destroy'])->name('destroy');
});

// === School Calendar card #196 / #174 / #179 / #186 ===
// Staff CRUD (super-admin, school-admin, teacher)
Route::middleware(['auth', 'role:super-admin,school-admin,teacher'])
    ->prefix('manage/school-calendar')
    ->name('manage.school-calendar.')
    ->group(function () {
        Route::get('events.json', [\App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController::class, 'eventsJson'])->name('events.json');
        Route::get('/', [\App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController::class, 'index'])->name('index');
        Route::get('create', [\App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController::class, 'create'])->name('create');
        Route::post('/', [\App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController::class, 'store'])->name('store');
        Route::get('{id}/edit', [\App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController::class, 'edit'])->name('edit');
        Route::put('{id}', [\App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController::class, 'update'])->name('update');
        Route::delete('{id}', [\App\Modules\SchoolCalendar\Controllers\ManageSchoolCalendarController::class, 'destroy'])->name('destroy');
    });

// All-roles read-only calendar view
Route::middleware(['auth'])
    ->prefix('my/calendar')
    ->name('my.calendar.')
    ->group(function () {
        Route::get('events.json', [\App\Modules\SchoolCalendar\Controllers\MyCalendarController::class, 'eventsJson'])->name('events.json');
        Route::get('/', [\App\Modules\SchoolCalendar\Controllers\MyCalendarController::class, 'index'])->name('index');
    });

// === Discussion Rooms module ===
require __DIR__.'/../app/Modules/Discussion/Routes/web.php';
require __DIR__.'/../app/Modules/SchoolCalendar/Routes/web.php';

// === Virtual Classrooms module ===
require __DIR__.'/../app/Modules/VirtualClasses/Routes/web.php';

// === Special Education module ===
require __DIR__.'/../app/Modules/SpecialEducation/Routes/web.php';
require __DIR__.'/../app/Modules/Mail/Routes/web.php';

// === WhatsApp Settings & Logs (Admin) — Task 7 ===
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->prefix('admin/whatsapp')
    ->name('admin.whatsapp.')
    ->group(function () {
        Route::get('settings', [\App\Modules\Whatsapp\Controllers\WhatsappSettingsController::class, 'index'])->name('index');
        Route::get('settings/{school}/edit', [\App\Modules\Whatsapp\Controllers\WhatsappSettingsController::class, 'edit'])->name('edit');
        Route::put('settings/{school}', [\App\Modules\Whatsapp\Controllers\WhatsappSettingsController::class, 'update'])->name('update');
        Route::get('logs', [\App\Modules\Whatsapp\Controllers\WhatsappSettingsController::class, 'logs'])->name('logs');
        Route::post('logs/{log}/resend', [\App\Modules\Whatsapp\Controllers\WhatsappSettingsController::class, 'resend'])->name('resend');
    });

// === Attendance Excuse — Admin Review — Task 7 ===
Route::middleware(['auth', 'role:super-admin,school-admin'])
    ->group(function () {
        Route::post('admin/attendance/{attendance}/excuse/review', [\App\Modules\Attendance\Controllers\ExcuseController::class, 'review'])->name('admin.attendance.excuse.review');
    });

// === Attendance Excuse — Parent Submission — Task 7 ===
Route::middleware(['auth', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::post('child/{child}/attendance/{attendance}/excuse', [\App\Modules\Attendance\Controllers\ExcuseController::class, 'store'])->name('attendance.excuse');
    });

// === Announcements module (Sprint 9 — Task 2) ===
require __DIR__.'/../app/Modules/Announcements/Routes/web.php';

// === Parents-as-contact — Communications (Sprint 9 — Trello #242) ===
require __DIR__.'/../app/Modules/Communications/Routes/web.php';
require __DIR__.'/../app/Modules/Whatsapp/Routes/web.php';
require __DIR__.'/../app/Modules/SmsServices/Routes/web.php';

// === Question-Bank rebuild — CORE screens (#249/#250/#253) ===
require __DIR__.'/../app/Modules/QuestionBankCore/Routes/web.php';

// === Sprint 10 — Attendance subsystem (#261/#262/#263 student, #264 teacher, #265 QR) ===
require __DIR__.'/../app/Modules/Attendance/Routes/web.php';
require __DIR__.'/../app/Modules/TeacherAttendance/Routes/web.php';
require __DIR__.'/../app/Modules/Qr/Routes/web.php';

// === Sprint 10 — Educational websites (#270) — NET-NEW ===
require __DIR__.'/../app/Modules/EducationalSites/Routes/web.php';

// === Sprint 10 — Admissions / Registration (#268) — NET-NEW ===
require __DIR__.'/../app/Modules/Admissions/Routes/web.php';

// Teacher materials hub — Trello #287
require __DIR__.'/../app/Modules/TeacherMaterials/Routes/web.php';
