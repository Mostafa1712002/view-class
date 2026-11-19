@extends('layouts.app')
@section('title', __('special_programs/exams.create_title'))
@section('page-title', __('special_programs/exams.create_title').' — '.\App\Modules\SpecialPrograms\Support\Programs::label($program))
@section('content')
@php($ex = 'special_programs/exams')
<div class="card"><div class="card-body">
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p class="mb-0">{{ $error }}</p>@endforeach</div>@endif
@if($needsSchool)<div class="alert alert-warning" role="alert">{{ __($ex.'.pick_school') }}</div>
@elseif($blocked)<div class="alert alert-warning" role="alert">{{ __($ex.'.no_subject_assigned') }}</div>
@elseif($subjects->isEmpty())<div class="alert alert-warning" role="alert">{{ __($ex.'.empty_bank') }}</div>
@else
<form method="POST" action="{{ route('manage.special-programs.exams.store', $program) }}" id="exam-form">
@csrf
<div class="form-group"><label for="title">{{ __($ex.'.title') }}</label>
<input type="text" id="title" name="title" class="form-control" maxlength="255" required value="{{ old('title') }}"></div>

<div class="form-group"><label for="type">{{ __($ex.'.type') }}</label>
@if($teacherMode)<input type="hidden" name="type" value="single"><p class="form-control-plaintext">{{ __($ex.'.type_single') }}</p>
@else<select id="type" name="type" class="form-control">
<option value="single" @selected(old('type', 'single') === 'single')>{{ __($ex.'.type_single') }}</option>
<option value="multi" @selected(old('type') === 'multi')>{{ __($ex.'.type_multi') }}</option></select>@endif</div>

<fieldset class="form-group"><legend class="h6">{{ __($ex.'.target_classes') }}</legend>
<small class="text-muted d-block mb-1">{{ __($ex.'.target_hint') }}</small>
@forelse($classes->groupBy('grade') as $grade => $list)
<div class="mb-1"><strong>{{ __($ex.'.grade') }} {{ $grade ?: '—' }}</strong>
@foreach($list as $class)
<div class="custom-control custom-checkbox custom-control-inline">
<input type="checkbox" class="custom-control-input" id="class_{{ $class->id }}" name="target_class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, old('target_class_ids', [])))>
<label class="custom-control-label" for="class_{{ $class->id }}">{{ $class->name }}</label></div>
@endforeach</div>
@empty<div class="alert alert-warning" role="alert">{{ __($ex.'.no_classes') }}</div>@endforelse
@unless($showAll)<a href="{{ route('manage.special-programs.exams.create', [$program, 'all' => 1]) }}">{{ __($ex.'.show_all_classes') }}</a>@endunless
</fieldset>

@if($groups->isNotEmpty())
<fieldset class="form-group"><legend class="h6">{{ __($ex.'.target_groups') }}</legend>
@foreach($groups as $group)
<div class="custom-control custom-checkbox custom-control-inline">
<input type="checkbox" class="custom-control-input" id="group_{{ $group->id }}" name="target_group_ids[]" value="{{ $group->id }}" @checked(in_array($group->id, old('target_group_ids', [])))>
<label class="custom-control-label" for="group_{{ $group->id }}">{{ $group->name }}</label></div>
@endforeach</fieldset>
@endif

<div class="form-row">
<div class="form-group col-md-4"><label for="expiry_hours">{{ __($ex.'.expiry_hours') }}</label>
<input type="number" id="expiry_hours" name="expiry_hours" class="form-control" min="1" max="{{ $maxHours }}" required value="{{ old('expiry_hours', $defaultHours) }}"></div>
<div class="form-group col-md-4"><label for="max_exit_attempts">{{ __($ex.'.max_exit_attempts') }}</label>
<input type="number" id="max_exit_attempts" name="max_exit_attempts" class="form-control" min="1" max="50" value="{{ old('max_exit_attempts') }}">
<small class="text-muted">{{ __($ex.'.max_exit_hint') }}</small></div>
</div>

<h2 class="h5">{{ __($ex.'.subjects') }}</h2>
<div id="subject-rows"></div>
<button type="button" class="btn btn-light mb-3" id="add-subject"><x-svg-icon name="plus-lg" /> {{ __($ex.'.add_subject') }}</button>
<div><button class="btn btn-primary">{{ __($ex.'.save') }}</button>
<a class="btn btn-light" href="{{ route('manage.special-programs.exams.index', $program) }}">{{ __($ex.'.cancel') }}</a></div>
</form>

<template id="subject-tpl">
<div class="card-body">
<div class="form-row">
<div class="form-group col-md-4"><label>{{ __($ex.'.subject') }}</label>
<select name="subjects[__i__][sp_subject_id]" class="form-control f-subject" required>
@foreach($subjects as $s)<option value="{{ $s->id }}">{{ __('special_programs.subjects.'.$s->code) }}</option>@endforeach</select></div>
@unless($teacherMode)
<div class="form-group col-md-3"><label>{{ __($ex.'.teacher') }}</label>
<select name="subjects[__i__][teacher_id]" class="form-control"><option value="">{{ __($ex.'.no_teacher') }}</option>
@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
@endunless
<div class="form-group col-md-2"><label>{{ __($ex.'.duration') }}</label><input type="number" name="subjects[__i__][duration_minutes]" class="form-control" min="1" max="300" required></div>
<div class="form-group col-md-2"><label class="l-questions">{{ __($ex.'.questions') }}</label><input type="number" name="subjects[__i__][total_questions]" class="form-control f-total" min="1" max="{{ $teacherMode ? 100 : 400 }}" required></div>
<div class="form-group col-md-1"><label>{{ __($ex.'.order') }}</label><input type="number" name="subjects[__i__][order_index]" class="form-control f-order" min="1" max="50"></div>
</div>
<p class="mb-1"><strong>{{ __($ex.'.pool_total') }}:</strong> <span class="s-total">—</span></p>
<div class="alert alert-warning d-none w-pool" role="alert">{{ __($ex.'.warn_more_than_pool') }}</div>

@if($program === 'qiyas')
<div class="form-row">
<div class="form-group col-md-3"><label>{{ __($ex.'.term') }}</label>
<select name="subjects[__i__][term]" class="form-control f-term" required>
<option value="first">{{ __('special_programs.terms.first') }}</option><option value="second">{{ __('special_programs.terms.second') }}</option></select></div>
</div>
<fieldset class="form-group"><legend class="h6">{{ __($ex.'.weeks') }}</legend>
@foreach(range(1, 19) as $w)
<label class="mr-2"><input type="checkbox" name="subjects[__i__][weeks][]" value="{{ $w }}" class="f-week"> {{ __($ex.'.week_n', ['n' => $w]) }}</label>
@endforeach</fieldset>
@endif

@if($program !== 'tahsili')
<fieldset class="form-group"><legend class="h6">{{ __($ex.'.levels') }}</legend>
<small class="text-muted d-block">{{ __($ex.'.levels_hint') }}</small>
@foreach(range(1, 12) as $g)
<label class="mr-2"><input type="checkbox" name="subjects[__i__][question_grade_levels][]" value="{{ $g }}" class="f-level"> {{ __($ex.'.level_n', ['n' => $g]) }} (<span class="c-level" data-level="{{ $g }}">0</span>)</label>
@endforeach</fieldset>
<fieldset class="form-group sk-box"><legend class="h6">{{ __($ex.'.skills') }}</legend>
<small class="text-muted d-block">{{ __($ex.'.skills_hint') }}</small>
<div class="sk-list"></div>
<span class="badge badge-info">{{ __($ex.'.total') }}: <span class="sk-sum">0</span>%</span>
<span class="badge badge-secondary">{{ __($ex.'.remaining') }}: <span class="sk-rest">100</span>%</span></fieldset>
@else
<div class="form-row">
@foreach(['easy' => 'easy_pct', 'medium' => 'medium_pct', 'hard' => 'hard_pct'] as $key => $field)
<div class="form-group col-md-2"><label>{{ __($ex.'.'.$key) }}</label><input type="number" name="subjects[__i__][{{ $field }}]" class="form-control f-pct" min="0" max="100" required></div>
@endforeach
<div class="form-group col-md-2"><label>{{ __($ex.'.total') }}</label><p class="form-control-plaintext"><span class="pct-sum">0</span>%</p></div>
</div>
<div class="form-row">
<div class="form-group col-md-3"><label>{{ __($ex.'.range_start') }}</label><input type="number" name="subjects[__i__][range_start]" class="form-control f-rs" min="1"></div>
<div class="form-group col-md-3"><label>{{ __($ex.'.range_end') }}</label><input type="number" name="subjects[__i__][range_end]" class="form-control f-re" min="1"></div>
<div class="form-group col-md-6"><p class="mb-0">{{ __($ex.'.range_count') }}: <strong class="s-range">—</strong></p>
<p class="mb-0">{{ __($ex.'.bank_max_order') }}: <strong class="s-max">—</strong></p></div>
</div>
<div class="alert alert-warning d-none w-range" role="alert">{{ __($ex.'.warn_range_short') }}</div>
<div class="alert alert-info">{{ __($ex.'.tahsili_card') }}: <strong class="t-bank">—</strong> · {{ __($ex.'.weeks_count') }}: <strong class="t-weeks">{{ $weeks }}</strong> · {{ __($ex.'.weekly_count') }}: <strong class="t-weekly">—</strong></div>
@endif
<button type="button" class="btn btn-sm btn-light rm"><x-svg-icon name="trash" :size="14" /> {{ __($ex.'.remove_subject') }}</button>
</div>
</template>
@endif
</div></div>
@endsection

@if(! $needsSchool && ! $blocked && $subjects->isNotEmpty())
@push('scripts')
<script>
(function () {
  var bank = @json($bank), program = @json($program), statsUrl = @js(route('manage.special-programs.bank-stats', $program));
  var old = @js(old('subjects', [])), teacherMode = @json($teacherMode);
  var tpl = document.getElementById('subject-tpl'), box = document.getElementById('subject-rows'), addBtn = document.getElementById('add-subject');
  var typeSel = document.getElementById('type'), n = 0, timers = new WeakMap();
  var readingLabel = @json(__($ex.'.passages')), questionsLabel = @json(__($ex.'.questions'));

  function q(row, sel) { return row.querySelector(sel); }
  function num(el) { return el && el.value !== '' ? parseInt(el.value, 10) : 0; }

  function renderSkills(row) {
    var list = q(row, '.sk-list'); if (!list) return;
    var s = bank[q(row, '.f-subject').value], i = row.dataset.i;
    q(row, '.l-questions').textContent = s.reading ? readingLabel : questionsLabel;
    list.innerHTML = '';
    q(row, '.sk-box').classList.toggle('d-none', s.reading);
    if (s.reading) return;
    s.skills.forEach(function (k) {
      var d = document.createElement('div'); d.className = 'form-inline mb-1';
      d.innerHTML = '<label class="mr-2" style="min-width:14rem"></label><input type="number" min="0" max="100" class="form-control f-share" style="width:6rem"> <small class="mr-2 text-muted">%</small><small class="text-muted">(<span class="c-skill" data-skill="' + k.id + '">0</span>)</small>';
      d.querySelector('label').textContent = k.name;
      d.querySelector('input').name = 'subjects[' + i + '][shares][' + k.id + ']';
      list.appendChild(d);
    });
  }

  function refresh(row) {
    var s = bank[q(row, '.f-subject').value];
    var sum = 0; row.querySelectorAll('.f-share').forEach(function (e) { sum += num(e); });
    if (q(row, '.sk-sum')) { q(row, '.sk-sum').textContent = sum; q(row, '.sk-rest').textContent = 100 - sum; }
    var ps = 0; row.querySelectorAll('.f-pct').forEach(function (e) { ps += num(e); });
    if (q(row, '.pct-sum')) q(row, '.pct-sum').textContent = ps;
    q(row, '.s-total').textContent = s.stats.total;
    q(row, '.w-pool').classList.toggle('d-none', !(num(q(row, '.f-total')) > s.stats.total));
    clearTimeout(timers.get(row)); timers.set(row, setTimeout(function () { loadStats(row); }, 300));
  }

  function loadStats(row) {
    var p = new URLSearchParams(); p.set('sp_subject_id', q(row, '.f-subject').value);
    if (q(row, '.f-term')) p.set('term', q(row, '.f-term').value);
    row.querySelectorAll('.f-week:checked').forEach(function (e) { p.append('weeks[]', e.value); });
    row.querySelectorAll('.f-level:checked').forEach(function (e) { p.append('grade_levels[]', e.value); });
    var rs = q(row, '.f-rs'), re = q(row, '.f-re');
    if (rs && re && rs.value && re.value) { p.set('range_start', rs.value); p.set('range_end', re.value); }
    fetch(statsUrl + '?' + p.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; }).then(function (d) {
        if (!d) return;
        q(row, '.s-total').textContent = d.total;
        row.querySelectorAll('.c-level').forEach(function (e) { e.textContent = (d.by_level || {})[e.dataset.level] || 0; });
        row.querySelectorAll('.c-skill').forEach(function (e) { e.textContent = (d.by_skill || {})[e.dataset.skill] || 0; });
        q(row, '.w-pool').classList.toggle('d-none', !(num(q(row, '.f-total')) > d.total));
        if (q(row, '.s-range')) {
          q(row, '.s-range').textContent = d.in_range; q(row, '.s-max').textContent = d.max_order === null ? '—' : d.max_order;
          q(row, '.t-bank').textContent = d.bank_total; q(row, '.t-weeks').textContent = d.weeks_count; q(row, '.t-weekly').textContent = d.weekly_count;
          q(row, '.w-range').classList.toggle('d-none', !(rs.value && re.value && num(q(row, '.f-total')) > d.in_range));
        }
      });
  }

  function flatten(v, prefix, out) {
    if (Array.isArray(v)) out[prefix + '[]'] = v;
    else if (v && typeof v === 'object') Object.keys(v).forEach(function (k) { flatten(v[k], prefix + '[' + k + ']', out); });
    else out[prefix] = v;
    return out;
  }

  function addRow(values) {
    var w = document.createElement('div'); w.className = 'card mb-3 subject-row'; w.dataset.i = n;
    w.innerHTML = tpl.innerHTML.split('__i__').join(n); n++;
    box.appendChild(w);
    var i = w.dataset.i;
    if (values) {
      var sel = q(w, '.f-subject'); if (values.sp_subject_id && bank[values.sp_subject_id]) sel.value = values.sp_subject_id;
      renderSkills(w);
      var flat = flatten(values, 'subjects[' + i + ']', {});
      w.querySelectorAll('[name]').forEach(function (e) {
        var v = flat[e.name]; if (v === undefined || v === null) return;
        if (e.type === 'checkbox') e.checked = v.map(String).indexOf(e.value) > -1; else e.value = v;
      });
    } else { renderSkills(w); q(w, '.f-order').value = box.children.length; }
    w.addEventListener('input', function () { refresh(w); });
    w.addEventListener('change', function (ev) { if (ev.target.classList.contains('f-subject')) renderSkills(w); refresh(w); });
    q(w, '.rm').addEventListener('click', function () { w.remove(); toggleAdd(); });
    refresh(w); toggleAdd();
  }

  function single() { return teacherMode || (typeSel && typeSel.value === 'single'); }
  function toggleAdd() { addBtn.classList.toggle('d-none', single() && box.children.length >= 1); }

  addBtn.addEventListener('click', function () { addRow(null); });
  if (typeSel) typeSel.addEventListener('change', toggleAdd);
  (old.length || Object.keys(old).length ? Object.keys(old).map(function (k) { return old[k]; }) : [null]).forEach(addRow);
})();
</script>
@endpush
@endif
