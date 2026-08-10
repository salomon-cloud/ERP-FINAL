<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Contracts;

/**
 * Un enum de ESTADO: ademas de su etiqueta, sabe con que color se pinta.
 *
 * color() no devuelve un color ni una clase CSS, sino uno de los estados que
 * public/css/sisen.css ya tiene mapeado (activo, aprobado, pagada / cancelada,
 * rechazado, inactivo / pendiente / permiso / enviada, surtido, recibida). Asi
 * un estado nuevo reutiliza la paleta y el modulo no toca la hoja de estilos:
 *
 *     <x-badge :estado="$pedido->estado->color()" :label="$pedido->estado->label()" />
 */
interface EstadoPresentable extends Etiquetable
{
    /** El estado de la paleta de sisen.css con el que se pinta este caso. */
    public function color(): string;
}
