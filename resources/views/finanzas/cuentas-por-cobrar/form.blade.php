@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Cuenta por cobrar' : 'Nueva Cuenta por cobrar') . ' — xNova')
@section('page-title', $isEditing ? 'Finanzas / Cuentas por cobrar / Editar' : 'Finanzas / Cuentas por cobrar / Nueva')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar cuenta por cobrar' : 'Nueva cuenta por cobrar' }}</h1>
                <p class="form-subtitle">Registra lo que te deben para anticipar tus ingresos y darles seguimiento.</p>
            </div>
            <div class="form-toolbar">
                <a href="{{ route('finanzas.cuentas-por-cobrar.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form class="form-grid" method="POST" data-block-on-submit action="{{ $isEditing ? route('finanzas.cuentas-por-cobrar.update', $cuentaPorCobrar) : route('finanzas.cuentas-por-cobrar.store') }}">
                    @csrf
                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <x-input name="nombre" label="Nombre" value="{{ old('nombre', $cuentaPorCobrar->nombre) }}" placeholder="Ejemplo: Préstamo a Juan" />

                    <x-input name="deudor" label="Deudor (quién te debe)" value="{{ old('deudor', $cuentaPorCobrar->deudor) }}" placeholder="Ejemplo: Juan Pérez" />

                    <x-input name="monto_original" type="number" step="0.01" min="0" label="Monto total ({{ $moneda }})" value="{{ old('monto_original', $cuentaPorCobrar->monto_original) }}" />

                    @if ($isEditing && ! $cuentaPorCobrar->estaCobrada())
                        <div class="form-section-copy">Saldo pendiente actual: <strong>{{ $moneda }} {{ number_format((float) $cuentaPorCobrar->saldo_pendiente, 2) }}</strong>. Si cambias el monto total, el saldo pendiente se reajusta conservando lo ya cobrado.</div>
                    @endif

                    <x-input name="fecha" type="date" label="Fecha" value="{{ old('fecha', optional($cuentaPorCobrar->fecha)->format('Y-m-d')) }}" />

                    <x-input name="fecha_vencimiento" type="date" label="Fecha de vencimiento (opcional)" value="{{ old('fecha_vencimiento', optional($cuentaPorCobrar->fecha_vencimiento)->format('Y-m-d')) }}" />

                    <x-input name="categoria" label="Categoría (opcional)" value="{{ old('categoria', $cuentaPorCobrar->categoria) }}" placeholder="Ejemplo: Préstamos" />

                    <x-icon-picker name="icono" :value="old('icono', $cuentaPorCobrar->icono ?? null)" label="Ícono de la cuenta por cobrar" />

                    <x-input name="notas" label="Notas (opcional)" value="{{ old('notas', $cuentaPorCobrar->notas) }}" placeholder="Detalle o referencia" />

                    <label class="permission-check"><input type="checkbox" name="active" value="1" @checked(old('active', $cuentaPorCobrar->active ?? true))><span>Cuenta por cobrar activa</span></label>

                    @if ($errors->any())
                        <div class="form-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-actions"><a href="{{ route('finanzas.cuentas-por-cobrar.index') }}" class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">{{ $isEditing ? 'Guardar cambios' : 'Guardar' }}</button></div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
