@extends('layouts.app')

@section('title', __('appointments.schedule_show'))
@section('body_class', 'theme-light')

@php
    $isRtl    = app()->getLocale() === 'ar';
    $days     = __('appointments.days');
    $modes    = __('appointments.modes');
    $statuses = __('appointments.statuses');
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('appointments.schedule_show')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('appointments.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.appointment-schedules.index') }}">@lang('appointments.breadcrumb_schedules')</a></li>
                <li class="breadcrumb-item active">@lang('appointments.breadcrumb_show')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2">
        <a href="{{ route('manage.appointment-schedules.edit', $schedule->id) }}" class="btn btn-warning">
            <i class="la la-edit"></i> @lang('appointments.btn_edit')
        </a>
        <a href="{{ route('manage.appointment-schedules.index') }}" class="btn btn-secondary">
            @lang('appointments.btn_cancel')
        </a>
    </div>
</div>


<div class="card">
    <div class="card-header">
        <h4 class="card-title">{{ $schedule->title }}</h4>
    </div>
    <div class="card-content collapse show">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <dl>
                        <dt>@lang('appointments.field_date_from') / @lang('appointments.field_date_to')</dt>
                        <dd>{{ $schedule->date_from?->format('Y-m-d') }} — {{ $schedule->date_to?->format('Y-m-d') }}</dd>

                        <dt>@lang('appointments.field_time_from') / @lang('appointments.field_time_to')</dt>
                        <dd>{{ \Illuminate\Support\Str::substr($schedule->time_from, 0, 5) }} – {{ \Illuminate\Support\Str::substr($schedule->time_to, 0, 5) }}</dd>

                        <dt>@lang('appointments.field_slot_minutes')</dt>
                        <dd>{{ $schedule->slot_minutes }} @lang('appointments.min_label')</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <dl>
                        <dt>@lang('appointments.field_mode')</dt>
                        <dd>{{ $modes[$schedule->mode] ?? $schedule->mode }}</dd>

                        <dt>@lang('appointments.field_status')</dt>
                        <dd>{{ $statuses[$schedule->effective_status] ?? $schedule->effective_status }}</dd>

                        <dt>@lang('appointments.field_booking_open')</dt>
                        <dd>{{ $schedule->booking_open ? '✔' : '✘' }}</dd>
                    </dl>
                </div>
                <div class="col-md-4">
                    <dl>
                        <dt>@lang('appointments.table_booked')</dt>
                        <dd>
                            {{ $schedule->bookedCount() }} /
                            @if($schedule->availableCount() === null)
                                @lang('appointments.slots_unlimited')
                            @else
                                {{ $schedule->availableCount() }}
                            @endif
                        </dd>

                        <dt>@lang('appointments.field_location')</dt>
                        <dd>{{ $schedule->location ?? '—' }}</dd>

                        @if($schedule->notes)
                        <dt>@lang('appointments.field_notes')</dt>
                        <dd>{{ $schedule->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if(! empty($schedule->days))
            <div class="mt-1">
                <strong>@lang('appointments.field_days'):</strong>
                @foreach((array) $schedule->days as $day)
                    <span class="badge badge-light border mx-1">{{ $days[$day] ?? $day }}</span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Bookings list: Phase 2 placeholder --}}
<div class="card mt-2">
    <div class="card-header">
        <h4 class="card-title">@lang('appointments.page_title')</h4>
    </div>
    <div class="card-content collapse show">
        <div class="card-body text-center text-muted py-4">
            <i class="la la-clock" style="font-size:2rem"></i>
            <p class="mt-2">@lang('appointments.bookings_phase2')</p>
        </div>
    </div>
</div>
@endsection
