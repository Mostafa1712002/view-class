@csrf
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="form-group mb-3">
    <label class="form-label">@lang('behavior.behaviors.fields.group') <span class="text-danger">*</span></label>
    @php $gid = old('behavior_group_id', $behavior->behavior_group_id); @endphp
    <select name="behavior_group_id" class="custom-select" required>
        <option value="">@lang('behavior.behaviors.choose_group')</option>
        @foreach($groups as $g)
            <option value="{{ $g->id }}" @selected((string)$gid===(string)$g->id)>{{ $g->name }} ({{ __('behavior.types.'.$g->type) }})</option>
        @endforeach
    </select>
    @if($groups->isEmpty())<small class="text-muted d-block mt-1">@lang('behavior.behaviors.no_groups')</small>@endif
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('behavior.behaviors.fields.name') <span class="text-danger">*</span></label>
    <input type="text" name="name" value="{{ old('name', $behavior->name) }}" class="form-control" required maxlength="255">
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('behavior.behaviors.fields.description')</label>
    <textarea name="description" rows="3" class="form-control" maxlength="2000">{{ old('description', $behavior->description) }}</textarea>
</div>

<div class="form-group mb-3">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="bh-active" class="form-check-input" @checked(old('is_active', $behavior->is_active ?? true))>
        <label class="form-check-label" for="bh-active">@lang('behavior.behaviors.fields.is_active')</label>
    </div>
</div>

<div class="d-flex" style="gap:.5rem;">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('behavior.actions.save')</button>
    <a href="{{ route('admin.behavior.behaviors.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">@lang('behavior.actions.cancel')</a>
</div>
