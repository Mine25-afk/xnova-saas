@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Deuda' : 'Nueva Deuda') . ' — xNova')
@section('page-title', $isEditing ? 'Finanzas / Deudas / Editar' : 'Finanzas / Deudas / Nueva')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar deuda' : 'Nueva deuda' }}</h1>
                <p class="form-subtitle">Registra lo que debes para anticipar tus pagos y planificar mejor.</p>
            </div>
            <div class="form-toolbar">
                <a href="{{ route('finanzas.deudas.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form class="form-grid" method="POST" data-block-on-submit action="{{ $isEditing ? route('finanzas.deudas.update', $deuda) : route('finanzas.deudas.store') }}">
                    @csrf
                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <x-input name="nombre" label="Nombre" value="{{ old('nombre', $deuda->nombre) }}" placeholder="Ejemplo: Préstamo banco" />

                    <x-input name="acreedor" label="Acreedor (a quién le debes)" value="{{ old('acreedor', $deuda->acreedor) }}" placeholder="Ejemplo: Banco Atlántida" />

                    <x-input name="monto_original" type="number" step="0.01" min="0" label="Monto total ({{ $moneda }})" value="{{ old('monto_original', $deuda->monto_original) }}" />

                    @if ($isEditing && ! $deuda->estaPagada())
                        <div class="form-section-copy">Saldo pendiente actual: <strong>{{ $moneda }} {{ number_format((float) $deuda->saldo_pendiente, 2) }}</strong>. Si cambias el monto total, el saldo pendiente se reajusta conservando lo ya pagado.</div>
                    @endif

                    <x-input name="fecha" type="date" label="Fecha" value="{{ old('fecha', optional($deuda->fecha)->format('Y-m-d')) }}" />

                    <x-input name="fecha_vencimiento" type="date" label="Fecha de vencimiento (opcional)" value="{{ old('fecha_vencimiento', optional($deuda->fecha_vencimiento)->format('Y-m-d')) }}" />

                    <x-input name="categoria" label="Categoría (opcional)" value="{{ old('categoria', $deuda->categoria) }}" placeholder="Ejemplo: Préstamos" />

                    <x-icon-picker name="icono" :value="old('icono', $deuda->icono ?? null)" label="Ícono de la deuda" />

                    <x-input name="notas" label="Notas (opcional)" value="{{ old('notas', $deuda->notas) }}" placeholder="Detalle o referencia" />

                    <label class="permission-check"><input type="checkbox" name="active" value="1" @checked(old('active', $deuda->active ?? true))><span>Deuda activa</span></label>

                    @if ($errors->any())
                        <div class="form-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-actions"><a href="{{ route('finanzas.deudas.index') }}" class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">{{ $isEditing ? 'Guardar cambios' : 'Guardar' }}</button></div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
