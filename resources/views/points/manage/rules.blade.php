@extends('layouts.app')
@section('title', __('points_manage.rules_title'))
@section('page-title', __('points_manage.rules_title'))
@section('content')
<div class="card"><div class="card-body">
@include('points.manage._nav')
@php($canPlatform = $isPlatform)
@if($canPlatform)
<form method="POST" action="{{ route('manage.points.rules.platform') }}">@method('PUT')
@elseif($schoolId)
<form method="POST" action="{{ route('manage.points.rules.school') }}">@method('PUT')
@else<div>@endif
@csrf
<p class="text-muted small">{{ __('points_manage.rules_help') }}</p>
@foreach($groups as $beneficiary => $rules)
<h2 class="h5 mt-3">{{ __('points.beneficiaries.'.$beneficiary) }}</h2>
<div class="table-responsive"><table class="table"><thead><tr>
<th scope="col">{{ __('points_manage.rule') }}</th><th scope="col">{{ __('points_manage.calc_mode') }}</th>
<th scope="col">{{ __('points_manage.rule_points') }}</th><th scope="col">{{ __('points_manage.rule_period_points') }}</th>
@if($canPlatform)<th scope="col">{{ __('points_manage.rule_enabled') }}</th><th scope="col">{{ __('points_manage.rule_allow_override') }}</th>
@else<th scope="col">{{ __('points_manage.platform_value') }}</th><th scope="col">{{ __('points_manage.rule_disabled_school') }}</th>@endif
<th scope="col">{{ __('points_manage.options') }}</th></tr></thead><tbody>
@foreach($rules as $key => $rule)
@php($p = $platform[$key] ?? null)
@php($s = $school[$key] ?? null)
@php($wired = $rule['wired'])
@php($editable = $wired && ($canPlatform || (($p?->allow_school_override ?? false) && ! ($isVice && $beneficiary === 'vice'))))
@php($row = $canPlatform ? $p : $s)
<tr class="{{ $wired ? '' : 'text-muted' }}" style="{{ $wired ? '' : 'opacity:.6' }}">
<td>{{ \App\Modules\Gamification\Support\RuleCatalog::label($key) }}@unless($wired) <span class="badge badge-secondary">{{ __('points.unwired_badge') }}</span>@endunless</td>
<td>{{ __('points.calc_modes.'.$rule['calc_mode']) }}</td>
<td><input type="number" min="0" class="form-control form-control-sm" style="min-width:5rem" name="rules[{{ $key }}][points]" value="{{ $row?->points }}" @disabled(! $editable)></td>
<td><input type="number" min="0" class="form-control form-control-sm" style="min-width:5rem" name="rules[{{ $key }}][period_points]" value="{{ $row?->period_points }}" @disabled(! $editable)></td>
@if($canPlatform)
<td><input type="checkbox" name="rules[{{ $key }}][is_enabled]" value="1" @checked($p?->is_enabled) @disabled(! $editable)></td>
<td><input type="checkbox" name="rules[{{ $key }}][allow_school_override]" value="1" @checked($p?->allow_school_override) @disabled(! $editable)></td>
@else
<td>@if($p){{ (int) $p->points }} / {{ $p->is_enabled ? __('points_manage.rule_enabled') : '—' }}@else — @endif</td>
<td>@if($editable)<input type="checkbox" name="rules[{{ $key }}][disabled]" value="1" @checked($s && $s->is_enabled === false)>@elseif($wired)<small>{{ __('points_manage.not_overridable') }}</small>@endif</td>
@endif
<td>
@foreach($rule['options'] as $name => $schema)
@php($val = $row?->options[$name] ?? ($p?->options[$name] ?? null))
<label class="small d-block mb-1">{{ __('points.options.'.$name) }}
@if($schema['type'] === 'select')
<select class="form-control form-control-sm" name="rules[{{ $key }}][options][{{ $name }}]" @disabled(! $editable)><option value=""></option>
@foreach($schema['values'] as $v)<option value="{{ $v }}" @selected($val === $v)>{{ __('points_manage.options_values.'.$v) }}</option>@endforeach</select>
@else
<input class="form-control form-control-sm" type="{{ $schema['type'] === 'int' ? 'number' : ($schema['type'] === 'date' ? 'date' : 'datetime-local') }}" name="rules[{{ $key }}][options][{{ $name }}]" value="{{ $schema['type'] === 'datetime' && $val ? \Illuminate\Support\Str::of($val)->replace(' ', 'T')->limit(16, '') : $val }}" @disabled(! $editable)>
@endif</label>
@endforeach
</td></tr>
@endforeach
</tbody></table></div>
@endforeach
@if($canPlatform || $schoolId)<button class="btn btn-primary">{{ __('points_manage.save') }}</button></form>@else</div>@endif
</div></div>
@endsection
