@php
    $navigation = $layoutNavigation ?? [];
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'xNova')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
    <wireui:scripts />
    @vite('resources/js/app.js')
    @vite('resources/css/app.css')
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --sidebar-width:248px;
            --header-height:72px;
            /* Acento — identidad azul calibrada */
            --primary:#4461f2;
            --primary-dark:#3651e0;
            --primary-soft:rgba(68,97,242,.10);
            --primary-ring:rgba(68,97,242,.18);
            /* Neutrales fríos — una sola familia (slate) */
            --bg:#f5f6fa;
            --surface:#ffffff;
            --surface-soft:#f3f5f9;
            --border:#e6e8ef;
            --border-color:#e6e8ef;
            --border-strong:#d7dae3;
            --text-primary:#161922;
            --text-secondary:#5b616e;
            --text-muted:#9499a5;
            --success:#10b981;
            --success-soft:#d1fae5;
            --danger:#ef4444;
            --danger-soft:#fee2e2;
            /* Escala de radios — bloqueada */
            --radius-sm:10px;
            --radius-md:12px;
            --radius-lg:16px;
            --radius-pill:999px;
            /* Sombras tintadas y por capas (sin glow) */
            --shadow-xs:0 1px 2px rgba(20,26,45,.05);
            --shadow-sm:0 1px 2px rgba(20,26,45,.04),0 4px 12px -6px rgba(20,26,45,.10);
            --shadow-md:0 1px 3px rgba(20,26,45,.05),0 12px 28px -14px rgba(20,26,45,.14);
            /* Curvas de easing (las nativas son demasiado suaves) */
            --ease-out:cubic-bezier(.23,1,.32,1);
            --ease-in-out:cubic-bezier(.77,0,.175,1);
            --ease-drawer:cubic-bezier(.32,.72,0,1);
        }
        @keyframes page-enter{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
        body{min-height:100dvh;font-family:Poppins,sans-serif;background:linear-gradient(180deg,#fbfcff 0%,var(--bg) 100%);color:var(--text-primary);-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
        h1,h2,h3,.header-title{text-wrap:balance}
        .tnum{font-variant-numeric:tabular-nums;font-feature-settings:"tnum"}
        body.sidebar-open,body.sheet-open{overflow:hidden}
        @keyframes ui-blocker-spin{to{transform:rotate(360deg)}}
        .ui-blocker{position:fixed;inset:0;z-index:9990;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.42);backdrop-filter:blur(3px);opacity:0;pointer-events:none;transition:opacity .2s var(--ease-out)}
        .ui-blocker.is-active{opacity:1;pointer-events:auto}
        .ui-blocker__box{display:flex;flex-direction:column;align-items:center;gap:14px;padding:26px 30px;border-radius:var(--radius-lg);background:var(--surface);box-shadow:var(--shadow-md)}
        .ui-blocker__spinner{width:42px;height:42px;border-radius:50%;border:3px solid var(--border);border-top-color:var(--primary);animation:ui-blocker-spin .7s linear infinite}
        .ui-blocker__text{font-size:.92rem;font-weight:600;color:var(--text-secondary);letter-spacing:-.01em}
        .skip-link{position:fixed;left:16px;top:16px;z-index:999;transform:translateY(-140%);padding:10px 14px;border-radius:12px;background:var(--primary);color:#fff;text-decoration:none;font-weight:700;box-shadow:0 12px 26px rgba(15,23,42,.18);transition:transform .2s ease}
        .skip-link:focus{transform:translateY(0)}
        :focus-visible{outline:3px solid rgba(68,97,242,.55);outline-offset:3px}
        .app-shell{min-height:100dvh}
        .sidebar-overlay{position:fixed;inset:0;background:rgba(15,23,42,.35);backdrop-filter:blur(3px);opacity:0;pointer-events:none;transition:opacity .25s var(--ease-out);z-index:110}
        .sidebar-overlay.is-active{opacity:1;pointer-events:auto}
        .sidebar{position:fixed;inset:0 auto 0 0;width:var(--sidebar-width);background:#fafafb;border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:120;transition:transform .38s var(--ease-drawer)}
        .sidebar-brand{display:flex;align-items:center;gap:10px;padding:20px 20px 14px;text-decoration:none}
        .sidebar-brand__icon{width:32px;height:32px;border-radius:8px;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
        .sidebar-brand__icon svg{width:18px;height:18px;fill:currentColor}
        .sidebar-brand__text strong{font-size:1.15rem;font-weight:700;letter-spacing:-.02em;color:var(--text-primary)}
        .sidebar-search{padding:0 16px 16px}
        .sidebar-search__box{height:42px;display:flex;align-items:center;gap:9px;padding:0 10px 0 12px;border:1px solid var(--border);border-radius:12px;background:var(--surface-soft,#f3f5f9);transition:border-color .18s ease,background .18s ease,box-shadow .18s ease}
        .sidebar-search__box:focus-within{background:#fff;border-color:var(--primary);box-shadow:0 0 0 3px rgba(68,97,242,.12)}
        .sidebar-search__box svg{width:16px;height:16px;fill:#9aa0ab;flex-shrink:0;transition:fill .18s ease}
        .sidebar-search__box:focus-within svg{fill:var(--primary)}
        .sidebar-search__box input{flex:1;min-width:0;width:100%;border:none;outline:none;background:transparent;font:inherit;font-size:.86rem;color:var(--text-primary)}
        .sidebar-search__box input::placeholder{color:#9aa0ab}
        .sidebar-search__box input::-webkit-search-cancel-button{display:none}
        .sidebar-search__box kbd{display:inline-flex;align-items:center;gap:1px;font-family:inherit;font-size:.66rem;font-weight:600;color:#9aa0ab;background:#fff;border:1px solid var(--border);border-radius:6px;padding:3px 7px;line-height:1;flex-shrink:0;box-shadow:0 1px 0 rgba(15,23,42,.04)}
        .sidebar-nav{flex:1;overflow-y:auto;padding:4px 10px 16px}
        .sidebar-item,.sidebar-module-button,.sidebar-footer__logout{display:flex;align-items:center;gap:11px;width:100%;padding:9px 12px;border:none;border-radius:8px;background:none;color:#787d86;text-decoration:none;font:inherit;font-size:.88rem;font-weight:500;text-align:left;cursor:pointer;transition:background .18s ease,color .18s ease}
        .sidebar-item svg,.sidebar-module-button svg,.sidebar-footer__logout svg{width:17px;height:17px;fill:currentColor;flex-shrink:0;opacity:.78}
        .sidebar-item:hover,.sidebar-module-button:hover,.sidebar-footer__logout:hover{background:#eef0f4;color:var(--text-primary)}
        .sidebar-item.is-active,.sidebar-module-button.is-active{background:#fff;color:var(--primary);box-shadow:var(--shadow-xs);border:1px solid var(--border)}
        .sidebar-item.is-active svg,.sidebar-module-button.is-active svg{opacity:1}
        .sidebar-module{display:grid;gap:4px;margin-top:4px}
        .sidebar-module-button span{flex:1}
        .sidebar-module-button i{font-style:normal;font-size:.74rem;color:var(--text-muted);transition:transform .2s ease}
        .sidebar-module.is-open .sidebar-module-button i{transform:rotate(90deg)}
        .sidebar-module-items{display:none;padding-left:10px;margin-left:18px;border-left:1px solid #e5e7ec}
        .sidebar-module.is-open .sidebar-module-items{display:grid;gap:3px;padding-top:4px;padding-bottom:4px}
        .sidebar-module-items .sidebar-item{padding:8px 12px;font-size:.84rem}
        .sidebar-footer{padding:14px 14px;border-top:1px solid var(--border);display:grid;gap:8px;background:#fafafb}
        .sidebar-footer__user{padding:10px 12px;border-radius:10px;background:#fff;border:1px solid var(--border);display:grid;gap:2px}
        .sidebar-footer__user strong{font-size:.86rem;font-weight:600}
        .sidebar-footer__user span{font-size:.76rem;color:var(--text-secondary)}
        .sidebar-footer__logout{color:var(--danger);font-weight:500}
        .sidebar-footer__logout:hover{background:var(--danger-soft);color:var(--danger)}
        .main-content{margin-left:var(--sidebar-width);min-height:100dvh}
        .header{position:sticky;top:0;height:var(--header-height);background:rgba(255,255,255,.9);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;z-index:90}
        .header-left{display:flex;align-items:center;gap:14px;min-width:0}
        .header-title{font-size:1.1rem;font-weight:700;letter-spacing:-.02em;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .header-right{display:flex;align-items:center;gap:12px}
        .mobile-toggle{display:none;width:38px;height:38px;border-radius:10px;border:1px solid var(--border);background:#fff;color:var(--text-primary);cursor:pointer}
        .mobile-toggle svg{width:20px;height:20px;fill:currentColor}
        .header-role{display:inline-flex;align-items:center;padding:7px 12px;border-radius:999px;background:rgba(68,97,242,.1);color:var(--primary);font-size:.76rem;font-weight:600}
        .header-user{display:flex;align-items:center;gap:10px;padding:6px 12px 6px 8px;border-radius:12px;background:#fff;border:1px solid var(--border)}
        .header-avatar{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:#111318;color:#fff;font-weight:700;font-size:.84rem}
        .header-user__meta{display:grid}
        .header-user__meta strong{font-size:.82rem;font-weight:600}
        .header-user__meta span{font-size:.7rem;color:var(--text-muted)}
        .page-content{padding:30px 34px;animation:page-enter .42s var(--ease-out)}
        .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-md)}
        .card-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
        .card-body{padding:24px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 18px;border:none;border-radius:var(--radius-md);text-decoration:none;font:inherit;font-size:.92rem;font-weight:600;letter-spacing:-.01em;cursor:pointer;transition:transform .16s var(--ease-out),box-shadow .2s var(--ease-out),background .18s ease,border-color .18s ease}
        .btn svg{width:18px;height:18px;fill:currentColor}
        .btn:hover{transform:translateY(-1px)}
        .btn:active{transform:scale(.97)}
        .btn-primary{background:var(--primary);color:#fff;box-shadow:0 1px 2px rgba(20,26,45,.18)}
        .btn-primary:hover{background:var(--primary-dark);box-shadow:0 6px 16px -8px rgba(68,97,242,.6)}
        .btn-secondary{background:#fff;color:var(--text-primary);border:1px solid var(--border);box-shadow:var(--shadow-xs)}
        .btn-secondary:hover{border-color:var(--border-strong)}
        .btn-danger{background:var(--danger);color:#fff}
        [x-data*="wireui_dialog"]{align-items:center !important;padding:16px !important}
        [x-data*="wireui_dialog"] > div:last-child{padding:0 !important}
        [wireui-modal]{display:flex;align-items:center;justify-content:center;padding:16px}
        [wireui-modal] > div:last-child{min-height:calc(100dvh - 32px);align-items:center !important}
        .form-group{margin-bottom:18px}
        .form-label{display:block;font-size:.86rem;font-weight:600;color:var(--text-secondary);margin-bottom:8px}
        .form-input,.form-select{width:100%;padding:14px 16px;border-radius:var(--radius-md);border:1px solid var(--border);background:#fff;color:var(--text-primary);font:inherit;transition:border-color .18s ease,box-shadow .18s ease}
        .form-input:focus,.form-select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-ring)}
        .alert{padding:14px 16px;border-radius:var(--radius-md);margin-bottom:18px;font-size:.9rem;font-weight:500}
        .alert-success{background:var(--success-soft);color:var(--success)}
        .alert-error{background:var(--danger-soft);color:var(--danger)}

        /* ===== Navegación móvil (barra inferior + bottom sheets) — oculta en desktop ===== */
        .mobile-nav{display:none}
        .header-brand-mobile{display:none;align-items:center;gap:10px;text-decoration:none}
        .header-brand-mobile__icon{width:32px;height:32px;border-radius:var(--radius-sm);background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
        .header-brand-mobile__icon svg{width:18px;height:18px;fill:currentColor}
        .header-brand-mobile strong{font-size:1.05rem;font-weight:700;letter-spacing:-.02em;color:var(--text-primary)}
        .page-title-mobile{display:none;font-size:1.35rem;font-weight:700;letter-spacing:-.02em;color:var(--text-primary);margin-bottom:16px}
        .mobile-nav__item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;min-height:56px;padding:6px 4px;border:none;background:none;color:var(--text-muted);text-decoration:none;font:inherit;font-size:.68rem;font-weight:600;letter-spacing:.01em;cursor:pointer;transition:color .18s ease,transform .16s var(--ease-out)}
        .mobile-nav__item svg{width:23px;height:23px;fill:currentColor}
        .mobile-nav__item:active{transform:scale(.94)}
        .mobile-nav__item.is-active,.mobile-nav__item[aria-current="page"]{color:var(--primary)}
        .sheet-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.4);backdrop-filter:blur(3px);opacity:0;pointer-events:none;transition:opacity .25s var(--ease-out);z-index:135}
        .sheet-backdrop.is-active{opacity:1;pointer-events:auto}
        .mobile-sheet{position:fixed;left:0;right:0;bottom:0;z-index:140;background:var(--surface);border-top:1px solid var(--border);border-radius:var(--radius-lg) var(--radius-lg) 0 0;box-shadow:var(--shadow-md);max-height:85dvh;display:flex;flex-direction:column;transform:translateY(100%);transition:transform .38s var(--ease-drawer);padding-bottom:env(safe-area-inset-bottom)}
        .mobile-sheet.is-open{transform:translateY(0)}
        .mobile-sheet__grabber{width:40px;height:4px;border-radius:var(--radius-pill);background:var(--border-strong);margin:10px auto 4px;flex-shrink:0}
        .mobile-sheet__head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:6px 18px 12px;border-bottom:1px solid var(--border);flex-shrink:0}
        .mobile-sheet__title{font-size:1rem;font-weight:700;letter-spacing:-.01em;color:var(--text-primary)}
        .mobile-sheet__close{width:34px;height:34px;border-radius:var(--radius-md);border:1px solid var(--border);background:#fff;color:var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:transform .16s var(--ease-out),background .18s ease}
        .mobile-sheet__close:active{transform:scale(.94)}
        .mobile-sheet__close svg{width:18px;height:18px;fill:currentColor}
        .mobile-sheet__body{overflow-y:auto;padding:12px 14px 18px;-webkit-overflow-scrolling:touch}
        .msheet-link,.msheet-module-btn{display:flex;align-items:center;gap:12px;width:100%;min-height:48px;padding:11px 14px;border:none;border-radius:var(--radius-md);background:none;color:var(--text-secondary);text-decoration:none;font:inherit;font-size:.95rem;font-weight:500;text-align:left;cursor:pointer;transition:background .18s ease,color .18s ease}
        .msheet-link svg,.msheet-module-btn svg{width:20px;height:20px;fill:currentColor;flex-shrink:0;opacity:.8}
        .msheet-link:active,.msheet-module-btn:active{background:var(--surface-soft)}
        .msheet-link.is-active{background:var(--primary-soft);color:var(--primary)}
        .msheet-link.is-active svg{opacity:1}
        .msheet-module-btn i{margin-left:auto;font-style:normal;font-size:.9rem;color:var(--text-muted);transition:transform .2s var(--ease-out)}
        .msheet-module.is-open .msheet-module-btn i{transform:rotate(90deg)}
        .msheet-module-items{display:none;padding:2px 0 6px 14px;margin-left:18px;border-left:1px solid var(--border)}
        .msheet-module.is-open .msheet-module-items{display:grid;gap:2px}
        .msheet-module-items .msheet-link{min-height:44px;font-size:.9rem;font-weight:400}
        .msheet-divider{height:1px;background:var(--border);margin:10px 4px}
        .msheet-search{display:flex;align-items:center;gap:9px;height:46px;padding:0 14px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--surface-soft);margin-bottom:12px;transition:border-color .18s ease,background .18s ease,box-shadow .18s ease}
        .msheet-search:focus-within{background:#fff;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-ring)}
        .msheet-search svg{width:18px;height:18px;fill:var(--text-muted);flex-shrink:0}
        .msheet-search input{flex:1;min-width:0;border:none;outline:none;background:transparent;font:inherit;font-size:.95rem;color:var(--text-primary)}
        .msheet-result__meta{margin-left:auto;font-size:.74rem;font-weight:500;color:var(--text-muted)}
        .msheet-empty{display:none;padding:22px 8px;text-align:center;color:var(--text-muted);font-size:.9rem}
        .msheet-user{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--surface-soft);margin-bottom:6px}
        .msheet-user__avatar{width:44px;height:44px;border-radius:var(--radius-md);background:#111318;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0}
        .msheet-user__meta{display:grid;gap:2px;min-width:0}
        .msheet-user__meta strong{font-size:.95rem;font-weight:600}
        .msheet-user__meta span{font-size:.8rem;color:var(--text-secondary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .msheet-user__role{font-size:.78rem;color:var(--primary);font-weight:600;margin-top:1px}
        .msheet-logout{color:var(--danger)}
        .msheet-logout:active{background:var(--danger-soft)}

        @media (max-width: 1024px){
            .sidebar,.sidebar-overlay,.mobile-toggle,.header-title{display:none}
            .main-content{margin-left:0}
            .header{padding:0 18px}
            .header-role{display:none}
            .header-brand-mobile{display:flex}
            .page-title-mobile{display:block}
            .page-content{padding:20px 18px;padding-bottom:calc(76px + env(safe-area-inset-bottom) + 12px)}
            .mobile-nav{display:flex;align-items:stretch;position:fixed;left:0;right:0;bottom:0;z-index:130;background:rgba(255,255,255,.94);backdrop-filter:blur(16px);border-top:1px solid var(--border);padding-bottom:env(safe-area-inset-bottom)}
        }
        @media (max-width: 720px){
            .header-user__meta{display:none}
            .page-content{padding:18px 14px;padding-bottom:calc(76px + env(safe-area-inset-bottom) + 12px)}
            [x-data*="wireui_dialog"],[wireui-modal]{padding:14px !important}
            [wireui-modal] > div:last-child{min-height:calc(100dvh - 28px)}
        }
        @media (prefers-reduced-motion: reduce){
            *,*::before,*::after{animation-duration:.01ms !important;animation-delay:0 !important;animation-iteration-count:1 !important;scroll-behavior:auto !important;transition-duration:.01ms !important}
            .btn:hover,.btn:active{transform:none}
        }
    </style>
    @yield('styles')
    @livewireStyles
</head>
<body>
    <a href="#contenido-principal" class="skip-link">Saltar al contenido</a>
    <div class="app-shell">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

        <aside class="sidebar" id="sidebar" aria-label="Menu principal">
            <a href="{{ route('dashboard') }}" class="sidebar-brand">
                <span class="sidebar-brand__icon">
                    <svg viewBox="0 0 24 24"><path d="M4 3h3v7.5L14.5 3H18l-8 8.5L19 21h-3.5l-7-8-1.5 1.6V21H4V3z"/></svg>
                </span>
                <span class="sidebar-brand__text">
                    <strong>xNova</strong>
                </span>
            </a>

            <div class="sidebar-search">
                <label class="sidebar-search__box" for="sidebarSearch">
                    <svg viewBox="0 0 24 24"><path d="M9.5 3a6.5 6.5 0 0 1 5.16 10.45l4.45 4.44-1.42 1.42-4.44-4.45A6.5 6.5 0 1 1 9.5 3zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/></svg>
                    <input id="sidebarSearch" type="search" placeholder="Buscar módulo…" autocomplete="off">
                    <kbd>Ctrl K</kbd>
                </label>
            </div>

            <nav class="sidebar-nav" aria-label="Navegacion por permisos">
                <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                    <span>Panel</span>
                </a>

                @foreach ($navigation as $module)
                    <div class="sidebar-module {{ $module['active'] ? 'is-open' : '' }}">
                        <button type="button" class="sidebar-module-button {{ $module['active'] ? 'is-active' : '' }}" aria-expanded="{{ $module['active'] ? 'true' : 'false' }}" aria-controls="sidebar-module-{{ $loop->index }}">
                            <svg viewBox="0 0 24 24">{!! $module['icon'] !!}</svg>
                            <span>{{ $module['title'] }}</span>
                            <i>›</i>
                        </button>

                        <div class="sidebar-module-items" id="sidebar-module-{{ $loop->index }}">
                            @foreach ($module['items'] as $item)
                                <a href="{{ $item['href'] }}" class="sidebar-item {{ $item['active'] ? 'is-active' : '' }}">
                                    <svg viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                    <span>{{ $item['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer__user">
                    <strong>{{ $user?->name ?? 'Usuario' }}</strong>
                    <span>{{ $currentUserRoleName ?? 'Sin rol' }}</span>
                </div>

                @if (! empty($isCompanyCreator))
                    <a href="{{ route('empresa.preferencias') }}" class="sidebar-item {{ request()->routeIs('empresa.*') ? 'is-active' : '' }}">
                        <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54A.49.49 0 0 0 13.93 1h-3.86c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.32 7.47c-.11.2-.06.47.12.61l2.03 1.58c-.04.31-.07.65-.07.94s.03.63.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.49.38 1.03.7 1.62.94l.36 2.54c.03.24.23.41.47.41h3.86c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.12-.56 1.62-.94l2.39.96c.22.07.47 0 .59-.22l1.92-3.32c.11-.2.06-.47-.12-.61l-2.01-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg>
                        <span>Preferencias</span>
                    </a>
                @endif

                @if (! empty($isSuperAdmin))
                    <a href="{{ route('superadmin.empresas.index') }}" class="sidebar-item {{ request()->routeIs('superadmin.*') ? 'is-active' : '' }}">
                        <svg viewBox="0 0 24 24"><path d="M12 2l8 4v6c0 5.25-3.44 10.74-8 12-4.56-1.26-8-6.75-8-12V6l8-4z"/></svg>
                        <span>Superadmin</span>
                    </a>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-footer__logout">
                        <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                        <span>Cerrar sesion</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-content" id="contenido-principal" tabindex="-1">
            <header class="header">
                <div class="header-left">
                    <button type="button" class="mobile-toggle" id="mobileToggle" aria-label="Abrir menu" aria-controls="sidebar" aria-expanded="false">
                        <svg viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
                    </button>
                    <a href="{{ route('dashboard') }}" class="header-brand-mobile">
                        <span class="header-brand-mobile__icon">
                            <svg viewBox="0 0 24 24"><path d="M4 3h3v7.5L14.5 3H18l-8 8.5L19 21h-3.5l-7-8-1.5 1.6V21H4V3z"/></svg>
                        </span>
                        <strong>xNova</strong>
                    </a>
                    <h1 class="header-title">@yield('page-title', 'Panel')</h1>
                </div>

                <div class="header-right">
                    <span class="header-role">{{ $currentUserRoleName ?? 'Sin rol' }}</span>
                    <div class="header-user">
                        <span class="header-avatar">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</span>
                        <span class="header-user__meta">
                            <strong>{{ $user?->name ?? 'Usuario' }}</strong>
                            <span>{{ $user?->email ?? 'sin-correo' }}</span>
                        </span>
                    </div>
                </div>
            </header>

            <div class="page-content">
                <h1 class="page-title-mobile">@yield('page-title', 'Panel')</h1>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif

                @yield('content')
            </div>
        </main>

        @php
            $mobileScreens = collect($navigation)->flatMap(function ($module) {
                return collect($module['items'])->map(function ($item) use ($module) {
                    $item['module_title'] = $module['title'];
                    return $item;
                });
            })->values();
        @endphp

        {{-- Navegación móvil: barra inferior + bottom sheets (solo ≤1024px) --}}
        <div class="sheet-backdrop" id="sheetBackdrop" aria-hidden="true"></div>

        <nav class="mobile-nav" aria-label="Navegacion movil">
            <a href="{{ route('dashboard') }}" class="mobile-nav__item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                <span>Panel</span>
            </a>
            <button type="button" class="mobile-nav__item" data-sheet-open="sheetModulos" aria-haspopup="dialog" aria-controls="sheetModulos">
                <svg viewBox="0 0 24 24"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zm-9 9h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                <span>Modulos</span>
            </button>
            <button type="button" class="mobile-nav__item" data-sheet-open="sheetBuscar" aria-haspopup="dialog" aria-controls="sheetBuscar">
                <svg viewBox="0 0 24 24"><path d="M9.5 3a6.5 6.5 0 0 1 5.16 10.45l4.45 4.44-1.42 1.42-4.44-4.45A6.5 6.5 0 1 1 9.5 3zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/></svg>
                <span>Buscar</span>
            </button>
            <button type="button" class="mobile-nav__item" data-sheet-open="sheetPerfil" aria-haspopup="dialog" aria-controls="sheetPerfil">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.34 0-10 1.67-10 5v3h20v-3c0-3.33-6.66-5-10-5z"/></svg>
                <span>Perfil</span>
            </button>
        </nav>

        <div class="mobile-sheet" id="sheetModulos" role="dialog" aria-modal="true" aria-labelledby="sheetModulosTitle" aria-hidden="true">
            <span class="mobile-sheet__grabber" aria-hidden="true"></span>
            <div class="mobile-sheet__head">
                <span class="mobile-sheet__title" id="sheetModulosTitle">Modulos</span>
                <button type="button" class="mobile-sheet__close" data-sheet-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="mobile-sheet__body">
                <a href="{{ route('dashboard') }}" class="msheet-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                    <span>Panel</span>
                </a>
                @foreach ($navigation as $module)
                    <div class="msheet-module {{ $module['active'] ? 'is-open' : '' }}">
                        <button type="button" class="msheet-module-btn" aria-expanded="{{ $module['active'] ? 'true' : 'false' }}">
                            <svg viewBox="0 0 24 24">{!! $module['icon'] !!}</svg>
                            <span>{{ $module['title'] }}</span>
                            <i>&rsaquo;</i>
                        </button>
                        <div class="msheet-module-items">
                            @foreach ($module['items'] as $item)
                                <a href="{{ $item['href'] }}" class="msheet-link {{ $item['active'] ? 'is-active' : '' }}">
                                    <svg viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                    <span>{{ $item['title'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @if (! empty($isCompanyCreator))
                    <div class="msheet-divider"></div>
                    <a href="{{ route('empresa.preferencias') }}" class="msheet-link {{ request()->routeIs('empresa.*') ? 'is-active' : '' }}">
                        <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54A.49.49 0 0 0 13.93 1h-3.86c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.32 7.47c-.11.2-.06.47.12.61l2.03 1.58c-.04.31-.07.65-.07.94s.03.63.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.49.38 1.03.7 1.62.94l.36 2.54c.03.24.23.41.47.41h3.86c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.12-.56 1.62-.94l2.39.96c.22.07.47 0 .59-.22l1.92-3.32c.11-.2.06-.47-.12-.61l-2.01-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg>
                        <span>Preferencias</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="mobile-sheet" id="sheetBuscar" role="dialog" aria-modal="true" aria-labelledby="sheetBuscarTitle" aria-hidden="true">
            <span class="mobile-sheet__grabber" aria-hidden="true"></span>
            <div class="mobile-sheet__head">
                <span class="mobile-sheet__title" id="sheetBuscarTitle">Buscar pantalla</span>
                <button type="button" class="mobile-sheet__close" data-sheet-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="mobile-sheet__body">
                <label class="msheet-search" for="mobileSearchInput">
                    <svg viewBox="0 0 24 24"><path d="M9.5 3a6.5 6.5 0 0 1 5.16 10.45l4.45 4.44-1.42 1.42-4.44-4.45A6.5 6.5 0 1 1 9.5 3zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/></svg>
                    <input id="mobileSearchInput" type="search" placeholder="Buscar pantalla&hellip;" autocomplete="off">
                </label>
                <div id="mobileSearchResults">
                    @foreach ($mobileScreens as $screen)
                        <a href="{{ $screen['href'] }}" class="msheet-link" data-search="{{ \Illuminate\Support\Str::lower($screen['title'].' '.$screen['module_title']) }}">
                            <svg viewBox="0 0 24 24">{!! $screen['icon'] !!}</svg>
                            <span>{{ $screen['title'] }}</span>
                            <span class="msheet-result__meta">{{ $screen['module_title'] }}</span>
                        </a>
                    @endforeach
                    <div class="msheet-empty" id="mobileSearchEmpty">No se encontraron pantallas.</div>
                </div>
            </div>
        </div>

        <div class="mobile-sheet" id="sheetPerfil" role="dialog" aria-modal="true" aria-labelledby="sheetPerfilTitle" aria-hidden="true">
            <span class="mobile-sheet__grabber" aria-hidden="true"></span>
            <div class="mobile-sheet__head">
                <span class="mobile-sheet__title" id="sheetPerfilTitle">Perfil</span>
                <button type="button" class="mobile-sheet__close" data-sheet-close aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div class="mobile-sheet__body">
                <div class="msheet-user">
                    <span class="msheet-user__avatar">{{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}</span>
                    <span class="msheet-user__meta">
                        <strong>{{ $user?->name ?? 'Usuario' }}</strong>
                        <span>{{ $user?->email ?? 'sin-correo' }}</span>
                        <span class="msheet-user__role">{{ $currentUserRoleName ?? 'Sin rol' }}</span>
                    </span>
                </div>
                <div class="msheet-divider"></div>
                @if (! empty($isCompanyCreator))
                    <a href="{{ route('empresa.preferencias') }}" class="msheet-link {{ request()->routeIs('empresa.*') ? 'is-active' : '' }}">
                        <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54A.49.49 0 0 0 13.93 1h-3.86c-.24 0-.44.17-.47.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.32 7.47c-.11.2-.06.47.12.61l2.03 1.58c-.04.31-.07.65-.07.94s.03.63.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.49.38 1.03.7 1.62.94l.36 2.54c.03.24.23.41.47.41h3.86c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.12-.56 1.62-.94l2.39.96c.22.07.47 0 .59-.22l1.92-3.32c.11-.2.06-.47-.12-.61l-2.01-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg>
                        <span>Preferencias</span>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="msheet-link msheet-logout">
                        <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                        <span>Cerrar sesion</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <x-dialog />

    {{-- Bloqueador global de pantalla durante guardados --}}
    <div class="ui-blocker" id="uiBlocker" role="alert" aria-busy="true" aria-hidden="true">
        <div class="ui-blocker__box">
            <span class="ui-blocker__spinner" aria-hidden="true"></span>
            <span class="ui-blocker__text" id="uiBlockerText">Guardando&hellip;</span>
        </div>
    </div>

    <script>
        $(function () {
            var $body = $('body');
            var $sidebar = $('#sidebar');
            var $overlay = $('#sidebarOverlay');

            function openSidebar() {
                $sidebar.addClass('is-open');
                $overlay.addClass('is-active');
                $body.addClass('sidebar-open');
                $('#mobileToggle').attr('aria-expanded', 'true');
            }

            function closeSidebar() {
                $sidebar.removeClass('is-open');
                $overlay.removeClass('is-active');
                $body.removeClass('sidebar-open');
                $('#mobileToggle').attr('aria-expanded', 'false');
            }

            $('#mobileToggle').on('click', function () {
                if ($sidebar.hasClass('is-open')) {
                    closeSidebar();
                    return;
                }

                openSidebar();
            });

            $overlay.on('click', closeSidebar);

            $('.sidebar-module-button').on('click', function () {
                var $button = $(this);
                var $module = $button.closest('.sidebar-module');

                $module.toggleClass('is-open');
                $button.attr('aria-expanded', $module.hasClass('is-open') ? 'true' : 'false');
            });

            $('#sidebarSearch').on('input', function () {
                var query = $(this).val().toLowerCase().trim();

                $('.sidebar-item, .sidebar-module').show();

                if (!query) {
                    return;
                }

                $('.sidebar-module').each(function () {
                    var $module = $(this);
                    var moduleMatches = $module.find('.sidebar-module-button span').text().toLowerCase().indexOf(query) !== -1;
                    var hasVisibleItem = false;

                    $module.find('.sidebar-module-items .sidebar-item').each(function () {
                        var $item = $(this);
                        var itemMatches = $item.text().toLowerCase().indexOf(query) !== -1;
                        $item.toggle(itemMatches || moduleMatches);
                        hasVisibleItem = hasVisibleItem || itemMatches;
                    });

                    $module.toggle(moduleMatches || hasVisibleItem);
                    $module.toggleClass('is-open', moduleMatches || hasVisibleItem);
                });

                $('.sidebar-nav > .sidebar-item').each(function () {
                    var $item = $(this);
                    $item.toggle($item.text().toLowerCase().indexOf(query) !== -1);
                });
            });

            $('.sidebar a').on('click', function () {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });

            // Atajo Ctrl/⌘ + K para enfocar el buscador de módulos
            $(document).on('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    if (window.innerWidth <= 1024) {
                        openSidebar();
                    }
                    $('#sidebarSearch').trigger('focus');
                }
            });
        });
    </script>
    <script>
        // Navegación móvil: barra inferior + bottom sheets
        $(function () {
            var $body = $('body');
            var $backdrop = $('#sheetBackdrop');
            var lastFocused = null;

            function openSheet(id) {
                var $sheet = $('#' + id);
                if (!$sheet.length) return;
                lastFocused = document.activeElement;
                $backdrop.addClass('is-active');
                $sheet.addClass('is-open').attr('aria-hidden', 'false');
                $body.addClass('sheet-open');
                $('.mobile-nav__item[data-sheet-open]').removeClass('is-active');
                $('.mobile-nav__item[data-sheet-open="' + id + '"]').addClass('is-active');
                if (id === 'sheetBuscar') {
                    setTimeout(function () { $('#mobileSearchInput').trigger('focus'); }, 120);
                }
            }

            function closeSheets() {
                if (!$('.mobile-sheet.is-open').length) return;
                $('.mobile-sheet.is-open').removeClass('is-open').attr('aria-hidden', 'true');
                $backdrop.removeClass('is-active');
                $body.removeClass('sheet-open');
                $('.mobile-nav__item[data-sheet-open]').removeClass('is-active');
                if (lastFocused && typeof lastFocused.focus === 'function') {
                    lastFocused.focus();
                }
                lastFocused = null;
            }

            $('[data-sheet-open]').on('click', function () {
                var id = $(this).attr('data-sheet-open');
                var wasOpen = $('#' + id).hasClass('is-open');
                closeSheets();
                if (!wasOpen) {
                    openSheet(id);
                }
            });

            $('[data-sheet-close]').on('click', closeSheets);
            $backdrop.on('click', closeSheets);

            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeSheets();
                }
            });

            // Acordeón de módulos dentro del sheet
            $('.msheet-module-btn').on('click', function () {
                var $btn = $(this);
                var $module = $btn.closest('.msheet-module');
                $module.toggleClass('is-open');
                $btn.attr('aria-expanded', $module.hasClass('is-open') ? 'true' : 'false');
            });

            // Cerrar el sheet al tocar un enlace de navegación
            $('.mobile-sheet').on('click', '.msheet-link[href]', function () {
                closeSheets();
            });

            // Filtro en vivo del buscador móvil
            $('#mobileSearchInput').on('input', function () {
                var query = $(this).val().toLowerCase().trim();
                var visible = 0;

                $('#mobileSearchResults .msheet-link').each(function () {
                    var $item = $(this);
                    var haystack = $item.attr('data-search') || '';
                    var match = !query || haystack.indexOf(query) !== -1;
                    $item.toggle(match);
                    if (match) {
                        visible++;
                    }
                });

                $('#mobileSearchEmpty').toggle(visible === 0);
            });
        });
    </script>
    <script>
        window.notify = window.notify || function (type, title, message, options) {
            options = options || {};

            if (typeof window.iziToast !== 'undefined') {
                var method = typeof window.iziToast[type] === 'function' ? type : 'info';

                window.iziToast[method]({
                    title: title,
                    message: message,
                    position: 'topRight',
                    timeout: 3500,
                    ...options
                });

                return;
            }

            console[type === 'error' ? 'error' : 'log']((title || 'Notificacion') + ': ' + (message || ''));
        };

        @if (session('status'))
            window.notify('success', 'Operacion completada', @json(session('status')));
        @endif

        @if (session('success'))
            window.notify('success', 'Operacion completada', @json(session('success')));
        @endif

        @if (session('warning'))
            window.notify('warning', 'Atencion', @json(session('warning')));
        @endif

        @if (session('error'))
            window.notify('error', 'Error', @json(session('error')));
        @endif

        window.formUtils = window.formUtils || {
            clearErrors: function (target) {
                var $target = target && target.jquery ? target : $(target || document);
                $target.find('[data-error-for]').text('');
            },
            setErrors: function (errors, target) {
                var $target = target && target.jquery ? target : $(target || document);

                $.each(errors || {}, function (field, messages) {
                    var firstMessage = Array.isArray(messages) ? (messages[0] || '') : (messages || '');
                    $target.find('[data-error-for="' + field + '"]').text(firstMessage);
                });
            },
            setLoadingState: function (isLoading, target) {
                var $target = target && target.jquery ? target : $(target);

                $target.prop('disabled', isLoading);
                $target.toggleClass('opacity-70', isLoading);
                $target.attr('aria-busy', isLoading ? 'true' : 'false');
            }
        };

        window.listUtils = window.listUtils || {};

        window.listUtils.createStateHtml = window.listUtils.createStateHtml || function (config) {
            config = config || {};

            var entity = config.entity || 'registros';
            var colSpan = config.colSpan || 1;
            var loadingText = config.loadingText || ('Cargando ' + entity + '...');
            var emptyText = config.emptyText || ('No se encontraron ' + entity + '.');

            return {
                loadingRow: '<tr><td colspan="' + colSpan + '" class="list-loading-row">' + loadingText + '</td></tr>',
                emptyRow: '<tr><td colspan="' + colSpan + '" class="list-empty-row">' + emptyText + '</td></tr>',
                loadingCards: '<div class="list-loading">' + loadingText + '</div>',
                emptyCards: '<div class="list-empty">' + emptyText + '</div>'
            };
        };

        window.listUtils.bindFilterControls = window.listUtils.bindFilterControls || function (config) {
            config = config || {};

            var $searchInput = config.searchInput && config.searchInput.jquery ? config.searchInput : $(config.searchInput || []);
            var $perPageInput = config.perPageInput && config.perPageInput.jquery ? config.perPageInput : $(config.perPageInput || []);
            var $pagination = config.pagination && config.pagination.jquery ? config.pagination : $(config.pagination || []);
            var debounceMs = Number(config.debounceMs) || 350;
            var loadPage = typeof config.loadPage === 'function' ? config.loadPage : function () {};
            var getCurrentPage = typeof config.getCurrentPage === 'function' ? config.getCurrentPage : function () { return null; };
            var namespace = '.listControls' + Date.now() + Math.floor(Math.random() * 1000);
            var debounceTimer = null;

            if ($searchInput.length) {
                $searchInput.on('input' + namespace, function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        loadPage(1);
                    }, debounceMs);
                });
            }

            if ($perPageInput.length) {
                $perPageInput.on('change' + namespace, function () {
                    loadPage(1);
                });
            }

            if ($pagination.length) {
                $pagination.on('click' + namespace, '[data-page]', function () {
                    var nextPage = Number($(this).data('page'));

                    if (!nextPage || nextPage === getCurrentPage()) {
                        return;
                    }

                    loadPage(nextPage);
                });
            }

            return {
                destroy: function () {
                    clearTimeout(debounceTimer);

                    if ($searchInput.length) {
                        $searchInput.off(namespace);
                    }

                    if ($perPageInput.length) {
                        $perPageInput.off(namespace);
                    }

                    if ($pagination.length) {
                        $pagination.off(namespace);
                    }
                }
            };
        };

        window.utilidadesFormulario = window.utilidadesFormulario || {
            limpiarErrores: function (target) {
                window.formUtils.clearErrors(target);
            },
            mostrarErrores: function (errors, target) {
                window.formUtils.setErrors(errors, target);
            },
            cambiarEstadoCarga: function (isLoading, target) {
                window.formUtils.setLoadingState(isLoading, target);
            },
            cerrarModal: function (target) {
                var $target = target && target.jquery ? target : $(target || []);

                if (!$target.length) {
                    return;
                }

                $target.removeClass('is-open').attr('aria-hidden', 'true');
            }
        };

        window.utilidadesListado = window.utilidadesListado || {
            crearEstadoHtml: function (config) {
                return window.listUtils.createStateHtml(config);
            },
            vincularControlesFiltros: function (config) {
                return window.listUtils.bindFilterControls(config);
            }
        };

        window.utilidadesSelect = window.utilidadesSelect || {
            cargarOpciones: function (config) {
                config = config || {};

                var $select = config.select && config.select.jquery ? config.select : $(config.select || []);
                var placeholder = config.placeholder || 'Selecciona una opcion';
                var textoCarga = config.textoCarga || 'Cargando opciones...';
                var textoError = config.textoError || 'No se pudieron cargar las opciones';
                var textoVacio = config.textoVacio || placeholder;
                var valorSeleccionado = config.selected;

                if (!$select.length) {
                    return $.Deferred().reject().promise();
                }

                if (valorSeleccionado === undefined || valorSeleccionado === null || valorSeleccionado === '') {
                    valorSeleccionado = $select.data('selected') || $select.val() || '';
                }

                $select.prop('disabled', true).empty().append(
                    $('<option>', {
                        value: '',
                        text: textoCarga,
                    })
                );

                return $.ajax({
                    url: config.url,
                    method: 'GET',
                    data: config.data || {},
                }).done(function (response) {
                    var opciones = Array.isArray(response.opciones) ? response.opciones : [];

                    $select.empty().append(
                        $('<option>', {
                            value: '',
                            text: opciones.length ? placeholder : textoVacio,
                        })
                    );

                    $.each(opciones, function (_, opcion) {
                        var valor = opcion.valor ?? opcion.value ?? opcion.id ?? '';
                        var texto = opcion.texto ?? opcion.label ?? opcion.nombre ?? '';
                        var $option = $('<option>', {
                            value: valor,
                            text: texto,
                        });

                        if (String(valor) === String(valorSeleccionado)) {
                            $option.prop('selected', true);
                        }

                        $select.append($option);
                    });

                    if (valorSeleccionado !== '') {
                        $select.val(String(valorSeleccionado));
                    }
                }).fail(function () {
                    $select.empty().append(
                        $('<option>', {
                            value: '',
                            text: textoError,
                        })
                    );
                }).always(function () {
                    $select.prop('disabled', false);
                });
            }
        };
    </script>
    <script>
        // Bloqueador global de pantalla durante guardados (spinner + bloqueo de interacción)
        (function () {
            var blocker = document.getElementById('uiBlocker');
            var textEl = document.getElementById('uiBlockerText');

            window.uiBlocker = {
                show: function (texto) {
                    if (!blocker) return;
                    if (textEl && texto) { textEl.textContent = texto; }
                    blocker.classList.add('is-active');
                    blocker.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('sheet-open');
                },
                hide: function () {
                    if (!blocker) return;
                    blocker.classList.remove('is-active');
                    blocker.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('sheet-open');
                },
            };

            // Tipo A: formularios POST que recargan. El overlay se muestra al enviar y
            // desaparece solo cuando carga la pagina destino.
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (form && form.matches && form.matches('[data-block-on-submit]')) {
                    window.uiBlocker.show('Guardando…');
                }
            }, true);

            // Evita que el loader quede pegado al volver con el boton "atras" (bfcache).
            window.addEventListener('pageshow', function () { window.uiBlocker.hide(); });
        })();
    </script>
    @yield('scripts')

    {{-- Captura rápida global (FAB) — solo si el usuario puede crear movimientos --}}
    @auth
        @if (in_array('finanzas.movimientos.crear', $currentUserPermissionCodes ?? [], true))
            @include('partials.quick-capture')
        @endif
    @endauth

    @livewireScripts
</body>
</html>
