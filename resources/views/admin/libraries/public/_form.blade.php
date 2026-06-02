@csrf
<div class="row">
    <div class="col-md-8 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.title') <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $item->title) }}" class="form-control" required maxlength="255" />
    </div>
    <div class="col-md-4 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.content_type') <span class="text-danger">*</span></label>
        <select name="content_type" class="form-select" required>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected(old('content_type', $item->content_type ?? 'other')===$t)>@lang('libraries.types.'.$t)</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.description')</label>
        <textarea name="description" rows="3" class="form-control">{{ old('description', $item->description) }}</textarea>
    </div>
    <div class="col-md-6 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.subject')</label>
        <select name="subject_id" class="form-select">
            <option value="">—</option>
            @foreach($subjects as $s)
                <option value="{{ $s->id }}" @selected((string)old('subject_id', $item->subject_id)===(string)$s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.teacher')</label>
        <select name="teacher_id" class="form-select">
            <option value="">—</option>
            @foreach($teachers as $t)
                <option value="{{ $t->id }}" @selected((string)old('teacher_id', $item->teacher_id)===(string)$t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.tags')</label>
        <input type="text" name="tags" value="{{ old('tags', $item->tags) }}" class="form-control" placeholder="مثل: رياضيات, الصف الأول" />
    </div>
    <div class="col-md-3 col-6 lib-field">
        <label class="form-label">@lang('libraries.fields.sort_order')</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="form-control" min="0" />
    </div>
    <div class="col-md-6 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.external_url')</label>
        <input type="url" name="external_url" value="{{ old('external_url', $item->external_url) }}" class="form-control" placeholder="https://..." />
    </div>
    <div class="col-md-6 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.file')</label>
        <input type="file" name="file" class="form-control" />
        @if(! empty($item->file_path))
            <small class="d-block mt-1"><a href="{{ asset('storage/' . $item->file_path) }}" target="_blank">@lang('libraries.actions.download')</a></small>
        @endif
    </div>
    <div class="col-md-6 col-12 lib-field">
        <label class="form-label">@lang('libraries.fields.thumbnail')</label>
        <input type="file" name="thumbnail" accept="image/*" class="form-control" />
        @if(! empty($item->thumbnail_path))
            <img src="{{ asset('storage/' . $item->thumbnail_path) }}" alt="" class="mt-2" style="max-height:60px" />
        @endif
    </div>
    <div class="col-md-6 col-12 lib-field d-flex align-items-center" style="gap:1.5rem;">
        <div class="form-check">
            <input type="hidden" name="is_public" value="0" />
            <input type="checkbox" name="is_public" value="1" id="lib-publish" class="form-check-input" @checked(old('is_public', $item->is_public ?? true)) />
            <label class="form-check-label" for="lib-publish">@lang('libraries.form.publish')</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="allow_comments" value="0" />
            <input type="checkbox" name="allow_comments" value="1" id="lib-allow-comments" class="form-check-input" @checked(old('allow_comments', $item->allow_comments ?? true)) />
            <label class="form-check-label" for="lib-allow-comments">@lang('libraries.form.allow_comments')</label>
        </div>
    </div>
</div>
<div class="mt-3 d-flex gap-2 flex-wrap">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('libraries.actions.save')</button>
    <a href="{{ route('admin.libraries.public.index') }}" class="btn btn-outline-secondary">@lang('libraries.actions.cancel')</a>
</div>
