@extends('layouts.app')

@php
    $schoolName = app()->getLocale() === 'en'
        ? ($school->name_en ?: $school->name_ar ?: $school->name)
        : ($school->name_ar ?: $school->name);
@endphp

@section('title', trans('sms_services.messages_title', ['school' => $schoolName]))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            {{ trans('sms_services.messages_title', ['school' => $schoolName]) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms-services.index') }}">@lang('sms_services.breadcrumb')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.view_log')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="text-muted small">@lang('sms_services.balance_used')</div>
                    <h4 class="mb-0">{{ $setting->sms_used }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="text-muted small">@lang('sms_services.balance_remaining')</div>
                    <h4 class="mb-0">{{ max(0, $setting->sms_total - $setting->sms_used) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="text-muted small">@lang('sms_services.balance_total')</div>
                    <h4 class="mb-0">{{ $setting->sms_total }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('sms_services.msg_recipient')</th>
                            <th>@lang('sms_services.msg_body')</th>
                            <th>@lang('sms_services.default_sender')</th>
                            <th>@lang('sms_services.status')</th>
                            <th>@lang('sms_services.msg_sent_at')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $msg)
                        <tr>
                            <td>{{ $loop->iteration + ($messages->firstItem() - 1) }}</td>
                            <td>{{ $msg->recipient }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($msg->body, 60) }}</td>
                            <td>{{ $msg->sender ? ($msg->sender->name_ar ?: $msg->sender->name_en) : '-' }}</td>
                            <td>
                                @switch($msg->status)
                                    @case('sent')
                                        <span class="badge bg-success">@lang('sms_services.msg_status_sent')</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-danger">@lang('sms_services.msg_status_failed')</span>
                                        @break
                                    @default
                                        <span class="badge bg-warning text-dark">@lang('sms_services.msg_status_queued')</span>
                                @endswitch
                            </td>
                            <td>{{ $msg->sent_at ? $msg->sent_at->format('Y-m-d H:i') : '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">@lang('sms_services.no_messages')</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $messages->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
