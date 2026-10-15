@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Cuenta' : 'Nueva Cuenta') . ' — xNova')
@section('page-title', $isEditing ? 'Finanzas / Cuentas / Editar' : 'Finanzas / Cuentas / Nueva')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar cuenta' : 'Nueva cuenta' }}</h1>
                <p class="form-subtitle">Registra una cuenta real donde entran, salen o se transfieren saldos.</p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('finanzas.cuentas.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form class="form-grid" method="POST" data-block-on-submit action="{{ $isEditing ? route('finanzas.cuentas.update', $cuenta) : route('finanzas.cuentas.store') }}">
                    @csrf
                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <x-input name="nombre" label="Nombre de cuenta" value="{{ old('nombre', $cuenta->nombre) }}" placeholder="Ejemplo: Banco principal" />

                    <x-native-select name="finanzas_propietario_id" label="Propietario">
                        <option value="">Sin propietario</option>
                        @foreach ($propietarios as $propietario)
                            <option value="{{ $propietario->id }}" @selected((int) old('finanzas_propietario_id', $cuenta->finanzas_propietario_id ?? 0) === $propietario->id)>{{ $propietario->nombre }}</option>
                        @endforeach
                    </x-native-select>

                    <div class="form-grid-double">
                        <x-native-select name="tipo" label="Tipo">
                            @foreach (['banco' => 'Banco', 'efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'ahorro' => 'Ahorro', 'otro' => 'Otro'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('tipo', $cuenta->tipo ?? 'banco') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-native-select>

                        <x-input name="moneda" label="Moneda" maxlength="3" value="{{ old('moneda', $cuenta->moneda ?? 'USD') }}" />
                    </div>

                    <x-icon-picker name="icono" :value="old('icono', $cuenta->icono ?? null)" label="Ícono de la cuenta" />

                    @unless ($isEditing)
                        <x-input name="saldo_inicial" type="number" step="0.01" label="Saldo inicial opcional" value="{{ old('saldo_inicial', 0) }}" />
                    @endunless

                    <label class="permission-check">
                        <input type="checkbox" name="active" value="1" @checked(old('active', $cuenta->active ?? true))>
                        <span>Cuenta activa</span>
                    </label>

                    @if ($errors->any())
                        <div class="form-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-actions">
                        <a href="{{ route('finanzas.cuentas.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Guardar cambios' : 'Guardar' }}</button>
                    </div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
