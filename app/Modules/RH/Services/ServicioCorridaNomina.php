<?php

declare(strict_types=1);

namespace App\Modules\RH\Services;

use App\Models\User;
use App\Modules\RH\Enums\EstadoActivacion;
use App\Modules\RH\Enums\EstadoNomina;
use App\Modules\RH\Enums\EstadoNominaCorrida;
use App\Modules\RH\Models\Empleado;
use App\Modules\RH\Models\Nomina;
use App\Modules\RH\Models\NominaCorrida;
use App\Modules\RH\Models\NominaPeriodo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El ciclo de vida de una corrida de nomina: crear, procesar, aplicar, cancelar.
 *
 * DOS SUPUESTOS QUE HAY QUE CONFIRMAR CON RH ANTES DE PAGAR DE VERDAD:
 *
 *  1. `empleados.sueldo_base` se interpreta como sueldo MENSUAL, y de ahi se
 *     deriva lo que toca al periodo (una quincena es sueldo * 12 / 24). El
 *     esquema no dice de que periodicidad es la cifra, y de esto depende cada
 *     recibo. Si resulta ser otra cosa, se corrige en importeDelPeriodo() y en
 *     ningun otro lugar.
 *
 *  2. El ISR y el IMSS salen de una tasa plana de configuracion que por omision
 *     vale CERO. No se inventan aqui las tablas del SAT ni las cuotas del IMSS:
 *     mientras nadie configure `sisen.hr.isr_rate` e `sisen.hr.imss_rate`, los
 *     recibos salen sin retenciones y hay que capturarlas a mano. El calculo
 *     fiscal real es una tarea propia, con sus tablas y su UMA.
 *
 * Las horas extra tampoco se calculan solas: el esquema no modela jornadas ni
 * turnos, asi que no hay contra que comparar. Se capturan en el recibo.
 */
class ServicioCorridaNomina
{
    public function __construct(
        private readonly ServicioAsistencia $asistencias,
        private readonly ServicioAprobacionPermisos $permisos,
    ) {}

    /**
     * Abre una corrida sobre un periodo. El folio lo pone el observer.
     *
     * @throws RuntimeException si el periodo ya no admite corridas
     */
    public function crear(NominaPeriodo $periodo, ?int $organizacionId = null): NominaCorrida
    {
        if (! $periodo->estado->admiteCorridas()) {
            throw new RuntimeException(
                "El periodo {$periodo->codigo_periodo} esta {$periodo->estado->label()} y ya no admite corridas nuevas."
            );
        }

        return NominaCorrida::create([
            'periodo_id' => $periodo->id,
            'organizacion_id' => $organizacionId ?? $periodo->organizacion_id,
        ]);
    }

    /**
     * Genera los recibos del periodo. Se puede repetir mientras la corrida siga
     * en borrador: los recibos anteriores se dan de baja y se vuelven a armar.
     *
     * @throws RuntimeException si la corrida ya no es editable
     */
    public function procesar(NominaCorrida $corrida, User $usuario): NominaCorrida
    {
        if (! $corrida->estado->esEditable()) {
            throw new RuntimeException(
                "La corrida {$corrida->numero_corrida} esta {$corrida->estado->label()} y ya no se puede procesar."
            );
        }

        return DB::transaction(function () use ($corrida, $usuario): NominaCorrida {
            // Uno por uno para que el observer mantenga cuadrados los totales.
            $corrida->recibos()->get()->each(fn (Nomina $recibo) => $recibo->delete());

            $periodo = $corrida->periodo;

            foreach ($this->empleadosDelPeriodo($periodo) as $empleado) {
                $this->construirRecibo($corrida, $periodo, $empleado);
            }

            // Asignacion directa y no update(): estado, generada_en y
            // procesada_por NO son fillable a proposito, para que ninguna
            // peticion pueda moverlos. Un update() en masa los descartaria en
            // silencio y la corrida se quedaria en borrador.
            $corrida->estado = EstadoNominaCorrida::Procesada;
            $corrida->generada_en = now();
            $corrida->procesada_por = $usuario->id;
            $corrida->save();

            $corrida->registrarBitacora('procesada', [], ['estado' => EstadoNominaCorrida::Procesada->value]);

            return $corrida->refresh();
        });
    }

    /**
     * Da la corrida por definitiva.
     *
     * `$versionFila` es la version que el usuario tenia en pantalla. Si alguien
     * mas movio la corrida mientras tanto, la actualizacion condicionada no
     * afecta ninguna fila y se aborta: aplicar una nomina con numeros viejos
     * significa pagar mal.
     *
     * @throws RuntimeException si no esta procesada o si la version cambio
     */
    public function aplicar(NominaCorrida $corrida, User $usuario, int $versionFila): NominaCorrida
    {
        if ($corrida->estado !== EstadoNominaCorrida::Procesada) {
            throw new RuntimeException(
                "Solo se aplica una corrida procesada; {$corrida->numero_corrida} esta {$corrida->estado->label()}."
            );
        }

        return DB::transaction(function () use ($corrida, $usuario, $versionFila): NominaCorrida {
            $afectadas = NominaCorrida::query()
                ->whereKey($corrida->getKey())
                ->where('version_fila', $versionFila)
                ->update([
                    'estado' => EstadoNominaCorrida::Aplicada->value,
                    'aplicada_en' => now(),
                    'aprobada_por' => $usuario->id,
                    'version_fila' => $versionFila + 1,
                    'updated_at' => now(),
                ]);

            if ($afectadas === 0) {
                throw new RuntimeException(
                    'Alguien mas modifico esta corrida mientras la revisabas. Vuelve a cargarla antes de aplicarla.'
                );
            }

            $corrida->refresh();
            $corrida->registrarBitacora('aplicada', [], ['estado' => EstadoNominaCorrida::Aplicada->value]);

            // Aqui va la poliza de Finanzas (sueldos, por pagar, ISR e IMSS),
            // que llenara `poliza_id`. Se deja pendiente a proposito: la tabla
            // `polizas` es de Finanzas y ese modulo todavia no publica el
            // servicio que la contabiliza. RH nunca escribe esa tabla directo.

            return $corrida;
        });
    }

