<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Compartido\Models\Privilegio;
use App\Modules\Compartido\Models\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organizacion_id',
        'empleado_id',
        'name',
        'email',
        'telefono',
        'avatar_url',
        'password',
        'role',
        'estado',
        'debe_cambiar_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'debe_cambiar_password' => 'boolean',
            'password_cambiado_en' => 'datetime',
            'ultimo_acceso_en' => 'datetime',
            'bloqueado_en' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    /** Roles del modelo normalizado. La columna `role` de v1 sigue mandando en v1. */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'usuario_roles', 'user_id', 'rol_id');
    }

    /**
     * Verdadero cuando el usuario tiene alguno de los NOMBRES de rol indicados.
     *
     * Capa de compatibilidad: responde desde la tabla usuario_roles O desde la
     * columna heredada users.role, de modo que toda ruta y vista de v1 sigue
     * funcionando sin cambios mientras los modulos nuevos adoptan el modelo
     * normalizado.
     */
    public function hasAnyRole(array $roles): bool
    {
        if ($this->role === 'Administrador' || in_array($this->role, $roles, true)) {
            return true;
        }

        return $this->nombresDeRol()->contains(
            fn (string $nombre) => $nombre === 'Administrador' || in_array($nombre, $roles, true)
        );
    }

    /**
     * Verdadero cuando el usuario tiene alguno de los codigos de privilegio
     * indicados (por ejemplo finanzas.polizas.contabilizar). El administrador
     * siempre pasa.
     */
    public function tieneAlgunPrivilegio(array $privilegios): bool
    {
        if ($privilegios === []) {
            return true;
        }

        if ($this->role === 'Administrador' || $this->nombresDeRol()->contains('Administrador')) {
            return true;
        }

        return $this->codigosDePrivilegio()->intersect($privilegios)->isNotEmpty();
    }

    /** Nombres de rol que tiene por usuario_roles, memorizados por peticion. */
    public function nombresDeRol(): Collection
    {
        return $this->memorizar('sisen.roles', fn (): Collection => $this->roles()->pluck('nombre'));
    }

    /** Codigos de privilegio que le otorgan sus roles, memorizados por peticion. */
    public function codigosDePrivilegio(): Collection
    {
        return $this->memorizar('sisen.privilegios', fn (): Collection => Privilegio::query()
            ->join('rol_privilegios', 'rol_privilegios.privilegio_id', '=', 'privilegios.id')
            ->join('usuario_roles', 'usuario_roles.rol_id', '=', 'rol_privilegios.rol_id')
            ->where('usuario_roles.user_id', $this->getKey())
            ->pluck('privilegios.codigo'));
    }

    /**
     * La autorizacion se consulta en casi cada peticion, asi que las dos
     * busquedas anteriores se resuelven una sola vez por instancia y no una vez
     * por llamada.
     *
     * @param  callable(): Collection  $resolver
     */
    private function memorizar(string $clave, callable $resolver): Collection
    {
        if (! array_key_exists($clave, $this->cacheAutorizacion)) {
            $this->cacheAutorizacion[$clave] = $this->exists ? $resolver() : collect();
        }

        return $this->cacheAutorizacion[$clave];
    }

    /** @var array<string, Collection> */
    private array $cacheAutorizacion = [];
}
