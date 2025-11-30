<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

            <!-- Dashboard -->
            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="la la-home"></i>
                    <span class="menu-title">لوحة التحكم</span>
                </a>
            </li>

            <!-- Communications -->
            <li class="navigation-header">
                <span>التواصل</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                <a href="{{ route('notifications.index') }}">
                    <i class="la la-bell"></i>
                    <span class="menu-title">الإشعارات</span>
                    @php
                        $unreadNotifications = auth()->user()->customNotifications()->unread()->count();
                    @endphp
                    @if($unreadNotifications > 0)
                        <span class="badge badge-pill badge-danger float-right">{{ $unreadNotifications }}</span>
                    @endif
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                <a href="{{ route('messages.index') }}">
                    <i class="la la-envelope"></i>
                    <span class="menu-title">الرسائل</span>
                </a>
            </li>

            @if(auth()->user()->isSuperAdmin())
            <!-- System Admin Section -->
            <li class="navigation-header">
                <span>إدارة النظام</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}">
                <a href="{{ route('admin.schools.index') }}">
                    <i class="la la-building"></i>
                    <span class="menu-title">المدارس</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.maintenance.*') ? 'active' : '' }}">
                <a href="{{ route('admin.maintenance.index') }}">
                    <i class="la la-wrench"></i>
                    <span class="menu-title">صيانة النظام</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
            <!-- School Admin Section -->
            <li class="navigation-header">
                <span>الإدارة التعليمية</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.academic-years.*') ? 'active' : '' }}">
                <a href="{{ route('manage.academic-years.index') }}">
                    <i class="la la-calendar"></i>
                    <span class="menu-title">السنوات الدراسية</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.sections.*') ? 'active' : '' }}">
                <a href="{{ route('manage.sections.index') }}">
                    <i class="la la-sitemap"></i>
                    <span class="menu-title">المراحل الدراسية</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.classes.*') ? 'active' : '' }}">
                <a href="{{ route('manage.classes.index') }}">
                    <i class="la la-users"></i>
                    <span class="menu-title">الفصول</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.subjects.*') ? 'active' : '' }}">
                <a href="{{ route('manage.subjects.index') }}">
                    <i class="la la-book"></i>
                    <span class="menu-title">المواد الدراسية</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.users.*') ? 'active' : '' }}">
                <a href="{{ route('manage.users.index') }}">
                    <i class="la la-user"></i>
                    <span class="menu-title">المستخدمين</span>
                </a>
            </li>

            <!-- Schedule and Weekly Plans -->
            <li class="navigation-header">
                <span>الجداول والخطط</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.schedules.*') ? 'active' : '' }}">
                <a href="{{ route('manage.schedules.index') }}">
                    <i class="la la-calendar-check-o"></i>
                    <span class="menu-title">الجداول الدراسية</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('manage.weekly-plans.*') ? 'active' : '' }}">
                <a href="{{ route('manage.weekly-plans.index') }}">
                    <i class="la la-list-alt"></i>
                    <span class="menu-title">الخطط الأسبوعية</span>
                </a>
            </li>

            <!-- Exams and Grades -->
            <li class="navigation-header">
                <span>الاختبارات والدرجات</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}">
                <a href="{{ route('admin.exams.index') }}">
                    <i class="la la-file-text"></i>
                    <span class="menu-title">الاختبارات</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
                <a href="{{ route('admin.grades.index') }}">
                    <i class="la la-graduation-cap"></i>
                    <span class="menu-title">إدخال الدرجات</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.grades.class-report') ? 'active' : '' }}">
                <a href="{{ route('admin.grades.class-report') }}">
                    <i class="la la-bar-chart"></i>
                    <span class="menu-title">تقارير الصفوف</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.grades.student-report') ? 'active' : '' }}">
                <a href="{{ route('admin.grades.student-report') }}">
                    <i class="la la-user-graduate"></i>
                    <span class="menu-title">تقارير الطلاب</span>
                </a>
            </li>

            <!-- Attendance -->
            <li class="navigation-header">
                <span>الحضور والغياب</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.attendance.index') ? 'active' : '' }}">
                <a href="{{ route('admin.attendance.index') }}">
                    <i class="la la-check-square"></i>
                    <span class="menu-title">تسجيل الحضور</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.attendance.daily-report') ? 'active' : '' }}">
                <a href="{{ route('admin.attendance.daily-report') }}">
                    <i class="la la-calendar-day"></i>
                    <span class="menu-title">التقرير اليومي</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.attendance.class-report') ? 'active' : '' }}">
                <a href="{{ route('admin.attendance.class-report') }}">
                    <i class="la la-chart-bar"></i>
                    <span class="menu-title">تقرير الصف</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.attendance.student-report') ? 'active' : '' }}">
                <a href="{{ route('admin.attendance.student-report') }}">
                    <i class="la la-user-clock"></i>
                    <span class="menu-title">تقرير الطالب</span>
                </a>
            </li>

            <!-- Reports Section -->
            <li class="navigation-header">
                <span>التقارير</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.index') }}">
                    <i class="la la-file-alt"></i>
                    <span class="menu-title">مركز التقارير</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.reports.analytics') ? 'active' : '' }}">
                <a href="{{ route('admin.reports.analytics') }}">
                    <i class="la la-chart-line"></i>
                    <span class="menu-title">الإحصائيات</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.exports.*') ? 'active' : '' }}">
                <a href="{{ route('admin.exports.index') }}">
                    <i class="la la-download"></i>
                    <span class="menu-title">تصدير البيانات</span>
                </a>
            </li>

            <!-- Files & Assignments Section -->
            <li class="navigation-header">
                <span>الملفات والواجبات</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.files.*') ? 'active' : '' }}">
                <a href="{{ route('admin.files.index') }}">
                    <i class="la la-folder-open"></i>
                    <span class="menu-title">الملفات والمواد</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}">
                <a href="{{ route('admin.assignments.index') }}">
                    <i class="la la-tasks"></i>
                    <span class="menu-title">الواجبات</span>
                </a>
            </li>

            <!-- Settings Section -->
            <li class="navigation-header">
                <span>الإعدادات</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.settings.index') ? 'active' : '' }}">
                <a href="{{ route('admin.settings.index') }}">
                    <i class="la la-cog"></i>
                    <span class="menu-title">إعدادات المدرسة</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.settings.profile') ? 'active' : '' }}">
                <a href="{{ route('admin.settings.profile') }}">
                    <i class="la la-user"></i>
                    <span class="menu-title">الملف الشخصي</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
                <a href="{{ route('admin.activity-logs.index') }}">
                    <i class="la la-clipboard-list"></i>
                    <span class="menu-title">سجل النشاطات</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->isTeacher())
            <!-- Teacher Section -->
            <li class="navigation-header">
                <span>جدولي</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('teacher.schedule') ? 'active' : '' }}">
                <a href="{{ route('teacher.schedule') }}">
                    <i class="la la-calendar-check-o"></i>
                    <span class="menu-title">جدولي</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('teacher.weekly-plans.*') ? 'active' : '' }}">
                <a href="{{ route('teacher.weekly-plans.index') }}">
                    <i class="la la-list-alt"></i>
                    <span class="menu-title">خططي الأسبوعية</span>
                </a>
            </li>

            <!-- Teacher Exams and Grades -->
            <li class="navigation-header">
                <span>الاختبارات والدرجات</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('teacher.exams.*') ? 'active' : '' }}">
                <a href="{{ route('teacher.exams.index') }}">
                    <i class="la la-file-text"></i>
                    <span class="menu-title">اختباراتي</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('teacher.grades.*') ? 'active' : '' }}">
                <a href="{{ route('teacher.grades.index') }}">
                    <i class="la la-graduation-cap"></i>
                    <span class="menu-title">إدخال الدرجات</span>
                </a>
            </li>

            <!-- Teacher Attendance -->
            <li class="navigation-header">
                <span>الحضور والغياب</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('teacher.attendance.index') ? 'active' : '' }}">
                <a href="{{ route('teacher.attendance.index') }}">
                    <i class="la la-check-square"></i>
                    <span class="menu-title">تسجيل الحضور</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('teacher.attendance.daily-report') ? 'active' : '' }}">
                <a href="{{ route('teacher.attendance.daily-report') }}">
                    <i class="la la-calendar-day"></i>
                    <span class="menu-title">التقرير اليومي</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->isStudent())
            <!-- Student Section -->
            <li class="navigation-header">
                <span>بوابة الطالب</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                <a href="{{ route('student.dashboard') }}">
                    <i class="la la-home"></i>
                    <span class="menu-title">لوحة التحكم</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}">
                <a href="{{ route('student.schedule') }}">
                    <i class="la la-calendar-check-o"></i>
                    <span class="menu-title">جدولي</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('student.weekly-plans') ? 'active' : '' }}">
                <a href="{{ route('student.weekly-plans') }}">
                    <i class="la la-list-alt"></i>
                    <span class="menu-title">الخطط الأسبوعية</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('student.exams') ? 'active' : '' }}">
                <a href="{{ route('student.exams') }}">
                    <i class="la la-file-text"></i>
                    <span class="menu-title">الاختبارات</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('student.grades') ? 'active' : '' }}">
                <a href="{{ route('student.grades') }}">
                    <i class="la la-graduation-cap"></i>
                    <span class="menu-title">درجاتي</span>
                </a>
            </li>

            <li class="nav-item {{ request()->routeIs('student.attendance') ? 'active' : '' }}">
                <a href="{{ route('student.attendance') }}">
                    <i class="la la-check-square"></i>
                    <span class="menu-title">سجل حضوري</span>
                </a>
            </li>
            @endif

            @if(auth()->user()->isParent())
            <!-- Parent Section -->
            <li class="navigation-header">
                <span>بوابة ولي الأمر</span>
                <i class="la la-ellipsis-h"></i>
            </li>

            <li class="nav-item {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
                <a href="{{ route('parent.dashboard') }}">
                    <i class="la la-home"></i>
                    <span class="menu-title">لوحة التحكم</span>
                </a>
            </li>

            @foreach(auth()->user()->children as $child)
            <li class="nav-item has-sub {{ request()->is('parent/child/'.$child->id.'*') ? 'open' : '' }}">
                <a href="#">
                    <i class="la la-user"></i>
                    <span class="menu-title">{{ $child->name }}</span>
                </a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('parent.child') && request()->route('child') == $child->id ? 'active' : '' }}">
                        <a href="{{ route('parent.child', $child) }}">
                            <i class="la la-info-circle"></i>
                            <span class="menu-title">التفاصيل</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('parent.child.grades') && request()->route('child') == $child->id ? 'active' : '' }}">
                        <a href="{{ route('parent.child.grades', $child) }}">
                            <i class="la la-graduation-cap"></i>
                            <span class="menu-title">الدرجات</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('parent.child.attendance') && request()->route('child') == $child->id ? 'active' : '' }}">
                        <a href="{{ route('parent.child.attendance', $child) }}">
                            <i class="la la-check-square"></i>
                            <span class="menu-title">الحضور</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('parent.child.schedule') && request()->route('child') == $child->id ? 'active' : '' }}">
                        <a href="{{ route('parent.child.schedule', $child) }}">
                            <i class="la la-calendar"></i>
                            <span class="menu-title">الجدول</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endforeach

            <li class="nav-item {{ request()->routeIs('parent.contact-teacher') ? 'active' : '' }}">
                <a href="{{ route('parent.contact-teacher') }}">
                    <i class="la la-envelope"></i>
                    <span class="menu-title">تواصل مع المعلم</span>
                </a>
            </li>
            @endif

        </ul>
    </div>
</div>
