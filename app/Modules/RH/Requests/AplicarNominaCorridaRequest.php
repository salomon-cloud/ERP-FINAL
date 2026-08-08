<?php

declare(strict_types=1);

namespace App\Modules\RH\Requests;

/**
 * Aplicar una corrida: el paso que la vuelve definitiva y genera la poliza.
 *
 * Lo unico que recibe es `version_fila`, la version que el usuario tenia en
 * pantalla cuando decidio aplicar. ServicioCorridaNomina la compara contra la
 * de la base y se niega si no coinciden: entre que se abrio la pantalla y se
 * apreto el boton, alguien mas pudo haber tocado la corrida, y aplicar una
 * nomina con numeros viejos significa pagar mal.
 *
 * Es el bloqueo optimista que describe PLANNING en "Database Standards".
 */
class AplicarNominaCorridaRequest extends RequestBase
{
    public function rules(): array
    {
        return [
            'version_fila' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'version_fila' => 'version del registro',
        ]);
    }
}
