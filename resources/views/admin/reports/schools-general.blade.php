@extends('layouts.app')

@section('title', 'تقرير المدارس العام')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">تقرير المدارس العام</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.administrative') }}">التقارير الإدارية</a></li>
            <li class="breadcrumb-item active">تقرير المدارس العام</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('admin.reports._tabs', ['currentTab' => 'administrative'])

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">إحصائية المدارس ({{ $rows->count() }})</h4>
        </div>
        <div class="card-body p-0">
            @if($rows->isEmpty())
                <div class="text-center text-muted py-4">لا توجد مدارس في النطاق</div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>المدرسة</th>
                            <th class="text-center">عدد الطلاب</th>
                            <th class="text-center">عدد المعلمين</th>
                            <th class="text-center">عدد الفصول</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td><strong>{{ $row->school->name_ar ?? $row->school->name }}</strong></td>
                                <td class="text-center">{{ $row->students }}</td>
                                <td class="text-center">{{ $row->teachers }}</td>
                                <td class="text-center">{{ $row->classes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>الإجمالي</th>
                            <th class="text-center">{{ $rows->sum('students') }}</th>
                            <th class="text-center">{{ $rows->sum('teachers') }}</th>
                            <th class="text-center">{{ $rows->sum('classes') }}</th>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
