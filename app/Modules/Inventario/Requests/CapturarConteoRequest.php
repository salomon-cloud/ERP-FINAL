<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

/**
 * La captura del conteo: un arreglo linea_id => cantidad contada.
 *
 * Se acepta que una linea venga vacia: significa "todavia no la cuento", y es
 * distinto de contar cero. Por eso `nullable` y no `required`, y por eso el
 * servicio solo cambia el estado cuando hay al menos una capturada.
 */
class CapturarConteoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cantidades' => ['required', 'array'],
            'cantidades.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Las cantidades ya convertidas, con las llaves como enteros.
     *
     * @return array<int, float|null>
     */
    public function cantidades(): array
    {
        $cantidades = [];

        foreach ((array) $this->validated()['cantidades'] as $lineaId => $cantidad) {
            $cantidades[(int) $lineaId] = ($cantidad === null || $cantidad === '') ? null : (float) $cantidad;
        }

        return $cantidades;
    }
}
