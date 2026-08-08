<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guardia fino de rutas para el modelo de privilegios:
 *
 *     ->middleware('permission:finanzas.polizas.contabilizar')
 *     ->middleware('permission:ventas.pedidos.cancelar,ventas.pedidos.ver')  // cualquiera
 *
 * El middleware `role` sigue sirviendo a las rutas de SISEN v1; los modulos
 * nuevos usan este. Ver docs/PLANNING.md - "Roles & Permissions".
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$privilegios): Response
    {
        $usuario = $request->user();

        if (! $usuario || $usuario->estado !== 'activo' || ! $usuario->tieneAlgunPrivilegio($privilegios)) {
            abort(403, 'No tienes permiso para acceder a esta seccion.');
        }

        return $next($request);
    }
}
