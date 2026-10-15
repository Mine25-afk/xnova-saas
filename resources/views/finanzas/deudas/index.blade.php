@extends('layouts.main')

@section('title', 'Deudas — xNova')
@section('page-title', 'Finanzas / Deudas')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
    <link rel="stylesheet" href="{{ asset('css/finanzas-modals.css') }}">
@endsection

@section('content')
<div
    x-data="deudasPagoWizard({
        moneda: @js($moneda ?? 'LPS'),
        cuentas: @js($cuentasDestino ?? []),
        propietarios: @js($propietarios ?? []),
        pagarUrlTemplate: @js(route('finanzas.deudas.pagar', ['deuda' => 'DEUDA_ID'])),
    })"
    x-on:deuda-pagar.window="openPago($event.detail)"
>
<div class="list-page">
    <x-card class="list-card list-card--cards-only">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Deudas</div>
                    <div class="list-card-subtitle">Registra lo que debes y abónalo desde tus cuentas para planificar mejor.</div>
                </div>

                <a href="{{ route('finanzas.deudas.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nueva deuda
                </a>
            </div>
        </x-slot>

        <form id="deudasFilters" class="list-toolbar">
            <div class="list-search">
                <x-input id="deudas-search" name="search" icon="magnifying-glass" placeholder="Buscar deuda o acreedor" />
            </div>

            <div class="list-filter">
                <x-native-select id="deudas-estado" name="estado">
                    <option value="">Todas</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="pagada">Pagadas</option>
                </x-native-select>
            </div>

            <div class="list-per-page">
                <x-native-select id="deudas-per-page" name="per_page">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </x-native-select>
            </div>
        </form>

        <div class="totals-wrap" id="deudasTotalWrap" style="display:none;">
            <div class="totals-card totals-card--negative">
                <div class="totals-card__head">
                    <span class="totals-card__dot totals-card__dot--negative"></span>
                    <span class="totals-card__title">Total pendiente por pagar</span>
                </div>
                <div class="totals-card__list">
                    <div class="totals-card__row is-negative">
                        <strong class="totals-card__amount totals-card__amount--lg" id="deudasTotal">—</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards list-cards--premium" id="deudasCards">
                <div class="list-loading">Cargando deudas...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="deudasMeta">Cargando...</div>
            <div class="list-pagination" id="deudasPagination"></div>
        </div>
    </x-card>
