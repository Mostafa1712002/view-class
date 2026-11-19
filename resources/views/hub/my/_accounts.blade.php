{{-- $accounts: HubPlatformAccount collection (platform loaded); $canSee: the viewer is the owner or the owner's parent. --}}
@if($accounts->isNotEmpty())
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('hub_my.platform') }}</th><th scope="col">{{ __('hub_my.role') }}</th><th scope="col">{{ __('hub_my.status') }}</th>@if($canSee)<th scope="col">{{ __('hub_my.credentials') }}</th>@endif
</tr></thead><tbody>
@foreach($accounts as $a)
<tr><td>{{ $a->platform?->name_ar }}</td><td>{{ __('hub_my.role_'.$a->role) }}</td><td>{{ __('hub_my.status_'.$a->status) }}</td>
@if($canSee)<td>@if($a->login)
<details><summary>{{ __('hub_my.show_credentials') }}</summary>
<dl class="mb-0"><dt>{{ __('hub_my.login') }}</dt><dd><code dir="ltr">{{ $a->login }}</code></dd>
@if($a->managed && $a->password)<dt>{{ __('hub_my.password') }}</dt><dd><code dir="ltr">{{ $a->password }}</code></dd>@endif</dl>
<small class="text-muted">{{ __('hub_my.credentials_note') }}</small></details>@else—@endif
@if(Route::has('my.packages.accounts.open') && $a->status === 'active' && $a->enabled && ($a->managed || $a->link_confirmed_by))
<a class="btn btn-sm btn-primary mt-1" href="{{ route('my.packages.accounts.open', $a->id) }}" target="_blank" rel="noopener">{{ __('hub_my.open') }}</a>@endif</td>@endif</tr>
@endforeach
</tbody></table></div>
@endif
