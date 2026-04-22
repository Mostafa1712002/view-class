@extends('layouts.admin')

@section('title', 'تواصل مع المعلم')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">تواصل مع المعلمين</h1>
            <small class="text-muted">أرسل رسالة لمعلمي أبنائك</small>
        </div>
    </div>

    @if($children->count() > 0)
        <div class="row">
            @foreach($children as $child)
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">معلمي {{ $child->name }}</h5>
                        </div>
                        <div class="card-body">
                            @if($child->teachers && $child->teachers->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>@lang('common.teacher')</th>
                                                <th>@lang('common.subject')</th>
                                                <th>@lang('common.email')</th>
                                                <th class="text-center">إجراء</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($child->teachers as $teacher)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm me-2">
                                                                <span class="avatar-content bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                    {{ mb_substr($teacher->name, 0, 1) }}
                                                                </span>
                                                            </div>
                                                            <span>{{ $teacher->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>{{ $teacher->pivot->subject_name ?? '-' }}</td>
                                                    <td>{{ $teacher->email }}</td>
                                                    <td class="text-center">
                                                        <a href="{{ route('messages.create') }}?to={{ $teacher->id }}" class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-envelope me-1"></i>
                                                            إرسال رسالة
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bi bi-person-x display-4 text-muted"></i>
                                    <p class="text-muted mt-2">لا يوجد معلمين مسجلين لهذا الطالب</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h4 class="mt-3">لا يوجد أبناء مسجلين</h4>
                <p class="text-muted">يرجى التواصل مع إدارة المدرسة لربط حسابك بأبنائك</p>
            </div>
        </div>
    @endif
</div>
@endsection
