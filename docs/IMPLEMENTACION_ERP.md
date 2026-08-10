# Implementacion de Ventas, Compras e Inventario

Bitacora de lo que se construyo siguiendo `docs/david.md`, y de las decisiones
que hubo que tomar sobre la marcha. El blueprint sigue siendo `david.md`; este
documento dice **que quedo hecho y por que se resolvio asi**.

---

## 1. Lo que se construyo

Los tres modulos pasaron de esqueleto a operativos. **No se creo ni se modifico
ninguna migracion**: el esquema estaba fijo y se respeto (§24, D2).

| | Inventario | Compras | Ventas |
|---|---|---|---|
| Enums | 6 | 8 | 7 |
| Modelos | 16 | 12 | 12 |
| Servicios | 6 | 6 | 6 |
| Controladores | 13 (+1 API) | 8 | 8 |
| Observers (folios) | 3 | 6 | 5 |
| Vistas Blade | 36 | 30 | 33 |

Ademas, en `Compartido`: `CalculadoraLinea`, `SumaTotalesDeLineas`,
`ControlaDocumentos`, `OpcionesEnum`, `ExportadorCsv`, `RequestBase`,
`EstadoActivacion`, el contrato `Contabilizador` y las parciales `bitacora` y
`acciones-reporte`.

**242 rutas** nuevas, todas con `permission:`, todas sobreviven a `route:cache`.

### Los dos flujos completos, funcionando

```
COMPRAS   requisicion -> aprobar -> orden -> confirmar -> recepcion -> APLICAR
          -> [entra al inventario] -> factura (cotejo 3 vias) -> pago
VENTAS    cotizacion -> aceptar -> pedido -> CONFIRMAR [aparta existencia]
          -> SURTIR [sale del inventario] -> factura -> emitir -> cobro
```

---

## 2. Decisiones que el blueprint dejaba abiertas

### D-A. La existencia disponible ES `v_existencias.existencia`

`david.md` §11.2 definia `disponible = existencia - reservado`, pero
`v_existencias` suma **todos** los movimientos aplicados, y los apartados viven
en ese mismo libro. Habia dos salidas: modificar la vista (prohibido por D2) o
aceptar lo que la vista ya significa.

Se acepto lo segundo: **`v_existencias.existencia` ya es la existencia
DISPONIBLE**. Para que `valor_inventario` siguiera siendo correcto, los
movimientos `apartado` y `liberacion_apartado` se graban con
**`costo_unitario = 0`**: son una promesa, no valor.

El desglose de las tres cifras (fisica / apartado / disponible) lo da
`ServicioExistencias`, con una sola consulta agregada. No hizo falta la vista
`v_stock_reservado` que §24 dejaba como candidata.

**Consecuencia practica:** la invariante del modulo es una sola y muy simple —
*la suma del libro por (producto, almacen) nunca queda negativa* — y con ella
sola se impide vender sin existencia **y** apartar dos veces la misma pieza.

### D-B. El orden de los movimientos al surtir

Surtir hace dos movimientos por linea, y el orden **no es un detalle**:

1. `liberacion_apartado` (+) libera la reserva de lo que va a salir
2. `venta` (-) saca la mercancia

Al reves, la salida se validaria contra un disponible que todavia tiene
descontada la reserva **de este mismo pedido**, y el surtido fallaria por
"existencia insuficiente" teniendo la mercancia apartada justo para el.

### D-C. `Contabilizador`: el contrato que desbloquea Finanzas (§33)

`ServicioContabilizarPoliza` de Finanzas no existe (P3), y era un bloqueador de
las fases 3 y 4. La salida fue un contrato en Compartido
(`App\Modules\Compartido\Contracts\Contabilizador`) del que dependen Ventas y
Compras, con una implementacion de contingencia (`ContabilizadorPendiente`) que
**no inventa asientos**: deja en la bitacora que el documento quedo listo para
contabilizarse, con sus importes y su fecha.

Los documentos avanzan de estado igual (emitida, contabilizada) y su poliza
queda pendiente. **El dia que Finanzas publique el suyo, se cambia UNA linea en
`AppServiceProvider` y ni Ventas ni Compras se tocan.**

### D-D. Captura en dos pasos, no en un formulario gigante

