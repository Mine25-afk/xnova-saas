@extends('layouts.main')

@section('title', 'Preferencias de la empresa — xNova')
@section('page-title', 'Empresa / Preferencias')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">Preferencias de {{ $xnova->nombre }}</h1>
                <p class="form-subtitle">Ajusta los valores predeterminados de la empresa. Estos cambios solo los puede hacer el administrador (creador de la empresa).</p>
            </div>

            <div class="form-toolbar">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                @if (session('status'))
                    <div class="form-section-copy" style="background: rgba(16,185,129,.1); color: #047857; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('empresa.preferencias.update') }}" class="form-grid">
                    @csrf
                    @method('PATCH')

                    <div class="form-section">
                        <div>
                            <div class="form-section-title">Moneda predeterminada</div>
                            <p class="form-section-copy">Se utilizará como moneda por defecto al crear cuentas nuevas y para mostrar los totales del módulo de finanzas.</p>
                        </div>

                        <div class="form-field">
                            <x-native-select name="moneda_default" label="Moneda">
                                @foreach ($monedas as $code => $label)
                                    <option value="{{ $code }}" @selected(old('moneda_default', $xnova->moneda_default ?? 'LPS') === $code)>{{ $label }}</option>
                                @endforeach
                            </x-native-select>
                            @error('moneda_default')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24"><path d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zM12 19a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm3-10H5V5h10v4z"/></svg>
                            Guardar preferencias
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
