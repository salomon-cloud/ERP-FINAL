<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * El puente entre los documentos de Ventas/Compras y la contabilidad.
 *
 * POR QUE ESTE CONTRATO EXISTE AQUI Y NO EN FINANZAS
 *
 * Emitir una factura, aplicar un cobro o contabilizar una factura de proveedor
 * tienen que generar su poliza. Esa poliza es de Finanzas y la escribiria su
 * `ServicioContabilizarPoliza`, que TODAVIA NO EXISTE: Finanzas esta en
 * esqueleto igual que estaban estos tres modulos (docs/david.md, P3 y §33).
 *
 * Bloquear Ventas y Compras hasta que Finanzas exista no era opcion, y que cada
 * modulo escribiera directo en `polizas` habria roto la propiedad de las tablas.
 * La salida es este contrato: Ventas y Compras dependen de la INTERFAZ, y la
 * implementacion por omision (ContabilizadorPendiente) deja constancia en la
 * bitacora sin tocar la contabilidad.
 *
 * Cuando Finanzas publique su servicio, basta con que su ServiceProvider
 * reemplace el binding en el contenedor. Ni Ventas ni Compras cambian.
 */
interface Contabilizador
{
    /**
     * Contabiliza un documento ya validado por su modulo.
     *
     * @param  string  $origenTipo  factura | cobro | nota_credito | factura_proveedor | pago | devolucion_compra
     * @param  Model  $documento  el documento que origina el asiento
     * @param  array<string, mixed>  $conceptos  importes que la poliza necesita (subtotal, impuesto, total, ...)
     * @return int|null el id de la poliza generada, o null si quedo pendiente
     */
    public function contabilizar(string $origenTipo, Model $documento, array $conceptos): ?int;

    /**
     * Reversa la contabilizacion de un documento que se cancela.
     *
     * @return int|null el id de la poliza de reversa, o null si quedo pendiente
     */
    public function reversar(string $origenTipo, Model $documento, array $conceptos): ?int;
}