</div>

    {{-- MODAL: PAGAR DEUDA (asistente propietario → cuenta → bolsillo → monto) --}}
    <div class="action-modal" x-show="modalOpen" x-cloak x-on:keydown.escape.window="closeModal()">
        <div class="action-modal__backdrop" x-on:click="closeModal()" x-transition.opacity></div>
        <div class="action-modal__dialog"
             x-transition:enter="action-modal__enter"
             x-transition:enter-start="action-modal__enter-start"
             x-transition:enter-end="action-modal__enter-end"
        >
            <header class="action-modal__head">
                <div class="wizard-head__title-row">
                    <button type="button" class="wizard-back" x-show="step > minStep" x-on:click="back()" title="Atrás">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>
                    <div>
                        <h2 class="action-modal__title">Pagar deuda</h2>
                        <p class="action-modal__copy" x-text="deuda ? deuda.nombre + ' · Pendiente ' + fmt(deuda.saldo) : ''"></p>
                    </div>
                </div>
                <button type="button" class="action-modal__close" x-on:click="closeModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </header>

            {{-- Indicador de pasos --}}
            <div class="wizard-steps">
                <template x-if="needsPropietarioStep">
                    <span class="wizard-steps__dot" :class="step >= 0 ? 'is-active' : ''"></span>
                </template>
                <template x-if="needsPropietarioStep">
                    <span class="wizard-steps__bar" :class="step >= 1 ? 'is-active' : ''"></span>
                </template>
                <span class="wizard-steps__dot" :class="step >= 1 ? 'is-active' : ''"></span>
                <span class="wizard-steps__bar" :class="step >= 2 ? 'is-active' : ''"></span>
                <span class="wizard-steps__dot" :class="step >= 2 ? 'is-active' : ''"></span>
                <span class="wizard-steps__bar" :class="step >= 3 ? 'is-active' : ''"></span>
                <span class="wizard-steps__dot" :class="step >= 3 ? 'is-active' : ''"></span>
            </div>

            <div class="action-modal__body">
                <template x-if="!cuentas.length">
                    <div class="action-modal__empty">
                        No hay bolsillos disponibles para pagar.<br>
                        Crea una cuenta con un bolsillo primero.
                    </div>
                </template>

                {{-- PASO 0: PROPIETARIO --}}
                <div class="wizard-grid" x-show="cuentas.length && step === 0" x-cloak>
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

                {{-- PASO 1: CUENTA --}}
                <div class="wizard-grid" x-show="cuentas.length && step === 1">
                    <div class="wizard-context" x-show="needsPropietarioStep && sel.propietarioNombre" x-cloak>
                        Propietario: <strong x-text="sel.propietarioNombre"></strong>
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

                {{-- PASO 2: BOLSILLO --}}
                <div class="wizard-grid" x-show="cuentas.length && step === 2" x-cloak>
                    <div class="wizard-context" x-show="sel.cuentaNombre">
                        Cuenta: <strong x-text="sel.cuentaNombre"></strong>
                    </div>
                    <template x-for="b in (sel.cuenta ? sel.cuenta.bolsillos : [])" :key="b.id">
                        <button type="button" class="wizard-card" x-on:click="selectBolsillo(b)">
                            <span class="wizard-card__icon" x-text="(b.nombre || '?').charAt(0).toUpperCase()"></span>
                            <span class="wizard-card__text">
                                <span class="wizard-card__name" x-text="b.nombre"></span>
                                <span class="wizard-card__meta">Disponible: <span x-text="fmt(b.saldo)"></span></span>
                            </span>
                            <svg class="wizard-card__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                        </button>
                    </template>
                </div>

                {{-- PASO 3: MONTO --}}
                <div x-show="cuentas.length && step === 3" x-cloak>
                    <div class="wizard-summary">
                        <div class="wizard-summary__row">
                            <span>Deuda</span>
                            <strong x-text="deuda ? deuda.nombre : ''"></strong>
                        </div>
                        <div class="wizard-summary__row">
                            <span>Saldo pendiente</span>
                            <strong x-text="deuda ? fmt(deuda.saldo) : ''"></strong>
                        </div>
                        <div class="wizard-summary__row">
                            <span>Pagar desde</span>
                            <strong x-text="sel.bolsilloNombre"></strong>
                        </div>
                        <div class="wizard-summary__row wizard-summary__row--max">
                            <span>Disponible en bolsillo</span>
                            <strong x-text="fmt(sel.bolsilloSaldo)"></strong>
                        </div>
                    </div>

                    <div class="wizard-warning" x-show="deuda && Number(sel.bolsilloSaldo) < Number(deuda.saldo)" x-cloak>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path></svg>
                        <div>El saldo de este bolsillo no cubre toda la deuda. Puedes abonar como máximo <strong x-text="fmt(maxAbono)"></strong>.</div>
                    </div>

                    <div class="action-modal__form">
                        <label class="action-modal__field">
                            <span>Monto a abonar</span>
                            <input type="number" step="0.01" min="0.01" x-model="monto" :max="maxAbono" placeholder="0.00" x-ref="montoInput">
                        </label>
                        <button type="button" class="wizard-max-btn" x-on:click="monto = maxAbono">
                            Abonar máximo (<span x-text="fmt(maxAbono)"></span>)
                        </button>
                        <label class="pend-check">
                            <input type="checkbox" x-model="pendiente">
                            <span>Dejar como <strong>pendiente</strong> (no descuenta hasta confirmar)</span>
                        </label>
                        <button type="button" class="action-modal__submit"
                                x-on:click="submitPago()"
                                :disabled="loading || !monto || Number(monto) <= 0 || Number(monto) > maxAbono">
                            <span x-show="!loading" x-text="pendiente ? 'Dejar pendiente' : 'Registrar pago'"></span>
                            <span x-show="loading">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function deudasPagoWizard(config) {
        return {
            modalOpen: false,
            step: 1,
            loading: false,
            deuda: null,
            monto: '',
            pendiente: false,
            cuentas: config.cuentas || [],
            propietarios: config.propietarios || [],
            sel: { propietarioId: null, propietarioNombre: '', cuenta: null, cuentaId: null, cuentaNombre: '', bolsilloId: '', bolsilloNombre: '', bolsilloSaldo: 0 },

            get needsPropietarioStep() {
                return (this.propietarios || []).length > 1;
            },

            get minStep() {
                return this.needsPropietarioStep ? 0 : 1;
            },

            get filteredCuentas() {
                if (!this.needsPropietarioStep) return this.cuentas;
                if (this.sel.propietarioId === null || this.sel.propietarioId === undefined) return this.cuentas;
                return this.cuentas.filter(c => (c.propietario_id ?? null) === this.sel.propietarioId);
            },

            get maxAbono() {
                if (!this.deuda) return 0;
                return Math.max(0, Math.min(Number(this.deuda.saldo), Number(this.sel.bolsilloSaldo)));
            },

            fmt(n) {
                return config.moneda + ' ' + Number(n || 0).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            countCuentasPropietario(propId) {
                return this.cuentas.filter(c => (c.propietario_id ?? null) === (propId ?? null)).length;
            },

            openPago(detail) {
                if (!detail) return;
                this.deuda = {
                    id: detail.id,
                    nombre: detail.nombre,
                    saldo: Number(detail.saldo),
                };
                this.monto = '';
                this.sel = { propietarioId: null, propietarioNombre: '', cuenta: null, cuentaId: null, cuentaNombre: '', bolsilloId: '', bolsilloNombre: '', bolsilloSaldo: 0 };
                this.step = this.minStep;
                this.modalOpen = true;
                document.body.style.overflow = 'hidden';
            },

            closeModal() {
                this.modalOpen = false;
                document.body.style.overflow = '';
            },

            selectPropietario(prop) {
                this.sel.propietarioId = prop.id ?? null;
                this.sel.propietarioNombre = prop.nombre;
                this.step = 1;
            },

            selectCuenta(cta) {
                this.sel.cuenta = cta;
                this.sel.cuentaId = cta.id;
                this.sel.cuentaNombre = cta.nombre;
                this.sel.bolsilloId = '';
                this.sel.bolsilloNombre = '';
                this.step = 2;
            },

            selectBolsillo(b) {
                this.sel.bolsilloId = b.id;
                this.sel.bolsilloNombre = b.nombre;
                this.sel.bolsilloSaldo = Number(b.saldo || 0);
                this.step = 3;
                this.$nextTick(() => { if (this.$refs.montoInput) this.$refs.montoInput.focus(); });
            },

            back() {
                if (this.step > this.minStep) {
                    this.step -= 1;
                }
            },

            async submitPago() {
                if (this.loading) return;
                var monto = Number(this.monto);
                if (!this.deuda || !this.sel.bolsilloId || !monto || monto <= 0) {
                    window.notify('warning', 'Datos incompletos', 'Selecciona un bolsillo y un monto válido.');
                    return;
                }
                if (monto > this.maxAbono) {
                    window.notify('warning', 'Saldo insuficiente', 'Solo puedes abonar hasta ' + this.fmt(this.maxAbono) + '.');
                    return;
                }

                this.loading = true;
                window.uiBlocker.show('Guardando…');
                var url = config.pagarUrlTemplate.replace('DEUDA_ID', String(this.deuda.id));

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ finanzas_subcuenta_id: Number(this.sel.bolsilloId), monto: monto, pendiente: this.pendiente }),
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
        };
    }

    $(function () {
        var endpoint = @json(route('finanzas.deudas.data'));
        var $searchInput = $('#deudas-search');
        var $perPageInput = $('#deudas-per-page');
        var $estadoInput = $('#deudas-estado');
        var $cards = $('#deudasCards');
        var $meta = $('#deudasMeta');
        var $pagination = $('#deudasPagination');
        var $totalWrap = $('#deudasTotalWrap');
        var $total = $('#deudasTotal');
        var currentPage = 1;
        var listState = window.listUtils.createStateHtml({ entity: 'deudas', colSpan: 4 });

        function loadDeudas(page) {
            page = page || 1;
            currentPage = page;
            $cards.html(listState.loadingCards);

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: {
                    page: page,
                    search: $.trim($searchInput.val()),
                    per_page: $perPageInput.val(),
                    estado: $estadoInput.val(),
                },
            })
                .done(function (response) {
                    var meta = response.meta || {};
                    $cards.html($.trim(response.cards || '') !== '' ? response.cards : listState.emptyCards);
                    $pagination.html(response.pagination || '');

                    if (response.total_label) {
                        $total.text(response.total_label);
                        $totalWrap.show();
                    } else {
                        $totalWrap.hide();
                    }

                    if (!meta.total) {
                        $meta.text('No hay deudas registradas.');
                        return;
                    }

                    $meta.text('Mostrando ' + meta.from + '-' + meta.to + ' de ' + meta.total + ' deudas');
                }).fail(function () {
                    $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                    $pagination.html('');
                    $meta.text('Error cargando deudas.');
                    window.notify('error', 'Deudas', 'No se pudo obtener la lista de deudas.');
                });
        }

        // Botón "Pagar" en tarjetas (inyectadas por AJAX) → dispara el asistente Alpine.
        $cards.on('click', '.deuda-pay-btn', function () {
            var $btn = $(this);
            window.dispatchEvent(new CustomEvent('deuda-pagar', { detail: {
                id: $btn.data('id'),
                nombre: $btn.data('nombre'),
                saldo: $btn.data('saldo'),
                vencimiento: $btn.data('vencimiento'),
                vencida: String($btn.data('vencida')) === '1',
            } }));
        });

        window.listUtils.bindFilterControls({ searchInput: $searchInput, perPageInput: $perPageInput, pagination: $pagination, getCurrentPage: function () { return currentPage; }, loadPage: loadDeudas });
        $estadoInput.on('change', function () { loadDeudas(1); });
        window.listUtils.bindPersistentFilters({
            storageKey: 'deudas',
            inputs: { search: $searchInput, per_page: $perPageInput, estado: $estadoInput },
        });
        loadDeudas();
    });
</script>
@endsection
