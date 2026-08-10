<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Requests;

use Illuminate\Validation\Validator;

/**
 * Un renglon de cotizacion o de pedido: los dos capturan lo mismo.
 *
 * El precio es OPCIONAL: si no viene, ServicioResolverPrecio lo saca de la
 * lista del cliente. Se puede teclear para cerrar un trato puntual, y ahi entra
 * la regla del descuento maximo.
 */
class GuardarLineaVentaRequest extends RequestBase
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_descuento' => ['nullable', 'numeric', 'between:0,100'],
            'monto_descuento' => ['nullable', 'numeric', 'min:0'],
            'impuesto_id' => ['nullable', 'integer', 'exists:impuestos,id'],
        ];
    }

    public function withValidator(Validator $validador): void
    {
        $validador->after(function (Validator $validador): void {
            $porcentaje = (float) $this->input('porcentaje_descuento', 0);

            // El CHECK de la tabla prohibe los dos a la vez; aqui se avisa antes
            // de que la base lance un error crudo.
            if ($porcentaje > 0 && (float) $this->input('monto_descuento', 0) > 0) {
                $validador->errors()->add('monto_descuento',
                    'Captura el descuento en porcentaje o en monto, no en los dos.');
            }

            // Un descuento por encima del tope de configuracion exige el
            // privilegio de autorizarlo (docs/david.md §21.9).
            $maximo = (float) config('sisen.sales.max_discount', 100);

            if ($porcentaje > $maximo && ! $this->user()?->tieneAlgunPrivilegio(['ventas.descuentos.autorizar'])) {
                $validador->errors()->add('porcentaje_descuento', sprintf(
                    'Un descuento mayor al %s%% necesita autorizacion (privilegio ventas.descuentos.autorizar).',
                    rtrim(rtrim(number_format($maximo, 2, '.', ''), '0'), '.'),
                ));
            }
        });
    }
}
