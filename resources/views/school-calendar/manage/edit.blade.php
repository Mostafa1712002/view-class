@extends('layouts.app')

@section('title', __('school_calendar.edit_title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('school_calendar.edit_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('school_calendar.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.school-calendar.index') }}">@lang('school_calendar.title')</a></li>
                <li class="breadcrumb-item active">@lang('school_calendar.edit_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        <form action="{{ route('manage.school-calendar.destroy', $event->id) }}" method="POST" id="delete-event-form">
            @csrf @method('DELETE')
            <button type="button" class="btn btn-danger btn-delete-top" data-title="{{ $event->title }}">
                <i class="la la-trash"></i> @lang('school_calendar.btn_delete')
            </button>
        </form>
    </div>
</div>


<div class="card">
    <div class="card-content collapse show">
        <div class="card-body">
            <form action="{{ route('manage.school-calendar.update', $event->id) }}" method="POST">
                @csrf @method('PUT')
                @include('school-calendar.manage._form', ['event' => $event])
                <div class="mt-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('school_calendar.btn_save')
                    </button>
                    <a href="{{ route('manage.school-calendar.index') }}" class="btn btn-secondary">
                        @lang('school_calendar.btn_cancel')
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.btn-delete-top').on('click', function () {
        var title = $(this).data('title');
        var msg   = '@lang('school_calendar.confirm_delete')'.replace(':title', title);
        window.vcConfirm({ title: msg }).then(function (r) {
            if (r.isConfirmed) {
                document.getElementById('delete-event-form').submit();
            }
        });
    });
});
</script>
@endpush
