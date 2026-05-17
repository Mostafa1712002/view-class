<div class="student-header card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="student-avatar-large">
            @if($student->avatar || $student->profile_picture)
                <img src="{{ asset('storage/'.($student->avatar ?? $student->profile_picture)) }}" alt="" />
            @else
                {{ mb_substr($student->name, 0, 1) }}
            @endif
        </div>
        <div class="flex-grow-1">
            <h3 class="m-0">{{ $student->name }}</h3>
            <small class="text-muted">{{ '@'.$student->username }}</small>
            <div class="mt-1 d-flex flex-wrap gap-1">
                @if($student->national_id)
                    <span class="status-pill on"><i class="la la-id-card"></i> {{ $student->national_id }}</span>
                @endif
                @if(optional($student->section)->name)
                    <span class="grade-chip">{{ $student->section->name }}</span>
                @endif
                @if(optional($student->classRoom)->name)
                    <span class="class-chip">{{ $student->classRoom->name }}</span>
                @endif
                <span class="status-pill {{ $student->is_active ? 'on' : 'off' }}">
                    {{ $student->is_active ? __('users.student_status_active') : __('users.student_status_inactive') }}
                </span>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <a href="{{ route('admin.users.students.edit', $student->id) }}" class="btn btn-sm add-student-btn"><i class="la la-edit"></i> @lang('users.edit')</a>
            <a href="{{ route('admin.users.students.index') }}" class="btn btn-sm btn-soft"><i class="la la-arrow-left"></i> @lang('users.student_back_to_list')</a>
        </div>
    </div>
</div>
