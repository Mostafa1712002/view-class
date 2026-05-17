@extends('layouts.app')

@section('title', __('subject_tracks.edit_title'))
@section('body_class', 'theme-light')

@push('styles')
<style>
    .tr-form-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1.5rem; max-width: 820px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .tr-form-card .form-label { font-weight: 600; color: #0f172a; font-size: .9rem; }
    .tr-form-card .form-control {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .85rem; font-size: .93rem; color: #0f172a;
    }
    .tr-form-card .form-control:focus {
        border-color: var(--gold-300, #e3c279); box-shadow: 0 0 0 .2rem rgba(207,160,70,.16);
    }
</style>
@endpush

@section('content')
<div class="content-header row" style="margin-bottom:1.25rem;">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title" style="font-size:1.5rem; font-weight:700; color:#0f172a;">
            @lang('subject_tracks.edit_title') — {{ $track->name }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb" style="padding:0; margin:0; background:transparent; font-size:.85rem;">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subject-tracks.index') }}">@lang('subject_tracks.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('subject_tracks.edit_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <div class="tr-form-card">
        <form method="POST" action="{{ route('admin.subject-tracks.update', $track->id) }}">
            @include('admin.subjects.tracks._form', ['track' => $track])
        </form>
    </div>
</div>
@endsection
