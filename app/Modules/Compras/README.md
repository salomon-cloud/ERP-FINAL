# Modulo Compras

> El catalogo de proveedores y la cadena requisicion -> orden -> recepcion -> factura -> pago.

| | |
|---|---|
| **Namespace** | `App\Modules\Compras` |
| **Prefijo de URL** | `/compras` |
| **Nombres de ruta** | `compras.*` (API: `api.compras.*`) |
| **Namespace de vistas** | `compras::` |
| **Depende de** | Compartido, Inventario (productos, almacenes), Finanzas (cuentas por pagar), RH (departamento solicitante) |

## Tablas propias

Este modulo es el **unico** propietario de estas tablas. Cualquier otro modulo
que necesite leerlas lo hace a traves de sus modelos y servicios, nunca con SQL
directo ni con un modelo duplicado.

- `proveedores`
- `requisiciones`
- `requisicion_lineas`
- `ordenes_compra`
- `orden_compra_lineas`
- `recepciones`
- `recepcion_lineas`
- `facturas_proveedor`
- `factura_proveedor_lineas`
- `devoluciones_compra`
- `devolucion_compra_lineas`
- `pagos`

## Servicios (implementados)

- **ServicioRequisicion** - enviar, aprobar/rechazar, convertir en orden
- **ServicioOrdenCompra** - confirmar con bloqueo optimista, enviar, cancelar
- **ServicioRecepcion** - aplica la entrada al inventario y actualiza lo recibido
- **ServicioFacturaProveedor** - cotejo de tres vias y contabilizacion de CxP
- **ServicioDevolucionCompra** - saca la mercancia devuelta del almacen
- **ServicioPago** - aplica pagos totales o parciales

> Compras NO escribe en `movimientos_inventario`: se lo pide a los servicios de
> Inventario. La contabilizacion pasa por el contrato `Contabilizador` de
> Compartido mientras Finanzas publica el suyo -- ver `docs/IMPLEMENTACION_ERP.md` D-C.

## Estructura

```
Compras/
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
