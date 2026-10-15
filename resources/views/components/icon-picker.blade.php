@props([
    'name' => 'icono',
    'value' => null,
    'label' => 'Ícono',
    'placeholder' => 'Toca para elegir un ícono',
])

@php
    $icons = [
        ['name' => 'banknotes', 'label' => 'Billetes'],
        ['name' => 'credit-card', 'label' => 'Tarjeta'],
        ['name' => 'building-library', 'label' => 'Banco'],
        ['name' => 'wallet', 'label' => 'Cartera'],
        ['name' => 'home', 'label' => 'Hogar'],
        ['name' => 'key', 'label' => 'Alquiler'],
        ['name' => 'bolt', 'label' => 'Luz'],
        ['name' => 'fire', 'label' => 'Gas'],
        ['name' => 'wifi', 'label' => 'Internet'],
        ['name' => 'truck', 'label' => 'Transporte'],
        ['name' => 'globe-americas', 'label' => 'Viajes'],
        ['name' => 'map-pin', 'label' => 'Local'],
        ['name' => 'shopping-cart', 'label' => 'Compras'],
        ['name' => 'shopping-bag', 'label' => 'Tienda'],
        ['name' => 'cake', 'label' => 'Comida'],
        ['name' => 'gift', 'label' => 'Regalo'],
        ['name' => 'heart', 'label' => 'Salud'],
        ['name' => 'beaker', 'label' => 'Medicina'],
        ['name' => 'academic-cap', 'label' => 'Educación'],
        ['name' => 'briefcase', 'label' => 'Trabajo'],
        ['name' => 'book-open', 'label' => 'Libros'],
        ['name' => 'film', 'label' => 'Cine'],
        ['name' => 'musical-note', 'label' => 'Música'],
        ['name' => 'sparkles', 'label' => 'Belleza'],
        ['name' => 'star', 'label' => 'Favorito'],
        ['name' => 'phone', 'label' => 'Teléfono'],
        ['name' => 'envelope', 'label' => 'Suscripción'],
        ['name' => 'device-phone-mobile', 'label' => 'Móvil'],
        ['name' => 'user-group', 'label' => 'Familia'],
        ['name' => 'chart-bar', 'label' => 'Inversión'],
    ];

    $labelMap = collect($icons)->pluck('label', 'name')->toArray();
@endphp

<div
    x-data="iconPicker(@js($value), @js($labelMap))"
    class="icon-picker"
