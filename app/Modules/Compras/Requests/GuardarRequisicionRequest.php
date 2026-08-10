<?php

declare(strict_types=1);

namespace App\Modules\Compras\Requests;

/** Cabecera de una requisicion: quien pide, para cuando y para que area. */
class GuardarRequisicionRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
            'fecha_requerida' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
