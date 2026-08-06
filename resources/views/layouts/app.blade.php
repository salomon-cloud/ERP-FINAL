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
