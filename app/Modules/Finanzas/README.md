# Modulo Finanzas y Contabilidad

> El centro de gravedad: todo movimiento de dinero del ERP termina siendo una poliza aqui.

| | |
|---|---|
| **Namespace** | `App\Modules\Finanzas` |
| **Prefijo de URL** | `/finanzas` |
| **Nombres de ruta** | `finanzas.*` (API: `api.finanzas.*`) |
| **Namespace de vistas** | `finanzas::` |
| **Depende de** | Compartido, RH (contabilizacion de nomina) |

## Tablas propias

Este modulo es el **unico** propietario de estas tablas. Cualquier otro modulo
que necesite leerlas lo hace a traves de sus modelos y servicios, nunca con SQL
directo ni con un modelo duplicado.

- `catalogo_cuentas`
- `periodos_fiscales`
- `centros_costo`
- `polizas`
- `poliza_lineas`
- `presupuestos`
- `presupuesto_lineas`
- `cuentas_bancarias`
- `movimientos_bancarios`
- `conciliaciones_bancarias`
- `conciliacion_lineas`
- `impuestos`
- `facturas_electronicas`
- `tipos_cambio`

## Vistas de reporte propias

Las crean las migraciones de este modulo. Los reportes SIEMPRE
consultan estas vistas; jamas se guarda una copia desnormalizada.

- `v_libro_mayor`
- `v_balanza_comprobacion`
- `v_balance_general`
- `v_estado_resultados`
- `v_flujo_efectivo`
- `v_reporte_impuestos`
- `v_presupuesto_vs_real`

## Servicios esperados

- **ServicioContabilizarPoliza** - valida cuadre, periodo y cuenta, y contabiliza
- **ServicioCierre** - cierra un periodo fiscal con la poliza de resultados
- **ServicioConciliacion** - empareja movimientos bancarios con polizas
- **ServicioTimbrado** - gancho de emision del CFDI (integracion SAT futura)

## Estructura

```
Finanzas/
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

## Como continuar

1. Lee `docs/PLANNING.md` completo, sobre todo *Module Architecture*,
   *Database Standards*, *Naming Conventions* y la seccion de este modulo.
2. Sigue el **Apendice A** en orden: esquema -> modelos y enums -> traits ->
   observers -> requests -> services -> rutas -> vistas -> reportes -> pruebas.
3. Antes de cada commit: `./vendor/bin/pint` y `composer test`.
4. Si cambias una convencion, actualiza `docs/PLANNING.md` primero.
