@extends('layouts.app')

@section('title', 'بطاقة الطالب - ' . $student->name)
@section('body_class', 'theme-light')

@section('content')
@include('components.alerts')

<div class="content-header">
    <h2 class="content-header-title">بطاقة الطالب</h2>
    <div class="breadcrumb-wrapper d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
            <li class="breadcrumb-item active">{{ $student->name }}</li>
        </ol>
        <div class="d-flex align-items-center" style="gap:.4rem">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <x-svg-icon name="arrow-right" :size="14" class="me-1" /> العودة
            </a>
            <form action="{{ route('admin.reports.student-card-pdf') }}" method="GET" class="d-inline">
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <input type="hidden" name="academic_year_id" value="{{ $academicYear?->id }}">
                <button type="submit" class="btn btn-danger btn-sm">
                    <x-svg-icon name="file-earmark-pdf" :size="14" class="me-1" /> تصدير PDF
                </button>
            </form>
        </div>
    </div>
</div>

<div class="content-body">
    {{-- معلومات الطالب --}}
    <div class="ds-card ds-card-accent card mb-4">
        <div class="ds-card-body card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center mb-3 mb-md-0">
                    <span style="width:96px;height:96px;border-radius:50%;font-size:2.2rem;font-weight:800;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1f2a44,#2d3a5c);color:#f2d999">
                        {{ mb_substr($student->name, 0, 1) }}
                    </span>
                </div>
                <div class="col-md-10">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small d-block">اسم الطالب</label>
                            <span class="fw-bold">{{ $student->name }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small d-block">البريد الإلكتروني</label>
                            <span dir="ltr" style="display:inline-block">{{ $student->email }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small d-block">العام الدراسي</label>
                            <span>{{ $academicYear?->name ?? '-' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small d-block">الصف</label>
                            <span>{{ $enrollment?->classRoom?->name ?? 'غير مسجل' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small d-block">المرحلة</label>
                            <span>{{ $enrollment?->classRoom?->section?->name ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- إحصائيات سريعة --}}
    @php
        $overallAverage = $grades->count() > 0 ? round($grades->avg('average'), 1) : 0;
        $quickStats = [
            ['v' => $grades->count(), 'l' => 'عدد المواد', 'icon' => 'journal-text', 'tone' => 'navy'],
            ['v' => $overallAverage . '%', 'l' => 'المعدل العام', 'icon' => 'mortarboard', 'tone' => 'success'],
            ['v' => $attendanceStats['rate'] . '%', 'l' => 'نسبة الحضور', 'icon' => 'calendar-check', 'tone' => 'info'],
            ['v' => $attendanceStats['absent'], 'l' => 'أيام الغياب', 'icon' => 'calendar-x', 'tone' => 'warning'],
        ];
    @endphp
    <div class="row mb-4">
        @foreach($quickStats as $s)
            <div class="col-md-3 col-6 mb-3">
                <div class="ds-card card ds-stat h-100">
                    <div class="ds-card-body card-body d-flex align-items-center" style="gap:.7rem">
                        <span class="ds-badge-{{ $s['tone'] }}" style="width:44px;height:44px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0">
                            <x-svg-icon name="{{ $s['icon'] }}" :size="21" />
                        </span>
                        <div>
                            <div style="font-size:1.45rem;font-weight:800;color:#0f172a;line-height:1">{{ $s['v'] }}</div>
                            <div class="text-muted small">{{ $s['l'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- جدول الدرجات --}}
    <div class="ds-card card mb-4">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title mb-0">الدرجات حسب المادة</h5>
        </div>
        @if($grades->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover ds-table-tight mb-0">
                    <thead>
                        <tr>
                            <th>@lang('common.subject')</th>
                            <th class="text-center">الفترة الأولى</th>
                            <th class="text-center">الفترة الثانية</th>
                            <th class="text-center">الفترة الثالثة</th>
                            <th class="text-center">الفترة الرابعة</th>
                            <th class="text-center">المعدل</th>
                            <th class="text-center">@lang('common.status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grades as $subjectData)
                            <tr>
                                <td style="font-weight:600">{{ $subjectData['subject']->name }}</td>
                                <td class="text-center">{{ $subjectData['terms']->get('الفترة الأولى')?->total ?? '-' }}</td>
                                <td class="text-center">{{ $subjectData['terms']->get('الفترة الثانية')?->total ?? '-' }}</td>
                                <td class="text-center">{{ $subjectData['terms']->get('الفترة الثالثة')?->total ?? '-' }}</td>
                                <td class="text-center">{{ $subjectData['terms']->get('الفترة الرابعة')?->total ?? '-' }}</td>
                                <td class="text-center fw-bold">{{ $subjectData['average'] }}%</td>
                                <td class="text-center">
                                    @if($subjectData['average'] >= 50)
                                        <span class="ds-badge-success">ناجح</span>
                                    @else
                                        <span class="ds-badge-danger">راسب</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:700;background:var(--gray-50)">
                            <th>المعدل العام</th>
                            <th colspan="5" class="text-center">{{ $overallAverage }}%</th>
                            <th class="text-center">
                                @if($overallAverage >= 50)
                                    <span class="ds-badge-success">ناجح</span>
                                @else
                                    <span class="ds-badge-danger">راسب</span>
                                @endif
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="mortarboard" :size="30" /></div>
                <div class="ds-empty-title">لا توجد درجات مسجلة</div>
            </div>
        @endif
    </div>

    {{-- إحصائيات الحضور --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title mb-0">إحصائيات الحضور</h5>
        </div>
        <div class="ds-card-body card-body">
            @php
                $attTiles = [
                    ['v' => $attendanceStats['total'], 'l' => 'إجمالي الأيام', 'tone' => 'navy'],
                    ['v' => $attendanceStats['present'], 'l' => 'حاضر', 'tone' => 'success'],
                    ['v' => $attendanceStats['absent'], 'l' => 'غائب', 'tone' => 'danger'],
                    ['v' => $attendanceStats['late'], 'l' => 'متأخر', 'tone' => 'warning'],
                    ['v' => $attendanceStats['excused'], 'l' => 'بعذر', 'tone' => 'info'],
                    ['v' => $attendanceStats['rate'] . '%', 'l' => 'نسبة الحضور', 'tone' => 'gold'],
                ];
            @endphp
            <div class="row">
                @foreach($attTiles as $t)
                    <div class="col-md-2 col-4 text-center mb-3">
                        <div style="border:1px solid var(--border-subtle);border-radius:12px;padding:.9rem .5rem;background:#fff">
                            <div class="mb-1"><span class="ds-badge-{{ $t['tone'] }}">●</span></div>
                            <div style="font-size:1.3rem;font-weight:800;color:#0f172a;line-height:1">{{ $t['v'] }}</div>
                            <div class="text-muted" style="font-size:.74rem">{{ $t['l'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