>
    <label class="icon-picker__label">{{ $label }}</label>

    <input type="hidden" name="{{ $name }}" :value="selected || ''">

    {{-- TRIGGER --}}
    <button
        type="button"
        x-on:click="openPicker()"
        class="icon-picker__trigger"
        :class="{ 'is-empty': !selected }"
    >
        <span class="icon-picker__trigger-icon">
            @foreach ($icons as $icon)
                <x-icon name="{{ $icon['name'] }}" class="icon-picker__trigger-svg" x-show="selected === '{{ $icon['name'] }}'" x-cloak />
            @endforeach
            <svg class="icon-picker__trigger-empty" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" x-show="!selected">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M9 12h6M12 9v6"></path>
            </svg>
        </span>
        <span class="icon-picker__trigger-text">
            <span class="icon-picker__trigger-name" x-text="selected ? (labels[selected] || selected) : @js($placeholder)"></span>
            <span class="icon-picker__trigger-hint" x-show="!selected">Selecciona el ícono que mejor representa este registro.</span>
            <span class="icon-picker__trigger-hint" x-show="selected" x-cloak>Toca para cambiarlo.</span>
        </span>
        <svg class="icon-picker__trigger-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m9 6 6 6-6 6"></path>
        </svg>
    </button>

    {{-- MODAL --}}
    <div
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="close()"
        class="icon-picker-modal"
    >
        <div
            class="icon-picker-modal__backdrop"
            x-on:click="close()"
            x-transition:enter="ipm-fade"
            x-transition:enter-start="ipm-fade-start"
            x-transition:enter-end="ipm-fade-end"
            x-transition:leave="ipm-fade"
            x-transition:leave-start="ipm-fade-end"
            x-transition:leave-end="ipm-fade-start"
        ></div>

        <div
            class="icon-picker-modal__dialog"
            x-transition:enter="ipm-dialog"
            x-transition:enter-start="ipm-dialog-start"
            x-transition:enter-end="ipm-dialog-end"
            x-transition:leave="ipm-dialog"
            x-transition:leave-start="ipm-dialog-end"
            x-transition:leave-end="ipm-dialog-start"
        >
            <header class="icon-picker-modal__head">
                <div>
                    <h3 class="icon-picker-modal__title">Elige un ícono</h3>
                    <p class="icon-picker-modal__copy">Toca uno para asignarlo.</p>
                </div>
                <button type="button" x-on:click="close()" class="icon-picker-modal__close" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </header>

            <div class="icon-picker-modal__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input type="text" x-model="search" placeholder="Buscar (ej. comida, banco, luz...)" autocomplete="off">
                <button type="button" x-show="search" x-cloak x-on:click="search = ''" class="icon-picker-modal__search-clear">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
            </div>

            <div class="icon-picker-modal__grid">
                @foreach ($icons as $icon)
                    <button
                        type="button"
                        x-show="matches('{{ $icon['name'] }}')"
                        x-on:click="pick('{{ $icon['name'] }}')"
                        :class="{ 'is-selected': selected === '{{ $icon['name'] }}' }"
                        class="icon-picker-modal__option"
                        title="{{ $icon['label'] }}"
                    >
                        <span class="icon-picker-modal__option-icon">
                            <x-icon name="{{ $icon['name'] }}" class="icon-picker-modal__option-svg" />
                        </span>
                        <span class="icon-picker-modal__option-label">{{ $icon['label'] }}</span>
                    </button>
                @endforeach
            </div>

            <footer class="icon-picker-modal__foot" x-show="selected" x-cloak>
                <button type="button" x-on:click="clear()" class="icon-picker-modal__clear">Quitar ícono</button>
            </footer>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('alpine:init', function () {
            window.Alpine.data('iconPicker', function (initialValue, initialLabels) {
                return {
                    open: false,
                    selected: initialValue || null,
                    search: '',
                    labels: initialLabels || {},
                    pick: function (name) {
                        this.selected = name;
                        var self = this;
                        setTimeout(function () { self.close(); }, 180);
                    },
                    clear: function () {
                        this.selected = null;
                        this.close();
                    },
                    openPicker: function () {
                        this.open = true;
                        this.search = '';
                        document.body.style.overflow = 'hidden';
                    },
                    close: function () {
                        this.open = false;
                        document.body.style.overflow = '';
                    },
                    matches: function (name) {
                        if (!this.search) return true;
                        var label = (this.labels[name] || name).toLowerCase();
                        var query = this.search.toLowerCase();
                        return label.indexOf(query) !== -1 || name.toLowerCase().indexOf(query) !== -1;
                    },
                };
            });
        });
    </script>
    <style>
        [x-cloak] { display: none !important; }

        .icon-picker { display:grid; gap:7px; }
        .icon-picker__label { font-size:.82rem; font-weight:600; color:var(--text-secondary, #666b75); }

        /* ===== TRIGGER ===== */
        .icon-picker__trigger {
            display:flex; align-items:center; gap:14px;
            width:100%; padding:14px 16px;
            border:1px solid var(--border, #e7e9ef); border-radius:14px;
            background:#fff; color:var(--text-primary, #17181c);
            font:inherit; text-align:left;
            cursor:pointer;
            transition:border-color .2s ease, box-shadow .2s ease, transform .15s ease;
        }
        .icon-picker__trigger:hover {
            border-color:var(--primary, #4461f2);
            box-shadow:0 8px 24px rgba(68,97,242,.10);
        }
        .icon-picker__trigger:active { transform:scale(.99); }

        .icon-picker__trigger-icon {
            width:48px; height:48px; border-radius:12px;
            background:var(--primary-soft, rgba(68,97,242,.1)); color:var(--primary, #4461f2);
            display:grid; place-items:center; flex-shrink:0;
            position:relative;
            transition:transform .2s ease;
        }
        .icon-picker__trigger:hover .icon-picker__trigger-icon { transform:scale(1.05); }
        .icon-picker__trigger-icon > * { grid-area:1/1; }
        .icon-picker__trigger-svg { width:24px; height:24px; }
        .icon-picker__trigger-empty { width:24px; height:24px; opacity:.45; }

        .icon-picker__trigger-text { flex:1; display:grid; gap:2px; min-width:0; }
        .icon-picker__trigger-name {
            font-size:.95rem; font-weight:600; color:var(--text-primary, #17181c);
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .icon-picker__trigger.is-empty .icon-picker__trigger-name {
            color:var(--text-secondary, #666b75); font-weight:500;
        }
        .icon-picker__trigger-hint {
            font-size:.78rem; font-weight:400; color:var(--text-secondary, #666b75);
        }
        .icon-picker__trigger-arrow {
            width:18px; height:18px; color:#9aa0ab; flex-shrink:0;
        }

        /* ===== MODAL ===== */
        .icon-picker-modal {
            position:fixed; inset:0; z-index:200;
            display:flex; align-items:center; justify-content:center;
            padding:20px;
        }
        .icon-picker-modal__backdrop {
            position:absolute; inset:0;
            background:rgba(15,23,42,.55);
            backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
        }
        .icon-picker-modal__dialog {
            position:relative;
            width:min(640px, 100%);
            max-height:calc(100vh - 40px);
            background:#fff;
            border-radius:20px;
            box-shadow:0 32px 80px rgba(15,23,42,.35);
            display:flex; flex-direction:column;
            overflow:hidden;
        }

        /* Animaciones */
        .ipm-fade { transition:opacity .25s ease; }
        .ipm-fade-start { opacity:0; }
        .ipm-fade-end { opacity:1; }
        .ipm-dialog { transition:opacity .3s ease, transform .3s cubic-bezier(.22,1,.36,1); }
        .ipm-dialog-start { opacity:0; transform:translateY(20px) scale(.95); }
        .ipm-dialog-end { opacity:1; transform:translateY(0) scale(1); }

        .icon-picker-modal__head {
            display:flex; justify-content:space-between; align-items:flex-start; gap:14px;
            padding:24px 28px 18px;
            border-bottom:1px solid var(--border, #e7e9ef);
        }
        .icon-picker-modal__title {
            font-size:1.2rem; font-weight:700; color:var(--text-primary, #17181c);
            letter-spacing:-.01em;
        }
        .icon-picker-modal__copy {
            font-size:.85rem; color:var(--text-secondary, #666b75); margin-top:3px;
        }
        .icon-picker-modal__close {
            width:36px; height:36px; border:none; background:#f6f7fb;
            border-radius:10px; color:var(--text-secondary, #666b75); cursor:pointer;
            display:grid; place-items:center; flex-shrink:0;
            transition:background .2s ease, color .2s ease;
        }
        .icon-picker-modal__close svg { width:16px; height:16px; }
        .icon-picker-modal__close:hover { background:var(--danger-soft, #fee2e2); color:var(--danger, #ef4444); }

        .icon-picker-modal__search {
            display:flex; align-items:center; gap:10px;
            margin:14px 22px 8px;
            padding:0 14px;
            border:1px solid var(--border, #e7e9ef); border-radius:12px;
            background:#fff;
            transition:border-color .2s ease, box-shadow .2s ease;
        }
        .icon-picker-modal__search:focus-within {
            border-color:var(--primary, #4461f2);
            box-shadow:0 0 0 3px rgba(68,97,242,.10);
        }
        .icon-picker-modal__search > svg { width:18px; height:18px; color:#9aa0ab; flex-shrink:0; }
        .icon-picker-modal__search input {
            flex:1; min-height:42px; border:none; outline:none; background:transparent;
            font:inherit; font-size:.92rem; color:var(--text-primary, #17181c);
        }
        .icon-picker-modal__search-clear {
            width:24px; height:24px; border:none; background:transparent;
            color:#9aa0ab; cursor:pointer; display:grid; place-items:center;
            border-radius:6px;
        }
        .icon-picker-modal__search-clear:hover { background:#f3f4f7; color:var(--text-primary, #17181c); }
        .icon-picker-modal__search-clear svg { width:14px; height:14px; }

        .icon-picker-modal__grid {
            padding:14px 22px 22px;
            overflow-y:auto;
            display:grid;
            grid-template-columns:repeat(5, minmax(0, 1fr));
            gap:10px;
            flex:1;
        }
        .icon-picker-modal__option {
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:8px;
            padding:14px 8px;
            background:#f6f7fb; border:1.5px solid transparent; border-radius:14px;
            cursor:pointer;
            transition:all .18s ease;
        }
        .icon-picker-modal__option:hover {
            background:var(--primary-soft, rgba(68,97,242,.1));
            border-color:var(--primary, #4461f2);
            transform:translateY(-2px);
            box-shadow:0 6px 16px rgba(68,97,242,.15);
        }
        .icon-picker-modal__option.is-selected {
            background:var(--primary, #4461f2);
            border-color:var(--primary, #4461f2);
            color:#fff;
            box-shadow:0 8px 22px rgba(68,97,242,.35);
        }
        .icon-picker-modal__option.is-selected .icon-picker-modal__option-icon {
            background:rgba(255,255,255,.18); color:#fff;
        }
        .icon-picker-modal__option.is-selected .icon-picker-modal__option-label { color:#fff; }

        .icon-picker-modal__option-icon {
            width:42px; height:42px; border-radius:12px;
            background:rgba(68,97,242,.12); color:var(--primary, #4461f2);
            display:grid; place-items:center;
            transition:background .2s ease, color .2s ease;
        }
        .icon-picker-modal__option-svg { width:22px; height:22px; }
        .icon-picker-modal__option-label {
            font-size:.72rem; font-weight:600; color:var(--text-secondary, #666b75);
            text-align:center; line-height:1.2;
            transition:color .2s ease;
        }

        .icon-picker-modal__foot {
            padding:14px 22px;
            border-top:1px solid var(--border, #e7e9ef);
            display:flex; justify-content:flex-end;
            background:#fafbfc;
        }
        .icon-picker-modal__clear {
            border:none; background:transparent;
            color:var(--danger, #ef4444);
            font:inherit; font-size:.85rem; font-weight:600;
            cursor:pointer; padding:8px 14px; border-radius:8px;
            transition:background .2s ease;
        }
        .icon-picker-modal__clear:hover { background:var(--danger-soft, #fee2e2); }

        @media (max-width: 560px) {
            .icon-picker-modal { padding:0; align-items:flex-end; }
            .icon-picker-modal__dialog {
                width:100%; max-height:88vh; border-radius:20px 20px 0 0;
            }
            .icon-picker-modal__grid { grid-template-columns:repeat(4, minmax(0, 1fr)); }
            .ipm-dialog-start { transform:translateY(100%) scale(1); }
        }
    </style>
@endonce
