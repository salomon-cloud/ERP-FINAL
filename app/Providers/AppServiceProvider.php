<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registrarGateDePrivilegios();
    }

    /**
     * Hace que cualquier codigo de privilegio sirva como habilidad del Gate,
     * para que servicios y vistas puedan preguntar
     * $user->can('finanzas.polizas.contabilizar') o @can(...) sin escribir una
     * Policy por cada capacidad.
     *
     * Devolver null (y no false) cuando el codigo es desconocido deja que las
     * Policies y los Gates definidos a mano conserven su voz.
     */
    private function registrarGateDePrivilegios(): void
    {
        Gate::before(function (User $usuario, string $habilidad): ?bool {
            if (! str_contains($habilidad, '.')) {
                return null;
            }

            return $usuario->tieneAlgunPrivilegio([$habilidad]) ? true : null;
        });
    }
}
