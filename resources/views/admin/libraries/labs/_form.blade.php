@csrf
<div class="row">
    <div class="col-md-8 col-12 lib-field">
        <label class="form-label">@lang('libraries.labs.fields.title') <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $lab->title) }}" class="form-control" required maxlength="255" />
    </div>
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label">@lang('libraries.labs.fields.category')</label>
        <select name="category_id" class="form-select">
            <option value="">—</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected((string)old('category_id', $lab->category_id)===(string)$c->id)>{{ $c->name_ar }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 lib-field">
        <label class="form-label">@lang('libraries.labs.fields.description')</label>
        <textarea name="description" rows="3" class="form-control">{{ old('description', $lab->description) }}</textarea>
    </div>
    <div class="col-md-8 col-12 lib-field">
        <label class="form-label">@lang('libraries.labs.fields.external_url')</label>
        <input type="url" name="external_url" value="{{ old('external_url', $lab->external_url) }}" class="form-control" placeholder="https://phet.colorado.edu/..." />
    </div>
    <div class="col-md-2 col-6 lib-field">
        <label class="form-label">@lang('libraries.labs.fields.sort_order')</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $lab->sort_order ?? 0) }}" class="form-control" min="0" />
    </div>
    <div class="col-md-2 col-6 lib-field d-flex align-items-end">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0" />
            <input type="checkbox" name="is_active" value="1" id="lab-active" class="form-check-input" @checked(old('is_active', $lab->is_active ?? true)) />
            <label class="form-check-label" for="lab-active">@lang('libraries.labs.fields.is_active')</label>
        </div>
    </div>
    <div class="col-md-6 col-12 lib-field">
        <label class="form-label">@lang('libraries.labs.fields.thumbnail')</label>
        <input type="file" name="thumbnail" accept="image/*" class="form-control" />
        @if(! empty($lab->thumbnail_path))
            <img src="{{ asset('storage/' . $lab->thumbnail_path) }}" class="mt-2" style="max-height:80px" />
        @endif
    </div>
</div>
<div class="mt-3 d-flex gap-2 flex-wrap">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('libraries.actions.save')</button>
    <a href="{{ route('admin.libraries.labs.manage') }}" class="btn btn-outline-secondary">@lang('libraries.actions.cancel')</a>
</div>
