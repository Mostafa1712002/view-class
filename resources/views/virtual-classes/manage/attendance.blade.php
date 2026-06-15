@extends('layouts.app')

@section('title', __('virtual_classes.attendance_title') . ' — ' . $vc->title)
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $u     = auth()->user();
    $statusColors = [
        'present' => 'success',
        'absent'  => 'danger',
        'late'    => 'warning',
        'partial' => 'info',
    ];
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('virtual_classes.attendance_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('virtual_classes.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.virtual-classes.index') }}">@lang('virtual_classes.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.virtual-classes.show', $vc->id) }}">{{ Str::limit($vc->title, 30) }}</a></li>
                <li class="breadcrumb-item active">@lang('virtual_classes.attendance_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-4 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        @if($u->canDo('virtual_classes.recalc_attendance'))
        <form method="POST" action="{{ route('manage.virtual-classes.attendance.recalc', $vc->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <x-svg-icon name="arrow-repeat" size="16" /> @lang('virtual_classes.btn_recalc')
            </button>
        </form>
        @endif
        @if($u->canDo('virtual_classes.view_attendance'))
        <a href="{{ route('manage.virtual-classes.attendance.export', $vc->id) }}" class="btn btn-outline-success">
            <x-svg-icon name="file-earmark-spreadsheet" size="16" /> @lang('virtual_classes.btn_export')
        </a>
        @endif
        @if($u->canDo('virtual_classes.clear_cache'))
        <form method="POST" action="{{ route('manage.virtual-classes.cache.clear', $vc->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">
                <x-svg-icon name="trash" size="16" /> @lang('virtual_classes.btn_clear_cache')
            </button>
        </form>
        @endif
    </div>
</div>

{{-- Summary cards (from cached recalc) --}}
@if($summary)
<div class="row mb-2">
    @foreach(['present','late','partial','absent'] as $st)
    <div class="col-6 col-md-3">
        <div class="card border-{{ $statusColors[$st] }}">
            <div class="card-body py-2 text-center">
                <div class="h4 mb-0 text-{{ $statusColors[$st] }}">{{ $summary[$st] ?? 0 }}</div>
                <small class="text-muted">@lang('virtual_classes.summary_' . $st)</small>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="alert alert-info">
    <x-svg-icon name="info-circle" size="16" /> @lang('virtual_classes.summary_none')
</div>
@endif

<div class="card">
    <div class="card-content">
        <div class="card-body pb-1">
            <input type="text" id="vcAttSearch" class="form-control"
                   placeholder="@lang('virtual_classes.attendance_search')">
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0" id="vcAttTable">
                <thead>
                    <tr>
                        <th>@lang('virtual_classes.att_student')</th>
                        <th>@lang('virtual_classes.att_teacher')</th>
                        <th>@lang('virtual_classes.att_subject')</th>
                        <th>@lang('virtual_classes.att_class')</th>
                        <th>@lang('virtual_classes.att_joined')</th>
                        <th>@lang('virtual_classes.att_duration')</th>
                        <th>@lang('virtual_classes.att_status')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendees as $a)
                    <tr>
                        <td class="vc-att-name">
                            {{ $isRtl && optional($a->student)->name_ar ? $a->student->name_ar : optional($a->student)->name }}
                        </td>
                        <td>{{ optional($vc->teacher)->name }}</td>
                        <td>{{ optional($vc->subject)->name ?? '—' }}</td>
                        <td>{{ optional($vc->classRoom)->name ?? '—' }}</td>
                        <td class="text-nowrap">{{ optional($a->joined_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $a->duration_minutes }}</td>
                        <td>
                            @if($a->attendance_status)
                                <span class="badge badge-{{ $statusColors[$a->attendance_status] ?? 'secondary' }}">
                                    @lang('virtual_classes.att_' . $a->attendance_status)
                                </span>
                            @else
                                <span class="badge badge-light">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <x-svg-icon name="people" size="28" class="d-block mx-auto mb-2 text-muted" />
                            @lang('virtual_classes.attendance_empty')
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('vcAttSearch')?.addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    document.querySelectorAll('#vcAttTable tbody tr').forEach(function (row) {
        var name = (row.querySelector('.vc-att-name')?.textContent || '').toLowerCase();
        row.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
    });
});
</script>
@endpush
@endsection
