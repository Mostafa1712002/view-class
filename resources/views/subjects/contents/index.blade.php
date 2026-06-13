@extends('layouts.app')

@section('title', __('subjects_content.manage_title') . ' — ' . $subject->name)
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .content-type-badge {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .65rem; border-radius: 999px; font-size: .75rem; font-weight: 700;
    }
    body.theme-light .content-type-badge.video      { background: #eff6ff; color: #1d4ed8; }
    body.theme-light .content-type-badge.attachment { background: #fdf4ff; color: #9333ea; }
    body.theme-light .content-type-badge.link       { background: #f0fdf4; color: #16a34a; }
    body.theme-light .pub-badge { padding: .2rem .55rem; border-radius: 999px; font-size: .75rem; font-weight: 700; }
    body.theme-light .pub-badge.pub   { background: #dcfce7; color: #166534; }
    body.theme-light .pub-badge.draft { background: #f1f5f9; color: #475569; }
    body.theme-light .add-panel {
        background: #fffbf0; border: 1px solid #f1e4b8; border-radius: 14px; padding: 1.25rem 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('subjects_content.manage_title') — {{ $subject->name }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item active">@lang('subjects_content.manage_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">

    {{-- ── Add form ──────────────────────────────────────────────────────── --}}
    <div class="add-panel mb-4">
        <h5 class="fw-700 mb-3"><i class="la la-plus-circle"></i> @lang('subjects_content.add_title')</h5>
        <form method="POST"
              action="{{ route('manage.subject-contents.store', $subject->id) }}"
              enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-600">@lang('subjects_content.label_type') <span class="text-danger">*</span></label>
                    <select name="type" id="ct-type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">— اختر —</option>
                        <option value="video"      {{ old('type') === 'video'      ? 'selected' : '' }}>@lang('subjects_content.type_video')</option>
                        <option value="attachment" {{ old('type') === 'attachment' ? 'selected' : '' }}>@lang('subjects_content.type_attachment')</option>
                        <option value="link"       {{ old('type') === 'link'       ? 'selected' : '' }}>@lang('subjects_content.type_link')</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-600">@lang('subjects_content.label_title') <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="عنوان المحتوى" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- URL field (video / link) --}}
                <div class="col-md-12" id="url-field" style="display:none">
                    <label class="form-label fw-600">@lang('subjects_content.label_url')</label>
                    <input type="text" name="url" value="{{ old('url') }}"
                           class="form-control @error('url') is-invalid @enderror"
                           placeholder="https://...">
                    @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- File field (attachment) --}}
                <div class="col-md-6" id="file-field" style="display:none">
                    <label class="form-label fw-600">@lang('subjects_content.label_file')</label>
                    <input type="file" name="file"
                           class="form-control @error('file') is-invalid @enderror"
                           accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.png,.jpg,.jpeg,.mp4">
                    <div class="form-text">PDF, Word, PPT, Excel, صور، MP4</div>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-600">@lang('subjects_content.label_desc')</label>
                    <textarea name="description" rows="2"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="وصف اختياري">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-600">@lang('subjects_content.label_available_from')</label>
                    <input type="date" name="available_from" value="{{ old('available_from') }}"
                           class="form-control @error('available_from') is-invalid @enderror">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-600">@lang('subjects_content.label_available_until')</label>
                    <input type="date" name="available_until" value="{{ old('available_until') }}"
                           class="form-control @error('available_until') is-invalid @enderror">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1"
                               id="ct-pub" {{ old('is_published') ? 'checked' : '' }}>
                        <label class="form-check-label fw-600" for="ct-pub">@lang('subjects_content.label_published')</label>
                    </div>
                </div>

                <div class="col-md-3 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-plus"></i> @lang('subjects_content.btn_add')
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Contents table ────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-body p-0">
            @if($contents->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="la la-inbox la-3x d-block mb-2"></i>
                    @lang('subjects_content.empty')
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>@lang('subjects_content.label_type')</th>
                            <th>@lang('subjects_content.label_title')</th>
                            <th>@lang('subjects_content.label_teacher')</th>
                            <th>@lang('subjects_content.label_available_from')</th>
                            <th>@lang('subjects_content.label_available_until')</th>
                            <th>@lang('subjects_content.label_published')</th>
                            <th>@lang('subjects_content.label_views')</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($contents as $item)
                        <tr>
                            <td>
                                <span class="content-type-badge {{ $item->type }}">
                                    @if($item->type === 'video') <i class="la la-play-circle"></i>
                                    @elseif($item->type === 'attachment') <i class="la la-paperclip"></i>
                                    @else <i class="la la-link"></i>
                                    @endif
                                    {{ $item->getTypeLabel() }}
                                </span>
                            </td>
                            <td class="fw-600">{{ $item->title }}</td>
                            <td>{{ optional($item->teacher)->name ?? '—' }}</td>
                            <td>{{ $item->available_from?->format('Y-m-d') ?? '—' }}</td>
                            <td>{{ $item->available_until?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                <span class="pub-badge {{ $item->is_published ? 'pub' : 'draft' }}">
                                    {{ $item->is_published ? __('subjects_content.btn_publish') : 'مسودة' }}
                                </span>
                            </td>
                            <td>{{ number_format($item->views_count) }}</td>
                            <td class="text-end">
                                <form method="POST"
                                      action="{{ route('manage.subject-contents.toggle-publish', [$subject->id, $item->id]) }}"
                                      class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            title="{{ $item->is_published ? __('subjects_content.btn_unpublish') : __('subjects_content.btn_publish') }}">
                                        <i class="la {{ $item->is_published ? 'la-eye-slash' : 'la-eye' }}"></i>
                                    </button>
                                </form>

                                @if($item->type === 'attachment' && $item->file_path)
                                    <a href="{{ route('manage.subject-contents.download', [$subject->id, $item->id]) }}"
                                       class="btn btn-sm btn-outline-info" title="@lang('subjects_content.btn_download')">
                                        <i class="la la-download"></i>
                                    </a>
                                @endif

                                <form method="POST"
                                      action="{{ route('manage.subject-contents.destroy', [$subject->id, $item->id]) }}"
                                      class="d-inline" id="del-form-{{ $item->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            title="@lang('subjects_content.btn_delete')"
                                            onclick="window.vcConfirm({title:'@lang('subjects_content.confirm_delete')'}).then(r=>{if(r.isConfirmed) document.getElementById('del-form-{{ $item->id }}').submit();})">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $contents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const typeSelect = document.getElementById('ct-type');
    const urlField   = document.getElementById('url-field');
    const fileField  = document.getElementById('file-field');

    function toggleFields() {
        const t = typeSelect.value;
        urlField.style.display  = (t === 'video' || t === 'link')  ? '' : 'none';
        fileField.style.display = (t === 'attachment')              ? '' : 'none';
    }

    typeSelect.addEventListener('change', toggleFields);
    toggleFields(); // apply on page load for old() values
})();
</script>
@endpush
