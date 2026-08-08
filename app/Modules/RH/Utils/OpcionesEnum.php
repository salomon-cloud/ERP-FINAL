<?php

declare(strict_types=1);

namespace App\Modules\RH\Utils;

use App\Modules\RH\Contracts\Etiquetable;
use BackedEnum;

/**
 * Convierte un enum del modulo en las dos formas que el resto del codigo
 * necesita: el mapa valor => etiqueta de un <select>, y la lista de valores
 * validos de una regla Rule::in().
 *
 * Vive aqui, y no repetido en cada enum, para que agregar un caso nuevo no
 * obligue a tocar catorce archivos.
 */
final class OpcionesEnum
{
    /**
     * Mapa valor => etiqueta, listo para un <select>.
     *
     * @param  class-string<BackedEnum&Etiquetable>  $enum
     * @return array<string, string>
     */
    public static function de(string $enum): array
    {
        $opciones = [];

        foreach ($enum::cases() as $caso) {
            $opciones[(string) $caso->value] = $caso->label();
        }

        return $opciones;
    }

    /**
     * Solo los valores, para validar: Rule::in(OpcionesEnum::valores(...)).
     *
     * @param  class-string<BackedEnum>  $enum
     * @return array<int, string>
     */
    public static function valores(string $enum): array
    {
        return array_map(
            static fn (BackedEnum $caso): string => (string) $caso->value,
            $enum::cases()
        );
    }
}
