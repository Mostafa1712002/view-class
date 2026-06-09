{{--
    Shared filter card for evaluation reports.
    $mode: 'supervisors' | 'supervisors_detailed' | 'general_manager'
    Option collections are passed from the controller; each is optional and
    rendered only when provided.
--}}
@php
    $action = match ($mode) {
        'supervisors'          => route('admin.eval-reports.supervisors'),
        'supervisors_detailed' => route('admin.eval-reports.supervisors-detailed'),
        'general_manager'      => route('admin.eval-reports.general-manager'),
    };
    $opt = fn ($v) => ($filters[$v] ?? null);
@endphp
<form action="{{ $action }}" method="GET" class="card filters-card p-3 mb-3 no-print">
    <div class="row g-2 align-items-end">
        {{-- Form --}}
        @isset($formOptions)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.form')</label>
            <select name="form" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($formOptions as $f)
                    <option value="{{ $f->id }}" {{ (int)$opt('form') === $f->id ? 'selected' : '' }}>{{ $f->title }}</option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- School (super-admin only) --}}
        @isset($schoolOptions)
        @if ($schoolOptions->count() > 0)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.school')</label>
            <select name="school" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($schoolOptions as $s)
                    <option value="{{ $s->id }}" {{ (int)$opt('school') === $s->id ? 'selected' : '' }}>{{ $s->name_ar ?: $s->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @endisset

        {{-- Subject --}}
        @isset($subjectOptions)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.subject')</label>
            <select name="subject" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($subjectOptions as $s)
                    <option value="{{ $s->id }}" {{ (int)$opt('subject') === $s->id ? 'selected' : '' }}>{{ $s->name_ar ?: $s->name }}</option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- Supervisor (supervisor reports) --}}
        @isset($supervisorOptions)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.supervisor')</label>
            <select name="supervisor" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($supervisorOptions as $u)
                    <option value="{{ $u->id }}" {{ (int)$opt('supervisor') === $u->id ? 'selected' : '' }}>{{ $u->name_ar ?: $u->name }}</option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- Teacher (GM) --}}
        @isset($teacherOptions)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.teacher')</label>
            <select name="teacher" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($teacherOptions as $u)
                    <option value="{{ $u->id }}" {{ (int)$opt('teacher') === $u->id ? 'selected' : '' }}>{{ $u->name_ar ?: $u->name }}</option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- Evaluator (GM) --}}
        @isset($evaluatorOptions)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.evaluator')</label>
            <select name="evaluator" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($evaluatorOptions as $u)
                    <option value="{{ $u->id }}" {{ (int)$opt('evaluator') === $u->id ? 'selected' : '' }}>{{ $u->name_ar ?: $u->name }}</option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- Specialization (GM) --}}
        @isset($specializationOptions)
        @if ($specializationOptions->count() > 0)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.specialization')</label>
            <select name="specialization" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($specializationOptions as $sp)
                    <option value="{{ $sp }}" {{ $opt('specialization') === $sp ? 'selected' : '' }}>{{ $sp }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @endisset

        {{-- Evaluation status --}}
        @isset($evalStatuses)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.eval_status')</label>
            <select name="eval_status" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($evalStatuses as $val => $label)
                    <option value="{{ $val }}" {{ $opt('eval_status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- Visit status (supervisor reports) --}}
        @isset($visitStatuses)
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.visit_status')</label>
            <select name="visit_status" class="form-control eval-select2">
                <option value="">@lang('eval_reports.all')</option>
                @foreach ($visitStatuses as $val => $label)
                    <option value="{{ $val }}" {{ $opt('visit_status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endisset

        {{-- Score range (GM) --}}
        @if ($mode === 'general_manager')
        <div class="col-md-1 col-6">
            <label class="form-label">@lang('eval_reports.filters.score_from')</label>
            <input type="number" min="0" max="100" step="0.01" name="score_from" value="{{ $opt('score_from') }}" class="form-control">
        </div>
        <div class="col-md-1 col-6">
            <label class="form-label">@lang('eval_reports.filters.score_to')</label>
            <input type="number" min="0" max="100" step="0.01" name="score_to" value="{{ $opt('score_to') }}" class="form-control">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.has_evidence')</label>
            <select name="has_evidence" class="form-control">
                <option value="">@lang('eval_reports.all')</option>
                <option value="1" {{ $opt('has_evidence') === true ? 'selected' : '' }}>@lang('eval_reports.filters.yes')</option>
                <option value="0" {{ $opt('has_evidence') === false ? 'selected' : '' }}>@lang('eval_reports.filters.no')</option>
            </select>
        </div>
        @endif

        {{-- Date range --}}
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.date_from')</label>
            <input type="date" name="date_from" value="{{ $opt('date_from') }}" class="form-control">
        </div>
        <div class="col-md-2 col-6">
            <label class="form-label">@lang('eval_reports.filters.date_to')</label>
            <input type="date" name="date_to" value="{{ $opt('date_to') }}" class="form-control">
        </div>

        <div class="col-md-3 col-12 d-flex gap-1 align-items-end">
            <button type="submit" class="btn ev-add-btn flex-grow-1"><i class="la la-search"></i> @lang('eval_reports.show')</button>
            <a href="{{ $action }}" class="btn btn-outline-secondary" title="@lang('eval_reports.reset')"><i class="la la-redo"></i></a>
        </div>
    </div>
</form>

@push('scripts')
<script>
    (function () {
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.eval-select2').select2({ width: '100%' });
        }
    })();
</script>
@endpush
