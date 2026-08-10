<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Lo que comparten todos los FormRequest de los modulos ERP.
 *
 * Dos cosas, y ninguna es una regla de negocio:
 *
 *   1. La autorizacion NO se decide aqui. Vive en el middleware `permission:`
 *      de la ruta y, para lo fino, en el Service.
 *   2. Los mensajes y los nombres de campo en espanol. La aplicacion corre con
 *      APP_LOCALE=es pero no hay carpeta lang/ traducida, asi que sin esto el
 *      usuario leeria "validation.required".
 *
 * Cada modulo declara su propio RequestBase que extiende de este y agrega los
 * nombres de sus llaves foraneas con array_merge(parent::attributes(), [...]).
 */
abstract class RequestBase extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'required_if' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un numero entero.',
            'numeric' => 'El campo :attribute debe ser un numero.',
            'boolean' => 'El campo :attribute debe ser si o no.',
            'array' => 'El campo :attribute debe ser una lista.',
            'date' => 'El campo :attribute no es una fecha valida.',
            'email' => 'El campo :attribute debe ser un correo electronico valido.',
            'image' => 'El campo :attribute debe ser una imagen.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'unique' => 'Ese valor de :attribute ya esta registrado.',
            'in' => 'El valor seleccionado en :attribute no es valido.',
            'not_in' => 'El valor seleccionado en :attribute no es valido.',
            'enum' => 'El valor seleccionado en :attribute no es valido.',
            'different' => 'El campo :attribute debe ser distinto de :other.',
            'date_format' => 'El campo :attribute no coincide con el formato :format.',
            'after_or_equal' => 'El campo :attribute debe ser igual o posterior a :date.',
            'before_or_equal' => 'El campo :attribute debe ser igual o anterior a :date.',
            'prohibited' => 'El campo :attribute no se puede capturar aqui.',

            'size' => [
                'string' => 'El campo :attribute debe tener exactamente :size caracteres.',
                'numeric' => 'El campo :attribute debe ser :size.',
                'file' => 'El archivo :attribute debe pesar :size kilobytes.',
                'array' => 'El campo :attribute debe tener :size elementos.',
            ],
            'max' => [
                'string' => 'El campo :attribute no puede tener mas de :max caracteres.',
                'numeric' => 'El campo :attribute no puede ser mayor que :max.',
                'file' => 'El archivo :attribute no puede pesar mas de :max kilobytes.',
                'array' => 'El campo :attribute no puede tener mas de :max elementos.',
            ],
            'min' => [
                'string' => 'El campo :attribute debe tener al menos :min caracteres.',
                'numeric' => 'El campo :attribute no puede ser menor que :min.',
                'file' => 'El archivo :attribute debe pesar al menos :min kilobytes.',
                'array' => 'El campo :attribute debe tener al menos :min elementos.',
            ],
            'between' => [
                'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
                'numeric' => 'El campo :attribute debe estar entre :min y :max.',
                'file' => 'El archivo :attribute debe pesar entre :min y :max kilobytes.',
                'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
            ],
            'gt' => [
                'string' => 'El campo :attribute debe tener mas de :value caracteres.',
                'numeric' => 'El campo :attribute debe ser mayor que :value.',
                'file' => 'El archivo :attribute debe pesar mas de :value kilobytes.',
                'array' => 'El campo :attribute debe tener mas de :value elementos.',
            ],
            'gte' => [
                'string' => 'El campo :attribute debe tener :value caracteres o mas.',
                'numeric' => 'El campo :attribute debe ser mayor o igual que :value.',
                'file' => 'El archivo :attribute debe pesar :value kilobytes o mas.',
                'array' => 'El campo :attribute debe tener :value elementos o mas.',
            ],
        ];
    }

    /**
     * Nombres legibles de lo que se repite en los tres modulos. Cada peticion
     * agrega los suyos con array_merge.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organizacion_id' => 'organizacion',
            'producto_id' => 'producto',
            'almacen_id' => 'almacen',
            'ubicacion_id' => 'ubicacion',
            'categoria_id' => 'categoria',
            'unidad_id' => 'unidad de medida',
            'impuesto_id' => 'impuesto',
            'lote_id' => 'lote',
            'numero_serie_id' => 'numero de serie',
            'cliente_id' => 'cliente',
            'proveedor_id' => 'proveedor',
            'condicion_pago_id' => 'condicion de pago',
            'lista_precio_id' => 'lista de precios',
            'cuenta_bancaria_id' => 'cuenta bancaria',
            'periodo_fiscal_id' => 'periodo fiscal',
            'departamento_id' => 'departamento',
            'solicitante_id' => 'solicitante',
            'padre_id' => 'categoria padre',
            'precio_unitario' => 'precio unitario',
            'costo_unitario' => 'costo unitario',
            'porcentaje_descuento' => 'descuento (%)',
            'monto_descuento' => 'descuento',
            'tasa_impuesto' => 'tasa de impuesto',
            'fecha_entrega' => 'fecha de entrega',
            'fecha_emision' => 'fecha de emision',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'fecha_requerida' => 'fecha requerida',
            'fecha_caducidad' => 'fecha de caducidad',
            'forma_pago' => 'forma de pago',
            'stock_minimo' => 'stock minimo',
            'stock_maximo' => 'stock maximo',
            'precio_venta' => 'precio de venta',
            'lineas' => 'lineas del documento',
        ];
    }

    /**
     * El id del registro que se esta editando, o null si es un alta.
     *
     * Lo necesitan las reglas `unique`, que en una edicion tienen que ignorar
     * la fila del propio registro.
     */
    protected function idEnRuta(string $parametro): ?int
    {
        $valor = $this->route($parametro);

        if ($valor instanceof Model) {
            return (int) $valor->getKey();
        }

        return is_numeric($valor) ? (int) $valor : null;
    }
}
