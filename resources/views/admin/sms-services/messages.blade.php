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
        <h2 class="content-header-title mb-0">
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

    {{-- Balance KPI strip --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <div class="ds-card card text-center">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">@lang('sms_services.balance_used')</div>
                    <div style="font-size:1.6rem;font-weight:800;color:#0f172a;">{{ number_format($setting->sms_used) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="ds-card card text-center">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">@lang('sms_services.balance_remaining')</div>
                    <div style="font-size:1.6rem;font-weight:800;" class="text-gold">{{ number_format(max(0, $setting->sms_total - $setting->sms_used)) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="ds-card card text-center">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">@lang('sms_services.balance_total')</div>
                    <div style="font-size:1.6rem;font-weight:800;" class="text-navy">{{ number_format($setting->sms_total) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Messages table --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="ds-card-title mb-0">
                <x-svg-icon name="journal-text" :size="16" class="me-1" /> @lang('sms_services.view_log')
            </h5>
            @if($messages->total() ?? 0)
                <span class="text-muted small">{{ number_format($messages->total()) }} رسالة</span>
            @endif
        </div>

        @if($messages->count() === 0)
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="chat-text" :size="32" /></div>
                <div class="ds-empty-title">@lang('sms_services.no_messages')</div>
                <div class="ds-empty-desc">لم يتم إرسال أي رسائل لهذه المدرسة بعد.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-label="سجل الرسائل القصيرة">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">@lang('sms_services.msg_recipient')</th>
                        <th scope="col">@lang('sms_services.msg_body')</th>
                        <th scope="col">@lang('sms_services.default_sender')</th>
                        <th scope="col">@lang('sms_services.status')</th>
                        <th scope="col">@lang('sms_services.msg_sent_at')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $msg)
                    <tr>
                        <td>{{ $loop->iteration + ($messages->firstItem() - 1) }}</td>
                        <td dir="ltr" class="text-start">{{ $msg->recipient }}</td>
                        <td class="text-muted small">{{ \Illuminate\Support\Str::limit($msg->body, 60) }}</td>
                        <td>{{ $msg->sender ? ($msg->sender->name_ar ?: $msg->sender->name_en) : '—' }}</td>
                        <td>
                            @switch($msg->status)
                                @case('sent')
                                    <span class="ds-badge-success">@lang('sms_services.msg_status_sent')</span>
                                    @break
                                @case('failed')
                                    <span class="ds-badge-danger">@lang('sms_services.msg_status_failed')</span>
                                    @break
                                @default
                                    <span class="ds-badge-warning">@lang('sms_services.msg_status_queued')</span>
                            @endswitch
                        </td>
                        <td class="text-nowrap small">{{ $msg->sent_at ? $msg->sent_at->format('Y-m-d H:i') : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="ds-card-footer card-footer">
            {{ $messages->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
