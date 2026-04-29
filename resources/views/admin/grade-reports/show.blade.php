@extends('layouts.app')

@section('title', $report->title)

@section('content')
<div class="content-header">
    <h2 class="content-header-title">{{ $report->title }}</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.index') }}">إدارة الدرجات</a></li>
            <li class="breadcrumb-item active">{{ $report->title }}</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">معلومات التقرير
                <span class="badge badge-{{ ['dynamic'=>'primary','static'=>'info','gradesheet'=>'success'][$report->type] ?? 'secondary' }} ms-2">
                    {{ ['dynamic'=>'ديناميكي','static'=>'ثابت','gradesheet'=>'كشف درجات'][$report->type] ?? $report->type }}
                </span>
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2"><strong>الفصل الدراسي:</strong> {{ $report->academicTerm?->name ?? '—' }}</div>
                <div class="col-md-4 mb-2"><strong>الصف:</strong> {{ $report->classRoom?->name ?? '—' }}</div>
                <div class="col-md-4 mb-2"><strong>أنشئ بواسطة:</strong> {{ $report->creator?->name ?? '—' }}</div>
                <div class="col-md-4 mb-2"><strong>إدخال الدرجات:</strong> {{ $report->grade_input_starts_at?->format('Y-m-d') ?? '—' }} → {{ $report->grade_input_ends_at?->format('Y-m-d') ?? '—' }}</div>
                <div class="col-md-4 mb-2"><strong>الاحتساب:</strong> {{ $report->calc_starts_at?->format('Y-m-d') ?? '—' }} → {{ $report->calc_ends_at?->format('Y-m-d') ?? '—' }}</div>
                <div class="col-md-4 mb-2"><strong>فتح/إغلاق التقرير:</strong> {{ $report->opens_at?->format('Y-m-d') ?? '—' }} → {{ $report->closes_at?->format('Y-m-d') ?? '—' }}</div>
                <div class="col-12">
                    <strong>عرض على:</strong>
                    @if($report->visible_to_student) <span class="badge badge-success">الطالب</span> @endif
                    @if($report->visible_to_parent) <span class="badge badge-success">ولي الأمر</span> @endif
                    @if($report->visible_to_teacher) <span class="badge badge-success">المعلم</span> @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">الأعمدة ({{ $report->columns->count() }})</h4>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>الترتيب</th>
                        <th>العنوان</th>
                        <th>النوع</th>
                        <th>الوزن</th>
                        <th>الدرجة القصوى</th>
                        <th>ضمن المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report->columns as $col)
                        <tr>
                            <td>{{ $col->sort_order }}</td>
                            <td>{{ $col->title }}</td>
                            <td>{{ ['numeric'=>'رقمي','calculated'=>'محسوب','calculated_horizontal'=>'محسوب أفقياً'][$col->type] ?? $col->type }}</td>
                            <td>{{ $col->weight }}</td>
                            <td>{{ $col->max_score }}</td>
                            <td>{!! $col->is_in_total ? '<i class="la la-check text-success"></i>' : '<i class="la la-times text-muted"></i>' !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center">لا توجد أعمدة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <small class="text-muted">إضافة/تعديل الأعمدة + محرر الترويسة + تصدير Excel + ترتيب الطلاب — قيد التطوير في الإصدار التالي.</small>
        </div>
    </div>

    <div class="text-end">
        <a href="{{ route('admin.grade-reports.index') }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> رجوع</a>
    </div>
</div>
@endsection
