<?php

namespace App\Providers;

use App\Modules\Auth\Repositories\Contracts\SessionRepository;
use App\Modules\Auth\Repositories\Contracts\UserRepository;
use App\Modules\Auth\Repositories\EloquentSessionRepository;
use App\Modules\Auth\Repositories\EloquentUserRepository;
use App\Modules\Auth\Services\JwtService;
use App\Modules\Books\Repositories\Contracts\BookRepository;
use App\Modules\Books\Repositories\EloquentBookRepository;
use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;
use App\Modules\Dashboard\Repositories\EloquentDashboardStatsRepository;
use App\Modules\Profile\Repositories\Contracts\ProfileRepository;
use App\Modules\Profile\Repositories\EloquentProfileRepository;
use App\Modules\Scope\Repositories\Contracts\ScopeRepository;
use App\Modules\Scope\Repositories\EloquentScopeRepository;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use App\Modules\QuestionBanks\Repositories\EloquentQuestionBankRepository;
use App\Modules\GradeReports\Repositories\Contracts\GradeReportRepository;
use App\Modules\GradeReports\Repositories\EloquentGradeReportRepository;
use App\Modules\Lessons\Repositories\Contracts\LessonRepository;
use App\Modules\Lessons\Repositories\EloquentLessonRepository;
use App\Modules\Libraries\Repositories\Contracts\LibraryItemRepository;
use App\Modules\Libraries\Repositories\Contracts\LibraryRepository;
use App\Modules\Libraries\Repositories\EloquentLibraryItemRepository;
use App\Modules\Libraries\Repositories\EloquentLibraryRepository;
use App\Modules\SchoolBranches\Repositories\Contracts\SchoolBranchRepository;
use App\Modules\SchoolBranches\Repositories\EloquentSchoolBranchRepository;
use App\Modules\SmsServices\Repositories\Contracts\SmsSettingsRepository;
use App\Modules\SmsServices\Repositories\EloquentSmsSettingsRepository;
use App\Modules\Subjects\Repositories\Contracts\SubjectRepository;
use App\Modules\Subjects\Repositories\EloquentSubjectRepository;
use App\Modules\Users\Repositories\Contracts\AdminRepository;
use App\Modules\Users\Repositories\Contracts\ParentRepository;
use App\Modules\Users\Repositories\Contracts\StudentRepository;
use App\Modules\Users\Repositories\Contracts\TeacherRepository;
use App\Modules\Users\Repositories\EloquentAdminListRepository;
use App\Modules\Users\Repositories\EloquentParentListRepository;
use App\Modules\Users\Repositories\EloquentStudentListRepository;
use App\Modules\Users\Repositories\EloquentTeacherListRepository;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationFormRepository;
use App\Modules\Evaluation\Repositories\Contracts\EvaluationRepository;
use App\Modules\Evaluation\Repositories\Contracts\ClassVisitRepository;
use App\Modules\Evaluation\Repositories\EloquentEvaluationFormRepository;
use App\Modules\Evaluation\Repositories\EloquentEvaluationRepository;
use App\Modules\Evaluation\Repositories\EloquentClassVisitRepository;
use App\Modules\Appointments\Repositories\Contracts\AppointmentRepository;
use App\Modules\Appointments\Repositories\Contracts\AppointmentScheduleRepository;
use App\Modules\Appointments\Repositories\EloquentAppointmentRepository;
use App\Modules\Appointments\Repositories\EloquentAppointmentScheduleRepository;
use App\Modules\Support\Repositories\Contracts\SupportTicketRepository;
use App\Modules\Support\Repositories\EloquentSupportTicketRepository;
use App\Modules\SchoolCalendar\Repositories\Contracts\SchoolEventRepository;
use App\Modules\SchoolCalendar\Repositories\EloquentSchoolEventRepository;
use App\Modules\Discussion\Repositories\Contracts\DiscussionRepository;
use App\Modules\Discussion\Repositories\DiscussionEloquentRepository;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use App\Modules\VirtualClasses\Repositories\VirtualClassRepository;
use App\Modules\SpecialEducation\Repositories\Contracts\SpecialEducationRepository;
use App\Modules\SpecialEducation\Repositories\EloquentSpecialEducationRepository;
use App\Modules\Certificates\Repositories\Contracts\CertificateRepository;
use App\Modules\Certificates\Repositories\EloquentCertificateRepository;
use App\Modules\Mail\Repositories\Contracts\MailboxRepository;
use App\Modules\Mail\Repositories\EloquentMailboxRepository;
use App\Modules\Surveys\Repositories\Contracts\SurveyRepository;
use App\Modules\Surveys\Repositories\EloquentSurveyRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        SessionRepository::class => EloquentSessionRepository::class,
        DashboardStatsRepository::class => EloquentDashboardStatsRepository::class,
        ProfileRepository::class => EloquentProfileRepository::class,
        ScopeRepository::class => EloquentScopeRepository::class,
        StudentRepository::class => EloquentStudentListRepository::class,
        ParentRepository::class => EloquentParentListRepository::class,
        TeacherRepository::class => EloquentTeacherListRepository::class,
        AdminRepository::class => EloquentAdminListRepository::class,
        SubjectRepository::class => EloquentSubjectRepository::class,
        BookRepository::class => EloquentBookRepository::class,
        QuestionBankRepository::class => EloquentQuestionBankRepository::class,
        GradeReportRepository::class => EloquentGradeReportRepository::class,
        LessonRepository::class => EloquentLessonRepository::class,
        LibraryRepository::class => EloquentLibraryRepository::class,
        LibraryItemRepository::class => EloquentLibraryItemRepository::class,
        SchoolBranchRepository::class => EloquentSchoolBranchRepository::class,
        SmsSettingsRepository::class => EloquentSmsSettingsRepository::class,
        EvaluationFormRepository::class => EloquentEvaluationFormRepository::class,
        EvaluationRepository::class => EloquentEvaluationRepository::class,
        ClassVisitRepository::class => EloquentClassVisitRepository::class,
        AppointmentRepository::class         => EloquentAppointmentRepository::class,
        AppointmentScheduleRepository::class => EloquentAppointmentScheduleRepository::class,
        SupportTicketRepository::class        => EloquentSupportTicketRepository::class,
        SchoolEventRepository::class          => EloquentSchoolEventRepository::class,
        DiscussionRepository::class              => DiscussionEloquentRepository::class,
        VirtualClassRepositoryInterface::class   => VirtualClassRepository::class,
        SpecialEducationRepository::class        => EloquentSpecialEducationRepository::class,
        CertificateRepository::class             => EloquentCertificateRepository::class,
        MailboxRepository::class                 => EloquentMailboxRepository::class,
        SurveyRepository::class                  => EloquentSurveyRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(JwtService::class, fn () => JwtService::create());
    }
}
