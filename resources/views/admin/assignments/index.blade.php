@extends('layouts.admin')

@section('title', 'الواجبات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">الواجبات</h1>
        <a href="{{ route('admin.assignments.create') }}" class="btn btn-primary">
            <i class="la la-plus me-1"></i>واجب جديد
        </a>
    </div>

    {{-- فلتر --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">الكل</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>منشور</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>مغلق</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المادة</label>
                    <select name="subject_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الصف</label>
                    <select name="class_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="la la-search"></i> بحث
                    </button>
                    <a href="{{ route('admin.assignments.index') }}" class="btn btn-secondary">
                        <i class="la la-refresh"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- قائمة الواجبات --}}
    <div class="card">
        <div class="card-body">
            @if($assignments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الواجب</th>
                                <th>المادة</th>
                                <th>الصف</th>
                                <th>تاريخ التسليم</th>
                                <th class="text-center">التسليمات</th>
                                <th class="text-center">@lang('common.status')</th>
                                <th class="text-center">@lang('common.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignments as $assignment)
                                @php $stats = $assignment->submission_stats; @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $assignment->title }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $assignment->teacher?->name }}</small>
                                    </td>
                                    <td>{{ $assignment->subject?->name ?? '-' }}</td>
                                    <td>{{ $assignment->classRoom?->name ?? '-' }}</td>
                                    <td>
                                        <span class="{{ $assignment->is_overdue ? 'text-danger' : '' }}">
                                            {{ $assignment->due_date->format('Y-m-d') }}
                                        </span>
                                        @if($assignment->due_time)
                                            <br>
                                            <small>{{ $assignment->due_time->format('H:i') }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $stats['submitted'] }}/{{ $stats['total'] }}</span>
                                        <br>
                                        <small class="text-muted">{{ $stats['submission_rate'] }}%</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $assignment->status_color }}">
                                            {{ $assignment->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn btn-sm btn-info" title="عرض">
                                                <i class="la la-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.assignments.edit', $assignment) }}" class="btn btn-sm btn-warning" title="تعديل">
                                                <i class="la la-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                    <i class="la la-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $assignments->links() }}
            @else
                <div class="text-center py-5">
                    <i class="la la-tasks display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد واجبات</p>
                    <a href="{{ route('admin.assignments.create') }}" class="btn btn-primary">
                        <i class="la la-plus me-1"></i>واجب جديد
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
