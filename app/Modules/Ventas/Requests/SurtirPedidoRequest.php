<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

/**
 * Surtir un pedido, total o parcialmente.
 *
 * Sin `cantidades` se surte todo lo pendiente, que es el caso normal. Con ellas
 * se surte solo lo que se indique, para las entregas por partes.
 */
class SurtirPedidoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cantidades' => ['nullable', 'array'],
            'cantidades.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Las cantidades por linea, o null para surtir todo lo pendiente.
     *
     * @return array<int, float>|null
     */
    public function cantidades(): ?array
    {
        $capturadas = $this->validated()['cantidades'] ?? null;

        if (! is_array($capturadas) || $capturadas === []) {
            return null;
        }

        $cantidades = [];

        foreach ($capturadas as $lineaId => $cantidad) {
            $cantidades[(int) $lineaId] = (float) ($cantidad ?: 0);
        }

        return $cantidades;
    }
}
