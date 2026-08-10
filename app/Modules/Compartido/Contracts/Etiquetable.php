<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Contracts;

/**
 * Un enum que sabe como se llama en pantalla.
 *
 * RH declaro este mismo contrato dentro de su modulo cuando era el unico que lo
 * necesitaba. Ventas, Compras e Inventario lo necesitan los tres, asi que vive
 * aqui en vez de repetido cuatro veces. El de RH se deja intacto: cambiarlo
 * obligaria a tocar un modulo que ya esta probado y cerrado.
 */
interface Etiquetable
{
    /** El texto que ve el usuario. */
    public function label(): string;
}
