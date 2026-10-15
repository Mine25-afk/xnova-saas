<?php

namespace App\Services;

use App\Models\Paquete;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalSubscriptionService
{
    public function createSubscription(Paquete $package, int $users, int $branches, array $pricing): array
    {
        $planId = $package->paypal_plan_id ?: config('services.paypal.plan_id');

        if (blank($planId)) {
            throw new RuntimeException('No hay un plan de PayPal configurado para este paquete.');
        }

        $plan = $this->getPlan($planId);
        $sequence = $this->resolveRegularBillingSequence($plan);

        $payload = [
            'plan_id' => $planId,
            'custom_id' => Str::limit(
                "xnova-register-package-{$package->id}-users-{$users}-branches-{$branches}-".now()->timestamp,
                127,
                ''
            ),
            'plan' => [
                'billing_cycles' => [
                    [
                        'sequence' => $sequence,
                        'pricing_scheme' => [
                            'fixed_price' => [
                                'currency_code' => $pricing['currency'],
                                'value' => $this->formatAmount($pricing['monthly_total']),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->decodeResponse(
            $this->authorizedRequest()
                ->withHeaders(['Prefer' => 'return=representation'])
                ->post('/v1/billing/subscriptions', $payload),
            'No fue posible crear la suscripcion en PayPal.'
        );
    }

    public function getSubscription(string $subscriptionId): array
    {
        return $this->decodeResponse(
            $this->authorizedRequest()->get("/v1/billing/subscriptions/{$subscriptionId}"),
            'No fue posible consultar la suscripcion en PayPal.'
        );
    }

    public function getPlan(string $planId): array
    {
        $cacheKey = "paypal.plan.{$planId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($planId) {
            return $this->decodeResponse(
                $this->authorizedRequest()->get("/v1/billing/plans/{$planId}"),
                'No fue posible consultar el plan de PayPal.'
            );
        });
    }

    protected function authorizedRequest(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->withToken($this->accessToken());
    }

    protected function accessToken(): string
    {
        $mode = config('services.paypal.mode', 'sandbox');
        $cacheKey = "paypal.access_token.{$mode}";
        $cached = Cache::get($cacheKey);

        if (filled($cached)) {
            return $cached;
        }

        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');

        if (blank($clientId) || blank($clientSecret)) {
            throw new RuntimeException('Faltan las credenciales de PayPal en el archivo de configuracion.');
        }

        $response = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asForm()
            ->timeout(30)
            ->withBasicAuth($clientId, $clientSecret)
            ->post('/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        $payload = $this->decodeResponse($response, 'No fue posible autenticar con PayPal.');
        $accessToken = (string) ($payload['access_token'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('PayPal no devolvio un access token valido.');
        }

        $ttl = max(60, ((int) ($payload['expires_in'] ?? 32400)) - 60);
        Cache::put($cacheKey, $accessToken, now()->addSeconds($ttl));

        return $accessToken;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.paypal.base_url', 'https://api-m.sandbox.paypal.com'), '/');
    }

    protected function resolveRegularBillingSequence(array $plan): int
    {
        $regularCycle = collect($plan['billing_cycles'] ?? [])
            ->first(fn (array $cycle) => ($cycle['tenure_type'] ?? null) === 'REGULAR');

        if ($regularCycle) {
            return (int) ($regularCycle['sequence'] ?? 1);
        }

        $firstCycle = collect($plan['billing_cycles'] ?? [])->first();

        return max(1, (int) ($firstCycle['sequence'] ?? 1));
    }

    protected function decodeResponse(Response $response, string $message): array
    {
        if ($response->failed()) {
            $error = $response->json('message')
                ?? $response->json('details.0.issue')
                ?? $response->body();

            throw new RuntimeException(trim($message.' '.$error));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException($message.' Respuesta inesperada de PayPal.');
        }

        return $payload;
    }

    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
