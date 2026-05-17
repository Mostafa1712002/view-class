@extends('layouts.app')

@section('title', trans('grades_admin.grades_title'))

@section('body_class', 'theme-light')

@section('content')
@php($isRtl = app()->getLocale() === 'ar')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            {{ trans('grades_admin.grades_title') }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('grades_admin.home')</a></li>
                <li class="breadcrumb-item active">{{ trans('grades_admin.grades_title') }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12">
        <a href="{{ route('admin.grade-reports.index') }}" class="btn btn-outline-primary">
            <i class="la la-file-alt"></i> {{ trans('grades_admin.reports_title') }}
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-info"><i class="la la-info-circle"></i> {{ trans('grades_admin.grades_intro') }}</div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.grades.entry.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ trans('grades_admin.pick_report') }}</label>
                    <select name="report_id" class="form-control" required>
                        <option value="">{{ trans('grades_admin.pick_report') }}</option>
                        @foreach($reports as $r)
                            <option value="{{ $r->id }}" @selected(($selected['report_id'] ?? null) == $r->id)>
                                {{ $r->title }}
                                @if($r->classRoom) — {{ $r->classRoom->name }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ trans('grades_admin.pick_class') }}</label>
                    <select name="class_id" class="form-control">
                        <option value="">{{ trans('grades_admin.pick') }}</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected(($selected['class_id'] ?? null) == $c->id)>
                                {{ $c->name }} ({{ $c->grade_level }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ trans('grades_admin.pick_subject') }}</label>
                    <select name="subject_id" class="form-control">
                        <option value="">{{ trans('grades_admin.pick') }}</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(($selected['subject_id'] ?? null) == $s->id)>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="la la-search"></i> {{ trans('grades_admin.show_table') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($report)
        @if($report->is_locked)
            <div class="alert alert-warning"><i class="la la-lock"></i> {{ trans('grades_admin.report_locked') }}</div>
        @endif

        @if($columns->isEmpty())
            <div class="alert alert-warning">{{ trans('grades_admin.no_columns') }}
                <a href="{{ route('admin.grade-reports.edit', $report->id) }}" class="alert-link">{{ trans('grades_admin.edit_report') }}</a>
            </div>
        @elseif($students->isEmpty())
            <div class="alert alert-secondary">{{ trans('grades_admin.no_students') }}</div>
        @else
            <form method="POST" action="{{ route('admin.grades.entry.store') }}">
                @csrf
                <input type="hidden" name="report_id" value="{{ $report->id }}">
                <input type="hidden" name="class_id" value="{{ $selected['class_id'] }}">
                <input type="hidden" name="subject_id" value="{{ $selected['subject_id'] }}">

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            {{ $report->title }}
                            @if($selected['class_id']) — {{ optional($classes->firstWhere('id', $selected['class_id']))->name }} @endif
                        </h4>
                        @php($totalWeight = $columns->where('is_in_total', true)->sum('weight'))
                        <small class="text-muted">{{ trans('grades_admin.component_weight') }}: {{ rtrim(rtrim(number_format($totalWeight, 2, '.', ''), '0'), '.') }}%</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2" style="width:50px;">{{ trans('grades_admin.student_id') }}</th>
                                        <th rowspan="2">{{ trans('grades_admin.student_name') }}</th>
                                        @foreach($columns as $col)
                                            <th class="text-center" style="min-width:90px;">
                                                {{ $col->title }}<br>
                                                <small class="text-muted">{{ rtrim(rtrim(number_format($col->max_score, 2, '.', ''), '0'), '.') }}</small>
                                            </th>
                                        @endforeach
                                        <th rowspan="2" class="text-center" style="width:90px;">{{ trans('grades_admin.total') }}</th>
                                        <th rowspan="2" class="text-center" style="width:80px;">{{ trans('grades_admin.percentage') }}</th>
                                    </tr>
                                    <tr>
                                        @foreach($columns as $col)
                                            <th class="text-center"><small class="text-muted">{{ rtrim(rtrim(number_format($col->weight, 2, '.', ''), '0'), '.') }}%</small></th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $i => $student)
                                        <tr data-student-row="{{ $student->id }}">
                                            <td class="text-center">{{ $i + 1 }}</td>
                                            <td>{{ $student->name }}</td>
                                            @foreach($columns as $col)
                                                @php($key = $student->id.'-'.$col->id)
                                                @php($current = $values[$key] ?? null)
                                                <td>
                                                    <input type="number"
                                                           name="rows[{{ $student->id }}][{{ $col->id }}]"
                                                           class="form-control form-control-sm text-center grade-cell"
                                                           data-max="{{ $col->max_score }}"
                                                           data-weight="{{ $col->weight }}"
                                                           data-intotal="{{ $col->is_in_total ? 1 : 0 }}"
                                                           data-student="{{ $student->id }}"
                                                           min="0"
                                                           step="0.5"
                                                           max="{{ $col->max_score }}"
                                                           value="{{ $current ? rtrim(rtrim(number_format($current->score, 2, '.', ''), '0'), '.') : '' }}"
                                                           {{ $report->is_locked ? 'disabled' : '' }}>
                                                </td>
                                            @endforeach
                                            <td class="text-center fw-bold student-total" id="total-{{ $student->id }}">—</td>
                                            <td class="text-center student-pct" id="pct-{{ $student->id }}">—</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if(!$report->is_locked)
                    <div class="card-footer text-{{ $isRtl ? 'left' : 'right' }}">
                        <button type="submit" class="btn btn-success">
                            <i class="la la-save"></i> {{ trans('grades_admin.save_all') }}
                        </button>
                    </div>
                    @endif
                </div>
            </form>

            @push('scripts')
            <script>
            (function () {
                var cells = document.querySelectorAll('.grade-cell');

                function recalcStudent(studentId) {
                    var totalScore = 0;
                    var maxIfFull = 0;
                    document.querySelectorAll('.grade-cell[data-student="' + studentId + '"]').forEach(function (el) {
                        var w = parseFloat(el.getAttribute('data-weight')) || 0;
                        var max = parseFloat(el.getAttribute('data-max')) || 0;
                        var inTotal = el.getAttribute('data-intotal') === '1';
                        if (!inTotal) return;
                        var v = parseFloat(el.value);
                        if (max > 0 && w > 0) {
                            maxIfFull += w;
                            if (!isNaN(v)) {
                                totalScore += (v / max) * w;
                            }
                        } else if (!isNaN(v)) {
                            totalScore += v;
                            maxIfFull += max;
                        }
                    });
                    var totalEl = document.getElementById('total-' + studentId);
                    var pctEl = document.getElementById('pct-' + studentId);
                    if (totalEl) {
                        totalEl.textContent = totalScore > 0 ? (Math.round(totalScore * 100) / 100) : '—';
                    }
                    if (pctEl) {
                        if (maxIfFull > 0 && totalScore > 0) {
                            var pct = (totalScore / maxIfFull) * 100;
                            pctEl.textContent = (Math.round(pct * 10) / 10) + '%';
                        } else {
                            pctEl.textContent = '—';
                        }
                    }
                }

                cells.forEach(function (el) {
                    el.addEventListener('input', function () {
                        recalcStudent(el.getAttribute('data-student'));
                    });
                });
                // initial paint
                var seen = {};
                cells.forEach(function (el) {
                    var sid = el.getAttribute('data-student');
                    if (seen[sid]) return;
                    seen[sid] = true;
                    recalcStudent(sid);
                });
            })();
            </script>
            @endpush
        @endif
    @endif
</div>
@endsection
