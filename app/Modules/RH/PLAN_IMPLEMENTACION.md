# Plan de Implementacion — Modulo RH

> Borrador de trabajo. No es un reemplazo de `docs/PLANNING.md` ni de `README.md`:
> aterriza sus reglas en una lista de funcionalidades concretas, con el choque
> contra otros modulos ya revisado. Ante cualquier conflicto, manda `PLANNING.md`.
>
> **Este documento es solo planeacion.** Ninguna seccion de aqui — incluida la
> 6, "Orden de trabajo" — es una instruccion de que algo ya empezo. Es el orden
> en el que se implementara *cuando se de la senal de arrancar*. Hasta entonces,
> el unico archivo que se toca en este modulo es este `.md`.
>
> **Limite de alcance:** todo lo que se planea y, mas adelante, se implemente
> aqui vive dentro de `app/Modules/RH/`. No se edita ningun otro modulo
> (`Finanzas`, `Inventario`, `Compartido`, ...), ni el cascaron compartido
> (`resources/views/layouts/app.blade.php`, `app/Providers/ModuleServiceProvider.php`,
> `app/Modules/Compartido/Support/RegistroMenu.php`) salvo la unica llamada
> permitida por contrato: `RegistroMenu::registrar('RH', [...])` desde el propio
> `TableroController` de RH. Si una funcionalidad de RH *necesita* algo de otro
> modulo (activos de Inventario, notificaciones de Compartido), la solucion es
> leerlo a traves de los modelos/servicios que ese modulo expone — nunca copiar
> su tabla ni su logica dentro de RH (`PLANNING.md` — *ERP Philosophy* regla 5;
> Apendice B punto 5).

---

## 0. Principios de ingenieria (aplican a todo lo que se construya aqui)

