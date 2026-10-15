@extends('layouts.main')

@section('title', 'Empresas — Superadmin')
@section('page-title', 'Superadmin / Empresas')

@section('styles')
    <style>
        .sa-wrap { display: grid; gap: 20px; }
        .sa-head__title { font-size: 1.4rem; font-weight: 700; letter-spacing: -.02em; color: var(--text-primary); }
        .sa-head__sub { font-size: .9rem; color: var(--text-secondary); margin-top: 2px; }

        .sa-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
        .sa-stat { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 16px 18px; }
        .sa-stat__label { font-size: .76rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); }
        .sa-stat__value { font-size: 1.7rem; font-weight: 700; color: var(--text-primary); margin-top: 4px; letter-spacing: -.02em; }
        .sa-stat--trial .sa-stat__value { color: #b45309; }
        .sa-stat--vencido .sa-stat__value { color: var(--danger); }
        .sa-stat--activo .sa-stat__value { color: var(--success); }

        .sa-card { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; overflow: hidden; }
        .sa-table { width: 100%; border-collapse: collapse; }
        .sa-table th { text-align: left; font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); padding: 13px 16px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .sa-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); font-size: .9rem; color: var(--text-primary); vertical-align: middle; }
        .sa-table tr:last-child td { border-bottom: none; }
        .sa-table tbody tr { cursor: pointer; transition: background .15s ease; }
        .sa-table tbody tr:hover { background: var(--surface-soft); }

        .sa-empresa { display: flex; align-items: center; gap: 11px; }
        .sa-avatar { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-weight: 700; flex-shrink: 0; }
        .sa-empresa__name { font-weight: 700; }
        .sa-empresa__tipo { font-size: .76rem; color: var(--text-muted); }

        .sa-owner__email { font-size: .8rem; color: var(--text-secondary); }

        .sa-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 99px; font-size: .76rem; font-weight: 700; }
        .sa-badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .sa-badge--trial { background: #fef3c7; color: #b45309; }
        .sa-badge--activo { background: var(--success-soft); color: #047857; }
        .sa-badge--vencido { background: var(--danger-soft); color: var(--danger); }
        .sa-badge--sin_paquete { background: var(--surface-soft); color: var(--text-muted); }

        .sa-dias { font-size: .82rem; color: var(--text-secondary); }
        .sa-dias--urgente { color: var(--danger); font-weight: 700; }
        .sa-mods { font-size: .8rem; color: var(--text-secondary); }

        .sa-empty { padding: 40px 16px; text-align: center; color: var(--text-muted); }

        @media (max-width: 820px) {
            .sa-hide-sm { display: none; }
            .sa-table td, .sa-table th { padding: 12px 12px; }
        }
    </style>
@endsection

@section('content')
@php $colores = ['#4461f2', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6']; @endphp
<div class="sa-wrap">
    <div class="sa-head">
        <h1 class="sa-head__title">Empresas</h1>
        <p class="sa-head__sub">Estado de suscripción, paquete y módulos de cada empresa registrada.</p>
    </div>

    <div class="sa-stats">
        <div class="sa-stat"><div class="sa-stat__label">Total</div><div class="sa-stat__value">{{ $stats['total'] }}</div></div>
        <div class="sa-stat sa-stat--trial"><div class="sa-stat__label">En prueba</div><div class="sa-stat__value">{{ $stats['en_prueba'] }}</div></div>
        <div class="sa-stat sa-stat--vencido"><div class="sa-stat__label">Vencidas</div><div class="sa-stat__value">{{ $stats['vencidas'] }}</div></div>
        <div class="sa-stat sa-stat--activo"><div class="sa-stat__label">Activas</div><div class="sa-stat__value">{{ $stats['activas'] }}</div></div>
        <div class="sa-stat"><div class="sa-stat__label">Inactivas</div><div class="sa-stat__value">{{ $stats['inactivas'] }}</div></div>
    </div>

    <div class="sa-card">
        @if ($empresas->isEmpty())
            <div class="sa-empty">No hay empresas registradas.</div>
        @else
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th class="sa-hide-sm">Dueño</th>
                        <th class="sa-hide-sm">Paquete</th>
                        <th>Estado</th>
                        <th>Vence</th>
                        <th class="sa-hide-sm">Módulos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $e)
                        @php $url = route('superadmin.empresas.show', $e['id']); @endphp
                        <tr onclick="window.location='{{ $url }}'">
                            <td>
                                <div class="sa-empresa">
                                    <span class="sa-avatar" style="background: {{ $colores[$loop->index % count($colores)] }}">{{ mb_strtoupper(mb_substr($e['nombre'] ?? '?', 0, 1)) }}</span>
                                    <div>
                                        <div class="sa-empresa__name">{{ $e['nombre'] }} @unless($e['activa'])<span class="sa-badge sa-badge--vencido" style="margin-left:6px">inactiva</span>@endunless</div>
                                        <div class="sa-empresa__tipo">{{ $e['tipo'] ?? 'Sin tipo' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="sa-hide-sm">
                                <div>{{ $e['owner_nombre'] ?? '—' }}</div>
                                <div class="sa-owner__email">{{ $e['owner_email'] ?? '' }}</div>
                            </td>
                            <td class="sa-hide-sm">
                                {{ $e['paquete'] ?? '—' }}
                                @if ($e['precio_mensual'] !== null)
                                    <div class="sa-owner__email">{{ $e['moneda'] }} {{ number_format($e['precio_mensual'], 2) }}/mes</div>
                                @endif
                            </td>
                            <td><span class="sa-badge sa-badge--{{ $e['estado_codigo'] }}">{{ $e['estado_label'] }}</span></td>
                            <td>
                                @if ($e['fecha_fin'])
                                    <div>{{ $e['fecha_fin']->format('d/m/Y') }}</div>
                                    @if ($e['dias_restantes'] !== null)
                                        <div class="sa-dias {{ $e['dias_restantes'] <= 3 ? 'sa-dias--urgente' : '' }}">
                                            @if ($e['dias_restantes'] < 0)
                                                hace {{ abs($e['dias_restantes']) }} d
                                            @else
                                                en {{ $e['dias_restantes'] }} d
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <span class="sa-dias">—</span>
                                @endif
                            </td>
                            <td class="sa-hide-sm"><span class="sa-mods">{{ $e['modulos_count'] }} módulo{{ $e['modulos_count'] === 1 ? '' : 's' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
