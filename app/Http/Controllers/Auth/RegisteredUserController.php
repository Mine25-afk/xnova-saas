<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Paquete;
use App\Models\XnovaTipo;
use App\Services\OnboardingProvisioningService;
use App\Services\PayPalSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected PayPalSubscriptionService $payPalSubscriptionService,
        protected OnboardingProvisioningService $onboardingProvisioningService
    ) {
    }

    // Esta accion se usa en:
    // routes/auth.php -> route('register') y route('register.package')
    // Vista que devuelve:
    // resources/views/auth/register-package.blade.php
    public function package(Request $request): View
    {
        // Si Google regreso datos en la sesion temporal, los movemos a la sesion
        // fija del registro para poder seguir usando el flujo.
        if ($request->session()->has('google_onboarding')) {
            $request->session()->put('register.google_onboarding', $request->session()->get('google_onboarding'));
        }

        $googleOnboarding = $request->session()->get('register.google_onboarding');
        $pendingSetup = $request->session()->get('register.pending_setup');

        if (! is_array($googleOnboarding)) {
            $googleOnboarding = null;
        }

        if (! is_array($pendingSetup)) {
            $pendingSetup = null;
        }

        $businessTypes = $this->availableBusinessTypes();
        $selectedBusinessType = $this->resolveBusinessType($businessTypes, (int) $request->integer(
            'type_id',
            (int) $request->session()->get('register.selected_type_id', (int) ($businessTypes->first()['id'] ?? 0))
        ));

        $request->session()->put('register.selected_type_id', $selectedBusinessType['id']);

        $package = $this->activePackageQueryForBusinessType($selectedBusinessType)
            ->firstOrFail();

        $selectedUsers = (int) $request->integer(
            'users',
            (int) $request->session()->get('register.selected_users', (int) $package->usuarios_incluidos)
        );

        $selectedBranches = (int) $request->integer(
            'branches',
            (int) $request->session()->get('register.selected_branches', (int) ($package->sucursales_incluidas ?? 1))
        );

        $pricing = $this->buildPricing($package, $selectedBusinessType, $selectedUsers, $selectedBranches);

        // Guardamos la seleccion para que sobreviva si pasa por Google o por PayPal.
        $request->session()->put('register.selected_users', $pricing['selected_users']);
        $request->session()->put('register.selected_branches', $pricing['selected_branches']);

        return view('auth.register-package', [
            'googleOnboarding' => $googleOnboarding,
            'packageData' => [
                'id' => $package->id,
                'name' => $package->nombre,
                'base_price' => $pricing['base_price'],
                'currency' => $package->moneda ?: config('services.paypal.currency', 'USD'),
                'included_users' => $pricing['included_users'],
                'selected_users' => $pricing['selected_users'],
                'extra_users' => $pricing['extra_users'],
                'extra_user_price' => $pricing['extra_user_price'],
                'included_branches' => $pricing['included_branches'],
                'selected_branches' => $pricing['selected_branches'],
                'extra_branches' => $pricing['extra_branches'],
                'extra_branch_price' => $pricing['extra_branch_price'],
                'default_included_branches' => max(1, (int) ($package->sucursales_incluidas ?? 1)),
                'default_extra_branch_price' => (float) ($package->precio_sucursal_extra ?? 3),
                'monthly_total' => $pricing['monthly_total'],
                'selected_type_id' => $selectedBusinessType['id'],
                'selected_type_name' => $selectedBusinessType['name'],
                'selected_type_variant' => $selectedBusinessType['variant'],
                'supports_branches' => $selectedBusinessType['supports_branches'],
                'paypal_plan_id' => $package->paypal_plan_id ?: config('services.paypal.plan_id'),
            ],
            'businessTypes' => $businessTypes->values()->all(),
            'selectedBusinessType' => $selectedBusinessType,
            'pendingSetup' => $pendingSetup,
            'paypalClientId' => (string) config('services.paypal.client_id'),
            'paypalEnabled' => filled(config('services.paypal.client_id'))
                && filled($package->paypal_plan_id ?: config('services.paypal.plan_id')),
            'trialDays' => 7,
        ]);
    }

    // Esta accion se usa en:
    // routes/auth.php -> route('register.paypal.subscription')
    // La llama el JavaScript de la vista auth.register-package.
    public function createPaypalSubscription(Request $request): JsonResponse
    {
        $googleOnboarding = $request->session()->get('register.google_onboarding');

        if (! is_array($googleOnboarding) || blank($googleOnboarding['email'] ?? null)) {
            return response()->json(['message' => 'Primero conecta tu cuenta con Google.'], 422);
        }

        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'min:1'],
            'users' => ['required', 'integer', 'min:1'],
            'branches' => ['required', 'integer', 'min:0'],
            'type_id' => ['required', 'integer', Rule::exists('xNova_Tipo', 'id')],
        ]);

        $businessType = $this->resolveBusinessType($this->availableBusinessTypes(), (int) $validated['type_id']);
        $package = $this->activePackageQueryForBusinessType($businessType)
            ->whereKey((int) $validated['package_id'])
            ->firstOrFail();

        $pricing = $this->buildPricing($package, $businessType, (int) $validated['users'], (int) $validated['branches']);
        $pricing['currency'] = $package->moneda ?: config('services.paypal.currency', 'USD');

        try {
            $paypalSubscription = $this->payPalSubscriptionService->createSubscription(
                $package,
                $pricing['selected_users'],
                $pricing['selected_branches'],
                $pricing
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No fue posible abrir PayPal en este momento.',
                'details' => $exception->getMessage(),
            ], 422);
        }

        $request->session()->put('register.selected_users', $pricing['selected_users']);
        $request->session()->put('register.selected_branches', $pricing['selected_branches']);
        $request->session()->put('register.selected_type_id', $businessType['id']);

        return response()->json([
            'id' => $paypalSubscription['id'] ?? null,
            'status' => $paypalSubscription['status'] ?? null,
        ]);
    }

    // Esta accion se usa en:
    // routes/auth.php -> route('register.paypal.approve')
    // La llama el JavaScript despues de aprobar en PayPal.
    public function approvePaypalSubscription(Request $request): JsonResponse
    {
        $googleOnboarding = $request->session()->get('register.google_onboarding');

        if (! is_array($googleOnboarding) || blank($googleOnboarding['email'] ?? null)) {
            return response()->json(['message' => 'Tu sesion de Google ya no esta disponible.'], 422);
        }

        $validated = $request->validate([
            'subscription_id' => ['required', 'string'],
            'package_id' => ['required', 'integer', 'min:1'],
            'users' => ['required', 'integer', 'min:1'],
            'branches' => ['required', 'integer', 'min:0'],
            'type_id' => ['required', 'integer', Rule::exists('xNova_Tipo', 'id')],
        ]);

        $businessType = $this->resolveBusinessType($this->availableBusinessTypes(), (int) $validated['type_id']);
        $package = $this->activePackageQueryForBusinessType($businessType)
            ->whereKey((int) $validated['package_id'])
            ->firstOrFail();

        $pricing = $this->buildPricing($package, $businessType, (int) $validated['users'], (int) $validated['branches']);
        $currency = $package->moneda ?: config('services.paypal.currency', 'USD');

        try {
            $paypalSubscription = $this->payPalSubscriptionService->getSubscription($validated['subscription_id']);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No pudimos confirmar la suscripcion en PayPal.',
                'details' => $exception->getMessage(),
            ], 422);
        }

        $request->session()->put('register.selected_users', $pricing['selected_users']);
        $request->session()->put('register.selected_branches', $pricing['selected_branches']);
        $request->session()->put('register.selected_type_id', $businessType['id']);

        $status = Str::lower((string) ($paypalSubscription['status'] ?? 'approval_pending'));
        $activatedAt = now()->toIso8601String();

        if (in_array($status, ['active', 'approved', 'approval_pending'], true)) {
            $possibleDate = data_get($paypalSubscription, 'status_update_time') ?? data_get($paypalSubscription, 'create_time');

            if (filled($possibleDate)) {
                try {
                    $activatedAt = Carbon::parse($possibleDate)->toIso8601String();
                } catch (Throwable) {
                    $activatedAt = now()->toIso8601String();
                }
            }
        }

        $periodStart = null;
        $startDate = data_get($paypalSubscription, 'start_time') ?? data_get($paypalSubscription, 'create_time');

        if (filled($startDate)) {
            try {
                $periodStart = Carbon::parse($startDate)->toIso8601String();
            } catch (Throwable) {
                $periodStart = null;
            }
        }

        $periodEnd = null;
        $endDate = data_get($paypalSubscription, 'billing_info.next_billing_time');

        if (filled($endDate)) {
            try {
                $periodEnd = Carbon::parse($endDate)->toIso8601String();
            } catch (Throwable) {
                $periodEnd = null;
            }
        }

        // Este arreglo se vuelve a usar en la siguiente pantalla:
        // resources/views/auth/register-payment-summary.blade.php
        $request->session()->put('register.pending_setup', [
            'checkout_type' => 'paypal',
            'checkout_label' => 'Pago confirmado',
            'package_id' => $package->id,
            'package_name' => $package->nombre,
            'type_id' => $businessType['id'],
            'type_name' => $businessType['name'],
            'type_variant' => $businessType['variant'],
            'supports_branches' => $businessType['supports_branches'],
            'requires_default_entities' => $businessType['requires_default_entities'],
            'provider' => 'paypal',
            'status' => $status,
            'reference' => $validated['subscription_id'],
            'plan_id' => $package->paypal_plan_id ?: config('services.paypal.plan_id'),
            'currency' => $currency,
            'included_users' => $pricing['included_users'],
            'selected_users' => $pricing['selected_users'],
            'extra_users' => $pricing['extra_users'],
            'included_branches' => $pricing['included_branches'],
            'selected_branches' => $pricing['selected_branches'],
            'extra_branches' => $pricing['extra_branches'],
            'base_price' => $pricing['base_price'],
            'extra_user_price' => $pricing['extra_user_price'],
            'extra_branch_price' => $pricing['extra_branch_price'],
            'monthly_total' => $pricing['monthly_total'],
            'activated_at' => $activatedAt,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payload' => $paypalSubscription,
        ]);

        return response()->json([
            'checkout_ready' => true,
            'message' => 'Pago confirmado. Ya puedes crear tu empresa.',
            'redirect_url' => route('register.payment-summary'),
        ]);
    }

    // Esta accion se usa en:
    // routes/auth.php -> route('register.trial')
    // La llama el JavaScript de la vista auth.register-package.
    public function startTrial(Request $request): JsonResponse
    {
        $googleOnboarding = $request->session()->get('register.google_onboarding');

        if (! is_array($googleOnboarding) || blank($googleOnboarding['email'] ?? null)) {
            return response()->json(['message' => 'Primero conecta tu cuenta con Google.'], 422);
        }

        $validated = $request->validate([
            'package_id' => ['required', 'integer', 'min:1'],
            'users' => ['required', 'integer', 'min:1'],
            'branches' => ['required', 'integer', 'min:0'],
            'type_id' => ['required', 'integer', Rule::exists('xNova_Tipo', 'id')],
        ]);

        $businessType = $this->resolveBusinessType($this->availableBusinessTypes(), (int) $validated['type_id']);
        $package = $this->activePackageQueryForBusinessType($businessType)
            ->whereKey((int) $validated['package_id'])
            ->firstOrFail();

        $pricing = $this->buildPricing($package, $businessType, (int) $validated['users'], (int) $validated['branches']);
        $currency = $package->moneda ?: config('services.paypal.currency', 'USD');

        $request->session()->put('register.selected_users', $pricing['selected_users']);
        $request->session()->put('register.selected_branches', $pricing['selected_branches']);
        $request->session()->put('register.selected_type_id', $businessType['id']);

        $startsAt = now();
        $endsAt = now()->addDays(7);

        $request->session()->put('register.pending_setup', [
            'checkout_type' => 'trial',
            'checkout_label' => 'Prueba de 7 dias',
            'package_id' => $package->id,
            'package_name' => $package->nombre,
            'type_id' => $businessType['id'],
            'type_name' => $businessType['name'],
            'type_variant' => $businessType['variant'],
            'supports_branches' => $businessType['supports_branches'],
            'requires_default_entities' => $businessType['requires_default_entities'],
            'provider' => 'trial',
            'status' => 'trialing',
            'reference' => 'trial-'.Str::uuid(),
            'plan_id' => $package->paypal_plan_id ?: config('services.paypal.plan_id'),
            'currency' => $currency,
            'included_users' => $pricing['included_users'],
            'selected_users' => $pricing['selected_users'],
            'extra_users' => $pricing['extra_users'],
            'included_branches' => $pricing['included_branches'],
            'selected_branches' => $pricing['selected_branches'],
            'extra_branches' => $pricing['extra_branches'],
            'base_price' => $pricing['base_price'],
            'extra_user_price' => $pricing['extra_user_price'],
            'extra_branch_price' => $pricing['extra_branch_price'],
            'monthly_total' => $pricing['monthly_total'],
            'activated_at' => $startsAt->toIso8601String(),
            'period_start' => $startsAt->toIso8601String(),
            'period_end' => $endsAt->toIso8601String(),
            'payload' => [
                'mode' => 'trial',
                'trial_days' => 7,
                'trial_ends_at' => $endsAt->toIso8601String(),
            ],
        ]);

        return response()->json([
            'checkout_ready' => true,
            'message' => 'Prueba activada. Ya puedes crear tu empresa.',
            'redirect_url' => route('register.payment-summary'),
        ]);
    }

    // Esta accion se usa en:
    // routes/auth.php -> route('register.payment-summary')
    // Vista que devuelve:
    // resources/views/auth/register-payment-summary.blade.php
    public function paymentSummary(Request $request): View|RedirectResponse
    {
        $pendingSetup = $request->session()->get('register.pending_setup');

        if (! is_array($pendingSetup)) {
            return redirect()
                ->route('register')
                ->withErrors(['payment' => 'Primero elige PayPal o activa la prueba para continuar.']);
        }

        return view('auth.register-payment-summary', [
            'googleOnboarding' => $request->session()->get('register.google_onboarding'),
            'pendingSetup' => $pendingSetup,
        ]);
    }

    // Esta accion se usa en:
    // routes/auth.php -> route('register.payment-summary.store')
    // Aqui ya terminamos creando la empresa, usuario y datos iniciales.
    public function storePaymentSummary(Request $request): RedirectResponse
    {
        $pendingSetup = $request->session()->get('register.pending_setup');
        $googleOnboarding = $request->session()->get('register.google_onboarding');

        if (! is_array($pendingSetup)) {
            return redirect()
                ->route('register')
                ->withErrors(['payment' => 'Tu alta ya no esta disponible. Intenta nuevamente.']);
        }

        if (! is_array($googleOnboarding) || blank($googleOnboarding['email'] ?? null)) {
            return redirect()
                ->route('register')
                ->withErrors(['payment' => 'Necesitamos validar otra vez tu cuenta de Google.']);
        }

        $requiresDefaultEntities = (bool) ($pendingSetup['requires_default_entities'] ?? true);
        $supportsBranches = (bool) ($pendingSetup['supports_branches'] ?? true);

        $rules = [
            'company_name' => ['required', 'string', 'max:255', Rule::unique('xnovas', 'nombre')],
            'user_name' => ['required', 'string', 'max:255'],
            'branch_name' => [$supportsBranches ? 'required' : 'nullable', 'string', 'max:100'],
            'default_client_name' => [$requiresDefaultEntities ? 'required' : 'nullable', 'string', 'max:255'],
            'default_employee_name' => [$requiresDefaultEntities ? 'required' : 'nullable', 'string', 'max:255'],
        ];

        $validated = $request->validate($rules, [
            'company_name.required' => 'El nombre de la empresa es obligatorio.',
            'company_name.unique' => 'Ya existe una empresa con ese nombre. Elige otro.',
            'branch_name.required' => 'El nombre de la sucursal es obligatorio.',
            'user_name.required' => 'El nombre del usuario administrador es obligatorio.',
            'default_client_name.required' => 'El nombre por defecto para los clientes es obligatorio.',
            'default_employee_name.required' => 'El nombre por defecto para los empleados es obligatorio.',
        ]);

        $package = Paquete::query()
            ->where('activo', true)
            ->whereKey((int) $pendingSetup['package_id'])
            ->first();

        if (! $package) {
            $request->session()->forget('register.pending_setup');

            return redirect()
                ->route('register')
                ->withErrors(['payment' => 'Tu seleccion anterior ya no existe. Elige el plan nuevamente.']);
        }

        try {
            $result = $this->onboardingProvisioningService->complete(
                $googleOnboarding,
                $package,
                $pendingSetup,
                $validated
            );
        } catch (Throwable $exception) {
            // Si Laravel ya trae errores de validacion por campo, los dejamos pasar tal cual.
            if ($exception instanceof \Illuminate\Validation\ValidationException) {
                throw $exception;
            }

            // Guardamos un warning con contexto del registro para revisarlo internamente.
            Log::warning('Fallo al completar la configuracion inicial del registro.', [
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
                'google_email' => $googleOnboarding['email'] ?? null,
                'package_id' => $pendingSetup['package_id'] ?? null,
                'provider' => $pendingSetup['provider'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'branch_name' => $validated['branch_name'] ?? null,
                'user_name' => $validated['user_name'] ?? null,
                'selected_users' => $pendingSetup['selected_users'] ?? null,
                'selected_branches' => $pendingSetup['selected_branches'] ?? null,
            ]);

            // Para cualquier otro error, mostramos un mensaje mas claro en la parte superior.
            $errorMessage = 'No se pudo completar la configuracion inicial. ';

            if ($exception instanceof \Illuminate\Database\QueryException) {
                $errorMessage .= 'Parece que hubo un problema al guardar la informacion en la base de datos. ';
                $errorMessage .= 'Revisa si el nombre de la empresa ya existe o intenta nuevamente en unos segundos.';
            } 
            return back()
                ->withInput()
                ->withErrors(['payment' => $errorMessage]);
        }

        Auth::login($result['user'], remember: true);
        $request->session()->regenerate();

        // Limpiamos toda la sesion del flujo de registro para empezar limpio.
        $request->session()->forget([
            'google_onboarding',
            'register.google_onboarding',
            'register.selected_users',
            'register.selected_branches',
            'register.selected_type_id',
            'register.pending_setup',
        ]);

        return redirect(route('dashboard', absolute: false));
    }

    protected function availableBusinessTypes()
    {
        return XnovaTipo::query()
            ->with('modulos:id,nombre,codigo')
            ->orderByRaw("CASE tipo
                WHEN 'Clinica' THEN 1
                WHEN 'Salones' THEN 2
                WHEN 'Barberias' THEN 3
                WHEN 'Catalogos' THEN 4
                WHEN 'Finanzas Personales y en Pareja' THEN 5
                ELSE 99
            END")
            ->get()
            ->map(function (XnovaTipo $type): array {
                $config = $this->buildBusinessTypeConfig($type);
                $config['package'] = $this->packageSummaryForBusinessType($config);

                return $config;
            });
    }

    protected function resolveBusinessType($types, int $selectedTypeId): array
    {
        return $types->firstWhere('id', $selectedTypeId)
            ?? $types->first()
            ?? [
                'id' => 0,
                'name' => 'Clinica',
                'variant' => 'citas',
                'supports_branches' => true,
                'requires_default_entities' => true,
                'headline' => 'Plan Citas',
                'description' => 'Organiza agenda, equipo y reservaciones.',
                'secondary_description' => 'Listo para crecer con clientes, colaboradores y sucursales.',
                'features' => [],
                'module_cards' => [],
                'checkout_title' => 'Personalizar equipo',
                'checkout_description' => 'Configura usuarios y sucursales.',
            ];
    }

    protected function buildPricing(Paquete $package, array $businessType, int $requestedUsers, ?int $requestedBranches = null): array
    {
        $includedUsers = max(1, (int) $package->usuarios_incluidos);
        $selectedUsers = max($includedUsers, $requestedUsers);
        $extraUsers = max(0, $selectedUsers - $includedUsers);
        $supportsBranches = (bool) ($businessType['supports_branches'] ?? true);
        $includedBranches = $supportsBranches ? max(1, (int) ($package->sucursales_incluidas ?? 1)) : 0;
        $selectedBranches = $supportsBranches
            ? max($includedBranches, (int) ($requestedBranches ?? $includedBranches))
            : 0;
        $extraBranches = $supportsBranches ? max(0, $selectedBranches - $includedBranches) : 0;
        $basePrice = (float) $package->precio;
        $extraUserPrice = (float) $package->precio_usuario_extra;
        $extraBranchPrice = $supportsBranches ? (float) ($package->precio_sucursal_extra ?? 3) : 0.0;

        return [
            'base_price' => $basePrice,
            'included_users' => $includedUsers,
            'selected_users' => $selectedUsers,
            'extra_users' => $extraUsers,
            'extra_user_price' => $extraUserPrice,
            'included_branches' => $includedBranches,
            'selected_branches' => $selectedBranches,
            'extra_branches' => $extraBranches,
            'extra_branch_price' => $extraBranchPrice,
            'monthly_total' => $basePrice + ($extraUsers * $extraUserPrice) + ($extraBranches * $extraBranchPrice),
        ];
    }

    protected function activePackageQueryForBusinessType(array $businessType)
    {
        $query = Paquete::query()
            ->where('activo', true)
            ->orderBy('id');

        if (($businessType['variant'] ?? null) === 'finanzas') {
            return $query->whereHas('modulos', fn ($moduleQuery) => $moduleQuery->where('codigo', 'FINANZAS'));
        }

        return $query->whereDoesntHave('modulos', fn ($moduleQuery) => $moduleQuery->where('codigo', 'FINANZAS'));
    }

    protected function packageSummaryForBusinessType(array $businessType): ?array
    {
        $package = $this->activePackageQueryForBusinessType($businessType)->first();

        if (! $package) {
            return null;
        }

        $pricing = $this->buildPricing(
            $package,
            $businessType,
            (int) $package->usuarios_incluidos,
            (int) ($package->sucursales_incluidas ?? 0)
        );

        return [
            'id' => $package->id,
            'base_price' => $pricing['base_price'],
            'included_users' => $pricing['included_users'],
            'selected_users' => $pricing['selected_users'],
            'extra_user_price' => $pricing['extra_user_price'],
            'included_branches' => $pricing['included_branches'],
            'selected_branches' => $pricing['selected_branches'],
            'extra_branch_price' => $pricing['extra_branch_price'],
            'currency' => $package->moneda ?: config('services.paypal.currency', 'USD'),
        ];
    }

    protected function buildBusinessTypeConfig(XnovaTipo $type): array
    {
        $moduleCards = $this->buildModuleCards($type);

        return match ($type->tipo) {
            XnovaTipo::TIPO_FINANZAS => [
                'id' => $type->id,
                'name' => $type->tipo,
                'variant' => 'finanzas',
                'supports_branches' => false,
                'requires_default_entities' => false,
                'headline' => 'Plan Finanzas en Pareja',
                'description' => 'Ordena cuentas, ingresos, gastos fijos y movimientos sin plantillas caras ni hojas dificiles.',
                'secondary_description' => 'Pensado para una persona o pareja que quiere hablar de dinero con informacion clara y compartida.',
                'features' => [
                    '2 usuarios incluidos para trabajar en pareja.',
                    'Cuentas con saldo calculado por movimientos.',
                    'Ingresos y gastos fijos para proyectar el mes.',
                    'Dashboard y reportes financieros simples.',
                ],
                'module_cards' => $moduleCards,
                'checkout_title' => 'Personalizar finanzas',
                'checkout_description' => 'Define quienes administraran las cuentas. No requiere sucursales.',
            ],
            XnovaTipo::TIPO_CATALOGOS => [
                'id' => $type->id,
                'name' => $type->tipo,
                'variant' => 'catalogos',
                'supports_branches' => false,
                'requires_default_entities' => false,
                'headline' => 'Plan Catalogos',
                'description' => 'Publica productos con imagen, precio y estado en un catalogo limpio y ligero.',
                'secondary_description' => 'Pensado para vender por inventario sin la complejidad de sucursales, clientes o empleados base.',
                'features' => [
                    'Inventario con productos activos o inactivos.',
                    'Imagen opcional optimizada en WebP para cada producto.',
                    'Vista previa de reservacion web dentro del modulo.',
                ],
                'module_cards' => $moduleCards,
                'checkout_title' => 'Personalizar acceso',
                'checkout_description' => 'Define cuantos usuarios administraran el catalogo. No requiere sucursales.',
            ],
            XnovaTipo::TIPO_SALONES => [
                'id' => $type->id,
                'name' => $type->tipo,
                'variant' => 'citas',
                'supports_branches' => true,
                'requires_default_entities' => true,
                'headline' => 'Plan Salones',
                'description' => 'Controla agenda, clientes, equipo y reservaciones de belleza con una experiencia simple.',
                'secondary_description' => 'Incluye sucursal base, clientes, empleados y productos visibles en la reservacion web.',
                'features' => [
                    'Agenda diaria y calendario de citas.',
                    'Clientes y colaboradores listos desde el alta.',
                    'Reservacion web para nuevos turnos.',
                    'Productos del salon dentro de inventario.',
                ],
                'module_cards' => $moduleCards,
                'checkout_title' => 'Personalizar equipo',
                'checkout_description' => 'Configura usuarios y sucursales para el salon.',
            ],
            XnovaTipo::TIPO_BARBERIAS => [
                'id' => $type->id,
                'name' => $type->tipo,
                'variant' => 'citas',
                'supports_branches' => true,
                'requires_default_entities' => true,
                'headline' => 'Plan Barberias',
                'description' => 'Administra citas, barberos, clientes y reservaciones publicas sin pasos de sobra.',
                'secondary_description' => 'Ideal para operaciones rapidas con agenda, productos y visibilidad web.',
                'features' => [
                    'Control de turnos y disponibilidad.',
                    'Cliente y empleado general creados desde el inicio.',
                    'Reservacion web con productos activos.',
                    'Inventario con imagen optimizada.',
                ],
                'module_cards' => $moduleCards,
                'checkout_title' => 'Personalizar equipo',
                'checkout_description' => 'Configura usuarios y sucursales para la barberia.',
            ],
            default => [
                'id' => $type->id,
                'name' => $type->tipo,
                'variant' => 'citas',
                'supports_branches' => true,
                'requires_default_entities' => true,
                'headline' => 'Plan Clinicas',
                'description' => 'Organiza agenda, clientes, colaboradores y reservaciones con una base profesional.',
                'secondary_description' => 'Pensado para negocios que necesitan citas, sucursal base y control de equipo.',
                'features' => [
                    'Agenda de citas y calendario mensual.',
                    'Clientes y colaboradores desde el onboarding.',
                    'Reservacion web publica.',
                    'Inventario de productos dentro del plan.',
                ],
                'module_cards' => $moduleCards,
                'checkout_title' => 'Personalizar equipo',
                'checkout_description' => 'Configura usuarios y sucursales para la operacion.',
            ],
        };
    }

    protected function buildModuleCards(XnovaTipo $type): array
    {
        $sortMap = [
            'SEGURIDAD' => 1,
            'CONFIGURACION' => 2,
            'SOCIOS' => 3,
            'CITAS' => 4,
            'INVENTARIO' => 5,
            'FINANZAS' => 6,
        ];

        $modules = $type->modulos
            ->sortBy(fn ($module) => $sortMap[$module->codigo] ?? 99)
            ->values();

        if ($modules->isEmpty()) {
            return [];
        }

        return $modules->map(function ($module): array {
            return [
                'title' => (string) $module->nombre,
                'copy' => $this->moduleCardCopyByCode((string) $module->codigo),
            ];
        })->all();
    }

    protected function moduleCardCopyByCode(string $moduleCode): string
    {
        return match ($moduleCode) {
            'SEGURIDAD' => 'Usuarios, roles y permisos del equipo.',
            'CONFIGURACION' => 'Sucursales y parametros base de operacion.',
            'SOCIOS' => 'Clientes y colaboradores para trabajar el dia a dia.',
            'CITAS' => 'Listado, calendario y seguimiento de citas.',
            'INVENTARIO' => 'Productos, inventario y reservacion web.',
            'FINANZAS' => 'Propietarios, cuentas, fijos, movimientos y reportes.',
            default => 'Accesos incluidos para este tipo de negocio.',
        };
    }
}
