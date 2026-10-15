@extends('layouts.main')

@section('title', 'Detalles de Cita - xNova')
@section('page-title', 'Citas / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canEdit = in_array('citas.editar', $permissionCodes, true) && $cita->estaEditable();
    $estadoClase = $cita->estaTerminada() ? 'is-finished' : ($cita->estaCancelada() ? 'is-cancelled' : 'is-reserved');
    $horarioNombre = $cita->sucursal?->horario?->nombre ?? 'Sin horario asignado';
@endphp
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $cita->descripcion }}</h1>
                <p class="detail-subtitle">Consulta la informacion principal de la cita, su estado actual y la sucursal donde fue programada.</p>
            </div>

            <div class="detail-toolbar">
                @if ($canEdit)
                    <a href="{{ route('citas.edit', $cita) }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm2.92 2.33H5v-.92l8.06-8.06.92.92L5.92 19.58zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                        Editar
                    </a>
                @endif

                <a href="{{ route('citas.listado') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div class="detail-panel-title">Registro de la cita</div>
                    <div class="detail-panel-copy">Estos son los datos principales que se administran dentro del CRUD de citas.</div>
                </div>

                <div class="detail-grid">
                    <article class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-status {{ $estadoClase }}">{{ $cita->estado?->nombre ?? 'Sin estado' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Fecha</span>
                        <span class="detail-value">{{ optional($cita->fecha)->format('d/m/Y') ?? 'Sin fecha' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Hora de inicio</span>
                        <span class="detail-value">{{ substr((string) $cita->hora_inicio, 0, 5) }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Hora de salida</span>
                        <span class="detail-value">{{ substr((string) $cita->hora_salida, 0, 5) }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Cliente</span>
                        <span class="detail-value">{{ $cita->nombreClienteMostrado() }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Identidad del cliente</span>
                        <span class="detail-value">{{ $cita->identidadClienteMostrado() }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Telefono del cliente</span>
                        <span class="detail-value">{{ $cita->cliente?->telefono ?? 'Sin telefono' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Empleado</span>
                        <span class="detail-value">{{ trim(($cita->colaborador?->socio?->nombres ?? '') . ' ' . ($cita->colaborador?->socio?->apellidos ?? '')) ?: 'Sin empleado' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Puesto del empleado</span>
                        <span class="detail-value">{{ $cita->colaborador?->puesto?->nombre ?? 'Sin puesto' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Sucursal</span>
                        <span class="detail-value">{{ $cita->sucursal?->nombre ?? 'Sin sucursal' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Horario de la sucursal</span>
                        <span class="detail-value">{{ $horarioNombre }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Producto</span>
                        <span class="detail-value">{{ $cita->producto?->nombre ?? 'Sin producto' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Precio</span>
                        <span class="detail-value">{{ $cita->producto ? number_format((float) $cita->producto->precio, 2) : 'Sin precio' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Descripcion</span>
                        <span class="detail-value">{{ $cita->descripcion }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Comentario</span>
                        <span class="detail-value">{{ $cita->comentario ?: 'Sin comentario' }}</span>
                    </article>
                </div>
            </section>
        </div>
    </x-card>
</div>
@endsection
