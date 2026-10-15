@extends('layouts.main')

@section('title', 'Calendario de Citas - xNova')
@section('page-title', 'Citas / Calendario')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
@endsection

@section('content')
@php
    $permissionCodes = $currentUserPermissionCodes ?? [];
    $canCreate = in_array('citas.crear', $permissionCodes, true);
    $canShow = in_array('citas.detalles', $permissionCodes, true);
@endphp
<div class="list-page">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Calendario de citas</div>
                    <div class="list-card-subtitle">Visualiza las citas del mes con tarjetas por dia y navega entre meses.</div>
                </div>

                <div class="form-toolbar">
                    <a href="{{ route('citas.listado') }}" class="btn btn-secondary">
                        <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
                        Volver al listado
                    </a>

                    @if ($canCreate)
                        <a href="{{ route('citas.create') }}" class="btn btn-primary">
                            <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            Nueva cita
                        </a>
                    @endif
                </div>
            </div>
        </x-slot>

        <div class="calendar-toolbar">
            <div class="calendar-toolbar__group">
                <a href="{{ route('citas.calendario') }}" class="calendar-toolbar__today">
                    Mes actual
                </a>

                <a href="{{ route('citas.calendario', ['mes' => $mesAnterior]) }}" class="calendar-toolbar__icon" aria-label="Ver mes anterior">
                    <svg viewBox="0 0 24 24"><path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                </a>

                <a href="{{ route('citas.calendario', ['mes' => $mesSiguiente]) }}" class="calendar-toolbar__icon" aria-label="Ver mes siguiente">
                    <svg viewBox="0 0 24 24"><path d="m8.59 16.59 1.41 1.41 6-6-6-6-1.41 1.41L13.17 12z"/></svg>
                </a>
            </div>

            <div class="calendar-toolbar__title-block">
                <span class="calendar-toolbar__eyebrow">Vista mensual</span>
                <div class="calendar-toolbar__title">{{ $mesActualTexto }}</div>
            </div>

            <div class="calendar-toolbar__group calendar-toolbar__group--end">
                <span class="calendar-toolbar__badge">Calendario de citas</span>
            </div>
        </div>

        <div class="calendar-shell">
            <div class="calendar-table-wrap">
                <table class="calendar-table">
                    <thead>
                        <tr>
                            @foreach ($diasSemana as $diaSemana)
                                <th class="calendar-table__head">
                                    <span>{{ $diaSemana['corto'] }}</span>
                                    <small>{{ $diaSemana['nombre'] }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($semanasCalendario as $semana)
                            <tr>
                                @foreach ($semana as $dia)
                                    @php
                                        $cantidadCitas = $dia['citas']->count();
                                        $textoCitas = $cantidadCitas === 1 ? '1 cita' : $cantidadCitas . ' citas';
                                    @endphp
                                    <td class="calendar-table__cell {{ $dia['isCurrentMonth'] ? '' : 'is-outside' }} {{ $dia['isToday'] ? 'is-today' : '' }}">
                                        <article class="calendar-day-card">
                                            <div class="calendar-day-card__header">
                                                <div class="calendar-day-card__date">
                                                    <span class="calendar-day-card__number">{{ $dia['fecha']->format('j') }}</span>
                                                    @if ($dia['isToday'])
                                                        <span class="calendar-day-card__today">Hoy</span>
                                                    @endif
                                                </div>

                                                <span class="calendar-day-card__count">{{ $textoCitas }}</span>
                                            </div>

                                            <div class="calendar-day-card__body">
                                                @forelse ($dia['citas']->take(3) as $cita)
                                                    @php
                                                        $estadoClase = $cita->estaTerminada() ? 'is-finished' : ($cita->estaCancelada() ? 'is-cancelled' : 'is-reserved');
                                                        $cliente = trim(($cita->cliente?->nombres ?? '') . ' ' . ($cita->cliente?->apellidos ?? '')) ?: 'Sin cliente';
                                                    @endphp

                                                    @if ($canShow)
                                                        <a href="{{ route('citas.show', $cita) }}" class="calendar-chip {{ $estadoClase }}">
                                                            <span class="calendar-chip__time">{{ substr((string) $cita->hora_inicio, 0, 5) }}</span>
                                                            <span class="calendar-chip__name">{{ $cliente }}</span>
                                                            <span class="calendar-chip__status">{{ $cita->estado?->nombre ?? 'Sin estado' }}</span>
                                                        </a>
                                                    @else
                                                        <div class="calendar-chip {{ $estadoClase }}">
                                                            <span class="calendar-chip__time">{{ substr((string) $cita->hora_inicio, 0, 5) }}</span>
                                                            <span class="calendar-chip__name">{{ $cliente }}</span>
                                                            <span class="calendar-chip__status">{{ $cita->estado?->nombre ?? 'Sin estado' }}</span>
                                                        </div>
                                                    @endif
                                                @empty
                                                    <div class="calendar-day-card__empty">
                                                        {{ $dia['isCurrentMonth'] ? 'Sin citas registradas' : 'Fuera del mes actual' }}
                                                    </div>
                                                @endforelse

                                                @if ($cantidadCitas > 3)
                                                    <div class="calendar-day-card__more">+ {{ $cantidadCitas - 3 }} citas mas</div>
                                                @endif
                                            </div>
                                        </article>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-card>
</div>
@endsection
