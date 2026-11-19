@if($alerts)
    @if($alerts['unnumbered']->isNotEmpty())
        <div class="alert alert-warning" role="alert">
            {{ __('special_programs/staff.unnumbered_alert', ['classes' => $alerts['unnumbered']->map(fn ($c) => $c->name.($c->division ? ' - '.$c->division : ''))->implode('، ')]) }}
            @if(Route::has('classes.index'))<a href="{{ route('classes.index') }}">{{ __('special_programs/staff.edit_classes') }}</a>@endif
        </div>
    @endif
    @if($alerts['none_eligible'])
        <div class="alert alert-warning" role="alert">{{ __('special_programs/staff.none_eligible_alert') }}</div>
    @endif
@endif
