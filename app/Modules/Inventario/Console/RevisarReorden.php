<?php

declare(strict_types=1);

namespace App\Modules\Inventario\Console;

use App\Modules\Compartido\Models\RegistroBitacora;
use App\Modules\Inventario\Services\ServicioExistencias;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Revisa los minimos y avisa de lo que hay que comprar.
 *
 *     php artisan inventario:reorden
 *     php artisan inventario:reorden --almacen=3
 *
 * Se programa en routes/console.php para que corra a diario. Compara el
 * disponible de cada regla activa contra su minimo y deja el resultado en dos
 * lados: la salida de consola (para el operador) y la tabla `notificaciones`
 * (para la campana de la interfaz, cuando Compartido publique su
 * ServicioNotificaciones -- ver docs/david.md P4).
 *
 * Escribe la notificacion con el Query Builder y no con un modelo porque
 * Compartido todavia no publica uno para `notificaciones`; el dia que exista,
 * esta es la unica linea que cambia.
 */
class RevisarReorden extends Command
{
    protected $signature = 'inventario:reorden
                            {--almacen= : Revisar solo un almacen}
                            {--sin-avisos : Solo mostrar el resultado, sin notificar}';

    protected $description = 'Compara las existencias contra las reglas de reorden y avisa de lo que esta bajo minimo';

    public function handle(ServicioExistencias $existencias): int
    {
        $almacenId = $this->option('almacen') !== null ? (int) $this->option('almacen') : null;

        $faltantes = $existencias->bajoMinimo($almacenId);

        if ($faltantes->isEmpty()) {
            $this->info('Todo el inventario esta por encima de su minimo.');

            return self::SUCCESS;
        }

        $this->warn("{$faltantes->count()} producto(s) por debajo del minimo:");

        $this->table(
            ['SKU', 'Producto', 'Almacen', 'Disponible', 'Minimo', 'Sugerido'],
            $faltantes->map(fn ($fila) => [
                $fila->sku,
                $fila->producto_nombre,
                $fila->almacen_codigo,
                rtrim(rtrim(number_format((float) $fila->disponible, 4, '.', ''), '0'), '.'),
                rtrim(rtrim(number_format((float) $fila->cantidad_minima, 4, '.', ''), '0'), '.'),
                rtrim(rtrim(number_format($this->sugerido($fila), 4, '.', ''), '0'), '.'),
            ])->all(),
        );

        if (! $this->option('sin-avisos')) {
            $this->notificar($faltantes);
        }

        // Codigo distinto de cero: un cron puede detectar que hubo faltantes
        // sin tener que leer la salida.
        return self::FAILURE;
    }

    /**
     * Una notificacion por almacen y por destinatario, no una por producto:
     * nadie lee cuarenta avisos.
     *
     * Los destinatarios son quienes pueden ver existencias. Si nadie tiene ese
     * privilegio -- una instalacion recien sembrada, por ejemplo -- no se
     * inserta nada: `notificaciones.user_id` es obligatorio y un aviso sin
     * dueno no lo leeria nadie. El resumen queda igual en la bitacora.
     */
    private function notificar(Collection $faltantes): void
    {
        $destinatarios = $this->destinatarios();

        foreach ($faltantes->groupBy('almacen_id') as $almacenId => $productos) {
            $almacen = $productos->first()->almacen_nombre;

            $cuerpo = $productos->count().' producto(s) por debajo de su minimo: '.
                $productos->take(5)->pluck('producto_nombre')->implode(', ').
                ($productos->count() > 5 ? '...' : '');

            foreach ($destinatarios as $userId) {
                DB::table('notificaciones')->insert([
                    'user_id' => $userId,
                    'tipo' => 'stock_bajo',
                    'titulo' => "Stock bajo en {$almacen}",
                    'cuerpo' => $cuerpo,
                    'datos' => json_encode([
                        'almacen_id' => (int) $almacenId,
                        'productos' => $productos->count(),
                        'url' => route('inventario.reportes.stock-bajo', ['almacen_id' => $almacenId]),
                    ]),
                    'created_at' => now(),
                ]);
            }

            RegistroBitacora::create([
                'modulo' => 'Inventario',
                'accion' => 'alerta_stock_bajo',
                'entidad_tipo' => 'almacenes',
                'entidad_id' => (int) $almacenId,
                'valores_nuevos' => ['productos' => $productos->count(), 'avisados' => count($destinatarios)],
            ]);
        }

        $this->info(count($destinatarios) > 0
            ? 'Avisos enviados a '.count($destinatarios).' usuario(s).'
            : 'Nadie tiene el privilegio inventario.existencias.ver: solo se registro en la bitacora.');
    }

    /**
     * Los usuarios activos que pueden ver existencias, mas los administradores
     * de v1 (que pasan todo privilegio por la columna users.role).
     *
     * @return array<int, int>
     */
    private function destinatarios(): array
    {
        $porPrivilegio = DB::table('users')
            ->join('usuario_roles', 'usuario_roles.user_id', '=', 'users.id')
            ->join('rol_privilegios', 'rol_privilegios.rol_id', '=', 'usuario_roles.rol_id')
            ->join('privilegios', 'privilegios.id', '=', 'rol_privilegios.privilegio_id')
            ->where('privilegios.codigo', 'inventario.existencias.ver')
            ->where('users.estado', 'activo')
            ->whereNull('users.deleted_at')
            ->pluck('users.id');

        $administradores = DB::table('users')
            ->where('role', 'Administrador')
            ->where('estado', 'activo')
            ->whereNull('deleted_at')
            ->pluck('id');

        return $porPrivilegio->merge($administradores)->unique()->map(fn ($id) => (int) $id)->values()->all();
    }

    private function sugerido(object $fila): float
    {
        $reorden = (float) $fila->cantidad_reorden;

        if ($reorden > 0) {
            return $reorden;
        }

        $maximo = (float) $fila->cantidad_maxima;
        $objetivo = $maximo > 0 ? $maximo : (float) $fila->cantidad_minima;

        return max($objetivo - (float) $fila->disponible, 0.0);
    }
}
