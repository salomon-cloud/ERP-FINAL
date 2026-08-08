# Modulo CRM

> El mundo previo a la venta: prospectos, oportunidades, contactos, actividades y el embudo.

| | |
|---|---|
| **Namespace** | `App\Modules\CRM` |
| **Prefijo de URL** | `/crm` |
| **Nombres de ruta** | `crm.*` (API: `api.crm.*`) |
| **Namespace de vistas** | `crm::` |
| **Depende de** | Compartido, Ventas (clientes) |

## Tablas propias

Este modulo es el **unico** propietario de estas tablas. Cualquier otro modulo
que necesite leerlas lo hace a traves de sus modelos y servicios, nunca con SQL
directo ni con un modelo duplicado.

- `empresas`
- `prospectos`
- `contactos`
- `oportunidades`
- `actividades`
- `notas_crm`
- `tareas`

## Vistas de reporte propias

Las crean las migraciones de este modulo. Los reportes SIEMPRE
consultan estas vistas; jamas se guarda una copia desnormalizada.

- `v_embudo_ventas`

## Servicios esperados

- **ServicioConversionProspecto** - prospecto calificado -> cliente de Ventas
- **ServicioOportunidad** - cambios de etapa, ganada -> pedido
- **ServicioActividad** - linea de tiempo y seguimientos

## Estructura

```
CRM/
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
