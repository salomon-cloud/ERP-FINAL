<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Lo que comparten todos los FormRequest del modulo.
 *
 * Dos cosas, y ninguna es una regla de negocio:
 *
 *   1. La autorizacion NO se decide aqui. Vive en el middleware `permission:`
 *      de la ruta y, para lo fino, en el Service. Este metodo solo evita que
 *      cada peticion repita el mismo `return true`.
 *   2. Los nombres de campo en espanol para los mensajes de error. La
 *      aplicacion corre con APP_LOCALE=es pero no hay carpeta lang/ traducida,
 *      asi que sin esto el usuario leeria "The departamento_id field is
 *      required". Traducir los mensajes completos exigiria un lang/es en la
 *      raiz del proyecto, que esta fuera de este modulo.
 */
abstract class RequestBase extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * La bolsa de mensajes en espanol del modulo.
     *
     * No hay carpeta lang/ en la raiz y el framework solo trae `en`, asi que sin
     * esto el usuario leeria la llave cruda ("validation.required"). Traducir
     * desde aqui mantiene el arreglo dentro del modulo y no obliga a publicar
     * lang/es, que es una decision de quien lleva el cascaron.
     *
     * Las reglas de tamano (max, min, size, between, gt, gte) llevan un arreglo
     * por tipo de dato: Laravel elige la variante segun lo que se este validando,
     * de modo que "max" diga caracteres en un texto y kilobytes en un archivo.
     *
     * Una peticion que necesite un mensaje propio hace
     * array_merge(parent::messages(), [...]) y solo escribe el suyo.
     *
     * @return array<string, string|array<string, string>>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
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
            'date_format' => 'El campo :attribute no coincide con el formato :format.',
            'after_or_equal' => 'El campo :attribute debe ser igual o posterior a :date.',
            'before_or_equal' => 'El campo :attribute debe ser igual o anterior a :date.',

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
     * Nombres legibles de las llaves foraneas y campos que se repiten en todo
     * el modulo. Cada peticion agrega los suyos con array_merge.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'organizacion_id' => 'organizacion',
            'departamento_id' => 'departamento',
            'puesto_id' => 'puesto',
            'empleado_id' => 'empleado',
            'jefe_id' => 'jefe',
            'padre_id' => 'departamento padre',
            'user_id' => 'usuario',
            'periodo_id' => 'periodo',
            'corrida_id' => 'corrida',
            'evaluador_id' => 'evaluador',
            'adjunto_id' => 'adjunto',
            'verificado_por' => 'verificado por',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'fecha_pago' => 'fecha de pago',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'fecha_contratacion' => 'fecha de contratacion',
            'fecha_baja' => 'fecha de baja',
            'sueldo_base' => 'sueldo base',
            'tipo_contrato' => 'tipo de contrato',
            'frecuencia_pago' => 'frecuencia de pago',
            'numero_empleado' => 'numero de empleado',
            'cuenta_bancaria' => 'cuenta bancaria',
            'hora_entrada' => 'hora de entrada',
            'hora_salida' => 'hora de salida',
            'comentario_revision' => 'comentario de revision',
        ];
    }

    /**
     * El id del registro que se esta editando, o null si es un alta.
     *
     * Lo necesitan las reglas `unique`, que en una edicion tienen que ignorar
     * la fila del propio registro. Sirve tanto si la ruta trae el modelo ya
     * resuelto como si trae el id pelado.
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
