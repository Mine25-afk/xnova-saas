@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Ingreso Fijo' : 'Nuevo Ingreso Fijo') . ' — xNova')
@section('page-title', $isEditing ? 'Finanzas / Ingresos Fijos / Editar' : 'Finanzas / Ingresos Fijos / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar ingreso fijo' : 'Nuevo ingreso fijo' }}</h1>
                <p class="form-subtitle">Define ingresos recurrentes para proyectar el flujo mensual.</p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('finanzas.ingresos-fijos.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form class="form-grid" method="POST" data-block-on-submit action="{{ $isEditing ? route('finanzas.ingresos-fijos.update', $ingreso) : route('finanzas.ingresos-fijos.store') }}">
                    @csrf
                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <x-input name="nombre" label="Nombre" value="{{ old('nombre', $ingreso->nombre) }}" placeholder="Ejemplo: Sueldo" />

                    <x-native-select name="finanzas_propietario_id" label="Propietario">
                        @foreach ($propietarios as $propietario)
                            <option value="{{ $propietario->id }}" @selected((int) old('finanzas_propietario_id', $ingreso->finanzas_propietario_id ?? 0) === $propietario->id)>{{ $propietario->nombre }}</option>
                        @endforeach
                    </x-native-select>

                    <x-native-select name="finanzas_cuenta_id" label="Cuenta asociada">
                        <option value="">Sin cuenta fija</option>
                        @foreach ($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" @selected((int) old('finanzas_cuenta_id', $ingreso->finanzas_cuenta_id ?? 0) === $cuenta->id)>{{ $cuenta->nombre }}</option>
                        @endforeach
                    </x-native-select>

                    <x-input name="monto" type="number" step="0.01" min="0" label="Monto" value="{{ old('monto', $ingreso->monto) }}" />

                    <x-icon-picker name="icono" :value="old('icono', $ingreso->icono ?? null)" label="Ícono del ingreso" />

                    <label class="permission-check">
                        <input type="checkbox" name="preguntar_al_usar" value="1" @checked(old('preguntar_al_usar', $ingreso->preguntar_al_usar ?? false))>
                        <span>Preguntar monto al usar este ingreso (rebaja el saldo y se reinicia al llegar a cero)</span>
                    </label>

                    @if ($isEditing && $ingreso->preguntar_al_usar)
                        <div class="form-section-copy">Saldo restante actual: <strong>{{ number_format((float) ($ingreso->saldo_restante ?? $ingreso->monto), 2) }}</strong></div>
                    @endif

                    <label class="permission-check"><input type="checkbox" name="active" value="1" @checked(old('active', $ingreso->active ?? true))><span>Ingreso activo</span></label>

                    @if ($errors->any())
                        <div class="form-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-actions">
                        <a href="{{ route('finanzas.ingresos-fijos.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Guardar cambios' : 'Guardar' }}</button>
                    </div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
