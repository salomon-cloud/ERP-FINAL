<?php

declare(strict_types=1);

namespace App\Modules\Compras\Services;

use App\Modules\Compartido\Contracts\Contabilizador;
use App\Modules\Compras\Enums\EstadoFacturaProveedor;
use App\Modules\Compras\Enums\EstadoPago;
use App\Modules\Compras\Models\FacturaProveedor;
use App\Modules\Compras\Models\Pago;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Aplicar y cancelar pagos a proveedores.
 *
 * Aplicar hace tres cosas juntas: sube `total_pagado` de la factura, recalcula
 * su estado (pagada_parcial / pagada) y pide la poliza de banco. Los pagos
 * parciales son normales, y por eso el estado se DERIVA del importe acumulado
 * en vez de elegirse a mano.
 */
class ServicioPago
{
    public function __construct(private readonly Contabilizador $contabilizador) {}

    /**
     * @throws RuntimeException si el pago no es aplicable o excede el saldo
     */
    public function aplicar(Pago $pago): Pago
    {
        if (! $pago->estado->esEditable()) {
            throw new RuntimeException(
                "El pago {$pago->numero_pago} esta {$pago->estado->label()} y ya no se puede aplicar."
            );
        }

        $factura = $pago->factura;

        if ($factura !== null) {
            if (! $factura->estado->admitePago()) {
                throw new RuntimeException(
                    "La factura {$factura->numero_factura} esta {$factura->estado->label()}: ".
                    'solo se paga una factura contabilizada.'
                );
            }

            if ((float) $pago->monto > $factura->saldo + 0.001) {
                throw new RuntimeException(sprintf(
                    'El pago de $%s excede el saldo de la factura %s, que es de $%s.',
                    number_format((float) $pago->monto, 2),
                    $factura->numero_factura,
                    number_format($factura->saldo, 2),
                ));
            }
        }

        return DB::transaction(function () use ($pago, $factura): Pago {
            $pago->estado = EstadoPago::Aplicado;
            $pago->save();

            if ($factura !== null) {
                $factura->increment('total_pagado', (float) $pago->monto);
                $this->recalcularEstadoDeFactura($factura->refresh());
            }

            $this->contabilizador->contabilizar('pago', $pago, [
                'monto' => (float) $pago->monto,
                'proveedor_id' => $pago->proveedor_id,
                'cuenta_bancaria_id' => $pago->cuenta_bancaria_id,
                'factura_proveedor_id' => $pago->factura_proveedor_id,
            ]);

            $pago->registrarBitacora('aplicado', [], [
                'estado' => EstadoPago::Aplicado->value,
                'monto' => (float) $pago->monto,
            ]);

            return $pago->refresh();
        });
    }

    /**
     * Cancela el pago: devuelve su importe al saldo de la factura y pide la
     * poliza de reversa.
     *
     * @throws RuntimeException
     */
    public function cancelar(Pago $pago): Pago
    {
        if ($pago->estado === EstadoPago::Cancelado) {
            throw new RuntimeException("El pago {$pago->numero_pago} ya esta cancelado.");
        }

        return DB::transaction(function () use ($pago): Pago {
            $estabaAplicado = $pago->estado === EstadoPago::Aplicado;
            $factura = $pago->factura;

            $pago->estado = EstadoPago::Cancelado;
            $pago->save();

            if ($estabaAplicado && $factura !== null) {
                $factura->decrement('total_pagado', (float) $pago->monto);
                $this->recalcularEstadoDeFactura($factura->refresh());
            }

            if ($estabaAplicado) {
                $this->contabilizador->reversar('pago', $pago, ['monto' => (float) $pago->monto]);
            }

            $pago->registrarBitacora('cancelado', [], ['estado' => EstadoPago::Cancelado->value]);

            return $pago->refresh();
        });
    }

    /**
     * El estado de la factura sale de comparar lo pagado con el total.
     *
     * Nunca lo elige un usuario: si lo eligiera, podria marcarse "pagada" una
     * factura a la que le falta dinero.
     */
    private function recalcularEstadoDeFactura(FacturaProveedor $factura): void
    {
        // Una factura cancelada no cambia de estado por sus pagos.
        if ($factura->estado === EstadoFacturaProveedor::Cancelada) {
            return;
        }

        $pagado = (float) $factura->total_pagado;
        $total = (float) $factura->total;

        $nuevo = match (true) {
            $pagado + 0.001 >= $total && $total > 0 => EstadoFacturaProveedor::Pagada,
            $pagado > 0 => EstadoFacturaProveedor::PagadaParcial,
            default => EstadoFacturaProveedor::Contabilizada,
        };

        if ($factura->estado !== $nuevo) {
            $anterior = $factura->estado;
            $factura->estado = $nuevo;
            $factura->save();
            $factura->registrarBitacora('pago_actualizado',
                ['estado' => $anterior->value],
                ['estado' => $nuevo->value, 'total_pagado' => $pagado]);
        }
    }
}
