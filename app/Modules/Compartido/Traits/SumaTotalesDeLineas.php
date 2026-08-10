<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Traits;

/**
 * Recalcula los totales de la cabecera a partir de sus lineas.
 *
 * Los totales de un documento NUNCA se capturan ni se editan: son la suma de
 * sus renglones. Que vivan en columnas propias es por rendimiento -- un listado
 * de mil facturas no puede sumar las lineas de mil documentos -- pero la fuente
 * de verdad siguen siendo las lineas, y este metodo es el unico que las escribe.
 *
 * Lo llama el servicio despues de agregar o quitar una linea. NO es un observer
 * de la linea a proposito: convertir una cotizacion inserta veinte renglones de
 * golpe, y recalcular veinte veces serian diecinueve consultas de mas.
 *
 * El modelo que lo usa necesita una relacion `lineas` y las columnas subtotal,
 * total_impuesto y total. Las tablas que ademas llevan `total_descuento`
 * (cotizaciones, pedidos, facturas, ordenes de compra) lo declaran con
 * $llevaTotalDescuento; las notas de credito y las devoluciones no lo tienen.
 */
trait SumaTotalesDeLineas
{
    public function recalcularTotales(): static
    {
        $lineas = $this->lineas()->get();

        $this->subtotal = round((float) $lineas->sum('subtotal'), 2);
        $this->total_impuesto = round((float) $lineas->sum('monto_impuesto'), 2);
        $this->total = round((float) $lineas->sum('total'), 2);

        if ($this->llevaTotalDescuento()) {
            $this->total_descuento = round((float) $lineas->sum('monto_descuento'), 2);
        }

        $this->save();

        return $this;
    }

    /**
     * Si la tabla de este documento tiene columna `total_descuento`.
     *
     * Por omision si: la mayoria la tiene. Los documentos que no la tienen
     * sobrescriben este metodo devolviendo false.
     */
    protected function llevaTotalDescuento(): bool
    {
        return true;
    }
}
