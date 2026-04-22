@extends('layouts.admin')

@section('title', 'الملفات والمواد')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">الملفات والمواد</h1>
        <a href="{{ route('admin.files.create') }}" class="btn btn-primary">
            <i class="la la-plus me-1"></i>رفع ملف جديد
        </a>
    </div>

    {{-- فلتر --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">البحث</label>
                    <input type="text" name="search" class="form-control" placeholder="اسم الملف..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-select">
                        <option value="">الكل</option>
                        <option value="material" {{ request('type') == 'material' ? 'selected' : '' }}>مادة تعليمية</option>
                        <option value="assignment" {{ request('type') == 'assignment' ? 'selected' : '' }}>واجب</option>
                        <option value="resource" {{ request('type') == 'resource' ? 'selected' : '' }}>مرجع</option>
                        <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>أخرى</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
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
                    <a href="{{ route('admin.files.index') }}" class="btn btn-secondary">
                        <i class="la la-refresh"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- قائمة الملفات --}}
    <div class="card">
        <div class="card-body">
            @if($files->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الملف</th>
                                <th>@lang('common.type')</th>
                                <th>@lang('common.subject')</th>
                                <th>الصف</th>
                                <th>الحجم</th>
                                <th>التحميلات</th>
                                <th>رافع الملف</th>
                                <th class="text-center">@lang('common.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="la {{ $file->icon }} fs-3 me-2"></i>
                                            <div>
                                                <strong>{{ $file->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $file->original_name }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info">{{ $file->type_name }}</span></td>
                                    <td>{{ $file->subject?->name ?? '-' }}</td>
                                    <td>{{ $file->classRoom?->name ?? '-' }}</td>
                                    <td>{{ $file->formatted_size }}</td>
                                    <td>{{ $file->download_count }}</td>
                                    <td>{{ $file->uploader?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.files.download', $file) }}" class="btn btn-sm btn-success" title="تحميل">
                                                <i class="la la-download"></i>
                                            </a>
                                            <a href="{{ route('admin.files.edit', $file) }}" class="btn btn-sm btn-warning" title="تعديل">
                                                <i class="la la-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.files.destroy', $file) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
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
                {{ $files->links() }}
            @else
                <div class="text-center py-5">
                    <i class="la la-folder-open display-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد ملفات</p>
                    <a href="{{ route('admin.files.create') }}" class="btn btn-primary">
                        <i class="la la-plus me-1"></i>رفع ملف جديد
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
