<?php

namespace App\Providers;

use App\Modules\Auth\Repositories\Contracts\SessionRepository;
use App\Modules\Auth\Repositories\Contracts\UserRepository;
use App\Modules\Auth\Repositories\EloquentSessionRepository;
use App\Modules\Auth\Repositories\EloquentUserRepository;
use App\Modules\Auth\Services\JwtService;
use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;
use App\Modules\Dashboard\Repositories\EloquentDashboardStatsRepository;
use App\Modules\Profile\Repositories\Contracts\ProfileRepository;
use App\Modules\Profile\Repositories\EloquentProfileRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        SessionRepository::class => EloquentSessionRepository::class,
        DashboardStatsRepository::class => EloquentDashboardStatsRepository::class,
        ProfileRepository::class => EloquentProfileRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(JwtService::class, fn () => JwtService::create());
    }
}
