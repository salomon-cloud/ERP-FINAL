<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SISEN') - Sistema Empresarial de Nominas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('css/sisen.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <div class="mobile-overlay"></div>
    <aside class="sidebar">
        <div class="sidebar-brand d-flex align-items-center gap-3">
            <div class="brand-mark">SN</div>
            <div>
                <div class="fw-bold fs-5">SISEN</div>
                <small class="text-white-50">Nominas ERP</small>
            </div>
        </div>
        @php
            $role = auth()->user()->role ?? '';
            $canRH = in_array($role, ['Administrador', 'Recursos Humanos', 'Empleado']);
            $canCatalogs = in_array($role, ['Administrador', 'Recursos Humanos']);
            $canPayroll = in_array($role, ['Administrador', 'Contador', 'Empleado']);
            $canReports = in_array($role, ['Administrador', 'Contador']);
        @endphp
        <nav class="nav flex-column py-3">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
            @if($canRH)<a class="nav-link {{ request()->routeIs('empleados.*') ? 'active' : '' }}" href="{{ route('empleados.index') }}"><i class="bi bi-people"></i> Empleados</a>@endif
            @if($canCatalogs)<a class="nav-link {{ request()->routeIs('departamentos.*') ? 'active' : '' }}" href="{{ route('departamentos.index') }}"><i class="bi bi-building"></i> Departamentos</a>@endif
            @if($canCatalogs)<a class="nav-link {{ request()->routeIs('puestos.*') ? 'active' : '' }}" href="{{ route('puestos.index') }}"><i class="bi bi-briefcase"></i> Puestos</a>@endif
            @if($canPayroll)<a class="nav-link {{ request()->routeIs('nominas.*') ? 'active' : '' }}" href="{{ route('nominas.index') }}"><i class="bi bi-cash-stack"></i> Nominas</a>@endif
            @if($canRH)<a class="nav-link {{ request()->routeIs('asistencias.*') ? 'active' : '' }}" href="{{ route('asistencias.index') }}"><i class="bi bi-calendar-check"></i> Asistencias</a>@endif
            @if($canRH)<a class="nav-link {{ request()->routeIs('permisos.*') ? 'active' : '' }}" href="{{ route('permisos.index') }}"><i class="bi bi-calendar2-week"></i> Permisos y vacaciones</a>@endif
            @if($canReports)<a class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}" href="{{ route('reportes.index') }}"><i class="bi bi-bar-chart"></i> Reportes</a>@endif
            @if($role === 'Administrador')<a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}"><i class="bi bi-person-gear"></i> Usuarios</a>@endif
        </nav>

        {{-- Cada modulo registra sus propias entradas en RegistroMenu; este bloque
             no se toca al agregar un modulo. Ver PLANNING - Dashboard Philosophy.

             Un modulo con una sola entrada (su tablero, el que se auto-registra)
             se pinta como enlace suelto, igual que siempre. Un modulo con varias
             --- como RH, con doce --- se agrupa en un desplegable de Bootstrap,
             para no llenar la barra con una lista plana de enlaces. --}}
        @php
            $entradasPorModulo = collect(\App\Modules\Compartido\Support\RegistroMenu::visiblesPara(auth()->user()))
                ->groupBy('modulo');

            // El prefijo que decide si un enlace esta activo: los dos primeros
            // segmentos del nombre de ruta ("rh.empleados.index" -> "rh.empleados"),
            // o la ruta completa cuando no tiene un tercer segmento ("rh.organigrama").
            $prefijoDeRuta = fn (string $ruta) => count($p = explode('.', $ruta)) >= 3 ? $p[0].'.'.$p[1] : $ruta;
            $rutaActiva = fn (string $ruta) => request()->routeIs($prefijoDeRuta($ruta))
                || request()->routeIs($prefijoDeRuta($ruta).'.*');
        @endphp
        @if($entradasPorModulo->isNotEmpty())
            <div class="sidebar-section">Modulos ERP</div>
            <nav class="nav flex-column pb-3">
                @foreach($entradasPorModulo as $modulo => $entradas)
                    @if($entradas->count() === 1)
                        @php
                            $entrada = $entradas->first();
                        @endphp
                        <a class="nav-link {{ $rutaActiva($entrada['ruta']) ? 'active' : '' }}"
                           href="{{ route($entrada['ruta']) }}">
                            <i class="bi bi-{{ $entrada['icono'] }}"></i> {{ $entrada['etiqueta'] }}
                        </a>
                    @else
                        @php
                            $metadatos = \App\Modules\Compartido\Support\RegistroMenu::metadatos($modulo);
                            $idDesplegable = 'menu-'.\Illuminate\Support\Str::slug($modulo);
                            $moduloActivo = $entradas->contains(fn ($e) => $rutaActiva($e['ruta']));
                        @endphp
                        <a class="nav-link sidebar-toggle d-flex justify-content-between align-items-center {{ $moduloActivo ? 'active' : '' }}"
                           href="#{{ $idDesplegable }}" data-bs-toggle="collapse" role="button"
                           aria-expanded="{{ $moduloActivo ? 'true' : 'false' }}" aria-controls="{{ $idDesplegable }}">
                            <span><i class="bi bi-{{ $metadatos['icono'] }}"></i> {{ $metadatos['etiqueta'] }}</span>
                            <i class="bi bi-chevron-down small"></i>
                        </a>
                        <div class="collapse {{ $moduloActivo ? 'show' : '' }}" id="{{ $idDesplegable }}">
                            @foreach($entradas as $entrada)
                                <a class="nav-link nav-link-sub {{ $rutaActiva($entrada['ruta']) ? 'active' : '' }}"
                                   href="{{ route($entrada['ruta']) }}">
                                    <i class="bi bi-{{ $entrada['icono'] }}"></i> {{ $entrada['etiqueta'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>
        @endif
    </aside>

    <main class="main-content">
        <header class="topbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-primary d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button>
                <div>
                    <div class="fw-bold">@yield('header', 'Panel Administrativo')</div>
                    <small class="text-muted">@yield('subtitle', 'Gestion empresarial de recursos humanos y nominas')</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->role }}</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>
        </header>
        <section class="content-wrap">
            @include('partials.alerts')
            @yield('content')
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/sisen.js') }}"></script>
</body>
</html>
