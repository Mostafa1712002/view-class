@extends('layouts.app')

@section('title', 'تقارير المستخدمين')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">التقارير</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">تقارير المستخدمين</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('admin.reports._tabs', ['currentTab' => 'user'])

    {{-- Sub-tabs: students / teachers / parents --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'teachers' ? 'active' : '' }}" href="{{ route('admin.reports.user-reports', ['tab' => 'teachers']) }}">المعلمين</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'students' ? 'active' : '' }}" href="{{ route('admin.reports.user-reports', ['tab' => 'students']) }}">الطلاب</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'parents' ? 'active' : '' }}" href="{{ route('admin.reports.user-reports', ['tab' => 'parents']) }}">أولياء الأمور</a></li>
    </ul>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
                @if($tab === 'teachers') قائمة المعلمين
                @elseif($tab === 'students') قائمة الطلاب
                @else قائمة أولياء الأمور
                @endif
                <span class="badge badge-secondary ms-2">{{ $rows->total() }}</span>
            </h4>
        </div>
        <div class="card-body p-0">
            @if($rows->isEmpty())
                <div class="text-center text-muted py-4">لا توجد بيانات</div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>اسم المستخدم</th>
                            <th>المدرسة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->school?->name_ar ?? $u->school?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($rows->hasPages())
            <div class="card-footer">{{ $rows->withQueryString()->links() }}</div>
        @endif
    </div>

    <div class="alert alert-light border mt-3">
        <small>تفاصيل أداء كل مستخدم (الأنشطة المنفذة، التفاعل، عدد الاختبارات...) قيد التطوير.</small>
    </div>
</div>
@endsection
