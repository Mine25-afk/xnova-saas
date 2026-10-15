@extends('layouts.main')

@section('title', 'Detalles de Rol - xNova')
@section('page-title', 'Seguridad / Roles / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
@php
    $canEditRole = in_array('seguridad.roles.editar', $currentUserPermissionCodes ?? [], true);
@endphp
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $rol->nombre }}</h1>
                <p class="detail-subtitle">Consulta la informacion principal del rol y la bitacora simple de acciones registradas.</p>
            </div>

            <div class="detail-toolbar">
                @if ($canEditRole)
                    <a href="{{ route('roles.edit', $rol) }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm2.92 2.33H5v-.92l8.06-8.06.92.92L5.92 19.58zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                        Editar
                    </a>
                @endif

                <a href="{{ route('roles.listado') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div class="detail-panel-title">Registro del rol</div>
                </div>

                <div class="detail-grid">
                    <article class="detail-item">
                        <span class="detail-label">Nombre del rol</span>
                        <span class="detail-value">{{ $rol->nombre }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Permisos asignados</span>
                        <span class="detail-value">{{ $rol->permisos_count }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Usuarios asignados</span>
                        <span class="detail-value">{{ $rol->users_count }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Modulos con acceso</span>
                        <span class="detail-value">{{ $modulosCount }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Pantallas con acceso</span>
                        <span class="detail-value">{{ $pantallasCount }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Fecha de registro</span>
                        <span class="detail-value">{{ optional($rol->created_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</span>
                    </article>
                </div>
            </section>

            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div class="detail-panel-title">Bitacora</div>
                    <div class="detail-panel-copy">Solo se guarda un registro por accion: Crear y Editar.</div>
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
                            @forelse ($rol->logs as $log)
                                <tr>
                                    <td>{{ $log->Accion }}</td>
                                    <td>{{ optional($log->Fecha)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</td>
                                    <td>{{ $log->usuarioModifica?->name ?? 'Sin usuario' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="detail-empty">No hay acciones registradas para este rol.</td>
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
