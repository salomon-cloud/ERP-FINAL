<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoActivacion;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarPuestoRequest extends RequestBase
{
    public function rules(): array
    {
        $id = $this->idEnRuta('puesto');

        return [
            'departamento_id' => ['required', 'integer', 'exists:departamentos,id'],
            'codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('puestos', 'codigo')->whereNull('deleted_at')->ignore($id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'sueldo_minimo' => ['required', 'numeric', 'min:0'],
            'sueldo_maximo' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', Rule::enum(EstadoActivacion::class)],
        ];
    }

    /**
     * El rango de sueldo no se puede expresar con una regla suelta porque el 0
     * es un caso especial: significa "sin tope", no "cero pesos". Es la misma
     * regla que chk_puestos_rango_sueldo aplica en la base; aqui esta para que
     * el usuario reciba un mensaje y no un error de SQL.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $minimo = (float) $this->input('sueldo_minimo', 0);
                $maximo = (float) $this->input('sueldo_maximo', 0);

                if ($maximo > 0 && $maximo < $minimo) {
                    $validador->errors()->add(
                        'sueldo_maximo',
                        'El sueldo maximo debe ser mayor o igual al minimo, o 0 si el puesto no tiene tope.'
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'sueldo_minimo' => 'sueldo minimo',
            'sueldo_maximo' => 'sueldo maximo',
        ]);
    }
}
