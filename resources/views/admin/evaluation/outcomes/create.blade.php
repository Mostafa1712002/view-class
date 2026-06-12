@extends('layouts.app')

@section('title', __('evaluation_outcomes.create_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .ev-student-row td input { padding:.3rem .5rem; font-size:.85rem; }
    body.theme-light .ev-student-row td { vertical-align:middle; }
    body.theme-light .ev-add-row { font-size:.82rem; }
    body.theme-light .ev-save-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1.2rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation_outcomes.create_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.evaluations.outcomes.index') }}">@lang('evaluation_outcomes.breadcrumb_index')</a></li>
                <li class="breadcrumb-item active">@lang('evaluation_outcomes.breadcrumb_create')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.evaluations.outcomes.index') }}" class="btn btn-outline-secondary">
            <i class="la la-arrow-right"></i> @lang('evaluation_outcomes.actions.back_to_list')
        </a>
    </div>
</div>

<div class="content-body">
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.evaluations.outcomes.store') }}">
        @csrf

        <div class="row">
            {{-- Left column: test metadata --}}
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">بيانات الاختبار</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">@lang('evaluation_outcomes.fields.test_name') <span class="text-danger">*</span></label>
                                <input type="text" name="test_name" class="form-control @error('test_name') is-invalid @enderror"
                                       value="{{ old('test_name') }}" required>
                                @error('test_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">@lang('evaluation_outcomes.fields.test_type')</label>
                                <input type="text" name="test_type" class="form-control"
                                       value="{{ old('test_type') }}" placeholder="منتصف الفصل / نهاية الفصل">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('evaluation_outcomes.fields.test_date')</label>
                                <input type="date" name="test_date" class="form-control" value="{{ old('test_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('evaluation_outcomes.fields.grade_level')</label>
                                <input type="text" name="grade_level" class="form-control"
                                       value="{{ old('grade_level') }}" placeholder="الصف الأول">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">@lang('evaluation_outcomes.fields.class_label')</label>
                                <input type="text" name="class_label" class="form-control"
                                       value="{{ old('class_label') }}" placeholder="أ / ب / ج">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Students table --}}
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@lang('evaluation_outcomes.fields.students')</h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary ev-add-row" id="add-student-row">
                            <i class="la la-plus"></i> إضافة طالب
                        </button>
                    </div>
                    <div class="card-body p-0">
                        @error('students')<div class="alert alert-danger m-3">{{ $message }}</div>@enderror
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="students-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>@lang('evaluation_outcomes.detail.student_id')</th>
                                        <th>@lang('evaluation_outcomes.detail.score')</th>
                                        <th>@lang('evaluation_outcomes.detail.status')</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="students-body">
                                    @php $oldStudents = old('students', [['student_id'=>'','score'=>'','status'=>'present']]); @endphp
                                    @foreach($oldStudents as $i => $s)
                                    <tr class="ev-student-row">
                                        <td class="text-muted row-num">{{ $i + 1 }}</td>
                                        <td><input type="number" name="students[{{ $i }}][student_id]" class="form-control" value="{{ $s['student_id'] ?? '' }}" min="1" required></td>
                                        <td><input type="number" name="students[{{ $i }}][score]" class="form-control score-input" value="{{ $s['score'] ?? '' }}" min="0" max="100" step="0.01" {{ ($s['status'] ?? 'present') === 'present' ? 'required' : '' }}></td>
                                        <td>
                                            <select name="students[{{ $i }}][status]" class="form-select status-select">
                                                <option value="present" {{ ($s['status'] ?? '') === 'present' ? 'selected' : '' }}>@lang('evaluation_outcomes.detail.present')</option>
                                                <option value="absent"  {{ ($s['status'] ?? '') === 'absent'  ? 'selected' : '' }}>@lang('evaluation_outcomes.detail.absent')</option>
                                            </select>
                                        </td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="la la-times"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right column: method + submit --}}
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">@lang('evaluation_outcomes.methods.label')</h5></div>
                    <div class="card-body">
                        <label class="form-label">@lang('evaluation_outcomes.fields.method') <small class="text-muted">(اختياري — سيُستخدم إعداد المدرسة إن تُرك فارغاً)</small></label>
                        <select name="method" class="form-select">
                            <option value="">— إعداد المدرسة —</option>
                            @foreach($methods as $val => $label)
                                <option value="{{ $val }}" {{ old('method') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>

                        <div class="alert alert-warning mt-3 py-2" style="font-size:.82rem;">
                            <i class="la la-info-circle"></i>
                            @lang('evaluation_outcomes.settings.method_warning')
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn ev-save-btn w-100">
                            <i class="la la-calculator"></i> @lang('evaluation_outcomes.actions.save')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    let rowIndex = {{ count(old('students', [['student_id'=>'','score'=>'','status'=>'present']])) }};

    function rowHtml(i) {
        return `<tr class="ev-student-row">
            <td class="text-muted row-num">${i + 1}</td>
            <td><input type="number" name="students[${i}][student_id]" class="form-control" min="1" required></td>
            <td><input type="number" name="students[${i}][score]" class="form-control score-input" min="0" max="100" step="0.01" required></td>
            <td>
                <select name="students[${i}][status]" class="form-select status-select">
                    <option value="present">@lang('evaluation_outcomes.detail.present')</option>
                    <option value="absent">@lang('evaluation_outcomes.detail.absent')</option>
                </select>
            </td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="la la-times"></i></button></td>
        </tr>`;
    }

    document.getElementById('add-student-row').addEventListener('click', function () {
        document.getElementById('students-body').insertAdjacentHTML('beforeend', rowHtml(rowIndex++));
        renumber();
    });

    document.getElementById('students-body').addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (btn) {
            const rows = document.querySelectorAll('#students-body tr');
            if (rows.length <= 1) return; // keep at least one row
            btn.closest('tr').remove();
            renumber();
        }
    });

    // An absent student has no score: clear it + drop the (HTML5 + server) requirement.
    // A present student must have a score.
    function syncScore(statusSelect) {
        const row = statusSelect.closest('tr');
        const score = row && row.querySelector('.score-input');
        if (!score) return;
        if (statusSelect.value === 'absent') {
            score.value = '';
            score.required = false;
            score.readOnly = true;
            score.classList.add('bg-light');
        } else {
            score.required = true;
            score.readOnly = false;
            score.classList.remove('bg-light');
        }
    }
    document.getElementById('students-body').addEventListener('change', function (e) {
        if (e.target.classList.contains('status-select')) syncScore(e.target);
    });
    // Initialise existing rows on load.
    document.querySelectorAll('#students-body .status-select').forEach(syncScore);

    function renumber() {
        document.querySelectorAll('#students-body tr').forEach(function (tr, idx) {
            const num = tr.querySelector('.row-num');
            if (num) num.textContent = idx + 1;
            tr.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/students\[\d+\]/, `students[${idx}]`);
            });
        });
        rowIndex = document.querySelectorAll('#students-body tr').length;
    }
})();
</script>
@endpush
