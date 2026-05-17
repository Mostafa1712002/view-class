@php $active = $active ?? 'profile'; @endphp
<div class="student-subnav card mb-3">
    <div class="card-body p-2">
        <ul class="nav nav-pills flex-wrap gap-1">
            <li class="nav-item">
                <a class="nav-link {{ $active === 'profile' ? 'active' : '' }}" href="{{ route('admin.users.students.show', $student->id) }}">
                    <i class="la la-id-card-alt"></i> @lang('users.student_view_profile')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $active === 'parents' ? 'active' : '' }}" href="{{ route('admin.users.students.parents', $student->id) }}">
                    <i class="la la-user-friends"></i> @lang('users.parents_link')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $active === 'schedule' ? 'active' : '' }}" href="{{ route('admin.users.students.schedule', $student->id) }}">
                    <i class="la la-calendar"></i> @lang('users.schedule_link')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $active === 'lessons' ? 'active' : '' }}" href="{{ route('admin.users.students.lessons', $student->id) }}">
                    <i class="la la-chalkboard"></i> @lang('users.classes_link')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $active === 'attendance' ? 'active' : '' }}" href="{{ route('admin.users.students.attendance', $student->id) }}">
                    <i class="la la-times-circle"></i> @lang('users.absences_link')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $active === 'behavior' ? 'active' : '' }}" href="{{ route('admin.users.students.behavior', $student->id) }}">
                    <i class="la la-balance-scale"></i> @lang('users.behavior_link')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $active === 'medical' ? 'active' : '' }}" href="{{ route('admin.users.students.medical', $student->id) }}">
                    <i class="la la-notes-medical"></i> @lang('users.medical_link')
                </a>
            </li>
        </ul>
    </div>
</div>
