# SISEN ERP — Master Planning Document

> **Status:** Baseline / Foundation (v1.1)
> **Project:** SISEN (Sistema Empresarial Integrado de Nominas y Empresa)
> **Stack:** Laravel 12 · PHP 8.2 · Blade · Bootstrap 5.3 · **MariaDB**
> **Audience:** All module development teams (2 developers per module) and AI assistants (e.g. Claude Code) that will continue implementation module by module.
> **Guiding rule:** This document is the single source of truth. If there is ever ambiguity, resolve it here first.

---

# Decisiones v1.1 — leelas antes que nada

Estas cuatro decisiones **sustituyen** lo que diga cualquier seccion posterior
escrita en la version 1.0 del documento. Donde haya conflicto, mandan estas.

### 1. MariaDB, y solo MariaDB

Se descarta PostgreSQL como objetivo. La aplicacion, el esquema de referencia y
la suite de pruebas corren sobre **MariaDB** (driver `mariadb` de Laravel). No
hay compatibilidad multi-motor que mantener, y por eso las restricciones `CHECK`
y los indices unicos que ignoran el borrado logico son garantias reales y no
"documentacion aspiracional".

Las pruebas tambien corren sobre MariaDB (`phpunit.xml` apunta a `sisen_test`),
justamente para ejercitar esas restricciones. Crea la base una sola vez:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS sisen_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

### 2. `ERP.sql` se GENERA; el esquema vive en las migraciones

El esquema se crea y evoluciona **solo** con `php artisan migrate`. `ERP.sql`
nunca se importa: es el contrato de base de datos que los equipos leen, y se
regenera desde las migraciones para que no pueda desviarse de ellas:

```bash
php artisan sisen:esquema      # vuelca ERP.sql desde una base recien migrada
```

Si tu cambio de esquema no esta en una migracion, no existe.

### 3. Todo en espanol, la base de datos incluida

Tablas, columnas, nombres de modulo, archivos de migracion, clases, metodos,
variables y comentarios van en espanol, igual que las pantallas y las tablas que
SISEN v1 ya tenia (`departamentos`, `empleados`, `nominas`).

Solo conservan su nombre en ingles los elementos del framework, porque
renombrarlos romperia Laravel sin ganar nada: la tabla `users` y las columnas
`created_at`, `updated_at`, `deleted_at`, `password`, `remember_token`. Las
carpetas estructurales de cada modulo (`Controllers/`, `Models/`, `Services/`,
`Migrations/`, ...) tambien se quedan como estan, porque son el contrato de
arquitectura descrito mas abajo.

Los modulos y sus prefijos quedan asi:

| Carpeta | URL | Rutas | Vistas |
|---|---|---|---|
| `Compartido` | (raiz) | `compartido.*` | `compartido::` |
| `Finanzas` | `/finanzas` | `finanzas.*` | `finanzas::` |
| `Inventario` | `/inventario` | `inventario.*` | `inventario::` |
| `RH` | `/rh` | `rh.*` | `rh::` |
| `Ventas` | `/ventas` | `ventas.*` | `ventas::` |
| `Compras` | `/compras` | `compras.*` | `compras::` |
| `CRM` | `/crm` | `crm.*` | `crm::` |

Un choque de nombres a tener presente: v1 ya usa `permisos` para las solicitudes
de permiso y vacaciones, asi que los permisos de autorizacion se llaman
**`privilegios`** (`rol_privilegios`, `usuario_roles`).

### 4. RH EXTIENDE las tablas de v1; no crea tablas paralelas

`departamentos`, `puestos`, `empleados`, `asistencias`, `permisos` y `nominas`
son las tablas de RH del ERP. Las migraciones del modulo les **agregan**
columnas (jerarquia, datos fiscales y bancarios, auditoria, borrado logico) sin
renombrar ni eliminar nada de v1.

Esto elimina la duplicacion `departments`/`departamentos` que planteaba la v1.0
del documento: hay una sola verdad sobre un empleado, y las pantallas de v1
siguen funcionando sin tocarlas. La nomina se despliega en los tres niveles que
necesita (`nomina_periodos` -> `nomina_corridas` -> `nominas`), donde `nominas`
sigue siendo el recibo por empleado de siempre, ahora con `corrida_id`.

---

# Introduction

SISEN started as a payroll-only prototype (`SISEN v1` — "Sistema Empresarial de Nominas") built on Laravel 12 with Blade and Bootstrap. It currently covers a Human Resources slice: departments, positions, employees, payroll (`nominas`), attendance (`asistencias`), leave/permissions (`permisos`), a dashboard, 7 report screens and user management.

This document defines the **target architecture for the full ERP**: Finance & Accounting, Sales, Purchasing, Inventory, Human Resources and CRM, plus the shared foundation (auth, roles, permissions, audit, notifications, catalogs, attachments, dashboard, reports, search, filters, import/export).

The deliverables of this phase are:

1. `docs/PLANNING.md` — this document.
2. `ERP.sql` — the enterprise PostgreSQL schema (target database).
3. A **module skeleton** under `app/Modules/` — consistent, parallel-work-ready scaffolding. No business logic implemented yet.
4. Shared layouts, routing, types/contracts and reusable Blade components.

Everything in the existing prototype that works must keep working. This plan **documents** improvements; it does **not** rewrite the prototype.

---

## Baseline Analysis — SISEN v1 (current state)

This section captures what exists today so all teams share the same mental model. It was produced by a full repository review. **Nothing in this section was changed.**

### 1. Technology stack (current)

| Layer        | Technology                                                        |
|--------------|-------------------------------------------------------------------|
| Framework    | Laravel 12 (PHP ^8.2)                                             |
| Front-end    | Server-rendered Blade + Bootstrap 5.3.3 (CDN) + Bootstrap Icons + custom `public/css/sisen.css` + `public/js/sisen.js` |
| Build        | Vite 7 + Tailwind CSS 4 (configured, used mainly for auth scaffolding entrypoints) |
| Database     | MySQL (`sisen` database) — driver in `.env` (`DB_CONNECTION=mysql`) |
| Cache/Session| Database driver                                          |
| Auth         | Custom session auth (`AuthController`) — **not** Breeze/Fortify   |
| Authorization| Single `role` column on `users` + `RoleMiddleware` + `User::hasAnyRole()` |
| Locale       | Spanish (`es`, fallback `es`, faker `es_MX`)                       |
| Tests        | PHPUnit 11 (minimal scaffolding only)                              |
| Formatting   | Laravel Pint (`.editorconfig` indent 4, LF)                        |

### 2. Implemented modules (current)

- **HR / Payroll:** `departamentos`, `puestos`, `empleados`, `nominas`, `asistencias`, `permisos` — full CRUD, search (`buscar`), pagination, `show` detail pages, CSV-agnostic report screens.
- **Dashboard:** stat cards, quick links, recent lists.
- **Reports:** `reportes/*` (empleados, nominas, asistencias, permisos, departamentos, pagos-pendientes) — HTML views with print CSS.
- **Users:** `usuarios` CRUD (Admin only).

### 3. Conventions established in the prototype (MUST be preserved)

- **Routing:** `routes/web.php`; `Route::resource(...)`; role middleware inline per resource (`->middleware('role:...')`); custom actions as named PATCH routes (`nominas/{nomina}/pagar`).
- **Controllers:** `App\Http\Controllers`, plain resource controllers; validation inline in a private `validated(Request $request)`; role guards as private helpers (`denyEmployeeRole()`, `authorizeEmployee()`).
- **Models:** `App\Models`, `protected $fillable`, `protected $casts`, Eloquent relationships, computed attributes via `getXAttribute()` (e.g. `getNombreCompletoAttribute`).
- **Views:** `@extends('layouts.app')`, `@section('title'|'header'|'subtitle'|'content')`; `soft-card` wrapper; `@include('partials.badge', ['estado' => ...])`; `@include('partials.alerts')`; search input `name="buscar"`; pagination with `->withQueryString()` and `->links()`.
- **Design tokens:** custom properties in `sisen.css` (`--sisen-blue`, `--sisen-blue-dark`, `--sisen-bg`, `--sisen-text`, `--sisen-muted`, `--sisen-green`, `--sisen-red`, `--sisen-orange`); `sidebar` + `topbar` + `content-wrap` shell; `brand-mark`; `stat-card`/`stat-icon`; `badge-soft badge-{estado}` color mapping; `action-btn` icon buttons; mobile overlay + `data-sidebar-toggle`; `data-confirm` forms; `data-payroll-calc` totals preview.
- **Language:** UI strings in Spanish; table/column names in Spanish (singular/plural as used today); code identifiers in English.
- **Migrations:** `$table->id()`, `foreignId(...)->constrained(...)`, `enum` columns for states, `timestamps()`.

### 4. Documented improvements (analysis only — NOT implemented)

These are known gaps. They are addressed by the target architecture in this document. They must be introduced incrementally by the relevant teams, never in a breaking batch:

1. ~~**Roles & permissions are not normalized.**~~ **RESUELTO en v1.1.** `roles`, `privilegios`, `rol_privilegios` y `usuario_roles` ya existen. La columna `users.role` de v1 se queda y `User::hasAnyRole()` responde desde las dos fuentes, asi que ninguna ruta ni vista de v1 cambio (ver *Roles & Permissions*).
2. ~~**Database driver mismatch.**~~ **RESUELTO en v1.1.** No hay tal desajuste: el objetivo es MariaDB, el mismo motor sobre el que ya corria el prototipo. Ver *Decisiones v1.1*.
3. ~~**`users.empleado_id` no tiene llave foranea.**~~ **RESUELTO en v1.1** por la migracion `extender_tabla_users`.
4. **Dual front-end tooling.** Tailwind (via Vite) is configured but Bootstrap CDN is the real design system. Decision for the future: **stay on Bootstrap 5 + custom `sisen.css`** to keep the visual language intact; drop Tailwind only when a dedicated refactor is planned. New components MUST use Bootstrap + the SISEN tokens.
5. **No audit fields, no soft delete.** Prototype tables only have `timestamps()`. Target: `created_by`, `updated_by`, `deleted_at`, `row_version`.
6. **No API.** Target: JSON API per module behind `api/*` (see *API Structure*).
7. **No FormRequests / Services layer.** Target: one FormRequest per mutation, one Service per aggregate operation.
8. **No automated test coverage beyond scaffolding.** Target: Feature tests per module (see *Testing Strategy*).

---

# Project Vision

**One sentence:** SISEN becomes a modular, extensible, multi-team ERP for Mexican SME/SMB manufacturing and services companies, where Finance, Sales, Purchasing, Inventory, HR and CRM live under one roof, one login, one design system and one database.

**Why it exists (product):**
- A single source of truth for money (Finance), goods (Inventory/Purchasing) and people (HR/CRM).
- Full traceability: every transaction is numbered, posted, audited and reversible.
- Local-first compliance: CFDI-ready electronic invoices, RFC/tax structures, payroll periods (quincena), fiscal years.

