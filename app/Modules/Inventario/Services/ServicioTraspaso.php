<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Services;

use App\Models\User;
use App\Modules\Inventario\Enums\EstadoTraspaso;
use App\Modules\Inventario\Enums\TipoMovimiento;
use App\Modules\Inventario\Models\Traspaso;
use App\Modules\Inventario\Models\TraspasoLinea;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El ciclo de vida de un traspaso entre almacenes.
 *
 *   borrador --enviar--> en_transito --recibir--> recibido
 *
 * Son DOS movimientos separados en el tiempo, no uno solo, porque la mercancia
 * de verdad tarda en llegar: mientras esta en el camion no la tiene el origen
 * (ya salio) ni el destino (aun no llega). Fusionarlos escondería ese hueco y
 * los dos almacenes cuadrarian mal durante el traslado.
 *
 * El costo viaja con la linea, asi que el destino recibe lo mismo que valia en
 * el origen: un traspaso mueve valor, no lo crea ni lo destruye.
 */
class ServicioTraspaso
{
    public function __construct(private readonly ServicioMovimientoInventario $movimientos) {}

    /**
     * Descarga el almacen de origen y pone el traspaso en transito.
     *
     * @throws RuntimeException si no es un borrador, si no tiene lineas o si
     *                          falta existencia en el origen
     */
    public function enviar(Traspaso $traspaso, User $usuario): Traspaso
    {
        if ($traspaso->estado !== EstadoTraspaso::Borrador) {
            throw new RuntimeException(
                "El traspaso {$traspaso->numero_traspaso} esta {$traspaso->estado->label()} y ya no se puede enviar."
            );
        }

        if ($traspaso->lineas()->count() === 0) {
            throw new RuntimeException('Un traspaso sin lineas no se puede enviar.');
        }

        return DB::transaction(function () use ($traspaso, $usuario): Traspaso {
            foreach ($traspaso->lineas as $linea) {
                $this->movimientos->registrar(
                    TipoMovimiento::TraspasoSalida,
                    (int) $linea->producto_id,
                    (int) $traspaso->almacen_origen_id,
                    (float) $linea->cantidad,
                    (float) $linea->costo_unitario,
                    $traspaso,
                    ['lote_id' => $linea->lote_id, 'organizacion_id' => $traspaso->organizacion_id],
                );
            }

            $traspaso->estado = EstadoTraspaso::EnTransito;
            $traspaso->enviado_en = now();
            $traspaso->aprobado_por = $usuario->id;
            $traspaso->aprobado_en = now();
            $traspaso->save();

            $traspaso->registrarBitacora('enviado', [], ['estado' => EstadoTraspaso::EnTransito->value]);

            return $traspaso->refresh();
        });
    }

    /**
     * Da entrada en el almacen de destino y cierra el traspaso.
     *
     * @throws RuntimeException si el traspaso no venia en transito
     */
    public function recibir(Traspaso $traspaso, User $usuario): Traspaso
    {
        if ($traspaso->estado !== EstadoTraspaso::EnTransito) {
            throw new RuntimeException(
                "Solo se recibe un traspaso en transito; {$traspaso->numero_traspaso} esta {$traspaso->estado->label()}."
            );
        }

        return DB::transaction(function () use ($traspaso, $usuario): Traspaso {
            foreach ($traspaso->lineas as $linea) {
                $this->movimientos->registrar(
                    TipoMovimiento::TraspasoEntrada,
                    (int) $linea->producto_id,
                    (int) $traspaso->almacen_destino_id,
                    (float) $linea->cantidad,
                    (float) $linea->costo_unitario,
                    $traspaso,
                    ['lote_id' => $linea->lote_id, 'organizacion_id' => $traspaso->organizacion_id],
                );
            }

            $traspaso->estado = EstadoTraspaso::Recibido;
            $traspaso->recibido_en = now();
            $traspaso->save();

            $traspaso->registrarBitacora('recibido', [], [
                'estado' => EstadoTraspaso::Recibido->value,
                'recibido_por' => $usuario->id,
            ]);

            return $traspaso->refresh();
        });
    }

    /**
     * Cancela un traspaso que todavia no salio del almacen.
     *
     * En transito ya no se cancela: la mercancia esta fuera del anaquel y la
     * unica salida honesta es recibirla (aunque sea de vuelta con otro
     * traspaso) o levantar un ajuste con su motivo.
     *
     * @throws RuntimeException
     */
    public function cancelar(Traspaso $traspaso): Traspaso
    {
        if (! $traspaso->estado->esCancelable()) {
            throw new RuntimeException(
                "El traspaso {$traspaso->numero_traspaso} esta {$traspaso->estado->label()}: ".
                'ya salio del almacen de origen y solo se puede recibir.'
            );
        }

        $traspaso->estado = EstadoTraspaso::Cancelado;
        $traspaso->save();

        $traspaso->registrarBitacora('cancelado', [], ['estado' => EstadoTraspaso::Cancelado->value]);

        return $traspaso->refresh();
    }

    /**
     * Agrega una linea, tomando el costo del catalogo si no viene capturado.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws RuntimeException si el traspaso ya no es editable
     */
    public function agregarLinea(Traspaso $traspaso, array $datos, ServicioExistencias $existencias): TraspasoLinea
    {
        if (! $traspaso->estado->esEditable()) {
            throw new RuntimeException('Solo se le agregan lineas a un traspaso en borrador.');
        }

        $costo = $datos['costo_unitario']
            ?? $existencias->costoPromedio((int) $datos['producto_id'], (int) $traspaso->almacen_origen_id);

        return $traspaso->lineas()->create([
            'producto_id' => $datos['producto_id'],
            'cantidad' => $datos['cantidad'],
            'costo_unitario' => $costo,
            'lote_id' => $datos['lote_id'] ?? null,
        ]);
    }
}
