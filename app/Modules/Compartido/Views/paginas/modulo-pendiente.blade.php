@extends('layouts.app')

@section('title', $metadatos['etiqueta'])
@section('header', $metadatos['etiqueta'])
@section('subtitle', $metadatos['descripcion'])

@section('content')
    <x-page-header :title="$metadatos['etiqueta']" subtitle="Modulo en construccion" />

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <x-stat-card label="Migraciones" :value="$migraciones" icon="database" hint="Esquema del modulo" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Prefijo" value="/{{ $slug }}" icon="signpost-split" hint="URL del modulo" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Rutas" value="{{ $slug }}.*" icon="diagram-2" hint="Prefijo de nombres" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Vistas" value="{{ $slug }}::" icon="window-stack" hint="Namespace Blade" />
        </div>
    </div>

    <x-card title="Siguiente paso" subtitle="Este modulo ya esta cableado: rutas, vistas, migraciones y menu.">
        <p class="mb-3">{{ $metadatos['descripcion'] }}</p>

        <ol class="mb-3">
            <li>Lee <code>docs/PLANNING.md</code> y <code>{{ $readme }}</code>.</li>
            <li>Sigue el Apendice A: modelos y enums sobre las tablas ya migradas, luego
                observers, requests, services, rutas, vistas y reportes.</li>
            <li>Sustituye esta pagina por
                <code>app/Modules/{{ $modulo }}/Controllers/TableroController.php</code>,
                conservando el nombre de ruta <code>{{ $slug }}.dashboard</code>.</li>
            <li>Registra el menu del modulo con
                <code>RegistroMenu::registrar('{{ $modulo }}', [...])</code>.</li>
        </ol>

        <p class="text-muted mb-0">
            Las tablas de este modulo ya existen en MariaDB: las creo
            <code>php artisan migrate</code> desde
            <code>app/Modules/{{ $modulo }}/Migrations</code>. El archivo
            <code>ERP.sql</code> es solo el reflejo de ese esquema y nunca se importa.
        </p>
    </x-card>
@endsection
