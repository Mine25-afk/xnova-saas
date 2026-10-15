@extends('layouts.main')

@section('title', ($empresa['nombre'] ?? 'Empresa') . ' — Superadmin')
@section('page-title', 'Superadmin / Empresas / ' . ($empresa['nombre'] ?? ''))

@section('styles')
    <style>
        .sa-detail { display: grid; gap: 18px; max-width: 880px; }
        .sa-back { display: inline-flex; align-items: center; gap: 7px; color: var(--text-secondary); text-decoration: none; font-size: .88rem; font-weight: 600; }
        .sa-back:hover { color: var(--primary); }
        .sa-back svg { width: 18px; height: 18px; }

        .sa-hero { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 22px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .sa-hero__avatar { width: 60px; height: 60px; border-radius: 16px; display: grid; place-items: center; color: #fff; font-weight: 700; font-size: 1.6rem; flex-shrink: 0; }
        .sa-hero__main { flex: 1; min-width: 200px; }
        .sa-hero__name { font-size: 1.4rem; font-weight: 700; letter-spacing: -.02em; color: var(--text-primary); }
        .sa-hero__meta { font-size: .86rem; color: var(--text-secondary); margin-top: 2px; }

        .sa-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 99px; font-size: .8rem; font-weight: 700; }
        .sa-badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .sa-badge--trial { background: #fef3c7; color: #b45309; }
        .sa-badge--activo { background: var(--success-soft); color: #047857; }
        .sa-badge--vencido { background: var(--danger-soft); color: var(--danger); }
        .sa-badge--sin_paquete { background: var(--surface-soft); color: var(--text-muted); }

        .sa-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
        .sa-panel { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 18px 20px; }
        .sa-panel__title { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); margin-bottom: 12px; }
        .sa-row { display: flex; justify-content: space-between; gap: 12px; padding: 7px 0; font-size: .9rem; }
        .sa-row + .sa-row { border-top: 1px solid var(--border); }
        .sa-row__k { color: var(--text-secondary); }
        .sa-row__v { color: var(--text-primary); font-weight: 600; text-align: right; }
        .sa-row__v--urgente { color: var(--danger); }

        .sa-mods { display: flex; flex-wrap: wrap; gap: 8px; }
        .sa-mod { display: inline-flex; align-items: center; gap: 7px; padding: 7px 12px; border-radius: 10px; background: var(--primary-soft); color: var(--primary); font-size: .82rem; font-weight: 600; }
        .sa-mod__dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

        .sa-note { font-size: .82rem; color: var(--text-muted); }
    </style>
@endsection

@section('content')
@php $estado = $empresa['estado_codigo']; @endphp
<div class="sa-detail">
    <a href="{{ route('superadmin.empresas.index') }}" class="sa-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        Volver a empresas
    </a>

    <div class="sa-hero">
        <span class="sa-hero__avatar" style="background: #4461f2">{{ mb_strtoupper(mb_substr($empresa['nombre'] ?? '?', 0, 1)) }}</span>
        <div class="sa-hero__main">
            <div class="sa-hero__name">{{ $empresa['nombre'] }}</div>
            <div class="sa-hero__meta">{{ $empresa['tipo'] ?? 'Sin tipo' }} · creada {{ $empresa['creada']?->format('d/m/Y') ?? '—' }}</div>
        </div>
        <span class="sa-badge sa-badge--{{ $estado }}">{{ $empresa['estado_label'] }}</span>
    </div>

    <div class="sa-grid">
        <div class="sa-panel">
            <div class="sa-panel__title">Dueño</div>
            <div class="sa-row"><span class="sa-row__k">Nombre</span><span class="sa-row__v">{{ $empresa['owner_nombre'] ?? '—' }}</span></div>
            <div class="sa-row"><span class="sa-row__k">Correo</span><span class="sa-row__v">{{ $empresa['owner_email'] ?? '—' }}</span></div>
            <div class="sa-row"><span class="sa-row__k">Empresa activa</span><span class="sa-row__v">{{ $empresa['activa'] ? 'Sí' : 'No' }}</span></div>
        </div>

        <div class="sa-panel">
            <div class="sa-panel__title">Suscripción</div>
            <div class="sa-row"><span class="sa-row__k">Paquete</span><span class="sa-row__v">{{ $empresa['paquete'] ?? 'Sin paquete' }}</span></div>
            <div class="sa-row"><span class="sa-row__k">Precio</span><span class="sa-row__v">{{ $empresa['precio_mensual'] !== null ? $empresa['moneda'] . ' ' . number_format($empresa['precio_mensual'], 2) . '/mes' : '—' }}</span></div>
            <div class="sa-row"><span class="sa-row__k">Tipo</span><span class="sa-row__v">{{ $empresa['proveedor'] === 'trial' ? 'Prueba gratis' : ($empresa['proveedor'] ?? '—') }}</span></div>
            <div class="sa-row">
                <span class="sa-row__k">Vence</span>
                <span class="sa-row__v {{ ($empresa['dias_restantes'] !== null && $empresa['dias_restantes'] <= 3) ? 'sa-row__v--urgente' : '' }}">
                    @if ($empresa['fecha_fin'])
                        {{ $empresa['fecha_fin']->format('d/m/Y') }}
                        @if ($empresa['dias_restantes'] !== null)
                            ({{ $empresa['dias_restantes'] < 0 ? 'hace ' . abs($empresa['dias_restantes']) . ' d' : 'en ' . $empresa['dias_restantes'] . ' d' }})
                        @endif
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="sa-row"><span class="sa-row__k">Usuarios</span><span class="sa-row__v">{{ $empresa['usuarios_totales'] ?? '—' }}</span></div>
        </div>
    </div>

    <div class="sa-panel">
        <div class="sa-panel__title">Módulos activos ({{ $empresa['modulos_count'] }})</div>
        @if (empty($empresa['modulos']))
            <p class="sa-note">Esta empresa no tiene módulos asignados.</p>
        @else
            <div class="sa-mods">
                @foreach ($empresa['modulos'] as $m)
                    <span class="sa-mod"><span class="sa-mod__dot"></span>{{ $m['nombre'] }}</span>
                @endforeach
            </div>
            <p class="sa-note" style="margin-top:12px">Los módulos se determinan por el tipo de empresa. La edición por empresa llegará en la siguiente fase.</p>
        @endif
    </div>
</div>
@endsection
