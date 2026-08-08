<?php

declare(strict_types=1);

use App\Modules\Compartido\Support\EsquemaErp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * facturas_electronicas: el CFDI.
 *
 * El timbrado ante el SAT es integracion futura, pero la tabla ya guarda todo
 * lo que ese intercambio necesita (uuid, xml, pdf, respuesta del PAC,
 * cancelacion) y se enlaza de forma polimorfica al documento que la origina.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas_electronicas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('organizacion_id')->nullable()->constrained('organizaciones')->nullOnDelete();
            $tabla->string('serie', 10)->nullable();
            $tabla->string('folio', 30);
            $tabla->string('tipo_documento', 20);
            $tabla->string('modelo_tipo', 100)->nullable();
            $tabla->unsignedBigInteger('modelo_id')->nullable();
            $tabla->string('uuid', 80)->nullable();
            $tabla->string('ruta_xml', 500)->nullable();
            $tabla->string('ruta_pdf', 500)->nullable();
            $tabla->string('estado', 30)->default('generada');
            $tabla->json('respuesta_timbrado')->nullable();
            $tabla->timestamp('cancelada_en')->nullable();
            $tabla->string('uuid_cancelacion', 80)->nullable();
            EsquemaErp::auditoria($tabla);

            $tabla->unique(['serie', 'folio'], 'uq_facturas_electronicas_folio');
            $tabla->index(['modelo_tipo', 'modelo_id'], 'idx_facturas_electronicas_entidad');
            $tabla->index('estado', 'idx_facturas_electronicas_estado');
        });

        EsquemaErp::check('facturas_electronicas', 'chk_cfdi_tipo', "tipo_documento IN ('ingreso','egreso','traslado','nomina','pago')");
        EsquemaErp::check('facturas_electronicas', 'chk_cfdi_estado', "estado IN ('generada','timbrada','cancelada','rechazada')");
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas_electronicas');
    }
};
