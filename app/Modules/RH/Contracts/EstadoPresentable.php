<?php

declare(strict_types=1);

namespace App\Modules\RH\Contracts;

/**
 * Un enum de ESTADO: ademas de su etiqueta, sabe con que color se pinta.
 *
 * color() no devuelve un color ni una clase CSS, sino uno de los estados que
 * public/css/sisen.css ya tiene mapeado (activo, pagada, aprobado, presente /
 * inactivo, cancelada, rechazado, falta / pendiente, retardo / permiso). Asi un
 * estado nuevo de RH reutiliza la paleta existente y el modulo no necesita
 * tocar la hoja de estilos compartida:
 *
 *     <x-badge :estado="$corrida->estado->color()" :label="$corrida->estado->label()" />
 *
 * Ver docs/PLANNING.md - "Design Principles" 8.
 */
interface EstadoPresentable extends Etiquetable
{
    /** El estado de la paleta de sisen.css con el que se pinta este caso. */
    public function color(): string;
}
