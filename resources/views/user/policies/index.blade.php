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
                            @if($p->description)<p class="text-muted small flex-grow-1">{{ \Illuminate\Support\Str::limit($p->description, 120) }}</p>@endif
                            <a href="{{ route('policies.my.show', $p->id) }}" class="btn btn-sm btn-primary mt-2"><i class="la la-book-open"></i> @lang('policies.actions.read')</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
