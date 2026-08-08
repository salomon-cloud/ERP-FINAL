<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Llena creado_por y actualizado_por con el usuario autenticado.
 *
 * Toda tabla de negocio lleva ese par (PLANNING - "Database Standards",
 * conjunto de columnas universal). Los controladores nunca los asignan a mano.
 */
trait TieneCamposAuditoria
{
    public static function bootTieneCamposAuditoria(): void
    {
        static::creating(function ($modelo): void {
            $usuarioId = Auth::id();

            if ($usuarioId === null) {
                return;
            }

            if (empty($modelo->creado_por)) {
                $modelo->creado_por = $usuarioId;
            }

            if (empty($modelo->actualizado_por)) {
                $modelo->actualizado_por = $usuarioId;
            }
        });

        static::updating(function ($modelo): void {
            if (Auth::id() !== null) {
                $modelo->actualizado_por = Auth::id();
            }
        });
    }
}