**Why it exists (engineering):**
- Enable **6 independent teams (2 devs each)** to work in parallel on the same repository without stepping on each other: one folder per module, one namespace per module, one route prefix per module.
- Keep the foundation small but rigorous: consistent skeleton, shared components, shared contracts, shared database conventions.
- Be friendly to AI-assisted development: the plan, the skeleton and `ERP.sql` are precise enough that any assistant can implement one module end-to-end without guesswork.

**Out of scope for this phase:** complete business logic, real data, electronic-invoice integration, third-party systems. This phase delivers *architecture + foundation only*.

---

# ERP Philosophy

The architecture follows principles observed in SAP/Odoo/ERPNext/Dynamics but adapted to a Laravel + Blade monolith. Every team must internalize these.

1. **Document flow over CRUD.** In an ERP, records are *documents* with a lifecycle: `draft → posted/approved → … → cancelled`. They carry a human-readable number (`SO-000123`, `FC-000456`, `NV-000321`), generated by `document_sequences`. Editing a *posted* document is forbidden; corrections are new reversing documents. CRUD is only for master data (catalogs).
2. **Everything is posted to a ledger.** Money movements post to `journal_entries`; stock movements post to `stock_movements`. Reports are *derived views*, never a second copy of the data.
3. **Numbers before labels.** Financial and stock numbers are `NUMERIC(18,2)` (money) / `NUMERIC(18,6)` (rates and unit costs) — never `float`.
4. **Soft delete + audit.** Business data is never hard-deleted. Every table carries `created_by/updated_by/created_at/updated_at/deleted_at`; core documents add `row_version` for optimistic locking.
5. **Master data is shared; transactions belong to modules.** Customers, suppliers, products, chart of accounts are shared concepts. A table lives in **one** module; others reference it. Never duplicate `customers` in both Sales and CRM — Sales owns *customers*, CRM owns *leads/opportunities/contacts*.
6. **Consistency over cleverness.** One way to build a list page, one way to build a form, one way to name things. If you need a new pattern, document it here first.
7. **The database is the contract.** `ERP.sql` (target) and its Laravel migration port are the schema contract between teams. Renaming a column in another module's table requires that module's team.

---

# Global Architecture

## Logical layers

```
┌─────────────────────────────────────────────────────────────┐
│  Presentation  (Blade views, layouts, components)           │
│  resources/views + app/Modules/<Module>/Views               │
├─────────────────────────────────────────────────────────────┤
│  HTTP layer  (Routes, Controllers, FormRequests, Middleware)│
│  app/Modules/<Module>/Controllers · Requests · Routes       │
├─────────────────────────────────────────────────────────────┤
│  Application  (Services, Observers, Events, Utils)          │
│  app/Modules/<Module>/Services · Observers · Utils          │
├─────────────────────────────────────────────────────────────┤
│  Domain  (Models, Enums, Contracts, Traits)                 │
│  app/Modules/<Module>/Models · Enums · app/Modules/Shared   │
├─────────────────────────────────────────────────────────────┤
│  Infrastructure  (DB schema, filesystem disks, queue, mail) │
│  ERP.sql · Laravel migrations · config/                     │
└─────────────────────────────────────────────────────────────┘
```

## Runtime model

- **Monolith, not microservices.** One Laravel application, one deployment. Modules are *code organization*, not processes. This is what enables 6 parallel teams on one repo.
- **Service Provider autoloading.** `App\Providers\ModuleServiceProvider` scans `app/Modules/*` at boot and registers, per module: `Routes/web.php` (under `/module-slug` with `module.` route names), `Routes/api.php` (under `/api/module-slug`), Blade view namespace `module-name::`, and module migrations. Adding a module = adding a folder. No changes to `routes/web.php`.
- **Shared kernel stays central.** `bootstrap/app.php`, `config/*`, `app/Providers/AppServiceProvider`, the main `layouts.app` and shared components remain module-agnostic.
- **Database-first.** `ERP.sql` defines the target schema. Each module team ports its own tables to Laravel migrations **inside its module folder** (`app/Modules/<Module>/Migrations`), in dependency order.

## Module map and ownership (RACI-lite)

| # | Modulo (carpeta) | Tablas propias | Depende de |
|---|---|---|---|
| 0 | `Compartido` | organizaciones, users, roles, privilegios, rol_privilegios, usuario_roles, bitacora_auditoria, notificaciones, configuraciones, catalogos, adjuntos, etiquetas, favoritos, comentarios, secuencias_documento, lotes_importacion, reportes_guardados | — |
| 1 | `Finanzas` | catalogo_cuentas, periodos_fiscales, centros_costo, polizas, poliza_lineas, presupuestos, cuentas_bancarias, movimientos_bancarios, conciliaciones_bancarias, impuestos, facturas_electronicas, tipos_cambio | Compartido |
| 2 | `Inventario` | almacenes, ubicaciones, categorias_producto, unidades_medida, productos, codigos_barras, lotes, numeros_serie, movimientos_inventario, traspasos, ajustes_inventario, conteos_inventario, reglas_reorden | Compartido, Finanzas (impuestos) |
| 3 | `RH` | departamentos, puestos, empleados, asistencias, permisos, nominas (las seis de v1, extendidas), nomina_periodos, nomina_corridas, contratos, documentos_empleado, evaluaciones_desempeno | Compartido, Finanzas |
| 4 | `Ventas` | clientes, listas_precios, cotizaciones, pedidos, facturas, notas_credito, cobros (+ sus lineas) | Compartido, Inventario, Finanzas |
| 5 | `Compras` | proveedores, requisiciones, ordenes_compra, recepciones, facturas_proveedor, devoluciones_compra, pagos (+ sus lineas) | Compartido, Inventario, Finanzas, RH |
| 6 | `CRM` | empresas, prospectos, contactos, oportunidades, actividades, notas_crm, tareas | Compartido, Ventas (clientes) |

> El orden de migracion (y de trabajo) es el de la tabla: **Compartido → Finanzas
> → Inventario → RH → Ventas → Compras → CRM**. No es arbitrario: es el orden de
> dependencia de las llaves foraneas, y por eso las marcas de tiempo de las
> migraciones van 100 → 200 → 300 → 400 → 500 → 600 → 700, con las vistas de
> reporte al final en 900.

---

# Module Architecture

Every module uses **exactly the same structure**. No module may deviate. This is what allows teams to be interchangeable.

```
app/Modules/<Module>/                      # e.g. app/Modules/Finance
├── Controllers/                           # HTTP controllers (resource + actions)
├── Requests/                              # FormRequest validators (1 per mutation)
├── Services/                              # business operations / aggregate logic
├── Models/                                # Eloquent models
├── Enums/                                 # PHP backed enums for states/status
├── Observers/                             # Eloquent hooks (numbering, totals)
├── Utils/                                 # pure helpers, calculators, formatters
├── Contracts/                             # (optional) module-local interfaces
├── Routes/
│   ├── web.php                            # browser routes (registered by provider)
│   └── api.php                            # JSON API routes (registered by provider)
├── Views/
│   ├── dashboard/                         # module dashboard partial
│   ├── catalogs/                          # master-data index/create/edit pages
│   ├── pages/                             # transactional document pages
│   ├── components/                        # module-local Blade components
│   └── partials/                          # module-local partials (_form, _table)
└── Migrations/                            # module's Laravel migrations (port of ERP.sql)
```

### Mapping of the requested folder concepts (React-isms → Laravel idioms)

The original module spec mentioned Dashboard, Catalogs, Pages, Components, Services, Hooks, Types, Validators, Utils, Routes. The mapping below is **the contract** — use these names.

| Spec concept | Laravel/Blade equivalent in SISEN |
|---|---|
| `Dashboard` | `Views/dashboard/` + a `DashboardController` per module |
| `Catalogs` | `Models/` (master entities) + `Views/catalogs/` |
| `Pages` | `Views/pages/` (transactional documents) |
| `Components` | `Views/components/` (module-local) + shared `resources/views/components/` |
| `Services` | `Services/` |
| `Hooks` | `Observers/` (Eloquent) and `Listeners/` (events) |
| `Types` | `Enums/` (state machines) + `Models/` (entity types) + `app/Modules/Shared/Contracts` |
| `Validators` | `Requests/` (Laravel FormRequest) |
| `Utils` | `Utils/` |
| `Routes` | `Routes/web.php`, `Routes/api.php` |

### Per-module mandated contents (skeleton level)

Each module folder ships with:

1. `Routes/web.php` — a skeleton file that only declares the route group placeholder (empty on purpose; the module team fills it). The provider already applies `auth` middleware at the group level.
2. `Routes/api.php` — placeholder for the JSON API group (empty; see *API Structure*).
3. Empty subfolders (`Controllers/`, `Models/`, `Services/`, `Requests/`, `Enums/`, `Observers/`, `Utils/`, `Views/...`, `Migrations/`) tracked with `.gitkeep`.
4. A short `README.md` per module explaining its boundaries and the tables it owns (auto-derived from this document).

---

# Shared Components

Shared components live in `resources/views/components/` (global) and are documented here. They are **mandatory** for all module views so the whole ERP looks like one product.

| Component | Purpose | Usage |
|---|---|---|
| `<x-card>` | Wrapper for the `soft-card` visual | every list/detail/form section |
| `<x-page-header>` | Title + subtitle + action buttons | top of every module page |
| `<x-badge>` | Status badge (color-mapped, like `partials/badge`) | every state column |
| `<x-stat-card>` | Dashboard KPI card | module dashboards |
| `<x-table>` | Standard table wrapper (responsive, striped header) | lists |
| `<x-empty>` | Empty-state placeholder for lists/tables | lists |
| `<x-filter-bar>` | Global filter bar (search + selects + date range + clear) | list pages |

Skeleton phase ships the first four (`card`, `page-header`, `badge`, `stat-card`) plus `table`, `empty`, `filter-bar` as shared, **already styled** components. Module-local components must extend these, never restyle Bootstrap globally.

### Component contract

- All components render Bootstrap 5 markup using the SISEN tokens (no inline colors).
- All components accept a `class` attribute merged onto the root element.
- All components are documented with a BladeDoc-style comment showing props.
- Components are **presentational** — they never query the database.

### Layout contract

- `resources/views/layouts/app.blade.php` is the single application shell (sidebar + topbar + content). Module pages use `@extends('layouts.app')` + `@section('content')`.
- The sidebar is extended with **menu groups** declared by each module. A shared `MenuRegistry` (see *Dashboard philosophy* / *Navigation*) lets modules register their menu entries without touching `app.blade.php`. (Skeleton provides the registry hook.)
- `partials.alerts` and `partials.badge` are kept for backwards compatibility with SISEN v1 views.

