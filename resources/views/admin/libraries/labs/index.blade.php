@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.labs.title'))

@include('admin.libraries._styles')

@section('content')
<div class="lib-scope">
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.labs.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">@lang('libraries.breadcrumb')</li>
                <li class="breadcrumb-item active">@lang('libraries.labs.title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-end">
        {{-- Lab management is admin-only; teachers browse/view only (card #290). --}}
        @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
        <a href="{{ route('admin.libraries.labs.manage') }}" class="btn btn-outline-primary btn-sm"><i class="la la-cog"></i> @lang('libraries.labs.manage_title')</a>
        @endif
    </div>
</div>

<div class="content-body">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.public.index') }}">@lang('libraries.public.title')</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.private.index') }}">@lang('libraries.private.title')</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('admin.libraries.labs.index') }}">@lang('libraries.labs.title')</a></li>
    </ul>

    <div class="alert alert-info"><i class="la la-info-circle"></i> @lang('libraries.labs.coming_soon')</div>

    <div class="row">
        {{-- categories sidebar --}}
        <div class="col-md-3 col-12 mb-3">
            <div class="card lib-cat-card">
                <div class="card-header"><i class="la la-folder"></i> @lang('libraries.labs.categories')</div>
                <ul class="list-group list-group-flush lib-cat-list mb-0">
                    <li class="list-group-item {{ ! $activeCategory ? 'active' : '' }}">
                        <a href="{{ route('admin.libraries.labs.index') }}"><strong>@lang('libraries.labs.all_categories')</strong></a>
                    </li>
                    @foreach($categories as $cat)
                        <li class="list-group-item {{ $activeCategory && $activeCategory->id === $cat->id ? 'active' : '' }}">
                            <a href="{{ route('admin.libraries.labs.index', ['category' => $cat->slug]) }}">
                                <strong>{{ app()->getLocale() === 'ar' ? $cat->name_ar : ($cat->name_en ?? $cat->name_ar) }}</strong>
                            </a>
                            @if($cat->children->count())
                                <ul class="list-unstyled mt-1">
                                    @foreach($cat->children as $child)
                                        <li class="lib-subcat"><a href="{{ route('admin.libraries.labs.index', ['category' => $child->slug]) }}">— {{ app()->getLocale() === 'ar' ? $child->name_ar : ($child->name_en ?? $child->name_ar) }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- labs grid --}}
        <div class="col-md-9 col-12">
            @if($labs->count() === 0)
                <div class="card"><div class="lib-empty"><i class="la la-flask"></i>@lang('libraries.labs.no_results')</div></div>
            @else
                <div class="row">
                    @foreach($labs as $lab)
                        <div class="col-md-6 col-lg-4 col-12 mb-4">
                            <div class="lib-card h-100 d-flex flex-column">
                                <div class="lib-card-media">
                                    @if($lab->thumbnail_path)
                                        <img src="{{ asset('storage/' . $lab->thumbnail_path) }}" alt="{{ $lab->title }}" />
                                    @else
                                        <i class="la la-flask lib-icon"></i>
                                    @endif
                                </div>
                                <div class="lib-card-body flex-grow-1">
                                    <div class="lib-card-title">{{ $lab->title }}</div>
                                    @if($lab->description)<p class="lib-card-desc">{{ \Illuminate\Support\Str::limit($lab->description, 80) }}</p>@endif
                                </div>
                                <div class="lib-card-footer">
                                    @if($lab->external_url)
                                        <a href="{{ $lab->external_url }}" target="_blank" class="btn btn-primary btn-sm w-100"><i class="la la-external-link-alt"></i> @lang('libraries.labs.open')</a>
                                    @else
                                        <a href="{{ route('admin.libraries.labs.show', $lab->id) }}" class="btn btn-outline-primary btn-sm w-100"><i class="la la-eye"></i> @lang('libraries.actions.open')</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">{{ $labs->links() }}</div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection
