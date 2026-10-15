@extends('layouts.main')

@php
    $modules = collect($layoutNavigation ?? []);
    $quickLinks = $modules
        ->flatMap(function ($module) {
            return collect($module['items'])->map(function ($item) use ($module) {
                $item['module_title'] = $module['title'];

                return $item;
            });
        })
        ->values();

    $appointmentsLink = $quickLinks->firstWhere('route_name', 'citas.listado');
    $primaryAction = $appointmentsLink ?: $quickLinks->first();
@endphp

@section('title', 'Panel - xNova')
@section('page-title', 'Panel')

@section('styles')
<style>
    .dashboard{display:grid;gap:20px}
    .dashboard-hero{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(260px,.85fr);gap:20px}
    .hero-card{position:relative;overflow:hidden;padding:28px;border-radius:var(--radius-lg);background:var(--surface);border:1px solid var(--border);box-shadow:var(--shadow-md);display:grid;grid-template-columns:minmax(0,1fr) minmax(180px,250px);align-items:center;gap:12px}
    .hero-card::after{content:'';position:absolute;right:-80px;top:-90px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,var(--primary-soft) 0%,transparent 70%);pointer-events:none}
    .hero-copy{position:relative;z-index:1;display:grid;gap:14px}
    .hero-tag{display:inline-flex;width:fit-content;align-items:center;padding:7px 13px;border-radius:var(--radius-pill);background:var(--primary-soft);color:var(--primary);font-size:.78rem;font-weight:600;letter-spacing:.01em}
    .hero-title{font-size:clamp(1.8rem,2.9vw,2.7rem);line-height:1.05;letter-spacing:-.02em;font-weight:700;color:var(--text-primary)}
    .hero-text{max-width:42ch;font-size:.95rem;line-height:1.6;color:var(--text-secondary);text-wrap:pretty}
    .hero-image{position:relative;z-index:1;width:100%;max-height:220px;object-fit:contain;justify-self:end}
    .hero-empty{display:inline-flex;width:fit-content;padding:12px 16px;border-radius:var(--radius-md);background:var(--surface-soft);color:var(--text-secondary);font-size:.9rem}
    .stats-card{padding:24px;border-radius:var(--radius-lg);background:#1b2030;color:#fff;display:grid;gap:18px;box-shadow:var(--shadow-md)}
    .stats-card h2{font-size:.82rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#aab2c5}
    .stats-grid{display:grid;gap:12px}
    .stat{padding:16px 18px;border-radius:var(--radius-md);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.07)}
    .stat span{display:block;font-size:.8rem;color:#aab2c5}
    .stat strong{display:block;margin-top:6px;font-size:1.9rem;line-height:1;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
    .section-card{padding:24px;border-radius:var(--radius-lg)}
    .section-head{display:flex;align-items:end;justify-content:space-between;gap:14px;margin-bottom:18px}
    .section-title{font-size:1.15rem;font-weight:700;letter-spacing:-.02em;color:var(--text-primary)}
    .section-copy{font-size:.88rem;color:var(--text-secondary)}
    .modules-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    @keyframes card-rise{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
    .module-card{display:grid;gap:10px;padding:18px;border-radius:var(--radius-lg);border:1px solid var(--border);background:#fff;text-decoration:none;color:var(--text-primary);box-shadow:var(--shadow-sm);transition:transform .18s var(--ease-out),box-shadow .2s var(--ease-out),border-color .2s ease;animation:card-rise .5s var(--ease-out) backwards}
    .module-card:nth-child(1){animation-delay:.04s}
    .module-card:nth-child(2){animation-delay:.08s}
    .module-card:nth-child(3){animation-delay:.12s}
    .module-card:nth-child(4){animation-delay:.16s}
    .module-card:nth-child(5){animation-delay:.2s}
    .module-card:nth-child(6){animation-delay:.24s}
    .module-card:nth-child(n+7){animation-delay:.28s}
    .module-card:hover{transform:translateY(-3px);border-color:var(--primary);box-shadow:var(--shadow-md)}
    .module-card:active{transform:translateY(-1px) scale(.99)}
    .module-card__icon{width:48px;height:48px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;background:var(--primary-soft);color:var(--primary);transition:transform .18s var(--ease-out)}
    .module-card:hover .module-card__icon{transform:scale(1.06)}
    .module-card__icon svg{width:24px;height:24px;fill:currentColor}
    .module-card__title{font-size:.95rem;font-weight:600;letter-spacing:-.01em}
    .module-card__description{font-size:.8rem;line-height:1.5;color:var(--text-secondary)}
    .module-card__meta{font-size:.74rem;font-weight:500;color:var(--text-muted)}
    .empty-state{padding:20px;border-radius:var(--radius-md);background:var(--surface-soft);border:1px dashed var(--border-strong);color:var(--text-secondary)}
    @media (max-width: 1080px){
        .dashboard-hero{grid-template-columns:1fr}
        .modules-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media (max-width: 640px){
        .hero-card{grid-template-columns:1fr;gap:4px}
        .hero-image{max-height:170px;justify-self:center}
        .modules-grid{grid-template-columns:1fr}
        .section-head{flex-direction:column;align-items:flex-start}
    }
</style>
@endsection

@section('content')
<div class="dashboard">
    <section class="dashboard-hero">
        <article class="hero-card">
            <div class="hero-copy">
                <span class="hero-tag">xNova</span>
                <h1 class="hero-title">Empieza a trabajar con tus accesos activos</h1>
                <p class="hero-text">Tu menu lateral y estos accesos se arman en base a los permisos de tu rol, tambien cuando entres desde movil.</p>

                @if ($primaryAction)
                    <a href="{{ $primaryAction['href'] }}" class="btn btn-primary">
                        <svg viewBox="0 0 24 24">{!! $primaryAction['icon'] !!}</svg>
                        <span>{{ $appointmentsLink ? 'Ir a Citas' : 'Abrir '.$primaryAction['title'] }}</span>
                    </a>
                @else
                    <span class="hero-empty">Todavia no hay pantallas asignadas a este rol.</span>
                @endif
            </div>

            <img src="{{ asset('images/AstronautaSentado.png') }}" alt="Astronauta xNova" class="hero-image">
        </article>

        <article class="stats-card">
            <h2>Resumen de acceso</h2>

            <div class="stats-grid">
                <div class="stat">
                    <span>Modulos visibles</span>
                    <strong>{{ $modules->count() }}</strong>
                </div>
                <div class="stat">
                    <span>Pantallas disponibles</span>
                    <strong>{{ $quickLinks->count() }}</strong>
                </div>
                <div class="stat">
                    <span>Rol actual</span>
                    <strong style="font-size:1.1rem;line-height:1.4">{{ $currentUserRoleName ?? 'Sin rol' }}</strong>
                </div>
            </div>
        </article>
    </section>

    <section class="card section-card">
        <div class="section-head">
            <div>
                <h2 class="section-title">Pantallas disponibles</h2>
                <p class="section-copy">Estos accesos cambian segun el rol asignado al usuario.</p>
            </div>
        </div>

        @if ($quickLinks->isEmpty())
            <div class="empty-state">No hay pantallas activas para este usuario todavia.</div>
        @else
            <div class="modules-grid">
                @foreach ($quickLinks as $item)
                    <a href="{{ $item['href'] }}" class="module-card">
                        <span class="module-card__icon">
                            <svg viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                        </span>
                        <span class="module-card__title">{{ $item['title'] }}</span>
                        <span class="module-card__description">Entrar a {{ strtolower($item['title']) }} desde {{ strtolower($item['module_title']) }}.</span>
                        <span class="module-card__meta">{{ $item['module_title'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
