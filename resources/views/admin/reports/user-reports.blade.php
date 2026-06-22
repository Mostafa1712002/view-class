@extends('layouts.app')

@section('title', 'تقارير المستخدمين')
@section('body_class', 'theme-light')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">تقارير المستخدمين</h2>
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

    <div class="ds-card card">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title mb-0">
                @if($tab === 'teachers') قائمة المعلمين
                @elseif($tab === 'students') قائمة الطلاب
                @else قائمة أولياء الأمور
                @endif
            </h5>
            <span class="ds-badge-navy">{{ $rows->total() }}</span>
        </div>

        @if($rows->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="people" :size="30" /></div>
                <div class="ds-empty-title">لا توجد بيانات</div>
                <div class="ds-empty-desc">لم يتم العثور على مستخدمين في هذا التصنيف.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover ds-table-tight mb-0">
                    <thead>
                        <tr>
                            <th scope="col">الاسم</th>
                            <th scope="col">البريد الإلكتروني</th>
                            <th scope="col">اسم المستخدم</th>
                            <th scope="col">المدرسة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $u)
                            <tr>
                                <td style="font-weight:700;color:#0f172a">{{ $u->name }}</td>
                                <td dir="ltr" style="text-align:start">{{ $u->email }}</td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->school?->name_ar ?? $u->school?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())
                <div class="ds-card-footer card-footer">{{ $rows->withQueryString()->links() }}</div>
            @endif
        @endif
    </div>

    <div class="alert alert-light border mt-3 d-flex align-items-center" style="gap:.5rem">
        <x-svg-icon name="info-circle" :size="16" />
        <small>تفاصيل أداء كل مستخدم (الأنشطة المنفذة، التفاعل، عدد الاختبارات...) قيد التطوير.</small>
    </div>
</div>
@endsection
