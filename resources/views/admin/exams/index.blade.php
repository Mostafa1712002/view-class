@extends('layouts.admin')

@section('title', __('common.exams'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">إدارة الاختبارات</h1>
        <a href="{{ route('admin.exams.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            إضافة اختبار جديد
        </a>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">المادة</label>
                    <select name="subject_id" class="form-select">
                        <option value="">جميع المواد</option>
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
                        <option value="">جميع الصفوف</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-select">
                        <option value="">الكل</option>
                        @foreach(\App\Models\Exam::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">الكل</option>
                        @foreach(\App\Models\Exam::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="bi bi-search me-1"></i>
                        بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Exams Table --}}
    <div class="card">
        <div class="card-body">
            @if($exams->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>عنوان الاختبار</th>
                                <th>المادة</th>
                                <th>الصف</th>
                                <th>النوع</th>
                                <th>الأسئلة</th>
                                <th>الدرجة الكلية</th>
                                <th>@lang('common.status')</th>
                                <th>@lang('common.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exams as $exam)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.exams.show', $exam) }}" class="text-decoration-none">
                                            {{ $exam->title }}
                                        </a>
                                    </td>
                                    <td>{{ $exam->subject->name ?? '-' }}</td>
                                    <td>{{ $exam->classRoom->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $exam->type_label }}</span>
                                    </td>
                                    <td>{{ $exam->questions_count }}</td>
                                    <td>{{ number_format($exam->total_marks, 1) }}</td>
                                    <td>
                                        <span class="badge {{ $exam->status_class }}">
                                            {{ $exam->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-outline-primary" title="عرض">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.exams.questions.index', $exam) }}" class="btn btn-outline-info" title="الأسئلة">
                                                <i class="bi bi-list-ol"></i>
                                            </a>
                                            <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-outline-warning" title="تعديل">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.exams.destroy', $exam) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الاختبار؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $exams->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-journal-x display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد اختبارات</p>
                    <a href="{{ route('admin.exams.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>
                        إنشاء أول اختبار
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
