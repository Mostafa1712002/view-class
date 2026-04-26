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
        Route::get('permissions', [\App\Http\Controllers\Admin\School\SchoolPermissionController::class, 'index'])->name('permissions.index');
    });
});

// School Admin Routes
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('manage')->name('manage.')->group(function () {
    // Sections Management
    Route::resource('sections', \App\Http\Controllers\Admin\SectionController::class);

    // Classes Management
    Route::resource('classes', \App\Http\Controllers\Admin\ClassController::class);

    // Subjects Management
    Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class);

    // Users Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);

    // Academic Years
    Route::resource('academic-years', \App\Http\Controllers\Admin\AcademicYearController::class);

    // Schedules Management
    Route::resource('schedules', \App\Http\Controllers\Admin\ScheduleController::class);
    Route::post('schedules/{schedule}/periods', [\App\Http\Controllers\Admin\ScheduleController::class, 'storePeriod'])->name('schedules.store-period');
    Route::delete('schedules/{schedule}/periods/{period}', [\App\Http\Controllers\Admin\ScheduleController::class, 'destroyPeriod'])->name('schedules.destroy-period');

    // Weekly Plans Management
    Route::resource('weekly-plans', \App\Http\Controllers\Admin\WeeklyPlanController::class);
    Route::post('weekly-plans/{weekly_plan}/lock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'lock'])->name('weekly-plans.lock');
    Route::post('weekly-plans/{weekly_plan}/unlock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'unlock'])->name('weekly-plans.unlock');
    Route::post('weekly-plans/bulk-lock', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'bulkLock'])->name('weekly-plans.bulk-lock');
    Route::get('weekly-plans/{weekly_plan}/duplicate', [\App\Http\Controllers\Admin\WeeklyPlanController::class, 'duplicate'])->name('weekly-plans.duplicate');
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

// Reports Routes
Route::middleware(['auth', 'role:super-admin,school-admin'])->prefix('admin/reports')->name('admin.reports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
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
