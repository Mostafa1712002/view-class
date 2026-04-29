@extends('layouts.app')

@section('title', __('shell.nav_weekly_plan'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $daysAr = ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس'];
    $plansByDay = $weekPlans->groupBy(fn($p) => \Carbon\Carbon::parse($p->week_start_date)->dayOfWeekIso % 7);
    // Carbon dayOfWeekIso: 7=Sun, 1=Mon ... we want Sun=0, Mon=1 ... Thu=4
    // weekly_plans.week_start_date IS the week start (Sunday). Plan applies to whole week.
    // So we'll just show one column per day of the week and group plans by their subject's class.
    // For MVP: show plans as card list; days header shown for spec parity.
@endphp

<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">@lang('shell.nav_weekly_plan')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('shell.nav_weekly_plan')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        <a href="{{ route('manage.weekly-plans.create') }}" class="btn btn-primary"><i class="la la-plus"></i> @lang('common.create') خطة</a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Filter + week navigator --}}
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">البحث المتقدم</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('manage.weekly-plans.index') }}" class="row g-2">
                <input type="hidden" name="view" value="grid">
                <div class="col-md-2">
                    <label class="form-label small">المرحلة</label>
                    <select name="grade_level" class="form-control form-control-sm">
                        <option value="">— الكل —</option>
                        @foreach($gradeLevels as $g)
                            <option value="{{ $g }}" @selected(request('grade_level') == $g)>الصف {{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">الفصل</label>
                    <select name="class_id" class="form-control form-control-sm">
                        <option value="">— الكل —</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">المعلم</label>
                    <select name="teacher_id" class="form-control form-control-sm">
                        <option value="">— الكل —</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" @selected(request('teacher_id') == $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">المادة</label>
                    <select name="subject_id" class="form-control form-control-sm">
                        <option value="">— الكل —</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(request('subject_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">الحالة</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">— الكل —</option>
                        <option value="prepared" @selected(request('status') === 'prepared')>تم التحضير</option>
                        <option value="not_prepared" @selected(request('status') === 'not_prepared')>لم يتم التحضير</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-sm w-100"><i class="la la-search"></i> بحث</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Week navigator --}}
    <div class="card">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['week_start' => $weekStart->copy()->subWeek()->toDateString()])) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> الأسبوع السابق
                    </a>
                    <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['week_start' => now()->startOfWeek(\Carbon\Carbon::SUNDAY)->toDateString()])) }}" class="btn btn-outline-primary btn-sm mx-1">
                        الأسبوع الحالي
                    </a>
                    <a href="{{ route('manage.weekly-plans.index', array_merge(request()->query(), ['week_start' => $weekStart->copy()->addWeek()->toDateString()])) }}" class="btn btn-outline-secondary btn-sm">
                        الأسبوع التالي <i class="la la-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
                    </a>
                </div>
                <div class="text-muted">
                    <strong>الأسبوع:</strong> من {{ $weekStart->format('Y-m-d') }} إلى {{ $weekEnd->format('Y-m-d') }}
                </div>
                <div>
                    <a href="{{ route('manage.weekly-plans.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm"><i class="la la-file-pdf"></i> تصوير PDF</a>
                    <button type="button" class="btn btn-outline-success btn-sm" disabled title="قيد التطوير"><i class="la la-file-excel"></i> تحميل Excel</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="قيد التطوير"><i class="la la-cog"></i> تخصيص الأعمدة</button>
                    <a href="{{ route('manage.weekly-plans.index', ['view' => 'list']) }}" class="btn btn-outline-info btn-sm"><i class="la la-list"></i> عرض كقائمة</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Grid: each plan as a card, organised by class+subject --}}
    @if($weekPlans->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="la la-calendar-times la-3x text-muted"></i>
                <p class="mt-3 mb-0">لا توجد خطط للأسبوع المحدد. <a href="{{ route('manage.weekly-plans.create') }}">إضافة خطة جديدة</a></p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">خطط الأسبوع ({{ $weekPlans->count() }})</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px;">الحالة</th>
                                <th>المعلم</th>
                                <th>المادة</th>
                                <th>الفصل</th>
                                <th>الأهداف</th>
                                <th>الواجبات والمهام</th>
                                <th>الملاحظات</th>
                                <th class="text-center" style="width:140px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($weekPlans as $plan)
                                <tr>
                                    <td class="text-center">
                                        @if($plan->is_prepared)
                                            <span class="badge badge-success" title="تم التحضير">
                                                <i class="la la-check-circle"></i>
                                            </span>
                                        @else
                                            <span class="badge badge-warning" title="لم يتم التحضير">
                                                <i class="la la-circle"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $plan->teacher?->name }}</td>
                                    <td>{{ $plan->subject?->name }}</td>
                                    <td>{{ $plan->classRoom?->name }}</td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($plan->objectives, 60) }}</small></td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($plan->homework, 50) }}</small></td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($plan->notes, 50) }}</small></td>
                                    <td class="text-center">
                                        <form action="{{ route('manage.weekly-plans.mark-prepared', $plan) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-{{ $plan->is_prepared ? 'warning' : 'success' }}" title="{{ $plan->is_prepared ? 'إلغاء التحضير' : 'تعليم كمُحضّر' }}">
                                                <i class="la la-{{ $plan->is_prepared ? 'undo' : 'check' }}"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('manage.weekly-plans.show', $plan) }}" class="btn btn-sm btn-outline-info"><i class="la la-eye"></i></a>
                                        <a href="{{ route('manage.weekly-plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary"><i class="la la-edit"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Footer note --}}
        <div class="alert alert-light border mt-2">
            <small>
                <strong>نشاط:</strong>
                <i class="la la-circle text-warning"></i> دائرة صفراء = لم يتم التحضير &nbsp;
                <i class="la la-check-circle text-success"></i> دائرة خضراء = تم التحضير
            </small>
        </div>
    @endif
</div>
@endsection