    /**
     * Cancela la corrida y da de baja sus recibos.
     *
     * @throws RuntimeException si ya fue aplicada o cancelada
     */
    public function cancelar(NominaCorrida $corrida): NominaCorrida
    {
        if (! $corrida->estado->esCancelable()) {
            throw new RuntimeException(
                "La corrida {$corrida->numero_corrida} esta {$corrida->estado->label()} y ya no se puede cancelar."
            );
        }

        return DB::transaction(function () use ($corrida): NominaCorrida {
            $corrida->recibos()->get()->each(fn (Nomina $recibo) => $recibo->delete());

            // Directo, por lo mismo que en procesar(): estado no es fillable.
            $corrida->estado = EstadoNominaCorrida::Cancelada;
            $corrida->save();

            $corrida->registrarBitacora('cancelada', [], ['estado' => EstadoNominaCorrida::Cancelada->value]);

            return $corrida->refresh();
        });
    }

    /**
     * Los empleados que entran en el periodo: activos, de la misma organizacion
     * y con la misma frecuencia de pago.
     *
     * Lo de la frecuencia importa: procesar a un empleado mensual dentro de una
     * quincena le pagaria de mas.
     *
     * @return Collection<int, Empleado>
     */
    private function empleadosDelPeriodo(NominaPeriodo $periodo)
    {
        return Empleado::query()
            ->where('estado', EstadoActivacion::Activo)
            ->where('frecuencia_pago', $periodo->frecuencia)
            ->when($periodo->organizacion_id !== null,
                fn ($consulta) => $consulta->where('organizacion_id', $periodo->organizacion_id))
            // Quien fue dado de baja antes de que empezara el periodo no cobra.
            ->where(fn ($consulta) => $consulta
                ->whereNull('fecha_baja')
                ->orWhereDate('fecha_baja', '>=', $periodo->fecha_inicio))
            ->orderBy('id')
            ->get();
    }

    /** Arma el recibo de un empleado. El total lo calcula el observer. */
    private function construirRecibo(NominaCorrida $corrida, NominaPeriodo $periodo, Empleado $empleado): Nomina
    {
        $sueldoPeriodo = $this->importeDelPeriodo($empleado);

        $resumen = $this->asistencias->resumenDelPeriodo($empleado, $periodo->fecha_inicio, $periodo->fecha_fin);
        $sinGoce = $this->permisos->diasSinGoce($empleado, $periodo->fecha_inicio, $periodo->fecha_fin);

        $diasAusencia = $resumen['faltas'] + $sinGoce;
        $diasDelPeriodo = (int) $periodo->fecha_inicio->diffInDays($periodo->fecha_fin) + 1;

        $descuento = $diasDelPeriodo > 0
            ? round($sueldoPeriodo / $diasDelPeriodo * $diasAusencia, 2)
            : 0.0;

        // Nunca se descuenta mas de lo que se gana: un recibo negativo violaria
        // chk_nomina_corrida_totales al sumarse en la corrida.
        $descuento = min($descuento, $sueldoPeriodo);

        $base = round($sueldoPeriodo, 2);

        return Nomina::create([
            'empleado_id' => $empleado->id,
            'corrida_id' => $corrida->id,
            'periodo_pago' => $periodo->codigo_periodo,
            'fecha_pago' => $periodo->fecha_pago,
            'sueldo_base' => $base,
            'bonos' => 0,
            'horas_extra' => 0,
            'horas_extra_cantidad' => 0,
            'deducciones' => $descuento,
            'dias_ausencia' => $diasAusencia,
            'isr' => $this->retencion($base, 'sisen.hr.isr_rate'),
            'imss' => $this->retencion($base, 'sisen.hr.imss_rate'),
            'estado' => EstadoNomina::Pendiente,
        ]);
    }

    /**
     * Lo que le toca al empleado por este periodo, a partir de su sueldo
     * mensual y de cada cuanto se le paga (ver el supuesto 1 de la clase).
     */
    private function importeDelPeriodo(Empleado $empleado): float
    {
        $mensual = (float) $empleado->sueldo_base;

        return $mensual * 12 / $empleado->frecuencia_pago->periodosPorAno();
    }

    /**
     * Retencion por tasa plana de configuracion. Devuelve 0 mientras la tasa no
     * este configurada (ver el supuesto 2 de la clase).
     */
    private function retencion(float $base, string $llaveConfig): float
    {
        $tasa = (float) config($llaveConfig, 0.0);

        return round($base * $tasa, 2);
    }
}
