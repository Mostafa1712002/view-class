<?php

namespace App\Providers;

use App\Modules\Auth\Repositories\Contracts\SessionRepository;
use App\Modules\Auth\Repositories\Contracts\UserRepository;
use App\Modules\Auth\Repositories\EloquentSessionRepository;
use App\Modules\Auth\Repositories\EloquentUserRepository;
use App\Modules\Auth\Services\JwtService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        SessionRepository::class => EloquentSessionRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(JwtService::class, fn () => JwtService::create());
    }
}
