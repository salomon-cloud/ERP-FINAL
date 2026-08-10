<?php

declare(strict_types=1);

namespace App\Modules\Ventas\Services;

use App\Modules\Inventario\Models\Producto;
use App\Modules\Ventas\Models\Cliente;
use App\Modules\Ventas\Models\ListaPrecio;
use App\Modules\Ventas\Models\ListaPrecioItem;

/**
 * Que precio le toca a este producto, para este cliente, en esta cantidad.
 *
 * LA CASCADA, en este orden y con el primero que responda:
 *
 *   1. La lista del CLIENTE (su convenio particular)
 *   2. La lista PREDETERMINADA del sistema
 *   3. El `precio_venta` del catalogo
 *
 * Dentro de una lista, un producto puede tener varios escalones por cantidad
 * minima: 100 pesos a partir de 1 pieza, 85 a partir de 50. Se toma el escalon
 * MAS ALTO que la cantidad pedida alcanza -- que es el de precio mas bajo --
 * porque asi funciona un descuento por volumen.
 *
 * El precio que devuelve se CONGELA en la linea del documento. Que la lista
 * cambie manana no reescribe una cotizacion ya enviada.
 */
class ServicioResolverPrecio
{
    public function resolver(Producto $producto, ?Cliente $cliente = null, float $cantidad = 1, ?ListaPrecio $lista = null): float
    {
        $listaElegida = $lista
            ?? $cliente?->listaPrecio
            ?? ListaPrecio::predeterminada()->first();

        if ($listaElegida !== null) {
            $precio = $this->precioEnLista($listaElegida, $producto, $cantidad);

            if ($precio !== null) {
                return $precio;
            }
        }

        return (float) $producto->precio_venta;
    }

    /**
     * El precio del escalon que corresponde a esa cantidad, o null si el
     * producto no esta en la lista.
     */
    private function precioEnLista(ListaPrecio $lista, Producto $producto, float $cantidad): ?float
    {
        $item = ListaPrecioItem::query()
            ->where('lista_precio_id', $lista->getKey())
            ->where('producto_id', $producto->getKey())
            ->where('cantidad_minima', '<=', $cantidad)
            // El escalon mas alto que la cantidad alcanza: el de mayor
            // cantidad_minima, que en una lista bien armada es el mas barato.
            ->orderByDesc('cantidad_minima')
            ->first();

        return $item !== null ? (float) $item->precio : null;
    }

    /**
     * Que lista se le va a aplicar a un cliente, para mostrarla en pantalla.
     *
     * Devuelve null cuando no hay ninguna y el precio saldra del catalogo.
     */
    public function listaDe(?Cliente $cliente): ?ListaPrecio
    {
        return $cliente?->listaPrecio ?? ListaPrecio::predeterminada()->first();
    }
}
