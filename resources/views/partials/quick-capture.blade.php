{{-- ============================================================
     Captura rápida (FAB global). Se incluye desde layouts/main.blade.php.
     Solo se renderiza si el usuario puede crear movimientos de finanzas.
     Registra un gasto/ingreso en 3 toques sin salir de la pantalla actual.
     ============================================================ --}}
<link rel="stylesheet" href="{{ asset('css/quick-capture.css') }}?v=2">

<div
    x-data="quickCapture({
        opcionesUrl: @js(route('finanzas.quick.opciones')),
        storeUrl: @js(route('finanzas.quick.store')),
        monedaDefault: @js($monedaDefault ?? 'LPS'),
    })"
    @keydown.escape.window="open && close()"
>
    {{-- FAB --}}
    <button
        type="button"
        class="qc-fab"
        :class="{ 'is-open': open }"
        @click="toggle()"
        aria-label="Captura rápida de ingreso o gasto"
        title="Captura rápida (registrar gasto/ingreso)"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
    </button>

    {{-- MODAL --}}
    <div class="qc-modal" x-show="open" x-cloak style="display:none;" role="dialog" aria-modal="true" aria-label="Captura rápida">
        <div
            class="qc-modal__backdrop"
            x-show="open"
            x-transition:enter="qc-fade" x-transition:enter-start="qc-fade-start" x-transition:enter-end="qc-fade-end"
            x-transition:leave="qc-fade" x-transition:leave-start="qc-fade-end" x-transition:leave-end="qc-fade-start"
            @click="close()"
        ></div>

            <div
                class="qc-modal__dialog"
                x-show="open"
                x-transition:enter="qc-enter" x-transition:enter-start="qc-enter-start" x-transition:enter-end="qc-enter-end"
                x-transition:leave="qc-leave" x-transition:leave-start="qc-leave-start" x-transition:leave-end="qc-leave-end"
            >
                <span class="qc-head__grab"></span>
                <div class="qc-head">
                    <h2 class="qc-head__title">Registro rápido</h2>
                    <button type="button" class="qc-head__close" @click="close()" aria-label="Cerrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <path d="M18 6 6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                {{-- Sin cuentas: invitamos a crear una --}}
                <template x-if="loaded && cuentas.length === 0">
                    <div class="qc-empty">
                        Aún no tienes cuentas. <a href="{{ route('finanzas.cuentas.create') }}">Crea tu primera cuenta</a> para empezar a registrar.
                    </div>
                </template>

                <template x-if="cuentas.length > 0">
                    <form class="qc-body" @submit.prevent="guardar(false)">
                        {{-- Toggle gasto / ingreso --}}
                        <div class="qc-toggle" role="group" aria-label="Tipo de movimiento">
                            <button type="button" data-tipo="gasto" :class="{ 'is-active': tipo === 'gasto' }" @click="tipo = 'gasto'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7 7 7-7"/></svg>
                                Gasto
                            </button>
                            <button type="button" data-tipo="ingreso" :class="{ 'is-active': tipo === 'ingreso' }" @click="tipo = 'ingreso'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7-7-7 7"/></svg>
                                Ingreso
                            </button>
                        </div>

                        {{-- Propietario: chips (solo si hay más de uno) --}}
                        <div class="qc-field" x-show="hayVariosDuenos" x-cloak>
                            <label class="qc-field__label">¿De quién es la cuenta?</label>
                            <div class="qc-owners">
                                <template x-for="p in propietarios" :key="p.id ?? 'sin'">
                                    <button type="button" class="qc-owner" :class="{ 'is-active': mismoId(p.id, propietarioId) }" @click="seleccionarPropietario(p.id)">
                                        <span class="qc-owner__avatar" :style="'background:' + colorDe(p.nombre)" x-text="inicial(p.nombre)"></span>
                                        <span x-text="p.nombre"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Cuenta: tarjetas visuales (icono/inicial y saldo), filtradas por propietario --}}
                        <div class="qc-field">
                            <label class="qc-field__label">¿De qué cuenta?</label>
                            <div class="qc-cuentas">
                                <template x-for="cuenta in cuentasFiltradas()" :key="cuenta.id">
                                    <button type="button" class="qc-acct" :class="{ 'is-active': cuenta.id === cuentaId }" @click="seleccionarCuenta(cuenta.id)">
                                        <span class="qc-acct__avatar" :style="'background:' + colorDe(cuenta.nombre)" x-text="inicial(cuenta.nombre)"></span>
                                        <span class="qc-acct__info">
                                            <span class="qc-acct__name" x-text="cuenta.nombre"></span>
                                        </span>
                                        <span class="qc-acct__balance" x-text="cuenta.moneda + ' ' + fmt(cuenta.saldo)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Bolsillo: chips con saldo (solo si la cuenta tiene más de uno) --}}
                        <div class="qc-field" x-show="bolsillosActuales().length > 1" x-cloak>
                            <label class="qc-field__label">¿En qué bolsillo?</label>
                            <div class="qc-bolsillos">
                                <template x-for="b in bolsillosActuales()" :key="b.id">
                                    <button type="button" class="qc-chip" :class="{ 'is-active': b.id === subcuentaId }" @click="subcuentaId = b.id">
                                        <svg class="qc-chip__ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" x-show="b.es_general"><path d="M3 7h18v12H3z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                                        <svg class="qc-chip__ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" x-show="!b.es_general"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
                                        <span class="qc-chip__txt">
                                            <span x-text="b.nombre"></span>
                                            <span class="qc-chip__saldo" :class="{ 'is-negativo': Number(b.saldo) < 0 }" x-text="monedaActual() + ' ' + fmt(b.saldo)"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Monto --}}
                        <div class="qc-amount" :class="tipo === 'ingreso' ? 'is-ingreso' : 'is-gasto'">
                            <span class="qc-amount__label">Monto</span>
                            <div class="qc-amount__field">
                                <span class="qc-amount__currency" x-text="monedaActual()"></span>
                                <input
                                    type="number" inputmode="decimal" step="0.01" min="0.01"
                                    class="qc-amount__input" placeholder="0.00"
                                    x-model="monto" x-ref="montoInput"
                                >
                            </div>
                        </div>

                        {{-- Favoritos --}}
                        <template x-if="favoritos.length > 0">
                            <div class="qc-favs">
                                <template x-for="(fav, i) in favoritos" :key="i">
                                    <button type="button" class="qc-fav" @click="aplicarFavorito(fav)">
                                        <span class="qc-fav__dot" :class="fav.tipo === 'ingreso' ? 'is-ingreso' : 'is-gasto'"></span>
                                        <span x-text="fav.descripcion"></span>
                                    </button>
                                </template>
                            </div>
                        </template>

                        {{-- Más detalles (descripción + categoría + fecha) --}}
                        <button type="button" class="qc-more" :class="{ 'is-open': showMore }" @click="showMore = !showMore">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                            <span x-text="showMore ? 'Menos detalles' : 'Añadir descripción y categoría'"></span>
                        </button>

                        <div class="qc-more-panel" x-show="showMore" x-cloak>
                            <div class="qc-field">
                                <label class="qc-field__label" for="qc-desc">Descripción</label>
                                <input id="qc-desc" type="text" class="qc-control" maxlength="180"
                                    placeholder="Ej. Almuerzo, gasolina…" x-model="descripcion" list="qc-desc-list">
                                <datalist id="qc-desc-list">
                                    <template x-for="(fav, i) in favoritos" :key="'d'+i">
                                        <option :value="fav.descripcion"></option>
                                    </template>
                                </datalist>
                            </div>
                            <div class="qc-field">
                                <label class="qc-field__label" for="qc-cat">Categoría</label>
                                <input id="qc-cat" type="text" class="qc-control" maxlength="80"
                                    placeholder="Ej. Comida, Transporte…" x-model="categoria" list="qc-cat-list">
                                <datalist id="qc-cat-list">
                                    <template x-for="(cat, i) in categorias" :key="'c'+i">
                                        <option :value="cat"></option>
                                    </template>
                                </datalist>
                            </div>
                            <div class="qc-field">
                                <label class="qc-field__label" for="qc-fecha">Fecha</label>
                                <input id="qc-fecha" type="date" class="qc-control" x-model="fecha">
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="qc-actions">
                            <button type="submit" class="qc-btn qc-btn--primary" :disabled="loading">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <span x-text="loading ? 'Guardando…' : 'Guardar'"></span>
                            </button>
                            <button type="button" class="qc-btn qc-btn--ghost" :disabled="loading" @click="guardar(true)">
                                Guardar y otro
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
</div>

