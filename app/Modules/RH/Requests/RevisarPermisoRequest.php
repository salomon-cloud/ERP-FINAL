<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoPermiso;

/**
 * La decision sobre una solicitud: aprobarla o rechazarla.
 *
 * Es una mutacion distinta del alta y por eso tiene su propia peticion. Solo
 * admite los dos estados finales: `pendiente` no es una decision, y volver una
 * solicitud ya resuelta a pendiente borraria el rastro de quien la reviso.
 *
 * Que la solicitud siga pendiente al momento de decidir lo verifica
 * ServicioAprobacionPermisos, no esta clase: es una regla de negocio sobre el
 * estado actual del registro, no sobre la forma de lo que llega.
 */
class RevisarPermisoRequest extends RequestBase
{
    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:'.EstadoPermiso::Aprobado->value.','.EstadoPermiso::Rechazado->value],
            'comentario_revision' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'estado.in' => 'La decision solo puede ser aprobar o rechazar.',
        ]);
    }
}
