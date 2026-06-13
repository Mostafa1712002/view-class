@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.my.title'))

@include('admin.libraries._styles')

@section('content')
<div class="lib-scope">
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.my.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('libraries.my.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">

    {{-- ========== Public Library section ========== --}}
    <h5 class="lib-section-title mb-2"><i class="la la-globe"></i> @lang('libraries.my.public_section')</h5>

    {{-- Search filters --}}
    <div class="card lib-filter-card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('my.libraries.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-4 col-12 lib-field mb-0">
                        <label class="form-label">@lang('libraries.public.filters.title')</label>
                        <input type="text" name="title" value="{{ $publicFilters['title'] ?? '' }}" class="form-control" />
                    </div>
                    <div class="col-md-3 col-6 lib-field mb-0">
                        <label class="form-label">@lang('libraries.public.filters.content_type')</label>
                        <select name="content_type" class="form-select">
                            <option value="">@lang('libraries.public.filters.all')</option>
                            @foreach($types as $t)
                                <option value="{{ $t }}" @selected(($publicFilters['content_type'] ?? '') === $t)>@lang('libraries.types.'.$t)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6 lib-field mb-0">
                        <label class="form-label">@lang('libraries.public.filters.sort')</label>
                        <select name="sort" class="form-select">
                            <option value="newest" @selected(($publicFilters['sort'] ?? 'newest') === 'newest')>@lang('libraries.public.filters.sort_newest')</option>
                            <option value="oldest" @selected(($publicFilters['sort'] ?? '') === 'oldest')>@lang('libraries.public.filters.sort_oldest')</option>
                            <option value="top_rated" @selected(($publicFilters['sort'] ?? '') === 'top_rated')>@lang('libraries.public.filters.sort_top_rated')</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12 lib-field mb-0">
                        <button class="btn btn-primary w-100" type="submit"><i class="la la-filter"></i> @lang('libraries.my.filter_btn')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Public items grid --}}
    @if($publicItems->count() === 0)
        <div class="card mb-4"><div class="lib-empty"><i class="la la-book-open"></i>@lang('libraries.public.no_results')</div></div>
    @else
        <div class="row mb-4">
            @foreach($publicItems as $item)
                @php
                    $icon = $item->content_type === 'video' ? 'play-circle'
                        : ($item->content_type === 'pdf' ? 'file-pdf'
                        : ($item->content_type === 'image' ? 'image'
                        : ($item->content_type === 'presentation' ? 'desktop'
                        : ($item->content_type === 'link' ? 'link' : 'file-alt'))));
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
                            <div class="lib-card-meta"><i class="la la-calendar"></i> {{ $item->created_at?->format('Y-m-d') }}</div>
                            <div class="lib-card-meta">
                                <span style="color:#f59e0b;">★</span>
                                {{ number_format((float) ($item->ratings_avg ?? 0), 1) }}
                                <span class="text-muted">({{ $item->ratings_count ?? 0 }})</span>
                            </div>
                            @if($item->description)
                                <p class="lib-card-desc">{{ \Illuminate\Support\Str::limit($item->description, 80) }}</p>
                            @endif
                        </div>
                        <div class="lib-card-footer">
                            @if($item->file_path)
                                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="@lang('libraries.actions.download')"><i class="la la-download"></i></a>
                            @endif
                            @php $safeUrl = preg_match('#^https?://#i', (string) $item->external_url) ? $item->external_url : null; @endphp
                            @if($safeUrl)
                                <a href="{{ $safeUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary" title="@lang('libraries.actions.open')"><i class="la la-external-link-alt"></i></a>
                            @endif
                            @if(Route::has('admin.libraries.public.show'))
                                <a href="{{ route('admin.libraries.public.show', $item->id) }}" class="btn btn-sm btn-outline-info ms-auto" title="@lang('libraries.show.details')"><i class="la la-star"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 mb-4">{{ $publicItems->links() }}</div>
    @endif

    {{-- ========== Private libraries accessible to my children ========== --}}
    <h5 class="lib-section-title mb-2 mt-4"><i class="la la-lock"></i> @lang('libraries.my.private_section')</h5>

    @if($privateLibraries->isEmpty())
        <div class="card"><div class="lib-empty"><i class="la la-lock"></i>@lang('libraries.my.no_private')</div></div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>@lang('libraries.private.columns.title')</th>
                            <th>@lang('libraries.private.columns.items_count')</th>
                            <th>@lang('libraries.my.col_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($privateLibraries as $library)
                            <tr>
                                <td>
                                    <strong>{{ $library->title }}</strong>
                                    @if($library->description)
                                        <small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($library->description, 80) }}</small>
                                    @endif
                                </td>
                                <td>{{ $library->items_count }}</td>
                                <td>
                                    @if(Route::has('admin.libraries.private.items'))
                                        <a href="{{ route('admin.libraries.private.items', $library->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="la la-eye"></i> @lang('libraries.actions.view_items')
                                        </a>
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
@endsection
