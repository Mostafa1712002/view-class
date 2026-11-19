@extends('layouts.app')
@section('title', __('hub_admin.packages'))
@section('page-title', __('hub_admin.packages'))
@section('content')
<div class="card"><div class="card-body">
<a class="btn btn-primary mb-3" href="{{ route('admin.hub.packages.create') }}"><x-svg-icon name="plus-lg" /> {{ __('hub_admin.new_package') }}</a>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('hub_admin.name_ar') }}</th><th scope="col">{{ __('hub_admin.price') }}</th><th scope="col">{{ __('hub_admin.duration_days') }}</th><th scope="col">{{ __('hub_admin.items') }}</th><th scope="col">{{ __('hub_admin.is_purchasable') }}</th><th scope="col">{{ __('hub_admin.is_active') }}</th><th scope="col"></th>
</tr></thead><tbody>
@forelse($packages as $p)
<tr><td>{{ $p->name_ar }}</td><td>{{ $p->price }}</td><td>{{ $p->duration_days }}</td><td>{{ $p->items->count() }}</td>
<td>{{ $p->is_purchasable ? '✓' : '—' }}</td><td>{{ $p->is_active ? '✓' : '—' }}</td>
<td class="text-nowrap"><a href="{{ route('admin.hub.packages.edit', $p->id) }}">{{ __('hub_admin.edit') }}</a>
<form method="POST" action="{{ route('admin.hub.packages.toggle', $p->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-link p-0">{{ __('hub_admin.toggle') }}</button></form>
<form method="POST" action="{{ route('admin.hub.packages.destroy', $p->id) }}" class="d-inline" onsubmit="return confirm(@js(__('hub_admin.confirm_delete')))">@csrf @method('DELETE')<button class="btn btn-link text-danger p-0">{{ __('hub_admin.delete') }}</button></form></td></tr>
@empty<tr><td colspan="7">{{ __('hub_admin.empty') }}</td></tr>@endforelse
</tbody></table></div>{{ $packages->links() }}
</div></div>
@endsection
