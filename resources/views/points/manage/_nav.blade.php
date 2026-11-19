@php($tabs = [
    'balances' => ['manage.points.index', 'manage.points.index', 'manage.points.show'],
    'rules' => ['manage.points.rules', 'manage.points.rules'],
    'periods' => ['manage.points.periods.index', 'manage.points.periods.index'],
    'rewards' => ['manage.points.rewards.index', 'manage.points.rewards.index'],
    'honour' => ['manage.points.honour', 'manage.points.honour'],
])
@if($errors->any())<div class="alert alert-warning" role="alert">@foreach($errors->all() as $error)<p class="mb-1">{{ $error }}</p>@endforeach</div>@endif
@if(session('warning'))<div class="alert alert-warning" role="alert">{{ session('warning') }}</div>@endif
<ul class="nav nav-pills mb-3">
@foreach($tabs as $key => $routes)
@continue(in_array($key, ['periods', 'rewards']) && ! Gate::allows('manageRewards', \App\Modules\Gamification\Models\PointTransaction::class))
@continue($key === 'rules' && ! Gate::allows('manageRules', \App\Modules\Gamification\Models\PointTransaction::class))
<li class="nav-item"><a class="nav-link {{ request()->routeIs(...array_slice($routes, 1)) ? 'active' : '' }}" href="{{ route($routes[0]) }}">{{ __('points_manage.tabs.'.$key) }}</a></li>
@endforeach
@if(Route::has('my.points.index'))<li class="nav-item me-auto"><a class="nav-link" href="{{ route('my.points.index') }}"><x-svg-icon name="star" :size="14" /> {{ __('points_manage.my_points') }}</a></li>@endif
</ul>
