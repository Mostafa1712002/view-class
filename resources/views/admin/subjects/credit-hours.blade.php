@extends('layouts.app')

@section('title', __('sprint4.subjects.credit_hours_page.title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $undefinedLabel = __('sprint4.subjects.credit_hours_page.undefined');
    $gradeLabel = $selectedLevel > 0 ? ($gradeOptions[$selectedLevel] ?? null) : null;
@endphp

@push('styles')
<style>
    .cv-header { margin-bottom: 1.25rem; }
    .cv-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .cv-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .cv-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }
    .cv-help { color: #64748b; font-size: .9rem; max-width: 720px; line-height: 1.55; }

    .cv-picker {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .cv-picker .lbl {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a; margin-bottom: .6rem;
    }
    .cv-picker .lbl i { color: var(--gold-400); font-size: 1.1rem; }
    .cv-picker .row-flex {
        display: flex; flex-wrap: wrap; gap: .55rem; align-items: stretch;
    }
    .cv-picker select.form-control {
        flex: 1 1 320px; min-width: 0; max-width: 480px;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .9rem; font-size: .95rem; color: #0f172a;
    }
    .cv-picker select.form-control:focus {
        border-color: var(--gold-300);
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }

    .cv-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .cv-table { width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
    .cv-table thead th {
        background: #f8fafc; color: #475569;
        font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
        padding: .8rem 1rem; border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    .cv-table tbody td {
        padding: .85rem 1rem; border-bottom: 1px solid #f1f5f9;
        font-size: .94rem; color: #0f172a; vertical-align: middle;
    }
    .cv-table tbody tr:last-child td { border-bottom: 0; }
    .cv-table tbody tr:hover { background: #fafbfc; }

    .cv-name { font-weight: 600; color: #0f172a; }
    .cv-name small { color: #64748b; font-weight: 400; }
    .cv-pill {
        display: inline-block; padding: .2rem .6rem; border-radius: 999px;
        font-size: .78rem; font-weight: 600;
        background: #f1f5f9; color: #475569;
    }
    .cv-pill.muted { background: #fef9c3; color: #92400e; }

    .cv-input {
        width: 110px; padding: .45rem .6rem; border: 1px solid #e2e8f0; border-radius: 8px;
        font-size: .92rem; text-align: center; background: #fff;
    }
    .cv-input:focus { border-color: var(--gold-300); box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none; }

    .cv-computed { font-weight: 700; color: #0f172a; }
    .cv-computed.zero { color: #94a3b8; font-weight: 600; }

    .cv-switch { position: relative; display: inline-block; width: 46px; height: 26px; }
    .cv-switch input { opacity: 0; width: 0; height: 0; }
    .cv-slider {
        position: absolute; cursor: pointer; inset: 0;
        background: #cbd5e1; border-radius: 999px; transition: background .2s ease;
    }
    .cv-slider::before {
        content: ""; position: absolute; width: 20px; height: 20px;
        left: 3px; top: 3px; background: #fff; border-radius: 50%;
        transition: transform .2s ease; box-shadow: 0 1px 3px rgba(0,0,0,.18);
    }
    .cv-switch input:checked + .cv-slider { background: var(--gold-400, #cfa046); }
    .cv-switch input:checked + .cv-slider::before { transform: translateX({{ $isRtl ? '-20px' : '20px' }}); }

    .cv-empty { padding: 2.5rem 1rem; text-align: center; color: #64748b; }
    .cv-empty i { font-size: 2.4rem; color: #cbd5e1; margin-bottom: .5rem; display: block; }

    .cv-footer {
        display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;
        padding: .9rem 1.1rem; background: #fafbfc; border-top: 1px solid #f1f5f9;
    }
    .cv-footer .summary { color: #64748b; font-size: .88rem; }
    .cv-footer .btn-primary { background: var(--gold-500, #b88735); border-color: var(--gold-500, #b88735); padding: .55rem 1.3rem; font-weight: 600; }
    .cv-footer .btn-primary:hover { background: var(--gold-600, #9a6f25); border-color: var(--gold-600, #9a6f25); }

    @media (max-width: 575.98px) {
        .cv-table thead { display: none; }
        .cv-table tbody td { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .cv-table tbody td::before {
            content: attr(data-label); font-weight: 700; color: #475569;
            font-size: .78rem; text-transform: uppercase;
        }
        .cv-table tbody tr { display: block; border-bottom: 1px solid #f1f5f9; }
    }
</style>
@endpush

@section('content')
<div class="content-header row cv-header">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title">@lang('sprint4.subjects.credit_hours_page.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.subjects.credit_hours_page.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="cv-help">@lang('sprint4.subjects.credit_hours_page.help')</p>

    <form method="GET" action="{{ route('admin.subjects.credit-hours') }}" class="cv-picker">
        <div class="lbl">
            <i class="la la-graduation-cap"></i>
            @lang('sprint4.subjects.credit_hours_page.pick_grade')
        </div>
        <div class="row-flex">
            <select name="grade_level" class="form-control" onchange="this.form.submit()">
                <option value="">@lang('sprint4.subjects.credit_hours_page.pick_grade_placeholder')</option>
                @foreach($gradeOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedLevel === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <noscript>
                <button type="submit" class="btn btn-primary"><i class="la la-search"></i> @lang('common.search')</button>
            </noscript>
        </div>
    </form>

    @if($selectedLevel === 0)
        <div class="cv-card">
            <div class="cv-empty">
                <i class="la la-arrow-up"></i>
                <div>@lang('sprint4.subjects.credit_hours_page.choose_first')</div>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('admin.subjects.credit-hours.save') }}">
            @csrf @method('PATCH')
            <input type="hidden" name="grade_level" value="{{ $selectedLevel }}">

            <div class="cv-card">
                <div class="table-responsive">
                    <table class="cv-table">
                        <thead>
                            <tr>
                                <th>@lang('sprint4.subjects.columns.name')</th>
                                <th>@lang('sprint4.subjects.credit_hours_page.weekly_lessons')</th>
                                <th>@lang('sprint4.subjects.credit_hours_page.computed_value')</th>
                                <th style="width: 160px; text-align: center;">@lang('sprint4.subjects.credit_hours_page.actual_value')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjects as $subject)
                                @php
                                    $hasHours = !is_null($subject->credit_hours);
                                    $active   = (bool) ($subject->credit_hours_active ?? true);
                                    $computed = ($hasHours && $active) ? (int) $subject->credit_hours : 0;
                                @endphp
                                <tr>
                                    <td data-label="@lang('sprint4.subjects.columns.name')">
                                        <div class="cv-name">
                                            {{ $subject->name }}
                                            @if($gradeLabel)
                                                — {{ $gradeLabel }}
                                            @endif
                                        </div>
                                        @if($subject->name_en)
                                            <small>{{ $subject->name_en }}</small>
                                        @endif
                                    </td>
                                    <td data-label="@lang('sprint4.subjects.credit_hours_page.weekly_lessons')">
                                        <input type="number"
                                            class="cv-input"
                                            name="credit_hours[{{ $subject->id }}]"
                                            min="0" max="20"
                                            value="{{ $hasHours ? $subject->credit_hours : '' }}"
                                            placeholder="{{ $undefinedLabel }}">
                                    </td>
                                    <td data-label="@lang('sprint4.subjects.credit_hours_page.computed_value')">
                                        @if(! $hasHours)
                                            <span class="cv-pill muted">{{ $undefinedLabel }}</span>
                                        @else
                                            <span class="cv-computed {{ $computed === 0 ? 'zero' : '' }}">{{ $computed }}</span>
                                        @endif
                                    </td>
                                    <td data-label="@lang('sprint4.subjects.credit_hours_page.actual_value')" style="text-align: center;">
                                        <label class="cv-switch" title="@lang('sprint4.subjects.credit_hours_page.actual_value')">
                                            <input type="checkbox"
                                                name="credit_hours_active[{{ $subject->id }}]"
                                                value="1"
                                                @checked($active)>
                                            <span class="cv-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="cv-empty">
                                            <i class="la la-info-circle"></i>
                                            <div>@lang('sprint4.subjects.credit_hours_page.empty_for_grade')</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($subjects) > 0)
                    <div class="cv-footer">
                        <div class="summary">
                            @lang('sprint4.subjects.credit_hours_page.showing_grade', ['grade' => $gradeLabel])
                            — <strong>{{ count($subjects) }}</strong>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="la la-save"></i>
                            @lang('sprint4.subjects.credit_hours_page.save')
                        </button>
                    </div>
                @endif
            </div>
        </form>
    @endif
</div>
@endsection
