<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Services;

use App\Modules\Compartido\Contracts\Contabilizador;
use App\Modules\Ventas\Enums\EstadoCobro;
use App\Modules\Ventas\Enums\EstadoFactura;
use App\Modules\Ventas\Models\Cobro;
use App\Modules\Ventas\Models\Factura;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Aplicar y cancelar cobros.
 *
 * Aplicar hace tres cosas juntas: sube `total_cobrado` de la factura, recalcula
 * su estado (cobrada_parcial / cobrada) y pide la poliza de caja o banco. Los
 * cobros parciales son lo normal en credito, y por eso el estado se DERIVA del
 * importe acumulado en vez de elegirse a mano: nadie puede marcar como cobrada
 * una factura a la que le falta dinero.
 */
class ServicioCobro
{
    public function __construct(private readonly Contabilizador $contabilizador) {}

    /** @throws RuntimeException si el cobro no es aplicable o excede el saldo */
    public function aplicar(Cobro $cobro): Cobro
    {
        if (! $cobro->estado->esEditable()) {
            throw new RuntimeException(
                "El cobro {$cobro->numero_cobro} esta {$cobro->estado->label()} y ya no se puede aplicar."
            );
        }

        $factura = $cobro->factura;

        if ($factura !== null) {
            if (! $factura->estado->admiteCobro()) {
                throw new RuntimeException(
                    "La factura {$factura->numero_factura} esta {$factura->estado->label()}: ".
                    'solo se cobra una factura emitida.'
                );
            }

            if ((float) $cobro->monto > $factura->saldo + 0.001) {
                throw new RuntimeException(sprintf(
                    'El cobro de $%s excede el saldo de la factura %s, que es de $%s. '.
                    'Registralo como cobro a cuenta si el cliente adelanto dinero.',
                    number_format((float) $cobro->monto, 2),
                    $factura->numero_factura,
                    number_format($factura->saldo, 2),
                ));
            }
        }

        return DB::transaction(function () use ($cobro, $factura): Cobro {
            $cobro->estado = EstadoCobro::Aplicado;
            $cobro->save();

            if ($factura !== null) {
                $factura->increment('total_cobrado', (float) $cobro->monto);
                $this->recalcularEstadoDeFactura($factura->refresh());
            }

            $this->contabilizador->contabilizar('cobro', $cobro, [
                'monto' => (float) $cobro->monto,
                'cliente_id' => $cobro->cliente_id,
                'cuenta_bancaria_id' => $cobro->cuenta_bancaria_id,
                'factura_id' => $cobro->factura_id,
                'forma_pago' => $cobro->forma_pago->value,
            ]);

            $cobro->registrarBitacora('aplicado', [], [
                'estado' => EstadoCobro::Aplicado->value,
                'monto' => (float) $cobro->monto,
            ]);

            return $cobro->refresh();
        });
    }

    /**
     * Cancela el cobro: devuelve su importe al saldo de la factura y pide la
     * poliza de reversa.
     *
     * @throws RuntimeException
     */
    public function cancelar(Cobro $cobro): Cobro
    {
        if ($cobro->estado === EstadoCobro::Cancelado) {
            throw new RuntimeException("El cobro {$cobro->numero_cobro} ya esta cancelado.");
        }

        return DB::transaction(function () use ($cobro): Cobro {
            $estabaAplicado = $cobro->estado === EstadoCobro::Aplicado;
            $factura = $cobro->factura;

            $cobro->estado = EstadoCobro::Cancelado;
            $cobro->save();

            if ($estabaAplicado && $factura !== null) {
                $factura->decrement('total_cobrado', (float) $cobro->monto);
                $this->recalcularEstadoDeFactura($factura->refresh());
            }

            if ($estabaAplicado) {
                $this->contabilizador->reversar('cobro', $cobro, ['monto' => (float) $cobro->monto]);
            }

            $cobro->registrarBitacora('cancelado', [], ['estado' => EstadoCobro::Cancelado->value]);

            return $cobro->refresh();
        });
    }

    /**
     * Aplica un cobro a cuenta a una factura concreta.
     *
     * Es el caso del cliente que adelanta dinero y despues decide contra que lo
     * quiere aplicar. El cobro tiene que estar en borrador todavia: uno ya
     * aplicado sin factura movio el banco y reasignarlo seria cancelarlo y
     * volverlo a capturar.
     *
     * @throws RuntimeException
     */
    public function asignarFactura(Cobro $cobro, Factura $factura): Cobro
    {
        if (! $cobro->estado->esEditable()) {
            throw new RuntimeException(
                "El cobro {$cobro->numero_cobro} ya esta {$cobro->estado->label()}: cancelalo y capturalo de nuevo."
            );
        }

        if ((int) $factura->cliente_id !== (int) $cobro->cliente_id) {
            throw new RuntimeException('Esa factura es de otro cliente.');
        }

        $cobro->factura_id = $factura->id;
        $cobro->save();

        return $cobro->refresh();
    }

    /** El estado de la factura sale de comparar lo cobrado con el total. */
    private function recalcularEstadoDeFactura(Factura $factura): void
    {
        if ($factura->estado === EstadoFactura::Cancelada) {
            return;
        }

        $cobrado = (float) $factura->total_cobrado;
        $total = (float) $factura->total;

        $nuevo = match (true) {
            $cobrado + 0.001 >= $total && $total > 0 => EstadoFactura::Cobrada,
            $cobrado > 0 => EstadoFactura::CobradaParcial,
            default => EstadoFactura::Emitida,
        };

        if ($factura->estado !== $nuevo) {
            $anterior = $factura->estado;
            $factura->estado = $nuevo;
            $factura->save();
            $factura->registrarBitacora('cobro_actualizado',
                ['estado' => $anterior->value],
                ['estado' => $nuevo->value, 'total_cobrado' => $cobrado]);
        }
    }
}
