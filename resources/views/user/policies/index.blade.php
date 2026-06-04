@extends('layouts.app')
@section('title', __('policies.my_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('policies.my_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">@lang('policies.my_title')</li>
        </ol>
    </div>
</div>
<div class="content-body">
    <p class="text-muted">@lang('policies.my_intro')</p>

    {{-- Search + read-status filter --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('policies.my.index') }}" class="form-row align-items-end">
                <div class="form-group col-md-6 mb-2">
                    <label class="form-label small mb-1">@lang('policies.search')</label>
                    <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control form-control-sm" placeholder="@lang('policies.search')">
                </div>
                <div class="form-group col-md-4 mb-2">
                    <label class="form-label small mb-1">@lang('policies.cols.status')</label>
                    <select name="status" class="custom-select custom-select-sm">
                        <option value="" @selected(($status ?? '')==='')>@lang('policies.filter.all')</option>
                        <option value="unread" @selected(($status ?? '')==='unread')>@lang('policies.status.unread')</option>
                        <option value="read" @selected(($status ?? '')==='read')>@lang('policies.status.read')</option>
                    </select>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="la la-search"></i> @lang('policies.search_btn')</button>
                </div>
            </form>
        </div>
    </div>

    @if($policies->isEmpty())
        <div class="card"><div class="card-body text-center text-muted py-5"><i class="la la-gavel" style="font-size:2.5rem;"></i><p class="mb-0 mt-2">@lang('policies.my_empty')</p></div></div>
    @else
        <div class="row">
            @foreach($policies as $p)
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-1">{{ $p->title }}</h5>
                                @if(isset($readIds[$p->id]))
                                    <span class="badge badge-success">@lang('policies.status.read')</span>
                                @else
                                    <span class="badge badge-warning">@lang('policies.status.unread')</span>
                                @endif
                            </div>
                            <div class="small text-muted mb-2">
                                <span><i class="la la-calendar"></i> {{ optional($p->created_at)->format('Y-m-d') }}</span>
                                @if($p->creator)<span class="ml-2"><i class="la la-user"></i> {{ $p->creator->name }}</span>@endif
                                @if($p->file_path)
                                    <span class="badge badge-light ml-1"><i class="la la-file"></i> @lang('policies.type.file')</span>
                                @elseif($p->external_url)
                                    <span class="badge badge-light ml-1"><i class="la la-link"></i> @lang('policies.type.link')</span>
                                @endif
                            </div>
                            @if($p->description)<p class="text-muted small flex-grow-1">{{ \Illuminate\Support\Str::limit($p->description, 120) }}</p>@endif
                            <div class="mt-2 d-flex flex-wrap" style="gap:.35rem;">
                                <a href="{{ route('policies.my.show', $p->id) }}" class="btn btn-sm btn-primary"><i class="la la-book-open"></i> @lang('policies.actions.read')</a>
                                @if($p->file_path)
                                    <a href="{{ $p->fileUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="la la-download"></i> @lang('policies.actions.open')</a>
                                @elseif($p->external_url)
                                    <a href="{{ $p->external_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="la la-external-link-alt"></i> @lang('policies.actions.open_link')</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
