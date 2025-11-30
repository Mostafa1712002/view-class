@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">لوحة التحكم</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">لوحة التحكم</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-body">
    <!-- Welcome Card -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="text-white mb-1">مرحباً {{ Auth::user()->name }}</h4>
                            <p class="mb-0">أهلاً بك في المنصة الذهبية - النظام التعليمي الذكي</p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0"><i class="la la-calendar me-1"></i>{{ now()->format('Y/m/d') }}</p>
                            <small>{{ now()->locale('ar')->dayName }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->isSuperAdmin())
    <!-- Super Admin Stats -->
    <div class="row">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-primary p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-building la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $schools_count ?? 0 }}</h2>
                    <p class="card-text">المدارس</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-success p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-sitemap la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $sections_count ?? 0 }}</h2>
                    <p class="card-text">المراحل</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-warning p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-users la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $classes_count ?? 0 }}</h2>
                    <p class="card-text">الفصول</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-info p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-chalkboard-teacher la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $teachers_count ?? 0 }}</h2>
                    <p class="card-text">المعلمين</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-danger p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-user-graduate la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $students_count ?? 0 }}</h2>
                    <p class="card-text">الطلاب</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-secondary p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-book la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $subjects_count ?? 0 }}</h2>
                    <p class="card-text">المواد</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Schools -->
    @if(isset($recent_schools) && $recent_schools->count())
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">آخر المدارس المسجلة</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>المدرسة</th>
                                    <th>البريد</th>
                                    <th>الحالة</th>
                                    <th>تاريخ التسجيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_schools as $school)
                                <tr>
                                    <td>{{ $school->name }}</td>
                                    <td>{{ $school->email }}</td>
                                    <td>
                                        <span class="badge bg-{{ $school->is_active ? 'success' : 'secondary' }}">
                                            {{ $school->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </td>
                                    <td>{{ $school->created_at->format('Y/m/d') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    @if(Auth::user()->isSchoolAdmin())
    <!-- School Admin Stats -->
    <div class="row">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-success p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-sitemap la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $sections_count ?? 0 }}</h2>
                    <p class="card-text">المراحل</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-warning p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-users la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $classes_count ?? 0 }}</h2>
                    <p class="card-text">الفصول</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-info p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-chalkboard-teacher la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $teachers_count ?? 0 }}</h2>
                    <p class="card-text">المعلمين</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-danger p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-user-graduate la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $students_count ?? 0 }}</h2>
                    <p class="card-text">الطلاب</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-secondary p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-book la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $subjects_count ?? 0 }}</h2>
                    <p class="card-text">المواد</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-primary p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-tasks la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $pending_assignments ?? 0 }}</h2>
                    <p class="card-text">واجبات نشطة</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Attendance -->
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">حضور اليوم</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>الحاضرون</span>
                        <span class="text-success fw-bold">{{ $present_today ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>الغائبون</span>
                        <span class="text-danger fw-bold">{{ $absent_today ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>نسبة الحضور</span>
                        <span class="text-primary fw-bold">{{ $attendance_rate ?? 0 }}%</span>
                    </div>
                    <div class="progress mt-3" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $attendance_rate ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الاختبارات القادمة</h4>
                </div>
                <div class="card-body">
                    @if(isset($upcoming_exams) && $upcoming_exams->count())
                        @foreach($upcoming_exams->take(3) as $exam)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>{{ $exam->title }}</strong>
                                <br><small class="text-muted">{{ $exam->subject->name ?? '' }}</small>
                            </div>
                            <span class="badge bg-light-primary">{{ $exam->start_date->format('m/d') }}</span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center mb-0">لا توجد اختبارات قادمة</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">إحصائيات سريعة</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span><i class="la la-list-alt me-1"></i>الخطط الأسبوعية</span>
                        <span class="badge bg-info">{{ $weekly_plans_count ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span><i class="la la-tasks me-1"></i>الواجبات النشطة</span>
                        <span class="badge bg-warning">{{ $pending_assignments ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="la la-file-text me-1"></i>الاختبارات القادمة</span>
                        <span class="badge bg-primary">{{ isset($upcoming_exams) ? $upcoming_exams->count() : 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Grades -->
    @if(isset($recent_grades) && $recent_grades->count())
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">آخر الدرجات المسجلة</h4>
                    <a href="{{ route('admin.grades.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>المادة</th>
                                    <th>الاختبار</th>
                                    <th>الدرجة</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_grades as $grade)
                                <tr>
                                    <td>{{ $grade->student->name ?? '-' }}</td>
                                    <td>{{ $grade->subject->name ?? '-' }}</td>
                                    <td>{{ $grade->exam->title ?? '-' }}</td>
                                    <td>{{ $grade->score }}/{{ $grade->max_score }}</td>
                                    <td>
                                        <span class="badge bg-{{ $grade->percentage >= 60 ? 'success' : 'danger' }}">
                                            {{ number_format($grade->percentage, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    @if(Auth::user()->isTeacher())
    <!-- Teacher Stats -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-primary p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-book la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $subjects_count ?? 0 }}</h2>
                    <p class="card-text">المواد التي أدرسها</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-success p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-users la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $classes_count ?? 0 }}</h2>
                    <p class="card-text">الفصول</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-warning p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-file-text la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ isset($upcoming_exams) ? $upcoming_exams->count() : 0 }}</h2>
                    <p class="card-text">الاختبارات القادمة</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card text-center">
                <div class="card-body">
                    <div class="avatar bg-light-danger p-50 mb-1">
                        <div class="avatar-content">
                            <i class="la la-graduation-cap la-2x"></i>
                        </div>
                    </div>
                    <h2 class="fw-bolder">{{ $pending_grading ?? 0 }}</h2>
                    <p class="card-text">بانتظار التصحيح</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher's Schedule Today -->
    @if(isset($today_schedules) && $today_schedules->count())
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">جدول اليوم</h4>
                </div>
                <div class="card-body">
                    @foreach($today_schedules as $schedule)
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <strong>{{ $schedule->subject->name ?? '' }}</strong>
                            <br><small class="text-muted">{{ $schedule->classRoom->name ?? '' }}</small>
                        </div>
                        <span class="badge bg-primary">{{ $schedule->periods->first()->start_time ?? '' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الواجبات</h4>
                </div>
                <div class="card-body">
                    @if(isset($assignments) && $assignments->count())
                        @foreach($assignments as $assignment)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>{{ $assignment->title }}</strong>
                                <br><small class="text-muted">{{ $assignment->submissions_count }} تسليم</small>
                            </div>
                            <span class="badge bg-{{ $assignment->due_date->isPast() ? 'danger' : 'warning' }}">
                                {{ $assignment->due_date->format('m/d') }}
                            </span>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center mb-0">لا توجد واجبات</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    @if(Auth::user()->isStudent() || Auth::user()->isParent())
    <!-- Student/Parent View -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="la la-calendar la-4x text-primary mb-2"></i>
                    <h4>سيتم إضافة المزيد من المعلومات قريباً</h4>
                    <p class="text-muted">يمكنك متابعة الجدول الدراسي والدرجات والحضور من هنا</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
