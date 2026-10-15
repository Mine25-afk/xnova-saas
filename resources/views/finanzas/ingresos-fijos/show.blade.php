@extends('layouts.main')

@section('title', 'Detalles de Ingreso Fijo - xNova')
@section('page-title', 'Finanzas / Ingresos Fijos / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $ingreso->nombre }}</h1>
                <p class="detail-subtitle">Consulta la informacion principal del ingreso fijo y su frecuencia.</p>
            </div>
            <div class="detail-toolbar">
                <a href="{{ route('finanzas.ingresos-fijos.edit', $ingreso) }}" class="btn btn-primary">Editar</a>
                <a href="{{ route('finanzas.ingresos-fijos.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="detail-panel">
                <div class="detail-panel-header"><div class="detail-panel-title">Datos del ingreso</div></div>
                <div class="detail-grid">
                    <article class="detail-item"><span class="detail-label">Nombre</span><span class="detail-value">{{ $ingreso->nombre }}</span></article>
                    <article class="detail-item"><span class="detail-label">Monto</span><span class="detail-value">${{ number_format((float) $ingreso->monto, 2) }}</span></article>
                    <article class="detail-item"><span class="detail-label">Propietario</span><span class="detail-value">{{ $ingreso->propietario?->nombre ?? 'Sin propietario' }}</span></article>
                    <article class="detail-item"><span class="detail-label">Cuenta</span><span class="detail-value">{{ $ingreso->cuenta?->nombre ?? 'Sin cuenta fija' }}</span></article>
                    <article class="detail-item"><span class="detail-label">Estado</span><span class="detail-value">{{ $ingreso->active ? 'Activo' : 'Inactivo' }}</span></article>
                    <article class="detail-item"><span class="detail-label">Actualizado</span><span class="detail-value">{{ optional($ingreso->updated_at)->format('d/m/Y h:i A') ?? 'Sin fecha' }}</span></article>
                </div>
            </section>
        </div>
    </x-card>
</div>
@endsection