Los seis tipos de documento con lineas se capturan igual: primero la cabecera,
despues las lineas en la ficha. No es un carrito con JavaScript.

Razon: un formulario de veinte renglones que se pierde entero cuando una
validacion falla es peor que dos pasos. Ademas, la ficha es donde el documento
ya tiene folio y donde viven sus acciones de ciclo de vida, asi que es el lugar
natural. El autocompletado de producto del API (`/api/inventario/productos/buscar`)
esta publicado y trae precio, costo y disponible para cuando se quiera montar un
carrito dinamico encima.

### D-E. "Vencida" nunca se guarda

Ni en facturas, ni en facturas de proveedor, ni en cotizaciones. Se **deriva**
de comparar la fecha con hoy (`$factura->esta_vencida`). El CHECK de `facturas`
permite el valor `vencida`, pero guardarlo obligaria a un cron nocturno que lo
refrescara y se desincronizaria la primera noche que ese cron fallara.

Los listados filtran por vencidas con la misma comparacion, sin estado guardado.

### D-F. Devoluciones y listas de precios, bajo privilegios existentes

`RolPrivilegioSeeder` no siembra `compras.devoluciones.*` ni
`ventas.listas_precios.*`, y el seeder vive fuera de los modulos. En vez de
inventar privilegios que el seeder no conoce:

- devoluciones a proveedor -> `compras.facturas.*` (es un ajuste a una factura)
- listas de precios -> `ventas.clientes.*` (es parte de la relacion comercial)

Queda anotado en el encabezado de cada `Routes/web.php`: el dia que se agreguen
al seeder, se cambia ahi y en ningun otro lado.

---

## 3. Lo que se resolvio de la tabla de problemas de `david.md`

| # | Problema | Estado |
|---|---|---|
| P1 | Falta todo el codigo de app de los tres modulos | **Resuelto** |
| P5 | Tableros de placeholder sin privilegio | **Resuelto**: los tres tienen tablero propio con `permission:` |
| P7 | `v_existencias` no separa apartado | **Resuelto** por D-A, sin tocar la vista |
| P9 | Sin datos demo | **Resuelto**: `DatosDemoErpSeeder`, fuera de Cimientos |
| P10 | Sin comando de reorden | **Resuelto**: `inventario:reorden` + `Schedule` diario |
| P3 | Finanzas sin contabilizar | **Desbloqueado** por D-C; la poliza real sigue pendiente de Finanzas |
| P4 | Notificaciones sin servicio | **Parcial**: `inventario:reorden` escribe en `notificaciones`; falta la campana en la UI |
| P6 | `route:cache` pierde el menu | **Sin cambio**, igual que RH: exige un ServiceProvider por modulo registrado en `bootstrap/providers.php`, fuera del alcance de un modulo |

---

## 4. Como probarlo

```bash
# Datos de demostracion (NO en produccion)
php artisan db:seed --class=DatosDemoErpSeeder

# La revision de minimos
php artisan inventario:reorden

# Todo verde
composer test
./vendor/bin/pint --test app/Modules
```

El recorrido corto para ver el ERP entero funcionando:

1. **Compras** > Ordenes > nueva contra un proveedor, agrega lineas, confirma
2. **Compras** > Recepciones > nueva contra esa orden, captura y **aplica**
3. **Inventario** > Existencias: la mercancia ya esta ahi
4. **Ventas** > Pedidos > nuevo, agrega lineas con almacen, **confirma**
5. **Inventario** > Existencias: bajo el disponible, no la existencia fisica
6. **Ventas** > el pedido > **Surtir** > **Facturar** > **Emitir** > cobrar
7. **Inventario** > Kardex: la historia completa, movimiento por movimiento

---

## 5. Lo que sigue pendiente

Heredado de `david.md` §37, sin cambios:

- [ ] `ServicioContabilizarPoliza` en Finanzas (el contrato ya lo espera)
- [ ] `ServicioAdjuntos` + `ServicioNotificaciones` y la campana de la UI
- [ ] Pantalla de usuarios/roles en Compartido, para asignar roles sin tocar la base
- [ ] Timbrado CFDI (`facturas_electronicas` esta preparada, sin usar)
- [ ] Decidir con negocio si hace falta comparativo de cotizaciones de proveedor
      (D5 de `david.md`: por ahora se resuelve con varias ordenes)
