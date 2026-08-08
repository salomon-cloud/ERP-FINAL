<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Enums\FrecuenciaPago;
use App\Modules\RH\Enums\GeneroEmpleado;
use App\Modules\RH\Enums\TipoContrato;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion del expediente de un empleado.
 *
 * Conserva las reglas de SISEN v1 (CURP de 18, RFC de 12 o 13, correo unico) y
 * les suma las columnas que agrego el ERP.
 *
 * Cuidado con los `unique`, porque NO todos se comportan igual:
 *
 *   - `numero_empleado` usa un indice sobre columna generada, asi que ignora
 *     las filas con borrado logico y su valor se puede reutilizar.
 *   - `curp`, `rfc` y `correo` conservan el indice unico de v1, que SI abarca
 *     las filas borradas. Poner aqui un whereNull('deleted_at') haria pasar la
 *     validacion y luego reventar el INSERT contra la base.
 */
class GuardarEmpleadoRequest extends RequestBase
{
    public function rules(): array
    {
        $id = $this->idEnRuta('empleado');

        return [
            'organizacion_id' => ['nullable', 'integer', 'exists:organizaciones,id'],
            'departamento_id' => ['required', 'integer', 'exists:departamentos,id'],
            'puesto_id' => ['required', 'integer', 'exists:puestos,id'],

            // Nadie es su propio jefe.
            'jefe_id' => ['nullable', 'integer', 'exists:empleados,id', Rule::notIn(array_filter([$id]))],

            'user_id' => ['nullable', 'integer', 'exists:users,id'],

            'numero_empleado' => [
                'nullable', 'string', 'max:30',
                Rule::unique('empleados', 'numero_empleado')->whereNull('deleted_at')->ignore($id),
            ],

            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'genero' => ['nullable', Rule::enum(GeneroEmpleado::class)],

            'curp' => ['required', 'string', 'size:18', Rule::unique('empleados', 'curp')->ignore($id)],
            'rfc' => ['required', 'string', 'min:12', 'max:13', Rule::unique('empleados', 'rfc')->ignore($id)],
            'correo' => ['required', 'email', 'max:255', Rule::unique('empleados', 'correo')->ignore($id)],

            'nss' => ['nullable', 'string', 'max:30'],
            'banco' => ['nullable', 'string', 'max:150'],
            'cuenta_bancaria' => ['nullable', 'string', 'max:60'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string'],

            'fecha_nacimiento' => ['required', 'date'],
            'fecha_contratacion' => ['required', 'date'],

            // chk_empleados_fecha_baja: nadie causa baja antes de ser contratado.
            'fecha_baja' => ['nullable', 'date', 'after_or_equal:fecha_contratacion'],
            'motivo_baja' => ['nullable', 'string', 'max:300'],

            'tipo_contrato' => ['required', Rule::enum(TipoContrato::class)],
            'sueldo_base' => ['required', 'numeric', 'min:0'],
            'moneda' => ['required', 'string', 'size:3'],
            'frecuencia_pago' => ['required', Rule::enum(FrecuenciaPago::class)],
            'estado' => ['required', Rule::enum(EstadoActivacion::class)],
            'fotografia' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'jefe_id.not_in' => 'Un empleado no puede ser su propio jefe.',
        ]);
    }
}
