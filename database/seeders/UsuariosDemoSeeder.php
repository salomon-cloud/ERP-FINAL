<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Compartido\Models\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Los usuarios con los que se recorre la demostracion, uno por modulo.
 *
 * Vive aparte porque los tres seeders de demo (RH, CRM y Finanzas) necesitan
 * los mismos usuarios: tenerlos aqui evita que cada uno declare su propia copia
 * de admin@sisen.com y que las copias se contradigan.
 *
 * Lo importante es la ULTIMA linea de cada alta: el rol normalizado en
 * `usuario_roles`. Sin el, un usuario no tiene ni un privilegio y el middleware
 * `permission:` de Ventas, Compras, Inventario, CRM y Finanzas le responde 403
 * en cada pantalla -- sembrar los datos de esos modulos no sirve de nada si
 * nadie mas que el administrador puede verlos.
 *
 * La columna heredada `users.role` es un enum de v1 con solo cuatro valores
 * (Administrador, Recursos Humanos, Contador, Empleado), asi que los perfiles
 * que v1 no conocia -- Ventas, Compras, Almacenista -- entran como 'Empleado'
 * ahi y reciben su verdadero perfil en `usuario_roles`. Es justo la capa de
 * compatibilidad que describe User::hasAnyRole().
 *
 * NO es un seeder de cimientos: son cuentas inventadas con una contrasena
 * publica y no deben existir en produccion.
 */
class UsuariosDemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public const ADMIN = 'admin@sisen.com';

    public const RH = 'rh@sisen.com';

    public const CONTADOR = 'contador@sisen.com';

    public const EMPLEADO = 'empleado@sisen.com';

    public const CRM = 'crm@sisen.com';

    public const VENTAS = 'ventas@sisen.com';

    public const FINANZAS = 'finanzas@sisen.com';

    public const COMPRAS = 'compras@sisen.com';

    public const ALMACEN = 'almacen@sisen.com';

    /**
     * correo => [nombre, columna heredada users.role, codigos de rol normalizado]
     *
     * Los codigos de rol son los de RolPrivilegioSeeder::ROLES.
     *
     * @var array<string, array{0: string, 1: string, 2: array<int, string>}>
     */
    private const USUARIOS = [
        self::ADMIN => ['Administrador SISEN', 'Administrador', ['administrador']],
        self::RH => ['Recursos Humanos', 'Recursos Humanos', ['recursos_humanos']],
        self::CONTADOR => ['Contador', 'Contador', ['contador']],
        self::EMPLEADO => ['Empleado Demo', 'Empleado', ['empleado']],
        self::CRM => ['Equipo CRM', 'Empleado', ['ventas']],
        self::VENTAS => ['Ventas Demo', 'Empleado', ['ventas']],
        self::FINANZAS => ['Finanzas Demo', 'Contador', ['contador']],
        self::COMPRAS => ['Compras Demo', 'Empleado', ['compras']],
        self::ALMACEN => ['Almacen Demo', 'Empleado', ['almacenista']],
    ];

    /**
     * Los tres seeders de modulo lo invocan para poder correr sueltos, asi que
     * en una corrida completa entra cuatro veces. Bastaria con que fuera
     * idempotente, pero cada pasada vuelve a hashear nueve contrasenas con
     * bcrypt -- unos cuatro segundos tirados. Con esto solo la primera trabaja.
     */
    private static bool $sembrados = false;

    /** @return array<string, User> */
    public function run(): array
    {
        if (self::$sembrados) {
            return self::mapa();
        }

        // Los roles los siembra CimientosSeeder. Si falta alguno, se avisa en
        // vez de reventar: el seeder de usuarios no es quien los define.
        $roles = Rol::query()->pluck('id', 'codigo');

        $mapa = [];

        foreach (self::USUARIOS as $correo => [$nombre, $rolHeredado, $codigosRol]) {
            $usuario = User::updateOrCreate(
                ['email' => $correo],
                [
                    'name' => $nombre,
                    'role' => $rolHeredado,
                    'password' => Hash::make(self::PASSWORD),
                    'estado' => 'activo',
                    'debe_cambiar_password' => false,
                ]
            );

            $faltantes = array_diff($codigosRol, $roles->keys()->all());

            if ($faltantes !== []) {
                $this->command?->warn(
                    "Rol(es) [".implode(', ', $faltantes)."] no existen todavia: corre CimientosSeeder antes que este."
                );
            }

            // sync y no attach: volver a sembrar deja al usuario exactamente con
            // los roles de esta lista, sin duplicar ni arrastrar los de antes.
            $usuario->roles()->sync($roles->only($codigosRol)->values()->all());

            $mapa[$correo] = $usuario;
        }

        self::$sembrados = true;

        $this->command?->info('Usuarios demo sembrados (contrasena: '.self::PASSWORD.').');

        return $mapa;
    }

    /**
     * Los mismos usuarios, para el seeder de un modulo que corre solo.
     *
     * @return array<string, User>
     */
    public static function mapa(): array
    {
        return User::query()
            ->whereIn('email', array_keys(self::USUARIOS))
            ->get()
            ->keyBy('email')
            ->all();
    }
}
