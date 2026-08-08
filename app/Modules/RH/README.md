# Modulo Recursos Humanos

> Personas, asistencia, permisos y nomina. Eleva a nivel empresarial las tablas que SISEN v1 ya tenia.

| | |
|---|---|
| **Namespace** | `App\Modules\RH` |
| **Prefijo de URL** | `/rh` |
| **Nombres de ruta** | `rh.*` (API: `api.rh.*`) |
| **Namespace de vistas** | `rh::` |
| **Depende de** | Compartido, Finanzas (poliza de nomina) |

## Tablas propias

Este modulo es el **unico** propietario de estas tablas. Cualquier otro modulo
que necesite leerlas lo hace a traves de sus modelos y servicios, nunca con SQL
directo ni con un modelo duplicado.

- `departamentos (v1, extendida)`
- `puestos (v1, extendida)`
- `empleados (v1, extendida)`
- `asistencias (v1, extendida)`
- `permisos (v1, extendida)`
- `nominas (v1, extendida)`
- `nomina_periodos`
- `nomina_corridas`
- `contratos`
- `documentos_empleado`
- `evaluaciones_desempeno`

## Servicios esperados

- **ServicioCorridaNomina** - arma los recibos desde sueldo, asistencia y permisos
- **ServicioAsistencia** - entradas y salidas, horas trabajadas, tolerancia de retardo
- **ServicioAprobacionPermisos** - aprueba o rechaza y alimenta asistencia y nomina

## Estructura

```
RH/
|- Controllers/         controladores HTTP (+ Api/ para el API JSON)
|- Requests/            un FormRequest por mutacion
|- Services/            logica de negocio (contabilizar/aprobar/cancelar/convertir)
|- Models/              modelos Eloquent (uno por tabla)
|- Enums/               enums PHP para cada columna de estado o tipo
|- Observers/           folios de documento, recalculo de totales
|- Utils/               ayudantes puros, calculadoras, formateadores
|- Contracts/           interfaces locales del modulo
|- Routes/              web.php y api.php (los registra el provider)
|- Views/               dashboard/ catalogos/ paginas/ components/ partials/
`- Migrations/          las migraciones de este modulo
```

## Convivencia con SISEN v1

Este modulo NO crea tablas paralelas. `departamentos`, `puestos`, `empleados`,
`asistencias`, `permisos` y `nominas` son las tablas de SISEN v1: las migraciones
de RH les AGREGAN columnas (jerarquia, datos fiscales y bancarios, auditoria,
borrado logico) sin renombrar ni eliminar nada. Por eso hay una sola verdad
sobre un empleado y las pantallas v1 siguen funcionando sin tocarlas.

## Como continuar

1. Lee `docs/PLANNING.md` completo, sobre todo *Module Architecture*,
   *Database Standards*, *Naming Conventions* y la seccion de este modulo.
2. Sigue el **Apendice A** en orden: esquema -> modelos y enums -> traits ->
   observers -> requests -> services -> rutas -> vistas -> reportes -> pruebas.
3. Antes de cada commit: `./vendor/bin/pint` y `composer test`.
4. Si cambias una convencion, actualiza `docs/PLANNING.md` primero.
