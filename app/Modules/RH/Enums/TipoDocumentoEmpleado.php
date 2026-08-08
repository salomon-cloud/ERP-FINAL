<?php

declare(strict_types=1);

namespace App\Modules\RH\Enums;

use App\Modules\RH\Contracts\Etiquetable;

/**
 * documentos_empleado.tipo_documento, segun chk_documentos_tipo.
 *
 * Clasifica el expediente digital. El archivo en si no vive aqui: esta en la
 * tabla compartida `adjuntos`, a la que apunta documentos_empleado.adjunto_id.
 */
enum TipoDocumentoEmpleado: string implements Etiquetable
{
    case Contrato = 'contrato';
    case Identificacion = 'identificacion';
    case Fiscal = 'fiscal';
    case Salud = 'salud';
    case Academico = 'academico';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Contrato => 'Contrato',
            self::Identificacion => 'Identificacion',
            self::Fiscal => 'Fiscal',
            self::Salud => 'Salud',
            self::Academico => 'Academico',
            self::Otro => 'Otro',
        };
    }
}
