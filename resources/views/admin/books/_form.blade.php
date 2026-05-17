@csrf
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">@lang('books_admin.fields.title') <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $book->title) }}" class="form-control" required maxlength="255" />
    </div>

    <div class="col-md-4">
        <label class="form-label">@lang('books_admin.fields.source') <span class="text-danger">*</span></label>
        <select name="source" id="book-source" class="form-select" required>
            <option value="file" @selected(old('source', $book->source ?? 'file')==='file')>@lang('books_admin.source_file')</option>
            <option value="external_url" @selected(old('source', $book->source ?? 'file')==='external_url')>@lang('books_admin.source_external')</option>
        </select>
    </div>

    <div class="col-md-6 book-source-file">
        <label class="form-label">@lang('books_admin.fields.file') @if(empty($book->id))<span class="text-danger">*</span>@endif</label>
        <input type="file" name="file" accept="application/pdf" class="form-control" />
        <small class="text-muted d-block mt-1">@lang('books_admin.pdf_help')</small>
        @if(!empty($book->file_path))
            <small class="d-block mt-1">
                @lang('books_admin.current_file'):
                <a href="{{ asset('storage/' . $book->file_path) }}" target="_blank" rel="noopener">{{ basename($book->file_path) }}</a>
                <span class="text-muted">— @lang('books_admin.replace_file_hint')</span>
            </small>
        @endif
    </div>

    <div class="col-md-6 book-source-url">
        <label class="form-label">@lang('books_admin.fields.external_url')</label>
        <input type="url" name="external_url" value="{{ old('external_url', $book->external_url) }}" class="form-control" placeholder="https://..." maxlength="1024" />
    </div>

    <div class="col-md-4">
        <label class="form-label">@lang('books_admin.fields.subject') <span class="text-danger">*</span></label>
        <select name="subject_id" class="form-select" required>
            <option value="">@lang('books_admin.choose_subject')</option>
            @foreach($subjects as $s)
                <option value="{{ $s->id }}" @selected((string)old('subject_id', $book->subject_id)===(string)$s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">@lang('books_admin.fields.grade')</label>
        <select name="grade_level" class="form-select">
            <option value="">@lang('books_admin.choose_grade')</option>
            @foreach($grades as $val => $label)
                <option value="{{ $val }}" @selected((string)old('grade_level', $book->grade_level)===(string)$val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">@lang('books_admin.fields.term')</label>
        <select name="academic_term_id" class="form-select">
            <option value="">@lang('books_admin.choose_term')</option>
            @foreach($terms as $t)
                <option value="{{ $t->id }}" @selected((string)old('academic_term_id', $book->academic_term_id)===(string)$t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">@lang('books_admin.fields.description')</label>
        <textarea name="description" rows="3" class="form-control" maxlength="2000">{{ old('description', $book->description) }}</textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label">@lang('books_admin.fields.cover')</label>
        <input type="file" name="cover" accept="image/*" class="form-control" />
        @if(!empty($book->cover_path))
            <img src="{{ asset('storage/' . $book->cover_path) }}" alt="" class="mt-2" style="max-height:80px" />
        @endif
    </div>

    <div class="col-md-3">
        <label class="form-label d-block">@lang('books_admin.fields.is_ministry')</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_ministry" value="0" />
            <input class="form-check-input" type="checkbox" name="is_ministry" value="1" id="is_ministry" @checked(old('is_ministry', $book->is_ministry))>
            <label class="form-check-label" for="is_ministry">@lang('books_admin.ministry_yes')</label>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label d-block">@lang('books_admin.fields.is_active')</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_active" value="0" />
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $book->is_active ?? true))>
            <label class="form-check-label" for="is_active">@lang('books_admin.status_active')</label>
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('books_admin.save')</button>
    <button type="reset" class="btn btn-outline-secondary">@lang('books_admin.reset_form')</button>
    <a href="{{ route('manage.books.index') }}" class="btn btn-outline-dark">@lang('books_admin.back')</a>
</div>

<script>
    (function () {
        var sel = document.getElementById('book-source');
        if (!sel) return;
        var fileWrap = document.querySelector('.book-source-file');
        var urlWrap = document.querySelector('.book-source-url');
        function sync() {
            var isFile = sel.value === 'file';
            if (fileWrap) fileWrap.style.display = isFile ? '' : 'none';
            if (urlWrap) urlWrap.style.display = isFile ? 'none' : '';
        }
        sel.addEventListener('change', sync);
        sync();
    })();
</script>
