@extends('layouts.app')

@section('title', __('sms_services.title'))

@section('body_class', 'theme-light')

@section('content')

{{-- Page header --}}
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sms_services.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.breadcrumb')</li>
            </ol>
        </div>
        @if(__('sms_services.subtitle') !== 'sms_services.subtitle')
            <p class="text-muted mb-0">@lang('sms_services.subtitle')</p>
        @endif
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
        <x-svg-icon name="info-circle" :size="16" />
        <span>@lang('sms_services.note_no_gateway')</span>
    </div>

    <div class="ds-card card">
        <div class="ds-card-header card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="ds-card-title mb-0">@lang('sms_services.title')</h5>
        </div>

        @if($schools->count() === 0)
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="chat-text" :size="32" /></div>
                <div class="ds-empty-title">@lang('sms_services.no_schools')</div>
                <div class="ds-empty-desc">لا توجد مدارس مرتبطة بخدمة الرسائل القصيرة.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-label="خدمات الرسائل القصيرة">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">@lang('sms_services.school')</th>
                        <th scope="col">@lang('sms_services.balance')</th>
                        <th scope="col">@lang('sms_services.status')</th>
                        <th scope="col">@lang('sms_services.default_sender')</th>
                        <th scope="col" class="text-end">@lang('sms_services.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schools as $school)
                        @php
                            $setting = $school->smsSetting;
                            $used  = $setting?->sms_used ?? 0;
                            $total = $setting?->sms_total ?? 0;
                            $isActive = $setting?->is_active ?? false;
                            $defaultSender = $setting?->defaultSender;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration + ($schools->firstItem() - 1) }}</td>
                            <td>
                                <strong>{{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar ?: $school->name) : ($school->name_ar ?: $school->name) }}</strong>
                                @if($school->code)
                                    <br><small class="text-muted">{{ $school->code }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ trans('sms_services.balance_format', ['used' => $used, 'total' => $total]) }}</span>
                            </td>
                            <td>
                                @if($isActive)
                                    <span class="ds-badge-success">@lang('sms_services.active')</span>
                                @else
                                    <span class="ds-badge-danger">@lang('sms_services.inactive')</span>
                                @endif
                            </td>
                            <td>
                                @if($defaultSender)
                                    <span class="ds-badge-info">{{ app()->getLocale() === 'en' ? $defaultSender->name_en : $defaultSender->name_ar }}</span>
                                @else
                                    <span class="text-muted">@lang('sms_services.no_default_sender')</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                    <a href="{{ route('admin.sms-services.connection.edit', $school) }}"
                                       class="ds-action-btn" title="@lang('sms_services.edit_connection')" aria-label="@lang('sms_services.edit_connection')">
                                        <x-svg-icon name="plug" :size="14" />
                                    </a>
                                    <a href="{{ route('admin.sms-services.default-sender.edit', $school) }}"
                                       class="ds-action-btn" title="@lang('sms_services.edit_default_sender')" aria-label="@lang('sms_services.edit_default_sender')">
                                        <x-svg-icon name="person-badge" :size="14" />
                                    </a>
                                    <a href="{{ route('admin.sms-services.senders.index', $school) }}"
                                       class="ds-action-btn" title="@lang('sms_services.view_senders')" aria-label="@lang('sms_services.view_senders')">
                                        <x-svg-icon name="list" :size="14" />
                                    </a>
                                    <a href="{{ route('admin.sms-services.senders.create', $school) }}"
                                       class="ds-action-btn" title="@lang('sms_services.request_sender_name')" aria-label="@lang('sms_services.request_sender_name')">
                                        <x-svg-icon name="plus-lg" :size="14" />
                                    </a>
                                    <a href="{{ route('admin.sms-services.messages.index', $school) }}"
                                       class="ds-action-btn" title="@lang('sms_services.view_log')" aria-label="@lang('sms_services.view_log')">
                                        <x-svg-icon name="journal-text" :size="14" />
                                    </a>
                                    <form action="{{ route('admin.sms-services.toggle', $school) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('{{ $isActive ? __('sms_services.disable') : __('sms_services.enable') }}؟')">
                                        @csrf
                                        <button type="submit" class="ds-action-btn {{ $isActive ? 'ds-action-danger' : '' }}"
                                                title="{{ $isActive ? __('sms_services.disable') : __('sms_services.enable') }}"
                                                aria-label="{{ $isActive ? __('sms_services.disable') : __('sms_services.enable') }}">
                                            <x-svg-icon name="power" :size="14" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="ds-card-footer card-footer">
            {{ $schools->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
