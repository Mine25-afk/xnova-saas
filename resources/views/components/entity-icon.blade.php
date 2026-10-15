@props([
    'icon' => null,
    'fallback' => null,
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'sm' => 'entity-icon--sm',
        'lg' => 'entity-icon--lg',
        'xl' => 'entity-icon--xl',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => 'entity-icon ' . $sizeClass]) }}>
    @if ($icon)
        <x-icon name="{{ $icon }}" class="entity-icon__svg" />
    @elseif ($fallback)
        <span class="entity-icon__initial">{{ mb_strtoupper(mb_substr($fallback, 0, 1)) }}</span>
    @else
        <svg class="entity-icon__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="9"></circle>
        </svg>
    @endif
</span>

@once
    <style>
        .entity-icon {
            width:40px; height:40px;
            border-radius:11px;
            background:var(--primary-soft, rgba(68,97,242,.1));
            color:var(--primary, #4461f2);
            display:inline-flex; align-items:center; justify-content:center;
            flex-shrink:0;
        }
        .entity-icon__svg { width:20px; height:20px; }
        .entity-icon__initial { font-size:1rem; font-weight:700; }
        .entity-icon--sm { width:32px; height:32px; border-radius:9px; }
        .entity-icon--sm .entity-icon__svg { width:16px; height:16px; }
        .entity-icon--sm .entity-icon__initial { font-size:.86rem; }
        .entity-icon--lg { width:56px; height:56px; border-radius:14px; }
        .entity-icon--lg .entity-icon__svg { width:28px; height:28px; }
        .entity-icon--lg .entity-icon__initial { font-size:1.3rem; }
        .entity-icon--xl { width:72px; height:72px; border-radius:18px; }
        .entity-icon--xl .entity-icon__svg { width:36px; height:36px; }
        .entity-icon--xl .entity-icon__initial { font-size:1.7rem; }
        .list-card-item__head {
            display:flex; align-items:center; gap:12px;
        }

        /* === Fin-card: card fina y minimalista para listados de Finanzas === */
        .fin-card {
            position: relative;
            display: grid;
            gap: 12px;
            padding: 14px 16px;
            background: var(--surface, #fff);
            border: 1px solid var(--border, #e7e9ef);
            border-radius: 14px;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
            cursor: pointer;
        }
        .fin-card:hover {
            border-color: rgba(68, 97, 242, .35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .05);
            transform: translateY(-1px);
        }
        .fin-card:focus-within {
            border-color: var(--primary, #4461f2);
            box-shadow: 0 0 0 3px rgba(68, 97, 242, .15);
        }
        .fin-card--muted { opacity: .62; }

        /* Overlay link que hace la card entera clickeable */
        .fin-card__link {
            position: absolute;
            inset: 0;
            z-index: 1;
            border-radius: inherit;
            text-indent: -9999px;
            overflow: hidden;
        }
        .fin-card__link:focus-visible { outline: none; }

        /* Contenido por encima del overlay para los enlaces internos */
        .fin-card__row,
        .fin-card__footer { position: relative; z-index: 2; pointer-events: none; }
        .fin-card__footer .fin-card__status,
        .fin-card__footer .fin-card__actions,
        .fin-card__footer form { pointer-events: auto; }

        .fin-card__row {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 12px;
        }
        .fin-card__main { min-width: 0; }
        .fin-card__title {
            font-size: .96rem;
            font-weight: 700;
            color: var(--text-primary, #17181c);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .fin-card__meta {
            font-size: .78rem;
            color: var(--text-secondary, #666b75);
            margin-top: 3px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            line-height: 1.3;
        }
        .fin-card__dot {
            width: 3px; height: 3px;
            border-radius: 50%;
            background: var(--text-muted, #9a9ea7);
            display: inline-block;
            flex-shrink: 0;
        }
        .fin-card__amount {
            font-size: 1.02rem;
            font-weight: 700;
            color: var(--text-primary, #17181c);
            letter-spacing: -.01em;
            white-space: nowrap;
        }
        .fin-card__amount--neg { color: var(--danger, #ef4444); }

        .fin-card__badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(68, 97, 242, .12);
            color: var(--primary, #4461f2);
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .fin-card__progress {
            position: relative;
            z-index: 2;
            pointer-events: none;
            height: 6px;
            border-radius: 999px;
            background: #eef0f6;
            overflow: hidden;
        }
        .fin-card__progress-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--primary, #4461f2), var(--success, #10b981));
        }

        .fin-card__inline-form { display: inline; }
        .fin-card__danger-btn {
            background: none;
            border: none;
            padding: 0;
            font: inherit;
            font-size: .82rem;
            font-weight: 600;
            color: var(--danger, #ef4444);
            cursor: pointer;
            transition: color .15s ease;
        }
        .fin-card__danger-btn:hover { text-decoration: underline; }

        .fin-card__footer {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-top: 10px;
            border-top: 1px dashed var(--border, #e7e9ef);
        }
        .fin-card__status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .74rem;
            font-weight: 600;
            color: var(--text-secondary, #666b75);
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .fin-card__status::before {
            content: "";
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--success, #10b981);
            flex-shrink: 0;
        }
        .fin-card__status--off::before { background: var(--text-muted, #9a9ea7); }
        .fin-card__actions {
            display: flex;
            gap: 14px;
            margin-left: auto;
        }
        .fin-card__actions a {
            font-size: .82rem;
            font-weight: 600;
            color: var(--primary, #4461f2);
            text-decoration: none;
            transition: color .15s ease;
        }
        .fin-card__actions a:hover {
            color: var(--primary-dark, #3651e0);
            text-decoration: underline;
        }

        @media (max-width: 560px) {
            .fin-card__row {
                grid-template-columns: auto 1fr;
            }
            .fin-card__amount {
                grid-column: 1 / -1;
                text-align: left;
                font-size: 1.1rem;
                padding-left: 52px;
                margin-top: -4px;
            }
            .fin-card__footer { flex-wrap: wrap; }
        }
    </style>
@endonce
