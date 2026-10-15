@extends('layouts.main')

@section('title', 'Cuentas por cobrar — xNova')
@section('page-title', 'Finanzas / Cuentas por cobrar')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/listado.css') }}">
    <link rel="stylesheet" href="{{ asset('css/finanzas-modals.css') }}">
@endsection

@section('content')
<div
    x-data="cobrosWizard({
        moneda: @js($moneda ?? 'LPS'),
        cuentas: @js($cuentasDestino ?? []),
        propietarios: @js($propietarios ?? []),
        cobrarUrlTemplate: @js(route('finanzas.cuentas-por-cobrar.cobrar', ['cuentaPorCobrar' => 'CPC_ID'])),
    })"
    x-on:cuenta-cobrar.window="openCobro($event.detail)"
>
<div class="list-page">
    <x-card class="list-card list-card--cards-only">
        <x-slot name="header">
            <div class="list-card-header">
                <div>
                    <div class="list-card-title">Cuentas por cobrar</div>
                    <div class="list-card-subtitle">Registra lo que te deben y cóbralo hacia tus bolsillos como ingreso.</div>
                </div>

                <a href="{{ route('finanzas.cuentas-por-cobrar.create') }}" class="btn btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Nueva cuenta por cobrar
                </a>
            </div>
        </x-slot>

        <form id="cpcFilters" class="list-toolbar">
            <div class="list-search">
                <x-input id="cpc-search" name="search" icon="magnifying-glass" placeholder="Buscar por nombre o deudor" />
            </div>

            <div class="list-filter">
                <x-native-select id="cpc-estado" name="estado">
                    <option value="">Todas</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="cobrada">Cobradas</option>
                </x-native-select>
            </div>

            <div class="list-per-page">
                <x-native-select id="cpc-per-page" name="per_page">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </x-native-select>
            </div>
        </form>

        <div class="totals-wrap" id="cpcTotalWrap" style="display:none;">
            <div class="totals-card">
                <div class="totals-card__head">
                    <span class="totals-card__dot"></span>
                    <span class="totals-card__title">Total pendiente por cobrar</span>
                </div>
                <div class="totals-card__list">
                    <div class="totals-card__row is-positive">
                        <strong class="totals-card__amount totals-card__amount--lg" id="cpcTotal">—</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="list-cards-wrap">
            <div class="list-cards list-cards--premium" id="cpcCards">
                <div class="list-loading">Cargando cuentas por cobrar...</div>
            </div>
        </div>

        <div class="list-feedback">
            <div class="list-feedback__meta" id="cpcMeta">Cargando...</div>
            <div class="list-pagination" id="cpcPagination"></div>
        </div>
    </x-card>
