@extends('layouts.main')

@section('title', $subcuenta->nombre . ' — xNova')
@section('page-title', 'Finanzas / Cuentas / ' . $cuenta->nombre . ' / ' . $subcuenta->nombre)

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/finanzas-modals.css') }}?v=2">
@endsection

@section('content')
<div
    class="subcuenta-page"
    x-data="subcuentaActions({
        ingresoUrl: @js(route('finanzas.cuentas.subcuentas.ingreso', [$cuenta, $subcuenta])),
        gastoUrl: @js(route('finanzas.cuentas.subcuentas.gasto', [$cuenta, $subcuenta])),
        transferirUrl: @js(route('finanzas.cuentas.subcuentas.transferir', [$cuenta, $subcuenta])),
        anularUrlTemplate: @js(route('finanzas.cuentas.subcuentas.movimientos.anular', [$cuenta, $subcuenta, 'MOV_ID'])),
        moneda: @js($cuenta->moneda),
        saldo: @js((float) $saldo),
        cuentaId: @js($cuenta->id),
        subcuentaNombre: @js($subcuenta->nombre),
        cuentas: @js($cuentasDestino),
        propietarios: @js($propietariosDestino),
        deudas: @js($deudasPendientes),
        pagarDeudaUrl: @js(route('finanzas.cuentas.subcuentas.pagar-deuda', [$cuenta, $subcuenta])),
    })"
