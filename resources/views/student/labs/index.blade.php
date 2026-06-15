@extends('layouts.admin')

@section('body_class','theme-light')
@section('title', __('student.labs.title'))

@include('admin.libraries._styles')

@section('content')
<div class="lib-scope">
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('student.labs.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">@lang('student.labs.home')</a></li>
                <li class="breadcrumb-item active">@lang('student.labs.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <div class="row">
        {{-- categories sidebar --}}
        <div class="col-md-3 col-12 mb-3">
            <div class="card lib-cat-card">
                <div class="card-header"><i class="la la-folder"></i> @lang('student.labs.categories')</div>
                <ul class="list-group list-group-flush lib-cat-list mb-0">
                    <li class="list-group-item {{ ! $activeCategory ? 'active' : '' }}">
                        <a href="{{ route('student.labs.index') }}"><strong>@lang('student.labs.all_categories')</strong></a>
                    </li>
                    @foreach($categories as $cat)
                        <li class="list-group-item {{ $activeCategory && $activeCategory->id === $cat->id ? 'active' : '' }}">
                            <a href="{{ route('student.labs.index', ['category' => $cat->slug]) }}">
                                <strong>{{ app()->getLocale() === 'ar' ? $cat->name_ar : ($cat->name_en ?? $cat->name_ar) }}</strong>
                            </a>
                            @if($cat->children->count())
                                <ul class="list-unstyled mt-1">
                                    @foreach($cat->children as $child)
                                        <li class="lib-subcat {{ $activeCategory && $activeCategory->id === $child->id ? 'fw-bold' : '' }}">
                                            <a href="{{ route('student.labs.index', ['category' => $child->slug]) }}">— {{ app()->getLocale() === 'ar' ? $child->name_ar : ($child->name_en ?? $child->name_ar) }}</a>
                                        </li>
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
                <div class="card"><div class="lib-empty"><i class="la la-flask"></i>@lang('student.labs.no_results')</div></div>
            @else
                <div class="row">
                    @foreach($labs as $lab)
                        @php $labUrl = preg_match('#^https?://#i', (string) $lab->external_url) ? $lab->external_url : null; @endphp
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
                                    @if($lab->category)
                                        <div class="lib-card-meta"><i class="la la-folder"></i> {{ app()->getLocale() === 'ar' ? $lab->category->name_ar : ($lab->category->name_en ?? $lab->category->name_ar) }}</div>
                                    @endif
                                    @if($lab->description)<p class="lib-card-desc">{{ \Illuminate\Support\Str::limit($lab->description, 80) }}</p>@endif
                                </div>
                                <div class="lib-card-footer">
                                    @if($labUrl)
                                        <a href="{{ $labUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm w-100"><i class="la la-external-link-alt"></i> @lang('student.labs.open')</a>
                                    @else
                                        <span class="btn btn-outline-secondary btn-sm w-100 disabled"><i class="la la-flask"></i> @lang('student.labs.open')</span>
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
