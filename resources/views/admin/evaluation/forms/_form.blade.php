@php
    $f = $form ?? null;
    $s = fn (string $key, $default = false) => old('settings.'.$key, $f?->setting($key, $default));
    $val = fn (string $key, $default = null) => old($key, $f?->{$key} ?? $default);
    $typeVal = old('type', $f?->type?->value ?? 'rating_scale');
    $domainVal = old('usage_domain', $f?->usage_domain?->value ?? 'teacher');
    $levelLabels = old('level_labels', $levels ?? []);
    $toggles = [
        'allow_edit', 'allow_subject_view_results', 'allow_subject_comment', 'show_total_avg',
        'hide_percentages', 'class_visit_only', 'require_all_indicators', 'allow_general_notes',
        'allow_item_notes', 'require_evidence_per_flagged_item', 'allow_multiple_evaluators',
        'average_on_multiple', 'links_to_job_performance', 'notify_on_publish', 'notify_on_submit',
        'notify_on_result_available',
    ];
@endphp

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row">
    <div class="col-md-8 col-12 mb-3">
        <label class="form-label">@lang('evaluation.form.fields.title') <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ $val('title') }}" class="form-control" required maxlength="255">
    </div>
    <div class="col-md-4 col-12 mb-3">
        <label class="form-label">@lang('evaluation.form.fields.status')</label>
        <input type="text" class="form-control" value="{{ $f?->status?->label() ?? __('evaluation.form_status.draft') }}" disabled>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">@lang('evaluation.form.fields.description')</label>
        <textarea name="description" rows="2" class="form-control">{{ $val('description') }}</textarea>
    </div>

    <div class="col-md-4 col-12 mb-3">
        <label class="form-label">@lang('evaluation.form.fields.type') <span class="text-danger">*</span></label>
        <select name="type" id="ev-type" class="form-control select2">
            @foreach ($types as $tv => $tl)<option value="{{ $tv }}" {{ $typeVal === $tv ? 'selected' : '' }}>{{ $tl }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-4 col-12 mb-3">
        <label class="form-label">@lang('evaluation.form.fields.usage_domain') <span class="text-danger">*</span></label>
        <select name="usage_domain" class="form-control select2">
            @foreach ($domains as $dv => $dl)<option value="{{ $dv }}" {{ $domainVal === $dv ? 'selected' : '' }}>{{ $dl }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-4 col-12 mb-3" id="ev-levels-count-wrap">
        <label class="form-label">@lang('evaluation.form.fields.levels_count')</label>
        <input type="number" name="levels_count" id="ev-levels-count" value="{{ $val('levels_count', count($levelLabels) ?: 4) }}" class="form-control" min="2" max="10">
    </div>

    <div class="col-md-6 col-12 mb-3">
        <label class="form-label">@lang('evaluation.form.fields.start_date')</label>
        <input type="date" name="start_date" value="{{ $f?->start_date?->format('Y-m-d') ?? old('start_date') }}" class="form-control">
    </div>
    <div class="col-md-6 col-12 mb-3">
        <label class="form-label">@lang('evaluation.form.fields.close_date')</label>
        <input type="date" name="close_date" value="{{ $f?->close_date?->format('Y-m-d') ?? old('close_date') }}" class="form-control">
    </div>
</div>

{{-- Levels (rubric / rating scale) --}}
<div id="ev-levels-block" class="card p-3 mb-3" style="background:#fffdf8;border:1px solid #ece6d8;border-radius:12px;">
    <label class="form-label mb-2"><i class="la la-layer-group"></i> @lang('evaluation.form.fields.level_names')</label>
    <div class="row" id="ev-levels-list">
        @foreach ($levelLabels as $i => $lbl)
            <div class="col-md-3 col-6 mb-2"><input type="text" name="level_labels[]" value="{{ $lbl }}" class="form-control" placeholder="{{ __('evaluation.form.fields.level_n', ['n' => $i + 1]) }}"></div>
        @endforeach
    </div>
    <small class="text-muted">@lang('evaluation.form.fields.levels_help')</small>
</div>
<div id="ev-checklist-note" class="alert alert-info" style="display:none;">@lang('evaluation.form.fields.checklist_note')</div>

{{-- Phase E (#202): Shared mode toggle --}}
<div class="card p-3 mb-3" style="background:#f0f8ff;border:1px solid #b8d4f0;border-radius:12px;">
    <h6 class="mb-2" style="color:#1a5276;"><i class="la la-users"></i> @lang('evaluation.form.shared_mode_title', [], 'وضع التقييم المشترك')</h6>
    <div class="form-check">
        <input type="hidden" name="shared_mode" value="0">
        <input type="checkbox" name="shared_mode" value="1" id="shared_mode" class="form-check-input"
               {{ old('shared_mode', $f?->shared_mode ?? false) ? 'checked' : '' }}>
        <label class="form-check-label" for="shared_mode">
            @lang('evaluation.form.toggles.shared_mode', [], 'تقييم مشترك (بند واحد لكل معلم يملأه أكثر من مقيّم)')
        </label>
    </div>
    <small class="text-muted d-block mt-1">
        @lang('evaluation.form.shared_mode_help', [], 'عند تفعيل هذا الخيار، يتشارك جميع المقيّمين في تقييم واحد لكل معلم؛ كل مقيّم يملأ فقط البنود المسندة لدوره.')
    </small>
</div>

{{-- Settings toggles --}}
<h6 class="mt-2 mb-2" style="font-weight:700;color:var(--gold-500);"><i class="la la-cog"></i> @lang('evaluation.form.settings_title')</h6>
<div class="row">
    @foreach ($toggles as $key)
        <div class="col-md-4 col-12 mb-2">
            <div class="form-check">
                <input type="hidden" name="settings[{{ $key }}]" value="0">
                <input type="checkbox" name="settings[{{ $key }}]" value="1" id="set-{{ $key }}" class="form-check-input" {{ $s($key) ? 'checked' : '' }}>
                <label class="form-check-label" for="set-{{ $key }}">@lang('evaluation.form.toggles.'.$key)</label>
            </div>
        </div>
    @endforeach
</div>

{{-- Job-performance settings (shown only when links_to_job_performance is checked) --}}
<div id="job-perf-settings" class="card p-3 mb-3" style="background:#fffdf8;border:1px solid #ece6d8;border-radius:12px;display:none;">
    <h6 class="mb-3" style="color:var(--gold-500);"><i class="la la-briefcase"></i> @lang('evaluation.form.fields.job_perf_settings_title')</h6>
    <div class="row">
        <div class="col-md-4 col-12 mb-3">
            <label class="form-label">@lang('evaluation.form.fields.job_perf_aggregation')</label>
            <select name="settings[job_perf_aggregation]" class="form-control">
                <option value="average" {{ old('settings.job_perf_aggregation', $f?->job_perf_settings['aggregation'] ?? 'average') === 'average' ? 'selected' : '' }}>@lang('evaluation.form.fields.job_perf_aggregation_average')</option>
                <option value="last"    {{ old('settings.job_perf_aggregation', $f?->job_perf_settings['aggregation'] ?? 'average') === 'last'    ? 'selected' : '' }}>@lang('evaluation.form.fields.job_perf_aggregation_last')</option>
            </select>
        </div>
        <div class="col-md-4 col-12 mb-3">
            <label class="form-label">@lang('evaluation.form.fields.job_perf_count_on')</label>
            <select name="settings[job_perf_count_on]" class="form-control">
                <option value="submit"  {{ old('settings.job_perf_count_on', $f?->job_perf_settings['count_on'] ?? 'submit') === 'submit'  ? 'selected' : '' }}>@lang('evaluation.form.fields.job_perf_count_on_submit')</option>
                <option value="approve" {{ old('settings.job_perf_count_on', $f?->job_perf_settings['count_on'] ?? 'submit') === 'approve' ? 'selected' : '' }}>@lang('evaluation.form.fields.job_perf_count_on_approve')</option>
            </select>
        </div>
        <div class="col-md-4 col-12 mb-3">
            <label class="form-label">@lang('evaluation.form.fields.job_perf_weight')</label>
            <input type="number" name="settings[job_perf_weight]" class="form-control"
                   value="{{ old('settings.job_perf_weight', $f?->job_perf_settings['weight'] ?? '') }}"
                   min="0" max="100" step="0.01" placeholder="1–100">
        </div>
    </div>
</div>

<div class="col-12 mb-3 mt-2">
    <label class="form-label">@lang('evaluation.form.fields.internal_notes')</label>
    <textarea name="internal_notes" rows="2" class="form-control">{{ $val('internal_notes') }}</textarea>
</div>

<div class="d-flex gap-2 flex-wrap mt-2">
    <button type="submit" name="after" value="stay" class="btn btn-primary"><i class="la la-save"></i> @lang('evaluation.form.actions.save')</button>
    <button type="submit" name="after" value="items" class="btn btn-outline-primary"><i class="la la-list-ol"></i> @lang('evaluation.form.actions.save_continue')</button>
    <a href="{{ route('admin.evaluations.index') }}" class="btn btn-outline-secondary">@lang('evaluation.form.actions.back')</a>
</div>

@push('scripts')
<script>
jQuery(function ($) {
    var T = { levelN: @json(__('evaluation.form.fields.level_n', ['n' => '__N__'])) };
    var $type = $('#ev-type'), $countWrap = $('#ev-levels-count-wrap'), $count = $('#ev-levels-count');
    var $block = $('#ev-levels-block'), $list = $('#ev-levels-list'), $note = $('#ev-checklist-note');

    function isChecklist() { return $type.val() === 'checklist'; }

    function renderLevels() {
        var n = Math.max(2, Math.min(10, parseInt($count.val(), 10) || 4));
        var current = $list.find('input').map(function () { return this.value; }).get();
        $list.empty();
        for (var i = 0; i < n; i++) {
            var ph = T.levelN.replace('__N__', i + 1);
            var v = current[i] || '';
            $list.append('<div class="col-md-3 col-6 mb-2"><input type="text" name="level_labels[]" class="form-control" placeholder="' + ph + '" value="' + $('<div>').text(v).html() + '"></div>');
        }
    }

    function applyType() {
        if (isChecklist()) {
            $block.hide(); $countWrap.hide(); $note.show();
        } else {
            $block.show(); $countWrap.show(); $note.hide();
            if ($list.find('input').length === 0) renderLevels();
        }
    }

    $type.on('change', applyType);
    $count.on('input change', function () { if (!isChecklist()) renderLevels(); });
    applyType();

    // Job-performance settings panel toggle.
    var $jpCheck  = $('#set-links_to_job_performance');
    var $jpPanel  = $('#job-perf-settings');
    function applyJobPerf() { $jpPanel.toggle($jpCheck.is(':checked')); }
    $jpCheck.on('change', applyJobPerf);
    applyJobPerf();
});
</script>
@endpush
