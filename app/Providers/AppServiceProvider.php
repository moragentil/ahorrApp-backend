<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Interfaces
use App\Services\Interface\GastoServiceInterface;
use App\Services\Interface\CategoriaServiceInterface;
use App\Services\Interface\IngresoServiceInterface;
use App\Services\Interface\AhorroServiceInterface;
use App\Services\Interface\GrupoGastoServiceInterface;
use App\Services\Interface\GastoCompartidoServiceInterface;
use App\Services\Interface\AporteGastoServiceInterface;
use App\Services\Interface\InvitacionGrupoServiceInterface;

// Implementaciones
use App\Services\Implementation\GastoService;
use App\Services\Implementation\CategoriaService;
use App\Services\Implementation\IngresoService;
use App\Services\Implementation\AhorroService;
use App\Services\Implementation\GrupoGastoService;
use App\Services\Implementation\GastoCompartidoService;
use App\Services\Implementation\AporteGastoService;
use App\Services\Implementation\InvitacionGrupoService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Servicios existentes
        $this->app->bind(GastoServiceInterface::class, GastoService::class);
        $this->app->bind(CategoriaServiceInterface::class, CategoriaService::class);
        $this->app->bind(IngresoServiceInterface::class, IngresoService::class);
        $this->app->bind(AhorroServiceInterface::class, AhorroService::class);

        // Nuevos servicios de gastos compartidos
        $this->app->bind(GrupoGastoServiceInterface::class, GrupoGastoService::class);
        $this->app->bind(GastoCompartidoServiceInterface::class, GastoCompartidoService::class);
        $this->app->bind(AporteGastoServiceInterface::class, AporteGastoService::class);

        // Servicio de invitaciones a grupos
        $this->app->bind(InvitacionGrupoServiceInterface::class, InvitacionGrupoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
