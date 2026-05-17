@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.labs.title'))

@section('content')
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
        <a href="{{ route('admin.libraries.labs.manage') }}" class="btn btn-outline-primary btn-sm"><i class="la la-cog"></i> @lang('libraries.labs.manage_title')</a>
    </div>
</div>

<div class="content-body">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.public.index') }}">@lang('libraries.public.title')</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.libraries.private.index') }}">@lang('libraries.private.title')</a></li>
        <li class="nav-item"><a class="nav-link active" href="{{ route('admin.libraries.labs.index') }}">@lang('libraries.labs.title')</a></li>
    </ul>

    <div class="alert alert-info"><i class="la la-info-circle"></i> @lang('libraries.labs.coming_soon')</div>

    <div class="row g-3">
        {{-- categories sidebar --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">@lang('libraries.labs.categories')</h6></div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item {{ ! $activeCategory ? 'active' : '' }}">
                        <a href="{{ route('admin.libraries.labs.index') }}" class="text-decoration-none {{ ! $activeCategory ? 'text-white' : '' }}">@lang('libraries.labs.all_categories')</a>
                    </li>
                    @foreach($categories as $cat)
                        <li class="list-group-item {{ $activeCategory && $activeCategory->id === $cat->id ? 'active' : '' }}">
                            <a href="{{ route('admin.libraries.labs.index', ['category' => $cat->slug]) }}" class="text-decoration-none {{ $activeCategory && $activeCategory->id === $cat->id ? 'text-white' : '' }}">
                                <strong>{{ app()->getLocale() === 'ar' ? $cat->name_ar : ($cat->name_en ?? $cat->name_ar) }}</strong>
                            </a>
                            @if($cat->children->count())
                                <ul class="list-unstyled ms-3 mt-1 small">
                                    @foreach($cat->children as $child)
                                        <li><a href="{{ route('admin.libraries.labs.index', ['category' => $child->slug]) }}" class="text-decoration-none text-muted">— {{ app()->getLocale() === 'ar' ? $child->name_ar : ($child->name_en ?? $child->name_ar) }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- labs grid --}}
        <div class="col-md-9">
            @if($labs->count() === 0)
                <div class="card"><div class="card-body text-center text-muted py-5">@lang('libraries.labs.no_results')</div></div>
            @else
                <div class="row g-3">
                    @foreach($labs as $lab)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm">
                                @if($lab->thumbnail_path)
                                    <img src="{{ asset('storage/' . $lab->thumbnail_path) }}" class="card-img-top" style="height:140px;object-fit:cover" />
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:140px"><i class="la la-flask" style="font-size:3rem;color:#999"></i></div>
                                @endif
                                <div class="card-body">
                                    <h6 class="card-title mb-1">{{ $lab->title }}</h6>
                                    @if($lab->description)<p class="card-text small text-muted">{{ \Illuminate\Support\Str::limit($lab->description, 80) }}</p>@endif
                                </div>
                                <div class="card-footer bg-white">
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
@endsection
