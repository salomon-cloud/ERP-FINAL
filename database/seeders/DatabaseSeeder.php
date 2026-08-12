<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // El orden importa: los cimientos definen los roles que
        // UsuariosDemoSeeder asigna, y Finanzas cierra al final porque su
        // poliza de nomina se calcula sobre la corrida que deja RH.
        $this->call([
            CimientosSeeder::class,
            UsuariosDemoSeeder::class,
            DatosDemoErpSeeder::class,
            RhDemoSeeder::class,
            CrmDemoSeeder::class,
            FinanzasDemoSeeder::class,
        ]);
    }
}
