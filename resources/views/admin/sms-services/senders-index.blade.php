@extends('layouts.app')

@php
    $schoolName = app()->getLocale() === 'en'
        ? ($school->name_en ?: $school->name_ar ?: $school->name)
        : ($school->name_ar ?: $school->name);
@endphp

@section('title', trans('sms_services.senders_title', ['school' => $schoolName]))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            {{ trans('sms_services.senders_title', ['school' => $schoolName]) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.sms-services.index') }}">@lang('sms_services.breadcrumb')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.view_senders')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <div class="d-flex justify-content-md-end">
            <a href="{{ route('admin.sms-services.senders.create', $school) }}" class="btn btn-primary btn-sm">
                <i class="la la-plus"></i> @lang('sms_services.add_sender')
            </a>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('sms_services.name_ar')</th>
                            <th>@lang('sms_services.name_en')</th>
                            <th>@lang('sms_services.status')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($senders as $sender)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $sender->name_ar }}</strong></td>
                            <td>{{ $sender->name_en }}</td>
                            <td>
                                @switch($sender->status)
                                    @case('approved')
                                        <span class="badge bg-success">@lang('sms_services.sender_status_approved')</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">@lang('sms_services.sender_status_rejected')</span>
                                        @break
                                    @default
                                        <span class="badge bg-warning text-dark">@lang('sms_services.sender_status_pending')</span>
                                @endswitch
                            </td>
                            <td>
                                <form action="{{ route('admin.sms-services.senders.destroy', [$school, $sender]) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">@lang('sms_services.no_senders')</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
