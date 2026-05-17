@extends('layouts.app')

@section('title', __('school_branches.add_branch'))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('school_branches.add_branch')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.school-branches.index') }}">@lang('school_branches.title')</a></li>
                <li class="breadcrumb-item active">@lang('school_branches.add_branch')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.school-branches.store') }}" method="POST">
                @csrf
                @include('admin.school-branches._form')

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('common.save')
                    </button>
                    <a href="{{ route('admin.school-branches.index') }}" class="btn btn-secondary">
                        <i class="la la-times"></i> @lang('common.cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
