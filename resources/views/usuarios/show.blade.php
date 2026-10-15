@extends('layouts.main')

@section('title', 'Detalles de Usuario - xNova')
@section('page-title', 'Seguridad / Usuarios / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
@php
    $canEditUser = in_array('seguridad.usuarios.editar', $currentUserPermissionCodes ?? [], true);
    $empleadoAsignado = $usuario->colaborador?->socio
        ? trim($usuario->colaborador->socio->nombres . ' ' . $usuario->colaborador->socio->apellidos)
        : 'Sin empleado asignado';
@endphp
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $usuario->name }}</h1>
                <p class="detail-subtitle">Consulta la informacion principal del usuario y la bitacora simple de acciones registradas.</p>
            </div>

            <div class="detail-toolbar">
                @if ($canEditUser)
                    <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm2.92 2.33H5v-.92l8.06-8.06.92.92L5.92 19.58zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                        Editar
                    </a>
                @endif

                <a href="{{ route('usuarios.listado') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div class="detail-panel-title">Registro del usuario</div>
               </div>

                <div class="detail-grid">
                    <article class="detail-item">
                        <span class="detail-label">Nombre de usuario</span>
                        <span class="detail-value">{{ $usuario->name }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Gmail</span>
                        <span class="detail-value">{{ $usuario->email }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Rol asignado</span>
                        <span class="detail-value">{{ $usuario->rol?->nombre ?? 'Sin rol asignado' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Sucursal asignada</span>
                        <span class="detail-value">{{ $usuario->sucursal?->nombre ?? 'Sin sucursal asignada' }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Empleado asignado</span>
                        <span class="detail-value">{{ $empleadoAsignado }}</span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-status {{ $usuario->active ? 'is-active' : 'is-inactive' }}">
                            {{ $usuario->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </article>

                    <article class="detail-item">
                        <span class="detail-label">Fecha de registro</span>
                        <span class="detail-value">{{ optional($usuario->created_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</span>
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
                            @forelse ($usuario->logs as $log)
                                <tr>
                                    <td>{{ $log->Accion }}</td>
                                    <td>{{ optional($log->Fecha)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</td>
                                    <td>{{ $log->usuarioModifica?->name ?? 'Sin usuario' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="detail-empty">No hay acciones registradas para este usuario.</td>
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
