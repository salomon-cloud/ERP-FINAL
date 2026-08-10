<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Services;

use App\Modules\Compartido\Contracts\Contabilizador;
use App\Modules\Compartido\Models\RegistroBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * La implementacion de contingencia de Contabilizador mientras Finanzas no
 * publica su ServicioContabilizarPoliza (docs/david.md §33).
 *
 * NO inventa asientos contables. Lo unico que hace es dejar en la bitacora que
 * el documento quedo listo para contabilizarse, con sus importes, de modo que
 * el dia que exista el servicio real se pueda saber exactamente que quedo
 * pendiente y desde cuando.
 *
 * Devuelve null como id de poliza, y los servicios de Ventas/Compras estan
 * escritos para que eso sea un resultado valido: el documento avanza de estado
 * (emitida, contabilizada) y su `poliza_id` -- donde la tabla lo tiene -- se
 * queda vacio hasta que Finanzas lo llene.
 */
class ContabilizadorPendiente implements Contabilizador
{
    public function contabilizar(string $origenTipo, Model $documento, array $conceptos): ?int
    {
        $this->registrar('contabilizacion_pendiente', $origenTipo, $documento, $conceptos);

        return null;
    }

    public function reversar(string $origenTipo, Model $documento, array $conceptos): ?int
    {
        $this->registrar('reversa_pendiente', $origenTipo, $documento, $conceptos);

        return null;
    }

    /** @param array<string, mixed> $conceptos */
    private function registrar(string $accion, string $origenTipo, Model $documento, array $conceptos): void
    {
        RegistroBitacora::create([
            'user_id' => Auth::id(),
            'modulo' => 'Finanzas',
            'accion' => $accion,
            'entidad_tipo' => $documento::class,
            'entidad_id' => $documento->getKey(),
            'valores_nuevos' => ['origen_tipo' => $origenTipo] + $conceptos,
        ]);
    }
}
