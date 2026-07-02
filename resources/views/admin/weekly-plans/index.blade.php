@extends('layouts.app')

@section('title', __('shell.nav_weekly_plan'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('weekly_plan.page_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('weekly_plan.breadcrumb')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('weekly_plan.btn_add_plan')
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">فلترة</h4>
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
                <div class="col-md-2 mb-1">
                    <label>المعلم</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">الكل</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2 mb-1">
                    <label>المادة</label>
                    <select name="subject_id" class="form-control">
                        <option value="">الكل</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-1">
                    <label>الفصل</label>
                    <select name="class_id" class="form-control">
                        <option value="">الكل</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }} - {{ $class->division }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-1">
                    <label>الأسبوع</label>
                    <input type="date" name="week_start_date" class="form-control" value="{{ request('week_start_date') }}">
                </div>
                <div class="col-md-2 mb-1">
                    <label>الحالة</label>
                    <select name="status" class="form-control">
                        <option value="">الكل</option>
                        <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>مقفلة</option>
                        <option value="unlocked" {{ request('status') == 'unlocked' ? 'selected' : '' }}>مفتوحة</option>
                    </select>
                </div>
                <div class="col-md-2 mb-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm ml-1"><i class="la la-search"></i> بحث</button>
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm"><i class="la la-times"></i> إعادة</a>
                </div>
            </form>
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
    <form id="bulkForm" action="{{ route('manage.weekly-plans.bulk-lock') }}" method="POST">
        @csrf
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h4 class="card-title">قائمة الخطط</h4>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
            <button type="submit" form="bulkForm" class="btn btn-sm btn-outline-danger">قفل المحدد</button>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
                        <th><input type="checkbox" id="selectAll"></th>
                        @endif
                        <th>#</th>
                        <th>@lang('common.teacher')</th>
                        <th>@lang('common.subject')</th>
                        <th>@lang('common.classroom')</th>
                        <th>الأسبوع</th>
                        <th>@lang('common.status')</th>
                        <th>@lang('common.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                    <tr>
                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
                        <td>
                            @if(!$plan->is_locked)
                            <input type="checkbox" name="plan_ids[]" value="{{ $plan->id }}" class="plan-checkbox">
                            @endif
                        </td>
                        @endif
                        <td>{{ $plan->id }}</td>
                        <td>{{ $plan->teacher->name }}</td>
                        <td>{{ $plan->subject->name }}</td>
                        <td>{{ $plan->classRoom->name }} - {{ $plan->classRoom->division }}</td>
                        <td>{{ $plan->week_label }}</td>
                        <td>
                            <span class="badge {{ $plan->status_class }}">{{ $plan->status }}</span>
                            @if($plan->is_locked && $plan->lockedByUser)
                                <br><small class="text-muted">بواسطة: {{ $plan->lockedByUser->name }}</small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route($routePrefix . '.show', $plan) }}" class="btn btn-sm btn-outline-primary" title="عرض"><i class="la la-eye"></i></a>
                            @if($plan->canEdit(auth()->user()))
                            <a href="{{ route($routePrefix . '.edit', $plan) }}" class="btn btn-sm btn-outline-secondary" title="تعديل"><i class="la la-edit"></i></a>
                            @endif
                            <a href="{{ route($routePrefix . '.duplicate', $plan) }}" class="btn btn-sm btn-outline-secondary" title="نسخ للأسبوع القادم"><i class="la la-copy"></i></a>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
                                @if($plan->is_locked)
                                <form action="{{ route('manage.weekly-plans.unlock', $plan) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="فتح القفل"><i class="la la-lock-open"></i></button>
                                </form>
                                @else
                                <form action="{{ route('manage.weekly-plans.lock', $plan) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="قفل"><i class="la la-lock"></i></button>
                                </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">لا توجد خطط</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $plans->links() }}
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.plan-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
