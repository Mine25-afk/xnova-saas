@extends('layouts.main')

@section('title', ($isEditing ? 'Editar Movimiento' : 'Nuevo Movimiento') . ' - xNova')
@section('page-title', $isEditing ? 'Finanzas / Movimientos / Editar' : 'Finanzas / Movimientos / Nuevo')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endsection

@section('content')
<div class="form-page">
    <x-card class="form-card">
        <div class="form-header">
            <div>
                <h1 class="form-title">{{ $isEditing ? 'Editar movimiento' : 'Nuevo movimiento' }}</h1>
                <p class="form-subtitle">Usa ingresos, gastos, transferencias o ajustes para construir el saldo real.</p>
            </div>
            <div class="form-toolbar">
                <a href="{{ route('finanzas.movimientos.index') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                    Volver
                </a>
            </div>
        </div>

        <div class="form-body">
            <section class="form-panel">
                <form class="form-grid" method="POST" data-block-on-submit action="{{ $isEditing ? route('finanzas.movimientos.update', $movimiento) : route('finanzas.movimientos.store') }}">
                    @csrf
                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <x-native-select name="tipo" label="Tipo de movimiento">
                        @foreach (['ingreso' => 'Ingreso', 'gasto' => 'Gasto', 'transferencia' => 'Transferencia', 'ajuste' => 'Ajuste positivo'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('tipo', $movimiento->tipo ?? 'gasto') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-native-select>

                    <x-input name="descripcion" label="Descripcion" value="{{ old('descripcion', $movimiento->descripcion) }}" placeholder="Ejemplo: Supermercado" />

                    <div class="form-grid-double">
                        <x-native-select name="finanzas_cuenta_id" label="Cuenta origen">
                            @foreach ($cuentas as $cuenta)
                                <option value="{{ $cuenta->id }}" @selected((int) old('finanzas_cuenta_id', $movimiento->finanzas_cuenta_id ?? 0) === $cuenta->id)>{{ $cuenta->nombre }}</option>
                            @endforeach
                        </x-native-select>

                        <x-native-select name="finanzas_cuenta_destino_id" label="Cuenta destino solo para transferencias">
                            <option value="">No aplica</option>
                            @foreach ($cuentas as $cuenta)
                                <option value="{{ $cuenta->id }}" @selected((int) old('finanzas_cuenta_destino_id', $movimiento->finanzas_cuenta_destino_id ?? 0) === $cuenta->id)>{{ $cuenta->nombre }}</option>
                            @endforeach
                        </x-native-select>
                    </div>

                    <div class="form-grid-double">
                        <x-native-select name="finanzas_subcuenta_id" label="Bolsillo origen">
                            <option value="">General de la cuenta origen</option>
                            @foreach ($subcuentas as $subcuenta)
                                <option value="{{ $subcuenta->id }}" @selected((int) old('finanzas_subcuenta_id', $movimiento->finanzas_subcuenta_id ?? 0) === $subcuenta->id)>{{ $subcuenta->cuenta?->nombre }} / {{ $subcuenta->nombre }}</option>
                            @endforeach
                        </x-native-select>

                        <x-native-select name="finanzas_subcuenta_destino_id" label="Bolsillo destino solo para transferencias">
                            <option value="">General de la cuenta destino</option>
                            @foreach ($subcuentas as $subcuenta)
                                <option value="{{ $subcuenta->id }}" @selected((int) old('finanzas_subcuenta_destino_id', $movimiento->finanzas_subcuenta_destino_id ?? 0) === $subcuenta->id)>{{ $subcuenta->cuenta?->nombre }} / {{ $subcuenta->nombre }}</option>
                            @endforeach
                        </x-native-select>
                    </div>

                    <x-native-select name="finanzas_propietario_id" label="Propietario opcional">
                        <option value="">Sin propietario</option>
                        @foreach ($propietarios as $propietario)
                            <option value="{{ $propietario->id }}" @selected((int) old('finanzas_propietario_id', $movimiento->finanzas_propietario_id ?? 0) === $propietario->id)>{{ $propietario->nombre }}</option>
                        @endforeach
                    </x-native-select>

                    <div class="form-grid-double">
                        <x-input name="monto" type="number" step="0.01" min="0" label="Monto" value="{{ old('monto', $movimiento->monto) }}" />
                        <x-input name="fecha" type="date" label="Fecha" value="{{ old('fecha', optional($movimiento->fecha)->format('Y-m-d') ?? now()->toDateString()) }}" />
                    </div>

                    <x-input name="categoria" label="Categoria opcional" value="{{ old('categoria', $movimiento->categoria) }}" placeholder="Ejemplo: Hogar, comida, deuda" />

                    @if ($errors->any())
                        <div class="form-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-actions"><a href="{{ route('finanzas.movimientos.index') }}" class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-primary">{{ $isEditing ? 'Guardar cambios' : 'Guardar' }}</button></div>
                </form>
            </section>
        </div>
    </x-card>
</div>
@endsection
