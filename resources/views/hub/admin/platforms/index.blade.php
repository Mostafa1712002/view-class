@extends('layouts.app')
@section('title', __('hub_admin.platforms'))
@section('page-title', __('hub_admin.platforms'))
@section('content')
<div class="card"><div class="card-body">
<a class="btn btn-primary mb-3" href="{{ route('admin.hub.platforms.create') }}"><x-svg-icon name="plus-lg" /> {{ __('hub_admin.new_platform') }}</a>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('hub_admin.name_ar') }}</th><th scope="col">{{ __('hub_admin.slug') }}</th><th scope="col">{{ __('hub_admin.is_enabled') }}</th><th scope="col">{{ __('hub_admin.last_ok') }}</th><th scope="col">{{ __('hub_admin.last_error') }}</th><th scope="col"></th>
</tr></thead><tbody>
@forelse($platforms as $p)
<tr><td>{{ $p->name_ar }}</td><td>{{ $p->slug }}</td><td>{{ $p->is_enabled ? '✓' : '—' }}</td>
<td>{{ $p->last_ok_at?->format('Y-m-d H:i') ?? '—' }}</td><td>{{ $p->last_error ?? '—' }}</td>
<td><a href="{{ route('admin.hub.platforms.edit', $p->id) }}">{{ __('hub_admin.edit') }}</a></td></tr>
@empty<tr><td colspan="6">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>
</div></div>
@endsection
