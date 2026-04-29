@extends('layouts.app')

@section('title', 'إدارة الدرجات')

@section('content')
@php($isRtl = app()->getLocale() === 'ar')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">إدارة الدرجات</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">إدارة الدرجات</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        <a href="{{ route('admin.grade-reports.create') }}" class="btn btn-primary"><i class="la la-plus"></i> إنشاء تقرير</a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-info">
        <i class="la la-info-circle"></i>
        <strong>ملاحظة:</strong> لا يُسمح لمدير النظام بإدخال الدرجات مباشرة، بل يتم إدخالها من قبل المعلمين. هذه الشاشة لإدارة <em>تعريفات</em> التقارير فقط.
    </div>

    {{-- Type filter --}}
    <div class="card">
        <div class="card-body py-2">
            <div class="btn-group">
                <a href="{{ route('admin.grade-reports.index') }}" class="btn btn-sm btn-outline-{{ !request('type') ? 'primary' : 'secondary' }}">الكل</a>
                <a href="{{ route('admin.grade-reports.index', ['type' => 'dynamic']) }}" class="btn btn-sm btn-outline-{{ request('type') === 'dynamic' ? 'primary' : 'secondary' }}">ديناميكي</a>
                <a href="{{ route('admin.grade-reports.index', ['type' => 'static']) }}" class="btn btn-sm btn-outline-{{ request('type') === 'static' ? 'primary' : 'secondary' }}">ثابت</a>
                <a href="{{ route('admin.grade-reports.index', ['type' => 'gradesheet']) }}" class="btn btn-sm btn-outline-{{ request('type') === 'gradesheet' ? 'primary' : 'secondary' }}">كشف الدرجات</a>
            </div>
        </div>
    </div>

    @if($reports->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-file-alt la-3x text-muted"></i>
                <p class="mt-3 mb-0">لا توجد تقارير حتى الآن. <a href="{{ route('admin.grade-reports.create') }}">إنشاء تقرير ديناميكي جديد</a></p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>النوع</th>
                                <th>الفصل الدراسي</th>
                                <th>الصف</th>
                                <th>عدد الأعمدة</th>
                                <th>تاريخ الفتح</th>
                                <th>تاريخ الإغلاق</th>
                                <th class="text-center" style="width:120px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $r)
                                <tr>
                                    <td>{{ $r->title }}</td>
                                    <td>
                                        <span class="badge badge-{{ ['dynamic' => 'primary', 'static' => 'info', 'gradesheet' => 'success'][$r->type] ?? 'secondary' }}">
                                            {{ ['dynamic' => 'ديناميكي', 'static' => 'ثابت', 'gradesheet' => 'كشف درجات'][$r->type] ?? $r->type }}
                                        </span>
                                    </td>
                                    <td>{{ $r->academicTerm?->name ?? '—' }}</td>
                                    <td>{{ $r->classRoom?->name ?? '—' }}</td>
                                    <td>{{ $r->columns_count }}</td>
                                    <td>{{ $r->opens_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $r->closes_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.grade-reports.show', $r->id) }}" class="btn btn-sm btn-outline-info" title="عرض"><i class="la la-eye"></i></a>
                                        <form method="POST" action="{{ route('admin.grade-reports.destroy', $r->id) }}" class="d-inline" onsubmit="return confirm('حذف التقرير؟');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="la la-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($reports->hasPages())
                <div class="card-footer">{{ $reports->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection
