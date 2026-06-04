@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('behavior.records.add'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.records.add')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.behavior.records.index', ['tab' => $tab]) }}">@lang('behavior.records.title')</a></li>
            <li class="breadcrumb-item active">@lang('behavior.records.add')</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="card-body">
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $tab === 'student' ? 'active' : '' }}" href="{{ route('admin.behavior.records.create', ['tab' => 'student']) }}"><i class="la la-user-graduate"></i> @lang('behavior.tabs.students')</a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'teacher' ? 'active' : '' }}" href="{{ route('admin.behavior.records.create', ['tab' => 'teacher']) }}"><i class="la la-chalkboard-teacher"></i> @lang('behavior.tabs.teachers')</a></li>
    </ul>

    <form method="POST" action="{{ route('admin.behavior.records.store', ['tab' => $tab]) }}" id="record-form"
          data-actions-url="{{ route('admin.behavior.records.actions') }}">
        @csrf
        <div class="row">
            <div class="form-group mb-3 col-md-6">
                <label class="form-label">@lang('behavior.records.fields.subject_'.$tab) <span class="text-danger">*</span></label>
                @php $lockedId = ($lockedUser ?? null)?->id; @endphp
                <select name="subject_user_id" class="custom-select" required @if($lockedUser) disabled @endif>
                    @unless($lockedUser)<option value="">@lang('behavior.records.choose_'.$tab)</option>@endunless
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected($lockedId ? $lockedId===$u->id : (string)old('subject_user_id')===(string)$u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
                {{-- Disabled selects don't submit, so carry the locked id in a hidden field. --}}
                @if($lockedUser)<input type="hidden" name="subject_user_id" value="{{ $lockedUser->id }}">@endif
                {{-- When opened from the student's page, return there after saving (card #131). --}}
                @if($lockedUser && $tab === 'student')<input type="hidden" name="from_student_id" value="{{ $lockedUser->id }}">@endif
                @if($users->isEmpty())<small class="text-muted d-block mt-1">@lang('behavior.records.no_users')</small>@endif
            </div>
            <div class="form-group mb-3 col-md-6">
                <label class="form-label">@lang('behavior.records.fields.behavior') <span class="text-danger">*</span></label>
                <select name="behavior_id" id="rec-behavior" class="custom-select" required>
                    <option value="">@lang('behavior.records.choose_behavior')</option>
                    @foreach($behaviors as $b)
                        <option value="{{ $b->id }}" @selected((string)old('behavior_id')===(string)$b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
                @if($behaviors->isEmpty())<small class="text-muted d-block mt-1">@lang('behavior.records.no_behaviors')</small>@endif
            </div>
        </div>

        <div class="row">
            <div class="form-group mb-3 col-md-6">
                <label class="form-label">@lang('behavior.records.fields.action')</label>
                <select name="behavior_action_id" id="rec-action" class="custom-select" disabled>
                    <option value="">@lang('behavior.records.choose_behavior_first')</option>
                </select>
                <small class="text-muted d-block mt-1" id="rec-action-hint"></small>
            </div>
            <div class="form-group mb-3 col-md-6">
                <label class="form-label">@lang('behavior.records.fields.points')</label>
                <input type="number" name="points" id="rec-points" value="{{ old('points') }}" class="form-control" placeholder="0">
                <small class="text-muted d-block mt-1">@lang('behavior.records.points_hint')</small>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">@lang('behavior.records.fields.note')</label>
            <textarea name="note" rows="2" class="form-control" maxlength="2000">{{ old('note') }}</textarea>
        </div>

        <div class="d-flex" style="gap:.5rem;">
            <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('behavior.records.save')</button>
            <a href="{{ route('admin.behavior.records.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">@lang('behavior.actions.cancel')</a>
        </div>
    </form>
</div></div></div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('record-form');
    if (!form) return;
    var url = form.dataset.actionsUrl;
    var behaviorSel = document.getElementById('rec-behavior');
    var actionSel = document.getElementById('rec-action');
    var pointsInput = document.getElementById('rec-points');
    var hint = document.getElementById('rec-action-hint');
    var T = {
        chooseFirst: @json(__('behavior.records.choose_behavior_first')),
        chooseAction: @json(__('behavior.records.choose_action')),
        none: @json(__('behavior.records.no_actions')),
        notify: @json(__('behavior.records.will_notify')),
    };
    var actionsById = {};

    function loadActions() {
        var bid = behaviorSel.value;
        actionSel.innerHTML = '';
        actionsById = {};
        if (!bid) { actionSel.disabled = true; actionSel.innerHTML = '<option value="">' + T.chooseFirst + '</option>'; hint.textContent=''; return; }
        fetch(url + '?behavior_id=' + encodeURIComponent(bid), {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var list = d.actions || [];
                var first = document.createElement('option'); first.value=''; first.textContent = list.length ? T.chooseAction : T.none;
                actionSel.appendChild(first);
                list.forEach(function (a) {
                    actionsById[a.id] = a;
                    var o = document.createElement('option');
                    o.value = a.id;
                    o.textContent = a.description + ' (' + (a.signed_points >= 0 ? '+' : '') + a.signed_points + ')';
                    actionSel.appendChild(o);
                });
                actionSel.disabled = list.length === 0;
            });
    }

    function onAction() {
        var a = actionsById[actionSel.value];
        if (a) {
            pointsInput.value = a.signed_points;
            hint.textContent = a.notify_parent ? T.notify : '';
        } else {
            hint.textContent = '';
        }
    }

    behaviorSel.addEventListener('change', loadActions);
    actionSel.addEventListener('change', onAction);
    if (behaviorSel.value) loadActions();
})();
</script>
@endpush
@endsection
