@extends('layouts.app')
@section('title', __('schools.choose_school'))
@section('content')
<div class="card">
    <div class="card-body">
        <h1 class="h4">@lang('schools.choose_school')</h1>
        <ul class="list-group">
            @foreach($schools as $school)
                <li class="list-group-item"><a href="{{ route($targetRoute, $school) }}">{{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar ?: $school->name) : ($school->name_ar ?: $school->name) }}</a></li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
