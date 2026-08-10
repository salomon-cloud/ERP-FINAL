<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Traits;

use App\Modules\Compartido\Models\RegistroBitacora;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Lo que repiten los diecisiete controladores de documento del ERP.
 *
 * Dos cosas, y las dos venian copiadas en cada uno:
 *
 *   1. Ejecutar una accion del servicio y traducir su RuntimeException a un
 *      flash de error. Los servicios ya lanzan mensajes en espanol pensados
 *      para el usuario ("El pedido PED-000012 esta cancelado y ya no se puede
 *      surtir"); el controlador no vuelve a decidir nada, solo los presenta.
 *   2. Leer la linea de tiempo del documento desde bitacora_auditoria, que es
 *      lo que pinta la ficha de cada documento.
 */
trait ControlaDocumentos
{
    /**
     * Corre una accion de ciclo de vida y vuelve a la ficha del documento.
     *
     * @param  callable():mixed  $accion
     * @param  string  $ruta  nombre de ruta de la ficha (inventario.traspasos.show, ...)
     */
    protected function ejecutarAccion(callable $accion, Model $documento, string $ruta, string $exito): RedirectResponse
    {
        try {
            $accion();
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return redirect()->route($ruta, $documento)->with('success', $exito);
    }

    /**
     * Igual que la anterior, pero se queda donde estaba. Sirve para las
     * acciones que no cambian de pantalla: agregar o quitar una linea.
     *
     * @param  callable():mixed  $accion
     */
    protected function ejecutarEnSitio(callable $accion, string $exito): RedirectResponse
    {
        try {
            $accion();
        } catch (RuntimeException $error) {
            return back()->with('error', $error->getMessage());
        }

        return back()->with('success', $exito);
    }

    /**
     * La linea de tiempo del documento: altas, cambios y acciones semanticas
     * (confirmado, emitida, aplicada) que los servicios registraron.
     *
     * @return Collection<int, RegistroBitacora>
     */
    protected function bitacoraDe(Model $documento, int $limite = 25): Collection
    {
        return RegistroBitacora::query()
            ->where('entidad_tipo', $documento::class)
            ->where('entidad_id', $documento->getKey())
            ->with('usuario')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }
}
