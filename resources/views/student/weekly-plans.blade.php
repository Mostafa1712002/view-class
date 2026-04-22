@extends('layouts.admin')

@section('title', __('shell.nav_weekly_plan'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">الخطط الأسبوعية</h1>
            <small class="text-muted">{{ $class?->name ?? 'غير مسجل في صف' }}</small>
        </div>
    </div>

    {{-- Week Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">الأسبوع</label>
                    <input type="week" name="week" class="form-control"
                           value="{{ $selectedWeek ?? now()->format('Y-\\WW') }}"
                           onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    @if($weeklyPlans && $weeklyPlans->count() > 0)
        @foreach($weeklyPlans as $plan)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ $plan->subject->name ?? '-' }}</h5>
                        <small class="text-muted">{{ $plan->teacher->name ?? '-' }}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-info">{{ $plan->week_start?->format('Y-m-d') }} - {{ $plan->week_end?->format('Y-m-d') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">الأهداف</h6>
                            <p class="mb-0">{{ $plan->objectives ?? '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">المحتوى</h6>
                            <p class="mb-0">{{ $plan->content ?? '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">الأنشطة</h6>
                            <p class="mb-0">{{ $plan->activities ?? '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">الواجبات</h6>
                            <p class="mb-0">{{ $plan->homework ?? '-' }}</p>
                        </div>
                        @if($plan->resources)
                        <div class="col-12">
                            <h6 class="text-primary">الموارد والمراجع</h6>
                            <p class="mb-0">{{ $plan->resources }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-journal-text display-1 text-muted"></i>
                <p class="mt-3 text-muted">لا توجد خطط أسبوعية لهذا الأسبوع</p>
            </div>
        </div>
    @endif
</div>
@endsection