>
    {{-- HEADER --}}
    <header class="sub-header">
        <a href="{{ route('finanzas.cuentas.show', $cuenta) }}" class="sub-header__back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            <span>Volver a {{ $cuenta->nombre }}</span>
        </a>

        <div class="sub-header__main">
            <x-entity-icon :icon="$subcuenta->icono" :fallback="$subcuenta->nombre" size="xl" />
            <div class="sub-header__text">
                <h1 class="sub-header__title">{{ $subcuenta->nombre }}</h1>
                <p class="sub-header__meta">
                    Bolsillo de <strong>{{ $cuenta->nombre }}</strong>
                    @if ($subcuenta->es_general)
                        · General
                    @endif
                </p>
            </div>
            <div class="sub-header__balance">
                <span class="sub-header__balance-label">Saldo disponible</span>
                <strong class="sub-header__balance-value">{{ $cuenta->moneda }} {{ number_format((float) $saldo, 2) }}</strong>
            </div>
        </div>
    </header>

    {{-- 3 ACTION CARDS --}}
    <div class="action-cards">
        <button type="button" class="action-card action-card--ingreso" x-on:click="openModal('ingreso')" style="--card-delay: 0ms">
            <span class="action-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14"></path>
                </svg>
            </span>
            <span class="action-card__body">
                <span class="action-card__title">Agregar ingreso</span>
                <span class="action-card__copy">Suma dinero a este bolsillo</span>
            </span>
            <span class="action-card__arrow" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
            </span>
        </button>

        <button type="button" class="action-card action-card--gasto" x-on:click="openModal('gasto')" style="--card-delay: 80ms">
            <span class="action-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"></path>
                </svg>
            </span>
            <span class="action-card__body">
                <span class="action-card__title">Agregar gasto</span>
                <span class="action-card__copy">Resta dinero de este bolsillo</span>
            </span>
            <span class="action-card__arrow" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
            </span>
        </button>

        <button type="button" class="action-card action-card--transferir {{ (float) $saldo <= 0 ? 'action-card--blocked' : '' }}" x-on:click="openModal('transferir')" style="--card-delay: 160ms">
            <span class="action-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h13l-3-3M21 17H8l3 3"></path>
                </svg>
            </span>
            <span class="action-card__body">
                <span class="action-card__title">Transferir dinero</span>
                <span class="action-card__copy">Envíalo a otro bolsillo</span>
            </span>
            <span class="action-card__arrow" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
            </span>
        </button>

        @if ($puedePagarDeuda && $totalDeudas > 0)
            <button type="button" class="action-card action-card--deuda" x-on:click="openModal('deuda')" style="--card-delay: 240ms">
                <span class="action-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 7h18v10H3z"></path><path d="M3 11h18M7 15h4"></path>
                    </svg>
                </span>
                <span class="action-card__body">
                    <span class="action-card__title">Pagar deuda</span>
                    <span class="action-card__copy">Abona desde este bolsillo</span>
                </span>
                <span class="action-card__arrow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                </span>
            </button>
        @endif
    </div>

    {{-- MOVIMIENTOS RECIENTES --}}
    <section class="sub-history">
        <header class="sub-history__head">
            <h2 class="sub-history__title">Movimientos recientes</h2>
            <span class="sub-history__count">{{ $movimientos->count() }}</span>
        </header>

        @if ($movimientos->isEmpty())
            <div class="sub-history__empty">
                <p>Todavía no hay movimientos en este bolsillo.</p>
                <p class="sub-history__empty-hint">Usa las acciones de arriba para registrar el primero.</p>
            </div>
        @else
            <ul class="sub-history__list">
                @foreach ($movimientos as $mov)
                    @php
                        $esEntrada = $mov->tipo === \App\Models\FinanzasMovimiento::TIPO_INGRESO
                            || $mov->tipo === \App\Models\FinanzasMovimiento::TIPO_AJUSTE
                            || ($mov->tipo === \App\Models\FinanzasMovimiento::TIPO_TRANSFERENCIA && (int) $mov->finanzas_subcuenta_destino_id === (int) $subcuenta->id);
                        $tipoLabel = match ($mov->tipo) {
                            \App\Models\FinanzasMovimiento::TIPO_INGRESO => 'Ingreso',
                            \App\Models\FinanzasMovimiento::TIPO_GASTO => 'Gasto',
                            \App\Models\FinanzasMovimiento::TIPO_TRANSFERENCIA => $esEntrada ? 'Recibido' : 'Enviado',
                            \App\Models\FinanzasMovimiento::TIPO_AJUSTE => 'Ajuste',
                            default => ucfirst($mov->tipo),
                        };
                        $anulado = $mov->anulado_en !== null;
                        $montoMostrar = $anulado ? (float) ($mov->monto_original ?? $mov->monto) : (float) $mov->monto;
                    @endphp
                    <li class="sub-history__item {{ $anulado ? 'is-anulado' : '' }}">
                        <span class="sub-history__item-icon {{ $esEntrada ? 'is-positive' : 'is-negative' }}">
                            @if ($esEntrada)
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path></svg>
                            @endif
                        </span>
                        <div class="sub-history__item-body">
                            <div class="sub-history__item-title">
                                {{ $mov->descripcion }}
                                @if ($anulado)
                                    <span class="sub-history__chip">Anulado</span>
                                @endif
                            </div>
                            <div class="sub-history__item-meta">{{ $tipoLabel }} · {{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y') }}</div>
                            @if ($anulado && $mov->motivo_anulacion)
                                <div class="sub-history__item-note">Motivo: {{ $mov->motivo_anulacion }}</div>
                            @endif
                        </div>
                        <div class="sub-history__item-amount {{ $esEntrada ? 'is-positive' : 'is-negative' }}">
                            {{ $esEntrada ? '+' : '−' }}{{ $cuenta->moneda }} {{ number_format($montoMostrar, 2) }}
                        </div>
                        @unless ($anulado)
                            <button type="button"
                                    class="sub-history__item-action"
                                    title="Anular movimiento"
                                    x-on:click="openAnular({{ $mov->id }}, @js($mov->descripcion))">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 6l12 12M18 6 6 18"></path>
                                </svg>
                            </button>
                        @endunless
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- MODAL: ANULAR --}}
    <div class="action-modal" x-show="modal === 'anular'" x-cloak x-on:keydown.escape.window="closeModal()">
        <div class="action-modal__backdrop" x-on:click="closeModal()" x-transition.opacity></div>
        <div class="action-modal__dialog"
             x-transition:enter="action-modal__enter"
             x-transition:enter-start="action-modal__enter-start"
             x-transition:enter-end="action-modal__enter-end"
        >
            <header class="action-modal__head">
                <div>
                    <h2 class="action-modal__title">Anular movimiento</h2>
                    <p class="action-modal__copy" x-text="anular.descripcion ? ('“' + anular.descripcion + '” se pondrá en cero.') : 'El movimiento se pondrá en cero.'"></p>
                </div>
                <button type="button" class="action-modal__close" x-on:click="closeModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </header>

            <div class="action-modal__body">
                <div class="action-modal__form">
                    <label class="action-modal__field">
                        <span>Motivo de la anulación</span>
                        <textarea x-model="anular.motivo" rows="3" placeholder="Ej. Cargo duplicado, error de monto, devolución…" maxlength="240"></textarea>
                    </label>
                    <button type="button" class="action-modal__submit action-modal__submit--danger"
                            x-on:click="submitAnular()"
                            :disabled="loading || !anular.motivo || anular.motivo.trim().length < 3">
                        <span x-show="!loading">Anular movimiento</span>
                        <span x-show="loading">Anulando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: INGRESO --}}
    <div class="action-modal" x-show="modal === 'ingreso'" x-cloak x-on:keydown.escape.window="closeModal()">
        <div class="action-modal__backdrop" x-on:click="closeModal()" x-transition.opacity></div>
        <div class="action-modal__dialog"
             x-transition:enter="action-modal__enter"
             x-transition:enter-start="action-modal__enter-start"
             x-transition:enter-end="action-modal__enter-end"
        >
            <header class="action-modal__head">
                <div>
                    <h2 class="action-modal__title">Agregar ingreso</h2>
                    <p class="action-modal__copy">Elige un ingreso fijo o registra uno personalizado.</p>
                </div>
                <button type="button" class="action-modal__close" x-on:click="closeModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </header>

            <div class="action-modal__tabs">
                <button type="button" :class="tab === 'fijos' ? 'is-active' : ''" x-on:click="tab = 'fijos'">Ingresos fijos</button>
                <button type="button" :class="tab === 'custom' ? 'is-active' : ''" x-on:click="tab = 'custom'">Personalizado</button>
            </div>

            <div class="action-modal__body">
                {{-- Sub-vista: preguntar monto cuando el fijo tiene preguntar_al_usar --}}
                <div x-show="askFijo && askFijo.tipo === 'ingreso'" x-cloak>
                    <div class="fijo-ask">
                        <button type="button" class="fijo-ask__back" x-on:click="cancelarAskFijo()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                            Volver
                        </button>
                        <div class="fijo-ask__head">
                            <div class="fijo-ask__name" x-text="askFijo ? askFijo.nombre : ''"></div>
                            <div class="fijo-ask__saldo">
                                Saldo restante: <strong x-text="askFijo ? fmt(askFijo.saldo) : ''"></strong>
                                <span class="fijo-ask__saldo-meta">de <span x-text="askFijo ? fmt(askFijo.monto) : ''"></span></span>
                            </div>
                        </div>
                        <div class="action-modal__form">
                            <label class="action-modal__field">
                                <span>¿Cuánto vas a usar?</span>
                                <input type="number" step="0.01" min="0.01" x-model="askMonto" x-ref="askInput" :max="askFijo ? askFijo.saldo : null" placeholder="0.00">
                            </label>
                            <button type="button" class="fijo-ask__max" x-on:click="if (askFijo) askMonto = askFijo.saldo">
                                Usar todo el saldo (<span x-text="askFijo ? fmt(askFijo.saldo) : ''"></span>)
                            </button>
                            <button type="button" class="action-modal__submit"
                                    x-on:click="submitAskFijo()"
                                    :disabled="loading || !askFijo || !askMonto || Number(askMonto) <= 0">
                                <span x-show="!loading">Registrar ingreso</span>
                                <span x-show="loading">Guardando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="!askFijo && tab === 'fijos'">
                    @if ($ingresosFijos->isEmpty())
                        <div class="action-modal__empty">
                            Aún no tienes ingresos fijos registrados.<br>
                            Usa la pestaña <strong>Personalizado</strong> o crea uno desde el módulo de ingresos fijos.
                        </div>
                    @else
                        <div class="action-modal__options">
                            @foreach ($ingresosFijos as $fijo)
                                @php
                                    $preguntar = (bool) $fijo->preguntar_al_usar;
                                    $saldo = $preguntar ? (float) ($fijo->saldo_restante ?? $fijo->monto) : (float) $fijo->monto;
                                @endphp
                                <button type="button" class="action-modal__option"
                                        x-on:click="pickFijo('ingreso', @js([
                                            'id' => $fijo->id,
                                            'nombre' => $fijo->nombre,
                                            'monto' => (float) $fijo->monto,
                                            'preguntar' => $preguntar,
                                            'saldo' => $saldo,
                                        ]))"
                                        :disabled="loading">
                                    <x-entity-icon :icon="$fijo->icono" :fallback="$fijo->nombre" size="sm" />
                                    <span class="action-modal__option-text">
                                        <span class="action-modal__option-name">
                                            {{ $fijo->nombre }}
                                            @if ($preguntar)
                                                <span class="fijo-chip">Pregunta</span>
                                            @endif
                                        </span>
                                        <span class="action-modal__option-amount">
                                            @if ($preguntar)
                                                Saldo: {{ $cuenta->moneda }} {{ number_format($saldo, 2) }} / {{ number_format((float) $fijo->monto, 2) }}
                                            @else
                                                {{ $cuenta->moneda }} {{ number_format((float) $fijo->monto, 2) }}
                                            @endif
                                        </span>
                                    </span>
                                    <svg class="action-modal__option-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="!askFijo && tab === 'custom'" x-cloak>
                    <div class="action-modal__form">
                        <label class="action-modal__field">
                            <span>Nombre del ingreso</span>
                            <input type="text" x-model="custom.nombre" placeholder="Ej. Pago freelance" maxlength="180">
                        </label>
                        <label class="action-modal__field">
                            <span>Monto</span>
                            <input type="number" step="0.01" min="0.01" x-model="custom.monto" placeholder="0.00">
                        </label>
                        <button type="button" class="action-modal__submit" x-on:click="submitAction('ingreso', custom.nombre, custom.monto)" :disabled="loading || !custom.nombre || !custom.monto">
                            <span x-show="!loading">Registrar ingreso</span>
                            <span x-show="loading">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: GASTO --}}
    <div class="action-modal" x-show="modal === 'gasto'" x-cloak x-on:keydown.escape.window="closeModal()">
        <div class="action-modal__backdrop" x-on:click="closeModal()" x-transition.opacity></div>
        <div class="action-modal__dialog"
             x-transition:enter="action-modal__enter"
             x-transition:enter-start="action-modal__enter-start"
             x-transition:enter-end="action-modal__enter-end"
        >
            <header class="action-modal__head">
                <div>
                    <h2 class="action-modal__title">Agregar gasto</h2>
                    <p class="action-modal__copy">Elige un gasto fijo o registra uno personalizado.</p>
                </div>
                <button type="button" class="action-modal__close" x-on:click="closeModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </header>

            <div class="action-modal__tabs">
                <button type="button" :class="tab === 'fijos' ? 'is-active' : ''" x-on:click="tab = 'fijos'">Gastos fijos</button>
                <button type="button" :class="tab === 'custom' ? 'is-active' : ''" x-on:click="tab = 'custom'">Personalizado</button>
            </div>

            <div class="action-modal__body">
                {{-- Sub-vista: preguntar monto cuando el fijo tiene preguntar_al_usar --}}
                <div x-show="askFijo && askFijo.tipo === 'gasto'" x-cloak>
                    <div class="fijo-ask">
                        <button type="button" class="fijo-ask__back" x-on:click="cancelarAskFijo()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                            Volver
                        </button>
                        <div class="fijo-ask__head">
                            <div class="fijo-ask__name" x-text="askFijo ? askFijo.nombre : ''"></div>
                            <div class="fijo-ask__saldo">
                                Saldo restante: <strong x-text="askFijo ? fmt(askFijo.saldo) : ''"></strong>
                                <span class="fijo-ask__saldo-meta">de <span x-text="askFijo ? fmt(askFijo.monto) : ''"></span></span>
                            </div>
                        </div>
                        <div class="action-modal__form">
                            <label class="action-modal__field">
                                <span>¿Cuánto vas a usar?</span>
                                <input type="number" step="0.01" min="0.01" x-model="askMonto" x-ref="askInput" :max="askFijo ? askFijo.saldo : null" placeholder="0.00">
                            </label>
                            <button type="button" class="fijo-ask__max" x-on:click="if (askFijo) askMonto = askFijo.saldo">
                                Usar todo el saldo (<span x-text="askFijo ? fmt(askFijo.saldo) : ''"></span>)
                            </button>
                            <button type="button" class="action-modal__submit"
                                    x-on:click="submitAskFijo()"
                                    :disabled="loading || !askFijo || !askMonto || Number(askMonto) <= 0">
                                <span x-show="!loading">Registrar gasto</span>
                                <span x-show="loading">Guardando...</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="!askFijo && tab === 'fijos'">
                    @if ($gastosFijos->isEmpty())
                        <div class="action-modal__empty">
                            Aún no tienes gastos fijos registrados.<br>
                            Usa la pestaña <strong>Personalizado</strong> o crea uno desde el módulo de gastos fijos.
                        </div>
                    @else
                        <div class="action-modal__options">
                            @foreach ($gastosFijos as $fijo)
                                @php
                                    $preguntar = (bool) $fijo->preguntar_al_usar;
                                    $saldo = $preguntar ? (float) ($fijo->saldo_restante ?? $fijo->monto) : (float) $fijo->monto;
                                @endphp
                                <button type="button" class="action-modal__option"
                                        x-on:click="pickFijo('gasto', @js([
                                            'id' => $fijo->id,
                                            'nombre' => $fijo->nombre,
                                            'monto' => (float) $fijo->monto,
                                            'preguntar' => $preguntar,
                                            'saldo' => $saldo,
                                        ]))"
                                        :disabled="loading">
                                    <x-entity-icon :icon="$fijo->icono" :fallback="$fijo->nombre" size="sm" />
                                    <span class="action-modal__option-text">
                                        <span class="action-modal__option-name">
                                            {{ $fijo->nombre }}
                                            @if ($preguntar)
                                                <span class="fijo-chip">Pregunta</span>
                                            @endif
                                        </span>
                                        <span class="action-modal__option-amount">
                                            @if ($preguntar)
                                                Saldo: {{ $cuenta->moneda }} {{ number_format($saldo, 2) }} / {{ number_format((float) $fijo->monto, 2) }}
                                            @else
                                                {{ $cuenta->moneda }} {{ number_format((float) $fijo->monto, 2) }}
                                            @endif
                                        </span>
                                    </span>
                                    <svg class="action-modal__option-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="!askFijo && tab === 'custom'" x-cloak>
                    <div class="action-modal__form">
                        <label class="action-modal__field">
                            <span>Nombre del gasto</span>
                            <input type="text" x-model="custom.nombre" placeholder="Ej. Café" maxlength="180">
                        </label>
                        <label class="action-modal__field">
                            <span>Monto</span>
                            <input type="number" step="0.01" min="0.01" x-model="custom.monto" placeholder="0.00">
                        </label>
                        <button type="button" class="action-modal__submit" x-on:click="submitAction('gasto', custom.nombre, custom.monto)" :disabled="loading || !custom.nombre || !custom.monto">
                            <span x-show="!loading">Registrar gasto</span>
                            <span x-show="loading">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: PAGAR DEUDA --}}
    <div class="action-modal" x-show="modal === 'deuda'" x-cloak x-on:keydown.escape.window="closeModal()">
        <div class="action-modal__backdrop" x-on:click="closeModal()" x-transition.opacity></div>
        <div class="action-modal__dialog"
             x-transition:enter="action-modal__enter"
             x-transition:enter-start="action-modal__enter-start"
             x-transition:enter-end="action-modal__enter-end"
        >
            <header class="action-modal__head">
                <div>
                    <h2 class="action-modal__title">Pagar deuda</h2>
                    <p class="action-modal__copy">El abono sale de este bolsillo y se registra como gasto.</p>
                </div>
                <button type="button" class="action-modal__close" x-on:click="closeModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </header>

            <div class="action-modal__body">
                {{-- Paso 1: elegir deuda (tarjetas con su vencimiento) --}}
                <div x-show="!deudaSeleccionada" x-cloak>
                    <div class="wizard-context" style="padding-bottom:10px;">
                        Saldo disponible en este bolsillo: <strong x-text="fmt(config.saldo)"></strong>
                    </div>
                    <template x-if="!deudas.length">
                        <div class="action-modal__empty">No hay deudas pendientes por pagar.</div>
                    </template>
                    <div class="action-modal__options" x-show="deudas.length">
                        <template x-for="d in deudas" :key="d.id">
                            <button type="button" class="action-modal__option" x-on:click="seleccionarDeuda(d.id)">
                                <span class="wizard-card__icon" x-text="(d.nombre || '?').charAt(0).toUpperCase()"></span>
                                <span class="action-modal__option-text">
                                    <span class="action-modal__option-name" x-text="d.nombre"></span>
                                    <span class="action-modal__option-amount">Pendiente: <span x-text="fmt(d.saldo_pendiente)"></span></span>
                                    <template x-if="d.vencimiento">
                                        <span class="action-modal__option-due" :class="d.vencida ? 'is-overdue' : ''">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"></path></svg>
                                            <span x-text="(d.vencida ? 'Venció ' : 'Vence ') + d.vencimiento"></span>
                                        </span>
                                    </template>
                                </span>
                                <svg class="action-modal__option-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Paso 2: monto a abonar --}}
                <div x-show="deudaSeleccionada" x-cloak>
                    <button type="button" class="fijo-ask__back" x-on:click="pago.deudaId = ''; pago.monto = ''">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                        Cambiar deuda
                    </button>

                    <div class="wizard-summary" style="margin-top:12px;">
                        <div class="wizard-summary__row">
                            <span>Deuda</span>
                            <strong x-text="deudaSeleccionada ? deudaSeleccionada.nombre : ''"></strong>
                        </div>
                        <div class="wizard-summary__row">
                            <span>Saldo pendiente</span>
                            <strong x-text="deudaSeleccionada ? fmt(deudaSeleccionada.saldo_pendiente) : ''"></strong>
                        </div>
                        <div class="wizard-summary__row wizard-summary__row--max">
                            <span>Disponible en bolsillo</span>
                            <strong x-text="fmt(config.saldo)"></strong>
                        </div>
                    </div>

                    <div class="wizard-warning" x-show="deudaSeleccionada && Number(config.saldo) < Number(deudaSeleccionada.saldo_pendiente)" x-cloak style="margin-bottom:14px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
                        <div>El saldo de este bolsillo no cubre toda la deuda. Puedes abonar como máximo <strong x-text="fmt(maxPagoDeuda)"></strong>.</div>
                    </div>

                    <div class="action-modal__form">
                        <label class="action-modal__field">
                            <span>Monto a abonar</span>
                            <input type="number" step="0.01" min="0.01" x-model="pago.monto" :max="maxPagoDeuda" placeholder="0.00">
                        </label>
                        <button type="button" class="fijo-ask__max" x-on:click="pago.monto = maxPagoDeuda">
                            Abonar máximo (<span x-text="fmt(maxPagoDeuda)"></span>)
                        </button>
                        <label class="pend-check">
                            <input type="checkbox" x-model="pago.pendiente">
                            <span>Dejar como <strong>pendiente</strong> (no descuenta hasta confirmar)</span>
                        </label>
                        <button type="button" class="action-modal__submit" x-on:click="submitPagoDeuda()" :disabled="loading || !pago.monto || Number(pago.monto) <= 0 || Number(pago.monto) > maxPagoDeuda">
                            <span x-show="!loading" x-text="pago.pendiente ? 'Dejar pendiente' : 'Registrar pago'"></span>
                            <span x-show="loading">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="action-modal" x-show="modal === 'transferir'" x-cloak x-on:keydown.escape.window="closeModal()">
        <div class="action-modal__backdrop" x-on:click="closeModal()" x-transition.opacity></div>
        <div class="action-modal__dialog"
             x-transition:enter="action-modal__enter"
             x-transition:enter-start="action-modal__enter-start"
             x-transition:enter-end="action-modal__enter-end"
        >
            <header class="action-modal__head">
                <div class="wizard-head__title-row">
                    <button type="button" class="wizard-back" x-show="transferStep > (needsPropietarioStep ? 0 : 1)" x-on:click="transferBack()" title="Atrás">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>
                    <div>
                        <h2 class="action-modal__title">Transferir dinero</h2>
                        <p class="action-modal__copy">
                            <span x-show="transferStep === 0">Paso 1 de <span x-text="totalSteps"></span> · Elige el propietario destino</span>
                            <span x-show="transferStep === 1" x-cloak>Paso <span x-text="needsPropietarioStep ? 2 : 1"></span> de <span x-text="totalSteps"></span> · Elige la cuenta destino</span>
                            <span x-show="transferStep === 2" x-cloak>Paso <span x-text="needsPropietarioStep ? 3 : 2"></span> de <span x-text="totalSteps"></span> · Elige el bolsillo</span>
                            <span x-show="transferStep === 3" x-cloak>Paso <span x-text="totalSteps"></span> de <span x-text="totalSteps"></span> · Ingresa el monto</span>
                        </p>
                    </div>
                </div>
                <button type="button" class="action-modal__close" x-on:click="closeModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </header>

            {{-- Indicador de pasos --}}
            <div class="wizard-steps">
                <template x-if="needsPropietarioStep">
                    <span class="wizard-steps__dot" :class="transferStep >= 0 ? 'is-active' : ''"></span>
                </template>
                <template x-if="needsPropietarioStep">
                    <span class="wizard-steps__bar" :class="transferStep >= 1 ? 'is-active' : ''"></span>
                </template>
                <span class="wizard-steps__dot" :class="transferStep >= 1 ? 'is-active' : ''"></span>
                <span class="wizard-steps__bar" :class="transferStep >= 2 ? 'is-active' : ''"></span>
                <span class="wizard-steps__dot" :class="transferStep >= 2 ? 'is-active' : ''"></span>
                <span class="wizard-steps__bar" :class="transferStep >= 3 ? 'is-active' : ''"></span>
                <span class="wizard-steps__dot" :class="transferStep >= 3 ? 'is-active' : ''"></span>
            </div>

            <div class="action-modal__body">
                @if (empty($cuentasDestino))
                    <div class="action-modal__empty">
                        No hay otros bolsillos disponibles para transferir.<br>
                        Crea otro bolsillo en alguna cuenta primero.
                    </div>
                @else
                    {{-- PASO 0: PROPIETARIOS (solo si hay más de uno) --}}
                    <div class="wizard-grid" x-show="transferStep === 0" x-cloak>
                        <template x-for="prop in propietarios" :key="prop.id ?? 'null'">
                            <button type="button" class="wizard-card" x-on:click="selectPropietario(prop)">
                                <span class="wizard-card__icon" x-text="(prop.nombre || '?').charAt(0).toUpperCase()"></span>
                                <span class="wizard-card__text">
                                    <span class="wizard-card__name" x-text="prop.nombre"></span>
                                    <span class="wizard-card__meta" x-text="countCuentasPropietario(prop.id) + ' cuenta(s)'"></span>
                                </span>
                                <svg class="wizard-card__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                            </button>
                        </template>
                    </div>

                    {{-- PASO 1: CUENTAS --}}
                    <div class="wizard-grid" x-show="transferStep === 1">
                        <div class="wizard-context" x-show="needsPropietarioStep && transfer.propietarioNombre" x-cloak>
                            Propietario: <strong x-text="transfer.propietarioNombre"></strong>
                        </div>
                        <template x-for="cta in filteredCuentas" :key="cta.id">
                            <button type="button" class="wizard-card" x-on:click="selectCuenta(cta)">
                                <span class="wizard-card__icon" x-text="(cta.nombre || '?').charAt(0).toUpperCase()"></span>
                                <span class="wizard-card__text">
                                    <span class="wizard-card__name" x-text="cta.nombre"></span>
                                    <span class="wizard-card__meta">
                                        <span x-text="(cta.tipo ? (cta.tipo.charAt(0).toUpperCase() + cta.tipo.slice(1) + ' · ') : '')"></span>
                                        <span x-text="cta.bolsillos.length + (cta.bolsillos.length === 1 ? ' bolsillo' : ' bolsillos')"></span>
                                    </span>
                                </span>
                                <svg class="wizard-card__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                            </button>
                        </template>
                    </div>

                    {{-- PASO 2: BOLSILLOS --}}
                    <div class="wizard-grid" x-show="transferStep === 2" x-cloak>
                        <div class="wizard-context" x-show="transfer.cuentaNombre">
                            Cuenta: <strong x-text="transfer.cuentaNombre"></strong>
                        </div>
                        <template x-for="b in (transfer.cuenta ? transfer.cuenta.bolsillos : [])" :key="b.id">
                            <button type="button" class="wizard-card" x-on:click="selectBolsillo(b)">
                                <span class="wizard-card__icon" x-text="(b.nombre || '?').charAt(0).toUpperCase()"></span>
                                <span class="wizard-card__text">
                                    <span class="wizard-card__name" x-text="b.nombre"></span>
                                    <span class="wizard-card__meta" x-text="esBolsilloEspejo(b) ? 'Mismo propósito que este bolsillo' : (b.es_general ? 'Bolsillo general' : 'Bolsillo')"></span>
                                </span>
                                <svg class="wizard-card__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                            </button>
                        </template>

                        {{-- Si la otra cuenta no tiene un bolsillo con este propósito, se ofrece crearlo --}}
                        <template x-if="puedeCrearEspejo">
                            <button type="button" class="wizard-card wizard-card--nuevo" x-on:click="selectBolsilloEspejo()">
                                <span class="wizard-card__icon wizard-card__icon--nuevo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" style="width:18px;height:18px;"><path d="M12 5v14M5 12h14"></path></svg>
                                </span>
                                <span class="wizard-card__text">
                                    <span class="wizard-card__name">Crear el bolsillo "<span x-text="config.subcuentaNombre"></span>" aquí</span>
                                    <span class="wizard-card__meta">El dinero seguirá marcado para este propósito</span>
                                </span>
                                <svg class="wizard-card__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                            </button>
                        </template>
                    </div>

                    {{-- PASO 3: MONTO --}}
                    <div x-show="transferStep === 3" x-cloak>
                        <div class="wizard-summary">
                            <div class="wizard-summary__row">
                                <span>Cuenta</span>
                                <strong x-text="transfer.cuentaNombre"></strong>
                            </div>
                            <div class="wizard-summary__row">
                                <span>Bolsillo destino</span>
                                <strong x-text="transfer.destinoNombre"></strong>
                            </div>
                            <div class="wizard-summary__row wizard-summary__row--max">
                                <span>Máximo disponible</span>
                                <strong x-text="fmt(config.saldo)"></strong>
                            </div>
                        </div>

                        <div class="action-modal__form">
                            <label class="action-modal__field">
                                <span>Monto a transferir ({{ $cuenta->moneda }})</span>
                                <input type="number" step="0.01" min="0.01" x-model="transfer.monto" placeholder="0.00" x-ref="transferMonto">
                            </label>

                            {{-- Botón rápido: usar el máximo --}}
                            <button type="button" class="wizard-max-btn" x-on:click="transfer.monto = config.saldo">
                                Usar máximo (<span x-text="fmt(config.saldo)"></span>)
                            </button>

                            <label class="action-modal__field">
                                <span>Descripción (opcional)</span>
                                <input type="text" x-model="transfer.descripcion" placeholder="Motivo de la transferencia" maxlength="180">
                            </label>

                            <label class="pend-check">
                                <input type="checkbox" x-model="transfer.pendiente">
                                <span>Dejar como <strong>pendiente</strong> (no mueve el saldo hasta confirmar)</span>
                            </label>

                            {{-- Advertencia: el monto deja el bolsillo en negativo --}}
                            <div class="wizard-warning" x-show="Number(transfer.monto) > Number(config.saldo)" x-cloak>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                </svg>
                                <div>
                                    Esto dejará tu bolsillo en
                                    <strong x-text="fmt(Number(config.saldo) - Number(transfer.monto || 0))"></strong>.
                                    ¿Seguro que quieres continuar con saldo negativo?
                                </div>
                            </div>

                            <button type="button"
                                    class="action-modal__submit"
                                    :class="Number(transfer.monto) > Number(config.saldo) ? 'action-modal__submit--warning' : ''"
                                    x-on:click="submitTransferencia()"
                                    :disabled="loading || (!transfer.destinoId && !transfer.crearEspejo) || !transfer.monto || Number(transfer.monto) <= 0">
                                <span x-show="!loading && Number(transfer.monto) <= Number(config.saldo)">Transferir</span>
                                <span x-show="!loading && Number(transfer.monto) > Number(config.saldo)" x-cloak>Sí, transferir de todas formas</span>
                                <span x-show="loading" x-cloak>Procesando...</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    .subcuenta-page { display:grid; gap:24px; max-width:1080px; margin:0 auto; }

    /* ============ HEADER ============ */
    .sub-header { display:grid; gap:14px; }
    .sub-header__back {
        display:inline-flex; align-items:center; gap:6px;
        font-size:.86rem; font-weight:500; color:var(--text-secondary);
        text-decoration:none; width:fit-content;
        padding:6px 10px 6px 6px; border-radius:8px;
        transition:background .2s ease, color .2s ease;
    }
    .sub-header__back:hover { background:#eef0f4; color:var(--text-primary); }
    .sub-header__back svg { width:16px; height:16px; }

    .sub-header__main {
        display:flex; align-items:center; gap:18px; flex-wrap:wrap;
        padding:22px 26px;
        background:linear-gradient(180deg, #fff 0%, #fafbff 100%);
        border:1px solid var(--border); border-radius:18px;
        box-shadow:0 8px 24px rgba(15,23,42,.04);
    }
    .sub-header__text { flex:1; min-width:200px; }
    .sub-header__title { font-size:1.8rem; font-weight:700; letter-spacing:-.02em; color:var(--text-primary); line-height:1.1; }
    .sub-header__meta { font-size:.92rem; color:var(--text-secondary); margin-top:4px; }
    .sub-header__balance { text-align:right; display:grid; gap:2px; }
    .sub-header__balance-label { font-size:.74rem; font-weight:500; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; }
    .sub-header__balance-value { font-size:1.8rem; font-weight:700; color:var(--text-primary); letter-spacing:-.02em; }

    /* ============ ACTION CARDS ============ */
    .action-cards {
        display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px;
    }
    .action-card {
        display:flex; align-items:center; gap:14px;
        padding:18px 20px; background:#fff;
        border:1px solid var(--border); border-radius:14px;
        cursor:pointer; text-align:left; color:var(--text-primary);
        box-shadow:0 4px 14px rgba(15,23,42,.04);
        opacity:0; transform:translateY(12px);
        animation: action-card-in .5s cubic-bezier(.22,1,.36,1) forwards;
        animation-delay: var(--card-delay, 0ms);
        transition:transform .2s cubic-bezier(.22,1,.36,1), box-shadow .2s ease, border-color .2s ease;
    }
    @keyframes action-card-in {
        to { opacity:1; transform:translateY(0); }
    }
    .action-card:hover {
        transform:translateY(-2px);
        box-shadow:0 12px 26px rgba(15,23,42,.08);
    }
    .action-card__icon {
        width:48px; height:48px; border-radius:14px;
        display:grid; place-items:center; flex-shrink:0;
        transition:transform .25s ease;
    }
    .action-card__icon svg { width:24px; height:24px; }
    .action-card:hover .action-card__icon { transform:scale(1.08); }
    .action-card__body { flex:1; display:grid; gap:3px; }
    .action-card__title { font-size:1rem; font-weight:700; color:var(--text-primary); }
    .action-card__copy { font-size:.82rem; color:var(--text-secondary); font-weight:400; }
    .action-card__arrow {
        width:24px; height:24px; color:var(--text-secondary); opacity:.5;
        transition:opacity .2s ease, transform .25s ease;
    }
    .action-card__arrow svg { width:16px; height:16px; }
    .action-card:hover .action-card__arrow { opacity:1; transform:translateX(3px); }

    .action-card--ingreso .action-card__icon { background:rgba(16,185,129,.12); color:#10b981; }
    .action-card--ingreso:hover { border-color:rgba(16,185,129,.4); }
    .action-card--gasto .action-card__icon { background:rgba(239,68,68,.10); color:#ef4444; }
    .action-card--gasto:hover { border-color:rgba(239,68,68,.4); }
    .action-card--transferir .action-card__icon { background:var(--primary-soft); color:var(--primary); }
    .action-card--transferir:hover { border-color:rgba(68,97,242,.4); }
    .action-card--deuda .action-card__icon { background:rgba(245,158,11,.12); color:#f59e0b; }
    .action-card--deuda:hover { border-color:rgba(245,158,11,.4); }
    .action-card--blocked { opacity:.55; }
    .action-card--blocked:hover { transform:none; box-shadow:0 4px 14px rgba(15,23,42,.04); border-color:var(--border); }

    /* ============ HISTORIA ============ */
    .sub-history { background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px 24px; box-shadow:0 4px 14px rgba(15,23,42,.04); }
    .sub-history__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .sub-history__title { font-size:1.05rem; font-weight:700; color:var(--text-primary); letter-spacing:-.01em; }
    .sub-history__count { padding:3px 10px; border-radius:999px; background:var(--primary-soft); color:var(--primary); font-size:.74rem; font-weight:700; }

    .sub-history__empty { padding:32px 16px; text-align:center; color:var(--text-secondary); font-size:.9rem; }
    .sub-history__empty-hint { font-size:.82rem; margin-top:6px; opacity:.75; }

    .sub-history__list { list-style:none; display:grid; gap:6px; padding:0; margin:0; }
    .sub-history__item {
        position:relative;
        display:flex; align-items:center; gap:14px;
        padding:12px 14px;
        border:1px solid transparent;
        border-radius:12px;
        transition:background .18s ease, border-color .18s ease, transform .18s ease;
    }
    .sub-history__item:hover {
        background:#fff;
        border-color:var(--border);
        box-shadow:0 6px 14px rgba(15,23,42,.04);
    }
    .sub-history__item:hover .sub-history__item-action {
        opacity:1;
        transform:translateX(0);
        pointer-events:auto;
    }
    .sub-history__item-icon {
        width:36px; height:36px; border-radius:10px;
        display:grid; place-items:center; flex-shrink:0;
    }
    .sub-history__item-icon svg { width:16px; height:16px; }
    .sub-history__item-icon.is-positive { background:rgba(16,185,129,.12); color:#10b981; }
    .sub-history__item-icon.is-negative { background:rgba(239,68,68,.10); color:#ef4444; }
    .sub-history__item-body { flex:1; min-width:0; }
    .sub-history__item-title {
        font-size:.92rem; font-weight:600; color:var(--text-primary);
        display:flex; align-items:center; flex-wrap:wrap; gap:8px;
    }
    .sub-history__item-meta { font-size:.78rem; color:var(--text-secondary); margin-top:2px; }
    .sub-history__item-note {
        font-size:.76rem; color:var(--text-secondary);
        margin-top:6px; padding:6px 10px;
        background:#fff7ed;
        border-left:2px solid #f97316;
        border-radius:6px;
        line-height:1.4;
    }
    .sub-history__item-amount { font-size:.95rem; font-weight:700; white-space:nowrap; }
    .sub-history__item-amount.is-positive { color:#10b981; }
    .sub-history__item-amount.is-negative { color:#ef4444; }

    .sub-history__chip {
        display:inline-flex; align-items:center;
        padding:2px 8px; border-radius:999px;
        background:#fef3c7; color:#92400e;
        font-size:.66rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.04em;
    }

    .sub-history__item.is-anulado {
        opacity:.72;
        background:#fafafa;
    }
    .sub-history__item.is-anulado .sub-history__item-amount {
        text-decoration:line-through;
        color:var(--text-muted) !important;
    }
    .sub-history__item.is-anulado .sub-history__item-icon {
        filter:grayscale(.6);
    }

    .sub-history__item-action {
        width:30px; height:30px;
        border:none; background:#f6f7fb;
        border-radius:8px;
        display:grid; place-items:center;
        color:var(--text-secondary);
        cursor:pointer;
        opacity:0;
        transform:translateX(4px);
        pointer-events:none;
        transition:opacity .18s ease, transform .18s ease, background .18s ease, color .18s ease;
    }
    .sub-history__item-action svg { width:14px; height:14px; }
    .sub-history__item-action:hover {
        background:var(--danger-soft);
        color:var(--danger);
    }
    @media (hover: none) {
        .sub-history__item-action { opacity:1; transform:none; pointer-events:auto; }
    }

    /* Los estilos de .action-modal*, .wizard-*, .fijo-ask* viven en public/css/finanzas-modals.css */

    /* Responsive (solo estilos propios de esta pantalla) */
    @media (max-width: 768px) {
        .action-cards { grid-template-columns:1fr; }
        .sub-header__main { padding:18px 20px; }
        .sub-header__balance { text-align:left; flex-basis:100%; padding-top:8px; border-top:1px solid var(--border); }
        .sub-header__title { font-size:1.4rem; }
        .sub-header__balance-value { font-size:1.4rem; }
    }
</style>

<script>
    function subcuentaActions(config) {
        return {
            modal: null,
            tab: 'fijos',
            loading: false,
            custom: { nombre: '', monto: '' },
            cuentas: config.cuentas || [],
            propietarios: config.propietarios || [],
            transferStep: 1,
            transfer: { cuenta: null, cuentaId: null, cuentaNombre: '', destinoId: '', destinoNombre: '', monto: '', descripcion: '', propietarioId: null, propietarioNombre: '', pendiente: false, crearEspejo: false },
            anular: { movimientoId: null, descripcion: '', motivo: '' },
            askFijo: null,
            askMonto: '',
            deudas: config.deudas || [],
            pago: { deudaId: '', monto: '', pendiente: false },
            config,

            get needsPropietarioStep() {
                return (this.propietarios || []).length > 1;
            },

            get totalSteps() {
                return this.needsPropietarioStep ? 4 : 3;
            },

            get filteredCuentas() {
                if (!this.needsPropietarioStep) return this.cuentas;
                if (this.transfer.propietarioId === null || this.transfer.propietarioId === undefined) return this.cuentas;
                return this.cuentas.filter(c => (c.propietario_id ?? null) === this.transfer.propietarioId);
            },

            countCuentasPropietario(propId) {
                return this.cuentas.filter(c => (c.propietario_id ?? null) === (propId ?? null)).length;
            },

            fmt(n) {
                return this.config.moneda + ' ' + Number(n || 0).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            get deudaSeleccionada() {
                if (!this.pago.deudaId) return null;
                return (this.deudas || []).find(d => String(d.id) === String(this.pago.deudaId)) || null;
            },

            get maxPagoDeuda() {
                if (!this.deudaSeleccionada) return 0;
                return Math.max(0, Math.min(Number(this.deudaSeleccionada.saldo_pendiente), Number(this.config.saldo)));
            },

            seleccionarDeuda(id) {
                this.pago.deudaId = id;
                this.pago.monto = '';
            },

            openModal(name) {
                if (name === 'transferir' && Number(this.config.saldo) <= 0) {
                    window.notify('warning', 'Saldo insuficiente', 'Este bolsillo no tiene saldo positivo, no puedes transferir dinero.');
                    return;
                }

                this.modal = name;
                this.tab = 'fijos';
                this.custom = { nombre: '', monto: '' };
                this.askFijo = null;
                this.pago = { deudaId: '', monto: '' };
                if (name === 'transferir') {
                    this.transferStep = this.needsPropietarioStep ? 0 : 1;
                    this.transfer = { cuenta: null, cuentaId: null, cuentaNombre: '', destinoId: '', destinoNombre: '', monto: '', descripcion: '', propietarioId: null, propietarioNombre: '', pendiente: false, crearEspejo: false };
                }
                document.body.style.overflow = 'hidden';
            },

            pickFijo(tipo, fijo) {
                if (fijo && fijo.preguntar) {
                    this.askFijo = {
                        tipo: tipo,
                        id: fijo.id,
                        nombre: fijo.nombre,
                        monto: Number(fijo.monto),
                        saldo: Number(fijo.saldo),
                    };
                    this.askMonto = '';
                    this.$nextTick(() => { if (this.$refs.askInput) this.$refs.askInput.focus(); });
                    return;
                }

                this.submitAction(tipo, fijo.nombre, Number(fijo.monto), fijo.id);
            },

            cancelarAskFijo() {
                this.askFijo = null;
                this.askMonto = '';
            },

            submitAskFijo() {
                if (!this.askFijo) return;
                var monto = Number(this.askMonto);
                if (!monto || monto <= 0) {
                    window.notify('warning', 'Monto requerido', 'Ingresa un monto válido.');
                    return;
                }
                if (monto > Number(this.askFijo.saldo) + 0.001) {
                    window.notify('warning', 'Monto alto', 'No puedes usar más que el saldo restante (' + this.fmt(this.askFijo.saldo) + ').');
                    return;
                }
                this.submitAction(this.askFijo.tipo, this.askFijo.nombre, monto, this.askFijo.id);
            },

            selectPropietario(prop) {
                this.transfer.propietarioId = prop.id ?? null;
                this.transfer.propietarioNombre = prop.nombre;
                this.transferStep = 1;
            },

            selectCuenta(cta) {
                this.transfer.cuenta = cta;
                this.transfer.cuentaId = cta.id;
                this.transfer.cuentaNombre = cta.nombre;
                this.transfer.destinoId = '';
                this.transfer.destinoNombre = '';
                this.transfer.crearEspejo = false;
                this.transferStep = 2;
            },

            selectBolsillo(b) {
                this.transfer.destinoId = b.id;
                this.transfer.destinoNombre = b.nombre;
                this.transfer.crearEspejo = false;
                this.transferStep = 3;
                this.$nextTick(() => { if (this.$refs.transferMonto) this.$refs.transferMonto.focus(); });
            },

            // ¿Este bolsillo destino tiene el mismo nombre (propósito) que el origen?
            esBolsilloEspejo(b) {
                return (b.nombre || '').trim().toLowerCase() === (this.config.subcuentaNombre || '').trim().toLowerCase();
            },

            // Se ofrece crear el bolsillo espejo solo en otra cuenta que aún no lo tenga.
            get puedeCrearEspejo() {
                var cta = this.transfer.cuenta;
                if (!cta || Number(cta.id) === Number(this.config.cuentaId)) return false;
                return !(cta.bolsillos || []).some((b) => this.esBolsilloEspejo(b));
            },

            selectBolsilloEspejo() {
                this.transfer.destinoId = '';
                this.transfer.destinoNombre = this.config.subcuentaNombre + ' (se creará)';
                this.transfer.crearEspejo = true;
                this.transferStep = 3;
                this.$nextTick(() => { if (this.$refs.transferMonto) this.$refs.transferMonto.focus(); });
            },

            transferBack() {
                var minStep = this.needsPropietarioStep ? 0 : 1;
                if (this.transferStep > minStep) {
                    this.transferStep -= 1;
                }
            },

            openAnular(movimientoId, descripcion) {
                this.anular = { movimientoId: movimientoId, descripcion: descripcion || '', motivo: '' };
                this.modal = 'anular';
                document.body.style.overflow = 'hidden';
            },

            closeModal() {
                this.modal = null;
                document.body.style.overflow = '';
            },

            async submitAction(type, nombre, monto, fijoId) {
                if (this.loading) return;
                if (!nombre || !monto || Number(monto) <= 0) {
                    window.notify('warning', 'Datos incompletos', 'Ingresa nombre y monto válido.');
                    return;
                }

                this.loading = true;
                window.uiBlocker.show('Guardando…');
                const url = type === 'ingreso' ? this.config.ingresoUrl : this.config.gastoUrl;
                const body = { nombre: nombre, monto: Number(monto) };
                if (fijoId) {
                    if (type === 'ingreso') {
                        body.finanzas_ingreso_fijo_id = fijoId;
                    } else {
                        body.finanzas_gasto_fijo_id = fijoId;
                    }
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });

                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        throw new Error(err.message || 'No se pudo guardar.');
                    }

                    const data = await response.json();
                    var titulo = type === 'ingreso' ? 'Ingreso' : 'Gasto';
                    var mensaje = data.message || 'Registrado correctamente.';
                    if (data.reiniciado) {
                        mensaje += ' El saldo se reinició a ' + this.fmt(data.saldo_restante) + '.';
                    } else if (data.saldo_restante !== undefined && data.saldo_restante !== null) {
                        mensaje += ' Saldo restante: ' + this.fmt(data.saldo_restante) + '.';
                    }
                    window.notify('success', titulo, mensaje);
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) {
                    window.notify('error', 'Error', e.message || 'No se pudo guardar.');
                } finally {
                    this.loading = false;
                    window.uiBlocker.hide();
                }
            },

            async submitPagoDeuda() {
                if (this.loading) return;
                var monto = Number(this.pago.monto);
                if (!this.pago.deudaId || !monto || monto <= 0) {
                    window.notify('warning', 'Datos incompletos', 'Selecciona una deuda y un monto válido.');
                    return;
                }
                var deuda = this.deudaSeleccionada;
                if (deuda && monto > Number(deuda.saldo_pendiente)) {
                    window.notify('warning', 'Monto alto', 'El abono supera el saldo pendiente de la deuda.');
                    return;
                }
                if (monto > this.maxPagoDeuda) {
                    window.notify('warning', 'Saldo insuficiente', 'El bolsillo solo permite abonar hasta ' + this.fmt(this.maxPagoDeuda) + '.');
                    return;
                }

                this.loading = true;
                window.uiBlocker.show('Guardando…');

                try {
                    const response = await fetch(this.config.pagarDeudaUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ finanzas_deuda_id: Number(this.pago.deudaId), monto: monto, pendiente: this.pago.pendiente }),
                    });

                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        throw new Error(err.message || 'No se pudo registrar el pago.');
                    }

                    const data = await response.json();
                    window.notify('success', 'Pago de deuda', data.message || 'Abono registrado.');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) {
                    window.notify('error', 'Error', e.message || 'No se pudo registrar el pago.');
                } finally {
                    this.loading = false;
                    window.uiBlocker.hide();
                }
            },

            async submitTransferencia() {
                if (this.loading) return;
                if ((!this.transfer.destinoId && !this.transfer.crearEspejo) || !this.transfer.monto || Number(this.transfer.monto) <= 0) {
                    window.notify('warning', 'Datos incompletos', 'Selecciona destino y monto válido.');
                    return;
                }

                this.loading = true;
                window.uiBlocker.show('Guardando…');

                var body = {
                    monto: Number(this.transfer.monto),
                    descripcion: this.transfer.descripcion || null,
                    pendiente: this.transfer.pendiente,
                };
                if (this.transfer.crearEspejo) {
                    body.crear_bolsillo_espejo = true;
                    body.finanzas_cuenta_destino_id = Number(this.transfer.cuentaId);
                } else {
                    body.finanzas_subcuenta_destino_id = Number(this.transfer.destinoId);
                }

                try {
                    const response = await fetch(this.config.transferirUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(body),
                    });

                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        throw new Error(err.message || 'No se pudo transferir.');
                    }

                    const data = await response.json();
                    window.notify('success', 'Transferencia', data.message || 'Transferencia realizada.');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) {
                    window.notify('error', 'Error', e.message || 'No se pudo transferir.');
                } finally {
                    this.loading = false;
                    window.uiBlocker.hide();
                }
            },

            async submitAnular() {
                if (this.loading) return;
                var motivo = (this.anular.motivo || '').trim();
                if (!this.anular.movimientoId || motivo.length < 3) {
                    window.notify('warning', 'Motivo requerido', 'Escribe al menos 3 caracteres para anular.');
                    return;
                }

                this.loading = true;
                window.uiBlocker.show('Guardando…');
                var url = this.config.anularUrlTemplate.replace('MOV_ID', String(this.anular.movimientoId));

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ motivo: motivo }),
                    });

                    if (!response.ok) {
                        const err = await response.json().catch(() => ({}));
                        throw new Error(err.message || 'No se pudo anular.');
                    }

                    const data = await response.json();
                    window.notify('success', 'Anulado', data.message || 'Movimiento anulado.');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) {
                    window.notify('error', 'Error', e.message || 'No se pudo anular el movimiento.');
                } finally {
                    this.loading = false;
                    window.uiBlocker.hide();
                }
            },
        };
    }
</script>
@endsection
