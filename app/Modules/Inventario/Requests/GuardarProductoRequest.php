<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Requests;

use App\Modules\Compartido\Enums\EstadoActivacion;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un producto.
 *
 * El SKU es unico ENTRE LOS ACTIVOS, igual que el indice de la base: al ignorar
 * los borrados logicos, un codigo se puede reutilizar despues de dar de baja al
 * producto que lo tenia, y la regla de validacion tiene que decir lo mismo que
 * el indice o el usuario recibiria un error 500 en vez de un mensaje.
 */
class GuardarProductoRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->idEnRuta('producto');

        return [
            'sku' => [
                'required', 'string', 'max:50',
                Rule::unique('productos', 'sku')->ignore($id)->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias_producto,id'],
            'unidad_id' => ['nullable', 'integer', 'exists:unidades_medida,id'],
            'impuesto_id' => ['nullable', 'integer', 'exists:impuestos,id'],
            'costo' => ['required', 'numeric', 'min:0', 'max:9999999999999999'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'max:9999999999999999'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0', 'gte:stock_minimo'],
            'es_vendible' => ['boolean'],
            'es_comprable' => ['boolean'],
            'es_inventariable' => ['boolean'],
            'rastrea_serie' => ['boolean'],
            'estado' => ['required', Rule::enum(EstadoActivacion::class)],
        ];
    }

    /**
     * Las casillas no marcadas no llegan en la peticion, y sin esto una
     * edicion que desmarca "se puede vender" dejaria el valor anterior.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'es_vendible' => $this->boolean('es_vendible'),
            'es_comprable' => $this->boolean('es_comprable'),
            'es_inventariable' => $this->boolean('es_inventariable'),
            'rastrea_serie' => $this->boolean('rastrea_serie'),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'stock_maximo.gte' => 'El stock maximo no puede ser menor que el minimo.',
        ]);
    }
}
