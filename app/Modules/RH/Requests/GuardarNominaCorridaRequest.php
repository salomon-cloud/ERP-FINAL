<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

/**
 * Alta de una corrida de nomina.
 *
 * Es la peticion mas corta del modulo, y eso es intencional: de una corrida el
 * usuario solo elige QUE periodo procesar. Todo lo demas lo pone el sistema --
 * el folio lo asigna ObservadorNominaCorrida, los totales los recalcula
 * ObservadorNomina, y el estado lo mueve ServicioCorridaNomina.
 *
 * Que el periodo este abierto lo verifica el Service (EstadoNominaPeriodo::
 * admiteCorridas), porque es una regla sobre el estado actual del periodo y no
 * sobre la forma de lo que llega.
 */
class GuardarNominaCorridaRequest extends RequestBase
{
    public function rules(): array
    {
        return [
            'organizacion_id' => ['nullable', 'integer', 'exists:organizaciones,id'],
            'periodo_id' => ['required', 'integer', 'exists:nomina_periodos,id'],
        ];
    }
}
