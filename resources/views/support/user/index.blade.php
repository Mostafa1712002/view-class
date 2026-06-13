@extends('layouts.app')

@section('title', __('support.my_tickets_title'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('support.my_tickets_title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('support.breadcrumb_home')</a></li>
                <li class="breadcrumb-item active">@lang('support.breadcrumb_my_tickets')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }}">
        <a href="{{ route('my.support.create') }}" class="btn btn-primary">
            <i class="la la-plus"></i> @lang('support.btn_new_ticket')
        </a>
    </div>
</div>

{{-- #186: status counters --}}
@isset($counts)
<div class="row mb-2">
    @foreach([['all','la-list','secondary'],['open','la-folder-open','info'],['in_progress','la-spinner','warning'],['resolved','la-check','success'],['closed','la-lock','dark']] as [$key,$icon,$color])
        <div class="col-6 col-md mb-2">
            <div class="card mb-0">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">@lang('support.count_'.$key)</div>
                        <div class="h4 mb-0">{{ $counts[$key] }}</div>
                    </div>
                    <i class="la {{ $icon }} text-{{ $color }}" style="font-size:1.6rem;"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endisset


<div class="card">
    <div class="card-content">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('support.field_subject')</th>
                        <th>@lang('support.field_related_student')</th>
                        <th>@lang('support.field_category')</th>
                        <th>@lang('support.field_status')</th>
                        <th>@lang('support.field_priority')</th>
                        <th>@lang('support.field_created_at')</th>
                        <th>@lang('support.field_actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td>{{ $ticket->relatedStudent?->name ?? '—' }}</td>
                            <td>{{ __('support.category_' . $ticket->category) }}</td>
                            <td>
                                <span class="badge badge-{{ $ticket->statusColor() }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $ticket->priorityColor() }}">
                                    {{ $ticket->priorityLabel() }}
                                </span>
                            </td>
                            <td>{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('my.support.show', $ticket->id) }}" class="btn btn-sm btn-info">
                                    <i class="la la-eye"></i> @lang('support.btn_view')
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                @lang('support.empty_tickets')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
            <div class="p-2">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
