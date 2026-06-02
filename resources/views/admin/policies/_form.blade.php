@csrf
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="form-group mb-3">
    <label class="form-label">@lang('policies.fields.title') <span class="text-danger">*</span></label>
    <input type="text" name="title" value="{{ old('title', $policy->title) }}" class="form-control" required maxlength="255">
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('policies.fields.description')</label>
    <textarea name="description" rows="3" class="form-control">{{ old('description', $policy->description) }}</textarea>
</div>

<div class="form-group mb-3">
    <label class="form-label">@lang('policies.fields.target_roles') <span class="text-danger">*</span></label>
    @php $selected = old('target_roles', $policy->target_roles ?? []); @endphp
    <div class="d-flex flex-wrap" style="gap:1rem;">
        @foreach($roles as $r)
            <div class="form-check">
                <input type="checkbox" name="target_roles[]" value="{{ $r }}" id="role-{{ $r }}" class="form-check-input"
                    @checked(in_array($r, (array) $selected))>
                <label class="form-check-label" for="role-{{ $r }}">@lang('policies.roles.'.$r)</label>
            </div>
        @endforeach
    </div>
</div>

<div class="row">
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('policies.fields.external_url')</label>
        <input type="url" name="external_url" value="{{ old('external_url', $policy->external_url) }}" class="form-control" placeholder="https://...">
    </div>
    <div class="form-group mb-3 col-md-6">
        <label class="form-label">@lang('policies.fields.file')</label>
        <input type="file" name="file" class="form-control">
        @if($policy->file_path)
            <small class="d-block mt-1"><a href="{{ $policy->fileUrl() }}" target="_blank">@lang('policies.actions.open')</a></small>
        @endif
    </div>
</div>

<div class="d-flex" style="gap:.5rem;">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('policies.actions.save')</button>
    <a href="{{ route('admin.policies.index') }}" class="btn btn-soft">@lang('policies.actions.cancel')</a>
</div>
