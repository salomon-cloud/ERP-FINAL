# Modulo Inventario

> El catalogo de productos y el libro de movimientos. La existencia nunca es una columna: se deriva de movimientos_inventario.

| | |
|---|---|
| **Namespace** | `App\Modules\Inventario` |
| **Prefijo de URL** | `/inventario` |
| **Nombres de ruta** | `inventario.*` (API: `api.inventario.*`) |
| **Namespace de vistas** | `inventario::` |
| **Depende de** | Compartido, Finanzas (impuestos) |

## Tablas propias

Este modulo es el **unico** propietario de estas tablas. Cualquier otro modulo
que necesite leerlas lo hace a traves de sus modelos y servicios, nunca con SQL
directo ni con un modelo duplicado.

- `almacenes`
- `ubicaciones`
- `categorias_producto`
- `unidades_medida`
- `productos`
- `codigos_barras`
- `lotes`
- `numeros_serie`
- `movimientos_inventario`
- `traspasos`
- `traspaso_lineas`
- `ajustes_inventario`
- `ajuste_lineas`
- `conteos_inventario`
- `conteo_lineas`
- `reglas_reorden`

## Vistas de reporte propias

Las crean las migraciones de este modulo. Los reportes SIEMPRE
consultan estas vistas; jamas se guarda una copia desnormalizada.

- `v_existencias`

## Servicios esperados

- **ServicioMovimientoInventario** - el unico que escribe en el libro de movimientos
- **ServicioApartarExistencia** - lo consume Ventas al confirmar un pedido
- **ServicioAjusteInventario / ServicioTraspaso / ServicioConteo**

## Estructura

```
Inventario/
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
