<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * El contrato de la barra lateral entre el cascaron y los modulos.
 *
 * layouts.app pinta lo que este registrado aqui, asi que agregar un modulo
 * nunca significa editar el layout. Un modulo registra sus entradas desde su
 * propio arranque:
 *
 *     RegistroMenu::registrar('Finanzas', [
 *         ['etiqueta' => 'Polizas', 'icono' => 'journal-text',
 *          'ruta' => 'finanzas.polizas.index', 'privilegio' => 'finanzas.polizas.ver'],
 *     ]);
 *
 * Las entradas cuyo privilegio no tiene el usuario nunca se pintan, y las que
 * apuntan a una ruta que aun no existe se omiten, de modo que un modulo a medio
 * construir no puede romper el cascaron.
 */
final class RegistroMenu
{
    /**
     * Metadatos de presentacion por modulo. Es el unico lugar donde el cascaron
     * conoce a un modulo por su nombre; todo lo demas se descubre de la carpeta.
     *
     * @var array<string, array{etiqueta: string, icono: string, descripcion: string, orden: int}>
     */
    private const MODULOS = [
        'Finanzas' => [
            'etiqueta' => 'Finanzas',
            'icono' => 'bank',
            'descripcion' => 'Contabilidad, polizas, periodos fiscales, bancos e impuestos.',
            'orden' => 10,
        ],
        'Ventas' => [
            'etiqueta' => 'Ventas',
            'icono' => 'cart-check',
            'descripcion' => 'Clientes, cotizaciones, pedidos, facturas y cobranza.',
            'orden' => 20,
        ],
        'Compras' => [
            'etiqueta' => 'Compras',
            'icono' => 'truck',
            'descripcion' => 'Proveedores, requisiciones, ordenes de compra y pagos.',
            'orden' => 30,
        ],
        'Inventario' => [
            'etiqueta' => 'Inventario',
            'icono' => 'boxes',
            'descripcion' => 'Productos, almacenes, movimientos, conteos y minimos.',
            'orden' => 40,
        ],
        'RH' => [
            'etiqueta' => 'Recursos Humanos',
            'icono' => 'people-fill',
            'descripcion' => 'Empleados, asistencia, permisos, nomina y organigrama.',
            'orden' => 50,
        ],
        'CRM' => [
            'etiqueta' => 'CRM',
            'icono' => 'diagram-3',
            'descripcion' => 'Prospectos, oportunidades, contactos, actividades y embudo.',
            'orden' => 60,
        ],
        'Compartido' => [
            'etiqueta' => 'Plataforma',
            'icono' => 'gear',
            'descripcion' => 'Roles, privilegios, catalogos, adjuntos, bitacora y notificaciones.',
            'orden' => 90,
        ],
    ];

    /** @var array<string, array<string, mixed>> */
    private static array $entradas = [];

    /**
     * @param  array<int, array<string, mixed>>  $entradas
     */
    public static function registrar(string $modulo, array $entradas): void
    {
        foreach ($entradas as $entrada) {
            // Indexado por ruta, para que volver a registrar la misma entrada
            // -- otra instancia de la aplicacion en las pruebas, un modulo que
            // arranca dos veces -- no duplique el enlace en la barra lateral.
            self::$entradas[$entrada['ruta']] = [
                'modulo' => $modulo,
                'etiqueta' => $entrada['etiqueta'],
                'icono' => $entrada['icono'] ?? 'dot',
                'ruta' => $entrada['ruta'],
                'privilegio' => $entrada['privilegio'] ?? null,
                'orden' => $entrada['orden'] ?? self::metadatos($modulo)['orden'],
            ];
        }
    }

    /**
     * Todas las entradas registradas, ordenadas, sin filtrar por visibilidad.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function todas(): array
    {
        $entradas = array_values(self::$entradas);

        usort($entradas, fn (array $a, array $b) => [$a['orden'], $a['etiqueta']] <=> [$b['orden'], $b['etiqueta']]);

        return $entradas;
    }

    /**
     * Las entradas que el usuario puede ver y que apuntan a una ruta real.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function visiblesPara(?User $usuario): array
    {
        return array_values(array_filter(self::todas(), function (array $entrada) use ($usuario): bool {
            if (! Route::has($entrada['ruta'])) {
                return false;
            }

            if ($entrada['privilegio'] === null) {
                return true;
            }

            return $usuario !== null && $usuario->tieneAlgunPrivilegio([$entrada['privilegio']]);
        }));
    }

    /** Metadatos de presentacion de una carpeta de modulo. */
    public static function metadatos(string $modulo): array
    {
        return self::MODULOS[$modulo] ?? [
            'etiqueta' => $modulo,
            'icono' => 'dot',
            'descripcion' => '',
            'orden' => 99,
        ];
    }

    /** Metadatos buscados por slug de URL (finanzas, rh, crm, ...). */
    public static function metadatosPorSlug(string $slug): array
    {
        foreach (self::MODULOS as $modulo => $metadatos) {
            if (strtolower($modulo) === $slug) {
                return $metadatos + ['modulo' => $modulo];
            }
        }

        return self::metadatos($slug) + ['modulo' => $slug];
    }

    /** Punto de apoyo para pruebas: olvida todo lo registrado. */
    public static function limpiar(): void
    {
        self::$entradas = [];
    }
}
