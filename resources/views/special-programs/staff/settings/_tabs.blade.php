{{-- Global page: program switcher. School page passes tabs=false and uses partials/program-tabs instead. --}}
@if($tabs ?? true)
<ul class="nav nav-tabs mb-3">
@foreach(\App\Modules\SpecialPrograms\Support\Programs::keys() as $p)
<li class="nav-item"><a class="nav-link {{ $p === $program ? 'active' : '' }}" href="{{ $global ? route('manage.special-programs.settings.global', ['program' => $p]) : route('manage.special-programs.settings.index', $p) }}">{{ __('special_programs.programs.'.$p) }}</a></li>
@endforeach
</ul>
@endif
@if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-warning" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>@endif
