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
        <h2 class="content-header-title mb-0">
            {{ trans('sms_services.senders_title', ['school' => $schoolName]) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sms-services.index') }}">@lang('sms_services.breadcrumb')</a></li>
                <li class="breadcrumb-item active">@lang('sms_services.view_senders')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <div class="d-flex justify-content-md-end">
            <a href="{{ route('admin.sms-services.senders.create', $school) }}" class="btn btn-primary btn-sm">
                <x-svg-icon name="plus-lg" :size="14" class="me-1" /> @lang('sms_services.add_sender')
            </a>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="ds-card card">
        <div class="ds-card-header card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="ds-card-title mb-0">@lang('sms_services.view_senders')</h5>
        </div>

        @if($senders->count() === 0)
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="person-badge" :size="32" /></div>
                <div class="ds-empty-title">@lang('sms_services.no_senders')</div>
                <div class="ds-empty-desc">لم يتم طلب أي اسم مرسل لهذه المدرسة بعد.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" aria-label="أسماء المرسلين">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">@lang('sms_services.name_ar')</th>
                        <th scope="col">@lang('sms_services.name_en')</th>
                        <th scope="col">@lang('sms_services.status')</th>
                        <th scope="col" class="text-end">@lang('common.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($senders as $sender)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $sender->name_ar }}</strong></td>
                        <td dir="ltr" class="text-start">{{ $sender->name_en }}</td>
                        <td>
                            @switch($sender->status)
                                @case('approved')
                                    <span class="ds-badge-success">@lang('sms_services.sender_status_approved')</span>
                                    @break
                                @case('rejected')
                                    <span class="ds-badge-danger">@lang('sms_services.sender_status_rejected')</span>
                                    @break
                                @default
                                    <span class="ds-badge-warning">@lang('sms_services.sender_status_pending')</span>
                            @endswitch
                        </td>
                        <td class="text-end">
                            <form action="{{ route('admin.sms-services.senders.destroy', [$school, $sender]) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ds-action-btn ds-action-danger"
                                        title="@lang('common.delete')" aria-label="@lang('common.delete')">
                                    <x-svg-icon name="trash" :size="14" />
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
