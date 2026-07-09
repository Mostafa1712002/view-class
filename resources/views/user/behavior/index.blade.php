@extends('layouts.app')

@section('title', __('behavior.my.title'))
@section('body_class','theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('behavior.my.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('behavior.my.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @forelse($subjects as $s)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                <h5 class="mb-0">{{ $s->name }}</h5>
                <span>@lang('behavior.records.points_total'):
                    <strong style="font-size:1.35rem; color:{{ $s->pointsTotal < 0 ? '#dc3545' : '#28a745' }};">{{ $s->pointsTotal }}</strong>
                </span>
            </div>
            @if($s->behaviorRecords->isEmpty())
                <div class="card-body text-muted">@lang('behavior.my.empty')</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>@lang('behavior.records.cols.behavior')</th>
                                <th>@lang('behavior.records.cols.action')</th>
                                <th>@lang('behavior.records.cols.points')</th>
                                <th>@lang('behavior.records.fields.note')</th>
                                <th>@lang('behavior.records.cols.date')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($s->behaviorRecords as $r)
                                <tr>
                                    <td>{{ optional($r->behavior)->name ?? '—' }}</td>
                                    <td>{{ $r->action ? \Illuminate\Support\Str::limit($r->action->description, 40) : '—' }}</td>
                                    <td>
                                        @if($r->points > 0)<span class="badge badge-success">+{{ $r->points }}</span>
                                        @elseif($r->points < 0)<span class="badge badge-danger">{{ $r->points }}</span>
                                        @else<span class="badge badge-secondary">0</span>@endif
                                    </td>
                                    <td><small>{{ $r->note ? \Illuminate\Support\Str::limit($r->note, 60) : '—' }}</small></td>
                                    <td><small>{{ optional($r->created_at)->format('Y-m-d H:i') }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @empty
        <div class="card"><div class="card-body text-center text-muted">@lang('behavior.my.none')</div></div>
    @endforelse
</div>
@endsection
