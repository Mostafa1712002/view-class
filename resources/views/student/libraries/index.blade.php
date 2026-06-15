@extends('layouts.admin')

@section('body_class','theme-light')
@section('title', __('student.library.title'))

@include('admin.libraries._styles')

@php
    $activeTab = request('tab', 'public');
    if (! in_array($activeTab, ['public', 'private', 'files'], true)) {
        $activeTab = 'public';
    }
@endphp

@section('content')
<div class="lib-scope">
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('student.library.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">@lang('student.library.home')</a></li>
                <li class="breadcrumb-item active">@lang('student.library.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ===== Tabs ===== --}}
    <ul class="nav nav-tabs mb-3" id="libTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'public' ? 'active' : '' }}" href="#tab-public" data-bs-toggle="tab" data-toggle="tab" role="tab">
                <i class="la la-globe"></i> @lang('student.library.tab_public')
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'private' ? 'active' : '' }}" href="#tab-private" data-bs-toggle="tab" data-toggle="tab" role="tab">
                <i class="la la-lock"></i> @lang('student.library.tab_private')
                <span class="badge badge-light">{{ $privateLibraries->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'files' ? 'active' : '' }}" href="#tab-files" data-bs-toggle="tab" data-toggle="tab" role="tab">
                <i class="la la-folder-open"></i> @lang('student.library.tab_files')
                <span class="badge badge-light">{{ $myFiles->count() }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">

        {{-- ============ TAB 1: General / Public library ============ --}}
        <div class="tab-pane fade {{ $activeTab === 'public' ? 'show active' : '' }}" id="tab-public" role="tabpanel">

            <div class="card lib-filter-card mb-3">
                <div class="card-header"><i class="la la-filter"></i> @lang('student.library.filters')</div>
                <div class="card-body py-2">
                    <form method="GET" action="{{ route('student.libraries.index') }}">
                        <input type="hidden" name="tab" value="public" />
                        <div class="row align-items-end">
                            <div class="col-md-3 col-12 lib-field mb-2">
                                <label class="form-label">@lang('student.library.f_title')</label>
                                <input type="text" name="title" value="{{ $filters['title'] }}" class="form-control" />
                            </div>
                            <div class="col-md-2 col-6 lib-field mb-2">
                                <label class="form-label">@lang('student.library.f_content_type')</label>
                                <select name="content_type" class="form-select">
                                    <option value="">@lang('student.library.f_all')</option>
                                    @foreach($types as $t)
                                        <option value="{{ $t }}" @selected($filters['content_type'] === $t)>@lang('libraries.types.'.$t)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-6 lib-field mb-2">
                                <label class="form-label">@lang('student.library.f_subject')</label>
                                <select name="subject_id" class="form-select">
                                    <option value="">@lang('student.library.f_all')</option>
                                    @foreach($subjects as $s)
                                        <option value="{{ $s->id }}" @selected((string)($filters['subject_id'] ?? '') === (string)$s->id)>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-6 lib-field mb-2">
                                <label class="form-label">@lang('student.library.f_teacher')</label>
                                <select name="teacher_id" class="form-select">
                                    <option value="">@lang('student.library.f_all')</option>
                                    @foreach($teachers as $tt)
                                        <option value="{{ $tt->id }}" @selected((string)($filters['teacher_id'] ?? '') === (string)$tt->id)>{{ $tt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-6 lib-field mb-2">
                                <label class="form-label">@lang('student.library.f_tag')</label>
                                <input type="text" name="tag" value="{{ $filters['tag'] }}" class="form-control" />
                            </div>
                            <div class="col-md-2 col-6 lib-field mb-2">
                                <label class="form-label">@lang('student.library.f_sort')</label>
                                <select name="sort" class="form-select">
                                    <option value="newest" @selected($filters['sort'] === 'newest')>@lang('student.library.sort_newest')</option>
                                    <option value="oldest" @selected($filters['sort'] === 'oldest')>@lang('student.library.sort_oldest')</option>
                                    <option value="top_rated" @selected($filters['sort'] === 'top_rated')>@lang('student.library.sort_top_rated')</option>
                                </select>
                            </div>
                            <div class="col-md-1 col-6 lib-field mb-2">
                                <button class="btn btn-primary w-100" type="submit"><i class="la la-filter"></i> @lang('student.library.filter_btn')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($publicItems->count() === 0)
                <div class="card"><div class="lib-empty"><i class="la la-book-open"></i>@lang('student.library.no_public')</div></div>
            @else
                <div class="row">
                    @foreach($publicItems as $item)
                        @php
                            $icon = $item->content_type === 'video' ? 'play-circle'
                                : ($item->content_type === 'pdf' ? 'file-pdf'
                                : ($item->content_type === 'image' ? 'image'
                                : ($item->content_type === 'presentation' ? 'desktop'
                                : ($item->content_type === 'link' ? 'link' : 'file-alt'))));
                            $safeUrl = preg_match('#^https?://#i', (string) $item->external_url) ? $item->external_url : null;
                        @endphp
                        <div class="col-md-4 col-lg-3 col-6 mb-4">
                            <div class="lib-card h-100 d-flex flex-column">
                                <div class="lib-card-media">
                                    <span class="lib-type-chip">@lang('libraries.types.'.$item->content_type)</span>
                                    @if($item->thumbnail_path)
                                        <img src="{{ asset('storage/' . $item->thumbnail_path) }}" alt="{{ $item->title }}" />
                                    @else
                                        <i class="la la-{{ $icon }} lib-icon"></i>
                                    @endif
                                </div>
                                <div class="lib-card-body flex-grow-1">
                                    <div class="lib-card-title">{{ $item->title }}</div>
                                    <div class="lib-card-meta">
                                        <i class="la la-book"></i>
                                        {{ $item->subject?->name ?? __('student.library.general_item') }}
                                    </div>
                                    @if($item->teacher)
                                        <div class="lib-card-meta"><i class="la la-user"></i> {{ $item->teacher->name }}</div>
                                    @endif
                                    <div class="lib-card-meta"><i class="la la-calendar"></i> {{ $item->created_at?->format('Y-m-d') }}</div>
                                    <div class="lib-card-meta">
                                        <span style="color:#f59e0b;">★</span>
                                        {{ number_format((float) ($item->ratings_avg ?? 0), 1) }}
                                        <span class="text-muted">({{ $item->ratings_count ?? 0 }})</span>
                                        <span class="text-muted ms-2"><i class="la la-comments"></i> {{ $item->comments_count ?? 0 }}</span>
                                    </div>
                                    @if($item->description)
                                        <p class="lib-card-desc">{{ \Illuminate\Support\Str::limit($item->description, 80) }}</p>
                                    @endif
                                </div>
                                <div class="lib-card-footer">
                                    @if($safeUrl)
                                        <a href="{{ $safeUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary w-100"><i class="la la-external-link-alt"></i> @lang('student.library.open')</a>
                                    @elseif($item->file_path)
                                        <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="btn btn-sm btn-primary w-100"><i class="la la-eye"></i> @lang('student.library.view')</a>
                                    @else
                                        <span class="btn btn-sm btn-outline-secondary disabled w-100"><i class="la la-eye-slash"></i> @lang('student.library.view')</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2">{{ $publicItems->appends(['tab' => 'public'])->links() }}</div>
            @endif
        </div>

        {{-- ============ TAB 2: Private libraries ============ --}}
        <div class="tab-pane fade {{ $activeTab === 'private' ? 'show active' : '' }}" id="tab-private" role="tabpanel">
            @if($privateLibraries->isEmpty())
                <div class="card"><div class="lib-empty"><i class="la la-lock"></i>@lang('student.library.no_private')</div></div>
            @else
                @foreach($privateLibraries as $library)
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center">
                            <i class="la la-folder me-2"></i>
                            <strong>{{ $library->title }}</strong>
                            <span class="badge badge-light ms-2">{{ $library->items_count }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>@lang('student.library.priv_title')</th>
                                        <th>@lang('student.library.priv_type')</th>
                                        <th>@lang('student.library.priv_teacher')</th>
                                        <th>@lang('student.library.priv_subject')</th>
                                        <th>@lang('student.library.priv_date')</th>
                                        <th>@lang('student.library.priv_actions')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($library->items as $it)
                                        @php $itUrl = preg_match('#^https?://#i', (string)$it->external_url) ? $it->external_url : null; @endphp
                                        <tr>
                                            <td><strong>{{ $it->title }}</strong></td>
                                            <td><span class="badge badge-light">@lang('libraries.types.'.$it->content_type)</span></td>
                                            <td>{{ $it->teacher?->name ?? '—' }}</td>
                                            <td>{{ $it->subject?->name ?? '—' }}</td>
                                            <td>{{ $it->created_at?->format('Y-m-d') }}</td>
                                            <td>
                                                @if($itUrl)
                                                    <a href="{{ $itUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary"><i class="la la-external-link-alt"></i> @lang('student.library.open')</a>
                                                @elseif($it->file_path)
                                                    <a href="{{ asset('storage/' . $it->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="la la-eye"></i> @lang('student.library.view')</a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-3">—</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- ============ TAB 3: My Files ============ --}}
        <div class="tab-pane fade {{ $activeTab === 'files' ? 'show active' : '' }}" id="tab-files" role="tabpanel">
            @if($myFiles->isEmpty())
                <div class="card"><div class="lib-empty"><i class="la la-folder-open"></i>@lang('student.library.no_files')</div></div>
            @else
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>@lang('student.library.file_title')</th>
                                    <th>@lang('student.library.file_type')</th>
                                    <th>@lang('student.library.file_source')</th>
                                    <th>@lang('student.library.file_date')</th>
                                    <th>@lang('student.library.file_size')</th>
                                    <th>@lang('student.library.file_actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myFiles as $f)
                                    <tr>
                                        <td><strong>{{ $f['title'] }}</strong></td>
                                        <td><span class="badge badge-light text-uppercase">{{ $f['type'] ?: '—' }}</span></td>
                                        <td>@lang('student.library.src_'.$f['source'])</td>
                                        <td>{{ optional($f['uploaded_at'])->format('Y-m-d') ?? '—' }}</td>
                                        <td>{{ $f['size'] ? \Illuminate\Support\Number::fileSize($f['size']) : '—' }}</td>
                                        <td class="d-flex gap-1">
                                            @if($f['view'])
                                                <a href="{{ $f['view'] }}" target="_blank" class="btn btn-sm btn-outline-info" title="@lang('student.library.view')"><i class="la la-eye"></i></a>
                                            @endif
                                            <a href="{{ $f['download'] }}" class="btn btn-sm btn-outline-primary" title="@lang('student.library.download')"><i class="la la-download"></i></a>
                                            @if($f['can_delete'])
                                                <form method="POST" action="{{ route('student.libraries.files.destroy', ['source' => $f['source'], 'id' => $f['id']]) }}" onsubmit="return confirm('{{ __('student.library.delete_confirm') }}');" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('student.library.delete')"><i class="la la-trash"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
</div>
@endsection
