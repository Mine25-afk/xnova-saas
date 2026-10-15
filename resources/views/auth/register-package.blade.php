@php
    $googleName = $googleOnboarding['name'] ?? null;
    $googleEmail = $googleOnboarding['email'] ?? null;
    $hasGoogleOnboarding = filled($googleEmail);
    $currency = $packageData['currency'] ?? 'USD';
    $currencySymbol = $currency === 'USD' ? '$' : $currency.' ';
    $selectedTypeId = (int) ($selectedBusinessType['id'] ?? $packageData['selected_type_id'] ?? 0);
    $requiresDefaultEntities = (bool) ($selectedBusinessType['requires_default_entities'] ?? true);
    $supportsBranches = (bool) ($selectedBusinessType['supports_branches'] ?? true);
    $checkoutReady = is_array($pendingSetup ?? null);
    $initialStep = $errors->any() ? 'create' : 'select';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registro — xNova</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --primary:#4461f2;
            --primary-dark:#3651e0;
            --primary-soft:rgba(68,97,242,.1);
            --surface:#ffffff;
            --surface-soft:#f8fafc;
            --surface-alt:#f5f7fb;
            --border:#e2e8f0;
            --text-primary:#1e293b;
            --text-secondary:#64748b;
            --danger:#dc2626;
            --danger-soft:rgba(239,68,68,.1);
            --success:#0f9f73;
            --success-soft:rgba(16,185,129,.12);
        }
        body{
            min-height:100vh;
            font-family:'Poppins',sans-serif;
            background:linear-gradient(180deg,#f0f4ff 0%,#e8efff 50%,#dbe4ff 100%);
            color:var(--text-primary);
        }
        body.modal-open{
            overflow:hidden;
        }
        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:28px;
            width:100%;
            margin:0;
            padding:18px 30px 0 30px;
        }
        .topbar-brand{
            display:flex;
            align-items:center;
            gap:9px;
            text-decoration:none;
        }
        .topbar-brand-icon{
            width:32px;
            height:32px;
            background:var(--primary);
            border-radius:8px;
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
        }
        .topbar-brand-icon svg{
            width:18px;
            height:18px;
            fill:#fff;
        }
        .topbar-brand-name{
            font-size:1.2rem;
            font-weight:700;
            color:var(--text-primary);
            letter-spacing:-.3px;
        }
        .topbar-right{
            display:flex;
            align-items:center;
            gap:28px;
        }
        .topbar-link{
            display:inline-flex;
            align-items:center;
            gap:6px;
            color:#64748b;
            text-decoration:none;
            font-size:1rem;
            font-weight:600;
            transition:color .2s ease;
        }
        .topbar-link:hover{
            color:var(--primary);
        }
        .topbar-link svg{
            width:13px;
            height:13px;
            fill:currentColor;
            flex-shrink:0;
        }
        .topbar-link--login{
            color:var(--primary);
        }
        .page-shell{
            min-height:calc(100vh - 50px);
            max-width:1120px;
            margin:0 auto;
            padding:10px 16px 12px;
            display:grid;
            align-content:start;
            gap:12px;
        }
        .page-head{
            display:grid;
            gap:8px;
            justify-items:center;
            text-align:center;
        }
        .pill,.tag,.account-pill,.status-pill{
            display:inline-flex;
            align-items:center;
            width:fit-content;
            border-radius:999px;
            font-weight:700;
        }
        .pill{
            padding:12px 22px;
            background:rgba(68,97,242,.12);
            color:var(--primary);
            font-size:.92rem;
        }
        .page-head h1{
            font-size:clamp(1.5rem,2.2vw,2rem);
            line-height:1.15;
            font-weight:700;
            letter-spacing:-.02em;
        }
        .page-head p{
            max-width:560px;
            color:var(--text-secondary);
            font-size:.92rem;
            line-height:1.55;
        }
        .flow-shell{
            position:relative;
            overflow:hidden;
            transition:height .45s cubic-bezier(.22,1,.36,1);
        }
        .flow-track{
            display:flex;
            width:300%;
            transition:transform .55s cubic-bezier(.22,1,.36,1);
            will-change:transform;
        }
        .flow-shell[data-step="select"] .flow-track{
            transform:translateX(0);
        }
        .flow-shell[data-step="config"] .flow-track{
            transform:translateX(-33.3333%);
        }
        .flow-shell[data-step="create"] .flow-track{
            transform:translateX(-66.6666%);
        }
        .flow-panel{
            width:33.3333%;
            flex:0 0 33.3333%;
        }
        .progress-line{
            display:flex;
            justify-content:center;
            align-items:center;
            list-style:none;
            margin:0 auto 18px;
            max-width:560px;
            position:relative;
            gap:8px;
        }
        .progress-step{
            display:flex;
            flex-direction:column;
            align-items:center;
            text-align:center;
            gap:6px;
            position:relative;
            color:#94a3b8;
            font-size:.72rem;
            font-weight:500;
            letter-spacing:.01em;
            transition:color .3s ease;
            cursor:pointer;
        }
        .progress-connector{
            width:56px;
            height:1.5px;
            background:var(--border);
            border-radius:999px;
            transition:background .3s ease;
            margin-bottom:18px;
        }
        .progress-indicator{
            width:26px;
            height:26px;
            border-radius:50%;
            background:#fff;
            border:1.5px solid var(--border);
            display:grid;
            place-items:center;
            color:#94a3b8;
            font-weight:600;
            font-size:12px;
            position:relative;
            z-index:2;
            transition:all .3s ease;
        }
        .progress-step.is-active{
            color:var(--primary);
            font-weight:600;
        }
        .progress-step.is-active .progress-indicator{
            background:var(--primary);
            border-color:var(--primary);
            color:#fff;
            box-shadow:0 0 0 3px var(--primary-soft);
        }
        .progress-step.is-completed{
            color:var(--text-primary);
        }
        .progress-step.is-completed .progress-indicator{
            background:var(--success);
            border-color:var(--success);
            color:#fff;
        }
        .progress-connector.is-completed{
            background:var(--success);
        }
        .flow-panel--select{
            padding:8px 0 6px;
        }
        .flow-panel--config{
            display:grid;
            gap:12px;
        }
        .flow-panel--create{
            display:grid;
            gap:12px;
        }
        .create-card{
            max-width:860px;
            margin:0 auto;
            width:100%;
        }
        .create-inner{
            border-radius:18px;
            background:#fff;
            padding:28px 30px;
            display:grid;
            gap:18px;
        }
        .create-head{
            display:grid;
            gap:6px;
        }
        .create-head h2{
            font-size:1.5rem;
            line-height:1.15;
            font-weight:700;
            letter-spacing:-.02em;
        }
        .create-head p{
            color:var(--text-secondary);
            font-size:.88rem;
            line-height:1.55;
        }
        .mini-badge{
            display:inline-flex;
            align-items:center;
            gap:8px;
            width:fit-content;
            padding:7px 12px;
            border-radius:999px;
            background:#eef2f8;
            color:#4b5563;
            font-size:.75rem;
            font-weight:600;
        }
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
        .create-form{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:12px;
        }
        .field{
            display:grid;
            gap:7px;
        }
        .field.full{grid-column:1/-1}
        .field label{
            font-size:.82rem;
            font-weight:600;
            color:var(--text-secondary);
        }
        .field input{
            width:100%;
            min-height:44px;
            border-radius:10px;
            border:1px solid var(--border);
            padding:0 14px;
            background:#fff;
            font:inherit;
            font-size:.9rem;
            color:var(--text-primary);
            transition:border-color .2s ease,box-shadow .2s ease;
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
            font-size:.78rem;
            line-height:1.5;
            color:#b91c1c;
            font-weight:500;
        }
        .checkout-methods{
            display:grid;
            gap:10px;
        }
        .checkout-actions{
            display:grid;
            grid-template-columns:repeat(2,minmax(0,1fr));
            gap:10px;
            align-items:center;
        }
        .checkout-actions > .cta:only-child,
        .checkout-actions > .paypal-placeholder:only-child,
        .checkout-actions > .paypal-shell:only-child{
            grid-column:1/-1;
        }
        .checkout-chip{
            display:inline-flex;
            align-items:center;
            gap:8px;
            width:fit-content;
            padding:6px 10px;
            border-radius:999px;
            background:var(--success-soft);
            color:var(--success);
            font-size:.76rem;
            font-weight:600;
        }
        .checkout-chip[hidden]{display:none}
        .type-selector{
            display:grid;
            gap:18px;
        }
        .type-selector__head{
            display:grid;
            gap:10px;
            justify-items:center;
            text-align:center;
        }
        .type-selector__eyebrow{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:9px 15px;
            border-radius:999px;
            background:rgba(68,97,242,.1);
            color:var(--primary);
            font-size:.8rem;
            font-weight:700;
            letter-spacing:.02em;
        }
        .type-selector__head h2{
            font-size:clamp(1.5rem,2.2vw,2rem);
            line-height:1.15;
            font-weight:700;
            letter-spacing:-.02em;
        }
        .type-selector__head p{
            max-width:720px;
            color:var(--text-secondary);
            font-size:1rem;
            line-height:1.75;
        }
        .type-selector__grid{
            display:grid;
            grid-template-columns:repeat(4,minmax(0,1fr));
            gap:16px;
        }
        .type-card{
            border:1px solid var(--border);
            border-radius:20px;
            background:#fff;
            box-shadow:0 1px 3px rgba(15,23,42,.04);
            padding:22px 18px 20px;
            display:grid;
            gap:12px;
            cursor:pointer;
            justify-items:center;
            text-align:center;
            min-height:200px;
            transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease;
        }
        .type-card:hover{
            transform:translateY(-3px);
            border-color:rgba(68,97,242,.3);
            box-shadow:0 8px 20px rgba(68,97,242,.08);
        }
        .type-card.is-active{
            border-color:var(--primary);
            border-width:1.5px;
            background:linear-gradient(180deg,#fff 0%,#f5f8ff 100%);
            box-shadow:0 8px 20px rgba(68,97,242,.12);
        }
        .type-card:focus,
        .type-card:focus-visible{
            outline:none;
        }
        .type-card__icon{
            width:54px;
            height:54px;
            border-radius:16px;
            background:linear-gradient(135deg,#eef3ff 0%,#dde8ff 100%);
            color:var(--primary);
            display:grid;
            place-items:center;
        }
        .type-card__icon svg{
            width:26px;
            height:26px;
            stroke:currentColor;
            fill:none;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        .type-card__title{
            font-size:1rem;
            font-weight:700;
            line-height:1.2;
            letter-spacing:-.01em;
        }
        .type-card__copy{
            color:var(--text-secondary);
            font-size:.82rem;
            line-height:1.55;
            max-width:220px;
            font-weight:400;
        }
        .pricing-grid{
            display:grid;
            grid-template-columns:minmax(0,1fr);
            gap:10px;
            align-items:start;
            justify-items:center;
        }
        .plan-card-wrap{
            position:relative;
            width:100%;
            max-width:860px;
            margin:0 auto;
            height:auto;
            overflow:visible;
        }
        .card{
            background:#fff;
            border:1px solid var(--border);
            border-radius:18px;
            box-shadow:0 2px 12px rgba(15,23,42,.04);
            padding:0;
            display:grid;
            gap:10px;
            height:100%;
        }
        .card-muted{
            background:#fff;
            border-color:var(--border);
            box-shadow:0 16px 40px rgba(15,23,42,.10);
            position:relative;
            z-index:4;
        }
        .add-ons-modal{
            position:fixed;
            inset:0;
            z-index:80;
            display:none;
            align-items:center;
            justify-content:center;
            padding:20px;
            background:rgba(15,23,42,.42);
            backdrop-filter:blur(4px);
        }
        .add-ons-modal.is-open{
            display:flex;
        }
        .add-ons-modal__dialog{
            width:min(640px,92vw);
            max-height:min(92vh,760px);
            overflow:auto;
            padding:24px;
            margin:0 auto;
            display:grid;
            gap:18px;
        }
        .add-ons-modal__head{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
        }
        .add-ons-modal__close{
            width:36px;
            height:36px;
            border:none;
            border-radius:12px;
            background:#edf2f8;
            color:#64748b;
            cursor:pointer;
            display:grid;
            place-items:center;
        }
        .add-ons-modal__close svg{
            width:18px;
            height:18px;
            stroke:currentColor;
            fill:none;
            stroke-width:2;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        .add-ons-modal__close:hover{
            background:#e2e8f0;
            color:#334155;
        }
        .plan-card{
            padding:0;
            gap:0;
            position:relative;
            overflow:visible;
            z-index:2;
            height:auto;
        }
        .plan-card__inner{
            padding:28px 30px 24px;
            border-radius:18px;
            background:#fff;
            display:grid;
            gap:18px;
            height:auto;
            align-content:start;
            position:relative;
            z-index:2;
        }
        .plan-mini-badge{
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
        .plan-mini-badge svg,
        .plan-security svg,
        .paypal-placeholder svg{
            width:15px;
            height:15px;
            stroke:currentColor;
            fill:none;
            stroke-width:1.8;
            stroke-linecap:round;
            stroke-linejoin:round;
            flex-shrink:0;
        }
        .plan-header{
            display:grid;
            gap:8px;
        }
        .plan-title-row{
            display:flex;
            align-items:flex-start;
            gap:14px;
            flex-wrap:wrap;
            justify-content:space-between;
        }
        .plan-title-tools{
            display:flex;
            align-items:center;
            gap:8px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }
        .plan-title-row h2{
            font-size:1.5rem;
            line-height:1.15;
            font-weight:700;
            letter-spacing:-.02em;
        }
        .plan-price-pill{
            display:inline-flex;
            align-items:center;
            padding:7px 13px;
            border-radius:999px;
            background:var(--primary-soft);
            color:var(--primary-dark);
            font-size:.78rem;
            font-weight:600;
        }
        .price-add-toggle{
            display:inline-flex;
            align-items:center;
            gap:5px;
            min-height:32px;
            padding:0 11px;
            border-radius:999px;
            border:1px solid var(--border);
            background:#fff;
            color:var(--text-secondary);
            font:inherit;
            font-size:.74rem;
            font-weight:600;
            cursor:pointer;
            transition:border-color .2s ease,color .2s ease;
        }
        .price-add-toggle svg{
            width:13px;
            height:13px;
            stroke:currentColor;
            fill:none;
            stroke-width:2;
            stroke-linecap:round;
            stroke-linejoin:round;
        }
        .price-add-toggle:hover{
            border-color:var(--primary);
            color:var(--primary);
        }
        .plan-description{
            color:var(--text-secondary);
            font-size:.88rem;
            line-height:1.55;
            font-weight:400;
        }
        .plan-feature-box{
            padding:16px 18px;
            border-radius:14px;
            background:var(--surface-soft);
            border:1px solid var(--border);
            display:grid;
            gap:12px;
        }
        .plan-feature-box__head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
        }
        .plan-feature-box__head strong{
            font-size:.85rem;
            font-weight:600;
            color:var(--text-primary);
        }
        .plan-feature-box__head span{
            color:var(--text-secondary);
            font-size:.75rem;
            font-weight:500;
        }
        .plan-feature-list{
            list-style:none;
            display:grid;
            gap:10px;
        }
        .plan-feature-list li{
            display:flex;
            align-items:flex-start;
            gap:10px;
            font-size:.85rem;
            line-height:1.45;
            font-weight:400;
            color:#475569;
        }
        .plan-total-box{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            padding:14px 18px;
            border-radius:14px;
            background:var(--primary-soft);
        }
        .plan-total-box span{
            color:var(--text-primary);
            font-size:.82rem;
            font-weight:500;
        }
        .plan-total-box strong{
            color:var(--primary-dark);
            font-size:1.15rem;
            line-height:1;
            font-weight:700;
        }
        .plan-feature-icon{
            width:18px;
            height:18px;
            border-radius:50%;
            background:var(--primary-soft);
            color:var(--primary);
            display:inline-flex;
            align-items:center;
            justify-content:center;
            font-size:.7rem;
            font-weight:700;
            flex-shrink:0;
            margin-top:2px;
        }
        .plan-actions{
            display:flex;
            justify-content:center;
            gap:12px;
            margin-top:4px;
        }
        .plan-actions .cta{
            min-height:46px;
            width:auto;
            padding:0 32px;
        }
        .paypal-shell{
            min-height:46px;
            display:grid;
            align-items:center;
            padding:3px;
            border-radius:12px;
            background:#fff;
            border:1.5px solid var(--border);
            transition:border-color .2s ease;
        }
        .paypal-shell:hover{
            border-color:#94a3b8;
        }
        .paypal-shell #paypal-button-container{
            min-height:40px;
        }
        .paypal-placeholder{
            min-height:46px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            border-radius:12px;
            background:#fff;
            color:var(--text-primary);
            border:1.5px solid var(--border);
            font:inherit;
            font-weight:600;
            font-size:.88rem;
            padding:0 18px;
        }
        .plan-security{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            color:#6f8198;
            font-size:.74rem;
            font-weight:400;
            text-align:center;
            margin-top:auto;
        }
        .plan-security svg{
            color:#16a34a;
        }
        .card-head{
            display:flex;
            justify-content:space-between;
            gap:16px;
            align-items:flex-start;
        }
        .tag{
            padding:9px 16px;
            background:var(--primary);
            color:#fff;
            font-size:.8rem;
        }
        .status-pill{
            padding:9px 14px;
            background:var(--primary-soft);
            color:var(--primary);
            font-size:.8rem;
        }
        .card-title{
            display:grid;
            gap:6px;
        }
        .card-title h2{
            font-size:1.45rem;
            line-height:1;
            font-weight:700;
        }
        .card-title p{
            color:var(--text-secondary);
            font-size:.84rem;
            line-height:1.6;
            font-weight:400;
        }
        .price-block{
            display:flex;
            align-items:flex-end;
            gap:10px;
            flex-wrap:wrap;
        }
        .price-block strong{
            font-size:4rem;
            line-height:.9;
            font-weight:800;
            letter-spacing:-.05em;
        }
        .price-block span{
            color:var(--text-secondary);
            font-size:1rem;
            padding-bottom:8px;
        }
        .account-pill{
            padding:10px 14px;
            background:var(--success-soft);
            color:var(--success);
            font-size:.84rem;
        }
        .feature-note,.addon-copy,.summary-row span,.helper-text,.status,.counter-note{
            color:var(--text-secondary);
        }
        .feature-list{
            list-style:none;
            display:grid;
            gap:13px;
        }
        .feature-list li{
            position:relative;
            padding-left:32px;
            font-size:.98rem;
            line-height:1.55;
        }
        .feature-list li::before{
            content:"";
            position:absolute;
            left:0;
            top:7px;
            width:18px;
            height:18px;
            border-radius:50%;
            background:var(--primary-soft);
        }
        .feature-list li::after{
            content:"";
            position:absolute;
            left:6px;
            top:12px;
            width:6px;
            height:3px;
            border-left:2px solid var(--primary);
            border-bottom:2px solid var(--primary);
            transform:rotate(-45deg);
        }
        .actions{
            display:grid;
            gap:12px;
        }
        .cta{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:46px;
            border:none;
            border-radius:12px;
            padding:0 20px;
            text-decoration:none;
            font:inherit;
            font-size:.9rem;
            font-weight:600;
            cursor:pointer;
            transition:transform .2s ease,box-shadow .2s ease,background .2s ease,border-color .2s ease;
        }
        .cta:hover{
            transform:translateY(-1px);
        }
        .cta-primary{
            background:var(--primary);
            color:#fff;
            box-shadow:0 4px 12px rgba(68,97,242,.22);
        }
        .cta-primary:hover{
            background:var(--primary-dark);
            box-shadow:0 6px 18px rgba(68,97,242,.32);
        }
        .cta-secondary{
            background:#fff;
            color:var(--text-primary);
            border:1.5px solid var(--border);
        }
        .cta-secondary:hover{
            border-color:#94a3b8;
            box-shadow:0 4px 12px rgba(15,23,42,.06);
        }
        .flash{
            padding:14px 16px;
            border-radius:18px;
            background:var(--danger-soft);
            color:var(--danger);
            font-size:.9rem;
            line-height:1.55;
        }
        .counter-group{
            display:grid;
            gap:10px;
        }
        .counter-card{
            padding:18px;
            border-radius:14px;
            background:#fff;
            border:1px solid var(--border);
            display:grid;
            gap:10px;
        }
        .counter-card__head{
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:12px;
        }
        .counter-card__head span{
            display:block;
            font-size:.78rem;
            color:var(--text-secondary);
            margin-bottom:4px;
            font-weight:500;
        }
        .counter-card__head strong{
            font-size:1.4rem;
            line-height:1;
            font-weight:700;
            color:var(--text-primary);
        }
        .counter-note{
            font-size:.74rem;
            line-height:1.5;
            color:var(--text-secondary);
        }
        .counter{
            display:grid;
            grid-template-columns:36px minmax(0,1fr) 36px;
            gap:8px;
        }
        .counter-btn{
            min-height:36px;
            border:none;
            border-radius:8px;
            background:var(--primary-soft);
            color:var(--primary);
            font:inherit;
            font-size:1rem;
            font-weight:600;
            cursor:pointer;
            transition:background .2s ease;
        }
        .counter-btn:hover{
            background:rgba(68,97,242,.18);
        }
        .counter-btn:disabled,.counter-btn.is-disabled{
            opacity:.42;
            cursor:not-allowed;
        }
        .counter-input{
            width:100%;
            min-height:36px;
            border-radius:8px;
            border:1px solid var(--border);
            background:#fff;
            text-align:center;
            font:inherit;
            font-size:.9rem;
            font-weight:600;
        }
        .counter-input:focus{
            outline:none;
            border-color:rgba(68,97,242,.34);
        }
        .summary{
            display:grid;
            gap:8px;
            padding:8px 10px 0;
            border-top:1px solid rgba(148,163,184,.22);
        }
        .summary-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }
        .summary-row span{
            font-size:.78rem;
            font-weight:400;
        }
        .summary-row strong{
            font-size:.82rem;
            font-weight:600;
        }
        .summary-row.total strong{
            font-size:1.25rem;
            color:var(--primary-dark);
        }
        .helper-text{
            font-size:.85rem;
            line-height:1.6;
        }
        .status{
            min-height:22px;
            font-size:.72rem;
            line-height:1.5;
        }
        .status.error{color:var(--danger)}
        .status.success{color:var(--success)}
        .plan-card .status{
            text-align:center;
        }
        .sr-only{display:none}
        @media (min-width: 981px) and (max-height: 860px){
            .topbar{padding:8px 22px 0 14px}
            .page-shell{min-height:calc(100vh - 42px);padding:8px 14px 10px;gap:10px}
            .page-head h1{font-size:clamp(1.4rem,2vw,1.8rem)}
            .page-head p{font-size:.88rem;line-height:1.4}
            .progress-line{margin:0 auto 12px}
            .type-selector__grid{gap:12px}
            .type-card{min-height:170px;padding:16px 14px;gap:10px}
            .type-card__copy{font-size:.78rem;line-height:1.45}
            .plan-card__inner{padding:20px 22px}
            .plan-title-row h2{font-size:1.35rem}
            .plan-title-tools{gap:6px}
            .price-add-toggle{min-height:30px;padding:0 9px;font-size:.72rem}
            .plan-description[data-plan-secondary]{display:none}
            .plan-feature-box{padding:12px 14px;gap:10px}
            .plan-feature-list{gap:8px}
            .plan-feature-list li{font-size:.8rem}
            .plan-total-box{padding:10px 14px}
            .plan-total-box strong{font-size:1.05rem}
            .plan-card-wrap{max-width:720px}
            .summary-row.total strong{font-size:1.15rem}
            .create-inner{padding:20px 22px;gap:12px}
            .field input{min-height:40px}
        }
        @media (max-width: 980px){
            .pricing-grid{grid-template-columns:1fr}
            .plan-card-wrap{max-width:100%}
            .type-selector__grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .add-ons-modal{padding:14px}
            .add-ons-modal__dialog{max-height:calc(100vh - 24px);padding:14px}
        }
        @media (max-width: 720px){
            .topbar{
                gap:14px;
                padding:14px 14px 0;
            }
            .page-shell{padding:14px}
            .card{padding:0;border-radius:16px}
            .card-head,.counter-card__head{flex-direction:column;align-items:flex-start}
            .price-block strong{font-size:3.2rem}
            .plan-card{padding:0}
            .plan-card__inner{padding:20px 18px}
            .type-card{min-height:180px;padding:18px 14px}
            .plan-title-row h2{font-size:1.3rem}
            .plan-title-tools{width:100%;justify-content:flex-start}
            .card-title h2{font-size:1.2rem}
            .plan-actions{flex-direction:column}
            .plan-actions .cta{width:100%}
            .plan-feature-box__head{flex-direction:column;align-items:flex-start}
            .create-form{grid-template-columns:1fr}
            .create-inner{padding:20px 18px}
            .checkout-actions{grid-template-columns:1fr}
            .progress-connector{width:36px}
        }
        @media (max-width: 560px){
            .type-selector__grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="{{ route('login') }}" class="topbar-brand" aria-label="xNova">
            <div class="topbar-brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 3h3v7.5L14.5 3H18l-8 8.5L19 21h-3.5l-7-8-1.5 1.6V21H4V3z"/>
                </svg>
            </div>
            <span class="topbar-brand-name">xNova</span>
        </a>
        <div class="topbar-right">
            <span class="topbar-link">
                <span>Español</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5 5-5z"></path></svg>
            </span>
            <a href="{{ route('login') }}" class="topbar-link topbar-link--login">Iniciar sesión</a>
        </div>
    </header>

    <div class="page-shell">
        <header class="page-head">
            <h1 data-flow-title>Escoge el rubro</h1>
            <p data-flow-copy>Selecciona el rubro y luego ajusta tu configuracion para crear la empresa.</p>
        </header>

        <ul class="progress-line" data-progress-line>
            <li class="progress-step is-active" data-step-indicator="select">
                <span class="progress-indicator">1</span>
                <span>Rubro</span>
            </li>
            <li class="progress-connector" data-step-connector="1" aria-hidden="true"></li>
            <li class="progress-step" data-step-indicator="config">
                <span class="progress-indicator">2</span>
                <span>Configuracion</span>
            </li>
            <li class="progress-connector" data-step-connector="2" aria-hidden="true"></li>
            <li class="progress-step" data-step-indicator="create">
                <span class="progress-indicator">3</span>
                <span>Crear</span>
            </li>
        </ul>

        <div class="flow-shell" data-flow-shell data-step="{{ $initialStep }}">
            <div class="flow-track">
                <section class="flow-panel flow-panel--select">
                    <section class="type-selector">
                        <div class="type-selector__grid">
                            @foreach ($businessTypes as $businessType)
                                @php
                                    $typeIcon = match ($businessType['name']) {
                                        'Clinica' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3v6a4 4 0 0 0 8 0V3"></path><path d="M6 3h2"></path><path d="M12 3h2"></path><circle cx="18" cy="14" r="2"></circle><path d="M14 17v2a3 3 0 0 0 6 0v-3"></path><path d="M10 13v3a4 4 0 0 0 4 4"></path></svg>',
                                        'Salones' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v5"></path><path d="M12 16v5"></path><path d="M4.9 4.9l3.5 3.5"></path><path d="M15.6 15.6l3.5 3.5"></path><path d="M3 12h5"></path><path d="M16 12h5"></path><path d="M4.9 19.1l3.5-3.5"></path><path d="M15.6 8.4l3.5-3.5"></path></svg>',
                                        'Barberias' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m8 4 8 8"></path><path d="m16 4-8 8"></path><path d="M7 13 4 20"></path><path d="M17 13l3 7"></path><circle cx="7" cy="7" r="2"></circle><circle cx="17" cy="7" r="2"></circle></svg>',
                                        'Catalogos' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="7" height="7" rx="1.8"></rect><rect x="14" y="4" width="7" height="7" rx="1.8"></rect><rect x="3" y="13" width="7" height="7" rx="1.8"></rect><rect x="14" y="13" width="7" height="7" rx="1.8"></rect></svg>',
                                        'Finanzas Personales y en Pareja', 'Finanzas' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"></rect><path d="M3 10h18"></path><circle cx="16" cy="14.5" r="1.5"></circle><path d="M7 14h3"></path></svg>',
                                        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path><path d="M10 21v-7h4v7"></path></svg>',
                                    };
                                @endphp
                                <button
                                    type="button"
                                    class="type-card {{ (int) $businessType['id'] === $selectedTypeId ? 'is-active' : '' }}"
                                    data-type-card
                                    data-type-id="{{ $businessType['id'] }}"
                                >
                                    <span class="type-card__icon" aria-hidden="true">{!! $typeIcon !!}</span>
                                    <strong class="type-card__title">{{ $businessType['name'] }}</strong>
                                    <span class="type-card__copy">{{ $businessType['description'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                </section>

                <section class="flow-panel flow-panel--config">
                    <main class="pricing-grid">
                        <div class="plan-card-wrap">
                            <section class="card plan-card">
                            <div class="plan-card__inner">
                                <div class="plan-header">
                                    <div class="plan-title-row">
                                        <h2 data-plan-headline>{{ $selectedBusinessType['headline'] }}</h2>
                                        <div class="plan-title-tools">
                                            <span class="plan-price-pill" data-plan-price-pill>{{ $currencySymbol }}{{ number_format($packageData['base_price'], 0) }} por mes</span>
                                            <button type="button" class="price-add-toggle" data-toggle-addons>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                                                <span>Agregar</span>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="plan-description" data-plan-description>{{ $selectedBusinessType['description'] }}</p>
                                    <p class="plan-description" data-plan-secondary>{{ $selectedBusinessType['secondary_description'] }}</p>
                                </div>

                                <div class="plan-feature-box">
                                    <div class="plan-feature-box__head">
                                        <strong>Todo lo necesario para gestionar tu operacion</strong>
                                        <span data-plan-users-copy>{{ $packageData['included_users'] }} usuarios incluidos</span>
                                    </div>

                                    <ul class="plan-feature-list" data-plan-feature-list>
                                        <li><span class="plan-feature-icon">✓</span><span>Agenda de Citas y Calendario Manual</span></li>
                                        <li><span class="plan-feature-icon">✓</span><span>Visualizar los huecos libres para una cita</span></li>
                                        <li><span class="plan-feature-icon">✓</span><span>Reservacion por link para clientes</span></li>
                                        <li><span class="plan-feature-icon">✓</span><span>Recordatorios por Correo Electronico</span></li>
                                        <li><span class="plan-feature-icon">✓</span><span>Reportes</span></li>
                                    </ul>
                                </div>

                                <div class="plan-total-box">
                                    <span>Total del plan</span>
                                    <strong data-total-amount-main>{{ $currencySymbol }}{{ number_format($packageData['monthly_total'], 2) }}</strong>
                                </div>
                                
                                @if (isset($errors) && $errors->any())
                                    <div class="flash">{{ $errors->first() }}</div>
                                @endif

                                <div class="plan-actions">
                                    <button type="button" class="cta cta-primary" data-continue-create>Continuar a crear empresa</button>
                                </div>

                                <div class="plan-security">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v6c0 4.4-3 8.4-7 9-4-0.6-7-4.6-7-9V6l7-3z"></path><path d="m9.5 12 1.7 1.7 3.3-3.4"></path></svg>
                                    <span>Pago seguro y activacion inmediata</span>
                                </div>

                                <div class="sr-only">
                                    <input type="hidden" value="{{ $packageData['id'] }}" data-package-id>
                                    <input type="hidden" value="{{ $packageData['selected_users'] }}" data-users-hidden>
                                    <input type="hidden" value="{{ $packageData['selected_branches'] }}" data-branches-hidden>
                                    <input type="hidden" value="{{ $selectedTypeId }}" data-type-hidden>
                                </div>

                            </div>
                            </section>
                        </div>
                    </main>

                </section>

                <section class="flow-panel flow-panel--create">
                    <section class="card create-card">
                        <div class="create-inner">
                            <div class="create-head">
                                <h2>Configura tu empresa</h2>
                                <p data-create-copy>
                                    @if ($requiresDefaultEntities)
                                        Elige si iniciaras con PayPal o prueba de 7 dias, luego crea la empresa, sucursal y datos base.
                                    @else
                                        Elige si iniciaras con PayPal o prueba de 7 dias, luego crea la empresa y usuario administrador.
                                    @endif
                                </p>
                            </div>

                            @if ($errors->has('payment'))
                                <div class="flash">{{ $errors->first('payment') }}</div>
                            @endif

                            <form method="POST" action="{{ route('register.payment-summary.store') }}" class="create-form" data-company-form>
                                @csrf

                                <div class="field full">
                                    <label for="company_name">Nombre de la empresa</label>
                                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" class="@error('company_name') input-error @enderror" required>
                                    @error('company_name')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="field" data-field-branches @unless($supportsBranches) hidden @endunless>
                                    <label for="branch_name">Nombre de la sucursal principal</label>
                                    <input id="branch_name" name="branch_name" type="text" value="{{ old('branch_name') }}" class="@error('branch_name') input-error @enderror" @if($supportsBranches) required @endif>
                                    @error('branch_name')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="field">
                                    <label for="user_name">Nombre de tu usuario</label>
                                    <input id="user_name" name="user_name" type="text" value="{{ old('user_name', $googleName) }}" class="@error('user_name') input-error @enderror" required>
                                    @error('user_name')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="field" data-field-default-client @unless($requiresDefaultEntities) hidden @endunless>
                                    <label for="default_client_name">Cliente por defecto</label>
                                    <input id="default_client_name" name="default_client_name" type="text" value="{{ old('default_client_name', 'Cliente General') }}" class="@error('default_client_name') input-error @enderror" @if($requiresDefaultEntities) required @endif>
                                    @error('default_client_name')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="field" data-field-default-employee @unless($requiresDefaultEntities) hidden @endunless>
                                    <label for="default_employee_name">Empleado por defecto</label>
                                    <input id="default_employee_name" name="default_employee_name" type="text" value="{{ old('default_employee_name', $googleName ?: 'Empleado General') }}" class="@error('default_employee_name') input-error @enderror" @if($requiresDefaultEntities) required @endif>
                                    @error('default_employee_name')
                                        <div class="field-error">{{ $message }}</div>
                                    @enderror
                                </div>

                            </form>

                            <div class="checkout-methods">
                                <div class="checkout-actions">
                                    @if (! $hasGoogleOnboarding)
                                        <a
                                            href="{{ route('google.redirect', ['users' => $packageData['selected_users'], 'branches' => $packageData['selected_branches'], 'type_id' => $selectedTypeId]) }}"
                                            data-base-url="{{ route('google.redirect') }}"
                                            data-google-link
                                            class="cta cta-primary"
                                        >
                                            Conectar con Google
                                        </a>
                                    @else
                                        <button type="button" class="cta cta-primary" data-start-trial>Iniciar prueba de 7 dias gratis</button>

                                        @if ($paypalEnabled)
                                            <div class="paypal-shell">
                                                <div id="paypal-button-container"></div>
                                            </div>
                                        @else
                                            <button type="button" class="paypal-placeholder" disabled>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 7V5a4 4 0 0 1 8 0v2"></path><rect x="5" y="7" width="14" height="12" rx="2"></rect><path d="M9 11h6"></path></svg>
                                                <span>PayPal</span>
                                            </button>
                                        @endif
                                    @endif
                                </div>

                                <span class="checkout-chip" data-checkout-chip @unless($checkoutReady) hidden @endunless>
                                    Metodo listo: {{ ($pendingSetup['checkout_type'] ?? null) === 'paypal' ? 'PayPal' : 'Prueba de 7 dias' }}
                                </span>
                                <div class="status" data-payment-status></div>
                            </div>
                        </div>
                    </section>
                </section>
            </div>
        </div>
    </div>

    <div class="add-ons-modal" data-add-ons-modal hidden>
        <section class="card card-muted add-ons-modal__dialog" role="dialog" aria-modal="true" aria-label="Agregar al plan">
            <div class="add-ons-modal__head">
                <div class="card-title">
                    <h2>Agregar al plan</h2>
                    <p class="helper-text">Usuarios extra, sucursales y sus totales.</p>
                </div>
                <button type="button" class="add-ons-modal__close" data-close-addons-button>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12"></path><path d="M18 6 6 18"></path></svg>
                </button>
            </div>

            <div class="counter-group">
                <article class="counter-card">
                    <div class="counter-card__head">
                        <div>
                            <span>Usuarios extra</span>
                            <strong data-extra-users>{{ $packageData['extra_users'] }}</strong>
                        </div>
                        <div class="addon-copy">{{ $currencySymbol }}{{ number_format($packageData['extra_user_price'], 2) }} por usuario</div>
                    </div>

                    <p class="counter-note" data-users-note>El plan incluye {{ $packageData['included_users'] }} usuarios. Desde el usuario {{ $packageData['included_users'] + 1 }} se suma este valor mensual.</p>

                    <div class="counter">
                        <button type="button" class="counter-btn" data-decrease-users>-</button>
                        <input
                            type="number"
                            min="0"
                            step="1"
                            value="{{ $packageData['extra_users'] }}"
                            class="counter-input"
                            data-extra-users-input
                        >
                        <button type="button" class="counter-btn" data-increase-users>+</button>
                    </div>
                </article>

                <article class="counter-card" data-branches-card @unless($selectedBusinessType['supports_branches']) hidden @endunless>
                    <div class="counter-card__head">
                        <div>
                            <span>Sucursales extra</span>
                            <strong data-extra-branches>{{ $packageData['extra_branches'] }}</strong>
                        </div>
                        <div class="addon-copy">{{ $currencySymbol }}{{ number_format($packageData['extra_branch_price'], 2) }} por sucursal</div>
                    </div>

                    <p class="counter-note" data-branches-note>El plan incluye {{ $packageData['included_branches'] }} sucursal base. Cada sucursal adicional aumenta el total mensual.</p>

                    <div class="counter">
                        <button type="button" class="counter-btn" data-decrease-branches>-</button>
                        <input
                            type="number"
                            min="0"
                            step="1"
                            value="{{ $packageData['extra_branches'] }}"
                            class="counter-input"
                            data-extra-branches-input
                        >
                        <button type="button" class="counter-btn" data-increase-branches>+</button>
                    </div>
                </article>
            </div>

            <div class="summary">
                <div class="summary-row">
                    <span>Total por usuarios</span>
                    <strong data-extra-users-amount>{{ $currencySymbol }}{{ number_format($packageData['extra_users'] * $packageData['extra_user_price'], 2) }}</strong>
                </div>
                <div class="summary-row" data-branches-summary-row @unless($selectedBusinessType['supports_branches']) hidden @endunless>
                    <span>Total por sucursales</span>
                    <strong data-extra-branches-amount>{{ $currencySymbol }}{{ number_format($packageData['extra_branches'] * $packageData['extra_branch_price'], 2) }}</strong>
                </div>
                <div class="summary-row total">
                    <span>Total final</span>
                    <strong data-total-amount>{{ $currencySymbol }}{{ number_format($packageData['monthly_total'], 2) }}</strong>
                </div>
            </div>
        </section>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    @if ($hasGoogleOnboarding && $paypalEnabled)
        <script src="https://www.paypal.com/sdk/js?client-id={{ urlencode($paypalClientId) }}&vault=true&intent=subscription&components=buttons&currency={{ urlencode($packageData['currency']) }}&disable-funding=credit,card"></script>
    @endif
    <script>
        $(function () {
            var businessTypes = @json(array_values($businessTypes));
            var pendingSetup = @json($pendingSetup);
            var businessTypeMap = {};

            businessTypes.forEach(function (item) {
                businessTypeMap[String(item.id)] = item;
            });

            var state = {
                packageId: Number($('[data-package-id]').val() || {{ (int) $packageData['id'] }}),
                users: Number($('[data-users-hidden]').val() || {{ (int) $packageData['selected_users'] }}),
                branches: Number($('[data-branches-hidden]').val() || {{ (int) $packageData['selected_branches'] }}),
                typeId: Number($('[data-type-hidden]').val() || {{ $selectedTypeId }}),
                includedUsers: {{ (int) $packageData['included_users'] }},
                includedBranchesBase: {{ (int) ($packageData['default_included_branches'] ?? 1) }},
                basePrice: {{ json_encode((float) $packageData['base_price']) }},
                extraUserPrice: {{ json_encode((float) $packageData['extra_user_price']) }},
                extraBranchPriceBase: {{ json_encode((float) ($packageData['default_extra_branch_price'] ?? 3)) }},
                currencySymbol: @json($currencySymbol),
                supportsBranches: {{ ($selectedBusinessType['supports_branches'] ?? false) ? 'true' : 'false' }},
                requiresDefaultEntities: {{ ($selectedBusinessType['requires_default_entities'] ?? false) ? 'true' : 'false' }},
                canPay: {{ $hasGoogleOnboarding && $paypalEnabled ? 'true' : 'false' }},
                canStartTrial: {{ $hasGoogleOnboarding ? 'true' : 'false' }},
                checkoutReady: false,
                checkoutType: null
            };

            var $extraUsersInput = $('[data-extra-users-input]');
            var $extraBranchesInput = $('[data-extra-branches-input]');
            var $usersHidden = $('[data-users-hidden]');
            var $branchesHidden = $('[data-branches-hidden]');
            var $typeHidden = $('[data-type-hidden]');
            var $typeCards = $('[data-type-card]');
            var $decreaseUsersButton = $('[data-decrease-users]');
            var $increaseUsersButton = $('[data-increase-users]');
            var $decreaseBranchesButton = $('[data-decrease-branches]');
            var $increaseBranchesButton = $('[data-increase-branches]');
            var $extraUsers = $('[data-extra-users]');
            var $extraBranches = $('[data-extra-branches]');
            var $extraUsersAmount = $('[data-extra-users-amount]');
            var $extraBranchesAmount = $('[data-extra-branches-amount]');
            var $totalAmount = $('[data-total-amount]');
            var $totalAmountMain = $('[data-total-amount-main]');
            var $status = $('[data-payment-status]');
            var $trialButton = $('[data-start-trial]');
            var $checkoutChip = $('[data-checkout-chip]');
            var $companyForm = $('[data-company-form]');
            var $toggleAddons = $('[data-toggle-addons]');
            var $addOnsModal = $('[data-add-ons-modal]');
            var $closeAddonsButton = $('[data-close-addons-button]');
            var $branchesCard = $('[data-branches-card]');
            var $branchesSummaryRow = $('[data-branches-summary-row]');
            var $planHeadline = $('[data-plan-headline]');
            var $planDescription = $('[data-plan-description]');
            var $planSecondary = $('[data-plan-secondary]');
            var $planFeatureList = $('[data-plan-feature-list]');
            var $planPricePill = $('[data-plan-price-pill]');
            var $planUsersCopy = $('[data-plan-users-copy]');
            var $usersNote = $('[data-users-note]');
            var $branchesNote = $('[data-branches-note]');
            var $flowShell = $('[data-flow-shell]');
            var $flowTitle = $('[data-flow-title]');
            var $flowCopy = $('[data-flow-copy]');
            var $continueCreate = $('[data-continue-create]');
            var $selectPanel = $('.flow-panel--select');
            var $configPanel = $('.flow-panel--config');
            var $createPanel = $('.flow-panel--create');
            var $progressSteps = $('.progress-step');
            var $progressConnectors = $('[data-step-connector]');
            var $fieldBranches = $('[data-field-branches]');
            var $fieldDefaultClient = $('[data-field-default-client]');
            var $fieldDefaultEmployee = $('[data-field-default-employee]');
            var $branchInput = $('#branch_name');
            var $defaultClientInput = $('#default_client_name');
            var $defaultEmployeeInput = $('#default_employee_name');
            var stepOrder = ['select', 'config', 'create'];

            function closeAddonsModal() {
                $addOnsModal.removeClass('is-open').prop('hidden', true);
                $('body').removeClass('modal-open');
                window.requestAnimationFrame(syncFlowHeight);
            }

            function openAddonsModal() {
                $addOnsModal.prop('hidden', false).addClass('is-open');
                $('body').addClass('modal-open');
            }

            function currentBusinessType() {
                return businessTypeMap[String(state.typeId)] || businessTypes[0] || null;
            }

            function syncFlowHeight() {
                var activeStep = $flowShell.attr('data-step') || 'select';
                var $activePanel = $selectPanel;

                if (activeStep === 'config') {
                    $activePanel = $configPanel;
                }

                if (activeStep === 'create') {
                    $activePanel = $createPanel;
                }

                $flowShell.css('height', $activePanel.outerHeight() + 'px');
            }

            function setStep(step) {
                var nextStep = stepOrder.indexOf(step) >= 0 ? step : 'select';
                var selectedType = currentBusinessType();
                var flowTitle = '';
                var flowDescription = '';

                if (nextStep === 'select') {
                    flowTitle = 'Escoge el rubro';
                    flowDescription = 'Selecciona el rubro para ajustar tu plan y activar el acceso inicial.';
                } else if (nextStep === 'config') {
                    flowTitle = 'Configura tu plan';
                    flowDescription = 'Ajusta usuarios y sucursales para ' + ((selectedType && selectedType.name) ? selectedType.name : 'tu negocio') + '.';
                } else {
                    flowTitle = 'Crear empresa';
                    flowDescription = 'Primero valida PayPal o prueba gratis y luego completa los datos para terminar el alta.';
                }

                $flowShell.attr('data-step', nextStep);
                $flowTitle.text(flowTitle);
                $flowCopy.text(flowDescription);

                if (nextStep !== 'config') {
                    closeAddonsModal();
                }

                $progressSteps.removeClass('is-active is-completed');
                $progressConnectors.removeClass('is-completed');
                var stepIndex = stepOrder.indexOf(nextStep);

                $progressSteps.each(function (index) {
                    if (index < stepIndex) {
                        $(this).addClass('is-completed');
                    } else if (index === stepIndex) {
                        $(this).addClass('is-active');
                    }
                });

                $progressConnectors.each(function () {
                    var connectorIndex = Number($(this).data('step-connector'));
                    if (connectorIndex <= stepIndex) {
                        $(this).addClass('is-completed');
                    }
                });

                window.requestAnimationFrame(syncFlowHeight);
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function money(value) {
                return state.currencySymbol + Number(value).toFixed(2);
            }

            function clampExtra(value) {
                var amount = parseInt(value, 10);

                if (isNaN(amount) || amount < 0) {
                    return 0;
                }

                return amount;
            }

            function setStatus(message, type) {
                $status.removeClass('error success').text(message || '');

                if (type) {
                    $status.addClass(type);
                }

                syncFlowHeight();
            }

            function syncCheckoutVisuals() {
                var label = state.checkoutType === 'paypal' ? 'Metodo listo: PayPal' : 'Metodo listo: Prueba de 7 dias';
                $checkoutChip.prop('hidden', !state.checkoutReady);
                $checkoutChip.text(label);
            }

            function ensureCompanyFormValid() {
                if (!($companyForm.length && $companyForm[0])) {
                    return false;
                }

                if (!$companyForm[0].checkValidity()) {
                    $companyForm[0].reportValidity();
                    return false;
                }

                return true;
            }

            function submitCompanyFormAfterCheckout() {
                if (!state.checkoutReady) {
                    return;
                }

                if (!($companyForm.length && $companyForm[0])) {
                    return;
                }

                $companyForm[0].submit();
            }

            function setCheckoutReady(checkoutType, message) {
                state.checkoutReady = true;
                state.checkoutType = checkoutType;
                syncCheckoutVisuals();
                setStatus(message || 'Metodo de pago validado. Ya puedes crear tu empresa.', 'success');
            }

            function clearCheckoutReady() {
                state.checkoutReady = false;
                state.checkoutType = null;
                syncCheckoutVisuals();
            }

            function syncCreateFields() {
                $fieldBranches.prop('hidden', !state.supportsBranches);
                $fieldDefaultClient.prop('hidden', !state.requiresDefaultEntities);
                $fieldDefaultEmployee.prop('hidden', !state.requiresDefaultEntities);

                $branchInput.prop('required', state.supportsBranches);
                $defaultClientInput.prop('required', state.requiresDefaultEntities);
                $defaultEmployeeInput.prop('required', state.requiresDefaultEntities);
            }

            function renderBusinessType() {
                var type = currentBusinessType();

                if (!type) {
                    return;
                }

                var previousPackageId = state.packageId;
                var packageData = type.package || null;

                if (packageData) {
                    state.packageId = Number(packageData.id || state.packageId);
                    state.includedUsers = Number(packageData.included_users || state.includedUsers);
                    state.includedBranchesBase = Number(packageData.included_branches || 0);
                    state.basePrice = Number(packageData.base_price || state.basePrice);
                    state.extraUserPrice = Number(packageData.extra_user_price || state.extraUserPrice);
                    state.extraBranchPriceBase = Number(packageData.extra_branch_price || 0);

                    if (previousPackageId !== state.packageId) {
                        state.users = state.includedUsers;
                        state.branches = type.supports_branches ? state.includedBranchesBase : 0;
                    }
                }

                state.supportsBranches = !!type.supports_branches;
                state.requiresDefaultEntities = !!type.requires_default_entities;

                $typeHidden.val(type.id);
                $('[data-package-id]').val(state.packageId);
                $typeCards.each(function () {
                    var $card = $(this);
                    $card.toggleClass('is-active', Number($card.data('type-id')) === Number(type.id));
                });

                $planHeadline.text(type.headline || 'Plan');
                $planDescription.text(type.description || '');
                $planSecondary.text(type.secondary_description || '');
                $planPricePill.text(money(state.basePrice).replace('.00', '') + ' por mes');
                $planUsersCopy.text(state.includedUsers + ' usuarios incluidos');
                $usersNote.text('El plan incluye ' + state.includedUsers + ' usuarios. Desde el usuario ' + (state.includedUsers + 1) + ' se suma este valor mensual.');

                var fixedFeatures = Array.isArray(type.features) && type.features.length ? type.features : [
                    'Accesos incluidos para este plan',
                    'Gestion operativa desde xNova',
                    'Reportes basicos'
                ];

                var featureItems = fixedFeatures.map(function (feature) {
                    return '<li><span class="plan-feature-icon">✓</span><span>' + escapeHtml(feature) + '</span></li>';
                }).join('');

                $planFeatureList.html(featureItems);

                if (state.supportsBranches) {
                    if (state.branches <= 0) {
                        state.branches = state.includedBranchesBase;
                    }

                    $branchesCard.prop('hidden', false);
                    $branchesSummaryRow.prop('hidden', false);
                    $branchesNote.text('El plan incluye ' + state.includedBranchesBase + ' sucursal base. Cada sucursal adicional aumenta el total mensual.');
                } else {
                    state.branches = 0;
                    $branchesCard.prop('hidden', true);
                    $branchesSummaryRow.prop('hidden', true);
                    $extraBranchesInput.val(0);
                    $branchesHidden.val(0);
                    $extraBranches.text('0');
                    $extraBranchesAmount.text(money(0));
                }

                syncCreateFields();
            }

            function calculate(users, branches) {
                var includedBranches = state.supportsBranches ? state.includedBranchesBase : 0;
                var selectedUsers = Math.max(state.includedUsers, parseInt(users, 10) || state.includedUsers);
                var selectedBranches = state.supportsBranches
                    ? Math.max(includedBranches, parseInt(branches, 10) || includedBranches)
                    : 0;
                var extraUsers = Math.max(0, selectedUsers - state.includedUsers);
                var extraBranches = state.supportsBranches ? Math.max(0, selectedBranches - includedBranches) : 0;
                var extraUsersAmount = extraUsers * state.extraUserPrice;
                var extraBranchesAmount = extraBranches * (state.supportsBranches ? state.extraBranchPriceBase : 0);

                return {
                    selectedUsers: selectedUsers,
                    selectedBranches: selectedBranches,
                    extraUsers: extraUsers,
                    extraBranches: extraBranches,
                    extraUsersAmount: extraUsersAmount,
                    extraBranchesAmount: extraBranchesAmount,
                    total: state.basePrice + extraUsersAmount + extraBranchesAmount
                };
            }

            function getSummary() {
                var summary = calculate(state.users, state.branches);
                state.users = summary.selectedUsers;
                state.branches = summary.selectedBranches;

                return summary;
            }

            function syncUi() {
                var summary = getSummary();
                var disableUsersDecrease = summary.extraUsers <= 0;
                var disableBranchesDecrease = summary.extraBranches <= 0;

                $extraUsersInput.val(summary.extraUsers);
                $extraBranchesInput.val(summary.extraBranches);
                $usersHidden.val(summary.selectedUsers);
                $branchesHidden.val(summary.selectedBranches);
                $extraUsers.text(summary.extraUsers);
                $extraBranches.text(summary.extraBranches);
                $extraUsersAmount.text(money(summary.extraUsersAmount));
                $extraBranchesAmount.text(money(summary.extraBranchesAmount));
                $totalAmount.text(money(summary.total));
                $totalAmountMain.text(money(summary.total));
                $decreaseUsersButton.prop('disabled', disableUsersDecrease).toggleClass('is-disabled', disableUsersDecrease);
                $decreaseBranchesButton.prop('disabled', !state.supportsBranches || disableBranchesDecrease).toggleClass('is-disabled', !state.supportsBranches || disableBranchesDecrease);
                $increaseBranchesButton.prop('disabled', !state.supportsBranches).toggleClass('is-disabled', !state.supportsBranches);
                $extraBranchesInput.prop('disabled', !state.supportsBranches);
                syncFlowHeight();
            }

            function messageFrom(xhr, fallback) {
                if (xhr && xhr.responseJSON) {
                    return xhr.responseJSON.message || xhr.responseJSON.details || fallback;
                }

                return fallback;
            }

            function postJson(url, payload) {
                return $.ajax({
                    url: url,
                    method: 'POST',
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }

            function setTrialLoading(isLoading) {
                $trialButton.prop('disabled', isLoading).toggleClass('is-disabled', isLoading);
            }

            function setExtraUsers(extraUsers) {
                state.users = state.includedUsers + clampExtra(extraUsers);
                clearCheckoutReady();
                syncUi();
            }

            function setExtraBranches(extraBranches) {
                state.branches = state.supportsBranches ? state.includedBranchesBase + clampExtra(extraBranches) : 0;
                clearCheckoutReady();
                syncUi();
            }

            function hydratePendingCheckout() {
                if (!pendingSetup || typeof pendingSetup !== 'object') {
                    return;
                }

                var sameSelection = Number(pendingSetup.package_id || 0) === state.packageId
                    && Number(pendingSetup.type_id || 0) === state.typeId
                    && Number(pendingSetup.selected_users || 0) === state.users
                    && Number(pendingSetup.selected_branches || 0) === state.branches;

                if (sameSelection) {
                    setCheckoutReady(String(pendingSetup.checkout_type || 'trial'), 'Metodo de pago validado. Ya puedes crear tu empresa.');
                }
            }

            $typeCards.on('click', function () {
                state.typeId = Number($(this).data('type-id')) || state.typeId;
                renderBusinessType();
                clearCheckoutReady();
                syncUi();
                setStep('config');
            });

            $continueCreate.on('click', function () {
                setStep('create');
            });

            $progressSteps.on('click', function () {
                var targetStep = String($(this).data('step-indicator') || 'select');
                if (stepOrder.indexOf(targetStep) === -1) {
                    return;
                }
                setStep(targetStep);
            });

            $toggleAddons.on('click', function () {
                openAddonsModal();
            });

            $closeAddonsButton.on('click', function () {
                closeAddonsModal();
            });

            $addOnsModal.on('click', function (event) {
                if (event.target === this) {
                    closeAddonsModal();
                }
            });

            $(document).on('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAddonsModal();
                }
            });

            $decreaseUsersButton.on('click', function () {
                setExtraUsers(clampExtra($extraUsersInput.val()) - 1);
            });

            $increaseUsersButton.on('click', function () {
                setExtraUsers(clampExtra($extraUsersInput.val()) + 1);
            });

            $decreaseBranchesButton.on('click', function () {
                setExtraBranches(clampExtra($extraBranchesInput.val()) - 1);
            });

            $increaseBranchesButton.on('click', function () {
                setExtraBranches(clampExtra($extraBranchesInput.val()) + 1);
            });

            $extraUsersInput.on('input change', function () {
                setExtraUsers($(this).val());
            });

            $extraBranchesInput.on('input change', function () {
                setExtraBranches($(this).val());
            });

            $('[data-google-link]').on('click', function (event) {
                event.preventDefault();

                var params = new URLSearchParams({
                    users: state.users,
                    branches: state.branches,
                    type_id: state.typeId
                });

                window.location.href = $(this).data('base-url') + '?' + params.toString();
            });

            $trialButton.on('click', function () {
                if (!state.canStartTrial) {
                    return;
                }

                if (!ensureCompanyFormValid()) {
                    setStatus('Completa primero los datos de la empresa para continuar.', 'error');
                    return;
                }

                setTrialLoading(true);
                setStatus('Activando tu prueba...');

                postJson(@json(route('register.trial')), {
                    package_id: state.packageId,
                    users: state.users,
                    branches: state.branches,
                    type_id: state.typeId
                }).done(function (response) {
                    setCheckoutReady('trial', response.message || 'Prueba activada. Ya puedes crear tu empresa.');
                    submitCompanyFormAfterCheckout();
                }).fail(function (xhr) {
                    setStatus(messageFrom(xhr, 'No fue posible activar la prueba.'), 'error');
                }).always(function () {
                    setTrialLoading(false);
                });
            });

            $companyForm.on('submit', function (event) {
                if (!state.checkoutReady) {
                    event.preventDefault();
                    setStep('create');
                    setStatus('Antes de crear la empresa debes validar PayPal o activar la prueba de 7 dias.', 'error');
                }
            });

            renderBusinessType();
            syncUi();
            closeAddonsModal();
            syncCheckoutVisuals();
            hydratePendingCheckout();
            setStep($flowShell.attr('data-step'));
            $(window).on('resize load', syncFlowHeight);

            if (!state.canPay || !$('#paypal-button-container').length || typeof paypal === 'undefined') {
                return;
            }

            paypal.Buttons({
                fundingSource: paypal.FUNDING.PAYPAL,
                style: {
                    shape: 'pill',
                    color: 'silver',
                    label: 'paypal',
                    height: 48
                },
                createSubscription: function () {
                    if (!ensureCompanyFormValid()) {
                        setStatus('Completa primero los datos de la empresa para continuar.', 'error');
                        return $.Deferred().reject('form_invalid').promise();
                    }

                    setStatus('Abriendo PayPal...');

                    return postJson(@json(route('register.paypal.subscription')), {
                        package_id: state.packageId,
                        users: state.users,
                        branches: state.branches,
                        type_id: state.typeId
                    }).then(function (response) {
                        if (!response.id) {
                            setStatus('PayPal no devolvio una suscripcion valida.', 'error');
                            return $.Deferred().reject('missing_subscription_id').promise();
                        }

                        setStatus('Confirma la suscripcion en PayPal.');
                        return response.id;
                    }, function (xhr) {
                        setStatus(messageFrom(xhr, 'No fue posible abrir PayPal.'), 'error');
                        return $.Deferred().reject('create_failed').promise();
                    });
                },
                onApprove: function (data) {
                    setStatus('Confirmando tu pago...');

                    return postJson(@json(route('register.paypal.approve')), {
                        subscription_id: data.subscriptionID,
                        package_id: state.packageId,
                        users: state.users,
                        branches: state.branches,
                        type_id: state.typeId
                    }).then(function (response) {
                        setCheckoutReady('paypal', response.message || 'Pago confirmado. Ya puedes crear tu empresa.');
                        submitCompanyFormAfterCheckout();
                    }, function (xhr) {
                        setStatus(messageFrom(xhr, 'No pudimos confirmar el pago.'), 'error');
                        return $.Deferred().reject('approve_failed').promise();
                    });
                },
                onCancel: function () {
                    setStatus('El pago se cancelo. Puedes intentarlo de nuevo.', 'error');
                },
                onError: function () {
                    setStatus('PayPal no pudo completar el proceso.', 'error');
                }
            }).render('#paypal-button-container').then(function () {
                syncFlowHeight();
            });
        });
    </script>
</body>
</html>
