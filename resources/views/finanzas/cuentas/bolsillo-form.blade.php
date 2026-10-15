@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Bolsillo' : 'Nuevo Bolsillo') . ' — xNova')
@section('page-title', 'Finanzas / Cuentas / ' . $cuenta->nombre . ' / ' . ($isEditing ? 'Editar Bolsillo' : 'Nuevo Bolsillo'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar bolsillo' : 'Nuevo bolsillo' }}</h1>
                <p class="form-subtitle">
                    {{ $isEditing
                        ? 'Modifica el nombre, la meta o el ícono de este bolsillo de '.$cuenta->nombre.'.'
                        : 'Crea un bolsillo dentro de '.$cuenta->nombre.' para separar el dinero por uso.' }}
                </p>
            </div>
            <div class="form-toolbar">
                <a href="{{ route('finanzas.cuentas.show', $cuenta) }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form class="form-grid" method="POST" data-block-on-submit action="{{ $isEditing ? route('finanzas.cuentas.bolsillos.update', [$cuenta, $bolsillo]) : route('finanzas.cuentas.bolsillos.store', $cuenta) }}">
                    @csrf
                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <x-input name="nombre" label="Nombre del bolsillo" value="{{ old('nombre', $bolsillo->nombre) }}" placeholder="Ejemplo: Comida, Transporte, Ahorro" />

                    <x-input name="meta" type="number" step="0.01" min="0" label="Meta opcional ({{ $cuenta->moneda }})" value="{{ old('meta', $bolsillo->meta) }}" placeholder="Ej: 1500.00" />

                    <x-icon-picker name="icono" :value="old('icono', $bolsillo->icono ?? null)" label="Ícono del bolsillo" />

                    @if ($errors->any())
                        <div class="form-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-actions">
                        <a href="{{ route('finanzas.cuentas.show', $cuenta) }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">{{ $isEditing ? 'Guardar cambios' : 'Guardar bolsillo' }}</button>
                    </div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
