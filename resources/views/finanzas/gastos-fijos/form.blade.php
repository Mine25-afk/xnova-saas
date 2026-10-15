@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Gasto Fijo' : 'Nuevo Gasto Fijo') . ' — xNova')
@section('page-title', $isEditing ? 'Finanzas / Gastos Fijos / Editar' : 'Finanzas / Gastos Fijos / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar gasto fijo' : 'Nuevo gasto fijo' }}</h1>
                <p class="form-subtitle">Define compromisos recurrentes para anticipar salidas de dinero.</p>
            </div>
            <div class="form-toolbar">
                <a href="{{ route('finanzas.gastos-fijos.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form class="form-grid" method="POST" data-block-on-submit action="{{ $isEditing ? route('finanzas.gastos-fijos.update', $gasto) : route('finanzas.gastos-fijos.store') }}">
                    @csrf
                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <x-input name="nombre" label="Nombre" value="{{ old('nombre', $gasto->nombre) }}" placeholder="Ejemplo: Renta" />

                    <x-native-select name="finanzas_propietario_id" label="Propietario">
                        @foreach ($propietarios as $propietario)
                            <option value="{{ $propietario->id }}" @selected((int) old('finanzas_propietario_id', $gasto->finanzas_propietario_id ?? 0) === $propietario->id)>{{ $propietario->nombre }}</option>
                        @endforeach
                    </x-native-select>

                    <x-input name="monto" type="number" step="0.01" min="0" label="Monto" value="{{ old('monto', $gasto->monto) }}" />

                    <x-icon-picker name="icono" :value="old('icono', $gasto->icono ?? null)" label="Ícono del gasto" />

                    <label class="permission-check">
                        <input type="checkbox" name="preguntar_al_usar" value="1" @checked(old('preguntar_al_usar', $gasto->preguntar_al_usar ?? false))>
                        <span>Preguntar monto al usar este gasto (rebaja el saldo y se reinicia al llegar a cero)</span>
                    </label>

                    @if ($isEditing && $gasto->preguntar_al_usar)
                        <div class="form-section-copy">Saldo restante actual: <strong>{{ number_format((float) ($gasto->saldo_restante ?? $gasto->monto), 2) }}</strong></div>
                    @endif

                    <label class="permission-check"><input type="checkbox" name="active" value="1" @checked(old('active', $gasto->active ?? true))><span>Gasto activo</span></label>

                    @if ($errors->any())
                        <div class="form-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-actions"><a href="{{ route('finanzas.gastos-fijos.index') }}" class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">{{ $isEditing ? 'Guardar cambios' : 'Guardar' }}</button></div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
