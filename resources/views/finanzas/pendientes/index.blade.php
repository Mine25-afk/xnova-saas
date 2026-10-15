@extends('layouts.main')

@section('title', 'Pendientes — Finanzas')
@section('page-title', 'Finanzas / Pendientes')

@section('styles')
    <style>
        .pend-wrap { display: grid; gap: 18px; max-width: 920px; }
        .pend-head__title { font-size: 1.4rem; font-weight: 700; letter-spacing: -.02em; color: var(--text-primary); }
        .pend-head__sub { font-size: .9rem; color: var(--text-secondary); margin-top: 2px; }

        .pend-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
        .pend-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 18px;
            padding: 16px 18px; display: grid; gap: 12px;
            box-shadow: var(--shadow-xs);
        }
        .pend-card__top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .pend-tag { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 99px; font-size: .74rem; font-weight: 700; }
        .pend-tag::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .pend-tag--transferencia { background: var(--primary-soft); color: var(--primary); }
        .pend-tag--deuda { background: var(--danger-soft); color: var(--danger); }
        .pend-tag--cobro { background: var(--success-soft); color: #047857; }
        .pend-card__fecha { font-size: .78rem; color: var(--text-muted); }

        .pend-card__monto { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; color: var(--text-primary); }
        .pend-card__desc { font-size: .9rem; color: var(--text-primary); font-weight: 600; }
        .pend-flow { display: flex; align-items: center; gap: 8px; font-size: .82rem; color: var(--text-secondary); flex-wrap: wrap; }
        .pend-flow__chip { background: var(--surface-soft); border: 1px solid var(--border); border-radius: 9px; padding: 4px 9px; font-weight: 600; }
        .pend-flow svg { width: 15px; height: 15px; color: var(--text-muted); flex-shrink: 0; }

        .pend-actions { display: grid; grid-template-columns: 1fr auto; gap: 9px; margin-top: 2px; }
        .pend-btn { height: 44px; padding: 0 16px; border: none; border-radius: 12px; font: inherit; font-size: .9rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 7px; transition: background .2s ease, transform .1s ease, opacity .2s ease; }
        .pend-btn svg { width: 17px; height: 17px; }
        .pend-btn--ok { background: var(--success); color: #fff; }
        .pend-btn--ok:hover { filter: brightness(.95); }
        .pend-btn--ok:active { transform: scale(.98); }
        .pend-btn--cancel { background: var(--surface-soft); color: var(--text-secondary); }
        .pend-btn--cancel:hover { background: var(--danger-soft); color: var(--danger); }
        .pend-btn[disabled] { opacity: .55; cursor: not-allowed; }

        .pend-empty { text-align: center; padding: 50px 20px; color: var(--text-muted); }
        .pend-empty svg { width: 46px; height: 46px; color: var(--border-strong); margin-bottom: 10px; }
    </style>
@endsection

@section('content')
<div
    class="pend-wrap"
    x-data="pendientes({
        items: @js($pendientes),
        confirmarUrlTemplate: @js($confirmarUrlTemplate),
        anularUrlTemplate: @js($anularUrlTemplate),
    })"
>
    <div class="pend-head">
        <h1 class="pend-head__title">Operaciones pendientes</h1>
        <p class="pend-head__sub">Confirma aquí lo que ya pasó en la vida real. El saldo se mueve solo cuando confirmas.</p>
    </div>

    <template x-if="items.length === 0">
        <div class="pend-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
            <p>No tienes operaciones pendientes. Todo al día. 🎉</p>
        </div>
    </template>

    <div class="pend-grid" x-show="items.length > 0">
        <template x-for="it in items" :key="it.id">
            <div class="pend-card">
                <div class="pend-card__top">
                    <span class="pend-tag" :class="'pend-tag--' + it.clase" x-text="it.titulo"></span>
                    <span class="pend-card__fecha" x-text="it.fecha"></span>
                </div>

                <div>
                    <div class="pend-card__monto" x-text="it.moneda + ' ' + fmt(it.monto)"></div>
                    <div class="pend-card__desc" x-text="it.descripcion"></div>
                </div>

                <div class="pend-flow">
                    <span class="pend-flow__chip" x-text="it.origen"></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    <span class="pend-flow__chip" x-text="it.destino"></span>
                </div>

                <div class="pend-actions">
                    <button type="button" class="pend-btn pend-btn--ok" :disabled="busy === it.id" @click="confirmar(it)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        <span x-text="busy === it.id ? 'Confirmando…' : 'Confirmar'"></span>
                    </button>
                    <button type="button" class="pend-btn pend-btn--cancel" :disabled="busy === it.id" @click="cancelar(it)">Cancelar</button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    function pendientes(config) {
        return {
            items: config.items || [],
            busy: null,

            fmt(n) {
                return Number(n || 0).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },

            async post(url) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({}),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'No se pudo procesar.');
                }
                return res.json();
            },

            remover(id) {
                this.items = this.items.filter((x) => x.id !== id);
            },

            async confirmar(it) {
                if (this.busy) return;
                this.busy = it.id;
                if (window.uiBlocker) window.uiBlocker.show('Confirmando…');
                try {
                    const data = await this.post(this.confirmarUrlTemplate().replace('MOV_ID', it.id));
                    window.notify('success', 'Confirmado', data.message || 'Operación confirmada.');
                    this.remover(it.id);
                } catch (e) {
                    window.notify('error', 'Error', e.message);
                } finally {
                    this.busy = null;
                    if (window.uiBlocker) window.uiBlocker.hide();
                }
            },

            async cancelar(it) {
                if (this.busy) return;
                this.busy = it.id;
                if (window.uiBlocker) window.uiBlocker.show('Cancelando…');
                try {
                    const data = await this.post(this.anularUrlTemplate().replace('MOV_ID', it.id));
                    window.notify('success', 'Cancelado', data.message || 'Pendiente cancelado.');
                    this.remover(it.id);
                } catch (e) {
                    window.notify('error', 'Error', e.message);
                } finally {
                    this.busy = null;
                    if (window.uiBlocker) window.uiBlocker.hide();
                }
            },

            // Las URLs vienen como string; las envolvemos para reusar .replace().
            confirmarUrlTemplate() { return config.confirmarUrlTemplate; },
            anularUrlTemplate() { return config.anularUrlTemplate; },
        };
    }
</script>
@endsection
