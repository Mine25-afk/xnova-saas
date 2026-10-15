@php
    $fechaMinima = now()->toDateString();
    $selectedMode = old('auth_mode', 'guest');
    $selectedCollaboratorDni = old('colaborador_dni', optional($singleCollaborator)->dni);
    $selectedSucursalId = old('sucursal_Id', optional($singleCollaborator)->sucursal_Id);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservacion por Link - {{ $empresa->nombre }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        :root {
            --bg-1: #f4faff;
            --bg-2: #eef8f2;
            --ink: #0f172a;
            --muted: #475569;
            --border: rgba(148, 163, 184, 0.32);
            --primary: #0284c7;
            --primary-strong: #0369a1;
            --accent: #0f766e;
            --white: #ffffff;
            --danger: #b91c1c;
            --danger-soft: rgba(239, 68, 68, 0.12);
            --ok: #047857;
            --ok-soft: rgba(16, 185, 129, 0.12);
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            font-family: Manrope, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 10%, rgba(2, 132, 199, 0.2), transparent 30%),
                radial-gradient(circle at 92% 90%, rgba(15, 118, 110, 0.16), transparent 28%),
                linear-gradient(140deg, var(--bg-1), var(--bg-2));
            padding: 22px 14px 30px;
        }

        .shell {
            width: min(100%, 1140px);
            margin: 0 auto;
            display: grid;
            gap: 20px;
        }

        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            padding: 28px;
            color: #fff;
            background: linear-gradient(115deg, #0b3f8a 0%, #0369a1 52%, #0f766e 100%);
            box-shadow: 0 22px 60px rgba(11, 63, 138, 0.34);
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            right: -80px;
            bottom: -130px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.26), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .hero small {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.74rem;
        }

        .hero h1 {
            margin: 12px 0 8px;
            font: 800 clamp(1.85rem, 3.8vw, 3rem)/1.06 Sora, sans-serif;
        }

        .hero p {
            margin: 0;
            max-width: 760px;
            line-height: 1.65;
            opacity: 0.94;
        }

        .wizard {
            border: 1px solid var(--border);
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 16px 42px rgba(15, 23, 42, 0.12);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .wizard-top {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: thin;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
        }

        .step-dot {
            min-width: 150px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 14px;
            padding: 9px 10px;
            display: grid;
            gap: 4px;
            background: #fff;
            opacity: 0.62;
            transition: all 0.25s ease;
        }

        .step-dot.is-active {
            opacity: 1;
            border-color: rgba(2, 132, 199, 0.55);
            box-shadow: 0 10px 20px rgba(2, 132, 199, 0.16);
            transform: translateY(-1px);
        }

        .step-dot b {
            font-size: 0.77rem;
            color: #0369a1;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .step-dot span {
            font-weight: 700;
            font-size: 0.85rem;
            color: #0f172a;
            line-height: 1.3;
        }

        .wizard-body {
            padding: 18px;
            display: grid;
            gap: 14px;
        }

        .alert {
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.9rem;
            line-height: 1.55;
            border: 1px solid transparent;
        }

        .alert-success {
            color: var(--ok);
            background: var(--ok-soft);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            color: var(--danger);
            background: var(--danger-soft);
            border-color: rgba(239, 68, 68, 0.24);
        }

        .step-panel {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 20px;
            background: #fff;
            padding: 18px;
            display: none;
            gap: 14px;
            animation: panelIn 0.3s ease;
        }

        .step-panel.is-active {
            display: grid;
        }

        @keyframes panelIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .step-panel h2 {
            margin: 0;
            font: 700 1.04rem/1.2 Sora, sans-serif;
        }

        .step-panel p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
            font-size: 0.92rem;
        }

        .auth-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .auth-card {
            border: 1px solid rgba(148, 163, 184, 0.34);
            border-radius: 14px;
            background: #f8fbff;
            padding: 13px;
            display: grid;
            gap: 7px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .auth-card.is-active {
            border-color: rgba(2, 132, 199, 0.6);
            background: rgba(2, 132, 199, 0.08);
            transform: translateY(-1px);
        }

        .auth-card h3 {
            margin: 0;
            font: 700 0.93rem/1.2 Sora, sans-serif;
        }

        .auth-card p {
            margin: 0;
            font-size: 0.84rem;
        }

        .mode-panel {
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 14px;
            background: #f8fbff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .mode-panel[hidden] {
            display: none;
        }

        .field-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .label {
            font-size: 0.83rem;
            font-weight: 700;
            color: #334155;
        }

        .input,
        .select,
        .textarea,
        .readonly-box {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 12px;
            background: #fff;
            color: #0f172a;
            font: inherit;
            padding: 11px 12px;
        }

        .readonly-box {
            background: #f8fafc;
            font-weight: 600;
        }

        .input:focus,
        .select:focus,
        .textarea:focus {
            outline: none;
            border-color: rgba(2, 132, 199, 0.7);
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.11);
        }

        .textarea {
            min-height: 100px;
            resize: vertical;
        }

        .error {
            min-height: 18px;
            font-size: 0.77rem;
            font-weight: 700;
            color: var(--danger);
        }

        .product-preview,
        .confirm-box {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 14px;
            background: #f8fbff;
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .product-top {
            display: grid;
            grid-template-columns: 76px minmax(0, 1fr);
            gap: 10px;
        }

        .product-media {
            width: 76px;
            height: 76px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: linear-gradient(120deg, #dbeafe, #cffafe);
            overflow: hidden;
            display: grid;
            place-items: center;
            font: 800 1.35rem/1 Sora, sans-serif;
            color: #0f766e;
        }

        .product-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .product-name {
            margin: 0;
            font: 700 0.95rem/1.3 Sora, sans-serif;
        }

        .product-price {
            margin-top: 5px;
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.12);
            color: #0f766e;
            font-size: 0.77rem;
            font-weight: 800;
        }

        .product-description {
            margin: 0;
            color: var(--muted);
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .confirm-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .confirm-item {
            border: 1px solid rgba(148, 163, 184, 0.26);
            border-radius: 11px;
            padding: 8px 10px;
            background: #fff;
            display: grid;
            gap: 3px;
        }

        .confirm-item b {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }

        .confirm-item span {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.9rem;
        }

        .wizard-actions {
            border-top: 1px solid rgba(148, 163, 184, 0.28);
            margin-top: 2px;
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: #fff;
            color: #0f172a;
            border-radius: 12px;
            min-height: 42px;
            padding: 0 14px;
            font: 700 0.88rem/1 Sora, sans-serif;
            cursor: pointer;
        }

        .btn-primary {
            border: none;
            color: #fff;
            background: linear-gradient(120deg, var(--primary), var(--accent));
            box-shadow: 0 10px 24px rgba(2, 132, 199, 0.23);
        }

        @media (max-width: 860px) {
            .auth-grid,
            .field-grid,
            .confirm-grid {
                grid-template-columns: 1fr;
            }

            .hero,
            .wizard-body,
            .step-panel {
                padding: 16px;
            }

            .wizard-top {
                padding: 12px;
            }

            .step-dot {
                min-width: 136px;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <section class="hero">
            <small>Reservacion por Link</small>
            <h1>{{ $empresa->nombre }}</h1>
            <p>Flujo guiado de 5 pasos: inicia sesion o invitado, elige servicio, colaborador, horario y confirma por correo.</p>
        </section>

        <section class="wizard">
            <div class="wizard-top">
                <div class="step-dot is-active" data-dot="1"><b>Paso 1</b><span>Iniciar sesion o invitado</span></div>
                <div class="step-dot" data-dot="2"><b>Paso 2</b><span>Elegir Servicio - Producto</span></div>
                <div class="step-dot" data-dot="3"><b>Paso 3</b><span>Elegir Colaborador</span></div>
                <div class="step-dot" data-dot="4"><b>Paso 4</b><span>Elegir Hora Disponible</span></div>
                <div class="step-dot" data-dot="5"><b>Paso 5</b><span>Recibir Confirmacion - Correo</span></div>
            </div>

            <form method="POST" action="{{ route('reservaciones.public.store', $empresa->reservacion_uid) }}" id="reservationForm">
                @csrf
                <input type="hidden" name="auth_mode" id="auth_mode" value="{{ $selectedMode }}">
                <input type="hidden" name="sucursal_Id" id="sucursal_Id" value="{{ $selectedSucursalId }}">

                <div class="wizard-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-error">Revisa los campos marcados para continuar.</div>
                    @endif

                    <section class="step-panel is-active" data-step="1">
                        <h2>1) Iniciar sesion o invitado</h2>
                        <p>Este es el paso principal. Google usa tu correo para ubicar o crear cliente. Invitado usa cliente general y limita 1 cita diaria por sesion/IP.</p>

                        <div class="auth-grid" id="authModeCards">
                            <button type="button" class="auth-card {{ $selectedMode === 'google' ? 'is-active' : '' }}" data-mode="google">
                                <h3>Continuar con Google</h3>
                                <p>Recupera datos por correo y reutiliza historial cuando ya existe.</p>
                            </button>
                            <button type="button" class="auth-card {{ $selectedMode === 'guest' ? 'is-active' : '' }}" data-mode="guest">
                                <h3>Continuar como invitado</h3>
                                <p>Flujo rapido con cliente general de la empresa.</p>
                            </button>
                        </div>

                        <div class="error">{{ $errors->first('auth_mode') }}</div>

                        <div class="mode-panel" id="googlePanel" @if ($selectedMode !== 'google') hidden @endif>
                            <div id="googleSignInButton"></div>
                            <p style="margin:0;color:var(--muted);font-size:.86rem;">Si no aparece el boton, puedes escribir manualmente tus datos de Google.</p>

                            <div class="field-grid">
                                <div class="field">
                                    <label class="label" for="google_name">Nombre en Google</label>
                                    <input class="input" id="google_name" name="google_name" type="text" maxlength="150" value="{{ old('google_name') }}" placeholder="Ejemplo: Ana Martinez">
                                    <div class="error">{{ $errors->first('google_name') }}</div>
                                </div>

                                <div class="field">
                                    <label class="label" for="google_email">Correo Google</label>
                                    <input class="input" id="google_email" name="google_email" type="email" maxlength="150" value="{{ old('google_email') }}" placeholder="tu_correo@gmail.com">
                                    <div class="error">{{ $errors->first('google_email') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mode-panel" id="guestPanel" @if ($selectedMode !== 'guest') hidden @endif>
                            <p style="margin:0;color:var(--muted);font-size:.9rem;">Como invitado, se usa el cliente general de {{ $empresa->nombre }}.</p>
                        </div>
                    </section>

                    <section class="step-panel" data-step="2">
                        <h2>2) Elegir Servicio - Producto</h2>
                        <p>Selecciona el servicio principal y revisa su detalle.</p>

                        <div class="field-grid">
                            <div class="field field-full">
                                <label class="label" for="producto_id">Servicio / Producto</label>
                                <select class="select" id="producto_id" name="producto_id" required>
                                    <option value="">Selecciona un servicio</option>
                                    @foreach ($productos as $producto)
                                        <option
                                            value="{{ $producto->id }}"
                                            data-name="{{ $producto->nombre }}"
                                            data-description="{{ $producto->descripcion }}"
                                            data-price="{{ number_format((float) $producto->precio, 2) }}"
                                            data-image-url="{{ $producto->imagen_url }}"
                                            @selected((string) old('producto_id') === (string) $producto->id)
                                        >
                                            {{ $producto->nombre }} - {{ number_format((float) $producto->precio, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="error">{{ $errors->first('producto_id') }}</div>
                            </div>

                            <div class="field field-full">
                                <div class="product-preview">
                                    <div id="productoResumen" style="margin:0;color:var(--muted);font-size:.9rem;">Selecciona un servicio para visualizar su detalle.</div>
                                    <div class="product-top" id="productoCard" hidden>
                                        <div class="product-media">
                                            <span id="productoMediaPlaceholder">?</span>
                                            <img id="productoImagen" src="" alt="" hidden>
                                        </div>
                                        <div>
                                            <h3 class="product-name" id="productoTitulo"></h3>
                                            <div class="product-price" id="productoPrecio"></div>
                                            <p class="product-description" id="productoDescripcion"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="step-panel" data-step="3">
                        <h2>3) Elegir Colaborador</h2>
                        <p>Selecciona quien te atendera. La sucursal se asigna automaticamente.</p>

                        <div class="field-grid">
                            <div class="field">
                                <label class="label" for="colaborador_dni">Colaborador</label>
                                <select class="select" id="colaborador_dni" name="colaborador_dni" required>
                                    <option value="">Selecciona un colaborador</option>
                                    @foreach ($colaboradores as $colaborador)
                                        <option
                                            value="{{ $colaborador->dni }}"
                                            data-sucursal-id="{{ $colaborador->sucursal_Id }}"
                                            data-sucursal-name="{{ $colaborador->sucursal?->nombre }}"
                                            @selected((string) $selectedCollaboratorDni === (string) $colaborador->dni)
                                        >
                                            {{ trim(($colaborador->socio?->nombres ?? '').' '.($colaborador->socio?->apellidos ?? '')) ?: $colaborador->dni }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="error">{{ $errors->first('colaborador_dni') }}</div>
                            </div>

                            <div class="field">
                                <label class="label">Sucursal asignada</label>
                                <div class="readonly-box" id="selectedSucursalLabel">Sin sucursal</div>
                                <div class="error">{{ $errors->first('sucursal_Id') }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="step-panel" data-step="4">
                        <h2>4) Elegir Hora Disponible</h2>
                        <p>Define fecha y hora para programar tu cita.</p>

                        <div class="field-grid">
                            <div class="field">
                                <label class="label" for="fecha">Fecha</label>
                                <input class="input" id="fecha" name="fecha" type="date" min="{{ $fechaMinima }}" value="{{ old('fecha', $fechaMinima) }}" required>
                                <div class="error">{{ $errors->first('fecha') }}</div>
                            </div>

                            <div class="field">
                                <label class="label" for="hora_inicio">Hora de inicio</label>
                                <input class="input" id="hora_inicio" name="hora_inicio" type="time" value="{{ old('hora_inicio', '09:00') }}" required>
                                <div class="error">{{ $errors->first('hora_inicio') }}</div>
                            </div>

                            <div class="field">
                                <label class="label" for="hora_salida">Hora de salida</label>
                                <input class="input" id="hora_salida" name="hora_salida" type="time" value="{{ old('hora_salida', '10:00') }}" required>
                                <div class="error">{{ $errors->first('hora_salida') }}</div>
                            </div>

                            <div class="field field-full">
                                <label class="label" for="comentario">Comentario (opcional)</label>
                                <textarea class="textarea" id="comentario" name="comentario" placeholder="Agrega un comentario opcional.">{{ old('comentario') }}</textarea>
                                <div class="error">{{ $errors->first('comentario') }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="step-panel" data-step="5">
                        <h2>5) Recibir la Confirmacion - Correo</h2>
                        <p id="confirmationCopy">Te enviaremos confirmacion segun el modo seleccionado.</p>

                        <div class="confirm-box">
                            <div class="field field-full">
                                <label class="label">Correo de confirmacion</label>
                                <div class="readonly-box" id="confirmationTarget">Pendiente de definir segun modo de acceso</div>
                            </div>

                            <div class="confirm-grid">
                                <div class="confirm-item"><b>Modo</b><span id="summaryMode">-</span></div>
                                <div class="confirm-item"><b>Servicio</b><span id="summaryProduct">-</span></div>
                                <div class="confirm-item"><b>Colaborador</b><span id="summaryCollaborator">-</span></div>
                                <div class="confirm-item"><b>Sucursal</b><span id="summaryBranch">-</span></div>
                                <div class="confirm-item"><b>Fecha</b><span id="summaryDate">-</span></div>
                                <div class="confirm-item"><b>Horario</b><span id="summaryTime">-</span></div>
                            </div>
                        </div>
                    </section>

                    <div class="wizard-actions">
                        <button type="button" class="btn" id="prevStepBtn">Anterior</button>
                        <div style="display:flex;gap:10px;">
                            <button type="button" class="btn btn-primary" id="nextStepBtn">Siguiente</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn" hidden>Enviar reservacion</button>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <script>
        (function () {
            var selectedMode = @json($selectedMode);
            var googleClientId = @json($googleClientId);
            var firstErrorKey = @json($errors->keys()[0] ?? null);

            var form = document.getElementById('reservationForm');
            var authModeInput = document.getElementById('auth_mode');
            var authCards = Array.from(document.querySelectorAll('#authModeCards .auth-card'));
            var googlePanel = document.getElementById('googlePanel');
            var guestPanel = document.getElementById('guestPanel');
            var googleNameInput = document.getElementById('google_name');
            var googleEmailInput = document.getElementById('google_email');

            var collaboratorSelect = document.getElementById('colaborador_dni');
            var sucursalInput = document.getElementById('sucursal_Id');
            var sucursalLabel = document.getElementById('selectedSucursalLabel');

            var productSelect = document.getElementById('producto_id');
            var productSummary = document.getElementById('productoResumen');
            var productCard = document.getElementById('productoCard');
            var productTitle = document.getElementById('productoTitulo');
            var productPrice = document.getElementById('productoPrecio');
            var productDescription = document.getElementById('productoDescripcion');
            var productImage = document.getElementById('productoImagen');
            var productMediaPlaceholder = document.getElementById('productoMediaPlaceholder');

            var confirmationTarget = document.getElementById('confirmationTarget');
            var confirmationCopy = document.getElementById('confirmationCopy');
            var summaryMode = document.getElementById('summaryMode');
            var summaryProduct = document.getElementById('summaryProduct');
            var summaryCollaborator = document.getElementById('summaryCollaborator');
            var summaryBranch = document.getElementById('summaryBranch');
            var summaryDate = document.getElementById('summaryDate');
            var summaryTime = document.getElementById('summaryTime');

            var stepDots = Array.from(document.querySelectorAll('.step-dot'));
            var panels = Array.from(document.querySelectorAll('.step-panel'));
            var prevBtn = document.getElementById('prevStepBtn');
            var nextBtn = document.getElementById('nextStepBtn');
            var submitBtn = document.getElementById('submitBtn');

            function mapErrorToStep(errorKey) {
                if (!errorKey) return 1;
                if (['auth_mode', 'google_name', 'google_email'].indexOf(errorKey) !== -1) return 1;
                if (['producto_id'].indexOf(errorKey) !== -1) return 2;
                if (['colaborador_dni', 'sucursal_Id'].indexOf(errorKey) !== -1) return 3;
                if (['fecha', 'hora_inicio', 'hora_salida', 'comentario'].indexOf(errorKey) !== -1) return 4;
                return 1;
            }

            var currentStep = mapErrorToStep(firstErrorKey);

            function showStep(step) {
                currentStep = Math.min(5, Math.max(1, step));

                panels.forEach(function (panel) {
                    panel.classList.toggle('is-active', Number(panel.getAttribute('data-step')) === currentStep);
                });

                stepDots.forEach(function (dot) {
                    dot.classList.toggle('is-active', Number(dot.getAttribute('data-dot')) === currentStep);
                });

                prevBtn.disabled = currentStep === 1;
                nextBtn.hidden = currentStep === 5;
                submitBtn.hidden = currentStep !== 5;

                if (currentStep === 5) {
                    updateFinalSummary();
                }
            }

            function setMode(mode) {
                selectedMode = mode === 'google' ? 'google' : 'guest';
                authModeInput.value = selectedMode;

                authCards.forEach(function (card) {
                    card.classList.toggle('is-active', card.getAttribute('data-mode') === selectedMode);
                });

                googlePanel.hidden = selectedMode !== 'google';
                guestPanel.hidden = selectedMode !== 'guest';

                var requiredGoogle = selectedMode === 'google';
                googleNameInput.required = requiredGoogle;
                googleEmailInput.required = requiredGoogle;

                updateConfirmationTarget();
            }

            authCards.forEach(function (card) {
                card.addEventListener('click', function () {
                    setMode(card.getAttribute('data-mode') || 'guest');
                });
            });

            function updateSucursalFromCollaborator() {
                var option = collaboratorSelect.options[collaboratorSelect.selectedIndex];

                if (!option || !option.value) {
                    sucursalInput.value = '';
                    sucursalLabel.textContent = 'Sin sucursal';
                    return;
                }

                sucursalInput.value = option.getAttribute('data-sucursal-id') || '';
                sucursalLabel.textContent = option.getAttribute('data-sucursal-name') || 'Sin sucursal';
            }

            function updateProductSummary() {
                var option = productSelect.options[productSelect.selectedIndex];

                if (!option || !option.value) {
                    productSummary.textContent = 'Selecciona un servicio para visualizar su detalle.';
                    productCard.hidden = true;
                    return;
                }

                var name = option.getAttribute('data-name') || 'Servicio';
                var description = option.getAttribute('data-description') || 'Sin descripcion registrada.';
                var price = option.getAttribute('data-price') || '0.00';
                var imageUrl = option.getAttribute('data-image-url') || '';

                productSummary.textContent = 'Verifica que este servicio sea el correcto antes de continuar.';
                productTitle.textContent = name;
                productPrice.textContent = 'Precio: ' + price;
                productDescription.textContent = description;
                productMediaPlaceholder.textContent = (name.charAt(0) || '?').toUpperCase();
                productCard.hidden = false;

                if (imageUrl) {
                    productImage.src = imageUrl;
                    productImage.alt = name;
                    productImage.hidden = false;
                    productMediaPlaceholder.hidden = true;
                } else {
                    productImage.hidden = true;
                    productImage.removeAttribute('src');
                    productMediaPlaceholder.hidden = false;
                }
            }

            function updateConfirmationTarget() {
                if (selectedMode === 'google') {
                    var email = (googleEmailInput.value || '').trim();
                    confirmationTarget.textContent = email || 'Escribe tu correo de Google para confirmar.';
                    confirmationCopy.textContent = 'Si el correo existe como cliente lo reutilizamos. Si no existe, se crea automaticamente para esta empresa.';
                } else {
                    confirmationTarget.textContent = 'Correo del cliente general de la empresa';
                    confirmationCopy.textContent = 'En invitado, se usa cliente general y se limita 1 cita por dia por sesion/IP.';
                }
            }

            function decodeJwtPayload(token) {
                try {
                    var base64 = token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/');
                    var payload = decodeURIComponent(atob(base64).split('').map(function (char) {
                        return '%' + ('00' + char.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));

                    return JSON.parse(payload);
                } catch (error) {
                    return null;
                }
            }

            function setupGoogleButton() {
                if (!googleClientId || !window.google || !window.google.accounts || !window.google.accounts.id) {
                    return;
                }

                var container = document.getElementById('googleSignInButton');

                if (!container) {
                    return;
                }

                window.google.accounts.id.initialize({
                    client_id: googleClientId,
                    callback: function (response) {
                        var payload = decodeJwtPayload(response.credential || '');

                        if (!payload) {
                            return;
                        }

                        if (payload.name) {
                            googleNameInput.value = payload.name;
                        }

                        if (payload.email) {
                            googleEmailInput.value = payload.email;
                        }

                        setMode('google');
                    },
                });

                window.google.accounts.id.renderButton(container, {
                    type: 'standard',
                    theme: 'outline',
                    shape: 'pill',
                    text: 'continue_with',
                    size: 'large',
                    logo_alignment: 'left',
                });
            }

            function validateStep(step) {
                if (step === 1) {
                    if (selectedMode === 'google') {
                        return googleNameInput.value.trim() !== '' && googleEmailInput.value.trim() !== '';
                    }
                    return true;
                }

                if (step === 2) {
                    return productSelect.value !== '';
                }

                if (step === 3) {
                    return collaboratorSelect.value !== '' && sucursalInput.value !== '';
                }

                if (step === 4) {
                    var fecha = document.getElementById('fecha').value;
                    var horaInicio = document.getElementById('hora_inicio').value;
                    var horaSalida = document.getElementById('hora_salida').value;

                    return fecha !== '' && horaInicio !== '' && horaSalida !== '' && horaSalida > horaInicio;
                }

                return true;
            }

            function updateFinalSummary() {
                var productOption = productSelect.options[productSelect.selectedIndex];
                var collaboratorOption = collaboratorSelect.options[collaboratorSelect.selectedIndex];

                summaryMode.textContent = selectedMode === 'google' ? 'Google' : 'Invitado';
                summaryProduct.textContent = (productOption && productOption.value) ? (productOption.getAttribute('data-name') || productOption.textContent.trim()) : 'Sin seleccionar';
                summaryCollaborator.textContent = (collaboratorOption && collaboratorOption.value) ? collaboratorOption.textContent.trim() : 'Sin seleccionar';
                summaryBranch.textContent = sucursalLabel.textContent || 'Sin sucursal';
                summaryDate.textContent = document.getElementById('fecha').value || 'Sin fecha';

                var horaInicio = document.getElementById('hora_inicio').value || '--:--';
                var horaSalida = document.getElementById('hora_salida').value || '--:--';
                summaryTime.textContent = horaInicio + ' - ' + horaSalida;
            }

            nextBtn.addEventListener('click', function () {
                if (!validateStep(currentStep)) {
                    return;
                }

                showStep(currentStep + 1);
            });

            prevBtn.addEventListener('click', function () {
                showStep(currentStep - 1);
            });

            form.addEventListener('submit', function () {
                updateFinalSummary();
            });

            collaboratorSelect.addEventListener('change', function () {
                updateSucursalFromCollaborator();
                updateFinalSummary();
            });

            productSelect.addEventListener('change', function () {
                updateProductSummary();
                updateFinalSummary();
            });

            googleEmailInput.addEventListener('input', function () {
                updateConfirmationTarget();
                updateFinalSummary();
            });

            document.getElementById('fecha').addEventListener('change', updateFinalSummary);
            document.getElementById('hora_inicio').addEventListener('change', updateFinalSummary);
            document.getElementById('hora_salida').addEventListener('change', updateFinalSummary);

            setMode(selectedMode);
            updateSucursalFromCollaborator();
            updateProductSummary();
            updateConfirmationTarget();
            updateFinalSummary();
            showStep(currentStep);

            if (document.readyState === 'complete') {
                setupGoogleButton();
            } else {
                window.addEventListener('load', setupGoogleButton, { once: true });
            }
        })();
    </script>

    @if (filled($googleClientId))
        <script src="https://accounts.google.com/gsi/client" async defer></script>
    @endif
</body>
</html>