---

# Design Principles

1. **One visual language.** Bootstrap 5 components + SISEN CSS variables only. No ad-hoc hex colors, no per-module CSS files unless strictly needed (then: `public/css/modules/<module>.css`, imported in the module's views).
2. **Spacing scale (Bootstrap).** Use `g-3` grid gutters, `.p-3` card padding, `.mb-3` section gaps, `.gap-2/.gap-3` flex gaps. Never invent new spacing values.
3. **Typography.** Headings `h3/h4/h5 fw-bold`; labels `.form-label` (already bold); body default; muted text `.text-muted`/`.text-white-50`. Numeric columns right-aligned in tables, monospace not required.
4. **Cards.** `soft-card p-3` for every content block. Header rows inside cards use `d-flex justify-content-between align-items-center`.
5. **Tables.** `table align-middle`, `.table-responsive`, uppercase small thead (already in `sisen.css`), action buttons as `.btn btn-sm btn-outline-* action-btn` icon buttons, empty state via `x-empty`.
6. **Forms.** `row g-3`, `col-md-*` responsive fields, `form-label` + `form-control`/`form-select`, `@error ... is-invalid`, one `_form.blade.php` shared between create and edit, footer with `Cancelar` (outline-secondary) + `Guardar` (primary) buttons.
7. **Buttons.** Primary = create/save; outline-primary = secondary actions; outline-danger = delete/cancel document; outline-info = view/show.
8. **Status colors.** Reuse the existing badge color map (`badge-activo/pagada/aprobado/presente` → green; `inactivo/cancelada/rechazado/falta` → red; `pendiente/retardo` → amber; `permiso` → blue). Extend the map in `sisen.css` when new states appear — never invent colors in templates.
9. **Navigation.** Sidebar groups + active state via `request()->routeIs('module.*')`. Breadcrumbs via `x-page-header` subtitle (breadcrumb component added when a hierarchy appears).
10. **Feedback.** Flash messages only via `partials.alerts` (`success`/validation errors). Destructive actions confirm via `data-confirm`.

---

# UX Principles

1. **Every list page answers three questions in order:** *what's here?* (header + filters), *is there data?* (empty state), *can I act?* (actions).
2. **Create ≥ View ≥ Edit ≥ Delete** affordance hierarchy: always visible on list rows, destructive actions require confirmation.
3. **Search-first.** Every list has a `buscar` search box (name/code/RFC) plus module filters (status, date range). Search filters in the controller query, pagination keeps `withQueryString()`.
4. **Documents show a timeline.** Posted/approved/cancelled documents display their history (audit + activity) on the `show` page.
5. **Forms are progressive disclosure.** Required fields first; sections grouped logically; totals/dependencies computed client-side with `data-*` hooks (`data-payroll-calc` pattern) and recomputed server-side.
6. **Print-friendly.** All report/detail pages must render cleanly under the existing print CSS (`@media print` hides sidebar/topbar).
7. **Spanish UX copy, consistent tone.** Buttons: *Guardar*, *Cancelar*, *Buscar*, *Nuevo*, *Ver todos*, *Imprimir*. Errors: *Revisa los campos marcados.* Confirmation: *¿Estás seguro de eliminar …?*
8. **Mobile.** Sidebar collapses (existing `data-sidebar-toggle`); tables scroll horizontally; stat cards stack 2/4 per row.
9. **Keyboard & a11y:** labels tied to inputs, `required` attributes, focus styles from Bootstrap, contrast at AA for text on SISEN blue.

---

# Folder Organization

```
ERP-FINAL/
├── app/
│   ├── Http/                     # KEEP as-is (v1 controllers live here until migrated)
│   ├── Models/                   # KEEP as-is (v1 models)
│   ├── Middleware/               # KEEP (RoleMiddleware) + add PermissionMiddleware
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── ModuleServiceProvider.php   # NEW: autoloads app/Modules/*
│   └── Modules/                  # NEW: one folder per module (see Module Architecture)
│       ├── Shared/
│       ├── HR/
│       ├── Finance/
│       ├── Sales/
│       ├── Purchasing/
│       ├── Inventory/
│       └── CRM/
├── bootstrap/providers.php       # register ModuleServiceProvider
├── config/                       # shared config
├── database/
│   ├── migrations/               # KEEP (v1 migrations) — new tables live in module folders
│   └── seeders/                  # KEEP
├── docs/
│   └── PLANNING.md               # THIS DOCUMENT
├── public/
│   ├── css/sisen.css             # design tokens + global styles (extend, don't fork)
│   ├── css/modules/              # (optional) module CSS
│   └── js/sisen.js               # global behaviors (keep, extend)
├── resources/views/
│   ├── layouts/app.blade.php     # single shell
│   ├── components/               # shared components (NEW)
│   └── partials/                 # v1 partials (keep)
├── routes/
│   ├── web.php                   # KEEP (v1 routes) — modules self-register
│   └── api.php                   # NEW: global API bootstrap if needed
├── ERP.sql                       # target PostgreSQL schema
└── composer.json                 # PSR-4: "App\\Modules\\" => "app/Modules/"
```

### Rules

- **One module = one folder = one namespace `App\Modules\<Module>`.**
- Modules never import each other's *views or routes*; they import each other's *models* only through the owning module (e.g. Sales reads `App\Modules\Inventory\Models\Product`). Cross-module table access goes through the owning module's models/services.
- v1 code (`app/Http/Controllers`, `app/Models`, `resources/views/{empleados,...}`) is **frozen in place**. Teams port functionality into their module progressively; nothing is deleted until a feature parity milestone is approved.

---

# Coding Standards

## PHP / Laravel

- **Laravel 12, PHP 8.2+.** Strict types declared in new files (`declare(strict_types=1);`).
- **Pint** (`laravel/pint`) is the formatter. Run `./vendor/bin/pint` before committing. `.editorconfig`: 4-space indent, LF.
- **Style:** Laravel's default (PSR-12 flavored). Readable > clever. No comments that restate the code; comments only for intent, math, or business rules.
- **Controllers:** thin. They parse the request, call one Service, redirect back with flash. Validation lives in FormRequests; queries for lists live in the controller (simple) or in the model's scopes (complex).
- **Services:** one class per aggregate operation (`PayrollRunService`, `InvoicePostingService`). Services are static-free, dependency-injected, and return typed results.
- **Models:** `$fillable`, `$casts`, relationships, scopes for query reuse. Keep v1 accessor pattern (`getXAttribute()`).
- **Enums:** PHP `enum` (backed by string/int) for every state field. Enums define `label()` (Spanish) and optional `color()` used by `<x-badge>`.
- **FormRequests:** one per mutation (`StoreSaleOrderRequest`, `UpdateSaleOrderRequest`). Rule arrays only; no logic.
- **Observers:** document numbering (`document_sequences`), totals recomputation, default values. Never in controllers.
- **No `env()` calls outside `config/`.** Config values accessed via `config('sisen.*')`.
- **No raw SQL in controllers.** Queries via Eloquent; complex reporting via DB views (ported from `ERP.sql`).

## Blade

- Templates are presentational. No `@php` blocks doing logic — use view models/composers or pass prepared data from the controller.
- Use shared components (`x-card`, `x-badge`, `x-page-header`, `x-table`, `x-empty`, `x-filter-bar`).
- Every list page: search form (`name="buscar"`), filters, table, `x-empty`, `{{ $rows->links() }}`.
- Every form page: `@extends('layouts.app')`, `x-card` with `_form.blade.php` include, `@error ... is-invalid`.
- Spanish UI text, consistent copy (see *UX Principles*).

## JavaScript (public/js/sisen.js)

- Vanilla JS only, `DOMContentLoaded`, progressive enhancement via `data-*` attributes.
- New behaviors register like `data-payroll-calc` does: a generic `[data-…]` hook + a small handler. No jQuery, no new front-end framework unless a dedicated RFC is approved.

## Git / commits

- One concern per commit; imperative mood; conventional prefix optional. Do not commit secrets (see `.gitignore`).
- Branch per module (`feat/finance-...`, `feat/sales-...`). Merge to `David` (current default branch) via PR after tests pass.

---

# Naming Conventions

## Code

| Thing | Convention | Example |
|---|---|---|
| Namespace | `App\Modules\<Module>` | `App\Modules\Sales` |
| Class (model) | Singular, StudlyCase, English | `SalesOrder`, `StockMovement` |
| Controller | `<Entity>Controller` | `SalesOrderController` |
| FormRequest | `Store/Update + Entity + Request` | `StoreSalesOrderRequest` |
| Service | `<Operation>Service` | `InvoicePostingService` |
| Enum | Singular noun, StudlyCase | `SalesOrderStatus`, `DocumentType` |
| Trait | Adjective, `…able` | `HasAuditFields` |
| Interface | Adjective/`able` | `Auditable` |
| Method (service) | verb, camelCase | `post(Invoice $invoice): Invoice` |
| Route name | `module.resource.action` | `sales.orders.index` |
| Route param | camelCase model name | `{order}`, `{salesOrder}` |

## Database (maps to `ERP.sql` and to Laravel migrations)

| Thing | Convention | Example |
|---|---|---|
| Table | plural snake_case, English in target schema | `sales_orders` |
| Column | snake_case | `order_number` |
| FK column | `<singular_table>_id` | `customer_id` |
| PK | `id BIGSERIAL` (Postgres) / `$table->id()` (Laravel) | `id` |
| Audit columns | fixed set, see *Database Standards* | `created_by` |
| State columns | `status` or domain word (`estado` kept in v1 tables) | `status`, `estado` |
| Money | `NUMERIC(18,2)` / `decimal(18,2)` | `total_amount` |
| Rates | `NUMERIC(18,6)` / `decimal(18,6)` | `tax_rate` |

## Route/URL (browser)

- Base URL per module = module slug: `Sales → /sales`, `SalesOrder → /sales/orders`.
- Actions: `index/create/show/edit` (REST) + verb for custom (e.g. `POST /sales/orders/{order}/post`, `POST /sales/orders/{order}/cancel`).
- Route name pattern: `module.entities.action` (e.g. `sales.orders.post`, `sales.orders.cancel`).

## Views

- Module views under `app/Modules/<Module>/Views/<kind>/` named `index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`, `_form.blade.php`, `_table.blade.php`, `_filters.blade.php`.
- View namespace: `<camelModule>::kind.file` (e.g. `sales::pages.orders.index`).

---

# Database Standards

> El esquema vive en las migraciones de `app/Modules/<Modulo>/Migrations/`.
> `ERP.sql` es su reflejo generado (`php artisan sisen:esquema`) y nunca se
> importa. Todas las migraciones usan los ayudantes de
> `App\Modules\Compartido\Support\EsquemaErp`, que son los que hacen cumplir lo
> de abajo sin que cada equipo tenga que recordarlo.

## Conjunto de columnas universal (toda tabla de negocio)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` | `$tabla->id()` |
| `created_at` / `updated_at` | `TIMESTAMP NULL` | nombre de Laravel, igual que en v1 |
| `deleted_at` | `TIMESTAMP NULL` | borrado logico |
| `creado_por` | `BIGINT NULL -> users(id)` | lo llena el trait `TieneCamposAuditoria` |
| `actualizado_por` | `BIGINT NULL -> users(id)` | lo llena el trait `TieneCamposAuditoria` |
| `version_fila` | `INT NOT NULL DEFAULT 1` | bloqueo optimista, solo en documentos principales |

Se usa `EsquemaErp::auditoria($tabla)` para las cinco primeras y
`EsquemaErp::versionFila($tabla)` para la ultima. Tres clases de tabla quedan
fuera de la regla, y la prueba `EsquemaTest` conoce la lista: las del framework
o pivote, las de solo insercion (que llevan su propio actor: `aplicado_por`,
`subido_por`, `autor_id`) y las tablas hijas, que heredan la auditoria del padre.

## Rules

1. **Normalization:** 3NF. No duplicate master data; relationships via FKs. Lookup/catalog tables for enums that carry extra attributes; simple fixed enumerations become `CHECK` constraints.
2. **FKS:** every FK `ON DELETE` explicitly chosen — `RESTRICT` for protected history, `CASCADE` for ownership composition, `SET NULL` for optional references (e.g. user).
3. **Constraints:** `CHECK` for value ranges and cross-field rules (`EsquemaErp::check()`). `UNIQUE` on natural keys. Para que una fila con borrado logico no bloquee reutilizar un codigo o un correo se usa `EsquemaErp::unicoActivo()`: MariaDB no tiene indices parciales, asi que el equivalente es una columna generada virtual `<columna>_activo` que vale NULL en las filas borradas — y dos NULL nunca chocan en un indice unico — con el indice unico encima. La prueba `EsquemaTest` verifica ese comportamiento de punta a punta.
   > Cuidado con una restriccion real de MariaDB: una columna que participa en una llave foranea `ON DELETE SET NULL` **no puede** aparecer en un `CHECK`. Cuando choquen, la regla se aplica en el Service (asi ocurre con "un prospecto convertido debe apuntar a un cliente").
4. **Money:** `NUMERIC(18,2)`; rates `NUMERIC(18,6)`; stock quantities `NUMERIC(18,6)` unless integer pieces. Never `DOUBLE PRECISION` for money.
5. **Document numbering:** never auto-increment visible numbers. Use `document_sequences` (prefix + zero-padded counter) incremented atomically in an Observer.
6. **Soft delete:** only for master data and cancellable documents. Posted financial records are never deleted — they are voided/cancelled with a reversing entry.
7. **Indexes:** index every FK; index every `status` used in filters; index every column used in `WHERE ... LIKE` search as `pg_trgm` GIN in PostgreSQL (or `%term%` compatible index in MySQL).
8. **Enums:** PostgreSQL: `CREATE TYPE` is allowed, but **`CHECK` constraints are preferred** for portability between PostgreSQL and MySQL. Eloquent casts strings.
9. **Views (reporting):** derived reports are PostgreSQL views (`v_general_ledger`, `v_trial_balance`, `v_income_statement`, `v_balance_sheet`, `v_stock_levels`, `v_sales_funnel`, `v_customer_history`). No denormalized report tables.
10. **Multi-company:** `organizations` table exists; `company_id` added to core transactional tables when multi-company becomes a requirement (currently nullable to avoid breaking single-company flow).
11. **Laravel port:** every ERP.sql table gets an equivalent migration + Model + `HasAuditFields` trait usage. Table/column names in migrations match `ERP.sql`.

---

# Security

1. **Transport & session:** HTTPS in production, `SESSION_SECURE_COOKIE=true` when behind TLS, `SESSION_DRIVER=database`, `SESSION_LIFETIME=120`.
2. **Auth:** session-based (existing `AuthController` kept). Password hashing: Bcrypt rounds 12 (already configured). `remember_token` preserved. Account lockout after 5 failed attempts (config `sisen.auth.max_attempts`).
3. **Authorization:** two layers:
   - *Coarse:* `role` middleware on routes (`role:Administrador,Contador`). Keeps working in v1.
   - *Fine:* permission checks in controllers/services via `can('finance.invoices.post')` using the new permission model (see *Roles & Permissions*). A `PermissionMiddleware` (`permission:code`) is added for route-level enforcement of the new model.
4. **Input:** FormRequests whitelist fields; never trust request arrays. `mass_assignment` protected by `$fillable`. File uploads validated by extension+MIME+size and stored on the `public` disk under module folders.
5. **Output:** Blade escapes by default. `{!! !!}` forbidden unless the value is server-generated safe HTML. Reports use `number_format`/`money()` helpers.
6. **Data access:** list queries are scoped by role (existing pattern: employee only sees own records). Service-layer authorization helper `authorize()` on documents checks `created_by`, assigned owner and role.
7. **Secrets:** nothing in source. `.env` holds credentials; `.gitignore` already excludes it. Config via `config/sisen.php`.
8. **Audit & tamper:** every create/update/delete writes `audit_logs` (see *Audit Logs*). Critical documents use `row_version` optimistic locking to prevent lost updates.
9. **Rate limiting:** login route behind `throttle:5,1`.

---

# Roles & Permissions

## Model (target, defined in `ERP.sql`)

- `roles` — e.g. `Administrador`, `Recursos Humanos`, `Contador`, `Empleado` (v1) plus future `Ventas`, `Compras`, `Almacen`.
- `permissions` — atomic capabilities, coded as `<module>.<entity>.<action>` e.g. `finance.invoices.post`, `sales.orders.cancel`, `inventory.stock.view`.
- `role_permissions` — many-to-many role ↔ permission.
- `user_roles` — many-to-many user ↔ role (replaces the single `users.role` column going forward).

## Backwards compatibility

- The v1 `users.role` column stays. `User::hasAnyRole()` is kept and enriched: it returns true if the user has any of the requested role *names* via `user_roles`, **or** matches the legacy `users.role` column. This keeps v1 views/routes working untouched while new modules adopt the new model.
- New modules use **permissions**, not roles, for route-level checks (`permission:` middleware) and service-level checks (`$user->can('...')`).

## Roles to seed (not fake data — required baseline)

| Role | Legacy equivalent | Key permissions granted |
|---|---|---|
| `Administrador` | Administrador | all |
| `Recursos Humanos` | Recursos Humanos | HR.* |
| `Contador` | Contador | Finance.* , HR payroll view, reports |
| `Empleado` | Empleado | own records (HR view, own payroll/attendance) |
| `Ventas` | — | Sales.* (own pipeline), CRM.* |
| `Compras` | — | Purchasing.* |
| `Almacenista` | — | Inventory.* |

## Authorization flow (new code)

```
Route → auth → (role middleware | permission middleware) → Controller
        → FormRequest validates → Service checks fine-grained permission
        → action executes → audit_logs row written
```

---

# Notifications

- Table `notifications` (DB driver): `user_id`, `type`, `title`, `body`, `data JSONB`, `read_at`.
- Sources: document status changes (quote approved, order posted, bill due), leave approval, low stock alerts, payroll processed, comment mentions.
- Channels: in-app (mandatory) + Laravel mail (when MAIL_MAILER configured). Notifications are **persisted first**, delivered after.
- UI: a bell icon in the topbar with unread count (shared component `x-notifications`), and a dedicated `/notifications` page. Mark-as-read API endpoint per module's `api.php`.
- Pattern: dispatch an event (`SalesOrderPosted`) → listener writes notification + (optionally) email. Modules raise events; the notification layer is shared.

---

# Audit Logs

- Table `audit_logs`: `user_id`, `module`, `action` (`created|updated|deleted|posted|approved|cancelled|…`), `entity_type`, `entity_id`, `old_values JSONB`, `new_values JSONB`, `ip_address`, `user_agent`, `created_at`.
- Written by the shared `HasAuditTrail` trait (Eloquent observer) on every business table that uses it. Never in controllers.
- Document lifecycle actions (post/approve/cancel) log a semantic action even when the row itself barely changes.
- UI: the `show` page of any document renders a timeline from its audit rows (`x-timeline` component).
- Retention: kept indefinitely; `audit_logs` is append-only (no update/delete routes).

---

# Attachments

- Table `attachments`: polymorphic (`model_type`, `model_id`), `disk`, `path`, `original_name`, `mime_type`, `size_bytes`, `uploaded_by`, `created_at`.
- Storage: `public` disk by default (config `sisen.attachments.disk`). Invoices XML/PDF → subfolder by module (`finance/invoices/…`). Employee docs → `hr/documents/…`.
- Uploads go through a shared `AttachmentService` (MIME whitelist, size limit, virus scan hook) — never in controllers.
- UI: `x-attachments` component on document `show` pages (upload, list, download, delete). Downloads require authorization on the parent model.
- Rules: attachments are hard-deleted only when the parent is soft-deleted (cascade cleanup job); posted-document attachments are immutable (verified by `posted_at`).

---

---

# Dashboard Philosophy

- The dashboard is a **role-aware command center**, not a static chart dump. Each module contributes its own dashboard partial; the shell composes them by the user's roles/permissions.
- **Structure:** top row = KPI stat cards (`x-stat-card`, 4 per row, same style as v1); second row = quick actions (existing `quick-link` pattern); third row = lists/tables of recent documents.
- **KPI rules:** every KPI must be a real query, computed in the controller, never fabricated; every KPI links to its source list page.
- **MenuRegistry:** modules register their sidebar entries + dashboard partials in a shared registry (`App\Modules\Shared\Support\MenuRegistry`) consumed by `layouts.app`. Adding a module updates the sidebar automatically; no edit to the layout file.
- **Employee view:** role-aware scoping (v1 pattern — employee sees own data only) is the default for dashboards too.

---

# Reporting System

- **Report views in DB** (`v_*` views, see *Database Standards*): general ledger, trial balance, income statement, balance sheet, stock levels, sales funnel, customer history, payroll summaries. Teams query these with Eloquent/`DB::table`, add filters, and render HTML with print CSS. CSV/PDF export via *Import/Export*.
- **Saved reports:** table `saved_reports` (name, module, type, config JSONB, is_shared, created_by). Users can bookmark a configured report.
- **Report list page pattern** (matches v1 `reportes`): index of available reports per module; each report has a `filters` row (date range, status, entity), a results table, a *Print* button, an *Export CSV* button.
- **Financial statements** must agree with the ledger by construction (they are queries over the same journal data). No manual entry.
- **Naming:** reports are routes `module.reports.<name>` (e.g. `finance.reports.trial-balance`).

---

# Search System

- **Global search:** a topbar search box (shared `x-global-search`) that queries a searchable subset across modules (customers, suppliers, products, employees, orders, invoices) using a shared `SearchService` with per-module providers registered in the registry. Results grouped by module; each result links to the document's `show` page.
- **Per-list search:** every list page has `name="buscar"` doing `ILIKE %term%` on the display columns (v1 pattern, kept).
- **Advanced filters** are per-module (`_filters.blade.php` partial): status, date range, entity FK, min/max amounts. Filters always `->withQueryString()`.
- PostgreSQL: `pg_trgm` GIN indexes on searched text columns for scalable `%term%`.

---

# Global Filters

- Shared `<x-filter-bar>` component renders: search input, select dropdowns (from controller-provided options), date range inputs, a *Filtrar* button and a *Limpiar* (clear) button.
- Filter contract: controller reads named request params (`buscar`, `estado`, `desde`, `hasta`, `*_id`), applies `->when(...)` clauses (v1 pattern), returns the filtered query + current filter values to the view so the bar repopulates.
- Date ranges: `desde`/`hasta` on date columns; inclusive bounds (`whereDate('x','>=',$desde)`).
- Filter values are validated (in, date_format, exists) before hitting the query builder.

---

# Import/Export

- **Export:** shared `ExportService` (CSV today; PDF via report views + print CSS). Every report/list page gets an *Exportar CSV* button. Exports stream to disk and download; filename `<module>-<report>-<yyyymmdd>.csv`.
- **Import:** shared `ImportService` (CSV) with a template download, column validation, dry-run preview, and a summary of created/updated/skipped rows. Imports are staged (`import_batches`) before commit so errors never corrupt master data.
- Import scope (baseline): products, customers, suppliers, chart of accounts, employees. Document imports (orders/invoices) are **out of scope** until v2.

---

# Catalogs

- **Catalog = master data.** Catalog tables follow the `catalogs` shared pattern: `code` (unique), `name`, `description`, `is_active`, `sort_order`, audit fields.
- Two flavors:
  1. **Generic lookups** (`catalogs` table: `group` = e.g. `payment_terms`, `payment_method`, `currency`, `contract_type`) — used when a value list is needed across modules.
  2. **Domain catalogs** (own tables, e.g. `product_categories`, `chart_of_accounts`, `warehouses`) — when the entity carries its own attributes/relationships.
- Rule: a value that appears in two modules is either a shared catalog or owned by one module and referenced. Never inline free-text for codes.
- Catalog pages follow the module structure (`Views/catalogs/`) with the standard list/create/edit views; only users with the corresponding `*.*.manage` permission can edit catalogs.

---

# Configurations

- **App settings:** `settings` table (`group`, `key`, `value`, `is_json`). Groups: `company`, `finance`, `inventory`, `sales`, `notifications`, `security`.
- **Config access:** `config('sisen.*')` loads static defaults; dynamic settings read via `Settings` facade (cached, busted on write).
- **Per-module config:** each module may expose a settings page under `/<module>/settings` (permission `module.settings.manage`). Examples: Finance (fiscal period, default accounts), Inventory (default warehouse, reorder emails), Sales (default price list, order statuses), HR (payroll run day).
- No module writes global settings without a permission; the `Administrador` group is the owner of `settings`.

---

# Future Integrations

The architecture reserves integration points; nothing is wired yet.

| Integration | Hook | Owner |
|---|---|---|
| SAT / CFDI electronic invoicing | `electronic_invoices` table + `InvoiceIssuanceService` (posting/void hooks) | Finance |
| Payment gateways (STP, SPEI) | `customer_payments`/`supplier_payments` `method` + `reference`; webhook event `PaymentReceived` | Finance |
| E-commerce (Woocommerce/Magento) | Sales `api.php` endpoints (`POST /api/sales/orders`) | Sales |
| Barcode hardware / POS | `product_barcodes` + Inventory `api.php` stock lookups | Inventory |
| Email/calendar | Notification channel + HR `leave_requests` events | Shared / HR |
| BI / Power BI | Read-only PostgreSQL replica + `v_*` views | Shared |
| Single Sign-On | `users` + `permissions` model; auth events | Shared |
| Mobile / REST API | Global `api/*` structure below | Shared |

All integrations go through module Services and events — never through direct SQL from outside.

---

# API Structure

- **Two surfaces:** browser (`Routes/web.php`) and JSON API (`Routes/api.php`), both auto-registered per module.
- JSON API prefix: `/api/<module-slug>`. Auth: session cookie for same-origin; Sanctum tokens added when an external consumer appears. Every endpoint returns JSON error envelopes `{ "message": … , "errors": … }`.
- API pattern (Laravel resource style): `GET /api/finance/invoices`, `GET /api/finance/invoices/{id}`, `POST/PATCH/DELETE`, plus action endpoints `POST /api/finance/invoices/{id}/post`.
- API controllers live in `app/Modules/<Module>/Controllers/Api/<Entity>ApiController.php`; they reuse the same FormRequests + Services as the web layer (no duplicated logic).
- All API routes are permission-guarded (new permission model).

---

# Testing Strategy

- **Framework:** PHPUnit 11 (existing). `composer test` runs the suite after `config:clear`.
- **Tiers:**
  1. **Unit** — Services with fakes (faker, es_MX), calculators (`Nomina::calcularTotal` pattern extended), enums, Utils.
  2. **Feature** — one test file per resource flow: index (role scoping + search), create/store validation, update, destroy (soft delete), document lifecycle (post/cancel), permissions (403 paths).
  3. **Database** — migrations/port sanity: schema matches `ERP.sql` names; FK constraints enforce rules; `document_sequences` numbering increments.
- **Convention:** tests live in `tests/Feature/Modules/<Module>/…`, mirroring module structure. Factories in `database/factories/Modules/<Module>/…`.
- **Minimum bar:** every new route must have at least one feature test proving auth + authorization. No test = not done.
- **CI:** run Pint + `composer test` on push (GitHub Actions added in a follow-up); treat warnings as failures for new code.

---

# Deployment

- **Environment:** single instance monolith. Servers: web (Nginx + PHP-FPM 8.2), DB (PostgreSQL target, MySQL today), queue worker (`queue:listen` for jobs/notifications), scheduler (cron: `schedule:run` for reorder alerts, fiscal closures, payroll reminders), Vite build artifacts.
- **Pipeline (recommended):** `composer install --no-dev --optimize-autoloader` → `npm ci && npm run build` → `php artisan migrate --force` → `php artisan config:cache / route:cache / view:cache` → restart FPM/queue.
- **Env:** `.env` per environment; `APP_ENV`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, DB credentials, `MAIL_*`.
- **Backups:** nightly PostgreSQL/MySQL dump + storage (`public` attachments) snapshot. Restore drill quarterly.
- **Zero-downtime:** migrations additive-only in a release (new tables/columns; no destructive DDL until a v2 plan). Soft delete keeps data.
- **Monitoring:** Laravel logs (`LOG_LEVEL`), `php artisan pail` locally, Sentry/New Relic hook documented for later.

---

---

# CORE MODULE 1 — Finance & Accounting

**Folder:** `app/Modules/Finance` · **Route prefix:** `/finance` · **Table owner of:** chart_of_accounts, journal_entries, journal_entry_lines, fiscal_periods, cost_centers, budgets, budget_lines, bank_accounts, bank_transactions, bank_reconciliations, reconciliation_lines, taxes, electronic_invoices, currency_exchange_rates, saved_reports (finance scope). **Views owned:** `v_general_ledger`, `v_trial_balance`, `v_income_statement`, `v_balance_sheet`.

## 1.1 Chart of Accounts (`chart_of_accounts`)

- Self-referencing tree (`parent_id`), `code` (unique), `name`, `account_type` in `asset|liability|equity|revenue|expense|contra_asset|contra_liability|contra_equity|contra_revenue|contra_expense`, `normal_balance` (`debit|credit`), `is_header`, `allow_transactions`, `is_active`.
- Only non-header accounts accept journal lines. Balance sheets/income statements derive from the tree (`v_*` views).
- CATALOG management under `Finance/Views/catalogs/`; CRUD permission `finance.coa.manage`.

## 1.2 Journal Entries (`journal_entries` + `journal_entry_lines`)

- Header: `entry_number` (from `document_sequences` prefix `JE-`), `fiscal_period_id`, `entry_date`, `description`, `reference`, `source_type` (`manual|invoice|payment|payroll|credit_note|vendor_bill|closing|reconciliation|adjustment`), `source_id`, `status` (`draft|posted|void`), `total_debit`, `total_credit`, `posted_by/at`, `void_reason`.
- Lines: `account_id`, `fiscal_period_id`, `cost_center_id`, `description`, `debit`, `credit`; `CHECK (debit = 0 AND credit > 0) OR (debit > 0 AND credit = 0)`.
- **Posting service** (`JournalPostingService`): validates balance (Σ debit = Σ credit), period open, accounts non-header; writes audit + sets `posted`; generates the `entry_number` on create (draft) — number is reserved at creation.
- **Void:** only a posted entry; requires `void_reason`; creates a reversing entry (or marks both, per policy config). Never deletes posted entries.
- Integration hooks: Sales invoices, payments, vendor bills, payroll runs, bank reconciliation all produce journal entries via this service (they reference `source_type`/`source_id`).

## 1.3 General Ledger (`v_general_ledger` view)

- One row per posted journal line: entry date, entry number, account code/name, cost center, debit, credit, running balance (window function), source.
- Filters: date range, account, cost center, entry number. Read-only.

## 1.4 Trial Balance (`v_trial_balance` view)

- For a fiscal period/date range: per account — opening, debit total, credit total, closing balance, normal-balance sign check. Used to prove the books before closing.

## 1.5 Balance Sheet (`v_balance_sheet` view)

- Aggregates asset/liability/equity accounts from trial balance at a given date, grouped by account type → standard BS layout. Equity includes current period result (links to income statement).

## 1.6 Income Statement (`v_income_statement` view)

- Revenue − expense accounts grouped by type/cost center for a period; includes gross profit, operating result, net result. Periods comparable (current vs previous).

## 1.7 Cash Flow (`v_cash_flow` view)

- Operating / investing / financing classification derived from journal source types + bank accounts (baseline: operating from income/expense entries; refinements per team).

## 1.8 Fiscal Periods (`fiscal_periods`)

- `name`, `year`, `start_date`, `end_date`, `status` (`open|closed|locked`), `closed_by/at`.
- Rules: entries post only to open periods; `journal_entry_lines` carry their own `fiscal_period_id` so adjustments can be reposted; closing locks the period for regular postings.
- Closing process creates a closing entry (retained earnings) via `ClosingService`.

## 1.9 Closing Process

1. Verify all subledgers posted for the period (invoices, bills, payroll, payments).
2. Generate closing journal entry (P&L → retained earnings).
3. Mark period `closed`; reopen only with Admin permission + audit.

## 1.10 Cost Centers (`cost_centers`)

- Self-referencing tree, `code`, `name`, `is_active`. Optional dimension on journal lines for profit-center reporting (income statement by cost center).

## 1.11 Budgets (`budgets` + `budget_lines`)

- Header per fiscal period + cost center + name; lines per account + month with `projected_amount`, `actual_amount` (filled from posted lines on read). Budget vs actual report = `v_budget_vs_actual` (team-implemented view).

## 1.12 Bank Accounts (`bank_accounts`)

- `name`, `bank_name`, `account_number`, `account_type` (`checking|savings|credit_card`), `currency`, `opening_balance`, `is_active`. Referenced by payments/reconciliation.

## 1.13 Reconciliations (`bank_transactions`, `bank_reconciliations`, `reconciliation_lines`)

- Import bank statement lines → `bank_transactions` (`statement_date`, `description`, `amount`, `status`). `bank_reconciliations` per account+period; `reconciliation_lines` match bank transaction ↔ journal entry (`matched_at`, `by_user`). Unmatched transactions create `bank_fees`/adjustment entries (config).

## 1.14 Taxes (`taxes`)

- `code` (e.g. `IVA16`), `name`, `rate NUMERIC(18,6)`, `type` (`vat|withholding|stamp_duty|other`), `is_active`. Tax lines on documents reference `taxes.id`; tax report = `v_tax_report` (per period, per tax code).

## 1.15 Electronic Invoices (`electronic_invoices`)

- CFDI-oriented: `series`, `folio` (from `document_sequences` `CFDI-`), `document_type` (`factura|credit_note|debit_note|trash`), `uuid`, `xml_path`, `pdf_path`, `status` (`generated|stamped|voided|cancelled`), `stamp_response JSONB`, `cancelled_at`, `cancellation_uuid`. Linked to sales invoice / credit note (`model_type`/`model_id`). Wired to SAT integration later via `InvoiceIssuanceService`.

## 1.16 Financial Reports

- Routes `finance.reports.*`: `ledger`, `trial-balance`, `balance-sheet`, `income-statement`, `cash-flow`, `tax-report`, `budget-vs-actual`. All read-only views + filters + print/CSV. Permissions `finance.reports.view`.

## 1.17 Finance dashboard

- KPI cards: cash position, AR/AP totals, unpaid invoices/bills count, period result; recent journal entries table; quick links (new entry, close period, reconcile). Permission-aware.

**Team deliverable checklist (Finance):** port ERP.sql finance tables → migrations; models + enums + `HasAuditTrail`; FormRequests; `JournalPostingService` + `ClosingService` + `ReconciliationService`; v1-compatible views under `Finance/Views`; feature tests; register menu + dashboard partial.

---

# CORE MODULE 2 — Sales

**Folder:** `app/Modules/Sales` · **Route prefix:** `/sales` · **Owns:** customers, price_lists, price_list_items, sales_quotes (+lines), sales_orders (+lines), sales_invoices (+lines), credit_notes (+lines), customer_payments, opportunities report views. **Depends on:** Inventory (products), Finance (journal posting for invoices/payments), Shared.

## 2.1 Customers (`customers`)

- `code`, `name`, `legal_name`, `tax_id` (RFC), `email`, `phone`, `address`, `credit_limit`, `payment_term_id`, `currency`, `status` (`active|inactive`), audit fields.
- Owned by Sales. CRM references it for opportunities/customer history. `v_customer_history` view (Sales reports) aggregates quotes/orders/invoices/payments/returns per customer.

## 2.2 Quotes (`sales_quotes` + `sales_quote_lines`)

- `quote_number` (seq `COT-`), `customer_id`, `price_list_id`, `quote_date`, `valid_until`, `status` (`draft|sent|accepted|rejected|converted|cancelled`), line items (`product_id`, `description`, `quantity`, `unit_price`, `discount_rate`, `tax_id`, `tax_amount`, `subtotal`, `total`), header totals.
- Convert quote → order: `convertToOrder` copies lines, reserves nothing (stock reserved at order post). Totals recomputed in model observer.

## 2.3 Sales Orders (`sales_orders` + `sales_order_lines`)

- `order_number` (seq `SO-`), `quote_id`, `customer_id`, `price_list_id`, `order_date`, `expected_date`, `status` (`draft|confirmed|fulfilled|invoiced|partially_invoiced|cancelled`), totals; lines reference products + requested warehouse.
- Lifecycle: create (draft) → `confirm` (posts stock reservation via Inventory `ReserveStockService`) → fulfill (goods issue) → invoice. Cancel only while draft/confirmed; posted orders void via reversing movement.

## 2.4 Sales Invoices (`sales_invoices` + `sales_invoice_lines`)

- `invoice_number` (seq `FC-`), `sales_order_id` (nullable for on-demand), `customer_id`, `fiscal_period_id`, `issue_date`, `due_date`, `payment_term_id`, `status` (`draft|issued|partially_paid|paid|overdue|cancelled`), totals, `electronic_invoice_id`.
- `InvoiceIssueService`: validates order/fulfillment, posts sales revenue + tax + AR journal entry (via Finance `JournalPostingService`, `source_type=invoice`), issues CFDI placeholder (Finance), sets `issued`.
- Overdue = issued with `due_date < today` and unpaid — derived in `v_customer_history`/dashboard, not stored.

## 2.5 Credit Notes (`credit_notes` + `credit_note_lines`)

- `credit_note_number` (seq `NC-`), references `sales_invoice_id`, reason (`return|discount|error|other`), status (`draft|issued|cancelled`). Posting reverses revenue/tax/AR and (if `return`) calls Inventory goods return. Total credit limit per invoice enforced.

## 2.6 Payments (`customer_payments`)

- `payment_number` (seq `PAG-`), `customer_id`, `invoice_id` (nullable on-account), `payment_date`, `amount`, `method` (`cash|transfer|check|card|payment_link`), `reference`, `bank_account_id`, `status` (`draft|posted|cancelled`). Posting applies to invoice(s) and journals AR cash receipt. On-account payments later allocated via `PaymentAllocationService`.
- Partial payments allowed; invoice status derived (`partially_paid` when sum(allocations) < total).

## 2.7 Discounts

- Line-level `discount_rate` (percent) or `discount_amount`; header-level `header_discount` (catalog `discount_reason`). Constraints: discount ≤ 100%, authorization permission `sales.discounts.override` for rates above threshold (config `sisen.sales.max_discount`).

## 2.8 Price Lists (`price_lists` + `price_list_items`)

- Header (`code`, `name`, `currency`, `is_default`); items (`product_id`, `min_quantity`, `price`). Price resolution: customer default → price list → product sale_price. `PriceResolverService` shared with quotes/orders; effective price always captured in line `unit_price` snapshot (price changes never rewrite issued docs).

## 2.9 Sales Pipeline (`v_sales_pipeline` view)

- Aggregates CRM `opportunities` by stage/owner/expected close for the Sales dashboard (won amount, weighted forecast). The *funnel owner* is CRM; Sales reports on it. See CRM 6.2.

## 2.10 Sales Reports

- Routes `sales.reports.*`: `customers`, `orders`, `invoices`, `payments`, `pipeline`, `sales-by-product`. Filters: date range, customer, salesperson, status. All derived from posted documents (never approximations).

## 2.11 Sales dashboard

- KPIs: today's sales, open orders, AR overdue, pipeline value; quick links (new quote/order/invoice); recent invoices table.

**Team deliverable checklist (Sales):** port tables → migrations + models/enums; `PriceResolverService`, `InvoiceIssueService`, `PaymentPostingService`; FormRequests; views under `Sales/Views` (catalogs = customers/price lists; pages = quotes/orders/invoices/credit notes/payments); reports; feature tests; menu + dashboard partial.

---

---

# CORE MODULE 3 — Purchasing

**Folder:** `app/Modules/Purchasing` · **Route prefix:** `/purchasing` · **Owns:** suppliers, purchase_requests (+lines), purchase_orders (+lines), goods_receipts (+lines), vendor_bills (+lines), purchase_returns (+lines), supplier_payments. **Depends on:** Inventory (products/warehouses), Finance (vendor bill posting, payments), Shared.

## 3.1 Suppliers (`suppliers`)

- `code`, `name`, `legal_name`, `tax_id`, `contact_name`, `email`, `phone`, `address`, `payment_term_id`, `currency`, `status` (`active|inactive`), audit fields. Mirror of customers for the buy side.

## 3.2 Purchase Requests (`purchase_requests` + `purchase_request_lines`)

- `pr_number` (seq `PR-`), requester (user), `department_id`, `required_date`, `status` (`draft|submitted|approved|rejected|converted|closed`), lines (`product_id`, `quantity_requested`, `suggested_supplier_id`, `notes`).
- Approval flow: submit → approve/reject by approver (permission `purchasing.requests.approve`), audit trail of decision + comment.

## 3.3 Purchase Orders (`purchase_orders` + `purchase_order_lines`)

- `po_number` (seq `PO-`), `supplier_id`, `request_id` (nullable), `order_date`, `expected_date`, `currency`, `status` (`draft|sent|confirmed|received|partially_received|invoiced|cancelled`), totals; lines reference product, quantity, unit_cost, tax, warehouse destination.
- Convert PR → PO (aggregate multiple requests); confirm → send; receiving updates line `received_qty` (from goods receipts). Cancel only pre-receipt.

## 3.4 Goods Receipt (`goods_receipts` + `goods_receipt_lines`)

- `receipt_number` (seq `GR-`), `purchase_order_id`, `warehouse_id`, `received_at`, `status` (`draft|posted|cancelled`); lines reference PO lines, `quantity_received`, `location_id`.
- `GoodsReceiptService` posts stock in-movement (Inventory `stock_movements`, `movement_type='purchase'`), updates PO `received_qty`; over-receipt blocked by config tolerance.

## 3.5 Vendor Bills (`vendor_bills` + `vendor_bill_lines`)

- `bill_number` (seq `CB-`), `supplier_id`, `purchase_order_id`/`goods_receipt_id` (nullable), `bill_date`, `due_date`, `fiscal_period_id`, `status` (`draft|posted|partially_paid|paid|cancelled`), totals.
- `VendorBillService` posts AP + expense + input tax journal entry (`source_type='vendor_bill'`); three-way match (PO ↔ receipt ↔ bill) enforced with tolerance per line.
- Credit notes from supplier → `purchase_returns`.

## 3.6 Returns (`purchase_returns` + `purchase_return_lines`)

- `return_number` (seq `RD-`), `vendor_bill_id`, reason (`defective|wrong|excess|other`), status (`draft|posted|cancelled`). Posting reverses AP/expense and creates stock-out movement (`movement_type='return_out'`).

## 3.7 Supplier Payments (`supplier_payments`)

- `payment_number` (seq `PSP-`), `supplier_id`, `bill_id`, `payment_date`, `amount`, `method`, `reference`, `bank_account_id`, `status` (`draft|posted|cancelled`). Posts cash disbursement + AP application; partial payments supported.

## 3.8 Purchase Reports

- Routes `purchasing.reports.*`: `purchases-by-supplier`, `purchases-by-product`, `open-orders`, `vendor-bills`, `payments`. Filters: date range, supplier, status, warehouse.

## 3.9 Purchasing dashboard

- KPIs: open POs, pending receipts, AP balance/overdue, supplier count; quick links; recent receipts/bills.

**Team deliverable checklist (Purchasing):** port tables → migrations/models/enums; `GoodsReceiptService`, `VendorBillService`; FormRequests; views; reports; feature tests; menu + dashboard partial.

---

# CORE MODULE 4 — Inventory

**Folder:** `app/Modules/Inventory` · **Route prefix:** `/inventory` · **Owns:** warehouses, locations, product_categories, units_of_measure, products, product_barcodes, stock_movements, stock_transfers (+lines), stock_adjustments (+lines), lots, serial_numbers, inventory_counts (+lines), reorder_rules. **Views owned:** `v_stock_levels`. **Depends on:** Shared; consumed by Sales/Purchasing.

## 4.1 Warehouses & Locations (`warehouses`, `locations`)

- Warehouse: `code`, `name`, `address`, `is_active`. Location: `warehouse_id`, `code`, `name`, `is_pickable`, `is_active`. Stock is tracked at (product, location) granularity.

## 4.2 Products (`products`)

- `sku` (unique), `name`, `description`, `category_id`, `unit_id`, `cost_price`, `sale_price`, `min_stock`, `max_stock`, `is_sellable`, `is_purchasable`, `is_stockable`, `status` (`active|inactive`), `default_tax_id`.
- `product_barcodes`: multiple barcodes per product, `is_primary` flag (barcode/POS support).
- Product master is Inventory's; Sales/Purchasing reference it.

## 4.3 Categories & Units (`product_categories`, `units_of_measure`)

- Categories: self-referencing tree, `code`, `name`. Units: `code`, `name`, `base_ratio`, `is_base` (conversion to base unit). Quantity on movements stored in base unit.

## 4.4 Stock Movements (`stock_movements`) — the inventory ledger

- `product_id`, `warehouse_id`, `location_id`, `movement_type` (`purchase|sale|transfer_in|transfer_out|adjustment_in|adjustment_out|return_in|return_out|count|initial`), `quantity` (signed in base unit), `unit_cost`, `reference_type`/`reference_id` (link to PO/invoice/transfer/adjustment/count), `lot_id`, `serial_number_id`, `posted_at`, `posted_by`, status (`posted|cancelled`).
- **Every stock change is a movement; never direct quantity updates.** `v_stock_levels` computes on-hand per product/warehouse/location from movements. Cancelling a movement posts an equal-and-opposite movement (reversal), never deletes.
- Services consumed cross-module: `ReserveStockService` (Sales), `GoodsReceiptService` (Purchasing).

## 4.5 Transfers (`stock_transfers` + `stock_transfer_lines`)

- `transfer_number` (seq `TR-`), from/to warehouse, `status` (`draft|in_transit|received|cancelled`), `requested_by`, `approved_by`, `transferred_at`. Posting creates paired `transfer_out`/`transfer_in` movements (or `in_transit` location). Lines reference product + quantity + unit_cost.

## 4.6 Adjustments (`stock_adjustments` + `stock_adjustment_lines`)

- `adjustment_number` (seq `AJ-`), `reason`, `status` (`draft|posted|cancelled`), `approved_by`, `posted_at`. Lines set `quantity_difference`; posting creates `adjustment_in`/`adjustment_out` movements with the approved reason. Requires `inventory.adjustments.approve`.

## 4.7 Lots & Serial Numbers (`lots`, `serial_numbers`)

- Lots: `lot_number` (unique per product), `product_id`, `expiry_date`, `is_active`. Serial numbers: `serial_number`, `product_id`, `status` (`in_stock|sold|warranty|returned|scrapped`).
- Movements optionally capture `lot_id`/`serial_number_id` for full traceability (FIFO default; serial mandatory per product flag `track_serial` — future).

## 4.8 Inventory Counts (`inventory_counts` + `inventory_count_lines`)

- `count_number` (seq `INV-`), `warehouse_id`, `location_id`, `status` (`draft|in_progress|counted|adjusted|closed`), `counted_by`, `counted_at`, `closed_by`, `closed_at`.
- Lines: `product_id`, `expected_qty` (from `v_stock_levels` at start), `counted_qty`, `difference`. After approval, `InventoryAdjustmentService` posts adjustment movements from the differences; count closes.

## 4.9 Minimum Stock & Reorder Rules (`reorder_rules`)

- Per (product, warehouse): `min_quantity`, `max_quantity`, `reorder_quantity`, `lead_time_days`, `is_active`.
- Scheduled job (`reorder:check`): compares `v_stock_levels` vs rules → creates `purchase_requests` (via Purchasing) and sends notifications. Frequency via `routes/console.php` schedule.

## 4.10 Barcode Support

- `product_barcodes` scanned in transfers/adjustments/receipts; lookup by barcode in list pages (`buscar` matches barcode). POS-ready endpoint `GET /api/inventory/products/by-barcode/{barcode}`.

## 4.11 Inventory Reports

- Routes `inventory.reports.*`: `stock-levels`, `movements`, `valuation` (FIFO weighted cost), `expiring-lots`, `low-stock`, `counts-history`. Filters: warehouse, product, category, date range, movement type.

## 4.12 Inventory dashboard

- KPIs: total SKUs, units on hand, low-stock count, pending receipts, adjustment count; quick links; recent movements table.

**Team deliverable checklist (Inventory):** port tables → migrations/models/enums; `StockMovementService` (all movement types), `ReserveStockService`, `InventoryAdjustmentService`, `TransferService`, `CountService`; FormRequests; views (catalogs = products/warehouses/categories/units; pages = transfers/adjustments/counts/movements); `v_stock_levels` port + `reorder:check` command; feature tests; menu + dashboard partial.

---

# CORE MODULE 5 — Human Resources

**Folder:** `app/Modules/HR` · **Route prefix:** `/hr` · **Owns:** departments, positions, employees, attendance_records, leave_requests, payroll_periods, payroll_runs (+items), contracts, hr_documents, performance_reviews. **Depends on:** Shared; interfaces with Finance (payroll posting). **This module replaces/extends the v1 HR slice incrementally (v1 tables stay until parity).**

## 5.1 Employees (`employees`)

- `employee_number` (unique), personal data (names, gender, birth_date), contact, `department_id`, `position_id`, `manager_id` (self-ref → **organizational chart**), `hire_date`, `termination_date`, `contract_type`, `salary`, `currency`, `payment_frequency` (`weekly|biweekly|monthly`), tax/social ids, bank account (payment), `status` (`active|on_leave|terminated`), photo.
- Self-referencing `manager_id` + departments' `parent_id` render the org chart (shared `x-org-chart` component, level-based).

## 5.2 Departments & Positions (`departments`, `positions`)

- Departments: `code`, `name`, `parent_id`, `manager_id`, `description`, `status`. Positions: `department_id`, `code`, `name`, `min_salary`, `max_salary`, `description`, `status`. (v1 equivalents: `departamentos`, `puestos`.)

## 5.3 Attendance (`attendance_records`)

- `employee_id`, `attendance_date`, `check_in`, `check_out`, `status` (`present|absent|late|permission|holiday`), `worked_hours`, `notes`, `verified_by`. Bulk clock-in screen per team/department; late threshold config. (v1 equivalent: `asistencias`.)

## 5.4 Leave Requests (`leave_requests`)

- `employee_id`, `leave_type` (`vacation|permission|medical|maternity|paternity|unpaid`), `start_date`, `end_date`, `days`, `reason`, `status` (`pending|approved|rejected|cancelled`), `reviewed_by`, `reviewed_at`, `review_comment`.
- Approval flow feeds attendance (`permission`/`holiday`) and payroll (unpaid leaves → deduction). Balance per employee/type derived from approved history + policy config. (v1 equivalent: `permisos`.)

## 5.5 Payroll Preparation (`payroll_periods`, `payroll_runs`, `payroll_items`)

- `payroll_periods`: `period_code`, `start_date`, `end_date`, `payment_date`, `status` (`open|processed|closed`). Biweekly (quincena) default — matches v1.
- `payroll_runs`: `period_id`, `status` (`draft|processed|posted|cancelled`), `processed_by`, `approved_by`, `generated_at`.
- `payroll_items`: per employee — `basic_salary`, `bonuses`, `overtime`, `deductions`, `isr`, `imss`, `net_pay`, `status`. `PayrollRunService` computes items from attendance/leaves/salary config (keeps v1 `Nomina::calcularTotal` logic, generalized).
- Posting: `posted` run → Finance `journal_entries` (salary expense + payables + ISR/IMSS payable) via Finance service; bank file export optional.

## 5.6 Performance (`performance_reviews`)

- `employee_id`, `reviewer_id`, `review_period`, `score`, `strengths`, `improvements`, `goals JSONB`, `status` (`draft|submitted|acknowledged`), `reviewed_at`.

## 5.7 Documents (`hr_documents`) & Contracts (`contracts`)

- `hr_documents`: `employee_id`, `document_type` (`contract|id|tax|health|education|other`), title, attachment (via `attachments`), `expires_at`, `status`. 
- `contracts`: `employee_id`, `contract_type`, `start_date`, `end_date`, `salary`, `clause_summary`, `status`, `signed_at`. Active contract = current one with open end; history preserved.

## 5.8 Organizational Chart

- Rendered from `departments.parent_id` + `employees.manager_id`; drill-down per department; read-only for most, edit permission `hr.org.manage`.

## 5.9 HR Reports

- Routes `hr.reports.*`: `employees`, `attendance`, `leaves`, `payroll`, `contracts-expiring`, `headcount-by-department`. (Maps to v1 `reportes/*`.)

## 5.10 HR dashboard

- KPIs: headcount, active by department, attendance today, pending leaves, upcoming contract renewals; quick links; recent payroll run.

**Team deliverable checklist (HR):** port tables → migrations/models/enums; `PayrollRunService`, `AttendanceService`, `LeaveApprovalService`; FormRequests; views; org chart component; reports; feature tests; menu + dashboard partial; migration of v1 screens into module (incremental).

---

---

# CORE MODULE 6 — CRM

**Folder:** `app/Modules/CRM` · **Route prefix:** `/crm` · **Owns:** leads, opportunities, contacts, companies, activities, crm_notes, tasks. **Views owned:** `v_sales_funnel`, `v_customer_history` (data owned by Sales). **Depends on:** Shared; integrates with Sales customers.

## 6.1 Leads (`leads`)

- `source` (`web|referral|call|event|trade_show|other`), `first_name`, `last_name`, `company_name`, `email`, `phone`, `status` (`new|contacted|qualified|converted|lost`), `assigned_to`, `notes`, `converted_at`, `lost_reason`.
- `convertToCustomer`: creates Sales `customer` (+ optional contact/company), links `converted_customer_id`, flips status to `converted`. Only a qualified lead converts.

## 6.2 Opportunities (`opportunities`)

- `lead_id` (nullable), `customer_id` (nullable), `contact_id`, `name`, `stage` (`prospecting|qualification|proposal|negotiation|won|lost`), `amount`, `probability` (per stage, config-driven, editable), `expected_close_date`, `assigned_to`, `won_at`, `lost_at`, `lost_reason`.
- Owns the **sales funnel**: `v_sales_funnel` groups by stage with count + weighted value; `v_sales_pipeline` (Sales) is the report flavor. Stage transitions logged in activity timeline + audit; `won` triggers `SalesOrder` creation via `createOrderFromOpportunity`.

## 6.3 Contacts (`contacts`)

- `company_id` (nullable), `lead_id` (nullable), `first_name`, `last_name`, `email`, `phone`, `title`, `is_primary`. Contacts link to companies and/or leads; a converted lead's primary contact links to the new customer.

## 6.4 Companies (`companies`)

- B2B account records (do NOT confuse with the platform `organizations` table): `name`, `tax_id`, `industry`, `website`, `address`, `phone`, `status` (`active|inactive`). A company may have many contacts and multiple customers.

## 6.5 Activities (`activities`)

- `activity_type` (`call|email|meeting|note|task`), polymorphic `subject_type`/`subject_id` (lead/opportunity/company/contact), `scheduled_at`, `completed_at`, `summary`, `result`, `assigned_to`, `created_by`.
- Rendered as a chronological timeline on the entity's `show` page (`x-timeline`).

## 6.6 Notes (`crm_notes`)

- Polymorphic (`notable_type`/`notable_id`), `author_id`, `body`, `pinned`. Free-form internal notes, distinct from formal audit log.

## 6.7 Follow Ups & Tasks (`tasks`)

- `tasks`: `title`, `description`, `assigned_to`, `due_date`, `status` (`todo|in_progress|done|cancelled`), polymorphic `related_type`/`related_id`, `created_by`, `completed_at`.
- Follow-up = activity of type `call`/`email` scheduled on an entity; overdue follow-ups flagged on dashboards.

## 6.8 Customer History (`v_customer_history` view)

- Aggregates quotes, orders, invoices, payments, returns, opportunities per Sales customer for the CRM/detail page. Read-only; owned by Sales data, rendered in CRM.

## 6.9 CRM Reports & Dashboard

- Reports: `leads`, `opportunities`, `funnel`, `activities`, `team-performance` (won/amount per owner).
- Dashboard: KPIs (new leads, open opps, funnel value, won this month, follow-ups due); funnel mini-table; upcoming tasks.

**Team deliverable checklist (CRM):** port tables → migrations/models/enums; `LeadConversionService`, `OpportunityService` (stage transitions), `ActivityService`; FormRequests; views; funnel/customer-history views; feature tests; menu + dashboard partial.

---

# SHARED MODULES (Foundation)

**Folder:** `app/Modules/Shared` — infra, not a feature module. Owns: users, roles, permissions, role_permissions, user_roles, audit_logs, notifications, settings, catalogs, attachments, tags/taggables, favorites, comments, document_sequences, activities, import_batches, saved_reports.

## Authentication

- Existing `AuthController` (session) is the baseline and stays. Extensions (password reset via `password_reset_tokens`, 2FA, lockout) are additive; no framework rewrite.
- Login validates active status (`estado = activo`) — already implemented; keep.

## Authorization

- New permission model (`roles`/`permissions`) + compatibility with legacy `users.role`. `PermissionMiddleware` registered in `bootstrap/app.php`. Role middleware kept.

## Roles & Permissions

- See the dedicated *Roles & Permissions* section above. Tables + seed baseline defined in `ERP.sql`.

## Audit Logs

- `audit_logs` + `HasAuditTrail` trait + `x-timeline` component. See *Audit Logs* section.

## Notifications

- `notifications` table + `NotificationService` + topbar bell. See *Notifications* section.

## Settings

- `settings` table + `Settings` facade (cached). See *Configurations* section.

## Catalogs

- Generic `catalogs` table + seed of baseline groups (payment_terms, payment_method, currency, document_status, …) — see *Catalogs* section.

## Attachments

- `attachments` table + `AttachmentService` + `x-attachments` component. See *Attachments* section.

## Dashboard

- `MenuRegistry` + shared dashboard layout + `x-stat-card`. See *Dashboard Philosophy*.

## Reports

- `saved_reports` table + report conventions. See *Reporting System*.

## Search Engine

- `SearchService` + per-module providers + `x-global-search`. See *Search System*.

## Global Filters

- `<x-filter-bar>` + filter contract. See *Global Filters*.

## Activity Timeline

- `activities` (CRM) + `audit_logs` (formal) → both rendered by `x-timeline` on document/detail pages. Follows the same data contract: `{ at, by, type, summary }`.

## Comments

- `comments` (polymorphic, threaded via `parent_id`, soft-deleted). Rendered by `x-comments` on entities that allow discussion (quotes, orders, leaves, opportunities).

## History

- Formal history = `audit_logs` (field-level `old_values`/`new_values`). Entity detail pages always render it.

## Tags & Favorites

- `tags` + `taggables` (polymorphic) and `favorites` (user, polymorphic) — lightweight organization for list pages and dashboards. Components `x-tags`, `x-favorite-toggle`.

## Exports & Imports

- `ExportService` + `ImportService` + `import_batches`. See *Import/Export*.

## Sequences

- `document_sequences` — the only way to generate visible document numbers (`JE-`, `SO-`, `FC-`, `PO-`, `GR-`, `NV-`, …). `SequenceService::next('sales.orders')`. Atomic increment; never reuse cancelled numbers.

---

# APPENDIX A — Module Implementation Handbook (for teams & AI assistants)

Use this recipe to implement **any** module without asking again. It is the guarantee of consistency.

### A.1 Order of work per module
1. **Schema:** port your tables from `ERP.sql` into `app/Modules/<Module>/Migrations/` (Laravel-compatible; match names exactly; include `HasAuditFields` columns + `deleted_at`).
2. **Models + Enums:** one Eloquent model per table in `Models/`; one backed enum per `status`/`type` column in `Enums/` (with `label(): string` in Spanish and `color(): string` for `<x-badge>`).
3. **Traits/Contracts:** apply `App\Modules\Shared\Traits\HasAuditTrail` and `HasAuditFields`.
4. **Observers:** register document numbering + totals recompute in `Observers/` (wired in the module's `Providers/<Module>ServiceProvider` or `boot()` of a module provider; alternatively central `ModuleServiceProvider`).
5. **Requests:** one FormRequest per store/update in `Requests/`.
6. **Services:** implement lifecycle actions in `Services/` (post/approve/cancel/convert). Controllers never contain lifecycle logic.
7. **Routes:** fill `Routes/web.php` (browser) with `Route::controller(...)` or resources under the module group; **never** re-add middleware group — provider already applies `auth`. Use `permission:` middleware for new permissions.
8. **Views:** build under `Views/` using shared components + module partials (`_form`, `_table`, `_filters`). Register menu entries + dashboard partial via `MenuRegistry`.
9. **Reports:** add `Routes/.../reports/*` + `_filters` partials + CSV export.
10. **Tests:** Feature tests per resource + lifecycle in `tests/Feature/Modules/<Module>/`.

### A.2 Lifecycle template (copy for every document type)
```
CREATE (draft, number assigned via SequenceService)
  → UPDATE (draft only)
  → SUBMIT/POST/APPROVE (Service; validation + audit + side-effects)
  → CANCEL/VOID (only if not already posted/fulfilled; reversal instead of delete)
  → detail page shows: header card, lines table, totals, timeline, attachments, comments
```

### A.3 List page template
```
x-page-header (title + "Nuevo" button)
x-filter-bar (buscar + module filters + clear)
x-card → x-table (thead uppercase, align-middle, action-btn) → x-empty → {{ $rows->links() }}
```

### A.4 Form page template
```
x-page-header → x-card → _form.blade.php (row g-3 / col-md-*) → footer (Cancelar / Guardar)
```

### A.5 Definition of Done (module)
- [ ] Migrations match `ERP.sql` table names/columns.
- [ ] All states as Enums; all lifecycle via Services; audit writes on every mutation.
- [ ] List/search/filters/pagination per template.
- [ ] Show page with timeline + attachments.
- [ ] Reports with print + CSV.
- [ ] Feature tests green (`composer test`), Pint clean.
- [ ] Menu + dashboard registered; docs updated in this file if a convention changed.

---

# APPENDIX B — Hand-off to Claude Code (continued implementation)

Claude Code (or any assistant) continuing from here should:

1. Read this document **first** — especially *Module Architecture*, *Database Standards*, *Naming Conventions*, *UX Principles*, and the target module section.
2. Read the existing v1 code for style (controllers, `_form.blade.php`, `sisen.css`, `sisen.js`) and mirror it.
3. Read `ERP.sql` for the target tables of the assigned module; port them into `app/Modules/<Module>/Migrations/`.
4. Follow **Appendix A** in order. Implement one resource at a time, end to end, then run `./vendor/bin/pint` and `composer test`.
5. Never edit another module's folder or the shared shell without updating `PLANNING.md` accordingly.
6. When a business rule is ambiguous, add it to this document rather than inventing a divergent pattern.

---

# APPENDIX C — Roadmap

| Milestone | Scope | Exit criteria |
|---|---|---|
| M0 (this phase) | PLANNING.md + ERP.sql + skeleton + shared components + provider | Skeleton boots, `php artisan route:list` shows module groups, no v1 regression |
| M1 | Shared foundation (users/roles/permissions, settings, catalogs, sequences, audit trait) | Admin can assign roles/permissions; documents get numbers; audit writes |
| M2 | Inventory + HR full | stock ledger correct; payroll run posts; org chart renders |
| M3 | Finance core (COA, journal, periods, reports) | journal posts; trial balance/BS/IS correct |
| M4 | Sales + Purchasing + CRM | order→invoice→payment and PO→receipt→bill→payment flows work end-to-end |
| M5 | Notifications, search, import/export, API | global search + CSV in/out + API smoke tests |
| M6 | Hardening: SAT/CFDI integration, backups, CI, deployment | production runbook executed |

---

*Document ends. All changes to conventions must be recorded here first.*
