@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.public.title'))

@section('content')
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
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="la la-search"></i> @lang('libraries.public.filter_title')</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.libraries.public.index') }}">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small">@lang('libraries.public.filters.title')</label>
                        <input type="text" name="title" value="{{ $filters['title'] ?? '' }}" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-2"><label class="form-label small">@lang('libraries.public.filters.content_type')</label>
                        <select name="content_type" class="form-select form-select-sm">
                            <option value="">@lang('libraries.public.filters.all')</option>
                            @foreach($types as $t)
                                <option value="{{ $t }}" @selected(($filters['content_type'] ?? '')===$t)>@lang('libraries.types.'.$t)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label small">@lang('libraries.public.filters.subject')</label>
                        <select name="subject_id" class="form-select form-select-sm">
                            <option value="">@lang('libraries.public.filters.all')</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" @selected((string)($filters['subject_id'] ?? '')===(string)$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label small">@lang('libraries.public.filters.teacher')</label>
                        <select name="teacher_id" class="form-select form-select-sm">
                            <option value="">@lang('libraries.public.filters.all')</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" @selected((string)($filters['teacher_id'] ?? '')===(string)$t->id)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label small">@lang('libraries.public.filters.tag')</label>
                        <input type="text" name="tag" value="{{ $filters['tag'] ?? '' }}" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100" type="submit"><i class="la la-filter"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Items grid --}}
    @if($items->count() === 0)
        <div class="card"><div class="card-body text-center text-muted py-5">@lang('libraries.public.no_results')</div></div>
    @else
        <div class="row g-3">
            @foreach($items as $item)
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        @if($item->thumbnail_path)
                            <img src="{{ asset('storage/' . $item->thumbnail_path) }}" alt="" class="card-img-top" style="height:140px;object-fit:cover" />
                        @else
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:140px">
                                <i class="la la-{{ $item->content_type === 'video' ? 'play-circle' : ($item->content_type === 'pdf' ? 'file-pdf' : ($item->content_type === 'image' ? 'image' : ($item->content_type === 'presentation' ? 'desktop' : ($item->content_type === 'link' ? 'link' : 'file')))) }}" style="font-size:3rem;color:#999"></i>
                            </div>
                        @endif
                        <div class="card-body">
                            <h6 class="card-title mb-1">{{ $item->title }}</h6>
                            <small class="text-muted d-block mb-2">
                                <span class="badge bg-info">@lang('libraries.types.'.$item->content_type)</span>
                                <span>· {{ $item->created_at?->format('Y-m-d') }}</span>
                            </small>
                            @if($item->description)
                                <p class="card-text small text-muted" style="max-height:3em;overflow:hidden">{{ \Illuminate\Support\Str::limit($item->description, 80) }}</p>
                            @endif
                        </div>
                        <div class="card-footer d-flex gap-1 bg-white">
                            @if($item->file_path)
                                <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="la la-download"></i></a>
                            @endif
                            @if($item->external_url)
                                <a href="{{ $item->external_url }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="la la-external-link-alt"></i></a>
                            @endif
                            <a href="{{ route('admin.libraries.public.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary ms-auto"><i class="la la-pen"></i></a>
                            <form action="{{ route('admin.libraries.public.destroy', $item->id) }}" method="POST" onsubmit="return confirm('@lang('libraries.confirm_delete')')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3">{{ $items->links() }}</div>
    @endif
</div>
@endsection