Estos no son una seccion aparte del Core — son la forma en que se escribe
*cada* modelo, service, controller y vista de las secciones 1 y 2. Se
mencionan explicitos porque `PLANNING.md` los da por sentado ("Consistency
over cleverness", controllers delgados, un Service por operacion) pero no los
nombra con estas siglas.

- **SOLID**
  - *S — Responsabilidad unica*: un Service resuelve una operacion de negocio
    (`ServicioAsistencia` no calcula nomina). Un Controller solo traduce
    HTTP <-> Service. Un FormRequest solo valida.
  - *O — Abierto/cerrado*: los estados nuevos se agregan como caso de un Enum
    (`EstadoPermiso::Pendiente`, `::Aprobado`, ...), no como `if/else` nuevos
    repartidos en controllers y vistas.
  - *L — Sustitucion de Liskov*: si en el futuro hay una interfaz de
    aprobacion (`Contracts/Aprobable`), cualquier implementacion (permiso,
    corrida de nomina) debe poder usarse donde se espera esa interfaz, sin
    sorpresas.
  - *I — Segregacion de interfaces*: contratos pequenos y especificos en
    `Contracts/` (si se necesitan) en vez de una interfaz gigante que fuerza a
    implementar metodos que no aplican.
  - *D — Inversion de dependencias*: los Services reciben sus colaboradores
    inyectados (constructor), no hacen `new OtroService()` ni llaman
    `DB::table()` a tablas de otro modulo directamente.
- **Clean Code**: nombres que dicen que hacen (`aprobar()`, no `procesar2()`),
  funciones cortas con un nivel de abstraccion, sin comentarios que repitan lo
  que el codigo ya dice (ver `PLANNING.md` — *Coding Standards*).
- **DRY**: la formula de nomina vive en un solo lugar (`ServicioCorridaNomina`
  o un `Utils/CalculadoraNomina`), no copiada entre el Observer y el
  Controller. Los tres services del README se apoyan entre si en vez de
  repetir consultas.
- **KISS**: para el piso minimo, resolver el caso simple primero (una regla de
  aprobacion, un calculo de nomina) antes de generalizar para casos que RH
  todavia no tiene. La Fase 2 (seccion 4) existe justo para no meter esa
  complejidad antes de tiempo.
- **Alta cohesion, bajo acoplamiento**: cada carpeta del modulo agrupa una
  sola responsabilidad (`Models/` solo persistencia + relaciones, `Services/`
  solo reglas de negocio, `Observers/` solo reaccion a eventos del ORM). El
  acoplamiento entre RH y otros modulos pasa siempre por una interfaz publica
  (modelo o service del modulo dueno), nunca por una tabla o clase interna de
  otro modulo — lo mismo que ya exige la seccion 4 para el caso de Inventario
  y Compartido.

---

## 1. Piso minimo (Core — no negociable)

Es lo que el Apendice A y el README ya exigen. Sin esto el modulo no arranca.

- **Schema**: las 11 tablas ya migradas (`app/Modules/RH/Migrations/`) —
  `departamentos`, `puestos`, `empleados`, `asistencias`, `permisos`, `nominas`
  (las seis de v1, extendidas) + `nomina_periodos`, `nomina_corridas`,
  `contratos`, `documentos_empleado`, `evaluaciones_desempeno`.
- **Modelos**: uno por tabla en `Models/`, con `$fillable`, `$casts`,
  relaciones y accessors (`getNombreCompletoAttribute`, etc.), migrando lo que
  ya existe en `app/Models/*` y sumando los nuevos.
- **Enums**: uno por columna de estado/tipo (`EstadoEmpleado`, `EstadoAsistencia`,
  `EstadoPermiso`, `TipoContrato`, `EstadoNominaCorrida`, ...), con `label()` en
  espanol y `color()` para `<x-badge>`.
- **Traits**: `HasAuditTrail` (bitacora) y `HasAuditFields` (`creado_por`/
  `actualizado_por`) en todo modelo que los tenga en su migracion.
- **Observers**: folio de `nomina_corridas` (`numero_corrida`), recalculo de
  `horas_trabajadas` en asistencia, recalculo de `dias`/`total_neto`.
- **Requests**: un FormRequest por mutacion (Store/Update por entidad).
- **Services**: los tres que pide el README —
  `ServicioCorridaNomina`, `ServicioAsistencia`, `ServicioAprobacionPermisos`.
- **Routes**: `Routes/web.php` con el CRUD de cada catalogo/pagina + acciones
  propias (`permisos/{permiso}/aprobar`, `nomina-corridas/{corrida}/aplicar`).
- **Views**: dashboard, catalogos (empleados, departamentos, puestos),
  paginas (asistencia, permisos, nomina-periodos, nomina-corridas, contratos),
  reportes, usando los componentes compartidos (`x-card`, `x-table`,
  `x-filter-bar`, `x-badge`, `x-page-header`, `x-empty`).
- **Menu**: `RegistroMenu::registrar('RH', [...])` con una entrada por pagina
  principal, permisos correspondientes.
- **Tests**: feature test por recurso (index con busqueda/paginacion, store,
  update, destroy con soft delete) + uno por accion de ciclo de vida
  (aprobar permiso, aplicar corrida).

---

## 2. Funcionalidades del negocio (mapeadas a lo ya migrado)

Repaso de la lista que trajiste, contra lo que **ya existe** en el esquema y
en `PLANNING.md`. Todo esto es Core — no es opcional.

| # | Funcionalidad | Tabla(s) que ya la sostienen | Notas |
|---|---|---|---|
| 1 | Dashboard RH | (ninguna propia; agrega KPIs) | Empleados activos, ausencias de hoy, permisos pendientes, contratos por vencer (`documentos_empleado.vigencia` / `contratos.fecha_fin`). Sustituye `TableroModuloController` por `Controllers/TableroController.php`, conserva el nombre de ruta `rh.dashboard`. |
| 2 | Expediente de empleado | `empleados` (v1, extendida) | Ya tiene jerarquia (`jefe_id`), datos fiscales/bancarios, foto. Es el catalogo maestro: otros modulos (Compras aprobadores, Ventas vendedores) lo leen via `App\Modules\RH\Models\Empleado`, nunca con SQL directo. |
| 3 | Estructura organizacional | `departamentos`, `puestos` (v1, extendidas) | `departamentos.padre_id` + `jefe_id` + `empleados.manager_id` ya soportan el organigrama (`x-org-chart` en `PLANNING.md` 5.8). |
| 4 | Contratacion | `contratos` (nueva) | Vigencias, tipo, salario base para que nomina lo lea. Ojo: `empleados.sueldo_base` sigue siendo la fuente para `ServicioCorridaNomina`; `contratos.sueldo` es historico/legal, no el que se paga — deja esa distincion explicita en el service. |
| 5 | Asistencia y control de tiempo | `asistencias` (v1, extendida) | `horas_trabajadas` ya migrada. `ServicioAsistencia` es el puente hacia `ServicioCorridaNomina` (horas extra, faltas). |
| 6 | Vacaciones y permisos | `permisos` (v1, extendida) | `con_goce`, `revisado_por`, `dias` ya migrados. `ServicioAprobacionPermisos` alimenta asistencia (marca el dia como `permiso`) y nomina (permiso sin goce = deduccion). |
| 7 | Documentacion / expediente digital | `documentos_empleado` + `adjuntos` (Compartido) | **`adjuntos` es de Compartido, no se duplica.** `documentos_empleado` solo guarda el metadato de RH (`tipo_documento`, `vigencia`, `estado`) y una FK a `adjuntos.id`. El archivo binario sube via el `AttachmentService` compartido, carpeta `hr/documents/...` (ya documentado en `PLANNING.md` — *Attachments*). |
| 8 | Bajas | `empleados.fecha_baja` / `motivo_baja` (ya migradas) | La parte administrativa (fecha, motivo) ya tiene columnas. El **checklist de devolucion de equipo cruza con Inventario**, que todavia no define una tabla de "activos asignados a empleado" en `PLANNING.md`. Ver seccion 4 (choques) antes de tocar esto. |
| 9 | Evaluacion de desempeno | `evaluaciones_desempeno` (nueva) | **No es opcional**: ya esta migrada (`evaluador_id`, `calificacion`, `objetivos` JSON, `estado`). Muevela del "deseable" al Core — el trabajo pendiente es Modelo + Enum + vista, no el schema. |

---

## 3. Fase 2 (fuera del piso minimo, requiere tocar `PLANNING.md` primero)

Estas NO tienen tabla migrada todavia. El Apendice B, punto 6, es claro:
*"Cuando una regla de negocio sea ambigua, agregala a este documento antes de
inventar un patron distinto."* Antes de escribir una sola migracion para lo
de abajo, hay que sumarlas a la tabla de "Tablas propias" de RH en
`PLANNING.md` y a `docs/PLANNING.md` seccion *CORE MODULE 5*.

| # | Funcionalidad | Por que no entra al Core ahora | Como encajaria (si se aprueba) |
|---|---|---|---|
| 10 | Reclutamiento (ATS) | Tablas nuevas (`vacantes`, `candidatos`, `entrevistas`) sin precedente en `ERP.sql`/`PLANNING.md`. Un ATS completo es su propio subsistema. | Version basica: `vacantes` (puesto_id, estado) + `candidatos` (datos + estado `nuevo\|entrevista\|oferta\|contratado\|descartado`). Al pasar a `contratado`, crea el `Empleado` — mismo patron que `Lead::convertToCustomer` en CRM (`PLANNING.md` 6.1). |
| 11 | Capacitacion | Tablas nuevas (`cursos`, `inscripciones`), sin dueno definido. | `cursos` + `empleado_curso` (pivote con `estado`, `calificacion`, `fecha_completado`). Podria colgar de `evaluaciones_desempeno.objetivos` en vez de tabla nueva, si el alcance es chico. |
| 12 | Seguridad y salud laboral | Tablas nuevas (`incidentes`, `inspecciones`), sin dueno definido y bajo impacto para un MVP de ERP interno. | `incidentes_laborales` (empleado_id, tipo, fecha, gravedad, acciones_correctivas). Candidato claro a **no entrar** salvo que el negocio lo pida explicitamente. |

**Recomendacion:** dejar 10–12 fuera del alcance de esta entrega. Si el
profesor/cliente los pide, se agregan uno a la vez, siguiendo el Apendice A
completo (empezando por el schema), y documentando la tabla nueva en
`PLANNING.md` antes de migrar.

---

## 4. Choques detectados (no dupliques esto)

| Idea original | Choca con | Resolucion |
|---|---|---|
| "Comunicacion interna" | `notificaciones` es tabla de **Compartido** (`PLANNING.md` — *Notifications*, *Shared Modules*) | RH no crea tabla propia. Si necesita avisar (permiso aprobado, contrato por vencer), dispara evento y usa el `NotificationService` compartido — mismo patron que `SalesOrderPosted` en Finanzas. |
| "Documentacion / expediente digital" con subida de archivos | `adjuntos` es tabla de **Compartido** (polimorfica: `model_type`/`model_id`) | Ya resuelto en el schema: `documentos_empleado.adjunto_id -> adjuntos.id`. No se reinventa el upload; se usa `AttachmentService`. |
| "Bajas -> checklist de devolucion de equipo" | Inventario es dueno de `productos`/activos, pero **no existe todavia** una tabla de "equipo asignado a empleado" en `PLANNING.md` (Inventario solo tiene `stock_movements`, no asignacion nominal) | Dos opciones: (a) dejar el checklist como una lista de texto libre en `empleados`/`documentos_empleado` por ahora (sin FK real a Inventario), o (b) proponer en `PLANNING.md` una tabla `activos_asignados` dueno de Inventario, referenciada por RH. No inventar una tabla `activos` dentro de RH — violaria "master data compartida pertenece a un solo modulo" (`PLANNING.md` — *ERP Philosophy* regla 5). |
| "Evaluacion de desempeno" como opcional | Ya es tabla Core migrada (`evaluaciones_desempeno`) | No es Fase 2, es Core. Corregido en la seccion 2. |
| "Contratacion" con su propio salario | `contratos.sueldo` vs `empleados.sueldo_base` | No son la misma fuente de verdad. Dejar explicito en `ServicioCorridaNomina` cual lee (recomendado: `empleados.sueldo_base`, que un cambio de contrato actualiza via observer). |

---

## 5. Orden de trabajo (Apendice A.1, aplicado a RH)

1. ~~Schema~~ — hecho.
2. ~~Modelos + Enums (las 11 tablas)~~ — hecho. 11 modelos, 14 enums, 2 contratos
   (`Etiquetable` / `EstadoPresentable`) y `Utils/OpcionesEnum`. Los traits del
   paso 3 se aplicaron aqui mismo porque un modelo sin ellos habria quedado a
   medias. Notas de lo que se decidio sobre la marcha:
   - Un solo `EstadoActivacion` para las tres columnas `activo|inactivo`
     (departamentos, puestos, empleados) en vez de tres enums identicos; igual
     `TipoContrato` (empleados + contratos) y `FrecuenciaPago` (empleados +
     nomina_periodos).
   - Se sumo `TipoPermiso`, que faltaba en el conteo original de 13.
   - `color()` devuelve un estado que `sisen.css` ya tiene mapeado, para no
     tocar la hoja de estilos compartida (queda fuera del modulo).
   - `poliza_id` (Finanzas) y `adjunto_id` (Compartido) se quedan como columnas
     sin relacion Eloquent: esos modulos aun no publican su modelo, y RH no
     declara modelos sobre tablas ajenas.
   - Los modelos de `app/Models/*` (v1) NO se tocaron: siguen congelados segun
     `PLANNING.md`, y conviven con los del modulo sobre las mismas tablas.
3. ~~Traits (`TieneBitacora`, `TieneCamposAuditoria`) sobre los modelos que
   correspondan~~ — hecho junto con el paso 2.
4. ~~Observers (folio de corrida, recalculo de horas/dias)~~ — hecho. Cuatro:
   `ObservadorAsistencia` (horas trabajadas), `ObservadorPermiso` (dias),
   `ObservadorNomina` (total del recibo + totales de la corrida) y
   `ObservadorNominaCorrida` (folio NOM- desde ServicioFolios). Decisiones:
   - Se registran con el atributo `#[ObservedBy]` en cada modelo, no con un
     ServiceProvider: un provider de modulo obligaria a editar
     `bootstrap/providers.php`, que esta fuera del modulo.
   - Los totales de la corrida se sincronizan con el query builder (una sola
     consulta, sin eventos) para no dejar un renglon de bitacora por cada recibo
     al generar una corrida completa.
   - `total_pagar` y `horas_trabajadas` se recalculan siempre (son datos
     derivados); `dias` solo cuando el que guarda no lo dijo, para permitir
     medios dias y una futura politica de dias habiles.
5. ~~Requests (uno por mutacion)~~ — hecho. 13 clases: `RequestBase` + una
   `Guardar<Entidad>Request` por las 11 entidades + `RevisarPermisoRequest` y
   `AplicarNominaCorridaRequest`. Decisiones:
   - **Una peticion por entidad, no una por Store y otra por Update.** Las
     reglas de alta y edicion son identicas; lo unico que cambia es que los
     `unique` ignoren la propia fila, y eso lo resuelve `idEnRuta()`. Dos clases
     habrian sido la misma lista de reglas duplicada o una subclase vacia. Si
     alguna entidad llega a necesitar reglas distintas al editar, se extiende la
     clase existente. Es una desviacion de la letra de `PLANNING.md` ("one
     FormRequest per mutation") a favor de DRY/KISS.
   - **Los `unique` copian el comportamiento real de cada indice.** `codigo` y
     `numero_empleado` usan columna generada e ignoran los borrados logicos
     (`whereNull('deleted_at')`); `curp`, `rfc` y `correo` conservan el unico de
     v1, que SI abarca los borrados, y por eso su regla no lo excluye. Ponerlo
     al reves haria pasar la validacion y reventar el INSERT.
   - **Los campos derivados no se reciben**: `total_pagar`, `horas_trabajadas` y
     los totales de la corrida los ponen los observers. `permisos.estado` y el
     folio tampoco se aceptan: son del Service.
6. ~~Services: `ServicioAsistencia` -> `ServicioAprobacionPermisos` ->
   `ServicioCorridaNomina`~~ — hecho, en ese orden, cada uno inyectando al
   anterior por constructor. Decisiones:
   - Los tres modelos de nomina exponen su ciclo de vida solo por el Service.
     `NominaCorrida.estado` NO es fillable, asi que el Service asigna la
     propiedad directamente en vez de `update()`: un `update()` en masa la
     descartaria en silencio y la corrida se quedaria en borrador.
   - Todos los modelos declaran ahora `$attributes` con los mismos valores por
     omision del esquema. Sin eso, un modelo recien creado sin `estado` (que es
     justo lo que exigen las peticiones) lo tenia null en memoria y cualquier
     lectura del enum reventaba.
   - `aplicar()` usa bloqueo optimista real: `UPDATE ... WHERE version_fila = ?`.
     Si no afecta ninguna fila, alguien mas movio la corrida y se aborta.
   - Los mensajes de validacion en espanol viven en `RequestBase::messages()`,
     dentro del modulo, en vez de publicar `lang/es` en la raiz.
7. Routes (`Routes/web.php`, catalogos primero, paginas despues).
8. Views + `TableroController.php` (sustituye al provisional, conserva `rh.dashboard`) + `RegistroMenu::registrar('RH', [...])`.
9. Reportes (`hr.reports.*`: empleados, asistencia, permisos, nomina, contratos por vencer, headcount por departamento).
10. Tests por recurso + por accion de ciclo de vida.

### Decisiones de negocio pendientes de confirmar con RH

Ninguna bloquea el avance, pero las tres estan marcadas en el codigo y hay que
cerrarlas antes de pagar una nomina de verdad. Estan asi, y no resueltas a ojo,
porque inventar la regla habria producido numeros equivocados con apariencia de
correctos.

1. **De que periodicidad es `empleados.sueldo_base`.** El esquema no lo dice.
   `ServicioCorridaNomina` lo interpreta como sueldo MENSUAL y deriva el importe
   del periodo (`sueldo * 12 / periodos_por_ano`). Si resulta ser otra cosa, se
   corrige en `importeDelPeriodo()` y en ningun otro lugar.
2. **ISR e IMSS.** Salen de una tasa plana de configuracion que por omision vale
   CERO, asi que hoy los recibos se generan sin retenciones. Las tablas reales
   del SAT y las cuotas del IMSS son una tarea propia, con su UMA.
3. **Horas extra.** No se calculan solas porque el esquema no modela jornadas ni
   turnos: no hay contra que comparar. Se capturan en el recibo. Lo mismo aplica
   a la tolerancia de retardo, que necesita recibir la hora de entrada esperada
   como parametro.

### Resuelto sin tocar la raiz del proyecto

**No existe `lang/` y el framework solo trae `en`**, asi que toda la aplicacion
mostraba la llave cruda (`validation.required`) en vez de un mensaje. RH lo
resolvio con su propia bolsa de mensajes en `RequestBase::messages()`, que todas
las peticiones del modulo heredan; las que necesitan un texto propio hacen
`array_merge(parent::messages(), [...])`.

Ojo: **esto arregla solo las pantallas de RH.** Las de v1 y las de los demas
modulos siguen mostrando la llave cruda hasta que alguien publique
`lang/es/validation.php` en la raiz, que es una decision de quien lleve el
cascaron.

---

Definition of Done: la de `PLANNING.md` Apendice A.5 (migraciones == ERP.sql,
estados en Enums, `composer test` verde, Pint limpio, menu + dashboard
registrados).
