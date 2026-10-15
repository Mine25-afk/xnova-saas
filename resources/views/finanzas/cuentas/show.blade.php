@extends('layouts.main')

@section('title', 'Detalles de Cuenta — xNova')
@section('page-title', 'Finanzas / Cuentas / Detalles')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/detalle.css') }}">
    <style>
        .account-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }

        /* Grid de bolsillos: 3 columnas en escritorio, 2 en tablet, 1 en móvil */
        .fin-card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            align-items: stretch;
        }
        .fin-card-grid > .fin-card { height: 100%; }
        @media (max-width: 1024px) {
            .fin-card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .fin-card-grid { grid-template-columns: minmax(0, 1fr); }
        }

        /* Card "+ Agregar bolsillo": estilo dashed, misma altura que las demás */
        .fin-card-add {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 100%;
            padding: 18px 16px;
            border: 1.5px dashed var(--border, #e7e9ef);
            border-radius: 14px;
            background: transparent;
            color: var(--text-secondary, #666b75);
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease, color .18s ease, transform .18s ease;
        }
        .fin-card-add:hover {
            border-color: var(--primary, #4461f2);
            background: var(--primary-soft, rgba(68, 97, 242, .08));
            transform: translateY(-1px);
        }
        .fin-card-add__icon {
            width: 38px; height: 38px;
            border-radius: 11px;
            background: var(--primary-soft, rgba(68, 97, 242, .1));
            color: var(--primary, #4461f2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .fin-card-add__icon svg { width: 20px; height: 20px; }
        .fin-card-add:hover .fin-card-add__icon {
            background: var(--primary, #4461f2);
            color: #fff;
        }
        .fin-card-add__label {
            font-size: .9rem;
            font-weight: 700;
            color: var(--text-primary, #17181c);
        }
        .fin-card-add:hover .fin-card-add__label { color: var(--primary, #4461f2); }
        [x-cloak] { display: none !important; }

        /* ===== Modal Ajustar saldo ===== */
        .aj-overlay {
            position: fixed; inset: 0; z-index: 1000;
            background: rgba(23, 24, 28, .45);
            display: flex; align-items: center; justify-content: center;
            padding: 18px;
        }
        .aj-modal {
            background: var(--surface, #fff);
            border-radius: 18px;
            width: 100%; max-width: 520px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0,0,0,.25);
        }
        .aj-modal__head {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
            padding: 20px 22px 8px;
        }
        .aj-modal__title { font-size: 1.1rem; font-weight: 700; color: var(--text-primary, #17181c); margin: 0; }
        .aj-modal__sub { font-size: .82rem; color: var(--text-secondary, #666b75); margin: 4px 0 0; line-height: 1.4; }
        .aj-close { background: none; border: none; font-size: 1.6rem; line-height: 1; color: var(--text-secondary, #666b75); cursor: pointer; padding: 0 4px; }
        .aj-close:hover { color: var(--text-primary, #17181c); }
        .aj-body { padding: 8px 22px 4px; }
        .aj-field { display: block; margin-bottom: 16px; }
        .aj-label { display: block; font-size: .82rem; font-weight: 600; color: var(--text-primary, #17181c); margin-bottom: 6px; }
        .aj-input {
            width: 100%; height: 44px; border-radius: 11px;
            border: 1.5px solid var(--border, #e7e9ef);
            padding: 0 12px; font-size: .95rem; color: var(--text-primary, #17181c);
            background: var(--bg, #f6f7fb); transition: border-color .15s ease, background .15s ease;
        }
        .aj-input:focus { outline: none; border-color: var(--primary, #4461f2); background: #fff; }
        .aj-input--sm { height: 38px; width: 120px; text-align: right; }
        .aj-input.is-error { border-color: var(--danger, #ef4444); }

        .aj-diff { border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; background: var(--bg, #f6f7fb); border: 1px solid var(--border, #e7e9ef); }
        .aj-diff--pos { background: rgba(16,185,129,.08); border-color: rgba(16,185,129,.3); }
        .aj-diff--neg { background: rgba(239,68,68,.07); border-color: rgba(239,68,68,.3); }
        .aj-diff__row { display: flex; justify-content: space-between; align-items: center; font-size: .88rem; color: var(--text-secondary, #666b75); padding: 2px 0; }
        .aj-diff__row strong { color: var(--text-primary, #17181c); font-size: .98rem; }
        .aj-diff__hint { font-size: .8rem; color: var(--text-secondary, #666b75); margin: 6px 0 0; }

        .aj-pockets__head { display: flex; justify-content: space-between; align-items: center; font-size: .82rem; font-weight: 600; color: var(--text-primary, #17181c); margin-bottom: 8px; gap: 8px; }
        .aj-remaining { font-weight: 600; color: var(--text-secondary, #666b75); }
        .aj-remaining.is-over { color: var(--danger, #ef4444); }
        .aj-pocket { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 9px 0; border-top: 1px solid var(--border, #e7e9ef); }
        .aj-pocket__name { display: block; font-size: .9rem; font-weight: 600; color: var(--text-primary, #17181c); }
        .aj-pocket__bal { display: block; font-size: .76rem; color: var(--text-secondary, #666b75); }
        .aj-pocket__input { display: flex; align-items: center; gap: 6px; }
        .aj-mini { background: var(--primary-soft, rgba(68,97,242,.1)); color: var(--primary, #4461f2); border: none; border-radius: 8px; font-size: .74rem; font-weight: 600; padding: 6px 9px; cursor: pointer; }
        .aj-mini:hover { background: var(--primary, #4461f2); color: #fff; }
        .aj-warn { font-size: .8rem; color: var(--danger, #ef4444); margin: 10px 0 0; }

        .aj-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 22px 20px; }
        .aj-foot .btn[disabled] { opacity: .5; cursor: not-allowed; }

        .fin-card-add__hint {
            font-size: .74rem;
            color: var(--text-secondary, #666b75);
            line-height: 1.3;
            max-width: 200px;
        }
    </style>
@endsection

@section('content')
@php
    $inactivosCount = $cuenta->subcuentas->where('active', false)->count();
    $saldoActivos   = $cuenta->subcuentas
        ->filter(fn($b) => $b->active)
        ->sum(fn($b) => (float) ($balances[$b->id] ?? 0));

    // Bolsillos activos para el asistente de ajuste (id, nombre, saldo actual).
    $ajusteBolsillos = $cuenta->subcuentas
        ->filter(fn($b) => $b->active)
        ->map(fn($b) => [
            'id' => $b->id,
            'nombre' => $b->nombre,
            'saldo' => (float) ($balances[$b->id] ?? 0),
        ])->values();
@endphp
<div class="detail-page"
    x-data="cuentaAjuste({
        saldoActual: {{ (float) $saldoTotal }},
        moneda: @js($cuenta->moneda),
        bolsillos: @js($ajusteBolsillos)
    })">
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="account-actions">
        <a href="{{ route('finanzas.cuentas.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20z"/></svg>
            Volver
        </a>
    </div>

    <x-card class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ $cuenta->nombre }}</h1>
                <p class="detail-subtitle">Administra los bolsillos internos de esta cuenta y distribuye el dinero sin moverlo a otra cuenta real.</p>
            </div>

            <div class="detail-toolbar">
                <button type="button" class="btn btn-secondary" @click="abrir()">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;">
                        <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"></path>
                    </svg>
                    Ajustar saldo
                </button>
                <a href="{{ route('finanzas.cuentas.edit', $cuenta) }}" class="btn btn-primary">Editar cuenta</a>
                <span class="detail-status"
                    :class="(showInactivos ? {{ $saldoTotal }} : {{ $saldoActivos }}) >= 0 ? 'is-active' : 'is-inactive'"
                    x-text="showInactivos
                        ? 'Saldo {{ $cuenta->moneda }} {{ number_format((float) $saldoTotal, 2) }}'
                        : 'Saldo {{ $cuenta->moneda }} {{ number_format((float) $saldoActivos, 2) }}'">
                    Saldo {{ $cuenta->moneda }} {{ number_format((float) $saldoActivos, 2) }}
                </span>
            </div>
        </div>

        <div class="detail-body">
            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div>
                        <div class="detail-panel-title">Bolsillos</div>
                        <div class="detail-panel-copy">El bolsillo General recibe el dinero principal. Luego puedes distribuirlo a comida, carro, emergencias u otros fines.</div>
                    </div>
                    @if ($inactivosCount > 0)
                        <button type="button"
                            class="btn btn-secondary"
                            style="font-size:.82rem;padding:6px 14px;"
                            @click="showInactivos = !showInactivos"
                            x-text="showInactivos ? 'Ocultar inactivos' : 'Ver inactivos ({{ $inactivosCount }})'">
                            Ver inactivos ({{ $inactivosCount }})
                        </button>
                    @endif
                </div>

                <div class="fin-card-grid">
                    @foreach ($cuenta->subcuentas as $bolsillo)
                        @php
                            $saldo = (float) ($balances[$bolsillo->id] ?? 0);
                            $meta = (float) ($bolsillo->meta ?? 0);
                            $avance = $meta > 0 ? min(100, max(0, ($saldo / $meta) * 100)) : 0;
                        @endphp
                        <article class="fin-card {{ $bolsillo->active ? '' : 'fin-card--muted' }}"
                            x-show="{{ $bolsillo->active ? 'true' : 'showInactivos' }}"
                            x-cloak>
                            <a href="{{ route('finanzas.cuentas.subcuentas.show', [$cuenta, $bolsillo]) }}" class="fin-card__link" aria-label="Abrir bolsillo {{ $bolsillo->nombre }}">Abrir</a>

                            <div class="fin-card__row">
                                <x-entity-icon :icon="$bolsillo->icono ?: ($bolsillo->es_general ? 'building-library' : 'wallet')" :fallback="$bolsillo->nombre" size="md" />
                                <div class="fin-card__main">
                                    <div class="fin-card__title">{{ $bolsillo->nombre }}</div>
                                    <div class="fin-card__meta">
                                        @if ($bolsillo->es_general)
                                            <span class="fin-card__badge">General</span>
                                        @endif
                                        @if ($meta > 0)
                                            <span>Meta {{ $cuenta->moneda }} {{ number_format($meta, 2) }}</span>
                                            <span class="fin-card__dot"></span>
                                            <span>{{ number_format($avance, 0) }}%</span>
                                        @else
                                            <span>Sin meta</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="fin-card__amount {{ $saldo < 0 ? 'fin-card__amount--neg' : '' }}">{{ $cuenta->moneda }} {{ number_format($saldo, 2) }}</div>
                            </div>

                            @if ($meta > 0)
                                <div class="fin-card__progress" aria-hidden="true">
                                    <div class="fin-card__progress-fill" style="width: {{ $avance }}%"></div>
                                </div>
                            @endif

                            <div class="fin-card__footer">
                                <span class="fin-card__status fin-card__status--{{ $bolsillo->active ? 'on' : 'off' }}">
                                    {{ $bolsillo->active ? 'Activo' : 'Inactivo' }}
                                </span>
                                <div class="fin-card__actions">
                                    <a href="{{ route('finanzas.cuentas.bolsillos.edit', [$cuenta, $bolsillo]) }}">Editar</a>
                                    @unless ($bolsillo->es_general || ! $bolsillo->active)
                                        <form method="POST" action="{{ route('finanzas.cuentas.bolsillos.deactivate', [$cuenta, $bolsillo]) }}" class="fin-card__inline-form" data-block-on-submit>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="fin-card__danger-btn">Desactivar</button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        </article>
                    @endforeach

                    <a href="{{ route('finanzas.cuentas.bolsillos.create', $cuenta) }}" class="fin-card-add" aria-label="Agregar nuevo bolsillo">
                        <span class="fin-card-add__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M9 12h6M12 9v6"></path>
                            </svg>
                        </span>
                        <span class="fin-card-add__label">Agregar bolsillo</span>
                        <span class="fin-card-add__hint">Separa el dinero de esta cuenta por uso</span>
                    </a>
                </div>
            </section>

            <section class="detail-panel">
                <div class="detail-panel-header">
                    <div class="detail-panel-title">Movimientos recientes</div>
                </div>

                <div class="detail-table-wrap">
                    <table class="detail-table">
                        <thead><tr><th>Movimiento</th><th>Bolsillo</th><th>Monto</th></tr></thead>
                        <tbody>
                            @forelse ($movimientosRecientes as $movimiento)
                                <tr>
                                    <td>{{ $movimiento->descripcion }}<br><small>{{ $movimiento->fecha?->format('d/m/Y') }} · {{ $movimiento->tipo }}</small></td>
                                    <td>{{ $movimiento->subcuenta?->nombre ?? 'General' }}{{ $movimiento->subcuentaDestino ? ' -> '.$movimiento->subcuentaDestino->nombre : '' }}</td>
                                    <td>{{ $cuenta->moneda }} {{ number_format((float) $movimiento->monto, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="detail-empty">No hay movimientos para esta cuenta.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </x-card>

    {{-- ===== Modal: Ajustar saldo ===== --}}
    <div class="aj-overlay" x-show="open" x-cloak @keydown.escape.window="cerrar()" style="display:none;">
        <div class="aj-modal" @click.outside="cerrar()">
            <div class="aj-modal__head">
                <div>
                    <h3 class="aj-modal__title">Ajustar saldo de la cuenta</h3>
                    <p class="aj-modal__sub">Escribe el saldo real que tiene esta cuenta. Te diremos la diferencia y desde qué bolsillos repartirla.</p>
                </div>
                <button type="button" class="aj-close" @click="cerrar()" aria-label="Cerrar">&times;</button>
            </div>

            <form method="POST" action="{{ route('finanzas.cuentas.ajustar', $cuenta) }}" @submit="enviar($event)" data-block-on-submit>
                @csrf

                <div class="aj-body">
                    {{-- Saldo real --}}
                    <label class="aj-field">
                        <span class="aj-label">Saldo real de la cuenta ({{ $cuenta->moneda }})</span>
                        <input type="number" step="0.01" name="saldo_real" x-model.number="saldoReal"
                            class="aj-input" placeholder="0.00" required>
                    </label>

                    {{-- Resumen de diferencia --}}
                    <div class="aj-diff"
                        :class="!hayDiferencia ? 'aj-diff--zero' : (signo > 0 ? 'aj-diff--pos' : 'aj-diff--neg')">
                        <div class="aj-diff__row">
                            <span>Saldo actual</span>
                            <strong x-text="fmt(saldoActual)"></strong>
                        </div>
                        <div class="aj-diff__row" x-show="saldoReal !== '' && saldoReal !== null">
                            <span x-text="signo > 0 ? 'Falta agregar' : 'Sobra (a descontar)'"></span>
                            <strong x-text="fmt(absDiferencia)"></strong>
                        </div>
                        <p class="aj-diff__hint" x-show="saldoReal !== '' && !hayDiferencia">
                            El saldo real coincide con el actual. No hay nada que ajustar.
                        </p>
                    </div>

                    {{-- Reparto por bolsillos --}}
                    <template x-if="hayDiferencia">
                        <div class="aj-pockets">
                            <div class="aj-pockets__head">
                                <span x-text="signo > 0 ? '¿A qué bolsillos agregar?' : '¿De qué bolsillos descontar?'"></span>
                                <span class="aj-remaining"
                                    :class="restante < -0.001 ? 'is-over' : ''"
                                    x-text="'Falta repartir: ' + fmt(restante < 0 ? 0 : restante)"></span>
                            </div>

                            <template x-for="(b, i) in bolsillos" :key="b.id">
                                <div class="aj-pocket">
                                    <input type="hidden" :name="'ajustes['+i+'][finanzas_subcuenta_id]'" :value="b.id">
                                    <div class="aj-pocket__info">
                                        <span class="aj-pocket__name" x-text="b.nombre"></span>
                                        <span class="aj-pocket__bal" x-text="'Tiene ' + fmt(b.saldo)"></span>
                                    </div>
                                    <div class="aj-pocket__input">
                                        <input type="number" step="0.01" min="0"
                                            :name="'ajustes['+i+'][monto]'"
                                            x-model.number="amounts[i]"
                                            class="aj-input aj-input--sm"
                                            :class="excedeBolsillo(i) ? 'is-error' : ''"
                                            placeholder="0.00">
                                        <button type="button" class="aj-mini" @click="usarResto(i)">Resto</button>
                                    </div>
                                </div>
                            </template>

                            <p class="aj-warn" x-show="totalAsignado > absDiferencia + 0.001">
                                La suma asignada supera la diferencia. Reduce algún monto.
                            </p>
                            <p class="aj-warn" x-show="algunExcede">
                                Estás descontando de un bolsillo más de lo que tiene.
                            </p>
                        </div>
                    </template>
                </div>

                <div class="aj-foot">
                    <button type="button" class="btn btn-secondary" @click="cerrar()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="!puedeGuardar">Aplicar ajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cuentaAjuste', (cfg) => ({
            // Estado del toggle de inactivos (se mantiene aquí para no romper la vista)
            showInactivos: false,

            // Estado del modal de ajuste
            open: false,
            saldoActual: cfg.saldoActual,
            moneda: cfg.moneda,
            bolsillos: cfg.bolsillos,
            saldoReal: '',
            amounts: [],

            init() {
                this.amounts = this.bolsillos.map(() => '');
            },
            abrir() {
                this.saldoReal = '';
                this.amounts = this.bolsillos.map(() => '');
                this.open = true;
            },
            cerrar() { this.open = false; },

            get diferencia() {
                if (this.saldoReal === '' || this.saldoReal === null) return 0;
                return Math.round((Number(this.saldoReal) - this.saldoActual) * 100) / 100;
            },
            get signo() { return this.diferencia >= 0 ? 1 : -1; },
            get absDiferencia() { return Math.abs(this.diferencia); },
            get hayDiferencia() { return this.absDiferencia >= 0.01; },
            get totalAsignado() {
                return Math.round(this.amounts.reduce((s, v) => s + (Number(v) || 0), 0) * 100) / 100;
            },
            get restante() {
                return Math.round((this.absDiferencia - this.totalAsignado) * 100) / 100;
            },
            excedeBolsillo(i) {
                if (this.signo > 0) return false;
                return (Number(this.amounts[i]) || 0) > this.bolsillos[i].saldo + 0.001;
            },
            get algunExcede() {
                return this.bolsillos.some((b, i) => this.excedeBolsillo(i));
            },
            get puedeGuardar() {
                return this.hayDiferencia
                    && this.totalAsignado > 0
                    && this.totalAsignado <= this.absDiferencia + 0.001
                    && !this.algunExcede;
            },
            usarResto(i) {
                let falta = this.restante + (Number(this.amounts[i]) || 0);
                if (falta < 0) falta = 0;
                if (this.signo < 0) falta = Math.min(falta, this.bolsillos[i].saldo);
                this.amounts[i] = Math.round(falta * 100) / 100;
            },
            fmt(n) {
                return this.moneda + ' ' + Number(n || 0).toLocaleString('es-HN', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2,
                });
            },
            enviar(e) {
                if (!this.puedeGuardar) { e.preventDefault(); }
            },
        }));
    });
</script>
@endsection
