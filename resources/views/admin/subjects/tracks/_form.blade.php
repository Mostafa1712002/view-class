@csrf
@if(isset($track) && $track->exists)
    @method('PUT')
@endif

<div class="form-row">
    <div class="form-group col-md-6">
        <label class="form-label">@lang('subject_tracks.form.name') <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $track->name) }}" required maxlength="120">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group col-md-6">
        <label class="form-label">@lang('subject_tracks.form.name_en')</label>
        <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en', $track->name_en) }}" maxlength="120">
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label class="form-label">@lang('subject_tracks.form.sort_order')</label>
        <input type="number" name="sort_order" min="0" max="9999"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $track->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group col-md-8 d-flex align-items-end">
        <div class="custom-control custom-switch" style="padding-{{ app()->getLocale()==='ar' ? 'right' : 'left' }}: 2.5rem;">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   class="custom-control-input" @checked(old('is_active', $track->is_active ?? true))>
            <label class="custom-control-label" for="is_active">
                @lang('subject_tracks.form.is_active')
            </label>
        </div>
        <small class="form-text text-muted" style="margin-{{ app()->getLocale()==='ar' ? 'right' : 'left' }}: 1rem;">
            @lang('subject_tracks.form.is_active_hint')
        </small>
    </div>
</div>

<div class="form-group">
    <label class="form-label">@lang('subject_tracks.form.notes')</label>
    <textarea name="notes" rows="3" maxlength="2000"
              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $track->notes) }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="d-flex gap-2" style="gap:.6rem; margin-top:1.25rem;">
    <button type="submit" class="btn btn-primary"
            style="background: var(--gold-500, #b88735); border-color: var(--gold-500, #b88735); padding:.55rem 1.4rem; font-weight:600;">
        <i class="la la-save"></i> @lang('subject_tracks.save')
    </button>
    <a href="{{ route('admin.subject-tracks.index') }}" class="btn btn-light"
       style="border:1px solid #e2e8f0; padding:.55rem 1.2rem;">
        @lang('subject_tracks.cancel')
    </a>
</div>
