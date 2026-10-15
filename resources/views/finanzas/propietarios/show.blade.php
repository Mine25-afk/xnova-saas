@extends('layouts.main')

@section('title', 'Detalles de Propietario — xNova')
@section('page-title', 'Finanzas / Propietarios / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $propietario->nombre }}</h1>
                <p class="detail-subtitle">Resumen de cuentas, ingresos, gastos y movimientos asociados.</p>
            </div>

            <div class="detail-toolbar">
                <a href="{{ route('finanzas.propietarios.edit', $propietario) }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm2.92 2.33H5v-.92l8.06-8.06.92.92L5.92 19.58zM20.71 7.04a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
                    Editar
                </a>

                <a href="{{ route('finanzas.propietarios.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <div class="detail-grid">
                <article class="detail-item">
                    <span class="detail-label">Nombre</span>
                    <span class="detail-value">{{ $propietario->nombre }}</span>
                </article>

                <article class="detail-item">
                    <span class="detail-label">Fecha de registro</span>
                    <span class="detail-value">{{ optional($propietario->created_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</span>
                </article>

                <article class="detail-item">
                    <span class="detail-label">Cuentas asignadas</span>
                    <span class="detail-value">{{ $propietario->cuentas_count }}</span>
                </article>

                <article class="detail-item">
                    <span class="detail-label">Movimientos</span>
                    <span class="detail-value">{{ $movimientosCount }}</span>
                </article>

                <article class="detail-item">
                    <span class="detail-label">Ingresos fijos</span>
                    <span class="detail-value">{{ $ingresosFijosCount }}</span>
                </article>

                <article class="detail-item">
                    <span class="detail-label">Gastos fijos</span>
                    <span class="detail-value">{{ $gastosFijosCount }}</span>
                </article>
            </div>
        </div>
    </x-card>
</div>
@endsection
