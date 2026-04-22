@extends('layouts.app')

@section('title', 'تفاصيل المدرسة')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">{{ $school->name }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">المدارس</a></li>
                        <li class="breadcrumb-item active">التفاصيل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-warning">
            <i data-feather="edit"></i> تعديل
        </a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">معلومات المدرسة</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>@lang('common.name')</th>
                            <td>{{ $school->name }}</td>
                        </tr>
                        <tr>
                            <th>الرمز</th>
                            <td>{{ $school->code }}</td>
                        </tr>
                        <tr>
                            <th>@lang('common.email')</th>
                            <td>{{ $school->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>@lang('common.phone')</th>
                            <td>{{ $school->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>الموقع</th>
                            <td>{{ $school->website ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>العنوان</th>
                            <td>{{ $school->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>@lang('common.status')</th>
                            <td>
                                @if($school->is_active)
                                    <span class="badge bg-success">@lang('common.active')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('common.inactive')</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الأقسام ({{ $school->sections->count() }})</h4>
                </div>
                <div class="card-body">
                    @if($school->sections->count() > 0)
                        <ul class="list-group">
                            @foreach($school->sections as $section)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $section->name }}
                                    <span class="badge bg-primary">{{ $section->level_label }} - {{ $section->gender_label }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">لا توجد أقسام</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
