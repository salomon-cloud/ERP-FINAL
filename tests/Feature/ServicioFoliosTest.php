<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Compartido\Models\SecuenciaDocumento;
use App\Modules\Compartido\Services\ServicioFolios;
use Database\Seeders\SecuenciaDocumentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Los folios visibles son un requisito duro del ERP: consecutivos, sin huecos
 * inventados y sin reutilizar los cancelados.
 */
class ServicioFoliosTest extends TestCase
{
    use RefreshDatabase;

    private ServicioFolios $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SecuenciaDocumentoSeeder::class);
        $this->servicio = app(ServicioFolios::class);
    }

    public function test_el_primer_folio_arranca_en_uno_y_va_con_relleno(): void
    {
        $this->assertSame('PED-000001', $this->servicio->siguiente('pedidos'));
    }

    public function test_los_folios_avanzan_de_uno_en_uno(): void
    {
        $folios = collect(range(1, 3))->map(fn () => $this->servicio->siguiente('facturas'));

        $this->assertSame(['FAC-000001', 'FAC-000002', 'FAC-000003'], $folios->all());
    }

    public function test_cada_modulo_lleva_su_propio_contador(): void
    {
        $this->servicio->siguiente('pedidos');
        $this->servicio->siguiente('pedidos');

        $this->assertSame('OC-000001', $this->servicio->siguiente('ordenes_compra'));
    }

    public function test_consultar_no_consume_el_folio(): void
    {
        $this->assertSame('POL-000001', $this->servicio->consultar('polizas'));
        $this->assertSame('POL-000001', $this->servicio->consultar('polizas'));
        $this->assertSame('POL-000001', $this->servicio->siguiente('polizas'));
    }

    public function test_un_modulo_sin_secuencia_configurada_falla_de_forma_explicita(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No hay una secuencia configurada para [inexistente]');

        $this->servicio->siguiente('inexistente');
    }

    public function test_una_secuencia_inactiva_no_entrega_folios(): void
    {
        SecuenciaDocumento::where('modulo', 'cobros')->update(['activo' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('esta inactiva');

        $this->servicio->siguiente('cobros');
    }
}
