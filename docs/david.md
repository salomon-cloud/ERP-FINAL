# Especificación Maestra — ERP

Módulos: **VENTAS** · **COMPRAS** · **INVENTARIO**
Proyecto: **SISEN ERP** (Laravel 12 · PHP 8.2 · MariaDB · Blade · Bootstrap 5.3)

> Este documento es el *blueprint* único de implementación/rediseño de los módulos
> Ventas, Compras e Inventario. Un agente debe poder leerlo y construir los tres
> módulos sin necesidad de re-descubrir el sistema. **NO se ha implementado nada**:
> este documento es únicamente análisis y especificación.

---

## 1. Resumen ejecutivo

El proyecto **SISEN** es un ERP monolítico a base de módulos sobre Laravel 12. La
infraestructura (`Compartido`) y el módulo **RH** están **completamente implementados**
(controladores, modelos, enums, requests, servicios, observers, vistas, reportes, CSV,
menú y pruebas) y sirven como **patrón de referencia obligatorio**.

Los módulos **Ventas**, **Compras** e **Inventario** están en estado **ESQUELETO**: sus
esquemas de base de datos **ya están migrados y probados** (con CHECK constraints, índices,
auditoría, folios y vistas de reporte), sus privilegios ya están sembrados en la DB, y solo
publican la página de placeholder `ventas.dashboard` / `compras.dashboard` /
`inventario.dashboard`. **No existe ningún modelo, enum, service, controller, request ni
vista para estos tres módulos en ninguna rama del repositorio.**

El trabajo real, por lo tanto, **NO es diseñar tablas** (ya existen y no deben tocarse):
es **implementar el código de aplicación** (lógica, servicios, flujos, pantallas) sobre un
esquema fijo, respetando las reglas de integridad que la DB ya impone (CHECK, FKs, soft
deletes, folios, auditoría) y las convenciones que RH y Compartido ya establecieron.

La prioridad **no es agregar CRUDs** sino construir el **flujo ERP completo**:
Cotización → Pedido → Factura → Cobro (con salida de inventario), y
Requisición → Orden → Recepción → Factura proveedor → Pago (con entrada de inventario),
todo con el libro `movimientos_inventario` como única fuente de verdad de existencia.

---

## 2. Auditoría del sistema existente

### 2.1 Stack y versiones

| Capa | Tecnología | Detalle |
|---|---|---|
| Framework | Laravel **12** | `composer.json`, PHP ^8.2 |
| BD | **MariaDB** (solo MariaDB) | `DB_CONNECTION=mariadb`, DB `sisen` |
| Frontend | **Bootstrap 5.3.3** (CDN) + **Bootstrap Icons** | Layout `resources/views/layouts/app.blade.php` |
| CSS propio | `public/css/sisen.css` | design tokens, badges, sidebar, print |
| JS propio | `public/js/sisen.js` | vanilla JS (sidebar, confirm, calculos) |
| Vite/Tailwind | En `package.json` | Configurados pero **NO usados** en vistas |
| Tests | PHPUnit 11 | `tests/Feature/*` |
| Formato | Laravel Pint | `./vendor/bin/pint` |
| Camada de vistas | Blade + componentes `x-*` | `x-page-header`, `x-card`, `x-table`, ... |

### 2.2 Arquitectura de módulos (autodescubierta)

- `app/Providers/ModuleServiceProvider.php` escanea `app/Modules/*` y por cada carpeta:
  registra vistas (`<slug>::`), migraciones, rutas (`/web` bajo prefijo `<slug>` y nombres
  `<slug>.*`; API bajo `/api/<slug>` y `api.<slug>.*`) y el enlace de tablero en el menú.
- **Las rutas web NO incluyen `web`/`auth` repetidos**: el provider ya los aplica al grupo.
- `Modulos` existentes (orden de migración 100→700):
  1. **Compartido** (raíz `/`, cimientos / infraestructura)
  2. **Finanzas** (`/finanzas`) — dueño de impuestos, periodos fiscales, cuentas, polizas, bancos
  3. **Inventario** (`/inventario`) — dueño de productos, almacenes y movimientos
  4. **RH** (`/rh`) — implementado completo
  5. **Ventas** (`/ventas`) — esqueleto
  6. **Compras** (`/compras`) — esqueleto
  7. **CRM** (`/crm`) — esqueleto
- Dependencias: `Compras > RH (departamentos), Finanzas (CxP), Inventario (productos)`;
  `Ventas > Inventario (productos), Finanzas (CxP/ventas), CRM (lee clientes)`;
  `Inventario > Finanzas (impuestos)`.

### 2.3 Autorización (roles y privilegios)

- Modelo normalizado: `roles`, `privilegios`, `rol_privilegios`, `usuario_roles`
  (migración `100300`). Códigos de privilegio: `<modulo>.<entidad>.<accion>`.
- Compatibilidad v1: columna `users.role` se conserva y `User::hasAnyRole()` la respeta
  (rutas v1 de `routes/web.php` siguen funcionando).
- Middleware: `permission:<privilegio>[,<privilegio> ...]` (ANY) y `role:<Rol1,Rol2>` (ANY,
  para v1). `PermissionMiddleware` además exige `estado === 'activo'`.
- `User::tieneAlgunPrivilegio()` — Administrador pasa siempre. Los codigos se memorizan por request.
- Roles sembrados (`CimientosSeeder > RolPrivilegioSeeder`): `Administrador` (`*`),
  `Recursos Humanos`, `Contador`, `Ventas`, `Compras`, `Almacenista`, `Empleado`.
- `RolPrivilegioSeeder` **ya siembra los privilegios de los tres módulos**; la
  implementación **no debe crearlos de nuevo** (ver §20).

### 2.4 Folios y auditoría (infraestructura)

- **`ServicioFolios`** (Compartido): única fuente de números visibles; `siguiente('modulo')`
  bloquea la fila en transacción; los folios cancelados **no** se reutilizan. Secuencias
  sembradas: `cotizaciones COT-`, `pedidos PED-`, `facturas FAC-`, `notas_credito NC-`,
  `cobros COB-`, `requisiciones REQ-`, `ordenes_compra OC-`, `recepciones REC-`,
  `facturas_proveedor FP-`, `devoluciones_compra DEV-`, `pagos PAG-`, `traspasos TRA-`,
  `ajustes_inventario AJU-`, `conteos_inventario CON-`.
- **`TieneBitacora`** (trait): alta/baja/update automático + `registrarBitacora(accion)`
  para acciones semánticas (`contabilizada`, `aprobada`, ...).
- **`TieneCamposAuditoria`** (trait): llena `creado_por`/`actualizado_por`.
- **`EsquemaErp`** (support): `dinero(18,2)`, `cantidad(18,6)`, `auditoria()` (timestamps +
  softDeletes + autores), `versionFila()`, `unicoActivo()`, `check()`, `crearVista()`,
  `llaveForaneaDiferida()`.
- **`RegistroMenu`**: registro del menú lateral; cada módulo llama
  `RegistroMenu::registrar('Ventas', [...])` desde su `Routes/web.php` (las rutas que no
  existen o el usuario no puede ver **no** se pintan).

### 2 espacios y servicios transversales

- `catalogos` (grupos: condiciones_pago, forma_pago, moneda, tipo_contrato,
  motivo_descuento ya sembrados).
- `configuraciones` (grupo.clave, con default en `config/sisen.php`).
- `bitacora`, `notificaciones`, `adjuntos`, `etiquetas/etiquetables`, `favoritos`,
  `comentarios`, `lotes_importacion`, `reportes_guardados`.
- Plantilla de reportes y exportación CSV: patrón de RH `ExportadorCsv::descargar(...)`
  con BOM UTF-8 y `?formato=csv`.

### 2.5 Base de datos actual (verificada en MariaDB)

- BD `sisen`: **todas las tablas de los 7 módulos migradas** (≈130 tablas + 15 vistas `v_*`).
- **Sin datos de negocio**: `productos=0`, `clientes=0`, `proveedores=0`,
  `pedidos=0`, `ordenes_compra=0`, `movimientos_inventario=0`. Solo seed de cimientos
  + 4 usuarios demo + datos RH demo (departamentos, empleados, nominas, asistencias).
- Seeder base `CimientosSeeder` (roles, catálogos, secuencias) ejecutado.

---

## 3. Arquitectura actual

### 3.1 Rutas (solo placeholder en los 3 módulos)

Cada módulo tiene un `Routes/web.php` que apunta a `TableroModuloController`:

```php
Route::get('/', [TableroModuloController::class, '__invoke'])->name('dashboard');
```

El doc-block de cada `Routes/web.php` documenta el patrón esperado (recursos + `permission:`).

### 3.2 Estructura de carpetas a completar (ya existe el esqueleto)

```
app/Modules/{Ventas|Compras|Inventario}/
├── Controllers/        # vacíos (.gitkeep)
├── Controllers/Api/    # vacíos
├── Requests/           # vacíos
├── Services/           # vacíos
├── Models/             # vacíos
├── Enums/              # vacíos
├── Observers/          # vacíos
├── Utils/              # vacíos
├── Contracts/          # vacíos
├── Routes/web.php · api.php   # solo dashboard + placeholder
├── Views/{catalogos,paginas,components,partials,dashboard}/  # solo .gitkeep
└── Migrations/         # MIGRACIONES COMPLETAS (no tocar)
```

### 3.3 Modelos existentes reutilizables

- `app/Models/User.php` (auth). Ya incluye roles/privilegios.
- Solo los modelos de Compartido y RH existen. **No hay modelos de Ventas/Compras/Inventario**.

---

## 4. Inventario funcional

### 4.1 Funcionalidad encontrada (tabla maestra)

| Módulo | Funcionalidad | Existe | Ubicación | Estado | Observaciones |
|---|---|---|---|---|---|
| Compartido | Roles/privilegios | Sí | migraciones 100300 + seeder RR | EXISTENTE Y FUNCIONAL | No duplicar |
| Compartido | Catálogos (condiciones, forma_pago, moneda) | Sí | catalogos + seeder | EXISTENTE Y FUNCIONAL | Grupos sembrados |
| Compartido | Configuraciones | Sí | configuraciones + config/sisen.php | EXISTENTE Y FUNCIONAL | Lectura vía config |
| Compartido | Folios | Sí | secuencias_documento + ServicioFolios | EXISTENTE Y FUNCIONAL | No reusar folios |
| Compartido | Bitácora + auditor container | Sí | bitacora_auditoria + traits | EXISTENTE Y FUNCIONAL | Usar traits |
| Compartido | Notificaciones | Sí (tabla) | notificaciones | EXISTENTE PERO INCOMPLETO | Falta ServicioNotificaciones y UI de campana |
| Compartido | Adjuntos | Sí (tabla + config) | adjuntos | NO IMPLEMENTADO | Falta ServicioAdjuntos y componente x-attachments |
| Compartido | Búsqueda global / export/import | No | — | NO EXISTE | Falta ServicioBusqueda/Exportacion/Importacion |
| Finanzas | Impuestos (IVA, retenciones) | Sí | impuestos (+seeder | EXISTENTE Y FUNCIONAL | productos.impuesto_id la usa |
| Finanzas | Periodos fiscales | Sí | periodos_fiscales | EXISTENTE Y FUNCIONAL | facturas.periodo_fiscal_id |
| Finanzas | Cuentas bancarias | Sí | cuentas_bancarias | EXISTENTE Y FUNCIONAL | cobros/pagos.cuenta_bancaria_id |
| Finanzas | Polizas | Sí | polizas/poliza_lineas | EXISTENTE PERO INCOMPLETO | Falta ServicioContabilizarPoliza; Ventas/Compras emiten origen 'factura' etc. |
| Inventario | Catálogo productos | Sí (esquema) | productos... | EXISTENTE PERO SIN UI/LOGICA | Solo tablas |
| Inventario | Almacenes/ubicaciones | Sí (esquema) | almacenes/ubicaciones | Ídem | Solo tablas |
| Inventario | Unidades de medida | Sí (esquema) | unidades_medida | Ídem | Conversión por factor_base |
| Inventario | Categorías jerárquicas | Sí (esquema) | categorias_producto | Ídem | |
| Ventas | Clientes | Sí (esquema) | clientes/listas_precios | EXISTENTE PERO INCOMPLETO | Sin modelo/vistas |
| Ventas | Cotizaciones→Pedidos→Facturas→Cobros | Sí (esquema) | cotizaciones...cobros | EXISTENTE PERO INCOMPLETO | Sin modelos/servicios/vistas |
| Ventas | Notas de crédito | Sí (esquema) | notas_credito | Ídem | |
| Compras | Proveedores/requisiciones/OC/Recepciones | Sí (esquema) | [compras tablas] | Ídem | |
| Compras | Facturas proveedor/devoluciones/pagos | Sí (esquema) | Ídem | Ídem | |
| RH | Módulo completo (referencia) | Sí | app/Modules/RH/* | EXISTENTE Y FUNCIONAL | Patrón a copiar |
| V1 (app/Http/Controllers) | RH v1 (legacy) | Sí | rutas web.php v1 | EXISTENTE Y FUNCIONAL | No tocar; co-exist con módulos |

> **Regla:** no crear ninguna tabla duplicada ni módulo paralelo; la BD ya es dueña del esquema.

**No existe en ninguna parte** (debe construirse desde cero con código de app):
modelos/enums/requests/services/observers/views de Inventario, Ventas y Compras; tableros
propios (TableroController) de esos módulos; reportes (con sus vistas `v_*` ya creadas por
los módulos); comandos de reorden (`reorder:check`); UI de notificaciones; pantallas de
catálogos.

---

## 5. Problemas detectados (del sistema actual)

| # | Problema | Módulo | Impacto | Riesgo | Solución propuesta |
|---|---|---|---|---|---|
| P1 | Falta todo el código de app de Ventas/Compras/Inventario | Los 3 | No se pueden operar | Alto (depende de toda la implementación) | Implementar siguiendo RH (fase planen) |
| P2 | PLANNING.md está bilingüe / con nombres EN vs ES | Global | Confusión de servicios (InvoiceService vs ServicioFactura) | El ruma: imports/namespaces | **Mandar las convenciones reales del código** (Español; READMEs definen nombres de Servicios) |
| P3 | `ServicioContabilizarPoliza` (Finanzas) no existe | Finanzas↔Ventas/Compras | Ventas/Compras dependen de él para contabilizar | Obstructor de FASE 3/4 | Documentar como dependencia; Ventas/Compras usan Finanzas via su contrato (a implementar en finanzas) |
| P4 | No hay notificación/adjuntos funcionales | Compartido | stock_bajo, avisos de vencimientos no son emitibles | Alto (depende de inventario) | Implementar `ServicioNotificaciones` y UI de campana (Fuera del alcance, documentado) |
| P5 | Menú de módulos con única entrada (dashboard) sin privilegio auto | ModuleServiceProvider | Enlace visible para usuarios sin permiso → 403 | Bajo | Documentado en el Provider (RH); mitigar con tablero propio que requiere privilegio |
| P6 | `routes:cache` pierde las entradas de `RegistroMenu` registradas en `Routes/web.php` | Todos los módulos | Menú se pierde en cache | Bajo | No documentar como fix; usan `ServiceProvider` de módulo (PENDIENTE) |
| P7 | `v_existencias` no incluye existencia en ubicaciones `es_surtible=false`; no separa apartado/reservado | Inventario | dependencia de stock disponible | No tiene stock reservado distinto | Ver §17: agregar concepto `apartado` en movimiento; suma de movimientos `apartado`/`liberacion_apartado` |
| P8 | `movimientos_inventario` no guarda referencia de almacén/comprobante con NK (OK: origen_tipo/origen_id) | Inventario | Los reportes deben corresponder | OK como está; igual documentado |
| P9 | Sin datos demo para los 3 módulos | Los 3 | Anotación no usa | Pruebas manuales pérdidos | Agregar seeder de datos demo (en fase pruebas, NO en Cimientos) |
| P10 | `routes/console.php` vacío (sin scheduled reorder) | Inventario | Reorden automático no se ejecuta | Medio | Agregar comando `inventario:reorden` + cron (`schedule:run`) |

---

## 6. Objetivos del módulo ERP

1. Que **Ventas, Compras e Inventario operen como un solo ERP**, no sistemas aislados:
   - Venta confirmada → reserva existencias → salida real → movimiento → kardex.
   - Recepción de compra → entrada → existencia actualizada → kardex (y costo).
   - Devolución/cancelación → movimiento inverso controlado + ajuste financiero.
   - Traspaso/ajuste/conteo → registrado y auditable.
2. Que **toda operación se rastreable**: quién, cuándo, qué, antes/después, motivo, documento.
3. Que la **existencia nunca sea un campo**: se deriva de `movimientos_inventario` (vías `v_existencias`).
4. Que la UI sea **de nivel comercial**: coherente, rápida, con pocos clics y estados claros.
5. Que se **reutilice al máximo** la arquitectura existente (traits, componentes, middlewares, folios).
6. Que no se **rompa nada** de v1 (RH y rutas v1 deben seguir).
7. Que el resultado sea un **blueprint completo** (este documento) para su implementación.

---

## 7. Arquitectura funcional propuesta

### 7.1 Flujos maestros

```
COMPRAS
 PROVEEDOR
   ↓
 REQUISICIÓN (solicitud interna)   [opcional si hay stock bajo]
   ↓ aprobación
 COTIZACIÓN_PROVEEDOR (comparativo) [si aplica - ver sect. 10.3]
   ↓
 ORDEN DE COMPRA   (OC-000001)
   ↓ confirmar/enviar
 RECEPCIÓN (REC-…) → verificaciones, lote/caducidad, ubicación
   ↓ aplicar
 ENTRADA movimiento 'compra'  →  INVENTARIO / existencias / v_existencias
   ↓
 FACTURA PROVEEdor (FP-…) → CxP en Finanzas (cotejo 3 vías)
   ↓
 PAGO (PAG-…) → aplicado
```

```
VENTAS
 CLIENTE
   ↓
 COTIZACIÓN (COT-…)   BORRADOR→ENVIADA→ACEPTADA/VENCIDA/…→CONVERTIDA
   ↓ convertir
 PEDIDO (PED-…)  BORRADOR→CONFIRMADO (aparta existencias)→SURTIDO→FACTURADO
   ↓ surtido → SALIDA tipo 'venta'  →  movimientos_inventario
   ↓
 FACTURA (FAC-…) → BORRADOR→EMITIDA (contabiliza en Finanzas) → cobrada parcial/total
   ↓
 COBRO (COB-…) aplicado (pagares parciales)
   ↓ (opcional) NOTA DE CRÉDITO → devolución → entrada 'devolucion_entrada' + NC
```

```
INVENTARIO (otencia)
 stock bajo (v_existencias vs reglas_reorden)
   ↓ (comando inventario:reorden → notificaciones)
 REQUISICIÓN → (aprobar) → OC → Recepción → INVENTARIO
```

### 7.2 Regla de oro

> **Todo cambio de existencia ES un `movimientos_inventario` con signo**. Nunca se inserta
> ni se edita una columna " existencias". Cancelar un movimiento = crear el movimiento contrario
> (tipo `*_salida`/`*_entrada` inverso, estado se aplica con su `cancelado`? — ver rule:
> el campo `estado` del movimiento pasa a `cancelado` y se genera un `movimiento_contrario`
> — **decidir** ver §21.8). Un movimiento cancelado se marca `cancelado`, NO se borra.

### 7.3 Módulos que escriben en movimientos (caja blanca)

| Documento | Entrada | Movimientos que genera |
|---|---|---|
| Recepción (Compras) | `ServicioRecepcion` | `compra` (+) por línea, `origen_tipo=recepciones` |
| Pedido surtido (Ventas) | `ServicioSurtirPedido` | `venta` (−, from apartado), `liberacion_apartado` si sobra |
| Nota de crédito (Ventas) | Servicio NC | `devolucion_entrada` (+), si motivo `devolucion` |
| Devolución de compra (Compras) | ServicioDevolucion | `devolucion_salida` (−) |
| Traspaso | `ServicioTraspaso` | `traspaso_salida` (−, origen) + `traspaso_entrada` (+, destino) |
| Orden de compra confirmada | `ServicioApartarExistencia` | `apartado` (−, reserva) y `liberacion_apartado` si cobra/se libera |
| Ajuste | `ServicioAjusteInventario` | `ajuste_entrada` / `ajuste_salida` |
| Conteo | ServicioConteo | `conteo` (cada línea; signo = differencia) |
| Carga inicial | `inicial` | única vez, via comando/seeder |

---

## 8. Navegación propuesta

La barra **no** debe crecer sin control. El menú de cada módulo concentra pantallas
relacionadas bajo desplegables (patrón ya implementado por RH en `layout`).

### 8.1 Menú propuesto

**Ventas** (ordenes 25.x):
```
Ventas
├── Tablero        (ventas.dashboard)
├── Clientes       (ventas.clientes.index)
├── Listas de precios (ventas.listas-precios.index)
├── Cotizaciones   (ventas.cotizaciones.index)
├── Pedidos        (ventas.pedidos.index)
├── Facturas       (ventas.facturas.index)
├── Notas de crédito (ventas.notas-credito.index)
└── Reportes       (ventas.reportes.index)
```

**Compras** (30.x):
```
Compras
  ├── Tablero        (compras.dashboard)
  ├── Proveedores    (compras.proveedores.index)
  ├── Requisiciones  (compras.requisiciones.index)
  ├── Órdenes de compra (compras.ordenes.index)
  ├── Recepciones    (compras.recepciones.index)
  ├── Facturas de proveedor (compras.facturas.index)
  ├── Devoluciones   (compras.devoluciones.index)
  └── Reportes       (compras.reportes.index)
```

**Inventario** (orden 60→70):
```
Inventario
  ├── Tablero        (inventario.dashboard)
  ├── Productos      (inventario.productos.index)  ← con filtros: Todos/Medicamentos/Insumos/Papelería (por categoría/tipo)
  ├── Almacenes      (inventario.almacenes.index)
  ├── Ubicaciones    (dentro de Almacén / inventario.ubicaciones.index)
  ├── Unidades       (dentro de Catálogos: inventario.unidades.index + inventario.categorias.index)
  ├── Categorías     "
  ├── Existencias    (inventario.existencias.index)  ← pregunta a v_existencias, filtrar, acciones)
  ├── Movimientos (kardex) (inventario.movimientos.index)
  ├── Traspasos      (inventario.traspasos.index)
  ├── Ajustes        (inventario.ajustes.index)
  ├── Conteos        (inventario.conteos.index)
  └── Reportes       (inventario.reportes.index)
```

> Para **productos de distintos tipos** (medicamentos, insumos, papelería):
> un solo listado con **pestañas/filtro** (Todos / Medicamentos / Insumos / Papelería /
> Repuestos...) basado en **categorías** (`categorias_producto`), NO módulos independientes.
> Los atributos específicos (lote, caducidad, presentación...) se agregan como campos
> opcionales en pantalla según la categoría — ver §11.

**Registro del menú** (en `Routes/web.php` de cada módulo):

```php
RegistroMenu::registrar('Ventas', [
    ['etiqueta' => 'Tablero', 'icono' => 'speedometer2', 'ruta' => 'ventas.dashboard', 'privilegio' => 'ventas.pedidos.ver', 'orden' => 40.01],
    ['etiqueta' => 'Clientes', 'icono' => 'people', 'ruta' => 'ventas.categorias.clients.index', 'privilegio' => 'ventas.clientes.ver', 'orden' => 40.02],
    ...
]);
```

---

## 9. Ventas (diseño)

### 9.1 Clientes

- Pantalla: catálogo CRUD + vista de fila con **historial 360°** (vía `v_historial_cliente`).
- Columnas tabla: Código, Nombre, RFC, Teléfono, Moneda, Condición de pago, Límite de
  crédito, Saldo pendiente (calculado), Estado, Acciones.
- Filtros: buscar (nombre/código/RFC), estado, condición de pago.
- Datos: datos fiscales (razón_social, RFC opcional — México), contacto (correo, teléfono,
  dirección), `limite_credito`, `condicion_pago_id` (catálogo), `lista_precio_id`,
  `moneda`, `estado`.
- Regla: cliente con facturas/ventas **no se elimina físicamente** (soft-delete vía
  `unicoActivo` permite reutilizar el código tras borrado). Saldo se deriva de facturas/cobros.
- **Listas de precios**: `listas_precios` + `lista_precio_items` (por producto y
   cantidad mínima). Resolución de precio: cliente→lista → lista predeterminada →
   precio del producto (`ServicioResolverPrecio`). Un clic "Nueva venta" desde ficha del
   cliente preselecciona cliente.

### 9.2 Cotizaciones

- Flujo: **BORRADOR → ENVIADA → ACEPTADA → CONVERTIDA** (o **RECHAZADA / CANCELADA** /
  **VENCIDA** que se calcula por `vigencia`).
- Pantallas: listado (folio el que devuelve; buscar; estados), crear, ver (markdown detalles),
  acciones propias: **Enviar**, **Aceptar / Rechazar / Convertir a pedido**.
- La conversión copia líneas y totales (fotos) a un **pedido** BORRADOR. La cotización
  `convertida`.
- Resolución de precios automática al elegir producto (precio de línea se congela al crear línea).
- Vigencia: fecha `vigencia`; vencida = `vigencia < hoy` y aún `enviada`.

### 9.3 Pedidos (venta confirmada)

- Estados: **BORRADOR → CONFIRMADO → SURTIDO → FACTURADO_PARCIAL → FACTURADO → CANCELADO**.
- Crear: desde con carrito de líneas dinámico (producto + búsqueda SKU/barras, cantidad,
   almacén), recálculo automático (subtotal − descuento ± impuesto), selección cliente,
   lista de precios, fecha entrega.
- **Confirmar** (privilegio `ventas.pedidos.confirmar`): llama `ServicioApartarExistencia`
  → movimientos `apartado` por línea (reserva). Errores (insuficiente) → mensaje claro y
  las líneas que faltan marcadas.
- **Surtir** (o el surtido desde un listado de existencias): genera salidas `venta` y pasa
  `cantidad_surtida`; cuando `cantidad_surtida ≥ cantidad` (todas) → SURTIDO. La liberación
  de apartado: el movimiento `apartado` se libera (`liberacion_apartado`) en la proporción surtida.
- Elegante: confirm/cancelar solo en los estados correspondientes; cancelar devu proceso
  opuesto (liberar apartado solo, o movimiento inverso `liberacion_apartado` si se cancel la
  parte no surtida).
- Inventario **negativo prohibido** (`config('sisen.inventory.negative_stock_allowed')`=false):
  al surtir se validará contra existencias.

### 9.4 Facturas

- Estados: **BORRADOR → EMITIDA → COBRADA_PARCIAL → COBRADA | VENCIDA | CANCELADA**.
  `vencida` es **derivada** (emitidai fecha_vencimiento < hoy) no estatal.
- Crear a partir de pedido (1 pedido → 1+ facturas) o a la carta.
- **Emitir** (privilegio `ventas.facturas.emitir`): `ServicioEmitirFactura` —
  valida, genera la póliza de ingreso (Finanzas) con `origen_tipo='factura'`, marco SDI
  fill tipo_fiscal (timbrado CFDI = integración futura, `facturas_electronicas` preparado).
- total_cobrado se actualiza instancia por cobro; estado de factura se recalculaya automáticamente.

### 9.5 Notas de crédito (devoluciones)

- Estados: **BORRADOR → EMITIDA → CANCELADA**; motivo (`devolucion|descuento|error|otro`).
- **Emitir**: si motivo `devolucion`, genera la entrada de inventario `devolucion_entrada`
  para los productos/cantidades de la nota (con su costo). Contabiliza la NC en Finanzas
  (ingreso_contra + impuesto + CxC). Líneas referencian `factura_linea_id`.
- Regla: NC total por factura **no** puede exceder lo facturado; cantidad devuelta ≤ cantidad facturada.
- Cancelación de NC emitida → si se trata de devolución, genera `devolucion_salida` (inventario
  de vuelta podría NO ser posible solo). Ruta del estado validada en Servicio.

### 9.6 Cobros

- Estados: **BORRADOR → APLICADO → CANCELADO**; método (efectivo/transferencia/cheque/tarjeta/liga_pago).
- Aplicar (`ventas.cobros.aplicar`): `ServicioAplicarCobro` — asocia a 1..N facturas,
  total_cobrado += monto (con saldo restante recalculado) y contabiliza (Finanzas,
  `origen_tipo='cobro'`); pagos parciales permitidos. Cobro a cuenta (`factura_id` NULL)
  aplicable después (decisión: se implementa el flujo simple primero, ver §9.8).
- Prevención: cobro ya aplicado no se edita; cancelar un cobro genera la póliza reversa.

### 9.7 Cancelaciones (política general de Ventas)

| Documento | ¿Quién? | ¿Cuándo? | √ inverso inventario | √ inverso pagos | Auditoría |
|---|---|---|---|---|---|
| Pedido | `ventas.pedidos.cancelar` | solo BORRADOR/CONFIRMADO (si confirmado: liberar apartados; si quedó surtido, genera `devolucion_salida` por lo surtido) | Sha | No hay | durante acción |
| Factura | `ventas.facturas.cancelar` | solo BORRADOR/EMITIDA; si hay cobros → primero cancelarlos; si NC asociada → primero cancel NC | Ver NC | se exige | bloqueo |
| Nota crédito | `ventas.notas_credito.eliminar`? (ver §20) | solo BORRADOR | se revierte devolucion_entrada con devolucion_salida | — | |
| Cobro | `ventas.cobros.aplicar` inverse | solo BORRADOR | — | reversa la póliza | |

Cancelaciones: **nunca suprimir filas**; se cambia `estado` y se registran las transacciones
financieras/inventario oponentes. `version_fila` bloquea ediciones concurrentes.

---

## 10. Compras (diseño)

### 10.1 Proveedores (espejo de clientes)

- Igual que clientes pero con `contacto`, `condicion_pago`, `moneda`, `estado`.
- Filtros/búsqueda análogos. No editar ni borrar si ya tiene OC/recepciones/facturas —
  restriccion por FKs `restrictOnDelete`.

### 9.2 Requisiciones (solicitud de compra)

- Origen: solicitud interna (usuario solicitante, `departamento_id` opcional — apunta a RH).
- Estados: **BORRADOR → ENVIADA → APROBADA | RECHAZADA → CONVERTIDA | CERRADA**.
- **Enviar** (deudor), **Aprobar/Rechazar** requiere `compras.requisiciones.aprobar`;
  aprobar fija auditoría y avisos.
- Convertir a `orden de compra` (1:N requisiciones → 1 OC posible; 1-X líneas por OC).
  La requisición pasa a `convertido`.
- Cuando la OC se recibe/completa → requisición `cerrada` (opcional; la conversión del pedido
  indica cerrada automático).

### 10.3 Cotizaciones de proveedores / comparativo

- **DECISIÓN**: el esquema actual no tiene tabla de comparativo proveedor. Para el
  contexto: no agregar una tabla nueva a menos que sea estrictamente necesario.
  Alternativa simple: en la OC se eligen productos y unidades a precio tecleado; la
  comparación puede hacerse con **múltiples OC (una por proveedor)** + vista de cotización
  no almacenada o plan de exportación. **(DECISIÓN PENDIENTE** — ver §34). Mientras la
  afirmaré: no crear tabla `cotizaciones_proveedor`; el flujo real de este negocio usa
  "orden de compra directa" y las requisiquietas sirven de intermediario. Se documenta.

### 10.4 Orden de compra

- Estados: **BORRADOR → ENVIADA → CONFIRMADA → RECIBIDA_PARCIAL → RECIBIDA → FACTURADA → CANCELADA**.
  (El esquema no tiene `aprobada` aparte de `requisiciones`; para OC el estado `enviada`/
  `confirmada` cumple; se debe **confirmar** en el sentido de aprobación interna universal.
  **Nota conflicto con maquina**: CA CHECK permite `borrador, sent, confirmada, recibida,
  recibida_parcial, facturada, cancelada`. Usar estos valores exactos.)
- Flujo: crear (proveedor, fecha, líneas con auditoría y envío y costo unit (snap de costo)),
  totales automáticos; **confirmar** (internal approval) → **enviar** (estado `enviada`);
  las **recepciones** alimentan `cantidad_recibida` en líneas → `recibida_parcial` / `recibida`;
  **cancelar** solo antes de recepción.
- `version_fila` + `where (version_fila, $v)` en confirmar/cancelar para evitar deber concurrentes.

### 10.5 Recepción (flujo crítico)

- Crear HS. Dirección: `recepciones` guarda cabecera (OC, almacén, fecha, `recibido_por`),
  y `recepcion_lineas` **lanza** lo que se va a recibir (a partir de la OC con
  `cantidad - cantidad_recibida` pendiente; sobre recib JID seguir).
- En recepción: validar cantidades, **aceptar/rechazar** parcialmente (línea hasta
  `cantidad - cantidad_recibida`); capturar **lote** y **fecha de caducidad** en productos
  que lo requieran (`rastrea_serie`/lote por categoría); seleccionar **ubicación**.
- **Aplicar** (`compras.recepciones.aplicar`): `ServicioRecepcion` en DB::transaction:
  1. por cada línea generar movimiento `compra` (+) con `costo_unitario` (foto de OC) y
     `lote_id`, `ubicacion_id`;
  2. actualizar `cantidad_recibida` en la línea de OC;
  3. recalcular estado de la OC (recibida_parcial/recibida);
  4. auditoría + notificación caso `cantidad` difiere.
- **Sobrenar no permiudia** (> tolerancia de `config('sisen.inventory.over_receipt_tolerance')` = 0)
  → rechaza la petición en servicio.
- Recepción parcial: NATURAL por líneas (cada línea su día).

### 10.6 Factura de proveedor (cotejo 3 vías) — Dependencia Finanzas

- Estados: **BORRADOR → CONTABILIZADA → PAGADA_PARCIAL → PAGADA | CANCELADA**.
- `ServicioFacturaProveedor`: **cotejo de 3 vías** OC ↔ Recepción ↔ Factura (cantidades
  por línea, tolerancia) y luego contabiliza póliza de CxP + gasto + IVA acreditable
  (`origen_tipo='factura_proveedor'`) vía Finanzas.
- **Requiere ServicioContabilizarPoliza de Finanzas (no implementado)** → considerar el
  lote de dependencias §33 y el plan de fases §32; para no bloquear, se habilita la
  contabilización "registrada" con estatus `contabilizada` y concepto de póliza `pendiente`
  por si Finanzas aún no está. Este decision se documentió para levantamiento de riesgo

### 10.7 Devolución a proveedor

- Motivos: `defectuoso|equivocado|excedente|otro`; estados BORRADOR→APLICADA→CANCELADA.
- Aplicar: genera movimiento `devolucion_salida` (−) de las líneas devueltas (con
  `lote_id` correspondiente) + NC/póliza financiera para el proveedor. Validar cantidad ≤
  cantidad facturada (o recibida).

### 10.6 Pagos a proveedores

- Estados: BORRADOR→APLICADO→CANCELADO; forma efectivo/transferencia/cheque; `cuenta_bancaria_id`.
- Aplicar: suma `total_pagado` a facturas (parcial permitido), póliza `origen_tipo='pago'`.

---

## 11. Inventario (diseño) — módulo central

### 11.1 Productos

- **SKU único activo** (`uq_productos_sku` vía `unicoActivo`), nombre, descripción,
  categoría, unidad (`unidades_medida` con `factor_base` — conversión p.ej. 1 caja = 12 pza),
  impuesto (ID `impuestos`), **costo y precio_venta** (fotos de línea en documentos),
  `stock_minimo`/`stock_maximo`, flags `es_vendible/es_comprable/es_inventariable`,
  `rastrea_serie` (perto si fácil), `estado`.
- **Códigos de barras**: multi (tabla `codigos_barras`, `es_principal`).
- **Lotes**: `lotes` (por producto, `numero_lote` + `fecha_caducidad`) — con productos
  farmacéuticos/alimentario el tema importa. (Está la tabla; activar dónde sea necesario.)
- **Series**: `numeros_serie` (por producto con `rastrea_serie`, estados en_stock/vendido/...).
- **Medios/insumos/papelería**: como **categorías**; un producto define
  `categoria_id` (jerárquica `padre_id`). El formulario mostrá/omite campos según categoría
  (de aspecto, se gestiona con el **mismo CRUD producto**, sin duplicar).

### 11.2 Almacenes, ubicaciones y stock

- `almacenes` (código único activo, `activo`); `ubicaciones` (única por almacen-código,
  `es_surtible`).
- Stock por `(producto, almacén, ubicación)` derivado en `v_existencias`.
- **Definición de existencias (derivadas):**
  · `existencia` = SUM(movimientos.cantidad) (estado='aplicado')
  · `reservado/apartado` = movimiento tipo `apartado` (aún no surtido) → mostrar "reservado"
  · `disponible` = existencia − reservado
  · `en_ruta` (opcional) = cantidad OC confirmada no recibida (sa a consultar vs `ordenese.compras`)
  · `mínimo/máximo` de `reglas_reorden` (si `< mínima` → alert)
- La UI de "Existencias" muestra: producto, sku, almacén, ubicación, existencia disponible,
  reservado, mínim/máximo, valorizado (existencia×costo). Además paginación y filtros.

### 11.3 Movimientos / Kardex (historial)

- `movimientos_inventario` = **libro mayor**. Pantalla: filtros (producto/almacén/tipo/fecha/
  ubic), columnas: fecha(apicado), tipo (badge), producto, almacén, ubicación, cantidad con
  signo, costo unitario, lote/sería, documento de origen (tipo 999+id), estado, usuario
  que aplica.
- **No existe columna entren.** `v_existencias` la deriva (overrides).

### 4 Transferencias (`traspasos`)

- Estados: `BORRADOR → EN_TRANSITO → RECIBIDO | CANCELADO`; aprobación opcional
  (`inventario.traspasos.aprobar`).
- Surtir (aplicar): valida existencias en origen → movimiento `traspaso_salida`; cuando se
  recibe en destino: `traspaso_entrada` (misma cantidad, `costo_unitario`). El sistema
  mantiene el mismo  `plant` local.
- `almacen_origen != almacen_destino` (CHECK DB).

### 5 Ajustes

- Estados: `BORRADOR → APLICADO | CANCELADO`; requiere `aprobar` (`inventario.ajustes.aprobar`).
- Línea: `diferencia` con signo (positivo sobra → `ajuste_entrada`; negativo falta →
  `ajuste_salida`), `motivo`, evidencia (adjunto—`adjuntos` polimórfico).
- **Nunca editar stock directamente**: ajuste es documento + movimiento.

### 6 Conteos físicos

- Números `CON-xxxxxx`. Estados: `borrador → en_proceso → contado → ajustado → cerrado`.
- Al iniciar: captura `cantidad_esperada` de `v_existencias` (almacén/ubicación); captura
  `cantidad_contada`; calcula `diferencia`. Al cerrar: genera `conteo` movimientos por
  línea con diferencias ≠ 0 (o genera ajustes con motivo "conteo"). Se puede cerrar solo
  el con permiso `inventario.conteos.cerrar`.

### 7 Alertas (nivel de negocio)

- Comando `inventario:reorden` (routes/console.php): compara `v_existencias` vs
  `reglas_reorden` por (producto, almacén) → **notificaciones** de stock bajo / sobre-stock
  y genera (opcional) `requisiciones` automáticas si se activa. Falta `ServicioNotificaciones`
  de Compartido(§P4) → pendiente cuando exista.
- "Próximos a caducar" y "caducados": reporte sobre `lotes.fecha_caducidad`. Los conteos
  y recepciones validan.

---

## 12. Integración entre módulos

| Operación | VENTAS | INVENTARIO | COMPRAS | FINANZAS |
|---|---|---|---|---|
| Confirmar pedido | valida cliente/y líneas | aparta existencias | | posición |
| Surtir pedido | pedido → surtido | sale `venta` | | costo |
| Emitir factura | factura emitenda | | | póliza ingreso+imp+CxC |
| Cobro | cobra | | | póliza caja |
| NC devolución | NC emitenda | entrada devolucion | | póliza NC |
| Requisición aprob | | stock alerta/apartado | genera requisición | |
| OC confirmada | | reserva visual | | |
| Recepción | | entrada `compra` + exist | OC recibida | |
| Factura provider | | | cotejo 3 vías | póliza CxP+gastos+IVA |
| Pago provider | | | pág | póliza banco |
| Ajuste/conteo/trans | |movimiento kardex | | (valorización) |

Todos los movimientos de inventario **nunca** se hacen desde fuera; se hacen a través de los
servicios dueños (Inventario) que otros módulos llaman (Ventas/Compras).

---

## 13. Clientes

Modelo ya definido (tabla `clientes`). Pantallas CRUD + ficha 360° (`v_historial_cliente`).
Datos: codigo, organizacion, nombre, razon_social, rfc, correo, telefono, direccion,
limite_credito, condicion_pago (catálogo), lista_precio, moneda, estado. Privilegios
`ventas.clientes.*`. Restricción borrado: conserve con historial (los FKs son RESTRICC).

---

## 14. Proveedores

Tabla `proveedores`: codigo, nombre, razon_social, rfc, contacto, correo, telefono,
direccion, condicion_pago, moneda, estado. Privileges `compras.proveedores.*`.

---

## 15. Productos

Tabla `productos` (dueño: Inventario). Campos: sku, nombre, descripcion, categoria,
unidad (con factor_base y `es_base` — permite conversión 1 caja = 12 pza), impuesto, costo,
precio_venta, min/max, es_vendable/es_comprable/es_inventable, rastrea_serie, estado.
Codigos_barras. Lotes y series. Para ser **unnegocio**:
- Categorías jerárquicas `categorias_producto`.
- Unidades de medida con `factor_base`: al capturar el documento se convierte **a la base**
  Sa sección de la cantidad de inventario se guarda **en unidad base** (`movimientos.cantidad`
  ya es DECIMAL(18,6) == base).

---

## 16. Almacenes

`almacenes` + `ubicaciones`. En existencia: revisión que producto X se quiera con ubicación.
Modelo físico mínimo: UM.

---

## 17. Estados y máquinas de estado

### 8.1 Estados ya definidos (CHECK en BD) — ÚNICA lista valida

| Tabla | Estado |
|---|---|
| cotizaciones | borrador → enviada → aceptada → rechazada → convertida → cancelada |
| pedidos | borrador → confirmado → surtido → facturado_parcial → facturado → cancelado |
| facturas | borrador → emitida → cobrada_parcial → cobrada → vencida → cancelada |
| notas_credito | borrador → emitida → cancelada |
| cobros | borrador → aplicado → cancelado |
| requisiciones | borrador → enviada → aprobada → rechazada → convertida → cerrada |
| ordenes_compra | borrador → enviada → confirmada → recibida → recibida_parcial → facturada → cancelada |
| recepciones | borrador → aplicada → cancelada |
| facturas_proveedor | borrador → contabilizada → pagada_parcial → pagada → cancelada |
| devoluciones_compra | borrador → aplicada → cancelada |
| pagos | borrador → aplicado → cancelado |
| traspasos | borrador → en_transito → recibido → cancelado |
| ajustes_inventario | borrador → aplicado → cancelado |
| conteos_inventario | borrador → en_proceso → contado → ajustado → cerrado |
| productos/clientes/proveedores | activo | inactivo |
| movimientos_inventario | aplicado | cancelado |
| numeros_serie | en_stock | vendido | garantia | devuelto | baja |
| lotes | activo | inactivo |

### 8.2 Transiciones permitidas (matriz — cada transición en el Servicio)

**Pedidos:**
```
borrador ─confirmar→ confirmado ─surtir→ surtido ─facturado parcial/→facturado
borrador ─cancelar→ cancelado; confirmado ─cancelar→ cancelado (libera apartados)
surtido → no cancela; se resuelve con NC o ajuste.
```

**Factura:** `borrador→emitida→cobrada_parcial→cobrada`; `vencida` derivada;
`emitida/borrador→cancelada`. **cancelada** SOLO si no hay cobros ni NC (validación de Servicio);
si los hay → primero cancelar esos.

**Recepciones:** `borrador→aplicada`; cancelada solo si `borrador` (si ya aplicada → rechazo;
ya generó movimientos → para observar reverse del servicio con `cancelada`+movimientos inversos).

**Traspaso:** `borrador→en_transito→recibido`; `borrador→cancelado`.
**Ajuste:** `borrador→aplicado` (tras aprobar); `borrador→cancelado`.

---

## 20. Roles y permisos (matriz)

Privilegios **ya sembrados**. Asignación de roles a módulos (seeder):

| Rol | Módulos |
|---|---|
| Administrador | todos (`*`) |
| Ventas (rol) | `ventas.*` + `crm.*` |
| Compras (rol) | `compras.*` |
| Almacenista (rol) | `inventario.*` |
| Contador | `finanzas.*` (NO ventas/compras/inventarios directo) |
| Recursos Humanos | `rh.*` |
| Empleado | ninguno específico |

Matriz **Ventas** (grupo de privilegios):

| Módulo | Entidad | Acciones | Privilegios |
|---|---|---|---|
| Ventas | clientes | ver/crear/editar/eliminar | `ventas.clientes.*` |
| Ventas | cotizaciones | +convertir | `ventas.cotizaciones.*` |
| Ventas | pedidos | +confirmar, cancelar | `ventas.pedidos.{ver,crear,editar,eliminar,confirmar,cancelar}` |
| Ventas | facturas | +emitir, cancelar | `ventas.facturas.*` + emitir/cancelar |
| Ventas | notas_credito | +emitir | `ventas.notas_credito.*` |
| Ventas | cobros | +aplicar | `ventas.cobros.*` |
| Ventas | descuentos | +autorizar | `ventas.descuentos.*` (> máx. descuento config) |
| Ventas | reportes | ver | `ventas.reportes.*` |

**Matriz Compras:**

| Entidad | Acciones | Privilegios |
|---|---|---|
| proveedores | CRUD | `compras.proveedores.*` |
| requisiciones | CRUD + aprobar | `compras.requisiciones.*` |
| ordenes | CRUD + confirmar, cancelar | `compras.ordenes.*` |
| recepciones | CRUD + aplicar | `compras.recepciones.*` |
| facturas (proveedor) | CRUD + contabilizar | `compras.facturas.*` |
| pagos | CRUD + aplicar | `compras.pagos.*` |
| reportes | ver | `compras.reportes.*` |

**Matriz Inventario:**

| Entidad | acciones clave | Privilegios |
|---|---|---|
| productos | CRUD | `inventario.productos.*` |
| almacenes | CRUD | `inventario.almacenes.*` |
| existencias | CRUD (leer — ver excisión) | `inventario.existencias.*` |
| movimientos | ver (kardex) | `inventario.movimientos.*` |
| traspasos | CRUD + aprobar | `inventario.traspasos.*` |
| ajustes | CRUD + aprobar | `inventario.ajustes.*` |
| conteos | CRUD + cerrar | `inventario.conteos.*` |
| reportes | ver | `inventario.reportes.*` |

> Regla de doble freno: en rutas se usa `permission:<ver>` amplio para listados y
> `permission:<accion>` para acciones de ciclo de vida (confirmar, emitir, aplicar, aprobar,...).

---

## 21. Reglas de negocio (validar con contexto real)

1. **Existencia debe ser ≥ 0**. `config:sisen/inventory.negative_stock_allowed=false`.
2. **No vender sin existencia**: surtir valida en el almacén destino.
3. **No recibir más de lo pedido** (tolerancia 0 por defecto).
4. **No devolver más de lo vendido/recibido/facturado** (verificación con sumatorias).
5. **No crear duplicados** SKU activo (`unicoActivo`), barcos únicos, código de cliente/proveedor.
6. **No cancelar documento ya procesado** (solo estados permitidos por la máquina).
7. **No editar precios de documentos emitidos** (fotos de línea: `precio_unitario`,`tasa_impuesto` son copias).
8. **No borrar catestros con historial** (FKs RESTRICT; solodeletes vía `unicoActivo`).
9. **Descuento/autorización** según `config.sisen.sales.max_discount` + `ventas.descuentos.autorizar`.
10. **Cálculo de totales** SIEMPRE por el modelo/línea (métodos `calcularTotal` en línea
    y cabecera) → consistencia.
11. **Folios**: nunca libres en UI; siempre `ServicioFolios`.
12. **Lote/caducidad**: oblig para productos marcados; validar en recepción.
13. Conversiones unidad base en captura (si stock en 12× caja y vendes 1 caja, sale 12 pz base).
14. **Mover stock en transacciones**: síntous del/from siempre en `DB::transaction`.

---

## 22. UI/UX (nivel comercial)

### 8.1 Componentes a usar (existentes)

`<x-page-header>` · `<x-filter-bar>` · `<x-card>` · `<x-table>` · `<x-badge>`
`<x-empty>` · `<x-stat-card>` — todos en `resources/views/components`.

### 8.2 Directrices

- **Desktop-first**, pero funcional en tablet/móvil (grid col-md, sidebar desde
  `data-sidebar-toggle`, tablas con scroll horizontal).
- Menú por **desplegable por módulo** (el que ya hace `layout` con `RegistroMenu`).
- **Listado:** `page-header` + `filter-bar` (buscar + selestados) + card/table + empty + pag.
- **Form docs:** rows `g-3`, colspan, tarjetas por secciones (datos generales / precios /
  impuestos), cálculos automáticos de totbilidad visible, botón Guardar + Cancelar.
- **Detalle de documento (show):** encabezado (folio grande, estado `x-badge` con `color()`),
  tarjeta de datos, tabla de líneas con totales, **cronología** (bitácora), acciones
  contextuales (Enviar/Aprobar/Confirmar/Emitir/Aplicar/Cancelar) con confirmación y con csrf.
- **Estados visuales** con paleta existente: `badge-activo/aprobado/pagada` (verde),
  `badge-cancelada/rechazado` (rojo), `badge-pendiente/venido` (naranja), nuevos colores en
  `sisen.css` en caso necesario (ej: `badge-enviada`, `badge-recibida`, `badge-surtido`).
- **Confirmaciones**: formularios de acción con `data-confirm` (JS ya lo soporta) + mensajes
  `flash` `success`/`error`.
- **Feedback inmediato**: redirección + flash + valida `@error` en inputs + `invalid-feedback`.
- **Errores claros**: `RuntimeException` español, `->with('error', ...)`.

### 8.3 Panle de documento** — accion contextuales
- Pedido BORRADOR: [Editar] [Cancelar] [Confirmar]
- CONFIRMADO: [Surtir] [Facturar] (a través de pedido para facturo parcial) [Cancelar]
- Factura EMITIDA: [Ver] [NC] [Registrar cobro]
- Recepción BORRADOR: [Completar] [Cancelar] — OK [Aplicar]

### 8.4 Búsqueda/productividad

- Búsqueda en tabla `escobar` sobre `%termino%` (skU, nombre, código cliente/prov… incl.
  código de barras en productos).
- Autocompletado de producto (IDAL con código+nombre+precio) en el carrito de pedidos/OC/NC.
- **Atajos** de teclado opcional (Enter para agregar línea, E.g. `+` en el campo qty).
- Duplicar documento — copia cabecera con prefijo "Copia de..." (borrador); muy útil.
- Histórico reciente en ficas y acciones rápidas en tablero (Nueva venta/Nueva OC/…).

---

## 23. Modelo de datos (ya fijado por migraciones; NO crear nuevas tablas en la Fase 1)

Aquí **no se inventa** el esquema: las migraciones `500100..500300` (Ventas),
`600100..600200` (Compras) y `300100..300900` (Inventario) **ya definen** el modelo. Se listan
las entidades clave con su propósito y relaciones para que el implementador las modele
(modelos Eloquent 1:1, un modelo por tabla, con reglas de $table/fillable/casts como en RH).

### 23.1 Inventario

| Tabla | Campos clave | Relaciones | Casts |
|---|---|---|---|
| `almacenes` | codigo(30) nec, nombre, direccion, activo | — | bool |
| `ubicaciones` | almacen_id, codigo+nombre, es_surtible, activo | belongsTo almacen | |
| `categorias_producto` | padre_id self, codigo,nombre | árbol | |
| `unidades_medida` | codigo(10), nombre, factor_base(18,6), es_base | | `factor_base` decimal6 |
| `productos` | organizacion_id, sku única activo, nombre, categoria_id, unidad_id, impuesto_id, costo, precio_venta (din 2), stock_min/max, es_vendible/comprador/inventario, rastrea_serie, estado | categorizacion→categoria, unidad, impuesto(finanzas), codigos_barras hasMany, lotes, series, movimientos | decimales, enum EstadoActM |
| `codigos_barras` | producto_id, codigo, es_principal | | |
| `lotes` | producto_id, numero_lote, fecha_caducidad, activo | | date |
| `numeros_serie` | producto_id, numero_serie, estado | | enum |
| `movimientos_inventario` | producto, almacen, ubicacion?`, tipo (enum), cantidad (signo), costo_unitario, origen_tipo/id polim, lot, serie, estado (aplicado/cancelado), aplicado/enviado_por | | enum |
| `traspasos`/`traspaso_lineas` | (origen/dest/gen) | | |
| `ajustes_inventario`/`ajuste_lineas` | | |
| `conteos_inventario`/`conteo_lineas` | cantidad_esperada/contada/diferencia | |
| `reglas_reorden` | producto, almacen, cantidades | | |

### 3.2 Ventas tablas

| tabla | claves |
|---|---|
| `listas_precios` | codigo, nombre, moneda, es_pred_default |
| `lista_precio_items` | lista_id, producto_id, cantidad_min, precio |
| `clientes` | organizacion, codigo, nombre, rfc, contacto, condicion_pago (catálogo), lista_precio, limite_credito, moneda, estado |
| `cotizaciones`+lineas+b | estado enum; vigencia; montos |
| `pedidos`+lineas | desde cotización; almacen; cantidad_surtida; version_fila |
| `facturas`+lineas | periodo_fiscal; condicion; total_cobrado; version_fila |
| `notas_credito`+lineas | factura_id, motivo, estado |
| `cobros` | cliente, factura(opc), forma_pago, monto, referencia, banco |

### 3.3 Compras tablas

| tabla | claves |
|---|---|
| `proveedores` | codigo, nombre, rfc, contacto, condicion, moneda, estado |
| `requisiciones`+lineas | departamento_id(←RH), solicitante, fecha_req, estado; lín pot proveedor_sug |
| `ordenes_compra`+lineas | proveedor, requisicion(opc), fecha, cant_recibida, costos, vers_fila |
| `recepciones`+lineas | orden(file), almacena, cant_recibida, costo, ubicación, lot, estado |
| `facturas_proveedor`+lineas | orden/recepcion(opc), total_pagado, vers_fila |
| `devoluciones_compra`+lineas | motivos, factura_proveedor_id |
| `pagos` | proveedor, factura(opc), monto, forma, banco |

### 3.4 Modelos a crear (1 por mesa en cada módulo)

- Ventas: Cliente, ListaPrecio, ListaPrecioItem, Cotización, CotizaciónLínea (CotizacionLinea), Pedido, PedidoLinea, Factura, FacturaLinea, NotaCredito, NotaCreditoLinea, Cobro. Enumes: EstadoCotizacion, EstadoPedido, EstadoFactura, EstadoNotaCredito(con motivos?), FormaPagoCobro, ...
- Compras: Proveedor, Requisicion, RequisicionLinea, OrdenCompra, OrdenCompraLinea, Recepcion, RecepcionLinea, FacturaProveedor, FacturaProveedorLinea, DevolucionCompra, DevolucionCompraLinea, Pago. Enums: EstadoRequisicion, EstadoOrdenCompra, EstadoRecepcion, EstadoFacturaProveedor, EstadoPago, MotivoDevolucionCompra, FormaPago.
- Inventario: Almacen, Ubicacion, CategoriaProducto, UnidadMedida, Producto, CodigoBarras, Lote, NumeroSerie, MovimientoInventario, Traspaso, TraspasoLinea, AjusteInventario, AjusteLinea, ConteoInventario, ConteoLinea, ReglaReorden. Enums: TipoMovimientoInventario(?), EstadoMovimiento, EstadoTraspaso, EstadoAjuste, EstadoConteo, EstadoProducto.

> Los enums **String** con valores idénticos a los CHECK; métodos `label()`/`color()` y
> predicados (`esEditable`, `esCancelable`, ...) igual que RH.

---

## 24. Migraciones — Lo que se requiere / no

**NO se requiere ninguna migración nueva para el MVP**: las tablas están completas y son
propiedad del esquema `sisen`. NO te desvíes de ellas. Sin embargo, hay candidatos a
**revisiones futuras** (documentadas, NO implementadas):

- `v_existencias` para agregar **reservado** (cantidad de movimientos `apartado`): los
  reportes de stock pasarían a considerar los movimientos de reserva. → **se hace en
  implementación de Inventario** como segunda vistaderivada (`v_stock_reservado`) por
  posible costo de cambio; EN VEZ de modificar `v_existencias` (establecido como contrato).
- Índices para los polimorfism a veces se auto-crean por EL Q; revisar costos de
  `movimientos_inventario.origen` y `bitacora` en reportes (%) — ya tienen índices.

**Recordar**: `ERP.sql` es REGENERADO por comando `GenerarEsquemaSql` — **no** se editan a
mano, y no importa en producción.

---

## 25. Seguridad

- **Autorización** en rutas (`permission:`) y en servicios (reglas de candidato) para
  acciones de ciclo — mismo deldo que RH.
- **Validación**: FormRequest por mutación (Spanish messages), `Rule::enum`, reglas
  espejos de handcoss (no structure the client), DB CHECK como última defensa.
- **Mass assignment**: $fillable capa whitelist en cada modelo; folio/estado/totales NO
  son fillable (los gestionan observer/service).
- **CSRF**: aplicado por el grupo `web` (providers ya lo pone). API usa el mismo grupo de
  sesión (cookie CSRF). El día que haya consumidor externo → `auth:sanctum`.
- **XSS**: Blade con `{{ }}` escapado; **nunca** `{!! !!}` con datos de usuario (solo en
  componentes sanitize).
- **SQL injection**: Query Builder con parametros/Elocuent; no `DB::raw` de input un val.
- **Subida de archivos** (adjuntos en NC/ajustes/…): whitelist `config.attachments.available_mimes`+max; para despliegue de imagen.
- **Acceso directo a registros**: mostrar (show) exige permiso según modulo; documentos
  no eliminar por DELETE hard (soft-delete + reverse actions).
- Datos sensibles: contraseñas hasheadas; bit́  no fotografiar hashes (trait ya oculta).

---

## 26. Performance (recomendaciones)

- **N+1**: `with([...])` en listado (producto-linea relacionado); evitar cargar fecciones
  de línea por truncación; en show, `->with('lines')`.
- **Libro de movimientos value grande** → índices ya están (`producto_id+aplicado_en`,
  `almacen+ubicacion`, `origen_tipo+origen_id`, `tipo`).
- Reportes → SEM actua en `v_existencias` (vista); hacer **TODOS** reportes batch desde
  vistas no de table scan.
- List destinados de productos con `with('categoria')` y filtros se inyectan en SQL (no en
  memoria).
- `paginate()` en listados (RH usaba 10); creessar filtrarción; `.withQueryString()`.
- **Apartar**: índices compuestos `(producto_id, almacen)` en movimientos para el cálculo
  de disponibles en tiempo real de pedidos (© no consulta pesada).
- Concurrencia: al surtir desde stock, usar `lockForUpdate` (o fila segura mediante
  movimientos) para no sobre-vender dos pedidos simultánees.

---

## 27. Reportes

Las vistas de reporte (ya creadas por las migraciones):
- Ventas: `v_historial_cliente`, `v_embudo_por_responsable` (CRM).
- Inventario: `v_existencias`.
(Fuera: `v_flujo_efectivo` de Finanzas y otras.)

**Reportes a construir** (pantalla + CSV vía `?formato=csv`, mismos query que en pág):

| Módulo | Nombre | Ruta(s) | Qué muestra |
|---|---|---|---|
| Inventario | Existencias (inventario.reportes.existencias) | stock por producto/almacén/loc (v_existencias) |
| | Movimientos (kardex) por periodo | movimientos por filter |
| | Stock bajo / por reorden | vs reglas; listado |
| | Valoración historial | value (existencia×costo) |
| | Próximo a vencer (lotes) | `fecha_caducidad` próxima |
| Ventas | Ventas por período (v per día/mes/Rng) | facturas vs cobros |
| | Ventas por producto | sum líneas |
| | Ventas por cliente / vendedor | hecho facturas agrup material |
| | Devoluciones (NC) | motivos/importe |
| Compras | Compras por período | FP + entregas |
| | Compras por proveedor / producto | |
| | Recepciones / órdenes pendientes | |
| | Anticipos | pago de proveedor? |

Todos son **solo lectura**; cas arjejarch `volver con print + CSV` (patrón RH).

---

## 28. Auditoría

- **Todo el OCR caminando existe**: trae `TieneBitacora`+`TieneCamposAuditoria` → cada alta
  altera alimo una fila `bitacora_auditoria` y guarda `creado_por`/`actualizado_por`.
- **Pantalla de auditoría**: por el momento puede visualizarse desde `show` (línea de
  tiempo o interpolación), reportes de cambios, o vista de admin `/facturas` pendiente de
  Compartido (voice). Para Fase 1: muestra bitácora en el `show` de cada documento
  (mismo patrón que la RH) y en las listas un botón global (fuera de scope si era).
- Nunca **escribir** en la bitácora desde controller: solo por trait y `registrarBitacora`.

---

## 29. Casos de uso (narrativos)

1. **Venta**: cajero busca `SKU` (autocompleta), ingresa qty y almacén; al confirmar pedido,
   el apartado (si CLI) se confirma; al surtir, salida de inventario. Emite factura →
   póliza. Recibe cobro → saldo 0.
2. **Recepción parcial**: OC con 100; llegan 60, 40. DOS recepciones. Líneas marcan `recibida`.
3. **Devolución**: NC `devolucion` → entrada al inventario y acredita al cliente (o NC fiscal).
4. **Ajuste por conteo**: inventario físico 98 vs sistema 100 → movimiento `conteo` −2.
5. **Reorden**: regla mínima 50, exist 20 → notificación y GU prellenada de requisición.
6. **Cancelación de venta**: pedido confirmado cancelado → liberar apartado/stock de vuelta.
7. **Pagos parciales**: factura 10k, cobra 6k y 4k — estados `cobrada_parcial`→`cobrada`.

---

## 30. Casos de prueba (funcionales)

### 3.1 VENTAS
1. Crear cliente → 2. Crear pedido con 2 líneas → 3. validar cálculos (sub−desc+imp) →
  4. Confirmar (apartado) → 5. Surtir y verificar `movimientos_inventario` (−) y exist
  0 → 6. Emitir factura (estado `emitida`, póliza?) → 7. Cobrar → `cobrada`.
  8. Cancelar pedido BORRADOR → sin efecto en stock.
Negativos: confirmar sin existencias → error; emitir factura de pedido sin stock no
inventariable?; emitir NC > facturada.

### 3.2 Compras
1. Crear proveedor → 2. Requisición → 3. Aprobar → 4. OC → 5. Confirmar/recibir
 (movimiento `compra` +) → 6. Verificar existencia y kardex/oc recibida → 7. Fact FP →
  8. Pago.
Negativos: recepción sobrepasa cantidad → rechazo; recibo más que exist (¿) → rechazo.

### 3.3 Inventario
- Entrada (compra) → salida (venta) → traspaso (salide en origen + entrada en destino,
  mismo costo en destino → valida erre `almacenes ≠`), ajuste (+/−, requiere aprobar),
  conteo (esperado vs contado → diferencia → movimientos), y `v_existencias` coherente.

### 3.4 Transversales
- Privilegios: usuario sin `pedidos.confirmar` → 403.
- Flujos estados inválidos (surtiro sin confirmar) → 403 / error de Servicio.
- Concurrencia: dos confirmación de misma OC con `version_fila` (uno falla).
- Determinismo: folios consecutivos; cancel nunca reutiliza.

---

## 31. Criterios de aceptación

Una operación se considera **correctamente implementada** cuando:

1. Se registra **cabecera + líneas + totales** (con consistency observer).
2. **Estados** solo pueden transitar por las máquinas permitidas (Service + FormRequest,
   DB CHECK es última red).
3. La existencia/finanzas cambia **solo vía movimientos/cotización** (nunca edición directa).
4. Cada mutación se **audita** (bitácora) y registra actor/fecha.
5. Los **privilegios** se manifiestan en rutas y acciones (403 correctos).
6. Los folios existen y son únicos; folios cancelados no se regeneran.
7. El usuario obtiene **feedback** (flash/success/error) y **validaciones en ES**.
8. Las pantallas siguen los **patrones RH** (componentes x-*, filter-bar, etc).
9. Reportes = mismos datos que CSV y en pantalla (vocational).
10. `composer test` verde (sin regresión RH/v1) y `pint` sin diff.

---

## 32. Plan de implementación (fases)

### FASE 0 — Preparación (1-2 días)
- Conv lag: confirmar este documento. No cambiar esquema.
- Crear `docs/david.md` (este), copiar convenciones de RH.
- Clonar patrón RH (carpetas, bios, traits) en los 3 módulos.
**Entregable**: ninguna tabla nueva; esqueleto limpio.

### FASE 1 — Inventario core (base)  [es el primer cement because V/C depend]
Objetivo: productos, categorías, unidades, almacenes, existencias, movimientos, kardex.
- Models+Enums observadores, ServicioMovimientoInventario (solo al que escribe),
  ServicioAjuste, ServicioTraspaso, ServicioConteo.
- Rutas, listas de venta, CRUD, tablero, reportes (v_existencias).
- Comando `inventario:reorden`.
**Dependencias:** Compartido listo.

### FASE 2 — Compras
R entorno de moldeo (**inventario** está hecho):
- Cadena requisición → OC → recepción → factura → pago (con movimientos del fase 1).
- Servicios ServicioRecepcion, ServicioFacturaProveedor, devolución; `ServicioContabilizar` pendiente (finanzas).

### FASE 3 — Ventas
- Cadena pedido→surtido→factura→cobro; ServicioResolverPrecio, ServicioApartarExistencia,
- ServicioEmitirFactura, ServicioAplicarCobro, NC. Integración inventario+finanzas.

### FASE 4 — Integración
- Terminar los acoplamientos finanzas (pólizas de factura/cobro/FP/pago) cuando
  `ServicioContabilizar` de Finanzas esté listo; acepta la petición de proveedores (2).
- Búsqueda global, notif. stock bajo (cuando Servicie).
- Embeber hooks/cron (reorden) y esperar a que el backend normal.

### FASE 5 — Reportes & CSV (del Fase 1-3)
### FASE 6 — UX / performance (N+1, paginación, optim)
### FASE 7 — Pruebas
- Feature tests de los 3 módulos (fase 0. línea del §30).
- Regress; `composer test` verde.

Compatible con M0-M7 del `PLANNING` (M2=Inventory + RH, M4=Sales+Plus).

---

## 33. Riesgos

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Finanzas (ServicioContabilizar...) sin hacer | Bloquea emitir facturas/pagar | Contingencia: estados y número_doc correctos; la póliza se genera cuando Finanzas exista (Diseño póliza pendiente) |
| PLANNING bilingüe (EN vs ES) confunde | Mal nombrado de clases | Seguir **convenio real del código** (un idioma ES) y los README de cada módulo; registrar en Actas de este doc |
| Bitácora/adjuntos no terminados (Servicios) | notif de auditoría a medio | Fase 6-7 priorizadiv; bitácora ya funcionata |
| Cambiar esquema (tentación) | Romper integridad ría | Inmoral: no nuevos tables en Fase 1-3 |
| Dos módulos tocando inventario con currencia | Oversell/stock - | `lockForUpdate`, transacciones, checks DB |
| Error con folios/contadores | Duplicar números | `ServicioFolios` en observer, no reutilizar |
| 403 en menú (dashboard de placeholder) | UX frustrante | Tableros propios implementados en Fase 1 |

---

## 34. Decisiones de diseño (formato DO/RA/AL/IM)

**D1. Implementar el patrón RH (copiar y adaptar).**
- Motivo: es el ejemplo consumido, probado y consistente (controllers, services, views).
- Alternativas: crear frameworks propios (más coste, riesgo de inconsistencia).
- Impacto: la curva de aprendizaje es corta y la mantenibilidad alta.

**D2. No crear tablas nuevas.**
- Motivo: el esquema ya fue diseñado y migrado con CHECKs/FKs/vistas; actos adicionales
  violan integridad.
- Alternativa: agregar columnas para reservado etc. → NO aún; se agrega vista derivada.
- Impacto: esquema fijo = mayor takka al phase; la vista de "reservado" se añade como vista.

**D3. Stock que se deriva de movimientos; "reservado" = vista.**
- Motivo: trazabilidad / invariantes de negocio.
- Alternativa: columna `existencias` → prohibida por el README.
- Impact: cálculos en tiempo real, con índices.

**D4. Módulos un solo catalogo de producto con **filtros de categoría** en lugar de
pantallas separadas (medicamentos/insumos...
- Motivo: una sola lógica; UI coherente.
- Alternativa: 5 catálogos → duplicación + confusiones. PM.

**D5. Vital no crear tabla `cotizaciones_proveedor` ─ comparativo por OC duplicadas.**
- Razón: esquema actual no lo tiene y el negocio (aprox.) opera "OC directa". (DECISIÓN
  PENDIENTE si el negocio fuerza múltiples proveedores por compra.) IMPA (si se necesita
  completar, se implementa como herramienta reportiva no tabla).
- Impact: menos complejidad; la oferta se cambia en OC.

**D6. Los `estados` NO se re-inventó:** se usan exactamente los del CHECK. Debilidades
→ por ejemplo "vencido" es derivado para facturas.
**D7. Reglas financieras:** la contabilización de póliza es la misma `ServicioContabilizarPoliza`
  de Finanzas (origen_tipo factura/cobro/NC/FP/pago) y se llama desde los Servicios de Ventas/Compras.

**D8. Software de testing:** `tests/Feature/Modules/{Ventas|Compras|Inventario}` con base
 `PruebaX` (como `PruebaRH`), que siembra `RolPrivilegioSeeder`+secuencias y crea un
 `Administrador`.

---

## 35. Problemas actuales (tabla)

(Véase §5 con la tabla de hitos — aquí se arranca el camino.)

| # | Problema | Solución en implementación |
|---|---|---|
| F1 | Sin models/enums/services en V/C/I | Clonar RH; fase 1-3 |
| F2 | Finanzas sin “contabilizar” | Plan contingente §33 |
| F3 | Notificaciones incompletas | Utilizar si posible; fase 6 (no paso bloqueante) |
| F4 | Menú provisorio de módulos sin permisos | Tableros propios con `permission:` en el paso |
| F5 | No existen datos demo | seeders de prueba (no `Cimientos`), y línea deudas |
| F6 | No comandos de reorden | `inventario:reorden` en la Fase 1 |

---

## 36. Figuras DT

- **Modelo de datos**: tablas ya migradas (listadas en §2.4 y §23). **No se requieren migraciones nuevas** en esta fase.
- **Índices**: usar los existentes; evaluar únicamente los del cálculo de stock.

---

## 37. Pendientes

- [ ] (BLOCKER) Implementar `ServicioContabilizarPoliza` en Finanzas (`origen_tipo`)
      que Ventas/Compras invocan — si no, atacar con el plan contingente.
- [ ] Implementar `ServicioAdjuntos`/x-attachments + `ServicioNotificaciones` + campana UI
      (a medida privar cuando se necesiten stock_bajo).
- [ ] Decidir el "cotizaciones de proveedores" (D6) con negocio.
- [ ] Confirmar multifabricación (1 caja/12 pzo) con usuario final. Default: unidades_base.
- [ ] Definir si `reservado/por surtir` en OC se muestra como "por recibir".
- [ ] Vistas de `v_existencias` ampliadas (reservado) en fase posterior (no tocar contrato).
- [ ] Pantalla y política de `usuarios/roles` de Compartido para asignar roles Ventas/
      Compras/Almacen en UO (necesario para tests de rol).

---

## 38. Checklist final (definir FIN salvo que no lo implement)

Ventas:
- [ ] `ventas.dashboard` con KPIs + accesos.
- [ ] Gestión de clientes + listas de precios.
- [ ] Cotizacion (crear/enviar/aceptar|rechazar|convertir).
- [ ] Pedido (crear/confirmar/surtir/facturar|cancelar).
- [ ] Factura (crear/emitir/cancelar) + cobros parciales.
- [ ] Cobro (aplicar) + NC.
- [ ] Movimientos de inventario (venta y devolución_venta) y salida coherente.
- [ ] permisos `ventas.*` sembrados.

Compras:
- [ ] Proveedores + requisición + aprobar → OC (base estándar).
- [ ] OC: confirmar / recibir parcial, recibir, cancelar.
- [ ] Recepción: aplicar → entrada inventario.
- [ ] Fact. proveedor + conciliación 3 vías (a «multi») → contabilización (con contingencia).
- [ ] Devolución de compra + pago.
- [ ] Estado valido al final.

Inventario:
- [ ] Producto unificado (medicamentos/insumos por categoría).
- [ ] Reportes de inventario (existencias, kardex, reorden).
- [✓] Entendimiento de unidades y conversión.

Safe check: una sola guía, un solo modelo, un solo test (RH), sin multi-tecnología.

---

Glosario de convenciones clave (para el implementador):

- Rutas: `<modulo>.<entidad>.<accion>`; `Route::resource('productos', ...)` autom.
- Vistas: `<modulo>::catalogos/...`, `<modulo>::paginas/...`
- Permission: en rutas no repitas `web/auth`; en culpa pueden colocar una sola.
- FormRequest Base: ext `RequestBase` de RH cría — ¿se crea una `App\Modules\{X}\Requests\RequestBase` análoga.
- Modelos: usa `SoftDeletes,TieneBitacorra,TieneCamposAuditoria`, casts de Enum.
- Comp. Templates: `data-confirm`; flash `success`/`error`; `@error` feedback.
- Services: `DB::transaction`, privilegios finos, `$model->registrarBitacora('emitida',...)`.