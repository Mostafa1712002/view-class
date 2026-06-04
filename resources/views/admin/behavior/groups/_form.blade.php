@csrf
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<input type="hidden" name="scope" value="{{ old('scope', $group->scope) }}">

<div class="form-group mb-3">
    <label class="form-label">@lang('behavior.groups.fields.name') <span class="text-danger">*</span></label>
    <input type="text" name="name" value="{{ old('name', $group->name) }}" class="form-control" required maxlength="255">
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('behavior.groups.fields.type') <span class="text-danger">*</span></label>
    @php $t = old('type', $group->type ?? 'positive'); @endphp
    <select name="type" class="custom-select">
        <option value="positive" @selected($t==='positive')>@lang('behavior.types.positive')</option>
        <option value="negative" @selected($t==='negative')>@lang('behavior.types.negative')</option>
    </select>
</div>

<div class="form-group mb-3">
    <div class="form-check">
        <input type="hidden" name="available_for_teacher" value="0">
        <input type="checkbox" name="available_for_teacher" value="1" id="bg-avail" class="form-check-input"
            @checked(old('available_for_teacher', $group->available_for_teacher ?? true))>
        <label class="form-check-label" for="bg-avail">@lang('behavior.groups.fields.available_for_teacher')</label>
    </div>
</div>

<div class="form-group mb-3">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="bg-active" class="form-check-input"
            @checked(old('is_active', $group->is_active ?? true))>
        <label class="form-check-label" for="bg-active">@lang('behavior.groups.fields.is_active')</label>
    </div>
</div>

<div class="d-flex" style="gap:.5rem;">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('behavior.actions.save')</button>
    <a href="{{ route('admin.behavior.groups.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary">@lang('behavior.actions.cancel')</a>
</div>
