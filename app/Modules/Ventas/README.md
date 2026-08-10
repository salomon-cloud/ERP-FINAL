# Modulo Ventas

> El catalogo de clientes y la cadena cotizacion -> pedido -> factura -> cobro.

| | |
|---|---|
| **Namespace** | `App\Modules\Ventas` |
| **Prefijo de URL** | `/ventas` |
| **Nombres de ruta** | `ventas.*` (API: `api.ventas.*`) |
| **Namespace de vistas** | `ventas::` |
| **Depende de** | Compartido, Inventario (productos), Finanzas (polizas, impuestos) |

## Tablas propias

Este modulo es el **unico** propietario de estas tablas. Cualquier otro modulo
que necesite leerlas lo hace a traves de sus modelos y servicios, nunca con SQL
directo ni con un modelo duplicado.

- `clientes`
- `listas_precios`
- `lista_precio_items`
- `cotizaciones`
- `cotizacion_lineas`
- `pedidos`
- `pedido_lineas`
- `facturas`
- `factura_lineas`
- `notas_credito`
- `nota_credito_lineas`
- `cobros`

## Vistas de reporte propias

Las crean las migraciones de este modulo. Los reportes SIEMPRE
consultan estas vistas; jamas se guarda una copia desnormalizada.

- `v_historial_cliente`
- `v_embudo_por_responsable`

## Servicios (implementados)

- **ServicioResolverPrecio** - lista del cliente -> lista predeterminada -> precio del producto
- **ServicioCotizacion** - enviar, aceptar/rechazar, convertir en pedido
- **ServicioPedido** - confirmar (aparta existencia) y surtir (la saca del almacen)
- **ServicioFactura** - crea desde pedido, emite y contabiliza
- **ServicioCobro** - aplica cobros totales o parciales
- **ServicioNotaCredito** - acredita y, si el motivo es devolucion, regresa mercancia

> Ventas NO escribe en `movimientos_inventario`: se lo pide a los servicios de
> Inventario, que son sus duenos. Ver `docs/IMPLEMENTACION_ERP.md` D-B para el
> orden en que se liberan apartados al surtir.

## Estructura

```
Ventas/
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
