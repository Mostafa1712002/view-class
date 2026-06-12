@extends('layouts.app')

@section('title', __('virtual_classes.create_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('virtual_classes.create_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">@lang('virtual_classes.breadcrumb_home')</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('manage.virtual-classes.index') }}">@lang('virtual_classes.title')</a>
                </li>
                <li class="breadcrumb-item active">@lang('virtual_classes.create_title')</li>
            </ol>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-content collapse show">
        <div class="card-body">
            <form action="{{ route('manage.virtual-classes.store') }}" method="POST">
                @csrf
                @include('virtual-classes.manage._form', ['vc' => null])
                <div class="mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('virtual_classes.btn_save')
                    </button>
                    <a href="{{ route('manage.virtual-classes.index') }}" class="btn btn-secondary">
                        @lang('virtual_classes.btn_cancel_form')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
