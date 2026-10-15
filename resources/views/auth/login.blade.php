<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión — xNova</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            background: linear-gradient(180deg, #f0f4ff 0%, #e8efff 50%, #dbe4ff 100%);
            overflow: hidden;
        }

        .skip-link {
            position: fixed;
            left: 16px;
            top: 16px;
            z-index: 1000;
            transform: translateY(-140%);
            padding: 10px 14px;
            border-radius: 12px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.18);
            transition: transform 0.2s ease;
        }

        .skip-link:focus {
            transform: translateY(0);
        }

        :focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.55);
            outline-offset: 3px;
        }

        .page-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* Blobs decorativos de fondo */
        .bg-blob {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(60px);
        }

        .bg-blob--1 {
            width: 600px;
            height: 600px;
            background: rgba(59, 130, 246, 0.07);
            top: -120px;
            right: -80px;
        }

        .bg-blob--2 {
            width: 380px;
            height: 380px;
            background: rgba(99, 102, 241, 0.05);
            bottom: -60px;
            left: 60px;
        }

        .navbar,
        .main-content,
        .footer {
            position: relative;
            z-index: 1;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 50px;
            gap: 25px;
            flex-shrink: 0;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            text-decoration: none;
        }

        .nav-brand-icon {
            width: 32px;
            height: 32px;
            background: #2563eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nav-brand-icon svg {
            width: 18px;
            height: 18px;
            fill: #fff;
        }

        .nav-brand-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.3px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-item {
            font-size: 0.95rem;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .nav-item:hover {
            color: #3b82f6;
        }

        .nav-item.active {
            color: #3b82f6;
            font-weight: 600;
        }

        .nav-item--static {
            cursor: default;
        }

        .nav-item svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }

        .btn-comprar {
            padding: 10px 28px;
            background: transparent;
            border: 2px solid #3b82f6;
            border-radius: 25px;
            color: #3b82f6;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-comprar:hover {
            background: #3b82f6;
            color: #fff;
        }

        .main-content {
            flex: 1;
            display: grid;
            grid-template-columns: 1.2fr auto 0.8fr;
            align-items: center;
            padding: 0 40px 0 80px;
            gap: 30px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .left-section {
            max-width: 600px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.15);
            border-radius: 30px;
            margin-bottom: 22px;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            background: #3b82f6;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .badge-text {
            color: #2563eb;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .main-title {
            font-size: 4rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.05;
            letter-spacing: -1.5px;
            margin-bottom: 22px;
        }

        .main-subtitle {
            font-size: 1.15rem;
            color: #64748b;
            line-height: 1.75;
            font-weight: 400;
            max-width: 420px;
        }

        .center-section {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .astronaut-image {
            max-width: 420px;
            max-height: 65vh;
            width: 100%;
            height: auto;
            object-fit: contain;
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .right-section {
            display: flex;
            justify-content: flex-end;
            padding-right: 20px;
        }

        .login-card {
            width: min(100%, 420px);
            padding: 36px 36px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.10), 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .login-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: #64748b;
            font-size: 0.98rem;
            line-height: 1.6;
            margin-bottom: 26px;
        }

        .google-auth-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            width: 100%;
            padding: 16px 24px;
            background: #fff;
            border: 1.5px solid #d1d5db;
            border-radius: 14px;
            color: #374151;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
        }

        .google-auth-link:hover {
            border-color: #2563eb;
            color: #1e40af;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.12);
        }

        .google-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
        }

        .google-label {
            white-space: nowrap;
        }

        .legal-copy {
            margin-top: 24px;
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.65;
        }

        .legal-copy a {
            color: #4361ee;
            text-decoration: none;
        }

        .legal-copy a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 18px;
            color: #dc2626;
            font-size: 0.85rem;
        }

        .mobile-astronaut-section {
            display: none;
            background: transparent;
            padding: 0;
            position: relative;
            overflow: visible;
            flex-shrink: 0;
        }

        .mobile-badge {
            display: none;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.15);
            border-radius: 30px;
            margin-bottom: 20px;
        }

        .mobile-badge .badge-text {
            color: #2563eb;
            font-size: 0.88rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .astronaut-head-image {
            width: 100%;
            max-width: 400px;
            height: auto;
            display: block;
            margin: 0 auto -17px;
            position: relative;
            z-index: 10;
        }

        .mobile-login-card {
            background: #fff;
            border-radius: 24px;
            padding: 50px 25px 30px;
            box-shadow: 0 -10px 40px rgba(68, 97, 242, 0.15);
            width: 100%;
        }

        .mobile-footer-text {
            display: none;
            text-align: center;
            color: #94a3b8;
            font-size: 0.75rem;
            margin-top: 25px;
        }

        .footer {
            padding: 15px 50px;
            text-align: left;
            flex-shrink: 0;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .footer-text {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        @media (max-width: 1300px) {
            .main-content {
                display: flex;
                flex-direction: column;
                padding: 30px 20px 0;
                gap: 0;
                flex: 1;
            }

            .left-section {
                display: none;
            }

            .center-section {
                display: none;
            }

            .right-section {
                display: none;
            }

            .mobile-astronaut-section {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                flex: 1;
                order: 1;
            }

            .mobile-badge {
                display: inline-flex;
            }

            .mobile-footer-text {
                display: block;
            }

            .main-title {
                font-size: 2.8rem;
            }

            .main-subtitle {
                font-size: 1.1rem;
            }

            .login-card {
                width: 100%;
                max-width: 360px;
            }

            .footer {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .astronaut-head-image {
                max-width: 320px;
            }

            .mobile-login-card {
                max-width: 380px;
            }
        }

        @media (max-width: 720px) {
            body {
                overflow-y: auto;
            }

            .page-container {
                height: auto;
                min-height: 100vh;
                overflow: visible;
            }

            .navbar {
                padding: 15px 20px;
                gap: 12px;
            }

            .astronaut-head-image {
                max-width: 280px;
            }

            .mobile-login-card {
                max-width: 100%;
            }

            .mobile-login-card .login-title {
                font-size: 1.3rem;
                margin-bottom: 10px;
            }

            .mobile-login-card .login-subtitle {
                font-size: 0.92rem;
                margin-bottom: 22px;
            }

            .mobile-login-card .google-auth-link {
                font-size: 1rem;
                padding: 17px 18px;
                gap: 12px;
            }

            .mobile-login-card .google-icon {
                width: 28px;
                height: 28px;
            }

            .mobile-login-card .legal-copy {
                margin-top: 22px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                gap: 10px;
            }

            .btn-comprar {
                padding: 8px 20px;
                font-size: 0.85rem;
            }

            .main-title {
                font-size: 1.75rem;
            }

            .badge {
                padding: 8px 18px;
            }

            .badge-text {
                font-size: 0.8rem;
            }

            .google-label {
                white-space: normal;
                text-align: center;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }

            .google-auth-link:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <a href="#contenido-principal" class="skip-link">Saltar al contenido</a>
    <div class="page-container">
        <div class="bg-blob bg-blob--1" aria-hidden="true"></div>
        <div class="bg-blob bg-blob--2" aria-hidden="true"></div>

        <nav class="navbar" aria-label="Navegacion de inicio de sesion">
            <a href="#" class="nav-brand" aria-label="xNova">
                <div class="nav-brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 3h3v7.5L14.5 3H18l-8 8.5L19 21h-3.5l-7-8-1.5 1.6V21H4V3z"/>
                    </svg>
                </div>
                <span class="nav-brand-name">xNova</span>
            </a>
            <div class="nav-right">
                <span class="nav-item nav-item--static" aria-label="Idioma actual: espanol">
                    Español
                    <svg viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 10l5 5 5-5z"/>
                    </svg>
                </span>
                <span class="nav-item nav-item--static active" aria-current="page">Inicio de sesión</span>
            </div>
        </nav>

        <main class="main-content" id="contenido-principal" tabindex="-1">
            <section class="mobile-astronaut-section">
                <div class="mobile-badge">
                    <span class="badge-dot"></span>
                    <span class="badge-text">xNova</span>
                </div>
                <img src="{{ asset('images/login/CabezaAstronauta.png') }}" alt="Astronauta xNova" class="astronaut-head-image">
                <div class="mobile-login-card">
                    <h2 class="login-title">Bienvenido de vuelta</h2>
                    <p class="login-subtitle">Inicia sesion para continuar</p>

                    @if ($errors->any())
                        <div class="error-message">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <a href="{{ route('google.redirect') }}" class="google-auth-link">
                        <svg class="google-icon" viewBox="0 0 48 48" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#FFC107" d="M43.61 20.08H42V20H24v8h11.3C33.65 32.66 29.19 36 24 36c-6.63 0-12-5.37-12-12S17.37 12 24 12c3.06 0 5.84 1.15 7.96 3.04l5.66-5.66C34.05 6.05 29.28 4 24 4C12.95 4 4 12.95 4 24s8.95 20 20 20s20-8.95 20-20c0-1.34-.14-2.65-.39-3.92Z"/>
                            <path fill="#FF3D00" d="M6.31 14.69l6.57 4.82C14.66 15.11 18.96 12 24 12c3.06 0 5.84 1.15 7.96 3.04l5.66-5.66C34.05 6.05 29.28 4 24 4C16.32 4 9.67 8.34 6.31 14.69Z"/>
                            <path fill="#4CAF50" d="M24 44c5.18 0 9.86-1.98 13.41-5.2l-6.19-5.24C29.15 35.1 26.7 36 24 36c-5.17 0-9.62-3.31-11.28-7.91l-6.52 5.02C9.52 39.56 16.19 44 24 44Z"/>
                            <path fill="#1976D2" d="M43.61 20.08H42V20H24v8h11.3c-.79 2.24-2.23 4.17-4.08 5.56l.01-.01l6.19 5.24C36.97 39.09 44 34 44 24c0-1.34-.14-2.65-.39-3.92Z"/>
                        </svg>
                        <span class="google-label">Continuar con Google</span>
                    </a>

                    <p class="legal-copy">
                        Al continuar, aceptas nuestros
                        <a href="#">Terminos de servicio</a>
                        y
                        <a href="#">Politica de privacidad</a>.
                    </p>
                    <p class="mobile-footer-text">&#169;2026 Desarrolladores xNova</p>
                </div>
            </section>

            <section class="left-section">
                <div class="badge">
                    <span class="badge-dot"></span>
                    <span class="badge-text">xNova</span>
                </div>
                <h1 class="main-title">Administra tu negocio</h1>
                <p class="main-subtitle">
                    Automatiza procesos, centraliza operaciones y accede a soluciones diseñadas para crecer con tu empresa.
                </p>
            </section>

            <section class="center-section">
                <img src="{{ asset('images/login/Astronauta.png') }}" alt="Astronauta xNova" class="astronaut-image">
            </section>

            <section class="right-section">
                <div class="login-card">
                    <h2 class="login-title">Bienvenido de vuelta</h2>
                    <p class="login-subtitle">Inicia sesion para continuar</p>

                    @if ($errors->any())
                        <div class="error-message">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <a href="{{ route('google.redirect') }}" class="google-auth-link">
                        <svg class="google-icon" viewBox="0 0 48 48" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#FFC107" d="M43.61 20.08H42V20H24v8h11.3C33.65 32.66 29.19 36 24 36c-6.63 0-12-5.37-12-12S17.37 12 24 12c3.06 0 5.84 1.15 7.96 3.04l5.66-5.66C34.05 6.05 29.28 4 24 4C12.95 4 4 12.95 4 24s8.95 20 20 20s20-8.95 20-20c0-1.34-.14-2.65-.39-3.92Z"/>
                            <path fill="#FF3D00" d="M6.31 14.69l6.57 4.82C14.66 15.11 18.96 12 24 12c3.06 0 5.84 1.15 7.96 3.04l5.66-5.66C34.05 6.05 29.28 4 24 4C16.32 4 9.67 8.34 6.31 14.69Z"/>
                            <path fill="#4CAF50" d="M24 44c5.18 0 9.86-1.98 13.41-5.2l-6.19-5.24C29.15 35.1 26.7 36 24 36c-5.17 0-9.62-3.31-11.28-7.91l-6.52 5.02C9.52 39.56 16.19 44 24 44Z"/>
                            <path fill="#1976D2" d="M43.61 20.08H42V20H24v8h11.3c-.79 2.24-2.23 4.17-4.08 5.56l.01-.01l6.19 5.24C36.97 39.09 44 34 44 24c0-1.34-.14-2.65-.39-3.92Z"/>
                        </svg>
                        <span class="google-label">Continuar con Google</span>
                    </a>

                    <p class="legal-copy">
                        Al continuar, aceptas nuestros
                        <a href="#">Terminos de servicio</a>
                        y
                        <a href="#">Politica de privacidad</a>.
                    </p>
                </div>
            </section>
        </main>

        <footer class="footer">
            <p class="footer-text">&#169;2026 Desarrolladores xNova</p>
        </footer>
    </div>
</body>
</html>
