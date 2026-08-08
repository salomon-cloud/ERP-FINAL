<?php

declare(strict_types=1);

namespace App\Modules\RH\Contracts;

/**
 * Un enum que sabe decir su nombre en espanol.
 *
 * Es el contrato minimo: lo implementan los enums de TIPO (genero, tipo de
 * contrato, frecuencia de pago, ...), que se pintan en un <select> pero nunca
 * como badge de estado.
 *
 * Se mantiene separado de EstadoPresentable a proposito: obligar a un tipo a
 * declarar un color seria pedirle que implemente algo que no usa (segregacion
 * de interfaces).
 */
interface Etiquetable
{
    /** El nombre visible, en espanol, para selects y tablas. */
    public function label(): string;
}
