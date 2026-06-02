@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.public.title'))

@include('admin.libraries._styles')

@section('content')
<div class="lib-scope">
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.public.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">@lang('libraries.breadcrumb')</li>
                <li class="breadcrumb-item active">@lang('libraries.public.title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-end">
        <a href="{{ route('admin.libraries.public.create') }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('libraries.public.add_item')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    {{-- Library tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" href="{{ route('admin.libraries.public.index') }}">@lang('libraries.public.title')</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.private.index') }}">@lang('libraries.private.title')</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.labs.index') }}">@lang('libraries.labs.title')</a></li>
    </ul>

    {{-- Search filters --}}
    <div class="card lib-filter-card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="la la-search"></i> @lang('libraries.public.filter_title')</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.libraries.public.index') }}">
                <div class="row">
                    <div class="col-md-3 col-12 lib-field">
                        <label class="form-label">@lang('libraries.public.filters.title')</label>
                        <input type="text" name="title" value="{{ $filters['title'] ?? '' }}" class="form-control" />
                    </div>
                    <div class="col-md-2 col-6 lib-field">
                        <label class="form-label">@lang('libraries.public.filters.content_type')</label>
                        <select name="content_type" class="form-select">
                            <option value="">@lang('libraries.public.filters.all')</option>
                            @foreach($types as $t)
                                <option value="{{ $t }}" @selected(($filters['content_type'] ?? '')===$t)>@lang('libraries.types.'.$t)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6 lib-field">
                        <label class="form-label">@lang('libraries.public.filters.subject')</label>
                        <select name="subject_id" class="form-select">
                            <option value="">@lang('libraries.public.filters.all')</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" @selected((string)($filters['subject_id'] ?? '')===(string)$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6 lib-field">
                        <label class="form-label">@lang('libraries.public.filters.teacher')</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">@lang('libraries.public.filters.all')</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" @selected((string)($filters['teacher_id'] ?? '')===(string)$t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-6 lib-field">
                        <label class="form-label">@lang('libraries.public.filters.tag')</label>
                        <input type="text" name="tag" value="{{ $filters['tag'] ?? '' }}" class="form-control" />
                    </div>
                    <div class="col-md-2 col-6 lib-field">
                        <label class="form-label">@lang('libraries.public.filters.sort')</label>
                        <select name="sort" class="form-select">
                            <option value="newest" @selected(($filters['sort'] ?? 'newest')==='newest')>@lang('libraries.public.filters.sort_newest')</option>
                            <option value="oldest" @selected(($filters['sort'] ?? '')==='oldest')>@lang('libraries.public.filters.sort_oldest')</option>
                        </select>
                    </div>
                    <div class="col-md-1 col-12 d-flex align-items-end lib-field">
                        <button class="btn btn-primary w-100" type="submit"><i class="la la-filter"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Items grid --}}
    @if($items->count() === 0)
        <div class="card"><div class="lib-empty"><i class="la la-book-open"></i>@lang('libraries.public.no_results')</div></div>
    @else
        <div class="row">
            @foreach($items as $item)
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
                            @if($item->description)
                                <p class="lib-card-desc">{{ \Illuminate\Support\Str::limit($item->description, 80) }}</p>
                            @endif
                        </div>
                        <div class="lib-card-footer">
                            @if($item->file_path)
                                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="@lang('libraries.actions.download')"><i class="la la-download"></i></a>
                            @endif
                            @if($item->external_url)
                                <a href="{{ $item->external_url }}" target="_blank" class="btn btn-sm btn-outline-primary" title="@lang('libraries.actions.open')"><i class="la la-external-link-alt"></i></a>
                            @endif
                            <a href="{{ route('admin.libraries.public.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary ms-auto" title="@lang('libraries.actions.edit')"><i class="la la-pen"></i></a>
                            <form action="{{ route('admin.libraries.public.destroy', $item->id) }}" method="POST" onsubmit="return confirm('@lang('libraries.confirm_delete')')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('libraries.actions.delete')"><i class="la la-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3">{{ $items->links() }}</div>
    @endif
</div>
</div>
@endsection
