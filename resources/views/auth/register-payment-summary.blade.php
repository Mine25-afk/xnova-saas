@php
    $currency = $pendingSetup['currency'] ?? 'USD';
    $currencySymbol = $currency === 'USD' ? '$' : $currency.' ';
    $googleEmail = $googleOnboarding['email'] ?? null;
    $googleName = $googleOnboarding['name'] ?? null;
    $isTrial = ($pendingSetup['checkout_type'] ?? null) === 'trial';
    $typeName = $pendingSetup['type_name'] ?? 'Clinica';
    $supportsBranches = (bool) ($pendingSetup['supports_branches'] ?? true);
    $requiresDefaultEntities = (bool) ($pendingSetup['requires_default_entities'] ?? true);
    $invoiceLabel = $isTrial ? 'Prueba de 7 dias' : 'Pago aprobado';
    $invoiceDateLabel = $isTrial ? 'Prueba hasta' : 'Referencia';
    $invoiceDateValue = $pendingSetup['reference'] ?? '-';

    if ($isTrial && filled($pendingSetup['period_end'] ?? null)) {
        try {
            $invoiceDateValue = \Illuminate\Support\Carbon::parse($pendingSetup['period_end'])->format('d/m/Y');
        } catch (\Throwable) {
            $invoiceDateValue = '-';
        }
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracion inicial - xNova</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --primary:#4461f2;
            --primary-soft:rgba(68,97,242,.12);
            --surface:#ffffff;
            --surface-soft:#f8fbff;
            --border:#dbe4f5;
            --text-primary:#1e293b;
            --text-secondary:#64748b;
            --success:#0f9f73;
            --success-soft:rgba(16,185,129,.12);
            --danger:#dc2626;
            --danger-soft:rgba(239,68,68,.10);
            --shadow:0 14px 34px rgba(68,97,242,.09);
        }
        body{
            min-height:100vh;
            font-family:'Poppins',sans-serif;
            background:linear-gradient(180deg,#f0f4ff 0%,#e8efff 50%,#dbe4ff 100%);
            color:var(--text-primary);
        }
        .topbar{
            display:flex;
            justify-content:flex-end;
            align-items:center;
            gap:28px;
            width:100%;
            padding:16px 42px 0 18px;
        }
        .topbar-link,
        .topbar-button{
            display:inline-flex;
            align-items:center;
            gap:6px;
            border:none;
            background:none;
            color:#64748b;
            text-decoration:none;
            font:inherit;
            font-size:1rem;
            font-weight:600;
            cursor:pointer;
            transition:color .2s ease;
        }
        .topbar-link:hover,
        .topbar-button:hover{
            color:var(--primary);
        }
        .topbar-link svg,
        .topbar-button svg,
        .mini-badge svg{
            width:13px;
            height:13px;
            stroke:currentColor;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
            fill:none;
            flex-shrink:0;
        }
        .topbar-button{
            color:var(--primary);
        }
        .page-shell{
            min-height:calc(100vh - 58px);
            max-width:760px;
            margin:0 auto;
            padding:12px 18px 24px;
            display:grid;
            align-content:center;
            gap:14px;
        }
        .page-head{
            display:grid;
            gap:8px;
            justify-items:center;
            text-align:center;
        }
        .page-head h1{
            font-size:clamp(1.55rem,3vw,2.35rem);
            line-height:1;
            font-weight:700;
            letter-spacing:-.04em;
        }
        .page-head p{
            max-width:620px;
            font-size:.92rem;
            line-height:1.7;
            color:var(--text-secondary);
        }
        .card{
            background:rgba(255,255,255,.96);
            border:1px solid rgba(255,255,255,.88);
            border-radius:24px;
            box-shadow:var(--shadow);
            padding:16px;
        }
        .card-inner{
            border-radius:20px;
            background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
            border:1px solid rgba(255,255,255,.95);
            box-shadow:inset 0 1px 0 rgba(255,255,255,.8);
            padding:16px;
            display:grid;
            gap:14px;
        }
        .mini-badge,
        .invoice-badge{
            display:inline-flex;
            align-items:center;
            gap:8px;
            width:fit-content;
            padding:7px 12px;
            border-radius:999px;
            background:#eef2f8;
            color:#4b5563;
            font-size:.75rem;
            font-weight:500;
        }
        .invoice-badge{
            background:var(--success-soft);
            color:var(--success);
            font-weight:600;
        }
        .form-head{
            display:grid;
            gap:10px;
        }
        .form-head h2{
            font-size:1.65rem;
            line-height:.95;
            font-weight:700;
            letter-spacing:-.04em;
        }
        .form-head p,
        .identity,
        .note{
            color:var(--text-secondary);
        }
        .form-head p,
        .identity{
            font-size:.84rem;
            line-height:1.7;
        }
        .error{
            padding:13px 14px;
            border-radius:16px;
            background:var(--danger-soft);
            color:#b91c1c;
            font-size:.85rem;
            line-height:1.6;
        }
        .form-grid{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:12px;
        }
        .field{
            display:grid;
            gap:7px;
        }
        .field.full{
            grid-column:1/-1;
        }
        .field label{
            font-size:.8rem;
            font-weight:600;
            color:var(--text-secondary);
        }
        .field input{
            width:100%;
            min-height:48px;
            border-radius:15px;
            border:1px solid var(--border);
            padding:0 14px;
            background:#fff;
            font:inherit;
            font-size:.88rem;
            color:var(--text-primary);
        }
        .field input.input-error{
            border-color:rgba(220,38,38,.42);
            background:rgba(254,242,242,.92);
        }
        .field input:focus{
            outline:none;
            border-color:rgba(68,97,242,.4);
            box-shadow:0 0 0 4px rgba(68,97,242,.08);
        }
        .field-error{
            font-size:.77rem;
            line-height:1.5;
            color:#b91c1c;
            font-weight:500;
        }
        .cta{
            min-height:50px;
            border:none;
            border-radius:17px;
            background:linear-gradient(135deg,#4461f2,#3b82f6);
            color:#fff;
            font:inherit;
            font-size:.9rem;
            font-weight:700;
            cursor:pointer;
            box-shadow:0 16px 28px rgba(68,97,242,.18);
        }
        .note{
            font-size:.8rem;
            line-height:1.7;
            text-align:center;
        }
        .modal-backdrop{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,.38);
            backdrop-filter:blur(10px);
            display:grid;
            place-items:center;
            padding:18px;
            z-index:50;
        }
        .modal-backdrop[hidden]{
            display:none;
        }
        .modal-card{
            width:min(100%,520px);
            background:rgba(255,255,255,.98);
            border:1px solid rgba(255,255,255,.88);
            border-radius:24px;
            box-shadow:0 26px 58px rgba(15,23,42,.18);
            padding:16px;
            display:grid;
            gap:14px;
        }
        .modal-head{
            display:flex;
            justify-content:space-between;
            gap:12px;
            align-items:flex-start;
        }
        .modal-head h3{
            font-size:1.18rem;
            line-height:1.1;
            font-weight:700;
            letter-spacing:-.03em;
        }
        .modal-head p{
            margin-top:4px;
            font-size:.82rem;
            line-height:1.6;
            color:var(--text-secondary);
        }
        .modal-close{
            width:38px;
            height:38px;
            border:none;
            border-radius:999px;
            background:#eef2f8;
            color:#475569;
            font:inherit;
            font-size:1.15rem;
            cursor:pointer;
        }
        .invoice-box{
            display:grid;
            gap:10px;
            padding:14px;
            border-radius:18px;
            background:#f8fbff;
            border:1px solid rgba(219,228,245,.9);
        }
        .invoice-row{
            display:flex;
            justify-content:space-between;
            gap:12px;
            align-items:center;
            font-size:.83rem;
            line-height:1.5;
        }
        .invoice-row span{
            color:var(--text-secondary);
        }
        .invoice-row strong{
            font-weight:700;
            color:var(--text-primary);
        }
        .invoice-row.total{
            margin-top:2px;
            padding-top:12px;
            border-top:1px solid rgba(203,213,225,.8);
        }
        .invoice-row.total strong{
            font-size:1.6rem;
            color:var(--primary);
            letter-spacing:-.03em;
        }
        @media (max-width: 720px){
            .topbar{
                gap:14px;
                padding:14px 16px 0;
            }
            .topbar-link,
            .topbar-button{
                font-size:.92rem;
            }
            .page-shell{
                padding:12px 14px 18px;
            }
            .card{
                padding:12px;
                border-radius:22px;
            }
            .card-inner,
            .modal-card{
                padding:14px;
            }
            .form-grid{
                grid-template-columns:1fr;
            }
            .form-head h2{
                font-size:1.45rem;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <span class="topbar-link">
            <span>Español</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 9l6 6 6-6"></path>
            </svg>
        </span>

        <button type="button" class="topbar-button" id="invoiceOpen">
            <span>Factura</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 12h12"></path>
                <path d="M12 6l6 6-6 6"></path>
            </svg>
        </button>
    </header>

    <div class="page-shell">
        <header class="page-head">
            <h1>Finaliza tu configuracion inicial</h1>
            <p>Completa la informacion base para crear tu empresa tipo {{ $typeName }} y entrar al panel. El detalle de la compra lo puedes ver en Factura.</p>
        </header>

        <main>
            <section class="card">
                <div class="card-inner">
                    <div class="form-head">
                        <span class="mini-badge">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="4" y="5" width="16" height="15" rx="3"></rect>
                                <path d="M8 3v4"></path>
                                <path d="M16 3v4"></path>
                            </svg>
                            Paso final
                        </span>
                        <h2>Configura tu empresa</h2>
                        <p>
                            @if ($requiresDefaultEntities)
                                En este paso vamos a crear la empresa, la sucursal principal y los datos iniciales con los que vas a empezar.
                            @else
                                En este paso vamos a crear la empresa y el acceso administrador base para comenzar con tu catalogo.
                            @endif
                        </p>
                    </div>

                    @if ($errors->has('payment'))
                        <div class="error">{{ $errors->first('payment') }}</div>
                    @endif

                    <form method="POST" action="{{ route('register.payment-summary.store') }}" class="form-grid">
                        @csrf

                        <div class="field full">
                            <label for="company_name">Nombre de la empresa</label>
                            <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" class="@error('company_name') input-error @enderror" required autofocus>
                            @error('company_name')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($supportsBranches)
                            <div class="field">
                                <label for="branch_name">Nombre de la sucursal principal</label>
                                <input id="branch_name" name="branch_name" type="text" value="{{ old('branch_name') }}" class="@error('branch_name') input-error @enderror" required>
                                @error('branch_name')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="field">
                            <label for="user_name">Nombre de tu usuario</label>
                            <input id="user_name" name="user_name" type="text" value="{{ old('user_name', $googleName) }}" class="@error('user_name') input-error @enderror" required>
                            @error('user_name')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($requiresDefaultEntities)
                            <div class="field">
                                <label for="default_client_name">Cliente por defecto</label>
                                <input id="default_client_name" name="default_client_name" type="text" value="{{ old('default_client_name', 'Cliente General') }}" class="@error('default_client_name') input-error @enderror" required>
                                @error('default_client_name')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="default_employee_name">Empleado por defecto</label>
                                <input id="default_employee_name" name="default_employee_name" type="text" value="{{ old('default_employee_name', $googleName ?: 'Empleado General') }}" class="@error('default_employee_name') input-error @enderror" required>
                                @error('default_employee_name')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="field full">
                            <button type="submit" class="cta">Entrar a xNova</button>
                        </div>
                    </form>

                    <p class="note">
                        La factura solo se muestra como referencia en este paso. Luego veras la suscripcion completa en otra pantalla.
                    </p>
                </div>
            </section>
        </main>
    </div>

    <div class="modal-backdrop" id="invoiceModal" hidden>
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="invoiceTitle">
            <div class="modal-head">
                <div>
                    <span class="invoice-badge">{{ $invoiceLabel }}</span>
                    <h3 id="invoiceTitle">Factura del alta</h3>
                    <p>Este es el resumen de lo que llevas en este registro.</p>
                </div>

                <button type="button" class="modal-close" id="invoiceClose" aria-label="Cerrar factura">×</button>
            </div>

            <div class="invoice-box">
                <div class="invoice-row">
                    <span>Plan</span>
                    <strong>{{ $pendingSetup['package_name'] }}</strong>
                </div>
                <div class="invoice-row">
                    <span>Rubro</span>
                    <strong>{{ $typeName }}</strong>
                </div>
                <div class="invoice-row">
                    <span>Cuenta</span>
                    <strong>{{ $googleEmail ?: 'Sin correo' }}</strong>
                </div>
                <div class="invoice-row">
                    <span>Usuarios totales</span>
                    <strong>{{ $pendingSetup['selected_users'] }}</strong>
                </div>
                <div class="invoice-row">
                    <span>Usuarios extra</span>
                    <strong>{{ $pendingSetup['extra_users'] }}</strong>
                </div>
                @if ($supportsBranches)
                    <div class="invoice-row">
                        <span>Sucursales totales</span>
                        <strong>{{ $pendingSetup['selected_branches'] ?? 1 }}</strong>
                    </div>
                    <div class="invoice-row">
                        <span>Sucursales extra</span>
                        <strong>{{ $pendingSetup['extra_branches'] ?? 0 }}</strong>
                    </div>
                @endif
                <div class="invoice-row">
                    <span>Plan base</span>
                    <strong>{{ $currencySymbol }}{{ number_format((float) ($pendingSetup['base_price'] ?? 0), 2) }}</strong>
                </div>
                <div class="invoice-row">
                    <span>Recargo usuarios</span>
                    <strong>{{ $currencySymbol }}{{ number_format((float) (($pendingSetup['extra_users'] ?? 0) * ($pendingSetup['extra_user_price'] ?? 0)), 2) }}</strong>
                </div>
                @if ($supportsBranches)
                    <div class="invoice-row">
                        <span>Recargo sucursales</span>
                        <strong>{{ $currencySymbol }}{{ number_format((float) (($pendingSetup['extra_branches'] ?? 0) * ($pendingSetup['extra_branch_price'] ?? 0)), 2) }}</strong>
                    </div>
                @endif
                <div class="invoice-row">
                    <span>{{ $invoiceDateLabel }}</span>
                    <strong>{{ $invoiceDateValue }}</strong>
                </div>
                <div class="invoice-row total">
                    <span>Total mensual</span>
                    <strong>{{ $currencySymbol }}{{ number_format((float) $pendingSetup['monthly_total'], 2) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <script>
        const invoiceOpenButton = document.getElementById('invoiceOpen');
        const invoiceCloseButton = document.getElementById('invoiceClose');
        const invoiceModal = document.getElementById('invoiceModal');

        function openInvoiceModal() {
            invoiceModal.hidden = false;
            document.body.style.overflow = 'hidden';
        }

        function closeInvoiceModal() {
            invoiceModal.hidden = true;
            document.body.style.overflow = '';
        }

        invoiceOpenButton.addEventListener('click', openInvoiceModal);
        invoiceCloseButton.addEventListener('click', closeInvoiceModal);

        invoiceModal.addEventListener('click', function (event) {
            if (event.target === invoiceModal) {
                closeInvoiceModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !invoiceModal.hidden) {
                closeInvoiceModal();
            }
        });
    </script>
</body>
</html>
