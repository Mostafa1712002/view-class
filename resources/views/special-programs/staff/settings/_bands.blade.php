<h2 class="h5">{{ __('special_programs/settings.bands_title') }}</h2>
<div class="table-responsive"><table class="table">
<thead><tr><th>{{ __('special_programs/settings.level') }}</th><th>{{ __('special_programs/settings.min') }}</th><th>{{ __('special_programs/settings.max') }}</th></tr></thead>
<tbody>
@foreach($bands as $b)
<tr>
<td><span class="badge badge-{{ $b['color'] }}"><x-svg-icon :name="$b['icon']" :size="14" /> {{ __('special_programs.levels.'.$b['key']) }}</span></td>
<td><input type="number" class="form-control" name="bands[{{ $b['key'] }}][min]" min="0" max="100" required value="{{ old('bands.'.$b['key'].'.min', $b['min']) }}" aria-label="{{ __('special_programs/settings.min') }}" @disabled(! $editable)></td>
<td><input type="number" class="form-control" name="bands[{{ $b['key'] }}][max]" min="0" max="100" required value="{{ old('bands.'.$b['key'].'.max', $b['max']) }}" aria-label="{{ __('special_programs/settings.max') }}" @disabled(! $editable)></td>
</tr>
@endforeach
</tbody></table></div>
