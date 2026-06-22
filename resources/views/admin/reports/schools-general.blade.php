@extends('layouts.app')

@section('title', 'تقرير المدارس العام')
@section('body_class', 'theme-light')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">تقرير المدارس العام</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.administrative') }}">التقارير الإدارية</a></li>
            <li class="breadcrumb-item active">تقرير المدارس العام</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('admin.reports._tabs', ['currentTab' => 'administrative'])

    <div class="ds-card card">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title mb-0">إحصائية المدارس</h5>
            <span class="ds-badge-navy">{{ $rows->count() }}</span>
        </div>

        @if($rows->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="building" :size="30" /></div>
                <div class="ds-empty-title">لا توجد مدارس في النطاق</div>
                <div class="ds-empty-desc">لم يتم العثور على مدارس ضمن صلاحياتك.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover ds-table-tight mb-0">
                    <thead>
                        <tr>
                            <th scope="col">المدرسة</th>
                            <th scope="col" class="text-center">عدد الطلاب</th>
                            <th scope="col" class="text-center">عدد المعلمين</th>
                            <th scope="col" class="text-center">عدد الفصول</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td style="font-weight:700;color:#0f172a">{{ $row->school->name_ar ?? $row->school->name }}</td>
                                <td class="text-center"><span class="ds-badge-info">{{ $row->students }}</span></td>
                                <td class="text-center"><span class="ds-badge-navy">{{ $row->teachers }}</span></td>
                                <td class="text-center"><span class="ds-badge-success">{{ $row->classes }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:700;background:var(--gray-50)">
                            <th>الإجمالي</th>
                            <th class="text-center">{{ $rows->sum('students') }}</th>
                            <th class="text-center">{{ $rows->sum('teachers') }}</th>
                            <th class="text-center">{{ $rows->sum('classes') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
