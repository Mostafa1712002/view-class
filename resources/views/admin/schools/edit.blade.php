@extends('layouts.app')

@section('title', __('schools.edit_school'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('schools.edit_school')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('common.edit')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.schools.update', $school) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.schools._form')

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('common.save')
                    </button>
                    <a href="{{ route('admin.schools.index') }}" class="btn btn-secondary">
                        <i class="la la-times"></i> @lang('common.cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
