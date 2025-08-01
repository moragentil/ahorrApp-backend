<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Interface\IngresoServiceInterface;
use App\Services\Implementation\IngresoService;
use App\Services\Interface\GastoServiceInterface;
use App\Services\Implementation\GastoService;
use App\Services\Interface\UserServiceInterface;
use App\Services\Implementation\UserService;
use App\Services\Interface\CategoriaServiceInterface;
use App\Services\Implementation\CategoriaService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IngresoServiceInterface::class, IngresoService::class);
        $this->app->bind(GastoServiceInterface::class, GastoService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(CategoriaServiceInterface::class, CategoriaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
