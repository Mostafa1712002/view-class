@csrf
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="form-group mb-3">
    <label class="form-label">@lang('behavior.actions_page.fields.behavior') <span class="text-danger">*</span></label>
    @php $bid = old('behavior_id', $action->behavior_id); @endphp
    <select name="behavior_id" class="custom-select" required>
        <option value="">@lang('behavior.actions_page.choose_behavior')</option>
        @foreach($behaviors as $b)
            <option value="{{ $b->id }}" @selected((string)$bid===(string)$b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
    @if($behaviors->isEmpty())<small class="text-muted d-block mt-1">@lang('behavior.actions_page.no_behaviors')</small>@endif
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('behavior.actions_page.fields.description') <span class="text-danger">*</span></label>
    <textarea name="description" rows="3" class="form-control" required maxlength="2000">{{ old('description', $action->description) }}</textarea>
</div>

<div class="row">
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('behavior.actions_page.fields.points')</label>
        <input type="number" name="points" value="{{ old('points', $action->points ?? 0) }}" class="form-control" min="0" max="100000">
    </div>
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('behavior.actions_page.fields.point_type')</label>
        @php $pt = old('point_type', $action->point_type ?? 'add'); @endphp
        <select name="point_type" class="custom-select">
            <option value="add" @selected($pt==='add')>@lang('behavior.point_types.add')</option>
            <option value="deduct" @selected($pt==='deduct')>@lang('behavior.point_types.deduct')</option>
        </select>
    </div>
</div>

<div class="form-group mb-2">
    <div class="form-check">
        <input type="hidden" name="notify_parent" value="0">
        <input type="checkbox" name="notify_parent" value="1" id="ba-notify" class="form-check-input" @checked(old('notify_parent', $action->notify_parent ?? false))>
        <label class="form-check-label" for="ba-notify">@lang('behavior.actions_page.fields.notify_parent')</label>
    </div>
</div>

<div class="form-group mb-2">
    <div class="form-check">
        <input type="hidden" name="needs_followup" value="0">
        <input type="checkbox" name="needs_followup" value="1" id="ba-followup" class="form-check-input" @checked(old('needs_followup', $action->needs_followup ?? false))>
        <label class="form-check-label" for="ba-followup">@lang('behavior.actions_page.fields.needs_followup')</label>
    </div>
</div>

<div class="form-group mb-3">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="ba-active" class="form-check-input" @checked(old('is_active', $action->is_active ?? true))>
        <label class="form-check-label" for="ba-active">@lang('behavior.actions_page.fields.is_active')</label>
    </div>
</div>

<div class="d-flex" style="gap:.5rem;">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('behavior.actions.save')</button>
    <a href="{{ route('admin.behavior.actions.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">@lang('behavior.actions.cancel')</a>
</div>
