@extends('layouts.app')

@section('title', __('class_visits.form.edit_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .cv-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.55rem 1rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .cv-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .form-label { font-size:.82rem; color:#475569; font-weight:600; margin-bottom:.25rem; }
    body.theme-light .form-control, body.theme-light select { border-radius:10px; border:1px solid #e5e7eb; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('class_visits.form.edit_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.class-visits.index') }}">@lang('class_visits.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('class_visits.form.edit_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.class-visits._form')
</div>
@endsection
