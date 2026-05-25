@extends('layouts.app')

@section('title', __('sms_services.title'))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('sms_services.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.breadcrumb')</li>
            </ol>
        </div>
        <p class="text-muted mb-0">@lang('sms_services.subtitle')</p>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="alert alert-info" role="alert">
        <i class="la la-info-circle"></i> @lang('sms_services.note_no_gateway')
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('sms_services.school')</th>
                            <th>@lang('sms_services.balance')</th>
                            <th>@lang('sms_services.status')</th>
                            <th>@lang('sms_services.default_sender')</th>
                            <th>@lang('sms_services.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
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
                                    <span class="badge bg-success">@lang('sms_services.active')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('sms_services.inactive')</span>
                                @endif
                            </td>
                            <td>
                                @if($defaultSender)
                                    <span class="badge bg-info text-white">{{ app()->getLocale() === 'en' ? $defaultSender->name_en : $defaultSender->name_ar }}</span>
                                @else
                                    <span class="text-muted">@lang('sms_services.no_default_sender')</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <i class="la la-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.sms-services.connection.edit', $school) }}"><i class="la la-link"></i> @lang('sms_services.edit_connection')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.sms-services.default-sender.edit', $school) }}"><i class="la la-user-tag"></i> @lang('sms_services.edit_default_sender')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.sms-services.senders.index', $school) }}"><i class="la la-list"></i> @lang('sms_services.view_senders')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.sms-services.senders.create', $school) }}"><i class="la la-plus"></i> @lang('sms_services.request_sender_name')</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.sms-services.messages.index', $school) }}"><i class="la la-history"></i> @lang('sms_services.view_log')</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.sms-services.toggle', $school) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="la la-power-off"></i>
                                                    {{ $isActive ? __('sms_services.disable') : __('sms_services.enable') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">@lang('sms_services.no_schools')</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $schools->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
