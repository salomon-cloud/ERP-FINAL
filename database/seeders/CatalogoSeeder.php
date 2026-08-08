<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Compartido\Models\Catalogo;
use Illuminate\Database\Seeder;

/**
 * Listas de valores base que varios modulos referencian por llave foranea
 * (clientes.condicion_pago_id, proveedores.condicion_pago_id, ...).
 *
 * En `valor` viajan los dias de credito de cada condicion, para que el servicio
 * calcule la fecha de vencimiento sin tabla adicional.
 */
class CatalogoSeeder extends Seeder
{
    /** @var array<string, array<int, array{codigo: string, nombre: string, valor?: string}>> */
    private const CATALOGOS = [
        'condiciones_pago' => [
            ['codigo' => 'CONTADO', 'nombre' => 'De contado', 'valor' => '0'],
            ['codigo' => 'NET15', 'nombre' => '15 dias', 'valor' => '15'],
            ['codigo' => 'NET30', 'nombre' => '30 dias', 'valor' => '30'],
            ['codigo' => 'NET60', 'nombre' => '60 dias', 'valor' => '60'],
            ['codigo' => 'NET90', 'nombre' => '90 dias', 'valor' => '90'],
        ],
        'forma_pago' => [
            ['codigo' => 'EFECTIVO', 'nombre' => 'Efectivo'],
            ['codigo' => 'TRANSFERENCIA', 'nombre' => 'Transferencia electronica'],
            ['codigo' => 'CHEQUE', 'nombre' => 'Cheque'],
            ['codigo' => 'TARJETA', 'nombre' => 'Tarjeta de credito o debito'],
            ['codigo' => 'LIGA_PAGO', 'nombre' => 'Liga de pago'],
        ],
        'moneda' => [
            ['codigo' => 'MXN', 'nombre' => 'Peso mexicano'],
            ['codigo' => 'USD', 'nombre' => 'Dolar estadounidense'],
            ['codigo' => 'EUR', 'nombre' => 'Euro'],
        ],
        'tipo_contrato' => [
            ['codigo' => 'INDEFINIDO', 'nombre' => 'Por tiempo indeterminado'],
            ['codigo' => 'TEMPORAL', 'nombre' => 'Por tiempo determinado'],
            ['codigo' => 'PRACTICAS', 'nombre' => 'Practicas profesionales'],
            ['codigo' => 'SERVICIOS', 'nombre' => 'Prestacion de servicios'],
            ['codigo' => 'MEDIO_TIEMPO', 'nombre' => 'Medio tiempo'],
        ],
        'motivo_descuento' => [
            ['codigo' => 'VOLUMEN', 'nombre' => 'Descuento por volumen'],
            ['codigo' => 'PRONTO_PAGO', 'nombre' => 'Pronto pago'],
            ['codigo' => 'CAMPANA', 'nombre' => 'Campana comercial'],
            ['codigo' => 'AUTORIZADO', 'nombre' => 'Autorizado por direccion'],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOGOS as $grupo => $valores) {
            foreach ($valores as $orden => $valor) {
                Catalogo::updateOrCreate(
                    ['grupo' => $grupo, 'codigo' => $valor['codigo']],
                    [
                        'nombre' => $valor['nombre'],
                        'valor' => $valor['valor'] ?? null,
                        'orden' => $orden,
                        'activo' => true,
                    ]
                );
            }
        }
    }
}
