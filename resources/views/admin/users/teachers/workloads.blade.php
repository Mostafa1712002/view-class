@extends('layouts.app')
@section('title', __('users.workloads'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.workloads')</h2></div>
<div class="card"><div class="card-body">
    <table class="table table-hover">
        <thead><tr>
            <th>@lang('users.name')</th>
            <th>@lang('users.specialization')</th>
            <th>@lang('users.classes_link')</th>
        </tr></thead>
        <tbody>
        @forelse($teachers as $t)
            <tr>
                <td>{{ $t->name }}</td>
                <td>{{ $t->specialization ?? '—' }}</td>
                <td><span class="badge bg-info">{{ $t->classes_count ?? 0 }}</span></td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted py-3">@lang('users.no_results')</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
