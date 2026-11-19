@extends('layouts.app')
@section('title', __('special_programs/staff.hub_title'))
@section('page-title', __('special_programs/staff.hub_title'))
@section('content')
<p class="text-muted">{{ __('special_programs/staff.hub_intro') }}</p>
@unless($hasSchool)<div class="alert alert-warning" role="alert">{{ __('special_programs/staff.pick_school') }}</div>@endunless
<div class="row">
@foreach($cards as $program => $card)
    <div class="col-md-4 mb-3">
        <div class="ds-card h-100"><div class="card-body">
            <h5 class="ds-card-title"><x-svg-icon name="mortarboard" :size="18" /> {{ __('special_programs.programs.'.$program) }}</h5>
            @if($card['bank'] === 0)
                <div class="alert alert-warning mb-2" role="alert">{{ __('special_programs/staff.empty_bank') }}</div>
            @else
                <p class="mb-1">{{ __('special_programs/staff.bank_questions') }}: <strong>{{ $card['bank'] }}</strong></p>
            @endif
            @if($card['enrolled'] !== null)
                <p class="mb-2">{{ __('special_programs/staff.enrolled_students') }}: <strong>{{ $card['enrolled'] }}</strong></p>
            @endif
            <a class="btn btn-primary btn-sm" href="{{ route('manage.special-programs.program', $program) }}">{{ __('special_programs/staff.open_program') }}</a>
        </div></div>
    </div>
@endforeach
</div>
@endsection
