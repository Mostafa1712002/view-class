@extends('layouts.app')

@section('title', $isParent ? __('points_my.title_parent') : __('points_my.title'))
@section('body_class','theme-light')

@section('content')
@php $pageTitle = $isParent ? __('points_my.title_parent') : __('points_my.title'); @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0"><x-svg-icon name="trophy" /> {{ $pageTitle }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">{{ $pageTitle }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
@if($isParent)
    @if($children->isEmpty())
        <div class="card"><div class="card-body text-center text-muted">@lang('points_my.no_children')</div></div>
    @else
        <form method="GET" class="card card-body mb-3 form-inline" style="gap:.5rem;">
            <label for="child" class="mb-0">@lang('points_my.child')</label>
            <select name="child" id="child" class="form-control" onchange="this.form.submit()">
                @foreach($children as $c)
                    <option value="{{ $c->id }}" @selected($subject && $subject->id === $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    @endif
@endif

@if($subject)
    <div class="row mb-3">
        <div class="col-md-6 mb-2">
            <div class="card h-100"><div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted">@lang('points_my.balance')@if($isParent) — {{ $subject->name }}@endif</div>
                </div>
                <strong style="font-size:2rem; color:{{ $balance < 0 ? '#dc3545' : '#28a745' }};">{{ $balance }}</strong>
            </div></div>
        </div>
        @if($showBehavior)
        <div class="col-md-6 mb-2">
            <div class="card h-100"><div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted">@lang('points_my.behavior')</div>
                    <small class="text-muted">@lang('points_my.behavior_hint')</small>
                </div>
                <strong style="font-size:1.6rem; color:{{ $behaviorTotal < 0 ? '#dc3545' : '#28a745' }};">{{ $behaviorTotal }}</strong>
            </div></div>
        </div>
        @endif
    </div>

    @if(count($viceRules))
    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('points_my.vice_rules')</h5><small class="text-muted">@lang('points_my.vice_hint')</small></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>@lang('points_my.cols.rule')</th><th>@lang('points_my.rule_points')</th><th>@lang('points_my.deadline')</th><th>@lang('points_my.target')</th><th>@lang('points_my.window')</th></tr></thead>
                <tbody>
                @foreach($viceRules as $r)
                    @php $o = $r['options'] ?? []; $isVisits = $r['key'] === 'vice.class_visits'; @endphp
                    <tr>
                        <td>@lang('points.rules.'.$r['key'])</td>
                        <td>{{ $r['points'] }}</td>
                        <td>{{ $r['key'] === 'vice.school_schedule_on_time' ? ($o['deadline'] ?? __('points_my.none')) : '—' }}</td>
                        <td>{{ $isVisits ? ($o['visits_per_term'] ?? __('points_my.none')) : '—' }}</td>
                        <td>
                            @if($isVisits)
                                {{ ($o['window_start'] ?? null) && ($o['window_end'] ?? null) ? $o['window_start'].' '.__('points_my.to').' '.$o['window_end'] : __('points_my.current_term') }}
                            @else — @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('points_my.history')</h5></div>
        @if($history->isEmpty())
            <div class="card-body text-muted">@lang('points_my.empty_history')</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>@lang('points_my.cols.rule')</th><th>@lang('points_my.cols.source')</th>
                        <th>@lang('points_my.cols.points')</th><th>@lang('points_my.cols.reason')</th><th>@lang('points_my.cols.date')</th>
                    </tr></thead>
                    <tbody>
                    @foreach($history as $t)
                        <tr>
                            <td>{{ \Illuminate\Support\Facades\Lang::has('points.rules.'.$t->rule_key) ? __('points.rules.'.$t->rule_key) : $t->rule_key }}</td>
                            <td>{{ \Illuminate\Support\Facades\Lang::has('points.sources.'.$t->source_type) ? __('points.sources.'.$t->source_type) : $t->source_type }}</td>
                            <td>
                                @if($t->points > 0)<span class="badge badge-success">+{{ $t->points }}</span>
                                @elseif($t->points < 0)<span class="badge badge-danger">{{ $t->points }}</span>
                                @else<span class="badge badge-secondary">0</span>@endif
                            </td>
                            <td><small>{{ $t->reason ?: '—' }}</small></td>
                            <td><small>{{ optional($t->created_at)->format('Y-m-d H:i') }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body pb-0">{{ $history->links() }}</div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">@lang('points_my.rewards')</h5></div>
        @if($rewards->isEmpty())
            <div class="card-body text-muted">@lang('points_my.empty_rewards')</div>
        @else
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>@lang('points_my.reward_cols.period')</th><th>@lang('points_my.reward_cols.points')</th><th>@lang('points_my.reward_cols.status')</th><th>@lang('points_my.reward_cols.delivered')</th></tr></thead>
                    <tbody>
                    @foreach($rewards as $rw)
                        <tr>
                            <td>{{ $rw->period?->name ?? '—' }}</td>
                            <td>{{ $rw->points_at_award }}</td>
                            <td><span class="badge badge-{{ $rw->status === 'delivered' ? 'success' : 'warning' }}">@lang('points_my.status.'.$rw->status)</span></td>
                            <td><small>{{ optional($rw->delivered_at)->format('Y-m-d') ?? '—' }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
</div>
@endsection
