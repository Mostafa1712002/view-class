@extends('layouts.app')

@section('title', trans('grades_admin.edit_report'))

@section('body_class', 'theme-light')

@section('content')
@php($isRtl = app()->getLocale() === 'ar')
<div class="content-header">
    <h2 class="content-header-title">{{ trans('grades_admin.edit_report') }} — {{ $report->title }}</h2>
    <div class="breadcrumb-wrapper">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.grade-reports.index') }}">{{ trans('grades_admin.reports_title') }}</a></li>
            <li class="breadcrumb-item active">{{ $report->title }}</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Report metadata --}}
    <form method="POST" action="{{ route('admin.grade-reports.update', $report->id) }}">
        @csrf @method('PUT')
        @include('admin.grade-reports._form', ['report' => $report])
        <div class="text-{{ $isRtl ? 'left' : 'right' }} mt-3 mb-4">
            <a href="{{ route('admin.grade-reports.index') }}" class="btn btn-outline-secondary">
                <i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i> {{ trans('grades_admin.back') }}
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="la la-save"></i> {{ trans('grades_admin.save') }}
            </button>
        </div>
    </form>

    {{-- Components builder --}}
    <div class="card mt-4" id="components-builder">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('grades_admin.components_title') }}</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-light">{{ trans('grades_admin.components_intro') }}</div>

            <form method="POST" action="{{ route('admin.grade-reports.columns.update', $report->id) }}" id="components-form">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="components-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>{{ trans('grades_admin.component_title') }}</th>
                                <th style="width:120px;">{{ trans('grades_admin.component_weight') }}</th>
                                <th style="width:120px;">{{ trans('grades_admin.component_max') }}</th>
                                <th style="width:120px;">{{ trans('grades_admin.component_pass') }}</th>
                                <th class="text-center" style="width:100px;">{{ trans('grades_admin.component_in_total') }}</th>
                                <th class="text-center" style="width:100px;">{{ trans('grades_admin.component_visible') }}</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="components-body">
                            @foreach($report->columns as $i => $col)
                                <tr class="component-row">
                                    <td class="text-center row-index">{{ $i + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="columns[{{ $i }}][id]" value="{{ $col->id }}">
                                        <input type="text" name="columns[{{ $i }}][title]" class="form-control form-control-sm" value="{{ $col->title }}" required>
                                    </td>
                                    <td>
                                        <input type="number" name="columns[{{ $i }}][weight]" class="form-control form-control-sm col-weight" value="{{ rtrim(rtrim(number_format($col->weight, 2, '.', ''), '0'), '.') }}" min="0" step="0.5">
                                    </td>
                                    <td>
                                        <input type="number" name="columns[{{ $i }}][max_score]" class="form-control form-control-sm" value="{{ rtrim(rtrim(number_format($col->max_score, 2, '.', ''), '0'), '.') }}" min="0" step="0.5">
                                    </td>
                                    <td>
                                        <input type="number" name="columns[{{ $i }}][pass_threshold]" class="form-control form-control-sm" value="{{ $col->pass_threshold !== null ? rtrim(rtrim(number_format($col->pass_threshold, 2, '.', ''), '0'), '.') : '' }}" min="0" step="0.5" placeholder="—">
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" name="columns[{{ $i }}][is_in_total]" value="0">
                                        <input type="checkbox" name="columns[{{ $i }}][is_in_total]" value="1" @checked($col->is_in_total)>
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" name="columns[{{ $i }}][is_visible]" value="0">
                                        <input type="checkbox" name="columns[{{ $i }}][is_visible]" value="1" @checked($col->is_visible)>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="{{ trans('grades_admin.remove_component') }}">
                                            <i class="la la-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- A hidden seed row template — JS clones this for new rows (safer than innerHTML). --}}
                <template id="component-row-template">
                    <tr class="component-row">
                        <td class="text-center row-index"></td>
                        <td>
                            <input type="hidden" data-name="columns[__i__][id]" value="">
                            <input type="text" data-name="columns[__i__][title]" class="form-control form-control-sm" required>
                        </td>
                        <td><input type="number" data-name="columns[__i__][weight]" class="form-control form-control-sm col-weight" value="0" min="0" step="0.5"></td>
                        <td><input type="number" data-name="columns[__i__][max_score]" class="form-control form-control-sm" value="100" min="0" step="0.5"></td>
                        <td><input type="number" data-name="columns[__i__][pass_threshold]" class="form-control form-control-sm" min="0" step="0.5" placeholder="—"></td>
                        <td class="text-center">
                            <input type="hidden" data-name="columns[__i__][is_in_total]" value="0">
                            <input type="checkbox" data-name="columns[__i__][is_in_total]" value="1" checked>
                        </td>
                        <td class="text-center">
                            <input type="hidden" data-name="columns[__i__][is_visible]" value="0">
                            <input type="checkbox" data-name="columns[__i__][is_visible]" value="1" checked>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="{{ trans('grades_admin.remove_component') }}">
                                <i class="la la-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>

                <div class="d-flex align-items-center mt-2 gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-row">
                        <i class="la la-plus"></i> {{ trans('grades_admin.add_component') }}
                    </button>
                    <div class="ms-auto">
                        <span class="text-muted">{{ trans('grades_admin.component_weight') }}: </span>
                        <strong id="weight-total">0</strong>%
                        <span id="weight-warning" class="badge badge-warning d-none ms-2">{{ trans('grades_admin.weight_warning') }}</span>
                    </div>
                </div>

                <div class="text-{{ $isRtl ? 'left' : 'right' }} mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="la la-save"></i> {{ trans('grades_admin.save') }} — {{ trans('grades_admin.components_title') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var body = document.getElementById('components-body');
    var addBtn = document.getElementById('add-row');
    var weightTotalEl = document.getElementById('weight-total');
    var weightWarning = document.getElementById('weight-warning');
    var tpl = document.getElementById('component-row-template');

    function reindex() {
        var rows = body.querySelectorAll('tr.component-row');
        rows.forEach(function (row, idx) {
            row.querySelector('.row-index').textContent = String(idx + 1);
            row.querySelectorAll('input, select').forEach(function (el) {
                if (!el.name) return;
                el.name = el.name.replace(/columns\[\d+\]/, 'columns[' + idx + ']');
            });
        });
    }

    function recalc() {
        var total = 0;
        body.querySelectorAll('.col-weight').forEach(function (el) {
            var v = parseFloat(el.value);
            if (!isNaN(v)) total += v;
        });
        var rounded = Math.round(total * 100) / 100;
        weightTotalEl.textContent = String(rounded);
        if (rounded !== 100 && rounded !== 0) {
            weightWarning.classList.remove('d-none');
        } else {
            weightWarning.classList.add('d-none');
        }
    }

    function bindRow(row) {
        var btn = row.querySelector('.remove-row');
        if (btn) {
            btn.addEventListener('click', function () {
                row.remove();
                reindex();
                recalc();
            });
        }
        var wEl = row.querySelector('.col-weight');
        if (wEl) wEl.addEventListener('input', recalc);
    }

    body.querySelectorAll('tr.component-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
        var idx = body.querySelectorAll('tr.component-row').length;
        var clone = tpl.content.firstElementChild.cloneNode(true);
        clone.querySelector('.row-index').textContent = String(idx + 1);
        clone.querySelectorAll('[data-name]').forEach(function (el) {
            el.name = el.getAttribute('data-name').replace('__i__', String(idx));
            el.removeAttribute('data-name');
        });
        body.appendChild(clone);
        bindRow(clone);
        recalc();
    });

    recalc();
})();
</script>
@endpush
@endsection
