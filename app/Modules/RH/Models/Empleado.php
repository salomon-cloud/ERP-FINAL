<?php

declare(strict_types=1);

namespace App\Modules\RH\Models;

use App\Models\User;
use App\Modules\Compartido\Traits\TieneBitacora;
use App\Modules\Compartido\Traits\TieneCamposAuditoria;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Enums\FrecuenciaPago;
use App\Modules\RH\Enums\GeneroEmpleado;
use App\Modules\RH\Enums\TipoContrato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * El catalogo maestro de personas del ERP: la tabla `empleados` de SISEN v1,
 * elevada con numero de empleado, jerarquia (jefe_id), datos fiscales (NSS),
 * bancarios, baja, auditoria y borrado logico.
 *
 * Los demas modulos que necesiten a una persona (un aprobador en Compras, un
 * vendedor en Ventas) leen ESTE modelo. Nunca consultan la tabla por su cuenta
 * ni declaran un modelo propio sobre ella.
 *
 * Sobre el sueldo: `sueldo_base` es lo que la nomina paga hoy y es la fuente que
 * usa ServicioCorridaNomina. `contratos.sueldo` es el dato historico/legal de
 * cada contrato firmado, y no se usa para calcular el recibo.
 */
class Empleado extends Model
{
    use SoftDeletes, TieneBitacora, TieneCamposAuditoria;

    protected $table = 'empleados';

    protected $fillable = [
        'organizacion_id',
        'numero_empleado',
        'departamento_id',
        'puesto_id',
        'jefe_id',
        'user_id',
        'nombre',
        'apellidos',
        'genero',
        'curp',
        'rfc',
        'nss',
        'banco',
        'cuenta_bancaria',
        'correo',
        'telefono',
        'direccion',
        'fecha_nacimiento',
        'fecha_contratacion',
        'fecha_baja',
        'motivo_baja',
        'tipo_contrato',
        'sueldo_base',
        'moneda',
        'frecuencia_pago',
        'estado',
        'fotografia',
    ];

    /** Los mismos valores por omision que declara la migracion. */
    protected $attributes = [
        'estado' => 'activo',
        'tipo_contrato' => 'indefinido',
        'moneda' => 'MXN',
        'frecuencia_pago' => 'quincenal',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_contratacion' => 'date',
        'fecha_baja' => 'date',
        'sueldo_base' => 'decimal:2',
        'genero' => GeneroEmpleado::class,
        'tipo_contrato' => TipoContrato::class,
        'frecuencia_pago' => FrecuenciaPago::class,
        'estado' => EstadoActivacion::class,
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class);
    }

    /** La cuenta de acceso, cuando el empleado tiene una. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Su jefe directo: la mitad del organigrama. */
    public function jefe(): BelongsTo
    {
        return $this->belongsTo(self::class, 'jefe_id');
    }

    public function subordinados(): HasMany
    {
        return $this->hasMany(self::class, 'jefe_id');
    }

    /** Los departamentos que dirige (departamentos.jefe_id). */
    public function departamentosDirigidos(): HasMany
    {
        return $this->hasMany(Departamento::class, 'jefe_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(Permiso::class);
    }

    public function nominas(): HasMany
    {
        return $this->hasMany(Nomina::class);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoEmpleado::class);
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(EvaluacionDesempeno::class);
    }

    /** Las evaluaciones que este empleado aplico a otros. */
    public function evaluacionesAplicadas(): HasMany
    {
        return $this->hasMany(EvaluacionDesempeno::class, 'evaluador_id');
    }

    /** Accesor de SISEN v1, conservado tal cual porque las vistas lo usan. */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre.' '.$this->apellidos);
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('estado', EstadoActivacion::Activo);
    }

    /** La busqueda de la barra de filtros: nombre, numero, RFC, CURP o correo. */
    public function scopeBuscar(Builder $consulta, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $consulta;
        }

        return $consulta->where(function (Builder $filtro) use ($termino): void {
            $filtro->where('nombre', 'like', '%'.$termino.'%')
                ->orWhere('apellidos', 'like', '%'.$termino.'%')
                ->orWhere('numero_empleado', 'like', '%'.$termino.'%')
                ->orWhere('rfc', 'like', '%'.$termino.'%')
                ->orWhere('curp', 'like', '%'.$termino.'%')
                ->orWhere('correo', 'like', '%'.$termino.'%');
        });
    }
}
