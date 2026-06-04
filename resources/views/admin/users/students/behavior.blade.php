@extends('layouts.app')

@section('title', __('users.behavior_link').' — '.$student->name)
@section('body_class','theme-light')

@include('admin.users.students._sub_styles')

@section('content')
@php $active = 'behavior'; @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $student->name }} — @lang('users.behavior_link')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.show', $student->id) }}">{{ $student->name }}</a></li>
                <li class="breadcrumb-item active">@lang('users.behavior_link')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.users.students._header')
    @include('admin.users.students._subnav')

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:.5rem;">
        <div class="card mb-0" style="min-width:220px;">
            <div class="card-body py-2">
                <span class="text-muted">@lang('behavior.records.points_total'):</span>
                <strong style="font-size:1.4rem; color:{{ ($pointsTotal ?? 0) < 0 ? '#dc3545' : '#28a745' }};">{{ $pointsTotal ?? 0 }}</strong>
            </div>
        </div>
        <a href="{{ route('admin.behavior.records.create', ['tab' => 'student']) }}" class="btn btn-primary btn-sm">
            <i class="la la-plus"></i> @lang('behavior.records.add')
        </a>
    </div>

    <div class="card">
        @if(($records ?? collect())->isEmpty())
            <div class="card-body">
                <div class="empty-state">
                    <div class="icon-wrap"><i class="la la-balance-scale"></i></div>
                    <h4>@lang('users.student_behavior_empty_title')</h4>
                    <p>@lang('users.student_behavior_empty_desc')</p>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>@lang('behavior.records.cols.behavior')</th>
                            <th>@lang('behavior.records.cols.action')</th>
                            <th>@lang('behavior.records.cols.points')</th>
                            <th>@lang('behavior.records.cols.notified')</th>
                            <th>@lang('behavior.records.cols.followup')</th>
                            <th>@lang('behavior.records.cols.recorded_by')</th>
                            <th>@lang('behavior.records.cols.date')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $r)
                            <tr>
                                <td>{{ optional($r->behavior)->name ?? '—' }}</td>
                                <td>{{ $r->action ? \Illuminate\Support\Str::limit($r->action->description, 40) : '—' }}</td>
                                <td>
                                    @if($r->points > 0)<span class="badge badge-success">+{{ $r->points }}</span>
                                    @elseif($r->points < 0)<span class="badge badge-danger">{{ $r->points }}</span>
                                    @else<span class="badge badge-secondary">0</span>@endif
                                </td>
                                <td>@if($r->notified_parent)<span class="badge badge-info">@lang('behavior.yes')</span>@else<span class="badge badge-secondary">@lang('behavior.no')</span>@endif</td>
                                <td>@if($r->needs_followup)<span class="badge badge-warning">@lang('behavior.yes')</span>@else<span class="badge badge-secondary">@lang('behavior.no')</span>@endif</td>
                                <td><small>{{ optional($r->recorder)->name ?? '—' }}</small></td>
                                <td><small>{{ optional($r->created_at)->format('Y-m-d H:i') }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