</div>

    {{-- MODAL: COBRAR (asistente propietario → cuenta → bolsillo → monto) --}}
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
                        <h2 class="action-modal__title">Registrar cobro</h2>
                        <p class="action-modal__copy" x-text="item ? item.nombre + ' · Pendiente ' + fmt(item.saldo) : ''"></p>
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
                        No hay bolsillos disponibles para recibir el cobro.<br>
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
                                <span class="wizard-card__meta">Saldo actual: <span x-text="fmt(b.saldo)"></span></span>
                            </span>
                            <svg class="wizard-card__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 6 6 6-6 6"></path></svg>
                        </button>
                    </template>
                </div>

                {{-- PASO 3: MONTO --}}
                <div x-show="cuentas.length && step === 3" x-cloak>
                    <div class="wizard-summary">
                        <div class="wizard-summary__row">
                            <span>Cuenta por cobrar</span>
                            <strong x-text="item ? item.nombre : ''"></strong>
                        </div>
                        <div class="wizard-summary__row">
                            <span>Saldo pendiente</span>
                            <strong x-text="item ? fmt(item.saldo) : ''"></strong>
                        </div>
                        <div class="wizard-summary__row wizard-summary__row--max">
                            <span>Depositar en</span>
                            <strong x-text="sel.bolsilloNombre"></strong>
                        </div>
                    </div>

                    <div class="action-modal__form">
                        <label class="action-modal__field">
                            <span>Monto a cobrar</span>
                            <input type="number" step="0.01" min="0.01" x-model="monto" :max="maxCobro" placeholder="0.00" x-ref="montoInput">
                        </label>
                        <button type="button" class="wizard-max-btn" x-on:click="monto = maxCobro">
                            Cobrar todo (<span x-text="fmt(maxCobro)"></span>)
                        </button>
                        <label class="pend-check">
                            <input type="checkbox" x-model="pendiente">
                            <span>Dejar como <strong>pendiente</strong> (no suma hasta confirmar)</span>
                        </label>
                        <button type="button" class="action-modal__submit"
                                x-on:click="submitCobro()"
                                :disabled="loading || !monto || Number(monto) <= 0 || Number(monto) > maxCobro">
                            <span x-show="!loading" x-text="pendiente ? 'Dejar pendiente' : 'Registrar cobro'"></span>
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
    function cobrosWizard(config) {
        return {
            modalOpen: false,
            step: 1,
            loading: false,
            pendiente: false,
            item: null,
            monto: '',
            cuentas: config.cuentas || [],
            propietarios: config.propietarios || [],
            sel: { propietarioId: null, propietarioNombre: '', cuenta: null, cuentaId: null, cuentaNombre: '', bolsilloId: '', bolsilloNombre: '' },

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

            get maxCobro() {
                if (!this.item) return 0;
                return Math.max(0, Number(this.item.saldo));
            },

            fmt(n) {
                return config.moneda + ' ' + Number(n || 0).toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            countCuentasPropietario(propId) {
                return this.cuentas.filter(c => (c.propietario_id ?? null) === (propId ?? null)).length;
            },

            openCobro(detail) {
                if (!detail) return;
                this.item = {
                    id: detail.id,
                    nombre: detail.nombre,
                    saldo: Number(detail.saldo),
                };
                this.monto = '';
                this.sel = { propietarioId: null, propietarioNombre: '', cuenta: null, cuentaId: null, cuentaNombre: '', bolsilloId: '', bolsilloNombre: '' };
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
                this.step = 3;
                this.$nextTick(() => { if (this.$refs.montoInput) this.$refs.montoInput.focus(); });
            },

            back() {
                if (this.step > this.minStep) {
                    this.step -= 1;
                }
            },

            async submitCobro() {
                if (this.loading) return;
                var monto = Number(this.monto);
                if (!this.item || !this.sel.bolsilloId || !monto || monto <= 0) {
                    window.notify('warning', 'Datos incompletos', 'Selecciona un bolsillo y un monto válido.');
                    return;
                }
                if (monto > this.maxCobro) {
                    window.notify('warning', 'Monto alto', 'El cobro supera el saldo pendiente. Máximo ' + this.fmt(this.maxCobro) + '.');
                    return;
                }

                this.loading = true;
                window.uiBlocker.show('Guardando…');
                var url = config.cobrarUrlTemplate.replace('CPC_ID', String(this.item.id));

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
                        throw new Error(err.message || 'No se pudo registrar el cobro.');
                    }

                    const data = await response.json();
                    window.notify('success', 'Cobro registrado', data.message || 'Cobro registrado.');
                    this.closeModal();
                    setTimeout(() => window.location.reload(), 600);
                } catch (e) {
                    window.notify('error', 'Error', e.message || 'No se pudo registrar el cobro.');
                } finally {
                    this.loading = false;
                    window.uiBlocker.hide();
                }
            },
        };
    }

    $(function () {
        var endpoint = @json(route('finanzas.cuentas-por-cobrar.data'));
        var $searchInput = $('#cpc-search');
        var $perPageInput = $('#cpc-per-page');
        var $estadoInput = $('#cpc-estado');
        var $cards = $('#cpcCards');
        var $meta = $('#cpcMeta');
        var $pagination = $('#cpcPagination');
        var $totalWrap = $('#cpcTotalWrap');
        var $total = $('#cpcTotal');
        var currentPage = 1;
        var listState = window.listUtils.createStateHtml({ entity: 'cuentas por cobrar', colSpan: 4 });

        function loadItems(page) {
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
                        $meta.text('No hay cuentas por cobrar registradas.');
                        return;
                    }

                    $meta.text('Mostrando ' + meta.from + '-' + meta.to + ' de ' + meta.total + ' cuentas por cobrar');
                }).fail(function () {
                    $cards.html('<div class="list-empty">No se pudo cargar el listado.</div>');
                    $pagination.html('');
                    $meta.text('Error cargando cuentas por cobrar.');
                    window.notify('error', 'Cuentas por cobrar', 'No se pudo obtener la lista.');
                });
        }

        // Botón "Cobrar" en tarjetas (inyectadas por AJAX) → dispara el asistente Alpine.
        $cards.on('click', '.deuda-pay-btn', function () {
            var $btn = $(this);
            window.dispatchEvent(new CustomEvent('cuenta-cobrar', { detail: {
                id: $btn.data('id'),
                nombre: $btn.data('nombre'),
                saldo: $btn.data('saldo'),
                vencimiento: $btn.data('vencimiento'),
                vencida: String($btn.data('vencida')) === '1',
            } }));
        });

        window.listUtils.bindFilterControls({ searchInput: $searchInput, perPageInput: $perPageInput, pagination: $pagination, getCurrentPage: function () { return currentPage; }, loadPage: loadItems });
        $estadoInput.on('change', function () { loadItems(1); });
        window.listUtils.bindPersistentFilters({
            storageKey: 'cuentas-por-cobrar',
            inputs: { search: $searchInput, per_page: $perPageInput, estado: $estadoInput },
        });
        loadItems();
    });
</script>
@endsection
