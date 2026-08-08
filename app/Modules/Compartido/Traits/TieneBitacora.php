<?php

declare(strict_types=1);

namespace App\Modules\Compartido\Traits;

use App\Modules\Compartido\Models\RegistroBitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Escribe una fila de bitacora_auditoria por cada alta, cambio o baja del
 * modelo, con los valores anteriores y nuevos campo por campo.
 *
 * Las acciones del ciclo de vida de un documento (contabilizar, aprobar,
 * cancelar) llaman a registrarBitacora() con un nombre de accion semantico, de
 * modo que la linea de tiempo diga "contabilizada" y no "actualizada" aunque la
 * fila casi no haya cambiado.
 *
 * Nunca se escribe en la bitacora desde un controlador: para eso esta el trait.
 */
trait TieneBitacora
{
    public static function bootTieneBitacora(): void
    {
        static::created(fn ($modelo) => $modelo->registrarBitacora('creado', [], $modelo->atributosAuditables()));

        static::updated(function ($modelo): void {
            $cambios = $modelo->getChanges();
            unset($cambios['updated_at'], $cambios['actualizado_por']);

            if ($cambios === []) {
                return;
            }

            $modelo->registrarBitacora(
                'actualizado',
                array_intersect_key($modelo->getOriginal(), $cambios),
                $cambios
            );
        });

        static::deleted(fn ($modelo) => $modelo->registrarBitacora('eliminado', $modelo->atributosAuditables(), []));
    }

    /**
     * El modulo al que pertenece el modelo, deducido de su namespace:
     * App\Modules\Finanzas\Models\Poliza => "Finanzas".
     */
    public function moduloBitacora(): string
    {
        $segmentos = explode('\\', static::class);

        return $segmentos[2] ?? 'App';
    }

    /**
     * @param  array<string, mixed>  $anteriores
     * @param  array<string, mixed>  $nuevos
     */
    public function registrarBitacora(string $accion, array $anteriores = [], array $nuevos = []): void
    {
        RegistroBitacora::create([
            'user_id' => Auth::id(),
            'modulo' => $this->moduloBitacora(),
            'accion' => $accion,
            'entidad_tipo' => static::class,
            'entidad_id' => $this->getKey(),
            'valores_anteriores' => $anteriores ?: null,
            'valores_nuevos' => $nuevos ?: null,
            'direccion_ip' => Request::ip(),
            'agente_usuario' => substr((string) Request::userAgent(), 0, 500) ?: null,
        ]);
    }

    /**
     * Lo que vale la pena fotografiar: todo menos los atributos ocultos, para
     * que un hash de contrasena o un token jamas lleguen a la bitacora.
     *
     * @return array<string, mixed>
     */
    public function atributosAuditables(): array
    {
        return array_diff_key($this->attributesToArray(), array_flip($this->getHidden()));
    }
}
