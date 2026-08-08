<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

use App\Modules\RH\Enums\EstadoDocumentoEmpleado;
use App\Modules\RH\Enums\TipoDocumentoEmpleado;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de un documento del expediente.
 *
 * Aqui NO se sube el archivo. Lo que se guarda es el metadato de RH; el binario
 * vive en la tabla compartida `adjuntos` y se sube con el servicio de adjuntos
 * de Compartido, que es quien valida MIME, tamano y disco. Esta peticion solo
 * recibe el `adjunto_id` que ese servicio ya creo.
 *
 * Un documento sin adjunto es valido: es uno que el expediente exige y todavia
 * esta pendiente de entrega.
 */
class GuardarDocumentoEmpleadoRequest extends RequestBase
{
    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'adjunto_id' => ['nullable', 'integer', 'exists:adjuntos,id'],
            'tipo_documento' => ['required', Rule::enum(TipoDocumentoEmpleado::class)],
            'titulo' => ['required', 'string', 'max:200'],
            'vigencia' => ['nullable', 'date'],
            'estado' => ['required', Rule::enum(EstadoDocumentoEmpleado::class)],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'tipo_documento' => 'tipo de documento',
            'vigencia' => 'fecha de vigencia',
        ]);
    }
}
