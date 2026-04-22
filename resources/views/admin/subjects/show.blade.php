@extends('layouts.app')

@section('title', 'تفاصيل المادة')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">{{ $subject->name }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.subjects.index') }}">المواد</a></li>
                        <li class="breadcrumb-item active">التفاصيل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.subjects.edit', $subject) }}" class="btn btn-warning"><i data-feather="edit"></i> تعديل</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">معلومات المادة</h4></div>
                <div class="card-body">
                    <table class="table">
                        <tr><th>@lang('common.name')</th><td>{{ $subject->name }}</td></tr>
                        <tr><th>الرمز</th><td>{{ $subject->code }}</td></tr>
                        <tr><th>@lang('common.school')</th><td>{{ $subject->school->name ?? '-' }}</td></tr>
                        <tr><th>@lang('common.type')</th><td>{{ $subject->is_core ? 'أساسية' : 'اختيارية' }}</td></tr>
                        <tr><th>@lang('common.description')</th><td>{{ $subject->description ?? '-' }}</td></tr>
                        <tr><th>@lang('common.status')</th><td>@if($subject->is_active)<span class="badge bg-success">@lang('common.active')</span>@else<span class="badge bg-secondary">@lang('common.inactive')</span>@endif</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">المعلمين ({{ $subject->teachers->count() }})</h4></div>
                <div class="card-body">
                    @if($subject->teachers->count() > 0)
                        <ul class="list-group">
                            @foreach($subject->teachers as $teacher)
                                <li class="list-group-item">{{ $teacher->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">لا يوجد معلمين</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
