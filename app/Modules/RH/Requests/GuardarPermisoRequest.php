<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\TipoPermiso;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de una solicitud de permiso, vacaciones o incapacidad.
 *
 * `estado` no esta aqui a proposito. Una solicitud nace pendiente (valor por
 * omision de la columna) y solo cambia de estado por RevisarPermisoRequest a
 * traves de ServicioAprobacionPermisos, que ademas deja el rastro de quien
 * reviso y cuando. Si esta pantalla pudiera mandar estado=aprobado, cualquiera
 * se aprobaria sus propias vacaciones sin pasar por el flujo.
 *
 * `dias` es opcional: si no viene, ObservadorPermiso lo calcula del rango.
 */
class GuardarPermisoRequest extends RequestBase
{
    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'tipo' => ['required', Rule::enum(TipoPermiso::class)],
            'fecha_inicio' => ['required', 'date'],

            // chk_permisos_fechas
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],

            'dias' => ['nullable', 'numeric', 'min:0'],
            'con_goce' => ['boolean'],
            'motivo' => ['required', 'string'],
        ];
    }
}
