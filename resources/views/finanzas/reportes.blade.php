@extends('layouts.main')

@section('title', 'Reportes Financieros — xNova')
@section('page-title', 'Finanzas / Reportes')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
    <style>
        [x-cloak] { display: none !important; }

        .rep-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
        @media (max-width: 1024px) { .rep-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .rep-grid { grid-template-columns: 1fr; } }

        .rep-card {
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding: 20px;
            background: var(--surface, #fff);
            border: 1px solid var(--border, #e7e9ef);
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(15,23,42,.04);
            text-align: left;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            width: 100%;
            font: inherit;
            color: inherit;
        }
        .rep-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(68,97,242,.12);
            border-color: rgba(68,97,242,.35);
        }
        .rep-card__top { display: flex; align-items: center; gap: 12px; }
        .rep-card__title { font-size: 1.05rem; font-weight: 700; color: var(--text-primary, #17181c); }
        .rep-card__desc { font-size: .84rem; color: var(--text-secondary, #666b75); line-height: 1.45; flex: 1; }
        .rep-card__foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .rep-card__filters {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 11px; border-radius: 999px;
            background: var(--primary-soft, rgba(68,97,242,.1)); color: var(--primary, #4461f2);
            font-size: .76rem; font-weight: 700;
        }
        .rep-card__cta { font-size: .82rem; font-weight: 600; color: var(--primary, #4461f2); display: inline-flex; align-items: center; gap: 4px; }
        .rep-card__cta svg { width: 14px; height: 14px; }

        /* Modal */
        .rep-modal { position: fixed; inset: 0; z-index: 120; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .rep-modal__backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(6px); }
        .rep-modal__dialog {
            position: relative; width: min(540px, 100%); max-height: calc(100vh - 40px); overflow: auto;
            background: #fff; border-radius: 18px; box-shadow: 0 32px 80px rgba(15,23,42,.30);
        }
        .rep-modal__head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 22px 24px 14px; }
        .rep-modal__title { font-size: 1.2rem; font-weight: 700; color: var(--text-primary, #17181c); }
        .rep-modal__copy { font-size: .84rem; color: var(--text-secondary, #666b75); margin-top: 3px; }
        .rep-modal__close { width: 34px; height: 34px; border: none; background: #f6f7fb; border-radius: 10px; color: var(--text-secondary,#666b75); cursor: pointer; display: grid; place-items: center; flex-shrink: 0; }
        .rep-modal__close svg { width: 16px; height: 16px; }
        .rep-modal__close:hover { background: var(--danger-soft, #fee2e2); color: var(--danger, #ef4444); }
        .rep-modal__body { padding: 8px 24px 24px; }

        .rep-field { display: grid; gap: 6px; margin-bottom: 14px; }
        .rep-field > span { font-size: .82rem; font-weight: 600; color: var(--text-secondary, #666b75); }
        .rep-field input, .rep-field select {
            width: 100%; padding: 11px 13px; border: 1px solid var(--border, #e7e9ef); border-radius: 10px;
            background: #fff; font: inherit; font-size: .9rem; color: var(--text-primary, #17181c);
        }
        .rep-field input:focus, .rep-field select:focus { outline: none; border-color: var(--primary, #4461f2); box-shadow: 0 0 0 3px rgba(68,97,242,.1); }
        .rep-field--double { grid-template-columns: 1fr 1fr; gap: 10px; }
        .rep-field--double > div { display: grid; gap: 6px; }

        .rep-actions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 18px; }
        @media (max-width: 480px) { .rep-actions { grid-template-columns: 1fr; } }
        .rep-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 12px 14px; border: none; border-radius: 12px; font: inherit; font-size: .88rem; font-weight: 700; cursor: pointer; text-decoration: none; }
        .rep-btn svg { width: 16px; height: 16px; }
        .rep-btn--view { background: var(--primary, #4461f2); color: #fff; }
        .rep-btn--excel { background: #d1fae5; color: #047857; }
        .rep-btn--pdf { background: #fee2e2; color: #dc2626; }
        .rep-btn:hover { filter: brightness(.97); transform: translateY(-1px); transition: transform .15s ease; }
    </style>
@endsection

@section('content')
@php
    $filtroLabels = ['rango' => 'Rango de fechas', 'cuenta' => 'Cuenta', 'propietario' => 'Propietario', 'tipo' => 'Tipo de movimiento'];
@endphp
<div class="list-page" x-data="{ openReport: null, open(k){ this.openReport = k; document.body.style.overflow='hidden'; }, close(){ this.openReport = null; document.body.style.overflow=''; } }">
    <x-card class="list-card">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Reportes financieros</div>
                    <div class="list-card-subtitle">Genera reportes con filtros y expórtalos a Excel o PDF.</div>
                </div>
                <a href="{{ route('finanzas.dashboard') }}" class="btn btn-secondary">Ir al dashboard</a>
            </div>
        </x-slot>

        <div class="rep-grid">
            @foreach ($reportes as $key => $def)
                <button type="button" class="rep-card" x-on:click="open('{{ $key }}')">
                    <div class="rep-card__top">
                        <x-entity-icon :icon="$def['icono']" :fallback="$def['titulo']" size="lg" />
                        <div class="rep-card__title">{{ $def['titulo'] }}</div>
                    </div>
                    <div class="rep-card__desc">{{ $def['descripcion'] }}</div>
                    <div class="rep-card__foot">
                        <span class="rep-card__filters">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M7 12h10M11 18h2"></path></svg>
                            {{ count($def['filtros']) }} {{ count($def['filtros']) === 1 ? 'filtro' : 'filtros' }}
                        </span>
                        <span class="rep-card__cta">
                            Filtros y exportar
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                        </span>
                    </div>
                </button>
            @endforeach
        </div>
    </x-card>

    {{-- Modales de filtros (uno por reporte) --}}
    @foreach ($reportes as $key => $def)
        <div class="rep-modal" x-show="openReport === '{{ $key }}'" x-cloak x-on:keydown.escape.window="close()">
            <div class="rep-modal__backdrop" x-on:click="close()"></div>
            <div class="rep-modal__dialog">
                <div class="rep-modal__head">
                    <div>
                        <div class="rep-modal__title">{{ $def['titulo'] }}</div>
                        <div class="rep-modal__copy">Ajusta los filtros y elige cómo exportar.</div>
                    </div>
                    <button type="button" class="rep-modal__close" x-on:click="close()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                    </button>
                </div>

                <div class="rep-modal__body">
                    <form method="GET" action="{{ route('finanzas.reportes.ver', $key) }}" target="_blank">
                        @if (in_array('rango', $def['filtros']))
                            <div class="rep-field rep-field--double">
                                <div>
                                    <span>Desde</span>
                                    <input type="date" name="fecha_inicio" value="{{ now()->startOfMonth()->toDateString() }}">
                                </div>
                                <div>
                                    <span>Hasta</span>
                                    <input type="date" name="fecha_fin" value="{{ now()->toDateString() }}">
                                </div>
                            </div>
                        @endif

                        @if (in_array('cuenta', $def['filtros']))
                            <label class="rep-field">
                                <span>Cuenta</span>
                                <select name="cuenta">
                                    <option value="">Todas las cuentas</option>
                                    @foreach ($cuentas as $c)
                                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        @if (in_array('propietario', $def['filtros']))
                            <label class="rep-field">
                                <span>Propietario</span>
                                <select name="propietario">
                                    <option value="">Todos los propietarios</option>
                                    @foreach ($propietarios as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        @if (in_array('tipo', $def['filtros']))
                            <label class="rep-field">
                                <span>Tipo de movimiento</span>
                                <select name="tipo">
                                    <option value="">Todos los tipos</option>
                                    @foreach ($tiposMovimiento as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif

                        <div class="rep-actions">
                            <button type="submit" class="rep-btn rep-btn--view">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                Ver
                            </button>
                            <button type="submit" formaction="{{ route('finanzas.reportes.excel', $key) }}" class="rep-btn rep-btn--excel">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M9 13l6 4M15 13l-6 4"></path></svg>
                                Excel
                            </button>
                            <button type="submit" formaction="{{ route('finanzas.reportes.pdf', $key) }}" class="rep-btn rep-btn--pdf">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M9 15h6M9 11h2"></path></svg>
                                PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
