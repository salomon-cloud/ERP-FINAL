# Modulo Compartido (Cimientos)

> Infraestructura, no un modulo de negocio. Todo aquello de lo que cualquier otro modulo tiene permitido depender.

| | |
|---|---|
| **Namespace** | `App\Modules\Compartido` |
| **Prefijo de URL** | `/ (raiz de la aplicacion, sin prefijo)` |
| **Nombres de ruta** | `compartido.*` (API: `api.compartido.*`) |
| **Namespace de vistas** | `compartido::` |
| **Depende de** | - |

## Tablas propias

Este modulo es el **unico** propietario de estas tablas. Cualquier otro modulo
que necesite leerlas lo hace a traves de sus modelos y servicios, nunca con SQL
directo ni con un modelo duplicado.

- `organizaciones`
- `users (tabla v1 extendida)`
- `roles`
- `privilegios`
- `rol_privilegios`
- `usuario_roles`
- `bitacora_auditoria`
- `notificaciones`
- `configuraciones`
- `catalogos`
- `adjuntos`
- `etiquetas`
- `etiquetables`
- `favoritos`
- `comentarios`
- `secuencias_documento`
- `lotes_importacion`
- `reportes_guardados`

## Servicios esperados

- **ServicioFolios** - la unica forma de generar un folio de documento
- **ServicioAdjuntos** - carga y descarga con lista blanca de MIME y tamano
- **ServicioNotificaciones** - primero se persiste, luego se entrega
- **ServicioBusqueda** - busqueda global sobre proveedores por modulo
- **ServicioExportacion / ServicioImportacion** - CSV de salida y de entrada

## Estructura

```
Compartido/
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