<script>
    function quickCapture(config) {
        return {
            open: false,
            loaded: false,
            loading: false,
            showMore: false,
            tipo: 'gasto',
            monto: '',
            descripcion: '',
            categoria: '',
            fecha: '',
            cuentaId: null,
            subcuentaId: null,
            // undefined = aún sin elegir dueño; null = grupo "Sin propietario".
            propietarioId: undefined,
            cuentas: [],
            propietarios: [],
            hayVariosDuenos: false,
            categorias: [],
            favoritos: [],

            cuentaActual() {
                return this.cuentas.find((c) => c.id === this.cuentaId) || null;
            },

            // Compara ids de propietario tratando null y undefined como iguales.
            mismoId(a, b) {
                return (a ?? null) === (b ?? null);
            },

            cuentasFiltradas() {
                if (!this.hayVariosDuenos || this.propietarioId === undefined) return this.cuentas;
                return this.cuentas.filter((c) => this.mismoId(c.propietario_id, this.propietarioId));
            },

            seleccionarPropietario(id) {
                this.propietarioId = id ?? null;
                var actual = this.cuentaActual();
                if (!actual || !this.mismoId(actual.propietario_id, this.propietarioId)) {
                    var primera = this.cuentasFiltradas()[0];
                    this.seleccionarCuenta(primera ? primera.id : null);
                }
            },

            saldoActual() {
                var c = this.cuentaActual();
                return c ? Number(c.saldo || 0) : 0;
            },

            bolsillosActuales() {
                var c = this.cuentaActual();
                return (c && c.bolsillos) ? c.bolsillos : [];
            },

            monedaActual() {
                var c = this.cuentaActual();
                return (c && c.moneda) ? c.moneda : config.monedaDefault;
            },

            fmt(n) {
                return Number(n || 0).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            // Inicial para el avatar de la cuenta.
            inicial(nombre) {
                return (nombre || '?').trim().charAt(0).toUpperCase();
            },

            // Color estable derivado del nombre (mismo nombre → mismo color).
            colorDe(nombre) {
                var paleta = ['#4461f2', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6'];
                var s = String(nombre || '');
                var h = 0;
                for (var i = 0; i < s.length; i++) { h = (h * 31 + s.charCodeAt(i)) >>> 0; }
                return paleta[h % paleta.length];
            },

            // Al cambiar de cuenta, preselecciona el bolsillo pedido o su General.
            onCuentaChange(subcuentaId) {
                var bolsillos = this.bolsillosActuales();
                var pedido = subcuentaId ? bolsillos.find((b) => b.id === subcuentaId) : null;
                var general = pedido || bolsillos.find((b) => b.es_general) || bolsillos[0];
                this.subcuentaId = general ? general.id : null;
            },

            async toggle() {
                if (this.open) { this.close(); return; }
                this.open = true;
                document.body.style.overflow = 'hidden';
                if (!this.loaded) {
                    await this.cargarOpciones();
                }
                this.$nextTick(() => { if (this.$refs.montoInput) this.$refs.montoInput.focus(); });
            },

            close() {
                this.open = false;
                document.body.style.overflow = '';
            },

            hoy() {
                // Fecha local en formato YYYY-MM-DD sin desfase de zona horaria.
                var d = new Date();
                var off = d.getTimezoneOffset();
                return new Date(d.getTime() - off * 60000).toISOString().slice(0, 10);
            },

            async cargarOpciones() {
                try {
                    const res = await fetch(config.opcionesUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('No se pudieron cargar las opciones.');
                    const data = await res.json();
                    this.cuentas = data.cuentas || [];
                    this.propietarios = data.propietarios || [];
                    this.hayVariosDuenos = this.propietarios.length > 1;
                    this.categorias = data.categorias || [];
                    this.favoritos = data.favoritos || [];
                    this.fecha = this.hoy();
                    // Preselecciona la última cuenta y bolsillo usados (con su propietario).
                    var ult = data.ultima_seleccion || null;
                    if (ult && this.cuentas.some((c) => c.id === ult.cuenta_id)) {
                        this.seleccionarCuenta(ult.cuenta_id, ult.subcuenta_id);
                    } else {
                        this.seleccionarCuenta(data.ultima_cuenta_id || (this.cuentas[0] ? this.cuentas[0].id : null));
                    }
                } catch (e) {
                    window.notify('error', 'Error', e.message || 'No se pudieron cargar las opciones.');
                } finally {
                    this.loaded = true;
                }
            },

            // Selecciona una cuenta, sincroniza su propietario y fija el bolsillo
            // indicado (o el General si no aplica).
            seleccionarCuenta(id, subcuentaId) {
                var cuenta = this.cuentas.find((c) => c.id === id) || this.cuentas[0] || null;
                this.cuentaId = cuenta ? cuenta.id : null;
                if (cuenta) this.propietarioId = cuenta.propietario_id ?? null;
                this.onCuentaChange(subcuentaId);
            },

            aplicarFavorito(fav) {
                this.tipo = fav.tipo || 'gasto';
                this.descripcion = fav.descripcion || '';
                this.categoria = fav.categoria || '';
                if (fav.monto) this.monto = fav.monto;
                if (fav.finanzas_cuenta_id && this.cuentas.some((c) => c.id === fav.finanzas_cuenta_id)) {
                    this.seleccionarCuenta(fav.finanzas_cuenta_id);
                }
                this.$nextTick(() => { if (this.$refs.montoInput) this.$refs.montoInput.focus(); });
            },

            resetCampos() {
                this.monto = '';
                this.descripcion = '';
                this.categoria = '';
                this.showMore = false;
                this.fecha = this.hoy();
            },

            async guardar(seguir) {
                if (this.loading) return;
                var monto = Number(this.monto);
                if (!monto || monto <= 0) {
                    window.notify('warning', 'Falta el monto', 'Ingresa un monto mayor a cero.');
                    this.$nextTick(() => { if (this.$refs.montoInput) this.$refs.montoInput.focus(); });
                    return;
                }
                if (!this.cuentaId) {
                    window.notify('warning', 'Falta la cuenta', 'Selecciona una cuenta.');
                    return;
                }

                this.loading = true;
                if (window.uiBlocker) window.uiBlocker.show('Guardando…');

                try {
                    const res = await fetch(config.storeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            tipo: this.tipo,
                            finanzas_cuenta_id: this.cuentaId,
                            finanzas_subcuenta_id: this.subcuentaId || null,
                            monto: monto,
                            descripcion: this.descripcion || null,
                            categoria: this.categoria || null,
                            fecha: this.fecha || null,
                        }),
                    });

                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        throw new Error(err.message || 'No se pudo guardar.');
                    }

                    const data = await res.json();
                    var titulo = this.tipo === 'ingreso' ? 'Ingreso' : 'Gasto';
                    var mensaje = data.message || 'Registrado correctamente.';
                    if (data.saldo_cuenta !== undefined && data.saldo_cuenta !== null) {
                        mensaje += ' Saldo de la cuenta: ' + (data.moneda || '') + ' ' + this.fmt(data.saldo_cuenta) + '.';
                        // Refresca los saldos locales para que "Guardar y otro" los muestre al día.
                        var c = this.cuentaActual();
                        if (c) c.saldo = Number(data.saldo_cuenta);
                    }
                    if (data.saldo_bolsillo !== undefined && data.saldo_bolsillo !== null) {
                        var b = this.bolsillosActuales().find((x) => x.id === data.subcuenta_id);
                        if (b) b.saldo = Number(data.saldo_bolsillo);
                    }
                    window.notify('success', titulo, mensaje);

                    if (seguir) {
                        this.resetCampos();
                        this.$nextTick(() => { if (this.$refs.montoInput) this.$refs.montoInput.focus(); });
                    } else {
                        this.close();
                        // Si estamos en una pantalla de finanzas, refrescamos para ver el cambio reflejado.
                        if (/\/finanzas(\/|$)/.test(window.location.pathname)) {
                            setTimeout(() => window.location.reload(), 650);
                        }
                    }
                } catch (e) {
                    window.notify('error', 'Error', e.message || 'No se pudo guardar.');
                } finally {
                    this.loading = false;
                    if (window.uiBlocker) window.uiBlocker.hide();
                }
            },
        };
    }
</script>
