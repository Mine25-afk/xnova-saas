@extends('layouts.main')

@section('title', 'Detalles de Movimiento - xNova')
@section('page-title', 'Finanzas / Movimientos / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
@endsection

@section('content')
<div class="detail-page">
    <x-card class="detail-card">
        <div class="detail-header">
            <div><h1 class="detail-title">{{ $movimiento->descripcion }}</h1><p class="detail-subtitle">Consulta los datos del movimiento registrado.</p></div>
            <div class="detail-toolbar">
                <a href="{{ route('finanzas.movimientos.edit', $movimiento) }}" class="btn btn-primary">Editar</a>
                <a href="{{ route('finanzas.movimientos.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="detail-body">
            <section class="detail-panel">
                <div class="detail-panel-header"><div class="detail-panel-title">Datos del movimiento</div></div>
                <div class="detail-grid">
                    <article class="detail-item"><span class="detail-label">Descripcion</span><span class="detail-value">{{ $movimiento->descripcion }}</span></article>
                    <article class="detail-item"><span class="detail-label">Tipo</span><span class="detail-value">{{ ucfirst($movimiento->tipo) }}</span></article>
                    <article class="detail-item"><span class="detail-label">Monto</span><span class="detail-value">{{ $movimiento->cuenta?->moneda ?? 'USD' }} {{ number_format((float) $movimiento->monto, 2) }}</span></article>
                    <article class="detail-item"><span class="detail-label">Fecha</span><span class="detail-value">{{ optional($movimiento->fecha)->format('d/m/Y') ?? 'Sin fecha' }}</span></article>
                    <article class="detail-item"><span class="detail-label">Cuenta origen</span><span class="detail-value">{{ $movimiento->cuenta?->nombre ?? 'Sin cuenta' }} / {{ $movimiento->subcuenta?->nombre ?? 'General' }}</span></article>
                    <article class="detail-item"><span class="detail-label">Cuenta destino</span><span class="detail-value">{{ $movimiento->cuentaDestino?->nombre ? $movimiento->cuentaDestino->nombre.' / '.($movimiento->subcuentaDestino?->nombre ?? 'General') : 'No aplica' }}</span></article>
                    <article class="detail-item"><span class="detail-label">Propietario</span><span class="detail-value">{{ $movimiento->propietario?->nombre ?? 'Sin propietario' }}</span></article>
                    <article class="detail-item"><span class="detail-label">Categoria</span><span class="detail-value">{{ $movimiento->categoria ?: 'Sin categoria' }}</span></article>
                </div>
            </section>
        </div>
    </x-card>
</div>
@endsection
