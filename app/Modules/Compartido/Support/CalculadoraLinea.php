<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Support;

/**
 * El calculo de una linea de documento, en UN solo lugar.
 *
 * Cotizaciones, pedidos, facturas, notas de credito, ordenes de compra,
 * facturas de proveedor y devoluciones calculan todas exactamente igual. Tener
 * la formula repetida en siete servicios seria tener siete formas de que un
 * total se descuadre.
 *
 * LA FORMULA
 *
 *   base      = cantidad x precio unitario
 *   descuento = monto fijo, o el porcentaje sobre la base
 *   subtotal  = base - descuento
 *   impuesto  = subtotal x tasa
 *   total     = subtotal + impuesto
 *
 * El descuento se expresa en monto O en porcentaje, nunca en los dos a la vez:
 * lo impide el CHECK chk_*_linea_descuento de cada tabla de lineas.
 *
 * LA TASA es una FRACCION, no un porcentaje: el 16% de IVA viaja como 0.16,
 * igual que en `impuestos.tasa`. Guardarla como 16 haria que el impuesto saliera
 * cien veces mas grande.
 *
 * Todo se redondea a dos decimales porque las columnas de dinero son
 * DECIMAL(18,2): si no se redondeara aqui, MariaDB lo haria al guardar y la
 * suma de las lineas no cuadraria con el total de la cabecera.
 */
final class CalculadoraLinea
{
    /**
     * @return array{subtotal: float, monto_descuento: float, monto_impuesto: float, total: float}
     */
    public static function calcular(
        float $cantidad,
        float $precioUnitario,
        float $porcentajeDescuento = 0,
        float $montoDescuento = 0,
        float $tasaImpuesto = 0,
    ): array {
        $base = round($cantidad * $precioUnitario, 2);

        $descuento = $montoDescuento > 0
            ? round($montoDescuento, 2)
            : round($base * $porcentajeDescuento / 100, 2);

        // Un descuento nunca deja la linea en negativo: el CHECK de la tabla
        // exige subtotal >= 0 y un total negativo no significa nada aqui -- eso
        // es una nota de credito, que es otro documento.
        $descuento = min($descuento, $base);

        $subtotal = round($base - $descuento, 2);
        $impuesto = round($subtotal * $tasaImpuesto, 2);

        return [
            'subtotal' => $subtotal,
            'monto_descuento' => $descuento,
            'monto_impuesto' => $impuesto,
            'total' => round($subtotal + $impuesto, 2),
        ];
    }
}
