<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Los datos que la aplicacion NECESITA para funcionar: roles, privilegios,
 * catalogos y contadores de folio.
 *
 * No hay ni un dato de demostracion aqui, asi que es seguro correrlo en
 * produccion, y es idempotente: volver a ejecutarlo no duplica nada.
 *
 *     php artisan db:seed --class=CimientosSeeder
 */
class CimientosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolPrivilegioSeeder::class,
            CatalogoSeeder::class,
            SecuenciaDocumentoSeeder::class,
        ]);
    }
}
