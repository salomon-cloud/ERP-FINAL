<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Support;

use App\Modules\Compartido\Contracts\Etiquetable;
use BackedEnum;

/**
 * Convierte un enum en las dos formas que el resto del codigo necesita: el mapa
 * valor => etiqueta de un <select>, y la lista de valores validos de una regla
 * Rule::in().
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

    /**
     * Solo algunos casos, para los selectores que no ofrecen todo el ciclo de
     * vida: un formulario de alta deja elegir "borrador", nunca "cancelado".
     *
     * @param  array<int, BackedEnum&Etiquetable>  $casos
     * @return array<string, string>
     */
    public static function soloCasos(array $casos): array
    {
        $opciones = [];

        foreach ($casos as $caso) {
            $opciones[(string) $caso->value] = $caso->label();
        }

        return $opciones;
    }
}
