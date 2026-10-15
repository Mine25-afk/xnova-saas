@extends('layouts.main')

@php
    $pantalla = $isCollaborator ? 'Empleado' : 'Cliente';
    $urlListado = $isCollaborator ? route('socios.colaboradores.listado') : route('socios.clientes.listado');
    $urlEditar = route('socios.edit', $data);
    $permisoEditar = $isCollaborator ? 'socios.colaboradores.editar' : 'socios.clientes.editar';
    $canEdit = in_array($permisoEditar, $currentUserPermissionCodes ?? [], true) && $canManageDefaultRecord;
@endphp

@section('title', 'Detalles de ' . $pantalla . ' - xNova')
@section('page-title', 'Socios / ' . $pantalla . ' / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ trim($data->nombres . ' ' . $data->apellidos) }}</h1>
                <p class="detail-subtitle">Consulta la informacion principal del {{ strtolower($pantalla) }} y la bitacora simple de acciones registradas.</p>
            </div>

            <div class="detail-toolbar">
                @if ($canEdit)
                    <a href="{{ $urlEditar }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm2.92 2.33H5v-.92l8.06-8.06.92.92L5.92 19.58zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                        Editar
                    </a>
                @endif

                <a href="{{ $urlListado }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div class="detail-panel-title">Registro del {{ strtolower($pantalla) }}</div>
                </div>

                <div class="detail-grid">
                    <article class="detail-item">
                        <span class="detail-label">Identidad</span>
                        <span class="detail-value">{{ $data->dni }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Nombre completo</span>
                        <span class="detail-value">{{ trim($data->nombres . ' ' . $data->apellidos) }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Telefono</span>
                        <span class="detail-value">{{ $data->telefono }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Correo electronico</span>
                        <span class="detail-value">{{ $data->correo ?: 'Sin correo' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Sexo</span>
                        <span class="detail-value">{{ $data->sexo === 'F' ? 'Femenino' : 'Masculino' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Fecha de nacimiento</span>
                        <span class="detail-value">{{ optional($data->fecha_nacimiento)->format('d/m/Y') ?? 'Sin fecha' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Registro por defecto</span>
                        <span class="detail-value">{{ $data->es_por_defecto ? 'Si' : 'No' }}</span>
                    </article>

                    @if ($isCollaborator)
                        <article class="detail-item">
                            <span class="detail-label">Puesto asignado</span>
                            <span class="detail-value">{{ $data->colaborador?->puesto?->nombre ?? 'Sin puesto' }}</span>
                        </article>

                        <article class="detail-item">
                            <span class="detail-label">Sucursal asignada</span>
                            <span class="detail-value">{{ $data->colaborador?->sucursal?->nombre ?? 'Sin sucursal' }}</span>
                        </article>

                        <article class="detail-item">
                            <span class="detail-label">Usuarios asignados</span>
                            <span class="detail-value">{{ $usuariosAsignados }}</span>
                        </article>
                    @endif

                    <article class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-status {{ $data->active ? 'is-active' : 'is-inactive' }}">
                            {{ $data->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Fecha de registro</span>
                        <span class="detail-value">{{ optional($data->created_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</span>
                    </article>
                </div>
            </section>

            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div class="detail-panel-title">Bitacora</div>
                    <div class="detail-panel-copy">Solo se guarda un registro por accion: Crear, Editar, Activar y Desactivar.</div>
                </div>

                <div class="detail-table-wrap">
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>Accion</th>
                                <th>Fecha</th>
                                <th>Usuario que modifica</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data->logs as $log)
                                <tr>
                                    <td>{{ $log->Accion }}</td>
                                    <td>{{ optional($log->Fecha)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</td>
                                    <td>{{ $log->usuarioModifica?->name ?? 'Sin usuario' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="detail-empty">No hay acciones registradas para este {{ strtolower($pantalla) }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-card>
</div>
@endsection
